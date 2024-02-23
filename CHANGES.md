# Changelog

## 1.6.0 (2/23/24)
* Added: New `leaderboard-small` ad unit size.
* Added: New `mai_publisher_header_scripts` header scripts filter.
* Added: New `mai_publisher_ad_unit` and `mai_publisher_ad_video` filters on ad/video block final HTML.
* Added: New `mai_publisher_entries_ads` filter for ads in archives and Mai Post Grid in Mai Theme v2.
* Added: New `mai_publisher_load_connatix` filter to override if connatix header script should load or not.
* Added: BTF ads are now only fetched rendered as they scroll into view.
* Added: Better debugging when `?dfpdeb` and/or new `?maideb` query param are used.
* Changed: GAM domain and hashed domain are now editable.
* Changed: Better mobile size limits for some ads.
* Changed: Optimized load order for ad script fetching/rendering.
* Changed: Settings page heading CSS tweaks.
* Fixed: Layout CSS tweaks for WPRM recipes.
* Fixed: IATB sitewide category is now passed if there is no per-category category set.

## 1.5.0 (2/13/24)
* Changed: Ads with taxonomy conditions now include the descendant terms as well, when the taxonomy is hierarchical.
* Fixed: Error showing version/status on settings page when using Matomo v5.

## 1.4.0 (2/12/24)
* Changed: Taxonomy term checks now check for descendants as well. Now, if "Recipes" is a taxonomy condition and a post is only in a child category of "Entrees", the conditional still still be met.
* Fixed: The "All Taxonomies" setting was not working correctly.

## 1.3.0 (2/12/24)
* Added: New `mai_publisher_dom` and `mai_publisher_html` filters to hijack the full `DOMDocument` and `HTML` after things have run.
* Added: Check for ads in JS incase other JS is hijacking (like our split-testing).
* Changed: Added id to connatix header script.
* Fixed: Only refresh our ad slots, no longer globally refreshing all GAM ads on the page.
* Fixed: The DOM parser was affecting robots.txt files in some instances.

## 1.2.0 (2/7/24)
* Added: New admin menu link under Mai Theme.
* Changed: In archive/grid count setting is now "position" and uses the value as the actual entry/row position in the loop.
* Changed: Moved Mai Publisher admin menu link lower.
* Changed: Adds `entry` class to native ads if in a boxed configuration.
* Changed: Better aspect-ratio handling for CLS with native ads.
* Changed: Now using ACF clone fields for some entries fields.
* Changed: Added a div id to our ads script.
* Changed: Attempt to check if gpt.js is already loaded to avoid duplicates.
* Changed: Removed version number from gpt.js for better browser caching.
* Fixed: Only refresh our registered ad slots.
* Fixed: Better handling of placeholders for native ads.
* Fixed: More thorough handling of admin columns data.
* Fixed: Correctly pass custom video name to data-unit attribute for slot name, tracking, etc.
* Fixed: Warning with `WP_HTML_Tag_Processor` and multiple while loops.

## 1.1.1 (1/26/24)
* Fixed: Sticky footer was causing horizontal scroll.

## 1.1.0 (1/26/24)
* Added: Requires Mai Engine 2.32.3 to use in entries/grid settings.
* Added: Mai Engine support to insert ad units in between Mai Post/Term Grid entries.
* Added: Initial compatibility with native ads.
* Added: New `maipub_do_ad_unit()` helper function that uses new `Mai_Publisher_Ad_Unit` class.
* Changed: In entries ads are no longer inserted directly via the DOM.
* Changed: Ad type options for native top/left/right/video.
* Fixed: Targets were not correctly passing to ads.
* Fixed: Video name is now correctly passed for analytics tracking.
* Fixed: Connatix video taking over the screen when the Customizer is open.

## 1.0.1 (1/19/24)
* Fixed: Encoded special characters were displaying on the front end in some configurations.

## 1.0.0 (1/18/24)
* Initial, internal, and official release.
