=== Plugin Stats ===
Contributors: scompt
Donate link: http://scompt.com/projects/plugin-stats
Tags: wordpress, admin, stats, plugin, developer, shortcode
Requires at least: 2.5
Tested up to: 2.5.1
Stable tag: 1.1

Plugin Stats provides a shortcode, template function, and dashboard widget which graphs the downloads completed for plugins hosted at WordPress.org.

== Description ==

Plugin Stats provides a shortcode, template function, and dashboard widget which display graphs of the number of downloads completed for plugins hosted at WordPress.org.  The green bar on the graph shows the standard deviation of the downloads.  Also, today's downloads are shown using a red link crossing the graph.  The graphic is provided using the [Google Charts API](http://code.google.com/apis/chart/).

== Installation ==

1. Upload the `plugin-stats` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Usage ==

There's no Options/Settings panel for Plugin Stats.  Instead, configuration is done using the template function, shortcode, or dashboard widget and with filters.

= Template Function =

Plugin Stats provides a template function to allow you to display a stats graph in your site template.  Actually, it's an action, but it can be used the same way as a normal template function.  Here's a sample usage:

`<?php do_action('plugin-stats', 'wp-crontrol') ?>`

This would display a 360x100 graph of the past 180 days of plugin downloads for the plugin `wp-crontrol`.

`<?php do_action('plugin-stats', 'wp-crontrol', '360x100', true) ?>`

This displays a 360x100 graph of the past 180 days linked to the plugin stats page on WordPress.org.

= Shortcode =

A shortcode is also available for usage in posts and pages.  It has the same options as the template function.  Here are a few self-explanatory examples:

`[plugin-stats slugname="wp-crontrol" size="360x100" addlink="1"]
[plugin-stats slugname="wp-crontrol" size="100x100" addlink="0"]
[plugin-stats slugname="wp-crontrol"]`

= Dashboard Widget =

The dashboard widget allows you to enter a number of plugin slugs and have the graphs for all of them displayed in a widget on your dashboard.

= General Configuration =

There are a number of other configuration parameters that can be set using the WordPress filter system.

**`plugin-stats_build-link`**: Whether or not a new link will be built.  By default, a new link is built each time new stats are available or a new image size is requested.  This has the consequence that if you have two plugin-stats graphs on one page with different parameters (see below), the second one won't be generated.  To fix this, hook onto the `plugin-stats_build-link` filter and return `true` to force a new link.

**`plugin-stats_num-days`**: This is the number of days that should be included in the graph.  The default *and* upper limit is 180.

**`plugin-stats_img-link-args`**: An associative array of parameters that will be sent to the [Google Charts API](http://code.google.com/apis/chart/) to generate the graph.  Use this configuration value to change colors or add additional decoration to the graph.

**`plugin-stats_img-link`**: The finished `img` tag that is returned.

**`plugin-stats_cache-time`**: The number of seconds to cache the downloaded statistics.  Defaults to 600 (10 minutes).

== Frequently Asked Questions ==

= How do I ask a frequently asked question? =

Email [me](mailto:scompt@scompt.com).

== Screenshots ==

1. The shortcode in action.
1. The template function in action.
1. The dashboard widget in action.

== Future Plans ==

* Dunno, any ideas?

== Version History ==

= Version 1.0 =

* shortcode
* template function
* dashboard widget

= Version 1.1 =

* Fixed bug where after a linked graph, all graphs would be linked.