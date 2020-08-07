# Gravity Forms Entry Expiration
**Contributors:** forgravity, travislopes

**Requires at least:** 3.9.2

**Tested up to:** 5.5.0

### Description
This WordPress plugin allows you to automatically delete Gravity Forms entries older than a defined timeframe. After activating the plugin, set the oldest age for an entry on the Entry Expiration Settings page inside of Gravity Forms. At midnight, the plugin will delete all entries older than the time you set. Only forms set to be included in the entry expiration will have their entries deleted. You can include a form in the entry expiration process by selecting the "Include in entry expiration" option under Form Settings.

### Changelog
* Version 2.1 (2020-08-07)
	* Added capabilities.
	* Updated installation instructions.
	* Fixed fatal error during 2.0 upgrade process.
    * Fixed PHP notices.
	* Fixed search criteria not correctly preparing in certain scenarios.
	* Fixed search criteria not correctly preparing the search end date.
* Version 2.0
	* Added additional logging
	* Added expiration time and recurrence at the form level.
	* Added filter for setting entry expiration time for each form
	* Adjusted entry older than date to not be relative to midnight
	* Changed plugin loading method.
	* Rewrote expiration procedure to be more efficient.
* Version 1.2
	* Fixed update routine to not automatically enable forms for processing if running a fresh install
	* Changed expiration time setting to allow choosing between hours, days, weeks and months
* Version 1.1
	* Switched forms from being able to be excluded to having to include them for processing
	* Deletion cron now runs hourly instead of daily
	* Cron now only deletes 1000 entries at a time to prevent long execution times
	* Added filters for: payment status, number of entries to be processed at a time
* Version 1.0
	* Initial release

### Installation
#### Requirements
* WordPress version 3.9.2 and later (tested at 5.5.0)
* Gravity Forms 1.8.17 and later

#### Installation
1. Unpack the download package.
1. Upload all files to the `/wp-content/plugins/` directory, with folder
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Navigate to the Form Settings page for each form you want to have entries automatically expire and click the 'Entry Expiration' tab.
1. Define how often you want entries to be deleted and the minimum age required for entries to be deleted.