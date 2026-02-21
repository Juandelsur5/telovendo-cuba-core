<?php
declare(strict_types=1);

/**
 * Chat Manager
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\Chat;

use TVC\Audit\AuditLogger;

/**
 * ChatManager class
 */
class ChatManager {

	/**
	 * Initialize ChatManager
	 */
	public static function init(): void {
		// Initialization logic pending
	}

	/**
	 * Create conversation structure
	 *
	 * @param int $user_id User ID.
	 * @return array|false
	 */
	public static function create_conversation($user_id) {
		global $wpdb;

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

		$conversation_id = uniqid('tvc_chat_', true);
		$created_at      = current_time('mysql');

		// Insert initial system message
		$table_name = $wpdb->prefix . 'tvc_chat_messages';

		if (!\TVC\System\SecurityManager::table_exists($table_name)) {
			\TVC\Audit\AuditLogger::log(
				'missing_table_detected',
				$user_id,
				array('table' => $table_name)
			);
			return false;
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'conversation_id' => $conversation_id,
				'user_id'         => $user_id,
				'message'         => 'Conversation started',
				'created_at'      => $created_at,
			),
			array('%s', '%d', '%s', '%s')
		);

		if ($result === false) {
			\TVC\Audit\AuditLogger::log(
				'db_insert_failed',
				$user_id,
				array('error' => $wpdb->last_error, 'table' => $table_name)
			);
			return false;
		}

		// Audit log
		AuditLogger::log('chat_conversation_created', $user_id, array('conversation_id' => $conversation_id));

		return array(
			'conversation_id' => $conversation_id,
			'user_id'         => $user_id,
			'created_at'      => $created_at,
		);
	}

	/**
	 * Send message
	 *
	 * @param string $conversation_id Conversation ID.
	 * @param int    $user_id         User ID.
	 * @param string $message         Message content.
	 * @return bool
	 */
	public static function send_message($conversation_id, $user_id, $message): bool {
		global $wpdb;

		$conversation_id = sanitize_text_field($conversation_id);
		$user_id         = (int) $user_id;
		$message         = sanitize_textarea_field($message);

		$message = trim($message);

		if (strlen($message) > 5000) {
			$message = substr($message, 0, 5000);
		}

		if (!$conversation_id || !$user_id || !$message) {
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

		$table_name = $wpdb->prefix . 'tvc_chat_messages';

		if (!\TVC\System\SecurityManager::table_exists($table_name)) {
			\TVC\Audit\AuditLogger::log(
				'missing_table_detected',
				$user_id ? $user_id : null,
				array('table' => $table_name)
			);
			return false;
		}

		// Verify conversation ownership
		if (current_user_can('manage_options')) {
			// Admin bypass ownership check
		} else {
			$ownership_query = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE conversation_id = %s AND user_id = %d LIMIT 1",
				$conversation_id,
				$user_id
			);

			$ownership_count = (int) $wpdb->get_var($ownership_query);

			if ($ownership_count === 0) {
				\TVC\Audit\AuditLogger::log(
					'chat_ownership_violation',
					$user_id,
					array('conversation_id' => $conversation_id)
				);
				return false;
			}
		}

		try {
			$result = $wpdb->insert(
				$table_name,
				array(
					'conversation_id' => $conversation_id,
					'user_id'         => $user_id,
					'message'         => $message,
					'created_at'      => current_time('mysql'),
				),
				array('%s', '%d', '%s', '%s')
			);

			if ($result === false) {
				\TVC\Audit\AuditLogger::log(
					'db_insert_failed',
					$user_id ? $user_id : null,
					array('error' => $wpdb->last_error, 'table' => $table_name)
				);
				return false;
			}

			AuditLogger::log('chat_message_sent', $user_id, array('conversation_id' => $conversation_id));

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Count unread messages for a user in a conversation
	 *
	 * @param string $conversation_id Conversation ID.
	 * @param int    $user_id         User ID.
	 * @return int
	 */
	public static function count_unread($conversation_id, $user_id): int {
		global $wpdb;

		$conversation_id = sanitize_text_field($conversation_id);
		$user_id         = (int) $user_id;

		if (!$conversation_id || !$user_id) {
			return 0;
		}

		$user = get_user_by('id', (int) $user_id);

		if (!$user) {
			\TVC\Audit\AuditLogger::log(
				'invalid_user_reference',
				null,
				array('user_id' => $user_id)
			);
			return 0;
		}

		$table_name = $wpdb->prefix . 'tvc_chat_messages';

		if (!\TVC\System\SecurityManager::table_exists($table_name)) {
			\TVC\Audit\AuditLogger::log(
				'missing_table_detected',
				$user_id,
				array('table' => $table_name)
			);
			return 0;
		}

		$query      = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE conversation_id = %s AND user_id != %d",
			$conversation_id,
			$user_id
		);

		$count = $wpdb->get_var($query);

		return (int) $count;
	}
}

