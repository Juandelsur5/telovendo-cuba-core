<?php
declare(strict_types=1);

/**
 * Security Manager
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\System;

/**
 * SecurityManager class
 */
class SecurityManager {

	/**
	 * Sanitize string value
	 *
	 * @param mixed $value Value to sanitize.
	 * @return string
	 */
	public static function sanitize_string($value): string {
		return sanitize_text_field((string) $value);
	}

	/**
	 * Sanitize email value
	 *
	 * @param mixed $value Value to sanitize.
	 * @return string
	 */
	public static function sanitize_email($value): string {
		return sanitize_email((string) $value);
	}

	/**
	 * Sanitize integer value
	 *
	 * @param mixed $value Value to sanitize.
	 * @return int
	 */
	public static function sanitize_int($value): int {
		return (int) $value;
	}

	/**
	 * Recursively sanitize array
	 *
	 * @param array $data Data to sanitize.
	 * @return array
	 */
	public static function sanitize_array(array $data): array {
		$sanitized = array();
		foreach ($data as $key => $value) {
			$sanitized_key = sanitize_key((string) $key);
			if (is_array($value)) {
				$sanitized[$sanitized_key] = self::sanitize_array($value);
			} elseif (is_string($value)) {
				$sanitized[$sanitized_key] = sanitize_text_field($value);
			} else {
				$sanitized[$sanitized_key] = $value;
			}
		}
		return $sanitized;
	}

	/**
	 * Verify nonce
	 *
	 * @param string $nonce  Nonce value.
	 * @param string $action Action name.
	 * @return bool
	 */
	public static function verify_nonce(string $nonce, string $action): bool {
		return (bool) wp_verify_nonce($nonce, $action);
	}

	/**
	 * Enforce capability check
	 *
	 * @param string $capability Capability to check.
	 * @return void
	 */
	public static function enforce_capability(string $capability): void {
		if (!current_user_can($capability)) {
			wp_die('Unauthorized');
		}
	}

	/**
	 * Normalize boolean value
	 *
	 * @param mixed $value Value to normalize.
	 * @return bool
	 */
	public static function normalize_bool($value): bool {
		if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
			return true;
		}
		return false;
	}

	/**
	 * Check if database table exists
	 *
	 * @param string $table_name Table name.
	 * @return bool
	 */
	public static function table_exists(string $table_name): bool {
		global $wpdb;

		$table = $wpdb->get_var(
			$wpdb->prepare(
				"SHOW TABLES LIKE %s",
				$table_name
			)
		);

		return ($table === $table_name);
	}
}

