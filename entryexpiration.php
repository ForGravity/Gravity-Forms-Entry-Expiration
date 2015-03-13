<?php
	
	/*
	Plugin Name: Gravity Forms Entry Expiration
	Plugin URI: http://travislop.es/plugins/gravity-forms-entry-expiration/
	Description: Provides a simple way to remove old entries in Gravity Forms.
	Version: 1.2.1
	Author: travislopes
	Author URI: http://travislop.es
	Text Domain: gravityformsentryexpiration
	Domain Path: /languages
	*/
	
	if ( class_exists( 'GFForms' ) ) {
		
		GFForms::include_addon_framework();
		
		class GFEntryExpiration extends GFAddOn {
		
			protected $_version = '1.2.1';
			protected $_min_gravityforms_version = '1.8.17';
			protected $_slug = 'gravityformsentryexpiration';
			protected $_path = 'gravityformsentryexpiration/entryexpiration.php';
			protected $_full_path = __FILE__;
			protected $_url = 'http://github.com/travislopes/Gravity-Forms-Entry-Expiration';
			protected $_title = 'Gravity Forms Entry Expiration';
			protected $_short_title = 'Entry Expiration';
			
			protected $expiration_time_types = array( 'hours', 'days', 'weeks', 'months' );
		
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
						'description'	=> __( '<p>Gravity Forms Entry Cleaner examines your entries every hour and deletes any entries older than the timeframe designated below.</p>', 'gravityformsentryexpiration' ),
						'fields'		=> array(
							array(
								'name'		    => 'gf_entryexpiration_expire_time',
								'label'		    => __( 'Delete Entries After', 'gravityformsentryexpiration' ),
								'type'		    => 'expiration_time',
								'class'         => 'small',
								'default_value' => array(
									'amount'		=>	30,
									'type'			=>	'days'
								),
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

			// Expiration time field type
			protected function settings_expiration_time( $field, $echo = true ) {
		
				$field['type'] = 'text'; //making sure type is set to text
				$attributes    = $this->get_field_attributes( $field );
				$default_value = rgar( $field, 'value' ) ? rgar( $field, 'value' ) : rgar( $field, 'default_value' );
				$value         = $this->get_setting( $field['name'], $default_value );
		
				$name    = esc_attr( $field['name'] );
				$tooltip = isset( $choice['tooltip'] ) ? gform_tooltip( $choice['tooltip'], rgar( $choice, 'tooltip_class' ), true ) : '';
				$html    = '';
		
				/* Add amount field */
				$html .= '<input type="number" name="_gaddon_setting_' . esc_attr( $field['name'] ) . '[amount]" value="' . esc_attr( $value['amount'] ) . '" ' . implode( ' ', $attributes ) . ' />';
				
				/* Add type field */
				$html .= '<select name="_gaddon_setting_' . esc_attr( $field['name'] ) . '[type]" ' . implode( ' ', $attributes ) . ' />';
				
				foreach( $this->expiration_time_types as $type ) {
					
					$html .= '<option value="'. $type .'" '. selected( $type, $value['type'], false ) .'>'. $type .'</option>';
					
				}
				
				$html .= '</select>';
							
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
				
				$settings['Form Options']['gf_entryexpiration_include'] = '
				<tr>
					<th>
						<label for="gf_entryexpiration_include">'. __( 'Entry expiration', 'gravityformsentryexpiration' ) .'</label>
						<a href="#" onclick="return false;" class="gf_tooltip tooltip tooltip_form_honeypot" title="'. __( '<h6>Include Entries In Deletion</h6> Selecting this checkbox will include this form\'s entries in being deleted when all old entries are deleted.', 'gravityformsentryexpiration' ) .'"><i class="fa fa-question-circle"></i></a>
					</th>
					<td>
						<input type="checkbox" id="gf_entryexpiration_include" name="gf_entryexpiration_include" value="1"'. ( ( rgar( $form, 'gf_entryexpiration_include' ) == '1' ) ? ' checked="checked"' : '' ) .' />
						<label for="gf_entryexpiration_include">'. __( 'Include in entry expiration', 'gravityformsentryexpiration' ) .'</labe>
					</td>
				</tr>';
				
				return $settings;
				
			}
			
			// Save form settings
			public function save_form_setting( $form ) {
				
				$form['gf_entryexpiration_include'] = rgpost( 'gf_entryexpiration_include' );
				return $form;
				
			}
			
			// Delete old entries
			public function delete_old_entries() {
				
				/* Get plugin settings */
				$plugin_settings = get_option( 'gravityformsaddon_gravityformsentryexpiration_settings' );
				
				/* If plugin has not been configured yet, do not delete any entries. */
				if ( empty ( $plugin_settings['gf_entryexpiration_expire_time'] ) ) return;
			
				/* Create expiration time string */
				$expiration_time = $plugin_settings['gf_entryexpiration_expire_time']['amount'] . ' ' . $plugin_settings['gf_entryexpiration_expire_time']['type'];

				// Setup MySQL timestamp for which entries are older than
				$older_than = date( 'Y-m-d H:i:s', strtotime( '-'. $expiration_time ) );
				
				// Setup empty array of entries to delete
				$entry_ids = array();
				
				// Loop through each form
				$forms = GFFormsModel::get_form_ids();
				foreach ( $forms as &$form ) {
		
					// Get form meta
					$form = GFFormsModel::get_form_meta( $form );
					
					// Execute if form is included from entries
					if ( empty ( $form['gf_entryexpiration_include'] ) ) {
					
						// Get entries for form
						$form_entries = GFFormsModel::get_lead_ids(
							$form['id'], // $form_id
							'', // $search
							null, // $star
							null, // $read
							null, // $start_date
							$older_than, // $end_date
							null, // $status
							apply_filters( 'gf_entryexpiration_payment_' . $form['id'], apply_filters( 'gf_entryexpiration_payment', null, $form ), $form ) // $payment_status
						);
						
						foreach ( $form_entries as $form_entry ) {
							$entry_ids[] = $form_entry;
						}
					
					}
				}
				
				// Limit amount of entries to be deleted in this pass to avoid long execution times.
				$entry_ids = array_splice( $entry_ids, 0, apply_filters( 'gf_entryexpiration_limit', 1000 ) );
				
				// Delete the entries
				if( ! empty ( $entry_ids ) ) 
					GFFormsModel::delete_leads( $entry_ids );

			}
			
			// Run needed functions for activation
			public function run_activation_routine() {
				
				global $wpdb;
				
				/* Get previously installed version */
				$previous_version = get_option( 'gf_entryexpiration_version', '1.0.0' );
				
				/* Get plugin settings */
				$plugin_settings = get_option( 'gravityformsaddon_gravityformsentryexpiration_settings' );				
				
				/* If existing scheduled event exists and is daily, remove for switch to hourly. */
				if ( wp_get_schedule( 'gf_entryexpiration_delete_old_entries' ) === 'daily' ) {
					
					$was_scheduled = true;
					wp_clear_scheduled_hook( 'gf_entryexpiration_delete_old_entries' );
					
				} else {
					
					$was_scheduled = false;
					
				}
				
				/* Register cron */
				wp_schedule_event( strtotime( 'midnight' ), 'hourly', 'gf_entryexpiration_delete_old_entries' );
			
				/* Upgrade: 1.1.0 */
				if ( version_compare( $previous_version, '1.1.0', '<' ) && $was_scheduled ) {
					
					/* Get the current Gravity Forms */
					$forms = GFFormsModel::get_form_ids();
					$meta_table_name = GFFormsModel::get_meta_table_name();
					
					/* Loop through each form and switch to include setting where needed */
					foreach ( $forms as $form_id ) {

						/* Get form display meta. */
						$form_meta = $wpdb->get_var( $wpdb->prepare( "SELECT display_meta FROM {$meta_table_name} WHERE form_id=%d", $form_id ) );

						/* Decode JSON string */
						$form_meta = json_decode( $form_meta, true );

						/* If exclude is set, delete meta. */
						if ( ! empty ( $form_meta['gf_entryexpiration_exclude'] ) ) {
						
							unset( $form_meta['gf_entryexpiration_exclude'] );
						
						/* If exclude is not set, set to include. */
						
						} else {
							
							$form_meta['gf_entryexpiration_include'] = '1';
							
						}
						
						/* Update form meta. */
						$wpdb->query( $wpdb->prepare( "UPDATE $meta_table_name SET display_meta=%s WHERE form_id=%d", json_encode( $form_meta), $form_id ) );

					}
					
				}

				/* Upgrade: 1.2.0 */
				if ( ! empty( $plugin_settings['gf_entryexpiration_days_old'] ) ) {
					
					/* Change settings from "days" to allow hours, days, weeks and months. */
					$plugin_settings['gf_entryexpiration_expire_time'] = array(
						'amount'	=>	$plugin_settings['gf_entryexpiration_days_old'],
						'type'		=>	'days'
					);
					
					/* Remove days old setting */
					unset( $plugin_settings['gf_entryexpiration_days_old'] );
					
					/* Save settings */
					update_option( 'gravityformsaddon_gravityformsentryexpiration_settings', $plugin_settings );
										
				}
				
				/* Set current version */
				update_option( 'gf_entryexpiration_version', '1.1.1' );
			
			}
			
			// Run needed functions for deactivation
			public function run_deactivation_routine() {
				
				wp_clear_scheduled_hook( 'gf_entryexpiration_delete_old_entries' );
			
			}
			
		}
				
		// Initialize the class
		new GFEntryExpiration();
		
	}
	
	// Activation hooks
	register_activation_hook( __FILE__, array( 'GFEntryExpiration', 'run_activation_routine' ) );
	register_deactivation_hook( __FILE__, array( 'GFEntryExpiration', 'run_deactivation_routine' ) );
	
	// Register cron action
	add_action( 'gf_entryexpiration_delete_old_entries', 'gf_entryexpiration_delete_old_entries_action' );
	function gf_entryexpiration_delete_old_entries_action() {
		if ( class_exists( 'GFEntryExpiration' ) )
			GFEntryExpiration::delete_old_entries();
	}
	