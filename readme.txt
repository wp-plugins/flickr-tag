=== Flickr-Tag ===
Contributors: crimesagainstlogic
Tags: flickr, thumbnails, tag
Requires at least: 2.2.1
Tested up to: 2.2.1
Stable tag: trunk

This plugin allows you to show flickr sets/tags/individual photos in your posts by using a special tag.

== Description ==

When I started using WordPress for webopticon.com, I had a hard time finding a Flickr plugin that didn't download all the photos onto my server, have them appear in a separate gallery, or otherwise look totally kitsch. I just wanted something simple. I wanted thumbnails to show, and I wanted to be able to put them "inline" in my posts. Keep the photos and discussion on Flickr, as far as I'm concerned. I couldn't find anybody to share my design goals.

My solution was to write my own plugin.

== Installation ==

Start installation by uncompressing the gzip'd tarball to your local machine (you may need to re-add the extension .tar.gz; WordPress mangles it). 

Edit the file <span style="font-family: courier;">flickr/flickr.php</span> to set your Flickr API key and NSID. 

Save the file, and copy the entire flickr directory to <span style="font-family: courier;">[WordPress install root]/wp-content/plugins</span>. Make sure the cache directory (<span style="font-family: courier;">[WordPress install root]/wp-content/plugins/flickr/cache</span>) is writable by the webserver. 

Then activate the plugin in your WordPress plugins control panel.

After installation, you'll have a new "Flickr" tab in the "glovebox" that appears when you edit/write articles. Use it to insert a favorite, or a set. You can also use the new "flickr" tag, whose syntax is: 

<pre>
<flickr [params]>set:set_id</flickr>
<flickr [params]>tag:tag1[(,|&)tag2...][@username]</flickr>
<flickr [params]>[photo:]photo_id</flickr>
</pre>

== Frequently Asked Questions ==

Q: Is there an example I can see?
<br/>
A: Yes, see http://www.webopticon.com

== Screenshots ==

Yes, downloadable at: <a href="http://svn.wp-plugins.org/flickr-tag/trunk/screenshot.png">http://svn.wp-plugins.org/flickr-tag/trunk/screenshot.png</a>

== Special Tag Syntax ==

<pre>
<flickr [params]>set:set_id</flickr>
<flickr [params]>tag:tag1[(,|&)tag2...][@username]</flickr>
<flickr [params]>[photo:]photo_id</flickr>
</pre>
