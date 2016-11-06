<?php

GFForms::include_addon_framework();

/**
 * Gravity Forms Entry Expiration.
 *
 * @since     1.0
 * @author    Travis Lopes
 * @copyright Copyright (c) 2016, Travis Lopes
 */
class GFEntryExpiration extends GFAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of Gravity Forms Entry Expiration.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_version Contains the version, defined from entryexpiration.php
	 */
	protected $_version = GF_ENTRYEXPIRATION_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '1.9.13';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravity-forms-entry-expiration';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravity-forms-entry-expiration/entryexpiration.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'http://travislop.es/plugins/gravity-forms-entry-expiration';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms Entry Expiration';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'Entry Expiration';

	/**
	 * Defines the expiration time types.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $expiration_time_types Expiration time types.
	 */
	protected $expiration_time_types = array( 'hours', 'days', 'weeks', 'months' );

	/**
	 * Get instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 * @static
	 *
	 * @return $_instance
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Register needed hooks.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function init() {

		parent::init();

		add_filter( 'gform_form_settings', array( $this, 'add_form_setting' ), 10, 2 );
		add_filter( 'gform_pre_form_settings_save', array( $this, 'save_form_setting' ) );

	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Setup plugin settings fields.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'title'       => '',
				'description' => '<p>' . esc_html__( 'Gravity Forms Entry Expiration reviews your forms every hour. If the form is selected for entry expiration, any entries older than the timeframe below will be deleted.', 'gravityformsentryexpiration' ) . '</p>',
				'fields'      => array(
					array(
						'name'          => 'gf_entryexpiration_expire_time',
						'label'         => __( 'Delete Entries After', 'gravityformsentryexpiration' ),
						'type'          => 'expiration_time',
						'class'         => 'small',
						'default_value' => array(
							'amount' =>	 30,
							'type'   => 'days',
						),
					),
					array(
						'type'     => 'save',
						'messages' => array(
							'success' => __( 'Settings have been saved.', 'gravityformsentryexpiration' ),
						),
					),
				),
			),
		);

	}

	/**
	 * Entry Expiration time settings field.
	 *
	 * @since  1.0
	 * @access public
	 * @param  array $field
	 * @param  bool  $echo (default: true)
	 *
	 * @return string $html
	 */
	public function settings_expiration_time( $field, $echo = true ) {

		// Initialize return HTML string.
		$html = '';

		// Initialize amount and type fields.
		$amount_field = $type_field = $field;

		// Setup amount field attributes.
		$amount_field['name']       .= '[amount]';
		$amount_field['input_type']  = 'number';

		// Prepare type choices.
		$type_choices = array();
		foreach ( $this->expiration_time_types as $type ) {
			$type_choices[] = array(
				'label' => esc_html( $type ),
				'value' => esc_html( $type ),
			);
		}

		// Setup type field attributes.
		$type_field['name']    .= '[type]';
		$type_field['choices']  = $type_choices;

		// Display amount and type fields.
		$html .= $this->settings_text( $amount_field, false );
		$html .= $this->settings_select( $type_field, false );

		// If field ouput should be echoed, echo it.
		if ( $echo ) {
			echo $html;
		}

		return $html;

	}





	// # FORM SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Add Entry Expiration form settings.
	 *
	 * @since  1.0
	 * @access public
	 * @param  array $settings Form settings.
	 * @param  array $form Current form object.
	 *
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
	 * @since  1.0
	 * @access public
	 * @param  array $form Current form object.
	 *
	 * @return array $form
	 */
	public function save_form_setting( $form ) {

		$form['gf_entryexpiration_include'] = sanitize_text_field( rgpost( 'gf_entryexpiration_include' ) );
		return $form;

	}





	// # ENTRY DELETION ------------------------------------------------------------------------------------------------

	/**
	 * Delete old entries.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function delete_old_entries() {

		// Get plugin settings.
		$settings = gf_entryexpiration()->get_plugin_settings();

		// If plugin has not been configured yet, do not delete any entries.
		if ( ! rgar( $settings, 'gf_entryexpiration_expire_time' ) ) {
			$this->log_debug( __METHOD__ . '(): Skipping entry deletion because plugin is not configured.' );
			return;
		}

		// Get expiration amount and type.
		$expiration_time_amount = sanitize_text_field( $settings['gf_entryexpiration_expire_time']['amount'] );
		$expiration_time_type   = sanitize_text_field( $settings['gf_entryexpiration_expire_time']['type'] );

		// Validate expiration time type.
		if ( ! in_array( $expiration_time_type, $this->expiration_time_types ) ) {
			$expiration_time_type = $this->expiration_time_types[0];
		}

		// Create expiration time string.
		$expiration_time = $expiration_time_amount . ' ' . $expiration_time_type;

		// Setup MySQL timestamp for which entries are older than.
		$older_than = date( 'Y-m-d H:i:s', strtotime( '-'. $expiration_time ) );

		// Get forms.
		$forms = GFAPI::get_forms();

		// Loop through forms.
		foreach ( $forms as $form ) {

			// If entry expiration is not enabled for form, skip it.
			if ( ! rgar( $form, 'gf_entryexpiration_include' ) ) {
				$this->log_debug( __METHOD__ . "(): Skipping entry deletion for form #{$form['id']} because it is not enabled." );
				continue;
			}

			/**
			 * Filter the entry expiration time.
			 *
			 * @since 1.2.3
			 *
			 * @param string $older_than Current entry expiration time.
			 * @param array  $form Form object.
			 */
			$form_older_than = gf_apply_filters( array( 'gf_entryexpiration_older_than', $form['id'] ), $older_than, $form );

			/**
			 * Set the payment status when searching for expired entries.
			 *
			 * @since 1.1
			 *
			 * @param string null Payment status.
			 * @param array  $form Form object.
			 */
			$payment_status = gf_apply_filters( array( 'gf_entryexpiration_payment', $form['id'] ), null, $form );

			// Log the entry search.
			$this->log_debug( __METHOD__ . "(): Searching entries for form #{$form['id']} that were created before {$form_older_than}." );

			// Get entry IDs for form that match search criteria.
			$entry_ids = GFFormsModel::get_lead_ids(
				$form['id'], // $form_id
				'', // $search
				null, // $star
				null, // $read
				null, // $start_date
				$form_older_than, // $end_date
				null, // $status
				$payment_status // $payment_status
			);

			/**
			 * Set the entry deletion limit to avoid long execution times.
			 *
			 * @since 1.1
			 *
			 * @param int 1000 Entry deletion limit.
			 */
			$deletion_limit = apply_filters( 'gf_entryexpiration_limit', 1000 );

			// Reduce entry IDs to entry deletion limit.
			$entry_ids = array_splice( $entry_ids, 0, $deletion_limit );

			// If entries were found, delete them.
			if ( ! empty( $entry_ids ) ) {
				$this->log_debug( __METHOD__ . "(): Deleting entries for form #{$form['id']}: " . implode( ', ', $entry_ids ) );
				GFFormsModel::delete_leads( $entry_ids );
			} else {
				$this->log_debug( __METHOD__ . "(): No entries were found for form #{$form['id']}." );
			}

		}

	}





	// # UPGRADE -------------------------------------------------------------------------------------------------------

	/**
	 * Migrate needed settings.
	 *
	 * @since  1.1.0
	 * @access public
	 * @param  string $previous_version Version number the plugin is upgrading from.
	 */
	public function upgrade( $previous_version ) {

		// Get plugin settings.
		$settings = $this->get_plugin_settings();

		// If existing scheduled event exists and is daily, remove and switch to hourly.
		if ( 'daily' === wp_get_schedule( 'gf_entryexpiration_delete_old_entries' ) ) {
			wp_clear_scheduled_hook( 'gf_entryexpiration_delete_old_entries' );
			wp_schedule_event( strtotime( 'midnight' ), 'hourly', 'gf_entryexpiration_delete_old_entries' );
		}

		// Upgrade: 1.1.0.
		if ( ! empty( $previous_version ) && version_compare( $previous_version, '1.1.0', '<' ) ) {

			// Get the forms.
			$forms = GFAPI::get_forms();

			// Loop through each form and switch to include setting where needed.
			foreach ( $forms as &$form ) {

				// If exclude is set, remove. Otherwise, set to include.
				if ( rgar( $form, 'gf_entryexpiration_exclude' ) ) {
					unset( $form['gf_entryexpiration_exclude'] );
				} else {
					$form['gf_entryexpiration_include'] = '1';
				}

				// Save form.
				GFAPI::update_form( $form );

			}

		}

		// Upgrade: 1.2.0
		if ( ! empty( $previous_version ) && version_compare( $previous_version, '1.2.0', '<' ) ) {

			// Change settings from "days" to allow hours, days, weeks and months.
			$settings['gf_entryexpiration_expire_time'] = array(
				'amount'	=>	$settings['gf_entryexpiration_days_old'],
				'type'		=>	'days'
			);

			// Remove days old setting.
			unset( $settings['gf_entryexpiration_days_old'] );

			// Save settings.
			$this->update_plugin_settings( $settings );

		}

	}

}
