<?php
declare(strict_types=1);

/**
 * Advertising Manager
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\Advertising;

/**
 * AdvertisingManager class
 */
class AdvertisingManager {

	/**
	 * Initialize AdvertisingManager
	 */
	public static function init() {
		// Initialization logic pending
	}

	/**
	 * Check if ad is active
	 *
	 * @param int $ad_id Ad ID.
	 * @return bool
	 */
	public static function is_ad_active($ad_id) {
		$ad_id = (int) $ad_id;

		if (!$ad_id) {
			return false;
		}

		return false;
	}
}

