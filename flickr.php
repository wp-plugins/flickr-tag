<?php
/*
Plugin Name: Flickr
Description: This plugin allows you to show flickr sets/tags/individual photos in your posts by using a special tag.
Author: Jeff Maki
Author URI: http://www.webopticon.com
Version: 1.0
*/

/////////////////// START EDITING HERE ///////////////////////

// You do need to change this--apply for your own key at http://www.flickr.com/api
define(FLICKR_PLUGIN_API_KEY, " <api key goes here> ");
define(FLICKR_PLUGIN_NSID, " <nsid goes here> ");

// You probably don't need to change these...
define(FLICKR_PLUGIN_CACHE_TTL_S, 60 * 60 * 24 * 2); // 2 days
define(FLICKR_PLUGIN_CACHE_DIR, ABSPATH . "/wp-content/plugins/flickr/cache");

//////////////////// END EDITING HERE ////////////////////////

function flickr_api_call($params) {
	$encoded_params = array();

	foreach ($params as $k=>$v)
		$encoded_params[] = urlencode($k) . '=' . urlencode($v);

	// put params into canonical order; find hash
	ksort($params);
	$cache_key = md5(join($params, " "));

	if(file_exists(FLICKR_PLUGIN_CACHE_DIR . "/" . $cache_key . ".cache") && (time() - filemtime(FLICKR_PLUGIN_CACHE_DIR . "/" . $cache_key . ".cache")) < FLICKR_PLUGIN_CACHE_TTL_S)
		$o = unserialize(file_get_contents(FLICKR_PLUGIN_CACHE_DIR . "/" . $cache_key . ".cache"));
	else {
                @$c = curl_init();

                if($c) {
                        curl_setopt($c, CURLOPT_URL, "http://api.flickr.com/services/rest/");
                        curl_setopt($c, CURLOPT_POST, 1);
                        curl_setopt($c, CURLOPT_POSTFIELDS, implode('&', $encoded_params));
                        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 10);
  
                        $r = curl_exec($c);
                } else
                        $r = file_get_contents("http://api.flickr.com/services/rest/?" . implode('&', $encoded_params));

		if(! $r)
			return null;

		$o = unserialize($r);

		if($o['stat'] != "ok")
			return null;

		// save serialized response to cache
		if(! is_dir(FLICKR_PLUGIN_CACHE_DIR))
			mkdir(FLICKR_PLUGIN_CACHE_DIR);

		file_put_contents(FLICKR_PLUGIN_CACHE_DIR . "/" . $cache_key . ".cache", $r, LOCK_EX);
	}

	return $o;
}

add_action('wp_upload_tabs', 'get_flickr_tab');

function get_flickr_tab() {
	GLOBAL $post_id;

	if(! $post_id)	// only show on post edit/create page
		return array();

	return array('flickr' => array('Flickr', 'upload_files', 'get_flickr_tab_content', null, null));
}

function get_flickr_tab_content() {
	// FIXME: HACK
	if($_REQUEST['flickr_flushcache'] == "true" && strlen(FLICKR_PLUGIN_CACHE_DIR) > 0) // if FLICKR_PLUGIN_CACHE_DIR is empty, this removes the root! 
		system("rm -f " . FLICKR_PLUGIN_CACHE_DIR . "/*");

?>	
	<script language="JavaScript">
		function insertIntoEditor(h) {
			var win = window.opener ? window.opener : window.dialogArguments;
                
			if ( !win )
				win = top;
		
			tinyMCE = win.tinyMCE;
		
			if( typeof tinyMCE != 'undefined' && tinyMCE.getInstanceById('content') ) {
				tinyMCE.selectedInstance.getWin().focus();
				tinyMCE.execCommand('mceInsertContent', false, h);
			} else
				win.edInsertContent(win.edCanvas, h);
                        
			if(!this.ID)
				this.cancelView();
		
			return false;
		}
	</script>

	<div style="padding: 10px; padding-left: 15px;">
		Tag Usage Syntax:

		<p style="font-family: courier;">		
			&lt;flickr [params]&gt;set:set_id&lt;/flickr&gt;<br>
			&lt;flickr [params]&gt;tag:tag1[(,|&)tag2...][@username]&lt;/flickr&gt;<br>
			&lt;flickr [params]&gt;[photo:]photo_id&lt;/flickr&gt;
		</p>

		<p style="font-style: italic;">
			Any parameters you add to the flickr tag (e.g. "style" or "alt") are added to the inserted image tag. <br/>
			If no mode is provided, "photo" is assumed (depricated). 
		<p>

		Choose a set from the list below to insert into your post:

		<p style="padding-left: 30px;">
			<?php
				$params = array(
					'api_key'	=> FLICKR_PLUGIN_API_KEY,
					'method'	=> 'flickr.photosets.getList',
					'user_id'	=> FLICKR_PLUGIN_NSID,
					'format'	=> 'php_serial'
				);

				$r = flickr_api_call($params);

				if($r) {
					echo '<select id="flickr_sets">';

					foreach($r['photosets']['photoset'] as $number=>$photoset)
						echo '<option value="' . $photoset['id'] . '">' . $photoset['title']['_content'] . '</option>';
					
					echo '</select>';

					echo '<input class="button" type="button" value="Send to editor &raquo;" onClick="insertIntoEditor(\'<flickr>set:\' + document.getElementById(\'flickr_sets\').value + \'</flickr>\');">';
				} else { 
					echo "<I>No sets were found on Flickr. Did you setup your API key and NSID correctly?</I>";
				}
			?>
		</p>

		<p>
			Or, click on a thumbnail to insert one of your favorites into your post:
		</p>

		<a name="favorites">
		<p style="padding-left: 30px;">
			<?php
				$params = array(
					'api_key'	=> FLICKR_PLUGIN_API_KEY,
					'user_id'	=> FLICKR_PLUGIN_NSID,
					'method'	=> 'flickr.favorites.getPublicList',
					'format'	=> 'php_serial'
				);

				$r = flickr_api_call($params);

				if($r) {
					foreach($r['photos']['photo'] as $number=>$photo) {
						$img_url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_s.jpg";

						echo '<a href="#favorites" onClick="insertIntoEditor(\'<flickr>photo:' . $photo['id'] . '</flickr>\');" class="flickr_link"><img src="' . $img_url . '" alt="" class="flickr_img flickr_thumbnail"/></a>';
					}
				} else {
					echo "<I>No favorites were found on Flickr. Did you setup your API key and NSID correctly?</I>";
				}	
			?>
		</p>
	
		If you've changed your sets or tags on Flickr but aren't seeing the changes on your blog, you need to flush the Flickr cache by using the button below. 
		
		<p style="padding-left: 30px;">
			<input class="button" type="button" value="Flush Flickr Cache &raquo;" onClick="window.location.href='<?php echo $_SERVER['SCRIPT_URI'] . "?" . $_SERVER['QUERY_STRING']; ?>&flickr_flushcache=true';">
		</p>
	</div>
<?php
}

add_action('wp_head', 'flickr_javascript');

function flickr_javascript() {
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

add_action('the_content', 'expand_flickr');

function expand_flickr($content) {
	while(true) {
		$s = strpos($content, '<flickr');
		
		if(! $s)
			break;

		$s2 = strpos($content, '>', $s);

		if(! $s2)
			continue;

		$e = strpos($content, '</flickr>', $s2);

		if(! $e)
			continue;

		$tag_param = substr($content, $s + strlen('<flickr'), $s2 - $s - strlen('<flickr'));		// tag params for addition to "img" tag
		$contents = substr($content, $s2 + 1, $e - $s2 - 1);						// contents of tag ("cdata" in xml language)

		// replace flickr tag with rendered HTML
		$content = substr($content, 0, $s) . flickr_render($contents, $tag_param) . substr($content, $e + strlen('</flickr>'));
	}

	return $content;
}

function flickr_render($input, $tag_param) {
	$p =  strpos($input, ":");

	$mode = null;
	if($p) {
		$mode = strtolower(substr($input, 0, $p));
		$param = substr($input, $p + 1);
	} else
		$param = $input;


	$html = "";	
	switch($mode) {
		case "set":
			$params = array(
				'api_key'		=> FLICKR_PLUGIN_API_KEY,
				'photoset_id'		=> $param,
				'privacy_filter' 	=> 1, // public
				'method'		=> 'flickr.photosets.getPhotos',
				'format'		=> 'php_serial'
			);

			$r = flickr_api_call($params);

			if(! $r)
				return "";


			$html = "";	
			foreach($r['photoset']['photo'] as $number=>$photo) {
				$img_url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_s.jpg";
				$url = "http://www.flickr.com/photos/" . $r['photoset']['owner'] . "/" . $photo['id'] . "/in/set-" . $param . "/";


				$params = array(
					'api_key'		=> FLICKR_PLUGIN_API_KEY,
					'photo_id'		=> $photo['id'],
					'method'		=> 'flickr.photos.getInfo',
					'format'		=> 'php_serial'
				);

				$r2 = flickr_api_call($params);


				$extra = $tag_param;
				if($r2) 
					$extra .= 'title="' . htmlentities($r2['photo']['description']['_content']) . '"';

				$html .= '<a href="' . $url . '" class="flickr_link"><img src="' . $img_url . '" alt="" class="flickr_img flickr_thumbnail" ' . $extra . '/></a>';
			}

			break;

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
					'api_key'		=> FLICKR_PLUGIN_API_KEY,
					'username'		=> $user,
					'method'		=> 'flickr.people.findByUsername',
					'format'		=> 'php_serial'
				);

				$r = flickr_api_call($params);

				if(! $r)
					return "";

				$nsid = $r['user']['nsid'];
			}


			$params = array(
				'api_key'		=> FLICKR_PLUGIN_API_KEY,
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
				return "";


			$html = "";	
			foreach($r['photos']['photo'] as $number=>$photo) {
				$img_url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_s.jpg";
				$url = "http://www.flickr.com/photos/" . $photo['owner'] . "/" . $photo['id'] . "/";


				$params = array(
					'api_key'		=> FLICKR_PLUGIN_API_KEY,
					'photo_id'		=> $photo['id'],
					'method'		=> 'flickr.photos.getInfo',
					'format'		=> 'php_serial'
				);

				$r2 = flickr_api_call($params);


				$extra = $tag_param;
				if($r2) 
					$extra .= 'title="' . htmlentities($r2['photo']['description']['_content']) . '"';

				$html .= '<a href="' . $url . '" class="flickr_link"><img src="' . $img_url . '" alt="" class="flickr_img flickr_thumbnail" ' . $extra . '/></a>';
			}

			break;

		case "photo":
		default:
			$params = array(
				'api_key'		=> FLICKR_PLUGIN_API_KEY,
				'photo_id'		=> $param,
				'method'		=> 'flickr.photos.getInfo',
				'format'		=> 'php_serial'
			);

			$r = flickr_api_call($params);

			if(! $r)
				return "";

			$extra = $tag_param;
			$extra .= 'title="' . htmlentities($r['photo']['description']['_content']) . '"';

			$img_url = 'http://farm' . $r['photo']['farm'] . '.static.flickr.com/' . $r['photo']['server'] . '/' . $r['photo']['id'] . '_' . $r['photo']['secret'] . '_m.jpg';
			$url = "http://www.flickr.com/photos/" . $r['photo']['owner']['nsid'] . "/" . $r['photo']['id'] . "/";

			$html .= '<a href="' . $url . '" class="flickr_link"><img src="' . $img_url . '" alt="" class="flickr_img" ' . $extra . '/></a>';

			break;
	}

	return $html;
}
?>
