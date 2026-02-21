<?php
declare(strict_types=1);

/**
 * Plugin Name: TeloVendo Cuba Enterprise
 * Plugin URI: https://telovendo.cu
 * Description: Plugin empresarial para gestión avanzada de TeloVendo Cuba
 * Version: 1.0.0
 * Author: TeloVendo Cuba
 * Author URI: https://telovendo.cu
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: telovendo-cuba-enterprise
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.1
 */

if (!defined('ABSPATH')) {
	exit;
}

// PHP version check
if (version_compare(PHP_VERSION, '8.1', '<')) {
	if (function_exists('deactivate_plugins')) {
		deactivate_plugins(plugin_basename(__FILE__));
	}
	wp_die('TeloVendo Cuba Enterprise requires PHP 8.1 or higher. Current version: ' . PHP_VERSION);
}

// WordPress version check
global $wp_version;
if (version_compare($wp_version, '6.5', '<')) {
	if (function_exists('deactivate_plugins')) {
		deactivate_plugins(plugin_basename(__FILE__));
	}
	wp_die('TeloVendo Cuba Enterprise requires WordPress 6.5 or higher. Current version: ' . $wp_version);
}

namespace TVC;

require_once plugin_dir_path(__FILE__) . 'includes/core/ProfileManager.php';
require_once plugin_dir_path(__FILE__) . 'includes/core/PanelRouter.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/AdminConsole.php';
require_once plugin_dir_path(__FILE__) . 'includes/permissions/PermissionManager.php';
require_once plugin_dir_path(__FILE__) . 'includes/audit/AuditLogger.php';
require_once plugin_dir_path(__FILE__) . 'includes/chat/ChatManager.php';
require_once plugin_dir_path(__FILE__) . 'includes/marketplace/MarketplaceManager.php';
require_once plugin_dir_path(__FILE__) . 'includes/subscription/SubscriptionManager.php';
require_once plugin_dir_path(__FILE__) . 'includes/accreditation/AccreditationManager.php';
require_once plugin_dir_path(__FILE__) . 'includes/advertising/AdvertisingManager.php';
require_once plugin_dir_path(__FILE__) . 'includes/brain/BrainManager.php';
require_once plugin_dir_path(__FILE__) . 'includes/system/SecurityManager.php';
require_once plugin_dir_path(__FILE__) . 'includes/system/RequestGuard.php';
require_once plugin_dir_path(__FILE__) . 'includes/system/ConsoleAssets.php';
require_once plugin_dir_path(__FILE__) . 'includes/panels/TouristPanel.php';
require_once plugin_dir_path(__FILE__) . 'includes/panels/RealtorPanel.php';
require_once plugin_dir_path(__FILE__) . 'includes/panels/ConsoleLayout.php';
require_once plugin_dir_path(__FILE__) . 'database/migrations/MigrationManager.php';

use TVC\Core\ProfileManager;
use TVC\Core\PanelRouter;
use TVC\Admin\AdminConsole;
use TVC\Permissions\PermissionManager;
use TVC\Chat\ChatManager;
use TVC\Marketplace\MarketplaceManager;
use TVC\Subscription\SubscriptionManager;
use TVC\Accreditation\AccreditationManager;
use TVC\Advertising\AdvertisingManager;
use TVC\Brain\BrainManager;
use TVC\Panels\TouristPanel;
use TVC\Panels\RealtorPanel;
use TVC\Database\Migrations\MigrationManager;

/**
 * Clase principal del plugin TeloVendo Cuba Enterprise
 */
class TVC_Plugin {

	/**
	 * Plugin instance
	 *
	 * @var TVC_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get plugin instance
	 *
	 * @return TVC_Plugin
	 */
	public static function get_instance(): TVC_Plugin {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Prevent cloning
	 */
	private function __clone() {
	}

	/**
	 * Prevent unserialization
	 */
	public function __wakeup() {
		throw new \Exception('Cannot unserialize singleton');
	}

	/**
	 * Inicializa el plugin
	 */
	public function init(): void {
		ProfileManager::init();
		PanelRouter::init();
		AdminConsole::init();
		PermissionManager::init();
		ChatManager::init();
		MarketplaceManager::init();
		SubscriptionManager::init();
		AccreditationManager::init();
		AdvertisingManager::init();
		BrainManager::init();
		TouristPanel::init();
		RealtorPanel::init();
		\TVC\System\ConsoleAssets::init();
	}
}

/**
 * Inicializa el plugin cuando WordPress carga los plugins
 */
add_action('plugins_loaded', function() {
	$plugin = TVC_Plugin::get_instance();
	$plugin->init();
});

/**
 * Run migrations and flush rewrite rules on plugin activation
 */
register_activation_hook(__FILE__, function() {
	// Register endpoints first
	TouristPanel::register_endpoint();
	RealtorPanel::register_endpoint();
	
	// Run migrations
	MigrationManager::run();
	
	// Flush rewrite rules
	flush_rewrite_rules();
});

/**
 * Flush rewrite rules on plugin deactivation
 */
register_deactivation_hook(__FILE__, function() {
	flush_rewrite_rules();
});

