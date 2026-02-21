<?php
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
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Protección contra acceso directo
defined('ABSPATH') || exit;

namespace TVC;

/**
 * Clase principal del plugin TeloVendo Cuba Enterprise
 */
class TVC_Plugin {

	/**
	 * Inicializa el plugin
	 */
	public function init() {
		// Lógica de inicialización pendiente
	}
}

/**
 * Inicializa el plugin cuando WordPress carga los plugins
 */
add_action('plugins_loaded', function() {
	$plugin = new TVC_Plugin();
	$plugin->init();
});

