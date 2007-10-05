=== Plugin Name ===
Contributors: crimesagainstlogic
Tags: flickr, thumbnails, tag, sets, photos
Requires at least: 2.2.1
Tested up to: 2.2.1
Stable tag: trunk

Insert Flickr sets, tags or individual photos in your posts by using a special tag.

== Description ==

When I started using WordPress for webopticon.com, I had a hard time finding a Flickr plugin that didn't download all the photos onto my server, have them appear in a separate gallery, or otherwise look totally kitsch. I just wanted something simple. I wanted thumbnails to show, and I wanted to be able to put them "inline" in my posts. Keep the photos and discussion on Flickr, as far as I'm concerned. I couldn't find anybody to share my design goals.

My solution was to write my own plugin.

== Installation ==

1. Uncompress the downloaded archive in [WordPress install root]/wp-content/plugins.

2. Make sure the cache directory ([WordPress install root]/wp-content/plugins/flickr-tag/cache) is writable by the webserver. 

2. Activate the plugin in your WordPress plugins control panel.

3. Go to the "Options" section, then choose "Flickr Tag" to configure the plugin. 

After installation, you'll have a new "Flickr" tab in the "glovebox" that appears when you edit/write posts. Use it to insert a favorite, or a set. You can also use the new "flickr" tag, whose syntax is: 

&lt;flickr [params]&gt;set:set_id|tag:tag1[(,|&)tag2...][@username]|photo:photo_id&lt;/flickr&gt;

== Frequently Asked Questions ==

== Screenshots ==

== Special Tag Syntax ==

&lt;flickr [params]&gt;set:set_id|tag:tag1[(,|&)tag2...][@username]|photo:photo_id&lt;/flickr&gt;
