<?php
/*
Plugin Name: Flickr Tag
Description: Insert Flickr sets, tags or individual photos in your posts by using a special tag.
Author: Jeff Maki
Author URI: http://www.webopticon.com
Version: 1.3.1

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

require(dirname(__FILE__) . "/flickr-common.php");
require(dirname(__FILE__) . "/flickr-admin.php");

add_action('wp_head', 'flickr_get_head');

function flickr_get_head() {
?>
	<script type="text/javascript" src="/wp-content/plugins/flickr/js/yahoo.js"></script>
	<script type="text/javascript" src="/wp-content/plugins/flickr/js/dom.js"></script>
	<script type="text/javascript" src="/wp-content/plugins/flickr/js/event.js"></script>
	<script type="text/javascript" src="/wp-content/plugins/flickr/js/container.js"></script>
	<script type="text/javascript" src="/wp-content/plugins/flickr/js/utilities.js"></script>

	<script type="text/javascript" src="/wp-content/plugins/flickr/js/autoTooltips.js"></script>
	<link href="/wp-content/plugins/flickr/css/autoTooltips.css" type="text/css" rel="stylesheet"/>

	<link href="/wp-content/plugins/flickr/css/flickr.css" type="text/css" rel="stylesheet"/>
<?
}

add_action('the_content', 'flickr_expand');

function flickr_expand($content) {
	while(true) {
		$s = strpos($content, '<flickr');
		
		// no more flickr tags
		if(! $s) 
			break;

		$s2 = strpos($content, '>', $s);

		// malformed tag
		if(! $s2)
			continue;

		$e = strpos($content, '</flickr>', $s2);

		// malformed tag
		if(! $e)
			continue;

		$tag_params = substr($content, $s + strlen('<flickr'), $s2 - $s - strlen('<flickr'));		// tag params for addition to "img" tag
		$contents = substr($content, $s2 + 1, $e - $s2 - 1);						// contents of tag (i.e. "cdata" in xml parlance)

		// replace flickr tag with rendered HTML
		$content = substr($content, 0, $s) . flickr_render($contents, $tag_params) . substr($content, $e + strlen('</flickr>'));
	}

	return $content;
}

function flickr_render($input, $tag_params) {
	$param = null;
	$mode = null;

	$p = strpos($input, ":");
	if($p) {
		$mode = strtolower(substr($input, 0, $p));
		$param = substr($input, $p + 1);
	} else
		$param = $input;

	switch($mode) {
		case "set":
			$params = array(
				'photoset_id'		=> $param,
				'privacy_filter' 	=> 1, // public
				'method'		=> 'flickr.photosets.getPhotos',
				'format'		=> 'php_serial'
			);

			$r = flickr_api_call($params);

			if(! $r)
				return flickr_bad_config();

			return flickr_render_photos($r['photoset'], $mode, $tag_params);						

		case "tag":
			$p = strpos($param, "@");

			if($p) {
				$tags = substr($param, 0, $p);
				$user = substr($param, $p + 1);
			} else {
				$tags = $param;
				$user = null;
			}

			$nsid = null;
			if($user) {
				$params = array(
					'username'		=> $user,
					'method'		=> 'flickr.people.findByUsername',
					'format'		=> 'php_serial'
				);

				$r = flickr_api_call($params);

				if(! $r)
					return flickr_bad_config();

				$nsid = $r['user']['nsid'];
			}

			$params = array(
				'method'		=> 'flickr.photos.search',
				'tags'			=> $tags,
				'format'		=> 'php_serial'
			);

			if(strpos($tags, "&") > 0) {
				$params['tag_mode'] = "all";
				$params['tags'] = str_replace("&", ",", $params['tags']);
			} else
				$params['tag_mode'] = "any";

			if($nsid)
				$params['user_id'] = $nsid;

			$r = flickr_api_call($params);
	
			if(! $r)
				return flickr_bad_config();

			return flickr_render_photos($r['photos'], $mode, $tag_params);

		case "photo":
		default:
			$params = array(
				'photo_id'		=> $param,
				'method'		=> 'flickr.photos.getInfo',
				'format'		=> 'php_serial'
			);

			$r = flickr_api_call($params);

			if(! $r)
				return flickr_bad_config();

			return flickr_render_photos($r['photo'], $mode, $tag_params);
	}
}

function flickr_render_photos($result, $mode, $tag_params) {
	GLOBAL $flickr_config;

	$html = "";
	$extra = $tag_params;

	$i = null;
	switch($mode) {
		case "tag":
		case "set":
			$i = $result['photo'];
			break;

		default:
		case "photo":
			$i = array($result);
			break;
	}

	foreach($i as $photo) {
		$params = array(
			'photo_id'		=> $photo['id'],
			'method'		=> 'flickr.photos.getInfo',
			'format'		=> 'php_serial'
		);

		$r = flickr_api_call($params);

		if(! $r)
			return flickr_bad_config();
                                
		$a_url = "http://www.flickr.com/photos/" . $r['photo']['owner']['nsid'] . "/" . $photo['id'] . "/";

		switch($mode) {
			case "tag":
				$size = $flickr_config['tag_size'];
				$title = $r['photo'][$flickr_config['tag_tooltip']]['_content'];

				break;

			case "set":
				$size = $flickr_config['set_size'];
				$title = $r['photo'][$flickr_config['set_tooltip']]['_content'];

				$a_url .= "in/set-" . $result['id'] . "/";
	
				break;

			default:
			case "photo":
				$size = $flickr_config['photo_size'];
				$title = $r['photo'][$flickr_config['photo_tooltip']]['_content'];

				break;
		}

		if($title)
			$extra .= ' title="' . htmlentities($title) . '"';

		$img_url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_" . $size . ".jpg";

		$html .= '<a href="' . $a_url . '" class="flickr_link"><img src="' . $img_url . '" alt="" class="flickr_img ' . $size . ' ' . $mode . '" ' . $extra . '"/></a>';
	}

	return $html;
}

function flickr_bad_config() {
	return '<div class="flickr_error"><p>There was an error while querying Flickr. Check your configuration, or try this request again later.</p></div>';
}
?>
