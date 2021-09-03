<?php

GFForms::include_addon_framework();

/**
 * Gravity Forms Entry Expiration.
 *
 * @since     1.0
 * @author    Travis Lopes
 * @copyright Copyright (c) 2016, Travis Lopes
 */
class GF_Entry_Expiration extends GFAddOn {

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
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  2.0.4
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'forgravity_entryexpiration';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  2.0.4
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'forgravity_entryexpiration';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  2.0.4
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'forgravity_entryexpiration_uninstall';

	/**
	 * Defines the capabilities needed for Entry Expiration.
	 *
	 * @since  2.0.4
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'forgravity_entryexpiration', 'forgravity_entryexpiration_uninstall' );

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
	 * Register needed pre-initialization hooks.
	 *
	 * @since  2.0
	 * @access public
	 */
	public function pre_init() {

		parent::pre_init();

		add_filter( 'cron_schedules', array( $this, 'add_cron_schedule' ) );

		/**
		 * Define recurrence for Entry Expiration cron event.
		 *
		 * @param string $recurrence How often Entry Expiration cron event should run.
		 */
		$recurrence = apply_filters( 'gf_entryexpiration_recurrence', 'fifteen_minutes' );

		if ( ! wp_next_scheduled( 'gf_entryexpiration_maybe_expire' ) ) {
			$scheduled = wp_schedule_event( strtotime( 'midnight'), $recurrence, 'gf_entryexpiration_maybe_expire' );
		}

		add_action( 'gf_entryexpiration_maybe_expire', array( $this, 'maybe_run_expiration' ) );

		add_action( 'admin_init', array( $this, 'maybe_display_upgrade_message' ) );

	}

	/**
	 * Enqueue needed stylesheets.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function styles() {

		$styles = array(
			array(
				'handle'  => $this->_slug . '_form_settings',
				'src'     => $this->get_base_url() . '/css/form_settings.css',
				'version' => $this->_version,
				'enqueue' => array( array( 'admin_page' => array( 'form_settings' ) ) ),
			),
		);

		return array_merge( parent::styles(), $styles );

	}





	// # SETUP ---------------------------------------------------------------------------------------------------------

	/**
	 * Add quarter-hourly cron schedule.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @param array $schedules An array of non-default cron schedules.
	 *
	 * @return array
	 */
	public function add_cron_schedule( $schedules = array() ) {

		// Add fifteen minutes.
		if ( ! isset( $schedules['fifteen_minutes'] ) ) {
			$schedules['fifteen_minutes'] = array(
				'interval' => 15 * MINUTE_IN_SECONDS,
				'display'  => esc_html__( 'Every Fifteen Minutes', 'gravity-forms-entry-expiration' ),
			);
		}

		return $schedules;

	}

	/**
	 * Display upgrade message.
	 *
	 * @since 2.1
	 */
	public function maybe_display_upgrade_message() {

		// If message has already been displayed, exit.
		if ( get_option( 'gf_entryexpiration_message_displayed', false ) ) {
			return;
		}

		// Get lifetime processed entries.
		$lifetime_processed = get_option( 'gf_entryexpiration_lifetime_processed', 0 );

		// If we have not yet processed 100 entries, exit.
		if ( $lifetime_processed < 100 ) {
			return;
		}

		// Round processed entries.
		$lifetime_processed = ceil( $lifetime_processed / 100 ) * 100;

		// Prepare message.
		$message = sprintf(
			'Entry Expiration has <strong>removed over %d entries!</strong><br /><a href="%s">Upgrade to Entry Automation</a> to delete entries based off conditional logic, delete only specific fields from an entry and automatically export entries!',
			$lifetime_processed,
			'https://forgravity.com/plugins/entry-automation/?utm_source=wordpress&utm_medium=alert&utm_campaign=entry_expiration'
		);

		// Display message.
		GFCommon::add_dismissible_message( $message, 'gf_entryexpiration_upgrade_message', 'success', 'update_options', true );

		// Set flag that message was displayed.
		update_option( 'gf_entryexpiration_message_displayed', true );

	}




	// # UNINSTALL -----------------------------------------------------------------------------------------------------

	/**
	 * Remove cron event.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @param array $schedules An array of non-default cron schedules.
	 *
	 * @return array
	 */
	public function uninstall( $schedules = array() ) {

		wp_clear_scheduled_hook( 'gf_entryexpiration_maybe_expire' );

	}





	// # FORM SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Setup fields for form settings.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @param array $form The current form object.
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {

		return array(
			array(
				'fields' => array(
					array(
						'name'       => 'deletionEnable',
						'label'      => esc_html__( 'Enable Expiration', 'gravity-forms-entry-expiration' ),
						'type'       => 'checkbox',
						'onclick'    => "jQuery( this ).parents( 'form' ).submit()",
						'choices'    => array(
							array(
								'name'  => 'deletionEnable',
								'label' => esc_html__( 'Automatically delete form entries on a defined schedule', 'gravity-forms-entry-expiration' ),
							),
						),
					),
					array(
						'name'       => 'deletionDate',
						'label'      => esc_html__( 'Delete entries older than', 'gravity-forms-entry-expiration' ),
						'type'       => 'text_select',
						'required'   => true,
						'dependency' => array( 'field' => 'deletionEnable', 'values' => array( '1' ) ),
						'text'       => array(
							'name'        => 'deletionDate[number]',
							'class'       => 'small',
							'input_type'  => 'number',
							'after_input' => ' ',
						),
						'select'     => array(
							'name'    => 'deletionDate[unit]',
							'choices' => array(
								array( 'label' => 'minutes', 'value' => esc_html__( 'minutes', 'gravity-forms-entry-expiration' ) ),
								array( 'label' => 'hours',   'value' => esc_html__( 'hours', 'gravity-forms-entry-expiration' ) ),
								array( 'label' => 'days',    'value' => esc_html__( 'days', 'gravity-forms-entry-expiration' ) ),
								array( 'label' => 'weeks',   'value' => esc_html__( 'weeks', 'gravity-forms-entry-expiration' ) ),
								array( 'label' => 'months',  'value' => esc_html__( 'months', 'gravity-forms-entry-expiration' ) ),
								array( 'label' => 'years',   'value' => esc_html__( 'years', 'gravity-forms-entry-expiration' ) ),
							),
						),
					),
					array(
						'name'       => 'deletionRunTime',
						'label'      => esc_html__( 'Run deletion every', 'gravity-forms-entry-expiration' ),
						'type'       => 'text_select',
						'required'   => true,
						'dependency' => array( 'field' => 'deletionEnable', 'values' => array( '1' ) ),
						'text'       => array(
							'name'        => 'deletionRunTime[number]',
							'class'       => 'small',
							'input_type'  => 'number',
							'after_input' => ' ',
						),
						'select'     => array(
							'name'    => 'deletionRunTime[unit]',
							'choices' => array(
								array( 'label' => 'hours', 'value' => esc_html__( 'hours', 'gravity-forms-entry-expiration' ) ),
								array( 'label' => 'days',  'value' => esc_html__( 'days', 'gravity-forms-entry-expiration' ) ),
							),
						),
					),
				),
			),
			array(
				'fields' => array(
					array(
						'type'  => 'save',
						'messages' => array(
							'error'   => esc_html__( 'There was an error while saving the Entry Expiration settings. Please review the errors below and try again.', 'gravity-forms-entry-expiration' ),
							'success' => esc_html__( 'Entry Expiration settings updated.', 'gravity-forms-entry-expiration' ),
						),
					),
				),
			),
		);

	}

	/**
	 * Render a select settings field.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @param array $field Field settings.
	 * @param bool  $echo  Display field. Defaults to true.
	 *
	 * @uses GFAddOn::field_failed_validation()
	 * @uses GFAddOn::get_error_icon()
	 * @uses GFAddOn::get_field_attributes()
	 * @uses GFAddOn::get_select_options()
	 * @uses GFAddOn::get_setting()
	 *
	 * @return string
	 */
	public function settings_select( $field, $echo = true ) {

		// Get after select value.
		$after_select = rgar( $field, 'after_select' );

		// Remove after select property.
		unset( $field['after_select'] );

		// Get select field markup.
		$html = parent::settings_select( $field, false );

		// Add after select.
		if ( ! rgblank( $after_select ) ) {
			$html .= ' ' . $after_select;
		}

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Render a text and select settings field.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @param array $field Field settings.
	 * @param bool  $echo  Display field. Defaults to true.
	 *
	 * @return string
	 */
	public function settings_text_select( $field, $echo = true ) {

		// Initialize return HTML.
		$html = '';

		// Prepare text field.
		$text_field   = $field['text'];
		$text_field['type'] = 'text';

		// Prepare select field.
		$select_field         = $field['select'];
		$select_field['type'] = 'select';

		$html .= $this->settings_text( $text_field, false );
		$html .= $this->settings_select( $select_field, false );

		if ( $this->field_failed_validation( $field ) ) {
			$html .= $this->get_error_icon( $field );
		}

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Validates a text and select settings field.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @param array $field    Field settings.
	 * @param array $settings Submitted settings values.
	 */
	public function validate_text_select_settings( $field, $settings ) {

		// Convert text field name.
		$text_field_name = str_replace( array( '[', ']' ), array( '/', '' ), $field['text']['name'] );

		// Get text field value.
		$text_field_value = rgars( $settings, $text_field_name );

		// If text field is empty and field is required, set error.
		if ( rgblank( $text_field_value ) && rgar( $field, 'required' ) ) {
			$this->set_field_error( $field, esc_html__( 'This field is required.', 'gravity-forms-entry-expiration' ) );
			return;
		}

		// If text field is not numeric, set error.
		if ( ! rgblank( $text_field_value ) && ! ctype_digit( $text_field_value ) ) {
			$this->set_field_error( $field, esc_html__( 'You must use a whole number.', 'gravity-forms-entry-expiration' ) );
			return;
		}

	}

	/**
	 * Define the title for the form settings page.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @return string
	 */
	public function form_settings_page_title() {

		return esc_html__( 'Entry Expiration Settings', 'gravity-forms-entry-expiration' );

	}





	// # ENTRY EXPIRATION ----------------------------------------------------------------------------------------------

	/**
	 * Run Entry Expiration on forms that pass expiration conditions.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @uses GFAPI::get_forms()
	 * @uses GF_Entry_Expiration::maybe_run_deletion()
	 */
	public function maybe_run_expiration() {

		// Get forms.
		$forms = GFAPI::get_forms();

		// Loop through forms.
		foreach ( $forms as $form ) {

			// Get Entry Expiration settings.
			$settings = rgar( $form, $this->_slug );

			// If deletion is enabled, run deletion.
			if ( rgar( $settings, 'deletionEnable' ) ) {
				$this->maybe_run_deletion( $form, $settings );
			}

		}

	}

	/**
	 * Delete entries if form pass conditions.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $form     The form object.
	 * @param array $settings Entry Expiration settings.
	 *
	 * @uses GFAPI::count_entries()
	 * @uses GF_Entry_Expiration::delete_form_entries()
	 * @uses GF_Entry_Expiration::get_search_criteria()
	 */
	public function maybe_run_deletion( $form, $settings ) {

		// Get Entry Expiration transient for deletion.
		$transient_exists = get_transient( $this->_slug . '_' . $form['id'] );

		// If transient exists, skip form.
		if ( '1' === $transient_exists ) {
			$this->log_debug( __METHOD__ . '(): Skipping deletion for form #' . $form['id'] . ' because it is not due to be run yet.' );
			return;
		}

		// Define next run time.
		$next_run_time = $this->prepare_next_run_time( $settings );

		// Get search criteria for form.
		$search_criteria = $this->get_search_criteria( $settings, $form );

		// Log the search criteria.
		$this->log_debug( __METHOD__ . '(): Search criteria for form #' . $form['id'] . ': ' . print_r( $search_criteria, true ) );

		// Get entries found for search criteria.
		$found_entries = GFAPI::count_entries( $form['id'], $search_criteria );

		// If no entries were found, exit.
		if ( ! $found_entries ) {

			// Log that no entries were found.
			$this->log_debug( __METHOD__ . '(): Not deleting entries for form #' . $form['id'] . ' because no entries were found matching the search criteria.' );

			// Set transient.
			set_transient( $this->_slug . '_' . $form['id'], '1', $next_run_time );

			return;

		}

		// Delete form entries.
		$this->delete_form_entries( $form, $settings );

		// Set transient.
		set_transient( $this->_slug . '_' . $form['id'], '1', $next_run_time );

	}

	/**
	 * Delete form entries.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @param array $form     The form object.
	 * @param array $settings Entry Expiration settings.
	 *
	 * @uses GFAPI::get_entries()
	 * @uses GFAPI::delete_entry()
	 * @uses GF_Entry_Expiration::get_search_criteria()
	 */
	public function delete_form_entries( $form, $settings ) {

		// Prepare search critera.
		$search_criteria = $this->get_search_criteria( $settings, $form );

		// Prepare paging criteria.
		$paging = array(
			'offset'    => 0,
			'page_size' => 50,
		);

		// Get total entry count.
		$found_entries = GFAPI::count_entries( $form['id'], $search_criteria );

		// Set entries processed count.
		$entries_processed = 0;

		// Loop until all entries have been processed.
		while ( $entries_processed < $found_entries ) {

			// Log the page number.
			$this->log_debug( __METHOD__ . '(): Starting deletion of page ' . ( round( $entries_processed / $paging['page_size'] ) + 1 ) . ' of ' . ( round( $found_entries / $paging['page_size'] ) ) );

			// Get entries.
			$entries = GFAPI::get_entries( $form['id'], $search_criteria, null, $paging );

			// If no more entries were found, break.
			if ( empty( $entries ) ) {
				$this->log_debug( __METHOD__ . '(): No entries were found for this page.' );
				break;
			}

			// Loop through entries.
			foreach ( $entries as $entry ) {

				// Delete entry.
				GFAPI::delete_entry( $entry['id'] );

				// Increase entries processed count.
				$entries_processed++;

			}

			// Increase offset.
			$paging['offset'] += $paging['page_size'];

		}

		// Add to total processed.
		$lifetime_processed = get_option( 'gf_entryexpiration_lifetime_processed', 0 );
		$lifetime_processed += $entries_processed;
		update_option( 'gf_entryexpiration_lifetime_processed', $lifetime_processed );

		// Log that deletion has been completed.
		$this->log_debug( __METHOD__ . '(): Deletion completed.' );

	}

	/**
	 * Get Entry Expiration search criteria for form.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @param array  $form     The form object.
	 * @param array  $settings Entry Expiration settings.
	 *
	 * @return array
	 */
	public function get_search_criteria( $settings, $form ) {

		// Initialize search criteria.
		$search_criteria = array(
			'start_date'     => wp_date( 'Y-m-d H:i:s', 0 ),
			'end_date'       => wp_date( 'Y-m-d H:i:s', strtotime( '-' . $settings['deletionDate']['number'] . ' ' . $settings['deletionDate']['unit'] ) ),
			'payment_status' => null,
		);

		/**
		 * Filter the entry expiration time.
		 *
		 * @since 1.2.3
		 *
		 * @param string $older_than Current entry expiration time.
		 * @param array  $form Form object.
		 */
		$search_criteria['end_date'] = gf_apply_filters( array( 'gf_entryexpiration_older_than', $form['id'] ), $search_criteria['end_date'], $form );

		/**
		 * Set the payment status when searching for expired entries.
		 *
		 * @since 1.1
		 *
		 * @param string null Payment status.
		 * @param array  $form Form object.
		 */
		$search_criteria['payment_status'] = gf_apply_filters( array( 'gf_entryexpiration_payment', $form['id'] ), $search_criteria['payment_status'], $form );

		return $search_criteria;

	}

	/**
	 * Prepare the next time Entry Expiration should run.
	 *
	 * @since  2.0.3
	 * @access public
	 *
	 * @param array $settings Entry Expiration settings.
	 *
	 * @return int
	 */
	public function prepare_next_run_time( $settings ) {

		// Get run time number.
		$number = $settings['deletionRunTime']['number'];

		// Prepare run time based on unit.
		switch ( $settings['deletionRunTime']['unit'] ) {

			case 'days':
				$next_run_time = $number * DAY_IN_SECONDS;
				break;

			case 'hours':
				$next_run_time = $number * HOUR_IN_SECONDS;
				break;

			case 'months':
				$next_run_time = $number * MONTH_IN_SECONDS;
				break;

			case 'weeks':
				$next_run_time = $number * WEEK_IN_SECONDS;
				break;

		}

		// Adjust run time by five seconds.
		$next_run_time -= 5;

		return $next_run_time;

	}





	// # UPGRADE -------------------------------------------------------------------------------------------------------

	/**
	 * Migrate needed settings.
	 *
	 * @since  1.1.0
	 * @access public
	 *
	 * @param  string $previous_version Version number the plugin is upgrading from.
	 *
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFAddOn::save_form_settings()
	 * @uses GFAddOn::update_plugin_settings()
	 * @uses GFAPI::get_forms()
	 * @uses GFAPI::update_form()
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

		// Upgrade: 2.0
		if ( ! empty( $previous_version ) && version_compare( $previous_version, '2.0', '<' ) ) {

			// Remove old cron hook.
			wp_clear_scheduled_hook( 'gf_entryexpiration_delete_old_entries' );

			// Get plugin settings.
			$plugin_settings = $this->get_plugin_settings();

			// Get forms.
			$forms = GFAPI::get_forms();

			// Loop through forms.
			foreach ( $forms as $form ) {

				// If form is not included in Entry Expiration, skip it.
				if ( ! rgar( $form, 'gf_entryexpiration_include' ) ) {
					continue;
				}

				// Prepare form settings.
				$form_settings = array(
					'deletionEnable'  => '1',
					'deletionDate'    => array(
						'number' => rgars( $plugin_settings, 'gf_entryexpiration_expire_time/amount' ),
						'unit'   => rgars( $plugin_settings, 'gf_entryexpiration_expire_time/type' ),
					),
					'deletionRunTime' => array(
						'number' => 1,
						'unit'   => 'hours',
					),
				);

				// Save form settings.
				$this->save_form_settings( $form, $form_settings );

				// Remove old setting.
				unset( $form['gf_entryexpiration_include'] );

				// Save form.
				GFAPI::update_form( $form );

			}

			// Clear plugin settings.
			$this->update_plugin_settings( array() );

		}

	}

}
