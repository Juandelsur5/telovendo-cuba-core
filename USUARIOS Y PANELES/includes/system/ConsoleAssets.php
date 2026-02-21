<?php
declare(strict_types=1);

/**
 * Console Assets
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\System;

/**
 * ConsoleAssets class
 */
class ConsoleAssets {

	/**
	 * Initialize ConsoleAssets
	 */
	public static function init(): void {
		add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
	}

	/**
	 * Get plugin URL
	 *
	 * @return string
	 */
	private static function get_plugin_url(): string {
		// Calculate path to plugin root from includes/system/
		$plugin_file = dirname(dirname(dirname(__FILE__))) . '/telovendo-cuba-enterprise.php';
		return plugin_dir_url($plugin_file);
	}

	/**
	 * Enqueue assets for frontend panels
	 */
	public static function enqueue_assets(): void {
		global $wp_query;

		$is_tourist_panel = isset($wp_query->query_vars['tvc-tourist-panel']);
		$is_realtor_panel = isset($wp_query->query_vars['tvc-realtor-panel']);

		if (!$is_tourist_panel && !$is_realtor_panel) {
			return;
		}

		$plugin_url = self::get_plugin_url();
		$version    = defined('TVC_VERSION') ? TVC_VERSION : '1.0.0';

		wp_enqueue_style(
			'tvc-console-css',
			$plugin_url . 'assets/css/console.css',
			array(),
			$version
		);

		wp_enqueue_script(
			'tvc-console-js',
			$plugin_url . 'assets/js/console.js',
			array(),
			$version,
			true
		);
	}

	/**
	 * Enqueue assets for admin console
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_admin_assets(string $hook): void {
		if ($hook !== 'toplevel_page_tvc-admin-console') {
			return;
		}

		$plugin_url = self::get_plugin_url();
		$version    = defined('TVC_VERSION') ? TVC_VERSION : '1.0.0';

		wp_enqueue_style(
			'tvc-console-css',
			$plugin_url . 'assets/css/console.css',
			array(),
			$version
		);

		wp_enqueue_script(
			'tvc-console-js',
			$plugin_url . 'assets/js/console.js',
			array(),
			$version,
			true
		);
	}
}

