<?php
/**
Plugin Name: Gravity Forms Entry Expiration
Plugin URI: http://travislop.es/plugins/gravity-forms-entry-expiration/
Description: Provides a simple way to remove old entries in Gravity Forms.
Version: 1.2.3
Author: travislopes
Author URI: http://travislop.es
Text Domain: gravityformsentryexpiration
Domain Path: /languages
 **/

define( 'GF_ENTRYEXPIRATION_VERSION', '1.2.3' );

add_action( 'gform_loaded', array( 'GF_EntryExpiration_Bootstrap', 'load' ), 5 );
add_action( 'gf_entryexpiration_delete_old_entries', 'gf_entryexpiration_delete_old_entries_action' );
register_activation_hook( __FILE__, 'gf_entryexpiration_activation_routine' );
register_deactivation_hook( __FILE__, 'gf_entryexpiration_deactivation_routine' );

/**
 * Class GF_EntryExpiration_Bootstrap
 *
 * Handles the loading of Gravity Forms Entry Expiration and registers with the Add-On framework.
 */
class GF_EntryExpiration_Bootstrap {

	/**
	 * If the Add-On Framework exists, Gravity Forms Entry Expiration is loaded.
	 *
	 * @access public
	 * @static
	 */
	public static function load() {

		// If Add-On Framework is not loaded, exit.
		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		self::load_class();

		GFAddOn::register( 'GFEntryExpiration' );

	}

	/**
	 * Load Entry Expiration class.
	 *
	 * @access public
	 * @static
	 */
	public static function load_class() {

		if ( ! class_exists( 'GFEntryExpiration' ) ) {
			require_once( 'class-gf-entryexpiration.php' );
		}

	}

}

/**
 * Returns an instance of the GFEntryExpiration class.
 *
 * @see    GFEntryExpiration::get_instance()
 *
 * @return object GFEntryExpiration
 */
function gf_entryexpiration() {

	// Load Entry Expiration class.
	GF_EntryExpiration_Bootstrap::load_class();

	return GFEntryExpiration::get_instance();

}

/**
 * Deletes old Gravity Forms entries.
 *
 * @uses   GFEntryExpiration::delete_old_entries()
 *
 * @return object GFEntryExpiration
 */
function gf_entryexpiration_delete_old_entries_action() {

	// Load Entry Expiration class.
	GF_EntryExpiration_Bootstrap::load_class();

	return gf_entryexpiration()->delete_old_entries();

}

/**
 * Schedules event for entry deletion.
 */
function gf_entryexpiration_activation_routine() {

	// Load Entry Expiration class.
	GF_EntryExpiration_Bootstrap::load_class();

	// Get event recurrence.
	$recurrence = apply_filters( 'gf_entryexpiration_recurrence', 'hourly' );

	// Schedule event.
	$event_scheduled = wp_schedule_event( strtotime( 'midnight' ), $recurrence, 'gf_entryexpiration_delete_old_entries' );

	// Log event scheduled status.
	if ( false === $event_scheduled ) {
		gf_entryexpiration()->log_error( __METHOD__ . '(): Cron event for entry deletion could not be scheduled.' );
	} else {
		gf_entryexpiration()->log_debug( __METHOD__ . '(): Cron event for entry deletion successfully scheduled.' );
	}

}

/**
 * Deletes scheduled event for entry deletion.
 */
function gf_entryexpiration_deactivation_routine() {
	wp_clear_scheduled_hook( 'gf_entryexpiration_delete_old_entries' );
}
