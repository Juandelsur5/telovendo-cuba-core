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

		// Atomic update: increment counter only if window is still valid
		$update_query = $wpdb->prepare(
			"UPDATE {$table_name} SET request_count = request_count + 1 WHERE identifier = %s AND TIMESTAMPDIFF(SECOND, window_start, %s) <= 60",
			$identifier,
			$now
		);

		$wpdb->query($update_query);
		$affected_rows = $wpdb->rows_affected;

		if ($affected_rows === 0) {
			// Either window expired OR no row exists - reset or insert
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

		// Check if limit exceeded after atomic increment
		$check_query = $wpdb->prepare(
			"SELECT request_count FROM {$table_name} WHERE identifier = %s LIMIT 1",
			$identifier
		);
		$current_count = (int) $wpdb->get_var($check_query);

		if ($current_count >= self::$rate_limit) {
			AuditLogger::log('rate_limit_exceeded', $user_id ? $user_id : null, array('identifier' => $identifier));
			wp_die('Too many requests');
		}
	}
}

