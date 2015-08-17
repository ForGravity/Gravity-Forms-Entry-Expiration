<?php
	
/*
Plugin Name: Gravity Forms Entry Expiration
Plugin URI: http://travislop.es/plugins/gravity-forms-entry-expiration/
Description: Provides a simple way to remove old entries in Gravity Forms.
Version: 1.2.2
Author: travislopes
Author URI: http://travislop.es
Text Domain: gravityformsentryexpiration
Domain Path: /languages
*/

define( 'GF_ENTRYEXPIRATION_VERSION', '1.2.2' );

add_action( 'gform_loaded', array( 'GF_EntryExpiration_Bootstrap', 'load' ), 5 );
add_action( 'gf_entryexpiration_delete_old_entries', 'gf_entryexpiration_delete_old_entries_action' );
register_activation_hook( __FILE__, 'gf_entryexpiration_activation_routine' );
register_deactivation_hook( __FILE__, 'gf_entryexpiration_deactivation_routine' );	

class GF_EntryExpiration_Bootstrap {

	public static function load() {
		
		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}
		
		require_once( 'class-gf-entryexpiration.php' );
		
		GFAddOn::register( 'GFEntryExpiration' );
		
	}

}

function gf_entryexpiration() {
	return GFEntryExpiration::get_instance();
}

function gf_entryexpiration_delete_old_entries_action() {
	gf_entryexpiration()->delete_old_entries();
}

function gf_entryexpiration_activation_routine() {
	$recurrence = apply_filters( 'gf_entryexpiration_recurrence', 'hourly' );
	wp_schedule_event( strtotime( 'midnight' ), $recurrence, 'gf_entryexpiration_delete_old_entries' );
}

function gf_entryexpiration_deactivation_routine() {
	wp_clear_scheduled_hook( 'gf_entryexpiration_delete_old_entries' );
}
