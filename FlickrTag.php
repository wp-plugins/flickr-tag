<?php
/*
Plugin Name: Flickr Tag
Plugin URI: http://www.webopticon.com/archives/148
Description: Insert Flickr sets, tags or individual photos in your posts by using a special tag.
Author: Jeff Maki
Author URI: http://www.webopticon.com/about
Version: 2.0.0-RC4

Copyright 2007 Jeffrey Maki (email: crimesagainstlogic@gmail.com)

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

// remove wptexturize from filters, then re-add after our processing of "the content". 
// wptexturize messes up literal quotes that may be inside our "flickr" tags...
remove_filter('the_content', 'wptexturize');
add_filter('the_content', 'wptexturize', 16);

add_filter('the_content', array($e, "processContent"), 15);

// load admin stuff if we're in the admin section--otherwise skip for speed.
if(strpos($_SERVER['REQUEST_URI'], "wp-admin")) {
	require(dirname(__FILE__) . "/FlickrTagAdmin.php");

	$a = new FlickrTagAdmin();

	add_action("admin_menu", array($a, "setupAdminMenu"));
	add_action("wp_upload_tabs", array($a, "setupUserMenu"));
}
