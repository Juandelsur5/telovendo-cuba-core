<?php
declare(strict_types=1);

/**
 * Request Guard
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\System;

use TVC\Audit\AuditLogger;

/**
 * RequestGuard class
 */
class RequestGuard {

	/**
	 * Rate limit (requests per minute)
	 *
	 * @var int
	 */
	private static $rate_limit = 60;

	/**
	 * Initialize RequestGuard
	 */
	public static function init(): void {
		// Initialization logic pending
	}

	/**
	 * Check rate limit
	 *
	 * @return void
	 */
	public static function check(): void {
		global $wpdb;

		$user_id = get_current_user_id();
		$ip      = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

		if ($user_id) {
			$identifier = 'user_' . $user_id;
		} else {
			$ip_hash    = $ip ? md5($ip) : 'unknown';
			$identifier = 'ip_' . $ip_hash;
		}

		$table_name = $wpdb->prefix . 'tvc_rate_tracking';

		if (!\TVC\System\SecurityManager::table_exists($table_name)) {
			\TVC\Audit\AuditLogger::log(
				'missing_table_detected',
				$user_id ? $user_id : null,
				array('table' => $table_name)
			);
			return;
		}

		$now        = current_time('mysql');

		// Query existing record
		$query = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE identifier = %s LIMIT 1",
			$identifier
		);

		$record = $wpdb->get_row($query, ARRAY_A);

		if (!$record) {
			// Insert new row
			$result = $wpdb->insert(
				$table_name,
				array(
					'identifier'   => $identifier,
					'request_count' => 1,
					'window_start'  => $now,
				),
				array('%s', '%d', '%s')
			);

			if ($result === false) {
				\TVC\Audit\AuditLogger::log(
					'db_insert_failed',
					$user_id ? $user_id : null,
					array('error' => $wpdb->last_error, 'table' => $table_name)
				);
			}
			return;
		}

		// Calculate time difference
		if (!$record || !isset($record['window_start'])) {
			// Reset window safely
			$window_start_str = current_time('mysql');
			$result = $wpdb->update(
				$table_name,
				array(
					'request_count' => 1,
					'window_start'  => $window_start_str,
				),
				array('identifier' => $identifier),
				array('%d', '%s'),
				array('%s')
			);

			if ($result === false) {
				\TVC\Audit\AuditLogger::log(
					'db_update_failed',
					$user_id ? $user_id : null,
					array('error' => $wpdb->last_error, 'table' => $table_name)
				);
			}
			return;
		} else {
			$window_start_str = $record['window_start'];
		}

		$window_start = strtotime($window_start_str);
		if ($window_start === false) {
			\TVC\Audit\AuditLogger::log(
				'invalid_date_detected',
				$user_id ? $user_id : null,
				array('date' => $window_start_str ?? 'unknown')
			);
			// Reset window on invalid date
			$result = $wpdb->update(
				$table_name,
				array(
					'request_count' => 1,
					'window_start'  => $now,
				),
				array('identifier' => $identifier),
				array('%d', '%s'),
				array('%s')
			);

			if ($result === false) {
				\TVC\Audit\AuditLogger::log(
					'db_update_failed',
					$user_id ? $user_id : null,
					array('error' => $wpdb->last_error, 'table' => $table_name)
				);
			}
			return;
		}

		$current_time = strtotime($now);
		if ($current_time === false) {
			\TVC\Audit\AuditLogger::log(
				'invalid_date_detected',
				$user_id ? $user_id : null,
				array('date' => $now)
			);
			// Reset window on invalid current time
			$result = $wpdb->update(
				$table_name,
				array(
					'request_count' => 1,
					'window_start'  => $now,
				),
				array('identifier' => $identifier),
				array('%d', '%s'),
				array('%s')
			);

			if ($result === false) {
				\TVC\Audit\AuditLogger::log(
					'db_update_failed',
					$user_id ? $user_id : null,
					array('error' => $wpdb->last_error, 'table' => $table_name)
				);
			}
			return;
		}

		$diff_seconds = $current_time - $window_start;

		if ($diff_seconds > 60) {
			// Reset counter
			$result = $wpdb->update(
				$table_name,
				array(
					'request_count' => 1,
					'window_start'  => $now,
				),
				array('identifier' => $identifier),
				array('%d', '%s'),
				array('%s')
			);

			if ($result === false) {
				\TVC\Audit\AuditLogger::log(
					'db_update_failed',
					$user_id ? $user_id : null,
					array('error' => $wpdb->last_error, 'table' => $table_name)
				);
			}
			return;
		}

		// Check if limit exceeded
		$current_count = (int) $record['request_count'];
		if ($current_count >= self::$rate_limit) {
			AuditLogger::log('rate_limit_exceeded', $user_id ? $user_id : null, array('identifier' => $identifier));
			wp_die('Too many requests');
		}

		// Increment counter
		$result = $wpdb->update(
			$table_name,
			array('request_count' => $current_count + 1),
			array('identifier' => $identifier),
			array('%d'),
			array('%s')
		);

		if ($result === false) {
			\TVC\Audit\AuditLogger::log(
				'db_update_failed',
				$user_id ? $user_id : null,
				array('error' => $wpdb->last_error, 'table' => $table_name)
			);
		}
	}
}

