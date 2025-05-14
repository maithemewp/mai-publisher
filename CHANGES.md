# Changelog

## 1.14.1 (5/13/25)
* Added: extensive logging
* Added: try catch error handling and reporting
* Added: pbjs tweaks
* Added: consent management reporting and checking

## 1.13.0 (5/9/25)
* Added: Explicitly checking for consent before running.
* Added: Passing IAB categories directly to GAM.
* Added: Now passing schain to Prebid.
* Changed: Rewrite of timing and logic to check for consent as well as define/display on the fly and enable services after ATF ads are defined.
* Changed: Better handling of ad refreshes.
* Changed: PPID hashed to meet Google length requirements.
* Changed: Update IAB Content Taxonomies to 3.1.
* Changed: Even better logging.

## 1.12.0 (4/24/25)
* Added: New PCD script support.
* Added: New PPID support via Matomo `visitorId`.
* Added: Better ads JS timing by waiting for CMP and/or Matomo before initializing `googletag`.
* Added: New Mai Ad Unit block settings to hide ad(s) on Desktop, Tablet, and/or Mobile.
* Added: New `maiPublisherAnalyticsInit` JS event, which is now used to trigger the event to get the `visitorId` from Matomo.
* Added: New `beforeMaiPublisherAnalytics` JS event.
* Added: Updated README.md with code examples for customizing ad sizes.
* Added: Mai Ad support for drag/drop sorting and reordering via Simple Page Ordering plugin.
* Added: New `mai_publisher_before_enqueue_analytics` and `mai_publisher_after_enqueue_analytics` hooks for before/after enqueuing of `mai-publisher-analytics` script.
* Added: Custom user agent for `wp_remote_*` function calls.
* Changed: Converted `mai-publisher-analytics` script to be added via `wp_add_inline_script()`.
* Changed: Move page dimensions to `trackPageView` values in Matomo.
* Changed: Updated build process to use `wp-scripts` the WordPress way.
* Changed: Added classes to all SourcePoint script tags. This helps when using the split testing class.
* Changed: Using `?maideb` now shows the `ms` with the integer value for better readability when viewing console logs.
* Fixed: Content was being double processed causing weirdness, especially around `wpautop()`.

## 1.11.1 (12/27/24)
* Fixed: Updater files missing in some instances.

## 1.11.0 (12/10/24)
* Added: IAB categories support for custom taxonomies.
* Added: New `mai_publisher_location_choices` filter.
* Changed: Updated Sourcepoint to latest scripts.
* Changed: Now updating `mai_views_updated` time before hitting api’s.
* Changed: Now updating `mai_views_updated` time with 2x interval if getting stats fails.
* Changed: Simplified `get_acf_request()` to match Mai Engine and latest ACF changes.
* Changed: Only track stats/views on singular if it’s Jetpack since Jetpack doesn't handle archive stats.
* Fixed: Check for Jetpack at runtime before updating stats.
* Fixed: Error when `get_queried_object()` doesn't return an object.

## 1.10.1 (8/12/24)
* Fixed: Remove pbjs debugging per Magnite's request. They have their own built in debugging.

## 1.10.0 (7/31/24)
* Added: Support for prebid via Magnite.
* Added: Now using Laravel Mix for a build process.
* Changed: Load apstag right away if it’s enabled. No need to wait for googletag.
* Changed: Ad type default label is now Programmatic.
* Chnaged: Standard ad type option was removed.
* Changed: Now only tracking first item in the Mai Analytics Tracker block when there are multiple top level elements inside. This should avoid skewed counts from multiple items having the same name.

## 1.9.1 (7/17/24)
* Changed: Log full event in console when debugging.

## 1.9.0 (7/16/24)
* Added: New global setting to enable console logging.
* Added: Now preconnecting gpt, sourcepoint, and connatix when they are loaded.
* Changed: New Sourcepoint stub files.
* Changed: Removed `maipub_get_processed_ad_content()` function.
* Changed: Encode and process ad content on the fly, just as it's rendered.
* Fixed: Do not include Ninja Forms scripts when manipulating the DOM via DOMDocument. This makes sure HTML or special characters inside scripts are skipped. This fixes Ninja Forms not loading.
* Fixed: Safer handling of WooCommerce Memberships plans, including only checking when user is logged in.
* Fixed: Wrong variable name was throwing error when using Cool Stuff video from Conntatix.

## 1.8.0 (6/28/24)
* Added: New load delay setting to move event from `DOMContentLoaded` to window `load`, with a timed delay. This is for testing with CMP's like Sourcepoint.

## 1.7.2 (6/26/24)
* Added: New `mai_publisher_options` filter to modify options/settings on the fly.

## 1.7.1 (6/26/24)
* Changed: Added `mai-publisher-gpt` id to gpt.js script tag for easier debugging when viewing webpage source code.
* Changed: No longer parsing shortcodes, embeds, and dynamic blocks getting word count for dimension 8.
* Changed: Reorder Sourcepoint settings for consistency with their portal settings.
* Fixed: Node order was reveresed when loading after an element in the DOM.
* Fixed: Better handling of current page ID when not using static pages for Posts/Homepage.
* Fixed: Some select fields were unable to be cleared.
* Fixed: Terms saved in archive include/exclude terms were not always loaded in the field when refreshing.

## 1.7.0 (6/18/24)
* Added: New Mai Client GAM Ad Unit block.
* Added: Header/Footer scripts fields per ad.
* Added: Author archive support.
* Added: More data and info into ad placeholders.
* Added: New `mai_publisher_gam_ads` filter to modify GAM ads data on-the-fly.
* Added: New `mai_publisher_default_ads` filter to modify the default ads form ads.json.
* Changed: Now the GAM Hashed Domain is built off the GAM Domain instead of the home url.
* Changed: Ad placeholders are now fixed width to help debug wrongly sized ads.
* Changed: Update dependencies.
* Changed: Remove the rest of the legacy ad code.
* Changed: Better styling for ads when in debug mode.
* Fixed: Only run ad block field filters in the back end or when doing ajax.
* Fixed: Better handling of front page when it's set to display posts.

## 1.6.9 (5/23/24)
* Changed: Ads are now centered (again) by default inside entries/rows.
* Changed: Use `wptexturize()` instead of `mb_convert_encoding()`.
* Fixed: Mai GAM Ad Unit block inside Mai Ad block not rendering correctly in the editor.
* Fixed: Single ads showing as inactive in the back end when only set to show on specific posts/pages.

## 1.6.8 (5/9/24)
* Fixed: All Post Types setting for archive ads was not working correctly.

## 1.6.7 (5/9/24)
* Added: New `mai_publisher_load_gpt` filter to disable gpt from loading via the plugin.
* Changed: Removed deprecated ad sizes from config.
* Changed: Disable content encoded. It was causing issues, and we're relying on content being properly encoded while running this late.
* Fixed: Compatibility issue with Mai Custom Content Areas when inserting a CCA and Mai Post Grid ads in archive entries.

## 1.6.6 (4/5/24)
* Added: Integrates Sourcepoint.

## 1.6.5.1 (4/3/24)
* Fixed: Error trying to load dev dependencies on production sites.

## 1.6.5 (4/2/24)
* Added: Better support for in content ads on LearnDash content pages.
* Added: Spatie Ray for local/dev debugging before the WordPress plugin is loaded.

## 1.6.4 (3/29/24)
* Changed: Vertically center native ads when not boxed in Mai Post Grid.
* Fixed: Aspect ratio not working correctly on Mai Post Grid ads.
* Fixed: Enabling Amazon ads was breaking native/fluid ads.

## 1.6.3 (3/15/24)
* Fixed: Mai Publisher settings showing above default block settings on Mai Term Grid.

## 1.6.2 (3/6/24)
* Fixed: Only show the parent network code when the child code is the same. This is for owned domains.

## 1.6.1 (2/27/24)
* Added: After footer location was added to Single Content and Content Archives settings.

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
