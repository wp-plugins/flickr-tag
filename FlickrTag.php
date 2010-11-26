<?php
/*
Plugin Name: Flickr Tag
Plugin URI: http://wordpress.org/extend/plugins/flickr-tag/
Description: Insert Flickr sets, tags, photostreams, group pools or individual photos into your posts using a special Wordpress tag.
Author: Jeff Maki
Author URI: http://www.jeffmaki.com
Version: 2.4.7

Copyright 2008 Jeffrey Maki (email: crimesagainstlogic@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require(dirname(__FILE__) . "/FlickrTagCompat.php");
require(dirname(__FILE__) . "/FlickrTagCommon.php");
require(dirname(__FILE__) . "/FlickrTagEngine.php");

$e = new FlickrTagEngine();

// load admin stuff if we're in the admin section--otherwise skip for speed.
if(strpos($_SERVER['REQUEST_URI'], "/wp-admin/") !== false) {
	require(dirname(__FILE__) . "/FlickrTagAdmin.php");

	$a = new FlickrTagAdmin();
}
