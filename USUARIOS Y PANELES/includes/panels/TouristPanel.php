<?php
declare(strict_types=1);

/**
 * Tourist Panel
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\Panels;

use TVC\Panels\ConsoleLayout;
use TVC\System\SecurityManager;

/**
 * TouristPanel class
 */
class TouristPanel {

	/**
	 * Initialize TouristPanel
	 */
	public static function init(): void {
		add_action('init', array(__CLASS__, 'register_endpoint'));
		add_action('template_redirect', array(__CLASS__, 'handle_request'));
	}

	/**
	 * Register rewrite endpoint
	 */
	public static function register_endpoint(): void {
		add_rewrite_endpoint('tvc-tourist-panel', EP_ROOT);
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
		if ($profile_type !== 'tourist') {
			return;
		}

		global $wp_query;
		if (isset($wp_query->query_vars['tvc-tourist-panel'])) {
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

		$menu = array(
			array('label' => 'Flights', 'slug' => 'flights'),
			array('label' => 'Hotels', 'slug' => 'hotels'),
			array('label' => 'Transport', 'slug' => 'transport'),
			array('label' => 'Tours', 'slug' => 'tours'),
			array('label' => 'Restaurants', 'slug' => 'restaurants'),
			array('label' => 'Bars', 'slug' => 'bars'),
			array('label' => 'Stores', 'slug' => 'stores'),
		);

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
			'Tourist Control Panel',
			$menu,
			$section,
			function() use ($section) {
				echo '<div class="tvc-section">';
				echo '<h2>' . esc_html(ucfirst($section)) . '</h2>';
				echo '<p>Module under construction.</p>';
				echo '</div>';
			}
		);
	}
}

