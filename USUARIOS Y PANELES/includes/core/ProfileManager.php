<?php
declare(strict_types=1);

/**
 * Profile Manager
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\Core;

/**
 * ProfileManager class
 */
class ProfileManager {

	/**
	 * Initialize ProfileManager
	 */
	public static function init() {
		add_action('user_register', array(__CLASS__, 'create_default_profile'));
	}

	/**
	 * Create default profile on user registration
	 *
	 * @param int $user_id User ID.
	 */
	public static function create_default_profile($user_id) {
		$user_id = (int) $user_id;
		
		update_user_meta($user_id, 'tvc_profile_type', 'tourist');
		update_user_meta($user_id, 'tvc_staff_role', null);
		update_user_meta($user_id, 'tvc_account_status', 'active');
	}
}

