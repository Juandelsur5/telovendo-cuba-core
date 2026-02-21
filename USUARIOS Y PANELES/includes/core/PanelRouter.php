<?php
declare(strict_types=1);

/**
 * Panel Router
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\Core;

/**
 * PanelRouter class
 */
class PanelRouter {

	/**
	 * Initialize PanelRouter
	 */
	public static function init() {
		add_action('template_redirect', array(__CLASS__, 'route_user_panel'));
	}

	/**
	 * Route user to appropriate panel
	 */
	public static function route_user_panel() {
		if (!is_user_logged_in()) {
			return;
		}

		$user_id = get_current_user_id();
		if (!$user_id) {
			return;
		}

		$profile_type = get_user_meta($user_id, 'tvc_profile_type', true);
		if (!$profile_type) {
			return;
		}

		$target_slug = '';
		if ($profile_type === 'tourist') {
			$target_slug = 'tvc-tourist-panel';
		} elseif ($profile_type === 'realtor') {
			$target_slug = 'tvc-realtor-panel';
		}

		if (!$target_slug) {
			return;
		}

		$current_path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');

		if ($current_path === $target_slug) {
			return;
		}

		$redirect_url = home_url('/' . $target_slug);
		wp_safe_redirect($redirect_url);
		exit;
	}
}

