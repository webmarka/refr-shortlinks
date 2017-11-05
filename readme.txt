=== REFR Shortlinks ===
Contributors: webmarka, norcross (original plugin author)
Website Link: https://refr.ca/
Tags: REFR, shortlink, custom URL, YOURLS
Requires at least: 3.6
Tested up to: 4.4
Stable tag: 2.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://andrewnorcross.com/donate

Creates a custom short URL when saving posts.

== Description ==

Creates a REFR generated shortlink on demand or when saving posts.

Features:

*   Optional custom keyword for link creation.
*   Will retrieve existing URL if one has already been created.
*   Click count appears on post menu
*   Available for standard posts and custom post types.
*   Optional filter for wp_shortlink
*   Built in cron job will fetch updated click counts every hour.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `refr-link-creator` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the "REFR Settings" option in the Settings Menu.
4. Enter your REFR custom URL and API key
5. Enjoy!

== Frequently Asked Questions ==


= What's this all about? =

This plugin creates a shortlink (stored in the post meta table) for each post that can be used in sharing buttons, etc.

= What is REFR? =

REFR is a self-hosted PHP based application that allows you to make your own custom shortlinks, similar to bit.ly and j.mp. [Learn more about it here](http://refr.org/ "REFR download")

= How do I use the template tag? =

Place the following code in your theme file (usually single.php) `<?php refr_display_box(); ?>`

= The delete function doesn't remove the short URL from my REFR installation =

This is a limitation with the REFR API, as there is not a method yet to delete a link. The delete function has been added to the plugin to allow users to get the updated URL that they may have changed in the REFR admin panel

== Screenshots ==


== Changelog ==

= 1.0.0 - 2017-11-04 =
* Forked the excellent work from norcross named YOURLS Link Creator.
* See this link for previous changes from the original plugin : https://github.com/norcross/refr-link-creator
