=== Circular Year Planner ===
Contributors: andreaslundberg
Tags: calendar, planning, visualization, circular, year-planner
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 1.0.17
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A circular year planner to visualize fiscal years and events.

== Description ==

Circular Year Planner is a WordPress plugin that visualizes fiscal years and events in a circular calendar. Perfect for organizations and businesses that want to plan and communicate their activities in a visually appealing way.

= Features =

* **Circular Calendar View** - Year's months, weeks and events in concentric rings
* **Flexible Event Management** - Customizable event types with individual colors
* **Administrative Settings** - Manage event types and fiscal years
* **Easy Integration** - Use shortcode to display the calendar
* **REST API** - For external integrations
* **Responsive Design** - Works on all devices
* **Dynamic Scaling** - Rings automatically adjust for 6+ event types
* **Month Dividers** - Visual guide lines for better readability

= Usage =

Use shortcode in any page or post:

`[circular_year_planner]`

**Parameters:**

* `year` - Display specific fiscal year, e.g. `[circular_year_planner year="2024/2025"]`
* `types` - Display only certain event types (index), e.g. `[circular_year_planner types="0,1"]`
* `width` and `height` - Customize size, e.g. `[circular_year_planner width="1000" height="1000"]`

= Technical Information =

* Uses WordPress built-in jQuery
* SVG-based rendering for sharp graphics
* REST API endpoints for external integrations
* Follows WordPress Coding Standards

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin panel
3. Go to "Year Planning" → "Settings" to configure
4. Add events via "Year Planning" → "Add Event"
5. Use shortcode `[circular_year_planner]` to display the calendar

== Frequently Asked Questions ==

= How do I change the colors of event types? =

Go to Year Planning → Settings and click the color picker for each event type.

= Can I use a broken fiscal year? =

Yes, select which month the fiscal year starts in the settings. If you choose anything other than January, the year will be displayed as "24/25".

= How many event types can I have? =

You can have unlimited event types. The plugin automatically scales the rings proportionally from 6 event types upwards.

= Can I display multiple calendars on the same page? =

Yes, you can use multiple shortcodes with different parameters on the same page.

== Screenshots ==

1. Circular calendar view with months, weeks and events
2. Administrative settings for event types
3. Event editor with dates and type
4. Calendar legend with all event types

== Changelog ==

= 1.0.15 - 2024-10-17 =
* Dynamic ring size for 6+ event types
* Rings scale automatically proportionally
* Month dividers as radial guide lines
* Extra marked year divider line
* Better visual delimitation of months and years
* GPL v2 license added
* WordPress Plugin Directory guidelines followed

= 1.0.14 - 2024-10-16 =
* Week numbers now displayed every other week
* Better readability of week numbers

= 1.0.13 - 2024-10-16 =
* Text automatically flips on the left side
* All event text is now readable regardless of position

= 1.0.12 - 2024-10-16 =
* Event rings made 50% wider
* Light gray background on all event rings

= 1.0.11 - 2024-10-16 =
* Shortened display of fiscal year
* Fiscal year displayed as "2025" or "24/25"

= 1.0.0 =
* First version

== Upgrade Notice ==

= 1.0.15 =
New version with dynamic scaling and better visual delimitation. GPL-licensed for WordPress Plugin Directory.

== Additional Info ==

For support and questions, visit the plugin page on GitHub: https://github.com/andlun57/year-planning
