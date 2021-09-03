=== Entry Expiration for Gravity Forms ===
Contributors: forgravity, travislopes
Tags: gravity forms, entry, expiration
Requires at least: 5.3.0
Tested up to: 5.8.0
Stable tag: 4.7.3
Requires PHP: 5.3

Automatically remove old form entries on a custom, defined schedule

== Description ==

> #### [Entry Automation for Gravity Forms](https://forgravity.com/plugins/entry-automation/?utm_source=wordpress&utm_medium=readme&utm_campaign=readme) makes entry deletion more powerful and allows you to export your entries too!
>
> Entry Expiration started out as a simple tool to automatically remove your old form entries. But what if you need more control over when entries are deleted? Want to apply conditional logic to target specific entries? Or maybe delete specific field values rather than the whole entry? Need to generate an export file before getting rid of those entries?
>
> [Check out Entry Automation](https://forgravity.com/plugins/entry-automation/?utm_source=wordpress&utm_medium=readme&utm_campaign=readme) today!


When integrating Gravity Forms with a third-party service, it's often not necessary to keep entries around after a short period of time as you already have the data imported elsewhere.

Entry Expiration for Gravity Forms allows you to automatically delete Gravity Forms entries older than a defined timeframe. After activating the plugin, set the oldest age for an entry on the Entry Expiration Settings page. At midnight, the plugin will delete all entries on enabled forms that are older than the time you set.

== Installation ==
= Requirements =
* WordPress version 5.3.0 and later (tested at 5.8.0)
* Gravity Forms 1.8.17 and later

= Installation =
1. Unpack the download package.
1. Upload all files to the `/wp-content/plugins/` directory, with folder
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Navigate to the Form Settings page for each form you want to have entries automatically expire and click the 'Entry Expiration' tab.
1. Define how often you want entries to be deleted and the minimum age required for entries to be deleted.

== Changelog ==
= Version 2.2 (2021-09-02) =
* Fixed entries not deleting on schedule when server and WordPress timezones do not match.
= Version 2.1 (2020-08-07) =
* Added capabilities.
* Added support for Gravity Forms 2.5.
* Updated installation instructions.
* Fixed fatal error during 2.0 upgrade process.
* Fixed PHP notices.
* Fixed search criteria not correctly preparing in certain scenarios.
* Fixed search criteria not correctly preparing the search end date.
= Version 2.0 =
* Added additional logging
* Added expiration time and recurrence at the form level.
* Added filter for setting entry expiration time for each form
* Adjusted entry older than date to not be relative to midnight
* Changed plugin loading method.
* Rewrote expiration procedure to be more efficient.
= Version 1.2 =
* Fixed update routine to not automatically enable forms for processing if running a fresh install
* Changed expiration time setting to allow choosing between hours, days, weeks and months
= Version 1.1 =
* Switched forms from being able to be excluded to having to include them for processing
* Deletion cron now runs hourly instead of daily
* Cron now only deletes 1000 entries at a time to prevent long execution times
* Added filters for: payment status, number of entries to be processed at a time
= Version 1.0 =
* Initial release
