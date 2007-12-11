=== Plugin Name ===
Contributors: crimesagainstlogic
Tags: flickr, thumbnails, tag, sets, photos
Requires at least: 2.2.1
Tested up to: 2.2.1
Stable tag: trunk

Insert Flickr sets, tags or individual photos in your posts by using a special tag.

== Description ==

When I started using WordPress for webopticon.com, I had a hard time finding a Flickr plugin that didn't download all the photos onto my server, have them appear in a separate gallery, or otherwise look totally 
kitsch. I just wanted something simple. I wanted thumbnails to show, and I wanted to be able to put them "inline" in my posts. Keep the photos and discussion on Flickr, as far as I'm concerned. I couldn't find anybody to share my design goals.

My solution was to write my own plugin.

== Installation ==

1. Uncompress the downloaded archive in [WordPress install root]/wp-content/plugins.

2. Make sure the cache directory ([WordPress install root]/wp-content/plugins/flickr-tag/cache) is writable by the webserver. 

3. Activate the plugin in your WordPress plugins control panel.

4. Go to the "Options" section, then choose "Flickr Tag" to configure the plugin. 

After installation, you'll have a new "Flickr" tab in the "glovebox" that appears when you edit/write posts. Use it to insert a favorite, or a set. Or, use the "flickr" tag (syntax outlined in the "glovebox"). 

== Frequently Asked Questions ==

Q: What's new in 2.0?<br/>
A: <li>Ability to override default photo size.
   <li> Ability to override default photo count (sets, tags only).
   <li> New tag syntax for compatability with the visual HTML editor.
   <li> New OO architecture to make derivative code easier to write.
   <li> (Untested) better internationalization around htmlentities().
   <li> Increased compatability for ISPs that may not have libcurl enabled.
   <li> Changed conjunction operator in tag queries from & to +.

== Screenshots ==

== Special Thanks ==

Special thanks to the following for their contributions and bug reports:

Jon Baker<br/>
Niki Gorchilov<br/>
Michael Fruehmann<br/>
Tyson Cecka<br/>


