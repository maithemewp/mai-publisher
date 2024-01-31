# Changelog

## 1.2.0 (1/31/24)
* Added: New admin menu link under Mai Theme.
* Changed: In archive/grid count setting is now "position" and uses the value as the actual entry/row position in the loop.
* Changed: Moved Mai Publisher admin menu link lower.
* Changed: Adds `entry` class to native ads if in a boxed configuration.
* Changed: Better aspect-ratio handling for CLS with native ads.
* Fixed: Better handling of placeholders for native ads.
* Fixed: Correctly pass custom video name to data-unit attribute for slot name, tracking, etc.

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
