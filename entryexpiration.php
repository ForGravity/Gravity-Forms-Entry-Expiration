<?php
	
	/*
	Plugin Name: Gravity Forms Entry Expiration
	Plugin URI: http://github.com/travislopes/Gravity-Forms-Entry-Expiration
	Description: Provides a simple way to remove old entries in Gravity Forms.
	Version: 1.0.0
	Author: travislopes
	Author URI: http://travislop.es
	Text Domain: gravityformsentryexpiration
	Domain Path: /languages
	*/
	
	if ( class_exists( 'GFForms' ) ) {
		
		GFForms::include_addon_framework();
		
		class GFEntryExpiration extends GFAddOn {
		
			protected $_version = '1.0.0';
			protected $_min_gravityforms_version = '1.8.17';
			protected $_slug = 'gravityformsentryexpiration';
			protected $_path = 'gravityformsentryexpiration/entryexpiration.php';
			protected $_full_path = __FILE__;
			protected $_url = 'http://github.com/travislopes/Gravity-Forms-Entry-Expiration';
			protected $_title = 'Gravity Forms Entry Expiration';
			protected $_short_title = 'Entry Expiration';
		
			private static $_instance = null;
		
			public static function get_instance() {
				if ( self::$_instance == null ) {
					self::$_instance = new GFentryexpiration();
				}
		
				return self::$_instance;
			}
			
			public function init() {
				parent::init();
				
				add_filter( 'gform_form_settings', array( $this, 'add_form_setting'), 10, 2 );
				add_filter( 'gform_pre_form_settings_save', array( $this, 'save_form_setting') );
			}
			
			// Plugin settings page
			public function plugin_settings_fields() {
				return array(
					array(
						'title'			=> '',
						'description'	=> __( '<p>Gravity Forms Entry Cleaner examines your entries every night at midnight and deletes any entries older than the timeframe designated below.</p>', 'gravityformsentryexpiration' ),
						'fields'		=> array(
							array(
								'name'		    => 'gf_entryexpiration_days_old',
								'label'		    => __( 'Delete Entries After', 'gravityformsentryexpiration' ),
								'type'		    => 'numeric',
								'class'         => 'small',
								'after_input'   => 'days',
							),
							array(
								'type'		=> 'save',
								'messages'	=> array(
									'success'	=> __( 'Settings have been saved.', 'gravityformsentryexpiration' )
								),
							),
						),
					),
				);
			}
			
			// Numeric field type
			protected function settings_numeric( $field, $echo = true ) {
		
				$field['type'] = 'text'; //making sure type is set to text
				$attributes    = $this->get_field_attributes( $field );
				$default_value = rgar( $field, 'value' ) ? rgar( $field, 'value' ) : rgar( $field, 'default_value' );
				$value         = $this->get_setting( $field['name'], $default_value );
		
		
				$name    = esc_attr( $field['name'] );
				$tooltip = isset( $choice['tooltip'] ) ? gform_tooltip( $choice['tooltip'], rgar( $choice, 'tooltip_class' ), true ) : '';
				$html    = '';
		
				$html .= '<input
		                    type="number"
		                    name="_gaddon_setting_' . esc_attr( $field['name'] ) . '"
		                    value="' . esc_attr( $value ) . '" ' .
					implode( ' ', $attributes ) .
					' />';
					
				if ( isset( $field['after_input'] ) )
					$html .= ' '. esc_html( $field['after_input'] );
		
				$feedback_callback = rgar( $field, 'feedback_callback' );
				if ( is_callable( $feedback_callback ) ) {
					$is_valid = call_user_func_array( $feedback_callback, array( $value, $field ) );
					$icon     = '';
					if ( $is_valid === true )
						$icon = 'icon-check fa-check gf_valid'; // check icon
					else if ( $is_valid === false )
						$icon = 'icon-remove fa-times gf_invalid'; // x icon
		
					if ( ! empty( $icon ) )
						$html .= "&nbsp;&nbsp;<i class=\"fa {$icon}\"></i>";
				}
		
				if ( $this->field_failed_validation( $field ) )
					$html .= $this->get_error_icon( $field );
		
				if ( $echo )
					echo $html;
		
				return $html;
			}
		
			// Form settings page
			public function add_form_setting( $settings, $form ) {
				
	            $settings['Form Options']['gf_entryexpiration_exclude'] = '
		        <tr>
		            <th>
		            	<label for="gf_entryexpiration_exclude">'. __( 'Entry expiration', 'gravityformsentryexpiration' ) .'</label>
		            	<a href="#" onclick="return false;" class="gf_tooltip tooltip tooltip_form_honeypot" title="'. __( '<h6>Exclude Entries From Deletion</h6> Selecting this checkbox will exclude this form\'s entries from being deleted when all old entries are deleted.', 'gravityformsentryexpiration' ) .'"><i class="fa fa-question-circle"></i></a>
		            </th>
		            <td>
		            	<input type="checkbox" id="gf_entryexpiration_exclude" name="gf_entryexpiration_exclude" value="1"'. ( ( rgar( $form, 'gf_entryexpiration_exclude' ) == '1' ) ? ' checked="checked"' : '' ) .' />
		            	<label for="gf_entryexpiration_exclude">'. __( 'Exclude from entry expiration', 'gravityformsentryexpiration' ) .'</labe>
		            </td>
		        </tr>';

	            return $settings;
			}
			
			// Save form settings
			public function save_form_setting( $form ) {
				$form['gf_entryexpiration_exclude'] = rgpost( 'gf_entryexpiration_exclude' );
				return $form;
			}
			
			// Delete old entries
			public function delete_old_entries() {
				
				$plugin_settings = get_option( 'gravityformsaddon_gravityformsentryexpiration_settings' );
				if ( empty ( $plugin_settings['gf_entryexpiration_days_old'] ) ) return;
			
				// Setup MySQL timestamp for which entries are older than
				$older_than = date( 'Y-m-d H:i:s', strtotime( 'midnight -'. $plugin_settings['gf_entryexpiration_days_old'] .' days' ) );
				
				// Setup empty array of entries to delete
				$entry_ids = array();
				
				// Loop through each form
				$forms = GFFormsModel::get_form_ids();
				foreach ( $forms as &$form ) {
					
					// Get form meta
					$form = GFFormsModel::get_form_meta( $form );
					
					// Execute if form is not excluded from entries
					if ( empty ( $form['gf_entryexpiration_exclude'] ) ) {
					
						// Get entries for form
						$form_entries = GFFormsModel::get_lead_ids(
							$form['id'], // $form_id
							'', // $search
							null, // $star
							null, // $read
							null, // $start_date
							$older_than, // $end_date
							null, // $status
							null // $payment_status
						);
						
						foreach ( $form_entries as $form_entry ) {
							$entry_ids[] = $form_entry;
						}
					
					}
				}
								
				// Delete the entries
				if( !empty ( $entry_ids ) ) 
					GFFormsModel::delete_leads( $entry_ids );

			}
			
			// Create entry deletion cron
			public function create_cron() {
				wp_schedule_event( strtotime( 'midnight' ), 'daily', 'gf_entryexpiration_delete_old_entries' );
			}
			
			// Remove entry deletion cron
			public function remove_cron() {
				wp_clear_scheduled_hook( 'gf_entryexpiration_delete_old_entries' );
			}
			
		}
				
		// Initialize the class
		new GFEntryExpiration();
		
	}
	
	// Activation hooks
	register_activation_hook( __FILE__, array( 'GFEntryExpiration', 'create_cron' ) );
	register_deactivation_hook( __FILE__, array( 'GFEntryExpiration', 'remove_cron' ) );
	
	// Register cron action
	add_action( 'gf_entryexpiration_delete_old_entries', 'gf_entryexpiration_delete_old_entries_action' );
	function gf_entryexpiration_delete_old_entries_action() {
		if ( class_exists( 'GFEntryExpiration' ) )
			GFEntryExpiration::delete_old_entries();
	}
