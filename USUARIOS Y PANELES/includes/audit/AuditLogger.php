<?php
declare(strict_types=1);

/**
 * Audit Logger
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\Audit;

/**
 * AuditLogger class
 */
class AuditLogger {

	/**
	 * Log audit event
	 *
	 * @param string $event   Event name.
	 * @param int    $user_id User ID.
	 * @param array  $context Additional context.
	 */
	public static function log($event, $user_id = null, $context = array()): void {
		$event = sanitize_text_field($event);

		if ($user_id !== null) {
			$user_id = (int) $user_id;
		}

		if (!is_array($context)) {
			$context = array();
		}

		$context_json = wp_json_encode($context);

		$log_message = sprintf(
			'[TVC_AUDIT] %s | %s | %s',
			$event,
			$user_id !== null ? (string) $user_id : 'null',
			$context_json
		);

		// Try database persistence
		global $wpdb;
		$table_name = $wpdb->prefix . 'tvc_audit_logs';

		if (!\TVC\System\SecurityManager::table_exists($table_name)) {
			@error_log('[TVC_AUDIT] Missing table: ' . $table_name);
			// Always write to error_log as fallback
			@error_log($log_message);
			return;
		}

		try {
			$insert_data = array(
				'event'      => $event,
				'user_id'    => $user_id,
				'context'    => $context_json,
				'created_at' => current_time('mysql'),
			);

			$result = $wpdb->insert($table_name, $insert_data);

			if ($result === false) {
				// Log to error_log only, avoid recursive logging
				@error_log('[TVC_AUDIT_DB_ERROR] ' . $wpdb->last_error);
			}
		} catch (\Exception $e) {
			// Database insert failed, continue to error_log fallback
			@error_log('[TVC_AUDIT_DB_EXCEPTION] ' . $e->getMessage());
		}

		// Always write to error_log as fallback
		@error_log($log_message);
	}
}

