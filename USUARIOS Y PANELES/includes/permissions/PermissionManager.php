<?php
declare(strict_types=1);

/**
 * Permission Manager
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\Permissions;

use TVC\Audit\AuditLogger;

/**
 * PermissionManager class
 */
class PermissionManager {

	/**
	 * Available permission blocks
	 *
	 * @var array
	 */
	private static $blocks = array(
		'users',
		'marketplace',
		'finance',
		'support',
		'brain',
		'analytics',
		'system',
		'audit',
		'accreditation',
		'chat',
	);

	/**
	 * Initialize PermissionManager
	 */
	public static function init(): void {
		// Initialization logic pending
	}

	/**
	 * Get available blocks
	 *
	 * @return array
	 */
	public static function get_blocks(): array {
		return self::$blocks;
	}

	/**
	 * Check if user has access to a block
	 *
	 * @param int    $user_id User ID.
	 * @param string $block   Block name.
	 * @return bool
	 */
	public static function user_has_block($user_id, $block): bool {
		global $wpdb;

		$user_id = (int) $user_id;
		$block   = sanitize_text_field($block);

		$user = get_user_by('id', (int) $user_id);

		if (!$user) {
			\TVC\Audit\AuditLogger::log(
				'invalid_user_reference',
				null,
				array('user_id' => $user_id)
			);
			return false;
		}

		if (!in_array($block, self::$blocks, true)) {
			return false;
		}

		$table_name = $wpdb->prefix . 'tvc_user_permissions';

		if (!\TVC\System\SecurityManager::table_exists($table_name)) {
			\TVC\Audit\AuditLogger::log(
				'missing_table_detected',
				$user_id,
				array('table' => $table_name)
			);
			return false;
		}

		$query      = $wpdb->prepare(
			"SELECT granted FROM {$table_name} WHERE user_id = %d AND block_name = %s LIMIT 1",
			$user_id,
			$block
		);

		$result = $wpdb->get_var($query);

		if ($result === null) {
			return false;
		}

		return (bool) $result;
	}

	/**
	 * Grant block permission to user
	 *
	 * @param int    $user_id User ID.
	 * @param string $block   Block name.
	 * @return bool
	 */
	public static function grant_block($user_id, $block): bool {
		global $wpdb;

		$user_id = (int) $user_id;
		$block   = sanitize_text_field($block);

		if (!$user_id || !in_array($block, self::$blocks, true)) {
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

		$table_name = $wpdb->prefix . 'tvc_user_permissions';

		if (!\TVC\System\SecurityManager::table_exists($table_name)) {
			\TVC\Audit\AuditLogger::log(
				'missing_table_detected',
				$user_id,
				array('table' => $table_name)
			);
			return false;
		}

		// Check if record exists
		$query = $wpdb->prepare(
			"SELECT id FROM {$table_name} WHERE user_id = %d AND block_name = %s LIMIT 1",
			$user_id,
			$block
		);

		$existing = $wpdb->get_var($query);

		if ($existing) {
			// Update existing record
			$result = $wpdb->update(
				$table_name,
				array(
					'granted' => 1,
				),
				array(
					'user_id'   => $user_id,
					'block_name' => $block,
				),
				array('%d'),
				array('%d', '%s')
			);

			if ($result === false) {
				\TVC\Audit\AuditLogger::log(
					'db_update_failed',
					$user_id,
					array('error' => $wpdb->last_error, 'table' => $table_name)
				);
				return false;
			}
		} else {
			// Insert new record
			$result = $wpdb->insert(
				$table_name,
				array(
					'user_id'    => $user_id,
					'block_name' => $block,
					'granted'    => 1,
					'created_at' => current_time('mysql'),
				),
				array('%d', '%s', '%d', '%s')
			);

			if ($result === false) {
				\TVC\Audit\AuditLogger::log(
					'db_insert_failed',
					$user_id,
					array('error' => $wpdb->last_error, 'table' => $table_name)
				);
				return false;
			}
		}

		AuditLogger::log('permission_granted', $user_id, array('block' => $block));

		return true;
	}

	/**
	 * Revoke block permission from user
	 *
	 * @param int    $user_id User ID.
	 * @param string $block   Block name.
	 * @return bool
	 */
	public static function revoke_block($user_id, $block): bool {
		global $wpdb;

		$user_id = (int) $user_id;
		$block   = sanitize_text_field($block);

		if (!$user_id || !in_array($block, self::$blocks, true)) {
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

		$table_name = $wpdb->prefix . 'tvc_user_permissions';

		if (!\TVC\System\SecurityManager::table_exists($table_name)) {
			\TVC\Audit\AuditLogger::log(
				'missing_table_detected',
				$user_id,
				array('table' => $table_name)
			);
			return false;
		}

		$result = $wpdb->update(
			$table_name,
			array('granted' => 0),
			array(
				'user_id'   => $user_id,
				'block_name' => $block,
			),
			array('%d'),
			array('%d', '%s')
		);

		if ($result === false) {
			\TVC\Audit\AuditLogger::log(
				'db_update_failed',
				$user_id,
				array('error' => $wpdb->last_error, 'table' => $table_name)
			);
			return false;
		}

		AuditLogger::log('permission_revoked', $user_id, array('block' => $block));

		return true;
	}
}

