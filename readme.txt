=== JSON Content Importer ===
Contributors: berkux
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=APWXWK3DF2E22
Tags: json,import,import json, importer,content,cache,load,opendata, opendata import, advanced json import, json import,content import, import json to wordpress, json to content, display json
Requires at least: 3.0
Tested up to: 4.1
Stable tag: 1.0.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Plugin to import, cache and display a JSON-Feed. Display is done with wordpress-markups.


== Description ==

Plugin to import, cache and display a JSON-Feed. Display is done with wordpress-markups.

= JSON Content Importer - Powerful and Simple JSON-Import Plugin =

Use a simple wordpress-markup-tag to display a JSON-Feed.
Define the url of the JSON-Feed and other option like number of displayed items, cachetime etc.
And display the data with some extras like urlencoding when using in links:
[jsoncontentimporter
  url="http://...json"
  numberofdisplayeditems="number: how many items of level 1 should be displayed? display all: leave empty"
  basenode="starting point of datasets, tha base-node in the JSON-Feed where the data is?"
]
Any HTML-Code plus "basenode"-datafields wrapped in "{}"
{subloop:"basenode_subloop":"number of subloop-datasets to be displayed"}
Any HTML-Code plus "basenode_subloop"-datafields wrapped in "{}"
{/subloop}
[/jsoncontentimporter] 

There are some special add-ons for datafields:
"{street:ifNotEmptyAdd:,}": If datafield "street" is not empty, add "," after datafield-value. allowed chars are: " a-zA-Z0-9,;-:"
"{locationname:urlencode}": Insert the php-urlencoded value of the datafield "locationname". Needed when building URLs.
"{locationname:unique}": only display the first instance of a datafield. Needed when JSON delivers data more than once.

== Installation ==

For detailed installation instructions, please read the [standard installation procedure for WordPress plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

1. Login to your WordPress installation
2. Install plugin by uploading json-content-importer.zip to `/wp-content/plugins/`.
2. Activate the plugin through the _Plugins_ menu.
3. Klick on "JSON Content Importer" menuentry in the left bar: basic caching-settings and more instructions about usage.
4. Cache folder: WP_CONTENT_DIR.'/cache/jsoncontentimporter'. So "WP_CONTENT_DIR.'/cache/'" must be writable for the http-daemon. The plugin checks this and might aborts with an error-message like dir is missing or not writeable. if so: check permissions of the directories.


== Frequently Asked Questions ==

= What does this plugin do? =

This plugin gives a wp-shortcode for use in a page/blog to import, cache and display JSON-data. Inside wp-shortcode some markups (and attributes like urlencode) are defined to define how to display the data.  

= How can I make sure the plugin works? =

Create a sample-page and use the wordpress-shortcode "jsoncontentimporter". An example is given in the plugin-configpage and in the "Description"-Section.

== Changelog ==

= 1.0.2 =
Initial release on WordPress.org. Any comments and feature-requests are welcome: blog@kux.de



