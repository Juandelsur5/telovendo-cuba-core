<?php
declare(strict_types=1);

/**
 * Migration Manager
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\Database\Migrations;

/**
 * MigrationManager class
 */
class MigrationManager {

	/**
	 * Initialize MigrationManager
	 */
	public static function init(): void {
		// Initialization logic pending
	}

	/**
	 * Run migrations
	 */
	public static function run(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$table_audit_logs = $wpdb->prefix . 'tvc_audit_logs';
		$sql_audit_logs   = "CREATE TABLE IF NOT EXISTS {$table_audit_logs} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event VARCHAR(191) NOT NULL,
			user_id BIGINT UNSIGNED NULL,
			context LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY event (event),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		$table_chat_messages = $wpdb->prefix . 'tvc_chat_messages';
		$sql_chat_messages    = "CREATE TABLE IF NOT EXISTS {$table_chat_messages} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			conversation_id VARCHAR(191) NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			message LONGTEXT NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY conversation_id (conversation_id),
			KEY conversation_idx (conversation_id),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		$table_rate_tracking = $wpdb->prefix . 'tvc_rate_tracking';
		$sql_rate_tracking    = "CREATE TABLE IF NOT EXISTS {$table_rate_tracking} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			identifier VARCHAR(191) NOT NULL,
			request_count INT NOT NULL,
			window_start DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY identifier_unique (identifier),
			KEY identifier (identifier),
			KEY window_start (window_start)
		) {$charset_collate};";

		$table_subscription_history = $wpdb->prefix . 'tvc_subscription_history';
		$sql_subscription_history    = "CREATE TABLE IF NOT EXISTS {$table_subscription_history} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			plan VARCHAR(100) NOT NULL,
			started_at DATETIME NOT NULL,
			ended_at DATETIME NOT NULL,
			status VARCHAR(50) NOT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY user_history_idx (user_id),
			KEY status (status),
			KEY started_at (started_at)
		) {$charset_collate};";

		$table_user_permissions = $wpdb->prefix . 'tvc_user_permissions';
		$sql_user_permissions    = "CREATE TABLE IF NOT EXISTS {$table_user_permissions} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			block_name VARCHAR(100) NOT NULL,
			granted TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY user_block_unique (user_id, block_name),
			KEY user_id (user_id),
			KEY block_name (block_name),
			KEY granted (granted)
		) {$charset_collate};";

		dbDelta($sql_audit_logs);
		dbDelta($sql_chat_messages);
		dbDelta($sql_rate_tracking);
		dbDelta($sql_subscription_history);
		dbDelta($sql_user_permissions);
	}
}

