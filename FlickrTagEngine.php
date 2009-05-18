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
			wp_enqueue_script("scriptaculous-effects");
			wp_enqueue_script("scriptaculous-builder");

			wp_print_scripts();
	?>
                        <script type="text/javascript" src="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/js/lightbox.js"></script>

			<script type="text/javascript">
				window.LightboxOptions.fileBottomNavCloseImage = "<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/images/closelabel.gif";
				window.LightboxOptions.fileLoadingImage = "<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/images/loading.gif";
			</script>

			<link rel="stylesheet" href="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/css/lightbox.css" type="text/css" media="screen" />
	<?php
		} else if($this->optionGet('link_action') == "flickr") {
			wp_enqueue_script("jquery");

			wp_print_scripts();
	?>
			<script type="text/javascript" src="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/js/flickrTagTooltips.js"></script>

			<link href="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/css/flickrTagTooltips.css" type="text/css" rel="stylesheet"/>
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
		// split mode and parameters
		$mode = null;
		$param = null;

		$p = strpos($tag, ":");

		if($p !== false) {
			$mode = strtolower(substr($tag, 0, $p));
			$param = substr($tag, $p + 1);
		} else {
			$mode = $tag;
			$param = null;
		}

		if($mode != "photo" && $mode != "group" && $mode != "tag" && $mode != "set" && $mode != "photostream")
			return $this->error("The mode '" . $mode . "' is invalid.");


		// get size and limit defaults, then process overrides if given
		$size = $this->isPhotoSize($this->optionGet($mode . "_size"));
		$limit = $this->isDisplayLimit($this->optionGet($mode . "_limit"));

		$p = strpos($param, "(");
		
		if($p !== false) {
			$p2 = strpos($param, ")");

			if($p2 !== false) {
				$overrides = split(",", substr($param, $p + 1, $p2 - $p - 1));
			
				if($this->isPhotoSize(trim($overrides[0])) !== null)
					$size = $this->isPhotoSize(trim($overrides[0]));

				if($this->isDisplayLimit(trim($overrides[1])) !== null) 
					$limit = $this->isDisplayLimit(trim($overrides[1]));
			}

			// strip off overrides from param after processing
			$param = substr($param, 0, $p);
		}

		
		switch($mode) {
			case "set":
				if(! $param)
					return $this->error("No set ID was provided.");

				$params = array(
					'photoset_id'		=> $param,
					'privacy_filter' 	=> 1, // public
					'method'		=> 'flickr.photosets.getPhotos',
					'extras'		=> 'original_format',
					'format'		=> 'php_serial'
				);

				$r = $this->apiCall($params);

				if(! $r)
					return $this->error("Bad call to display set '" . $param . "'");

				return $this->renderPhotos($r['photoset'], $mode, $tag_attrs, $size, $limit);

			case "tag":
				$tags = null;
				$user = null;

				// user restriction
				$p = strpos($param, "@");

				if($p !== false) {
					$tags = substr($param, 0, $p);
					$user = substr($param, $p + 1);
				} else {
					$tags = $param;
					$user = null;
				}

				if(! $tags)
					return $this->error("No search tags were provided.");

				$params = array(
					'method'		=> 'flickr.photos.search',
					'tags'			=> str_replace("+", ",", $tags), // flickr requires tags be separated by a comma
					'format'		=> 'php_serial',
					'extras'		=> 'original_format',
					'sort'			=> 'relevance'
				);

				// the plus implies an "and" relationship between tags--otherwise an "or" relationship is assumed
				if(strpos($tags, "+") > 0)
					$params['tag_mode'] = "all";
				else
					$params['tag_mode'] = "any";
				
				// lookup username provided, add nsid to request params
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

			case "photostream":
				$params = array(
					'method'		=> 'flickr.people.getPublicPhotos',
					'extras'		=> 'original_format',
					'format'		=> 'php_serial',
				);

				// lookup username provided, add nsid to request params
                                if($param) {
                                        $params2 = array(
                                                'username'              => $param,                           
                                                'method'                => 'flickr.people.findByUsername',
                                                'format'                => 'php_serial'
                                        );

                                        $r = $this->apiCall($params2);

                                        if(! $r)
                                                return $this->error("Call to resolve user '" . $param . "' to an NSID failed.");
                                        else
						$params['user_id'] = $r['user']['nsid'];
                                } else {
					// default to self
					$params['user_id'] = $this->optionGet('nsid');
				}

				$r = $this->apiCall($params);

				if(! $r)
					return $this->error("Bad call to display photostream");

				return $this->renderPhotos($r['photos'], $mode, $tag_attrs, $size, $limit);

			case "photo":
				if(! $param)
					return $this->error("No photo ID was provided.");

				$params = array(
					'photo_id'		=> $param,
					'method'		=> 'flickr.photos.getInfo',
					'format'		=> 'php_serial'
				);

				$r = $this->apiCall($params);

				if(! $r)
					return $this->error("Call to display photo '" . $param . "' failed.");

				return $this->renderPhotos($r['photo'], $mode, $tag_attrs, $size, $limit);

			case "group":
				$group = null;
				$user = null;

				// user restriction
				$p = strpos($param, "@");

				if($p) {
					$group = substr($param, 0, $p);
					$user = substr($param, $p + 1);
				} else {
					$group = $param;
					$user = null;
				}

				if(! $group)
					return $this->error("No group was provided.");

				$params = array(
					'method'		=> 'flickr.groups.pools.getPhotos',
					'extras'		=> 'original_format',
					'format'		=> 'php_serial',
				);

				// convert group name to nsid
				$params2 = array(
					'text'			=> $group,
					'method'		=> 'flickr.groups.search',
					'format'		=> 'php_serial'
				);

				$r = $this->apiCall($params2);

				if(! $r)
					return $this->error("Call to resolve group '" . $group . "' to an NSID failed.");
				else {
					if(count($r['groups'][group]) > 0)
						$params['group_id'] = $r['groups'][group][0]['nsid']; // use the first matching group's NSID
					else
						return $this->error("No group matching '" . $group . "' was found.");
				}

				if($user) {
					$params3 = array(
						'username'		=> $user,
						'method'		=> 'flickr.people.findByUsername',
						'format'		=> 'php_serial'
					);

					$r = $this->apiCall($params3);

					if(! $r)
						return $this->error("Call to resolve user '" . $user . "' to an NSID failed.");
					else
						$params['user_id'] = $r['user']['nsid'];
				}

				$r = $this->apiCall($params);

				if(! $r)
					return $this->error("Call to display group with ID '" . $params['group_id'] . "' failed.");

				return $this->renderPhotos($r['photos'], $mode, $tag_attrs, $size, $limit);
		}
	}

	function renderPhotos($result, $mode, $tag_attrs, $size, $limit) {
		$html = '<p class="flickrTag_container">';

		// if we get a single photo back as a result, we need to wrap it in an array (HACK)
		$i = null;

		if($mode != "photo")
			$i = @array_slice($result['photo'], 0, $limit);
		else 
			$i = array($result);

		if(! $i)
			return;

		// construct extra tags to add to each "img" tag we make
		$default_extra = "";

		if(is_array($tag_attrs)) {
			foreach($tag_attrs as $k=>$v)
				if(strtolower($k) != "title") // we use title, so if the user tries to set it, ignore it.
					$default_extra .= $k . '="' . $v . '" ';

			$default_extra = trim($default_extra);
		}

		$lightbox_uid = md5(rand() . time());

		foreach($i as $photo) {
			$extra = $default_extra;


			// get photo metadata
			$params = array(
				'photo_id'		=> $photo['id'],
				'method'		=> 'flickr.photos.getInfo',
				'format'		=> 'php_serial'
			);

			$r = $this->apiCall($params);

			if(! $r)
				return $this->error("Call to get metadata for photo '" . $photo['id'] . "' failed.");


			// construct URLs for photo on flickr, and for the image itself                                
			$a_url = "http://www.flickr.com/photos/" . $r['photo']['owner']['nsid'] . "/" . $photo['id'] . "/" . (($mode == "set") ? "in/set-" . $result['id'] . "/" : "");

			$secret = $photo['secret'];
			$format = "jpg";

			if($size == "_o") {
				if($photo['originalsecret'] && $photo['originalformat']) {
					$secret = $photo['originalsecret'];
					$format = $photo['originalformat'];
				} else {
					$html .= $this->error("The author has chosen not to share the original photo file for the photo '" . $photo['id'] . "'.");
					continue;
				}
			}

			$img_url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $secret . $size . "." . $format;

			// this becomes the tooltip or the caption in the lightbox
			$title = trim($r['photo'][$this->optionGet($mode . '_tooltip')]['_content']);


			switch($this->optionGet("link_action")) {
				case "lightbox":
				case "lightbox_plugin":
					$title .= ' <a href="' . $a_url . '">view&nbsp;on&nbsp;flickr&raquo;</a>';
					$a_url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . ".jpg";

					$rel = "lightbox" . ((count($i) > 1) ? "[" . $lightbox_uid . "]" : "");

					$html .= '<a href="' . $a_url . '" class="flickr" title="' . htmlentities($title, ENT_COMPAT, get_option("blog_charset")) . '" rel="' . $rel . '">';
					$html .= '<img src="' . $img_url . '" alt="' . ((is_array($photo['title'])) ? $photo['title']['_content'] : $photo['title']) . '" class="flickr ' . $this->optionGet($mode . '_size') . ' ' . $mode . '" ' . $extra . '/>';
					$html .= '</a>';

					break;

				case "flickr":
					if($title)
						$extra .= ' title="' . htmlentities($title, ENT_COMPAT, get_option("blog_charset")) . '"';

					$html .= '<a href="' . $a_url . '" class="flickr">';
					$html .= '<img src="' . $img_url . '" alt="' . $photo['title'] . '" class="flickr ' . $this->optionGet($mode . '_size') . ' ' . $mode . '" ' . $extra . '/>';
					$html .= '</a>';
					
					break;

				case "none":
				default:
					if($title)
						$extra .= ' title="' . htmlentities($title, ENT_COMPAT, get_option("blog_charset")) . '"';

					$html .= '<img src="' . $img_url . '" alt="' . $photo['title'] . '" class="flickr ' . $this->optionGet($mode . '_size') . ' ' . $mode . '" ' . $extra . '/>';

					break;
			}
		}

		$html .= "</p>";

 		return $html;
	}
}
