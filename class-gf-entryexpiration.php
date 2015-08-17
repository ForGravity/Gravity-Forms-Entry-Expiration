<?php
	
GFForms::include_addon_framework();

class GFEntryExpiration extends GFAddOn {

	protected $_version = GF_ENTRYEXPIRATION_VERSION;
	protected $_min_gravityforms_version = '1.9.13';
	protected $_slug = 'gravity-forms-entry-expiration';
	protected $_path = 'gravity-forms-entry-expiration/entryexpiration.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://travislop.es/plugins/gravity-forms-entry-expiration';
	protected $_title = 'Gravity Forms Entry Expiration';
	protected $_short_title = 'Entry Expiration';
	private static $_instance = null;
	
	protected $expiration_time_types = array( 'hours', 'days', 'weeks', 'months' );

	/**
	 * Get instance of this class.
	 * 
	 * @access public
	 * @static
	 * @return $_instance
	 */
	public static function get_instance() {
		
		if ( self::$_instance == null ) {
			self::$_instance = new self;
		}

		return self::$_instance;
		
	}
	
	/**
	 * Register needed hooks.
	 * 
	 * @access public
	 * @return void
	 */
	public function init() {
		
		parent::init();
		
		add_filter( 'gform_form_settings', array( $this, 'add_form_setting' ), 10, 2 );
		add_filter( 'gform_pre_form_settings_save', array( $this, 'save_form_setting' ) );
		
	}
	
	/**
	 * Setup plugin settings fields.
	 * 
	 * @access public
	 * @return array
	 */
	public function plugin_settings_fields() {
		
		return array(
			array(
				'title'			=> '',
				'description'	=> '<p>' . esc_html__( 'Gravity Forms Entry Expiration reviews your forms every hour. If the form is selected for entry expiration, any entries older than the timeframe below will be deleted.', 'gravityformsentryexpiration' ) . '</p>',
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

	/**
	 * Entry Expiration time settings field.
	 * 
	 * @access public
	 * @param array $field
	 * @param bool $echo (default: true)
	 * @return string $html
	 */
	public function settings_expiration_time( $field, $echo = true ) {

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

	/**
	 * Add Entry Expiration form settings.
	 * 
	 * @access public
	 * @param array $settings
	 * @param array $form
	 * @return array $settings
	 */
	public function add_form_setting( $settings, $form ) {
		
		$settings['Form Options']['gf_entryexpiration_include']  = '<tr>';
		$settings['Form Options']['gf_entryexpiration_include'] .= '<th>';
		$settings['Form Options']['gf_entryexpiration_include'] .= '<label for="gf_entryexpiration_include">'. esc_html__( 'Entry Expiration', 'gravityformsentryexpiration' ) . '</label>';
		$settings['Form Options']['gf_entryexpiration_include'] .= '<a href="#" onclick="return false;" class="gf_tooltip tooltip tooltip_form_honeypot" title="'. esc_html__( '<h6>Include Entries In Deletion</h6> Selecting this checkbox will include this form\'s entries in being deleted when all old entries are deleted.', 'gravityformsentryexpiration' ) . '"><i class="fa fa-question-circle"></i></a>';
		$settings['Form Options']['gf_entryexpiration_include'] .= '</th>';
		$settings['Form Options']['gf_entryexpiration_include'] .= '<td>';
		$settings['Form Options']['gf_entryexpiration_include'] .= '<input type="checkbox" id="gf_entryexpiration_include" name="gf_entryexpiration_include" value="1" '. checked( '1', rgar( $form, 'gf_entryexpiration_include' ), false ) .' />';
		$settings['Form Options']['gf_entryexpiration_include'] .= '<label for="gf_entryexpiration_include">'. esc_html__( 'Include entries for expiration', 'gravityformsentryexpiration' ) . '</label>';
		$settings['Form Options']['gf_entryexpiration_include'] .= '</td>';
		$settings['Form Options']['gf_entryexpiration_include'] .= '</tr>';
		
		return $settings;
		
	}
	
	/**
	 * Save Entry Expiration form settings.
	 * 
	 * @access public
	 * @param array $form
	 * @return array $form
	 */
	public function save_form_setting( $form ) {
		
		$form['gf_entryexpiration_include'] = rgpost( 'gf_entryexpiration_include' );
		return $form;
		
	}
	
	/**
	 * Delete old entries.
	 * 
	 * @access public
	 * @return void
	 */
	public function delete_old_entries() {
		
		/* Get plugin settings */
		$settings = gf_entryexpiration()->get_plugin_settings();
		
		/* If plugin has not been configured yet, do not delete any entries. */
		if ( rgblank( $settings['gf_entryexpiration_expire_time'] ) ) {
			return;
		}
	
		/* Create expiration time string */
		$expiration_time = $settings['gf_entryexpiration_expire_time']['amount'] . ' ' . $settings['gf_entryexpiration_expire_time']['type'];

		/* Setup MySQL timestamp for which entries are older than */
		$older_than = date( 'Y-m-d H:i:s', strtotime( '-'. $expiration_time ) );
		
		/* Setup empty array of entries to delete */
		$entry_ids = array();
		
		/* Loop through each form */
		$forms = GFAPI::get_forms();
		foreach ( $forms as &$form ) {

			/* If entry expiration is not enabled, continue. */
			if ( ! rgar( $form, 'gf_entryexpiration_include' ) ) {
				continue;
			}
			
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
			
			if ( ! empty( $form_entries ) ) {
				
				foreach ( $form_entries as $form_entry ) {
					$entry_ids[] = $form_entry;
				}
				
			}

		}
		
		/* Limit amount of entries to be deleted in this pass to avoid long execution times. */
		$entry_ids = array_splice( $entry_ids, 0, apply_filters( 'gf_entryexpiration_limit', 1000 ) );
		
		/* Delete the entries/ */
		if ( ! empty ( $entry_ids ) ) {
			GFFormsModel::delete_leads( $entry_ids );
		}

	}
	
	/**
	 * Migrate needed settings.
	 * 
	 * @access public
	 * @param string $previous_version
	 * @return void
	 */
	public function upgrade( $previous_version ) {
		
		$settings = $this->get_plugin_settings();

		/* If existing scheduled event exists and is daily, remove for switch to hourly. */
		if ( wp_get_schedule( 'gf_entryexpiration_delete_old_entries' ) === 'daily' ) {
			wp_clear_scheduled_hook( 'gf_entryexpiration_delete_old_entries' );
			wp_schedule_event( strtotime( 'midnight' ), 'hourly', 'gf_entryexpiration_delete_old_entries' );
		}
		
		/* Upgrade: 1.1.0 */
		if ( $previous_version === '1.0.0' ) {
			
			/* Get the forms */
			$forms = GFAPI::get_forms();
			
			/* Loop through each form and switch to include setting where needed. */
			foreach ( $forms as &$form ) {
				
				/* If exclude is set, remove. Otherwise, set to include. */
				if ( rgar( $form, 'gf_entryexpiration_exclude' ) ) {
					
					unset( $form['gf_entryexpiration_exclude'] );
					
				} else {
					
					$form['gf_entryexpiration_include'] = '1';
					
				}

				/* Update form. */
				GFAPI::update_form( $form );

			}
			
		}

		/* Upgrade: 1.2.0 */
		if ( ! rgblank( $settings['gf_entryexpiration_days_old'] ) ) {
			
			/* Change settings from "days" to allow hours, days, weeks and months. */
			$settings['gf_entryexpiration_expire_time'] = array(
				'amount'	=>	$plugin_settings['gf_entryexpiration_days_old'],
				'type'		=>	'days'
			);
			
			/* Remove days old setting */
			unset( $settings['gf_entryexpiration_days_old'] );
			
			/* Save settings */
			$this->update_plugin_settings( $settings );
								
		}
		
	}
	
}
