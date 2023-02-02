<?php
/**
 * Class used to manage backwards-compatibility of the package.
 *
 * @since 0.8.0
 *
 * @package automattic/jetpack-waf
 */

namespace Automattic\Jetpack\Waf;

/**
 * Defines methods for ensuring backwards compatibility.
 */
class Waf_Compatibility {

	/**
	 * Add compatibilty hooks
	 *
	 * @since 0.8.0
	 *
	 * @return void
	 */
	public static function add_compatibility_hooks() {
		add_filter( 'default_option_' . Waf_Rules_Manager::AUTOMATIC_RULES_ENABLED_OPTION_NAME, __CLASS__ . '::default_option_waf_automatic_rules', 10, 3 );
		add_filter( 'default_option_' . Waf_Initializer::NEEDS_UPDATE_OPTION_NAME, __CLASS__ . '::default_option_waf_needs_update', 10, 3 );
		add_filter( 'default_option_' . Waf_Rules_Manager::IP_ALLOW_LIST_OPTION_NAME, __CLASS__ . '::default_option_waf_ip_allow_list', 10, 3 );
		add_filter( 'option_' . Waf_Rules_Manager::IP_ALLOW_LIST_OPTION_NAME, __CLASS__ . '::filter_option_waf_ip_allow_list', 10, 1 );
	}

	/**
	 * Run compatibility migrations.
	 *
	 * @since $$next-version$$
	 *
	 * @return void
	 */
	public static function run_compatibility_migrations() {
		self::migrate_brute_force_protection_ip_allow_list();
	}

	/**
	 * Provides a default value for sites that installed the WAF
	 * before the automatic rules option was introduced.
	 *
	 * @since 0.9.0
	 *
	 * @param mixed  $default         The default value to return if the option does not exist in the database.
	 * @param string $option          Option name.
	 * @param bool   $passed_default  Was get_option() passed a default value.
	 *
	 * @return mixed The default value to return if the option does not exist in the database.
	 */
	public static function default_option_waf_automatic_rules( $default, $option, $passed_default ) {
		// Allow get_option() to override this default value
		if ( $passed_default ) {
			return $default;
		}

		return self::get_default_automatic_rules_option();
	}

	/**
	 * If the option is not available, use the WAF module status
	 * to determine whether or not to run automatic rules.
	 *
	 * @since 0.9.0
	 *
	 * @return bool The default value for automatic rules.
	 */
	public static function get_default_automatic_rules_option() {
		return Waf_Runner::is_enabled();
	}

	/**
	 * Provides a default value for sites that installed the WAF
	 * before the NEEDS_UPDATE_OPTION_NAME option was added.
	 *
	 * @since 0.8.0
	 *
	 * @param mixed  $default         The default value to return if the option does not exist in the database.
	 * @param string $option          Option name.
	 * @param bool   $passed_default  Was get_option() passed a default value.
	 *
	 * @return mixed The default value to return if the option does not exist in the database.
	 */
	public static function default_option_waf_needs_update( $default, $option, $passed_default ) {
		// Allow get_option() to override this default value
		if ( $passed_default ) {
			return $default;
		}

		// If the option hasn't been added yet, the WAF needs to be updated.
		return true;
	}

	/**
	 * Merge the WAF and Brute Force Protection IP allow lists.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $waf_allow_list        The WAF IP allow list.
	 * @param array  $brute_force_allow_list The Brute Force Protection IP allow list. Array of IP objects.
	 *
	 * @return string The merged IP allow list.
	 */
	public static function merge_ip_allow_lists( $waf_allow_list, $brute_force_allow_list ) {

		if ( empty( $brute_force_allow_list ) ) {
			return $waf_allow_list;
		}

		// Convert the IP objects to strings.
		$brute_force_allow_list = array_map(
			function ( $ip_object ) {
				if ( isset( $ip_object->range ) && $ip_object->range ) {
					return $ip_object->range_low . '-' . $ip_object->range_high;
				}

				return $ip_object->ip_address;
			},
			$brute_force_allow_list
		);

		$brute_force_allow_list_string = implode( "\n", $brute_force_allow_list );

		if ( empty( $waf_allow_list ) ) {
			return $brute_force_allow_list_string;
		}

		// Return the lists merged into a single string.
		return "$waf_allow_list\n$brute_force_allow_list_string";
	}

	/**
	 * Migrate the brute force protection IP allow list option to the WAF option.
	 *
	 * @since $$next-version$$
	 *
	 * @return void
	 */
	public static function migrate_brute_force_protection_ip_allow_list() {
		$waf_allow_list         = get_option( 'jetpack_waf_ip_allow_list', false );
		$brute_force_allow_list = get_option( 'jetpack_protect_whitelist', false );

		if ( false === $waf_allow_list ) {
			$waf_allow_list = '';
		}

		if ( false !== $brute_force_allow_list ) {
			$merged_allow_list = self::merge_ip_allow_lists( $waf_allow_list, $brute_force_allow_list );
			if ( update_option( Waf_Rules_Manager::IP_ALLOW_LIST_OPTION_NAME, $merged_allow_list ) ) {
				delete_option( 'jetpack_protect_whitelist' );
			}
		}
	}

	/**
	 * Filter for Waf_Rules_Manager::IP_ALLOW_LIST_OPTION_NAME's option value.
	 * Merges the deprecated IP allow list from the brute force protection module
	 * with the existing option value, and flags that the WAF needs to be updated.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $waf_allow_list The current value of the option.
	 *
	 * @return array The merged IP allow list.
	 */
	public static function filter_option_waf_ip_allow_list( $waf_allow_list ) {
		$brute_force_allow_list = get_option( 'jetpack_protect_whitelist', false );
		if ( false !== $brute_force_allow_list ) {
			$waf_allow_list = self::merge_ip_allow_lists( $waf_allow_list, $brute_force_allow_list );
			update_option( Waf_Initializer::NEEDS_UPDATE_OPTION_NAME, 1 );
		}

		return $waf_allow_list;
	}

	/**
	 * Default option for when the Waf_Rules_Manager::IP_ALLOW_LIST_OPTION_NAME option is not set.
	 *
	 * @param mixed  $default         The default value to return if the option does not exist in the database.
	 * @param string $option          Option name.
	 * @param bool   $passed_default  Was get_option() passed a default value.
	 *
	 * @return mixed The default value to return if the option does not exist in the database.
	 */
	public static function default_option_waf_ip_allow_list( $default, $option, $passed_default ) {
		// Allow get_option() to override this default value
		if ( $passed_default ) {
			return $default;
		}

		$waf_allow_list = '';

		// If the brute force option exists, use that and flag that the WAF needs to be updated.
		$brute_force_allow_list = get_option( 'jetpack_protect_whitelist', false );
		if ( false !== $brute_force_allow_list ) {
			$waf_allow_list = self::merge_ip_allow_lists( $waf_allow_list, $brute_force_allow_list );
			update_option( Waf_Initializer::NEEDS_UPDATE_OPTION_NAME, 1 );
		}

		return $waf_allow_list;
	}

}
