=== Plugin Name ===
Contributors: crimesagainstlogic
Tags: flickr, thumbnails, tag, sets, photos, lightbox, images
Requires at least: 2.2.1
Tested up to: 2.2.1
Stable tag: trunk

Insert Flickr sets, tags or individual photos in your posts by using a special tag.

== Description ==

When I started using WordPress for [webopticon.com][], I had a hard time finding a [Flickr][] plugin that didn't download all 
the photos onto my server, have them appear in a separate gallery, or otherwise look totally kitsch. I just wanted something simple. I wanted thumbnails to show, and I wanted to be able to put them "inline" in my posts. Keep the photos and discussion on Flickr, as far as I'm concerned. I couldn't find anybody to share my design goals.

My solution was to write my own plugin.

[webopticon.com]: http://www.webopticon.com
[Flickr]: http://www.flickr.com

== Installation ==

This plugin mostly follows the [standard WordPress installation method][]:

1. Uncompress the downloaded archive in [WordPress install root]/wp-content/plugins.

1. Make sure the cache directory ([WordPress install root]/wp-content/plugins/flickr-tag/cache) is writable by the webserver. 

1. Activate the plugin in your WordPress plugins control panel.

1. Go to the "Options" admin page, then choose "Flickr Tag" to configure the plugin. 

After installation, you'll have a new "Flickr" tab in the "glovebox" that appears when you edit/write posts. Use it to insert a favorite, or a set. Or, use the "flickr" tag (syntax outlined in the glovebox). 

[standard WordPress installation method]: http://codex.wordpress.org/Managing_Plugins#Installing_Plugins

== Frequently Asked Questions ==

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
2. A new "Flickr" tab is available when writing blog entries. It provides an easy way to insert a set or recent photo from your photostream; it also shows the syntax of the "flickr" tag. 
3. An example of use. The plugin allows photos to be linked to Flickr, showing the description of the photo in a tooltip (as shown above), or the plugin can be configured to show a larger version of the photo in a [Lightbox][]. 

[Lightbox]: http://www.huddletogether.com/projects/lightbox2/

== Special Thanks ==

Special thanks to the following for their contributions and bug reports (listed in no particular order):

Jon Baker<br/>
Niki Gorchilov<br/>
Michael Fruehmann<br/>
Tyson Cecka<br/>


