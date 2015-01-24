=== JSON Content Importer ===
Contributors: berkux
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=APWXWK3DF2E22
Tags: json,import,importer,content,cache,load
Requires at least: 3.0
Tested up to: 4.1
Stable tag: 1.0.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Plugin to import, cache and display a JSON-Feed. Display is done with wordpress-markups.


== Description ==

Plugin to import, cache and display a JSON-Feed. Display is done with wordpress-markups.

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

Create a sample-page and use the wp-shortcode. An example is given in the plugin-configpage and below...

= Available Syntax for Wordpress-Pages and -Blogentries =
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


== Changelog ==

= 1.0.0 =

* Initial release: any comments and feature-requests are welcome at blog@kux.de

== Upgrade Notice ==

= 1.0.2 =

options.php: changed help-text and caching-parameters. class-json-content-importer.php: completely revised, esp. caching and path-setting

= 1.0.1 =

enhanced {subloop:NODE} to {subloop:NODE:NUMER_OF_DISPLAYED_ITEMS}. if there is no {subloop} in the markup display dataset (before: {subloop) required 

