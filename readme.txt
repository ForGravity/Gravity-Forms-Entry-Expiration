=== Gravity Forms Entry Expiration ===
Contributors: travislopes
Tags: gravity forms, entry, expiration
Requires at least: 3.9.2
Tested up to: 4.1.0
Stable tag: 4.1.0

Provides a simple way to remove old entries in Gravity Forms.

== Description ==
This WordPress plugin allows you to automatically delete Gravity Forms entries older than a defined timeframe. After activating the plugin, set the oldest age for an entry on the Entry Expiration Settings page inside of Gravity Forms. At midnight, the plugin will delete all entries older than the time you set. Only forms set to be included in the entry expiration will have their entries deleted. You can include a form in the entry expiration process by selecting the "Include in entry expiration" option under Form Settings.

== Installation ==
= Requirements =
* WordPress version 3.9.2 and later (tested at 4.1.0)
* Gravity Forms 1.8.17 and later

= Installation =
1. Unpack the download package.
1. Upload all files to the `/wp-content/plugins/` directory, with folder
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Navigate to the Entry Expiration Settings page inside of Gravity Forms and set the expiration timeframe.
1. Navigate to the Form Settings page for each form you want to have entries automatically expire and include them in the expiration process.

== Changelog ==
= v1.2.0 = 
* Fixed update routine to not automatically enable forms for processing if running a fresh install
* Changed expiration time setting to allow choosing between hours, days, weeks and months
= v1.1.0 =
* Switched forms from being able to be excluded to having to include them for processing
* Deletion cron now runs hourly instead of daily
* Cron now only deletes 1000 entries at a time to prevent long execution times
* Added filters for: payment status, number of entries to be processed at a time
= v1.0.0 =
* Initial release