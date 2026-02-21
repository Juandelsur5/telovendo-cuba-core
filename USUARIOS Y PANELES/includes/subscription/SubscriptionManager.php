<?php
declare(strict_types=1);

/**
 * Subscription Manager
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\Subscription;

use TVC\Audit\AuditLogger;

/**
 * SubscriptionManager class
 *
 * User meta keys:
 * - tvc_subscription_status
 * - tvc_subscription_start
 * - tvc_subscription_end
 * - tvc_subscription_plan
 */
class SubscriptionManager {

	/**
	 * Initialize SubscriptionManager
	 */
	public static function init(): void {
		// Initialization logic pending
	}

	/**
	 * Check if user subscription is active
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_user_active($user_id): bool {
		$user_id = (int) $user_id;

		if (!$user_id) {
			return false;
		}

		$user = get_user_by('id', (int) $user_id);

		if (!$user) {
			\TVC\Audit\AuditLogger::log(
				'invalid_user_reference',
				null,
				array('user_id' => $user_id)
			);
			return false;
		}

		$status = get_user_meta($user_id, 'tvc_subscription_status', true);
		if ($status !== 'active') {
			return false;
		}

		$end_date = get_user_meta($user_id, 'tvc_subscription_end', true);
		if (empty($end_date)) {
			return false;
		}

		$end_timestamp = strtotime($end_date);
		if ($end_timestamp === false) {
			\TVC\Audit\AuditLogger::log(
				'invalid_date_detected',
				$user_id,
				array('date' => $end_date)
			);
			return false;
		}

		$current_timestamp = current_time('timestamp');

		if ($end_timestamp < $current_timestamp) {
			return false;
		}

		return true;
	}

	/**
	 * Activate subscription for user
	 *
	 * @param int    $user_id User ID.
	 * @param int    $days    Number of days.
	 * @param string $plan    Plan name.
	 * @return bool
	 */
	public static function activate_subscription($user_id, $days, $plan): bool {
		global $wpdb;

		$user_id = (int) $user_id;
		$days    = (int) $days;
		$plan    = sanitize_text_field($plan);

		if (!$user_id || $days <= 0) {
			return false;
		}

		$user = get_user_by('id', (int) $user_id);

		if (!$user) {
			\TVC\Audit\AuditLogger::log(
				'invalid_user_reference',
				null,
				array('user_id' => $user_id)
			);
			return false;
		}

		$start = current_time('mysql');
		$end_timestamp = strtotime("+{$days} days", current_time('timestamp'));
		if ($end_timestamp === false) {
			\TVC\Audit\AuditLogger::log(
				'invalid_date_detected',
				$user_id,
				array('date' => "+{$days} days")
			);
			return false;
		}
		$end = date('Y-m-d H:i:s', $end_timestamp);

		$wpdb->query('START TRANSACTION');

		try {
			update_user_meta($user_id, 'tvc_subscription_status', 'active');
			update_user_meta($user_id, 'tvc_subscription_start', $start);
			update_user_meta($user_id, 'tvc_subscription_end', $end);
			update_user_meta($user_id, 'tvc_subscription_plan', $plan);

			// Insert into subscription history
			$table_name = $wpdb->prefix . 'tvc_subscription_history';

			if (!\TVC\System\SecurityManager::table_exists($table_name)) {
				throw new \Exception('History table missing');
			}

			$result = $wpdb->insert(
				$table_name,
				array(
					'user_id'    => $user_id,
					'plan'       => $plan,
					'started_at' => $start,
					'ended_at'   => $end,
					'status'     => 'active',
				),
				array('%d', '%s', '%s', '%s', '%s')
			);

			if ($result === false) {
				throw new \Exception($wpdb->last_error ?: 'Database insert failed');
			}

			if ($wpdb->last_error) {
				throw new \Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT');

			AuditLogger::log('subscription_activated', $user_id, array('plan' => $plan));

			return true;
		} catch (\Exception $e) {
			$wpdb->query('ROLLBACK');

			\TVC\Audit\AuditLogger::log(
				'subscription_transaction_failed',
				get_current_user_id() ? get_current_user_id() : $user_id,
				array('error' => $e->getMessage(), 'operation' => 'activate')
			);

			return false;
		}
	}

	/**
	 * Cancel subscription for user
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public static function cancel_subscription($user_id): void {
		global $wpdb;

		$user_id = (int) $user_id;

		if (!$user_id) {
			return;
		}

		$user = get_user_by('id', (int) $user_id);

		if (!$user) {
			\TVC\Audit\AuditLogger::log(
				'invalid_user_reference',
				null,
				array('user_id' => $user_id)
			);
			return;
		}

		// Read existing subscription data before canceling
		$plan      = get_user_meta($user_id, 'tvc_subscription_plan', true);
		$started_at = get_user_meta($user_id, 'tvc_subscription_start', true);

		$wpdb->query('START TRANSACTION');

		try {
			update_user_meta($user_id, 'tvc_subscription_status', 'cancelled');

			// Insert into subscription history
			$table_name = $wpdb->prefix . 'tvc_subscription_history';

			if (!\TVC\System\SecurityManager::table_exists($table_name)) {
				throw new \Exception('History table missing');
			}

			$result = $wpdb->insert(
				$table_name,
				array(
					'user_id'    => $user_id,
					'plan'       => $plan ? sanitize_text_field($plan) : '',
					'started_at' => $started_at ? $started_at : current_time('mysql'),
					'ended_at'   => current_time('mysql'),
					'status'     => 'cancelled',
				),
				array('%d', '%s', '%s', '%s', '%s')
			);

			if ($result === false) {
				throw new \Exception($wpdb->last_error ?: 'Database insert failed');
			}

			if ($wpdb->last_error) {
				throw new \Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT');

			AuditLogger::log('subscription_cancelled', $user_id);
		} catch (\Exception $e) {
			$wpdb->query('ROLLBACK');

			\TVC\Audit\AuditLogger::log(
				'subscription_transaction_failed',
				get_current_user_id() ? get_current_user_id() : $user_id,
				array('error' => $e->getMessage(), 'operation' => 'cancel')
			);
		}
	}
}

