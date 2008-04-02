<?php
/* 
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

class FlickrTagEngine extends FlickrTagCommon {
        function FlickrTagEngine() {
                parent::FlickrTagCommon();

		add_shortcode('flickr', array($this, "flickrShortcodeHandler"));

		add_action('wp_head', array($this, "getPublicHead"));
        }

	function getPublicHead() {
		if($this->optionGet('link_action') == "lightbox") {
	?>
			<script type="text/javascript" src="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/js/prototype.js"></script>
			<script type="text/javascript" src="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/js/scriptaculous.js?load=effects"></script>
			<script type="text/javascript" src="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/js/lightbox.js"></script>

			<link rel="stylesheet" href="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/css/lightbox.css" type="text/css" media="screen" />
	<?php
		} else {
	?>
			<script type="text/javascript" src="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/js/yahoo.js"></script>
			<script type="text/javascript" src="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/js/dom.js"></script>
			<script type="text/javascript" src="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/js/event.js"></script>
			<script type="text/javascript" src="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/js/container.js"></script>
			<script type="text/javascript" src="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/js/utilities.js"></script>

			<script type="text/javascript" src="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/js/autoTooltips.js"></script>
			<link href="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/css/autoTooltips.css" type="text/css" rel="stylesheet"/>
	<?php 
		}
	?>
		<link href="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/css/flickrTag.css" type="text/css" rel="stylesheet"/>
	<?php
	}

	function flickrShortcodeHandler($attrs, $contents = null) {
		return $this->renderTag($contents, $attrs);
	}

	function renderTag($tag, $tag_attrs = null) {
		$mode = null;
		$param = null;

		// split (optional) mode and extra parameters
		$p = strpos($tag, ":");

		if($p) {
			$mode = strtolower(substr($tag, 0, $p));
			$param = substr($tag, $p + 1);
		} else
			$param = $tag;

		if($mode != "set" && $mode != "tag" && $mode != "photo")
			$mode = "photo";

		// set size and limit defaults
		$size = $this->isPhotoSize($this->optionGet($mode . "_size"));
		$limit = $this->isDisplayLimit($this->optionGet($mode . "_limit"));

		// are size and or limit overrides are specified?
		$p = strpos($param, "(");
		
		if($p) {
			$p2 = strpos($param, ")");

			if($p2) {
				$overrides = split(",", substr($param, $p + 1, $p2 - $p - 1));
			
				if($this->isPhotoSize(trim($overrides[0])) !== null)
					$size = $this->isPhotoSize(trim($overrides[0]));

				if($this->isDisplayLimit(trim($overrides[1])) !== null) 
					$limit = $this->isDisplayLimit(trim($overrides[1]));
			}

			// strip off overrides after processing
			$param = substr($param, 0, $p);
		}

		switch($mode) {
			case "set":
				$params = array(
					'photoset_id'		=> $param,
					'privacy_filter' 	=> 1, // public
					'method'		=> 'flickr.photosets.getPhotos',
					'format'		=> 'php_serial'
				);

				$r = $this->apiCall($params);

				if(! $r)
					return $this->error("Bad call to display set '" . $param . "'");

				return $this->renderPhotos($r['photoset'], $mode, $tag_attrs, $size, $limit);

			case "tag":
				$p = strpos($param, "@");

				// user restriction provided...
				if($p) {
					$tags = substr($param, 0, $p);
					$user = substr($param, $p + 1);
				} else {
					$tags = $param;
					$user = null;
				}

				$params = array(
					'method'		=> 'flickr.photos.search',

					// the flickr API mandates tags be separated by a comma when using multiple tags
					'tags'			=> str_replace("+", ",", $tags),
					'format'		=> 'php_serial',
					'sort'			=> 'relevance'
				);

				// the plus implies an "and" relationship between tags--otherwise an "or" relationship is assumed
				if(strpos($tags, "+") > 0)
					$params['tag_mode'] = "all";
				else
					$params['tag_mode'] = "any";

				if($user) {
					$params2 = array(
						'username'		=> $user,
						'method'		=> 'flickr.people.findByUsername',
						'format'		=> 'php_serial'
					);

					$r = $this->apiCall($params2);

					if(! $r)
						return $this->error("Call to resolve user '" . $user . "' to an NSID failed.");
					else
						$params['user_id'] = $r['user']['nsid'];
				}

				$r = $this->apiCall($params);

				if(! $r)
					return $this->error("Call to display tag query '" . $tags . "' failed.");

				return $this->renderPhotos($r['photos'], $mode, $tag_attrs, $size, $limit);

			case "photo":
				$params = array(
					'photo_id'		=> $param,
					'method'		=> 'flickr.photos.getInfo',
					'format'		=> 'php_serial'
				);

				$r = $this->apiCall($params);

				if(! $r)
					return $this->error("Call to display photo '" . $param . "' failed.");

				return $this->renderPhotos($r['photo'], $mode, $tag_attrs, $size, $limit);
		}
	}

	function renderPhotos($result, $mode, $tag_attrs, $size, $limit) {
		$html = "";
		$i = null;

		// limit tag or set count, if specified
		if($mode == "tag" || $mode == "set")
			$i = @array_slice($result['photo'], 0, $limit);
		else 
			$i = array($result);

		if(! $i)
			return;

		$default_extra = "";

		if(is_array($tag_attrs)) {
			foreach($tag_attrs as $k=>$v)
				$default_extra .= $k . '="' . $v . '" ';

			$default_extra = trim($default_extra);
		}

		$lightbox_uid = md5(rand() . time());

		foreach($i as $photo) {
			$extra = $default_extra;

			$params = array(
				'photo_id'		=> $photo['id'],
				'method'		=> 'flickr.photos.getInfo',
				'format'		=> 'php_serial'
			);

			$r = $this->apiCall($params);

			if(! $r)
				return $this->error("Call to get metadata for photo '" . $photo['id'] . "' failed.");
                                

			$a_url = "http://www.flickr.com/photos/" . $r['photo']['owner']['nsid'] . "/" . $photo['id'] . "/" . (($mode == "set") ? "in/set-" . $result['id'] . "/" : "");
			$img_url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . $size . ".jpg";

			$title = trim($r['photo'][$this->optionGet($mode . '_tooltip')]['_content']);


			switch($this->optionGet("link_action")) {
				case "lightbox":
					$title .= ' <a href="' . $a_url . '">view&nbsp;on&nbsp;flickr&raquo;</a>';
					$a_url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . ".jpg";

					$rel = "lightbox" . (($mode == "tag" || $mode == "set") ? "[" . $lightbox_uid . "]" : "");

					$html .= '<a href="' . $a_url . '" class="flickr" title="' . htmlentities($title, ENT_COMPAT, get_option("blog_charset")) . '" rel="' . $rel . '"><img src="' . $img_url . '" alt="" class="flickr_img ' . $this->optionGet($mode . '_size') . ' ' . $mode . '" ' . $extra . '/></a>';

					break;

				case "flickr":
					if($title)
						$extra .= ' title="' . htmlentities($title, ENT_COMPAT, get_option("blog_charset")) . '"';

					$html .= '<a href="' . $a_url . '" class="flickr"><img src="' . $img_url . '" alt="" class="flickr_img ' . $this->optionGet($mode . '_size') . ' ' . $mode . '" ' . $extra . '/></a>';
					
					break;

				case "none":
				default:
					if($title)
						$extra .= ' title="' . htmlentities($title, ENT_COMPAT, get_option("blog_charset")) . '"';

					$html .= '<img src="' . $img_url . '" alt="" class="flickr_img ' . $this->optionGet($mode . '_size') . ' ' . $mode . '" ' . $extra . '/>';

					break;
			}
		}

		return $html;
	}
}
