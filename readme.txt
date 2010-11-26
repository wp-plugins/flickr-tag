=== Plugin Name ===
Contributors: jeffmaki
Tags: flickr, thumbnails, tag, sets, photos, lightbox, images
Requires at least: 2.5
Tested up to: 3.0.1
Stable tag: trunk

Insert Flickr sets, tags, photostreams, group pools or individual photos into your posts using a special Wordpress tag.

== Description ==

When I started using WordPress for my blog, I had a hard time finding a [Flickr][] plugin that didn't download all the photos onto my 
server, have them appear in a separate gallery out of context from my post, or otherwise look totally horrible. I just wanted something simple. 
Keep the photos themselves and discussion on Flickr, as far as I'm concerned. 

I couldn't find anybody to share my design goals, so Flickr Tag was born!

[Flickr]: http://www.flickr.com

== Installation ==

This plugin mostly follows the [standard WordPress installation method][]:

1. Uncompress the downloaded archive in [WordPress install root]/wp-content/plugins.

1. Make sure the cache directory ([WordPress install root]/wp-content/plugins/flickr-tag/cache) is writable by the webserver. 

1. Activate the plugin in your WordPress plugins control panel.

1. Go to the "Plugins" admin page, then choose "Flickr Tag" to configure the plugin. 

After installation and configuration, you'll have a new "Flickr" media icon that appears when you edit/write posts. Use it to insert a favorite, or a set. 
Or, use the "flickr" tag (syntax outlined in the popup media window). 

[standard WordPress installation method]: http://codex.wordpress.org/Managing_Plugins#Installing_Plugins

== Frequently Asked Questions ==

= What's new in 2.4.7? =
* Tested with WP 3.0.1.
* New API key.

= What's new in 2.4? =
* Fixed issue with insertion of images into editor under WP 2.7.
* Better error reporting.
* New photostream option.
* New group pool option.
* Availability of "original" size images.

= What's new in 2.3? =
* Compatability with 2.7.
* Removed use of header() in admin section; should remove errors sometimes displayed on screen.
* Increased compatability with other plugins/JavaScript/CSS code (namespaces).
* New Lightbox plugin mode--don't need to use Flickr Tag-builtin lightbox.
* Modified HTML genration to play nicely with Wordpress-generated HTML.
* Rewritten tooltip code--no YUI dependency.
* Used script dependency function of Wordpress to avoid script contention issues (two jQuery installs, etc.).
* ALT tags now contain photo title
* Lightbox updated
* Better cross-platform CSS 

= What's new in 2.2? =
* Compatability with 2.5.
* Config page moved to "Plugins" admin page.
* Use of Shortcode API.
* Added new media button and tabbed popup interface.
* Removed tag syntax migration tool: therefore, if upgrading from pre-2.0, you must upgrade to 2.0 or 2.1 before you upgrade to 2.2! 

= What's new in 2.1? =
* Configurable link behavior: lightbox, tooltip or none.
* Tag queries are sorted by "relevance". 

= What's new in 2.0? =
* Ability to override default photo size.
* Ability to override default photo count (sets, tags only). 
* New tag syntax for compatability with the visual HTML editor.
* New OO architecture to make derivative code easier to write.
* (Untested) better internationalization around htmlentities().
* Increased compatability for ISPs that may not have libcurl enabled.
* Changed conjunction operator in tag queries from & to +.
* XHTML compliant HTML tag generation.
* wptexturize() bug.
* Better (more verbose) error reporting.

== Screenshots ==

1. The plugin is easily configured through the admin panel. Be sure to visit this admin page upon initial installation to authenticate to Flickr.
2. A new "Flickr" media button is available when writing blog entries. It provides an easy way to insert a set or recent photo from your photostream (or a set).
3. An example of use. The plugin allows photos to be linked to Flickr, showing the description of the photo in a tooltip (as shown above), or the plugin can be configured to show a larger version of the photo in a [Lightbox][]. 

[Lightbox]: http://www.huddletogether.com/projects/lightbox2/

== Special Thanks ==

Special thanks to the following for their contributions and bug reports (listed in no particular order):

* Jon Baker
* Niki Gorchilov
* Michael Fruehmann
* Tyson Cecka
* Jean-Paul Horn

