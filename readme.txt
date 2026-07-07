=== Unintrusive Admin Bar ===
Contributors: varuiza
Tags: admin bar, toolbar, hide admin bar, remove admin bar, minimal
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hide the WordPress admin bar behind a small toggle icon, so it's out of the way until you need it.

== Description ==

Tired of clients asking you to just remove the WordPress admin bar (the bar at the top of the page that logged-in users see), so you lose one-click access to the dashboard for good? Annoyed by themes that don't reserve any space for it, so it overlaps your logo, your main menu, or the first row of content? Sick of that black bar eating up screen space every time you need to show a clean screenshot or a live demo to a client?

Unintrusive Admin Bar is a simple plugin that replaces the WordPress admin bar with a small toggle icon in the corner of the frontend, instead of a bar that stays fixed across the whole page. Clicking the icon reveals the full admin bar; clicking the arrow that appears within it hides it again: always one click away, never in the way.

= Features =

* No settings screen: install, activate, and it just works.
* No jQuery: plain JavaScript and CSS transitions, so it doesn't force that whole library onto every frontend pageview.
* Keyboard and screen-reader accessible: proper focus handling and live announcements via `wp.a11y.speak()`.
* Respects your operating system's "reduce motion" preference.
* Reuses WordPress's own toolbar markup and icon styling, so the toggle always looks consistent with the rest of the admin bar.
* Frontend-only: never changes anything in wp-admin.
* Collects no data and makes no external requests.

= Credits =

This plugin is a hardened fork of "WP Minimize Admin Bar", originally released by `plasticbrain` under GPLv2 or later. This fork keeps the original show/hide behavior and fixes several issues found in an independent audit of that code: assets and hooks now only run while the admin bar is actually showing, cache-busting for the bundled CSS/JS is now reliable, the toggle markup is now keyboard/screen-reader accessible, and every visible string is fully translatable.

== Installation ==

1. Upload the `unintrusive-admin-bar` directory to the `/wp-content/plugins/` directory, or install through the Plugins screen directly.
2. Activate the plugin through the "Plugins" screen in WordPress.

There are no plugin settings. Visit your site's frontend (not wp-admin) while logged in to see the toggle icon.

== Frequently Asked Questions ==

= Does this change anything in wp-admin? =

No. It only affects how the admin bar is presented on the logged-in frontend view of your site.

= Why don't I see the toggle icon? =

The plugin only loads when WordPress would show the admin bar in the first place (`is_admin_bar_showing()`). If your account has disabled "Show Toolbar when viewing site" in your profile, or another plugin hides the admin bar, this plugin has nothing to toggle.

= Does this affect visitors who aren't logged in? =

No. Nothing in this plugin loads at all unless WordPress would already be showing the admin bar to a logged-in user.

= Will this work with my theme, including block themes? =

Yes. It only hooks into WordPress's own admin bar API, so it behaves the same regardless of whether your theme is classic, block-based, or a hybrid.

= Does this plugin collect any data? =

No. It makes no external requests and doesn't store anything of its own.

== Screenshots ==

1. The toggle icon tucked in the corner, barely there until you need it.
2. One click reveals the full WordPress toolbar, exactly where you left it.

== Changelog ==

= 1.0.0 =
* Forked and hardened from `plasticbrain`'s abandoned "WP Minimize Admin Bar".
* Gate asset loading and the admin bar padding removal behind `is_admin_bar_showing()`, so nothing runs for visitors who never see the admin bar.
* Fix inconsistent cache-busting: each asset is now versioned from its own file's modification time.
* Fix a malformed toggle link.
* Add full internationalization.
* Make the hide icon inherit the admin bar's own icon color instead of a hardcoded red.
* Add the hide icon as a native Toolbar API node (`add_node()`) in WordPress's own default admin bar group, reusing core's own icon and screen-reader-text markup, instead of a specific menu item or a JS-injected element; it now renders with the rest of the bar and never appears in wp-admin.
* Fix animation queuing so repeated clicks on the toggle buttons no longer stack up.
* Remove obsolete vendor prefixes and Internet Explorer 8 fallbacks no longer needed by supported browsers.
* Fix the hide icon being invisible, and therefore stuck open, on screens narrower than 782px, where WordPress's own responsive admin bar styles hide every icon except a fixed core allowlist; size it to match the touch-target size of the rest of the admin bar icons at that width.
* Add `aria-expanded`/`aria-controls` to both toggle buttons, move focus to the relevant toggle after showing or hiding the bar, and announce the change to screen readers via `wp.a11y.speak()`.
* Respect the "reduce motion" operating system preference for every animation.
* Remove the jQuery dependency entirely in favor of plain JavaScript and CSS transitions, so logged-in visitors on themes that don't otherwise need jQuery no longer have it forced onto every page.
* Depend on WordPress's own `admin-bar` and `wp-a11y` script/style handles instead of relying on incidental load order.
* Ship minified `style.min.css`/`app.min.js` alongside the source, and load them by default using WordPress core's own `SCRIPT_DEBUG` convention (unminified only while `SCRIPT_DEBUG` is on).

== Upgrade Notice ==

= 1.0.0 =
Initial release of this fork.
