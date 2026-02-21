<?php
declare(strict_types=1);

/**
 * Realtor Panel
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\Panels;

use TVC\Subscription\SubscriptionManager;
use TVC\System\SecurityManager;
use TVC\Panels\ConsoleLayout;
use TVC\Chat\ChatManager;

/**
 * RealtorPanel class
 */
class RealtorPanel {

	/**
	 * Initialize RealtorPanel
	 */
	public static function init(): void {
		add_action('init', array(__CLASS__, 'register_endpoint'));
		add_action('template_redirect', array(__CLASS__, 'handle_request'));
	}

	/**
	 * Register rewrite endpoint
	 */
	public static function register_endpoint(): void {
		add_rewrite_endpoint('tvc-realtor-panel', EP_ROOT);
	}

	/**
	 * Handle template redirect
	 */
	public static function handle_request(): void {
		if (!is_user_logged_in()) {
			return;
		}

		$user_id = get_current_user_id();
		if (!$user_id) {
			return;
		}

		$profile_type = get_user_meta($user_id, 'tvc_profile_type', true);
		if ($profile_type !== 'realtor') {
			return;
		}

		global $wp_query;
		if (isset($wp_query->query_vars['tvc-realtor-panel'])) {
			if (SubscriptionManager::is_user_active($user_id) === false) {
				wp_die('Subscription required');
			}

			self::render();
			exit;
		}
	}

	/**
	 * Render panel
	 */
	public static function render(): void {
		if (!is_user_logged_in()) {
			return;
		}

		$user_id  = get_current_user_id();
		$is_active = SubscriptionManager::is_user_active($user_id);

		// Handle POST request for test subscription activation
		if (isset($_POST['activate_test_subscription'])) {
			$nonce = $_POST['tvc_subscription_nonce'] ?? '';
			if (SecurityManager::verify_nonce($nonce, 'tvc_activate_subscription')) {
				SubscriptionManager::activate_subscription($user_id, 30, 'test_plan');
				$redirect_url = add_query_arg('section', 'subscription', home_url('/tvc-realtor-panel'));
				wp_safe_redirect($redirect_url);
				exit;
			}
		}

		// Handle POST request for chat message
		if (isset($_POST['send_message'])) {
			$nonce = $_POST['tvc_chat_nonce'] ?? '';
			if (SecurityManager::verify_nonce($nonce, 'tvc_send_chat_message')) {
				$conversation_id = get_user_meta($user_id, 'tvc_chat_conversation_id', true);
				if ($conversation_id) {
					$message = $_POST['chat_message'] ?? '';
					$message = SecurityManager::sanitize_string($message);
					if ($message) {
						ChatManager::send_message($conversation_id, $user_id, $message);
					}
				}
				$redirect_url = add_query_arg('section', 'chat', home_url('/tvc-realtor-panel'));
				wp_safe_redirect($redirect_url);
				exit;
			}
		}

		$menu = array(
			array('label' => 'Dashboard', 'slug' => 'dashboard'),
			array('label' => 'My Listings', 'slug' => 'listings'),
			array('label' => 'Create Listing', 'slug' => 'create'),
			array('label' => 'Subscription', 'slug' => 'subscription'),
			array('label' => 'Chat', 'slug' => 'chat'),
			array('label' => 'Accreditation', 'slug' => 'accreditation'),
			array('label' => 'Analytics', 'slug' => 'analytics'),
		);

		// Add unread count to Chat menu item
		$conversation_id = get_user_meta($user_id, 'tvc_chat_conversation_id', true);
		if ($conversation_id) {
			$unread_count = ChatManager::count_unread($conversation_id, $user_id);
			if ($unread_count > 0) {
				foreach ($menu as $key => $item) {
					if ($item['slug'] === 'chat') {
						$menu[$key]['label'] = 'Chat (' . $unread_count . ')';
						break;
					}
				}
			}
		}

		if (empty($menu) || !isset($menu[0]['slug'])) {
			\TVC\Audit\AuditLogger::log(
				'invalid_menu_state',
				get_current_user_id(),
				array()
			);
			return;
		}

		$default_section = $menu[0]['slug'];

		$section = $_GET['section'] ?? $default_section;
		$section = SecurityManager::sanitize_string($section);

		$allowed_sections = array_column($menu, 'slug');

		if (!in_array($section, $allowed_sections, true)) {
			$section = $default_section;
		}

		ConsoleLayout::render(
			'Realtor Control Panel',
			$menu,
			$section,
			function() use ($section, $is_active, $user_id) {
				global $wpdb;

				echo '<div class="tvc-section">';
				echo '<h2>' . esc_html(ucfirst($section)) . '</h2>';

				if ($section === 'dashboard') {
					$status = get_user_meta($user_id, 'tvc_subscription_status', true);
					$status = $status ? SecurityManager::sanitize_string($status) : 'inactive';

					$days_remaining = 0;
					if ($status === 'active') {
						$end_date = get_user_meta($user_id, 'tvc_subscription_end', true);
						if ($end_date) {
							$end_timestamp   = strtotime($end_date);
							if ($end_timestamp === false) {
								\TVC\Audit\AuditLogger::log(
									'invalid_date_detected',
									$user_id,
									array('date' => $end_date)
								);
								$days_remaining = 0;
							} else {
								$current_timestamp = current_time('timestamp');
								$days_remaining   = max(0, floor(($end_timestamp - $current_timestamp) / 86400));
							}
						}
					}

					$conversation_id = get_user_meta($user_id, 'tvc_chat_conversation_id', true);
					$total_messages  = 0;
					if ($conversation_id) {
						$table_name = $wpdb->prefix . 'tvc_chat_messages';

						if (\TVC\System\SecurityManager::table_exists($table_name)) {
							$query      = $wpdb->prepare(
								"SELECT COUNT(*) FROM {$table_name} WHERE conversation_id = %s",
								$conversation_id
							);
							$total_messages = (int) $wpdb->get_var($query);
						} else {
							\TVC\Audit\AuditLogger::log(
								'missing_table_detected',
								$user_id,
								array('table' => $table_name)
							);
						}
					}

					echo '<h3>Dashboard</h3>';
					echo '<p>Subscription Status: ' . esc_html($status) . '</p>';
					echo '<p>Days Remaining: ' . esc_html($days_remaining) . '</p>';
					echo '<p>Total Chat Messages: ' . esc_html($total_messages) . '</p>';
				} elseif ($section === 'subscription') {
					$status = get_user_meta($user_id, 'tvc_subscription_status', true);
					$start  = get_user_meta($user_id, 'tvc_subscription_start', true);
					$end    = get_user_meta($user_id, 'tvc_subscription_end', true);
					$plan   = get_user_meta($user_id, 'tvc_subscription_plan', true);

					$status = $status ? SecurityManager::sanitize_string($status) : 'inactive';
					$start  = $start ? SecurityManager::sanitize_string($start) : '-';
					$end    = $end ? SecurityManager::sanitize_string($end) : '-';
					$plan   = $plan ? SecurityManager::sanitize_string($plan) : 'none';

					echo '<h3>Subscription Details</h3>';
					echo '<p>Status: ' . esc_html($status) . '</p>';
					echo '<p>Plan: ' . esc_html($plan) . '</p>';
					echo '<p>Start Date: ' . esc_html($start) . '</p>';
					echo '<p>End Date: ' . esc_html($end) . '</p>';

					if ($status !== 'active') {
						$nonce = wp_create_nonce('tvc_activate_subscription');
						echo '<form method="post">';
						echo '<input type="hidden" name="tvc_subscription_nonce" value="' . esc_attr($nonce) . '">';
						echo '<button type="submit" name="activate_test_subscription">Activate Test Plan (30 days)</button>';
						echo '</form>';
					}
				} elseif ($section === 'chat') {
					$conversation_id = get_user_meta($user_id, 'tvc_chat_conversation_id', true);

					if (empty($conversation_id)) {
						$conversation = ChatManager::create_conversation($user_id);
						if ($conversation && isset($conversation['conversation_id'])) {
							$conversation_id = $conversation['conversation_id'];
							update_user_meta($user_id, 'tvc_chat_conversation_id', $conversation_id);
						}
					}

					if ($conversation_id) {
						$table_name = $wpdb->prefix . 'tvc_chat_messages';

						if (!\TVC\System\SecurityManager::table_exists($table_name)) {
							\TVC\Audit\AuditLogger::log(
								'missing_table_detected',
								$user_id,
								array('table' => $table_name)
							);
							$messages = array();
						} else {
							$query      = $wpdb->prepare(
								"SELECT message, created_at FROM {$table_name} WHERE conversation_id = %s ORDER BY created_at ASC",
								$conversation_id
							);
							$messages   = $wpdb->get_results($query, ARRAY_A);
						}

						echo '<h3>Support Chat</h3>';
						echo '<div>';

						if ($messages) {
							foreach ($messages as $msg) {
								$date    = esc_html($msg['created_at']);
								$message = esc_html($msg['message']);
								echo '<p><strong>' . $date . '</strong>: ' . $message . '</p>';
							}
						}

						echo '</div>';

						$nonce = wp_create_nonce('tvc_send_chat_message');
						echo '<form method="post">';
						echo '<input type="hidden" name="tvc_chat_nonce" value="' . esc_attr($nonce) . '">';
						echo '<textarea name="chat_message"></textarea>';
						echo '<button type="submit" name="send_message">Send</button>';
						echo '</form>';
					}
				} else {
					if (!$is_active) {
						echo '<p>Subscription inactive. Please activate your plan.</p>';
					} else {
						echo '<p>Section under construction.</p>';
					}
				}

				echo '</div>';
			}
		);
	}
}

