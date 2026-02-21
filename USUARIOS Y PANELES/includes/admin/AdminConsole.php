<?php
declare(strict_types=1);

/**
 * Admin Console
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\Admin;

use TVC\Panels\ConsoleLayout;
use TVC\Permissions\PermissionManager;
use TVC\System\SecurityManager;
use TVC\Chat\ChatManager;

/**
 * AdminConsole class
 */
class AdminConsole {

	/**
	 * Initialize AdminConsole
	 */
	public static function init(): void {
		add_action('admin_menu', array(__CLASS__, 'register_menu'));
	}

	/**
	 * Register admin menu
	 */
	public static function register_menu() {
		add_menu_page(
			'TOCO Console',
			'TOCO',
			'manage_options',
			'tvc-admin-console',
			array(__CLASS__, 'render'),
			'dashicons-shield',
			3
		);
	}

	/**
	 * Render admin page
	 */
	public static function render(): void {
		if (!current_user_can('manage_options')) {
			wp_die('Unauthorized');
		}

		$user_id = get_current_user_id();

		// Handle POST request for admin chat reply
		if (isset($_POST['send_admin_reply'])) {
			$nonce = $_POST['tvc_admin_chat_nonce'] ?? '';
			if (SecurityManager::verify_nonce($nonce, 'tvc_admin_chat_reply')) {
				$conversation_id = $_GET['conversation'] ?? '';
				$conversation_id = SecurityManager::sanitize_string($conversation_id);
				$message         = $_POST['admin_reply'] ?? '';
				$message         = SecurityManager::sanitize_string($message);
				if ($conversation_id && $message) {
					ChatManager::send_message($conversation_id, $user_id, $message);
				}
				$redirect_url = add_query_arg(
					array(
						'page'        => 'tvc-admin-console',
						'section'     => 'chat',
						'conversation' => $conversation_id,
					),
					admin_url('admin.php')
				);
				wp_safe_redirect($redirect_url);
				exit;
			}
		}

		$sections = array(
			'users'         => 'users',
			'marketplace'   => 'marketplace',
			'finance'       => 'finance',
			'support'       => 'support',
			'brain'         => 'brain',
			'analytics'     => 'analytics',
			'system'        => 'system',
			'audit'         => 'audit',
			'accreditation' => 'accreditation',
			'chat'          => 'chat',
		);

		$menu = array();

		foreach ($sections as $label => $block) {
			if (PermissionManager::user_has_block($user_id, $block)) {
				$menu[] = array(
					'label' => ucfirst($label),
					'slug'  => $label,
				);
			}
		}

		if (empty($menu) || !isset($menu[0]['slug'])) {
			\TVC\Audit\AuditLogger::log(
				'invalid_menu_state',
				get_current_user_id(),
				array()
			);
			wp_die('No permissions assigned.');
		}

		$default_section = $menu[0]['slug'];

		$section = $_GET['section'] ?? $default_section;
		$section = SecurityManager::sanitize_string($section);

		$allowed_sections = array_column($menu, 'slug');

		if (!in_array($section, $allowed_sections, true)) {
			$section = $default_section;
		}

		ConsoleLayout::render(
			'TOCO Admin Console',
			$menu,
			$section,
			function() use ($section, $user_id, $menu, $default_section) {
				global $wpdb;

				echo '<div class="tvc-section">';
				echo '<h2>' . esc_html(ucfirst($section)) . '</h2>';

				if ($section === 'analytics' || ($section === $default_section && $section !== 'chat')) {
					// Total active subscriptions
					$active_subscriptions_query = $wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
						'tvc_subscription_status',
						'active'
					);
					$active_subscriptions = (int) $wpdb->get_var($active_subscriptions_query);

					// Total conversations
					$table_name = $wpdb->prefix . 'tvc_chat_messages';
					$total_conversations = 0;
					if (\TVC\System\SecurityManager::table_exists($table_name)) {
						$conversations_query = $wpdb->prepare(
							"SELECT COUNT(DISTINCT conversation_id) FROM {$table_name}"
						);
						$total_conversations = (int) $wpdb->get_var($conversations_query);
					} else {
						\TVC\Audit\AuditLogger::log(
							'missing_table_detected',
							$user_id,
							array('table' => $table_name)
						);
					}

					// Total audit logs
					$audit_table = $wpdb->prefix . 'tvc_audit_logs';
					$total_audit_logs = 0;
					if (\TVC\System\SecurityManager::table_exists($audit_table)) {
						$audit_query = $wpdb->prepare(
							"SELECT COUNT(*) FROM {$audit_table}"
						);
						$total_audit_logs = (int) $wpdb->get_var($audit_query);
					} else {
						\TVC\Audit\AuditLogger::log(
							'missing_table_detected',
							$user_id,
							array('table' => $audit_table)
						);
					}

					echo '<h3>Platform Overview</h3>';
					echo '<p>Active Subscriptions: ' . esc_html($active_subscriptions) . '</p>';
					echo '<p>Total Conversations: ' . esc_html($total_conversations) . '</p>';
					echo '<p>Total Audit Logs: ' . esc_html($total_audit_logs) . '</p>';
				} elseif ($section === 'chat') {
					$table_name = $wpdb->prefix . 'tvc_chat_messages';

					if (!\TVC\System\SecurityManager::table_exists($table_name)) {
						\TVC\Audit\AuditLogger::log(
							'missing_table_detected',
							$user_id,
							array('table' => $table_name)
						);
						$conversations = array();
					} else {
						$conversations_query = $wpdb->prepare(
							"SELECT DISTINCT conversation_id FROM {$table_name} ORDER BY id DESC LIMIT 100"
						);
						$conversations       = $wpdb->get_col($conversations_query);
					}

					echo '<h3>Conversations</h3>';
					echo '<ul>';

					foreach ($conversations as $conv_id) {
						$conv_id_safe = esc_attr($conv_id);
						$unread_count = ChatManager::count_unread($conv_id, $user_id);
						$display_text = $conv_id;
						if ($unread_count > 0) {
							$display_text .= ' (' . $unread_count . ')';
						}
						$url = add_query_arg(
							array(
								'page'        => 'tvc-admin-console',
								'section'     => 'chat',
								'conversation' => $conv_id_safe,
							),
							admin_url('admin.php')
						);
						echo '<li><a href="' . esc_url($url) . '">' . esc_html($display_text) . '</a></li>';
					}

					echo '</ul>';

					$selected_conversation = $_GET['conversation'] ?? '';
					if ($selected_conversation) {
						$selected_conversation = SecurityManager::sanitize_string($selected_conversation);

						if (\TVC\System\SecurityManager::table_exists($table_name)) {
							$messages_query = $wpdb->prepare(
								"SELECT message, created_at, user_id FROM {$table_name} WHERE conversation_id = %s ORDER BY created_at ASC",
								$selected_conversation
							);
							$messages       = $wpdb->get_results($messages_query, ARRAY_A);
						} else {
							$messages = array();
						}

						echo '<h4>Conversation: ' . esc_html($selected_conversation) . '</h4>';

						if ($messages) {
							foreach ($messages as $msg) {
								$user_id_msg = esc_html($msg['user_id']);
								$date        = esc_html($msg['created_at']);
								$message     = esc_html($msg['message']);
								echo '<p><strong>' . $user_id_msg . '</strong> [' . $date . ']: ' . $message . '</p>';
							}
						}

						$nonce = wp_create_nonce('tvc_admin_chat_reply');
						echo '<form method="post">';
						echo '<input type="hidden" name="tvc_admin_chat_nonce" value="' . esc_attr($nonce) . '">';
						echo '<textarea name="admin_reply"></textarea>';
						echo '<button type="submit" name="send_admin_reply">Reply</button>';
						echo '</form>';
					}
				} else {
					echo '<p>Admin module under construction.</p>';
				}

				echo '</div>';
			}
		);
	}
}

