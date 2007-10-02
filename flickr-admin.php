<?php
/*
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

add_action('admin_menu', 'flickr_get_admin_page');

function flickr_get_admin_page() {
	add_options_page("Flickr Tag", "Flickr Tag", "administrator", basename(__FILE__), "flickr_get_admin_page_content");
}

add_action('wp_upload_tabs', 'flickr_get_user_tab');

function flickr_get_user_tab() {
	GLOBAL $post_id;

	if(! $post_id)	// only show on post edit/create page
		return array();

	return array('flickr' => array('Flickr', 'upload_files', 'flickr_get_user_tab_content', null, null));
}

function flickr_get_admin_page_content() {
	GLOBAL $flickr_config;

	// flush cache
	if($_REQUEST['flickr_flush_cache']) {
		echo '<div class="updated fade"><p><strong>Removed ' . flickr_flush_cache() . ' item(s) from the cache.</strong></p></div>';

	} else {
	// save settings
		$c = 0;
		foreach($_REQUEST as $k=>$v) {
			// filter out form fields that are not ours
			if(substr($k, 0, 7) != "flickr_") 
				continue;

			update_option($k, trim($v));
			$c++;
		}

		if($c > 0) {
			flickr_load_config();

			echo '<div class="updated fade"><p><strong>Settings successfully saved.</strong></p></div>';
		}
	}	

	// check authentication token for validity
	$current_user = null;

	if($flickr_config['token']) {
		$params = array(
			'method'	=> 'flickr.auth.checkToken',
			'auth_token'	=> $flickr_auth_token,
			'format'	=> 'php_serial'
		);

		$r = flickr_api_call($params, false, true);

		if($r)
			$current_user = $r;
	} 

	if(! $current_user) 
		echo '<div class="error fade"><p><strong>Error! Our authentication token is either not set or was rejected by Flickr.</strong></p></div>';
?>
	<style type="text/css">
		.header {
			font-weight: bold;
		}

		.label {
			float: left;	
			text-align: right;
		
			width: 200px;

			padding: 0px;
			margin: 0px;
			margin-right: 10px;
			padding-top: 5px;
		}

		.more {
			font-style: italic;
			font-size: 75%;

			width: 400px;
			padding-left: 210px;
			margin-bottom: 30px;
		}

		.current {
			padding: 3px;
			background-color: #FFFFCC;
			border: 1px solid yellow;
		}

		.disabled {
			padding: 3px;
			background-color: #EFEFEF;
			border: 1px solid #C0C0C0;
			color: grey;
		}

		.disabled A {
			color: grey;
			text-decoration: none;
			cursor: default;
		}
	</style>

	<div class="wrap">
		<h2>Flickr Tag Plugin</h2>

		<form action="" method="post">
		<p class="header">Flickr Authentication</p>

		<?
			if($current_user)
				echo "Logged in to Flickr as <a href='http://www.flickr.com/people/" . $current_user['auth']['user']['nsid'] . "' target='_new'>" . $current_user['auth']['user']['username'] . "</a>.";
			
			if(! $flickr_config['token'] || ! $current_user) {
				$flickr_config['token'] = null;

				// convert frob into token
				if($_REQUEST['frob'] != "") { 
					$params = array(
						'method'	=> 'flickr.auth.getToken',
						'frob'		=> $_REQUEST['frob'],
						'format'	=> 'php_serial'
					);

					$r = flickr_api_call($params, false);

					if($r)
						update_option("flickr_token", $r['auth']['token']['_content']);
					
					header("Location: /wp-admin/options-general.php?page=" . basename(__FILE__));
				} else {
					$params = array(
						'method'	=> 'flickr.auth.getFrob',
						'format'	=> 'php_serial'
					);

					$r = flickr_api_call($params, false);

					$frob = $r['frob']['_content'];

					if($frob) {
						$flickr_url = "http://www.flickr.com/services/auth/";
						$flickr_url .= "?api_key=" . FLICKR_TAG_API_KEY;
						$flickr_url .= "&perms=read";
						$flickr_url .= "&frob=" . $frob;
						$flickr_url .= "&api_sig=" . md5(FLICKR_TAG_API_KEY_SS . "api_key" . FLICKR_TAG_API_KEY . "frob" . $frob . "permsread");

					?>
						Authorizing this plugin with Flickr is a simple, two step process:

						<p id="step1" class="current">
						<strong>Step 1:</strong> <a href="<?php echo $flickr_url; ?>" onClick="this.parentNode.className='disabled'; document.getElementById('step2').className='current';" target="_new">Authorize this application to access Flickr</a>. <em>This will open a new window. When you are finished, come back to this page.</em>
						</p>

						<p id="step2" class="disabled">
						<strong>Step 2:</strong> After authorizing this application with Flickr in the popup window, <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo basename(__FILE__); ?>&frob=<?php echo $frob; ?>">click here to complete the authorization process</a>.
						</p>
					<?php
					}
				}
			}
		?>

		<p class="header">Caching</p>

		<p class="label">Cache Lifetime:</p>
		<p class="field">
			<select size=1 name="flickr_cache_ttl">
				<option value="86400" <?php if($flickr_config['cache_ttl'] == "86400") echo "selected"; ?>>1 day</option>
				<option value="259200" <?php if($flickr_config['cache_ttl'] == "259200") echo "selected"; ?>>3 days</option>
				<option value="604800" <?php if($flickr_config['cache_ttl'] == "604800") echo "selected"; ?>>1 week</option>
				<option value="1209600" <?php if($flickr_config['cache_ttl'] == "1209600") echo "selected"; ?>>2 weeks</option>
				<option value="2592000" <?php if($flickr_config['cache_ttl'] == "2592000") echo "selected"; ?>>1 month</option>
			</select>
		</p>

		<p class="more">
			To save bandwidth and minimize queries of Flickr, this plugin saves responses from Flickr for later use in a cache. The cache lifetime specifies how long the saved copy should be used before it is considered to be "expired".
		</p>

		<p class="label">Flush Cache:</p>
		<p class="field">
			<input type="submit" value="Flush Cache Now" name="flickr_flush_cache">
		</p>

		<p class="more">
			If you have made changes on Flickr, but are not seeing these changes reflected on your blog, you may need to flush the Flickr cache. This will happen automatically after the cache lifetime period expires (set above).
		</p>

		<p class="header">Display</p>

		<p class="label">Individual Photo Size:</p>
		<p class="field">
			<select size=1 name="flickr_photo_size">
				<option value="s" <?php if($flickr_config['photo_size'] == "s") echo "selected"; ?>>Square (75 x 75 pixels)</option>
				<option value="t" <?php if($flickr_config['photo_size'] == "t") echo "selected"; ?>>Thumbnail (100 x 75 pixels)</option>
				<option value="m" <?php if($flickr_config['photo_size'] == "m") echo "selected"; ?>>Small (240 x 180 pixels)</option>
				<option value="b" <?php if($flickr_config['photo_size'] == "b") echo "selected"; ?>>Large (1024 x 768 pixels)</option>
			</select>
		</p>

		<p class="label">Individual Photo Tooltip:</p>	
		<p class="field">	
			<select size=1 name="flickr_photo_tooltip">
				<option value="description" <?php if($flickr_config['photo_tooltip'] == "description") echo "selected"; ?>>Photo Description</option>
				<option value="title" <?php if($flickr_config['photo_tooltip'] == "title") echo "selected"; ?>>Photo Title</option>
			</select>
		</p>

		<p class="more">
		The above options control how pictures using the tag mode "photo:..." are displayed.
		</p>

		<p class="label">Set Photo Size:</p>
		<p class="field">
			<select size=1 name="flickr_set_size">
				<option value="s" <?php if($flickr_config['set_size'] == "s") echo "selected"; ?>>Square (75 x 75 pixels)</option>
				<option value="t" <?php if($flickr_config['set_size'] == "t") echo "selected"; ?>>Thumbnail (100 x 75 pixels)</option>
				<option value="m" <?php if($flickr_config['set_size'] == "m") echo "selected"; ?>>Small (240 x 180 pixels)</option>
				<option value="b" <?php if($flickr_config['set_size'] == "b") echo "selected"; ?>>Large (1024 x 768 pixels)</option>
			</select>
		</p>

		<p class="label">Set Photo Tooltip:</p>	
		<p class="field">	
			<select size=1 name="flickr_set_tooltip">
				<option value="description" <?php if($flickr_config['set_tooltip'] == "description") echo "selected"; ?>>Photo Description</option>
				<option value="title" <?php if($flickr_config['set_tooltip'] == "title") echo "selected"; ?>>Photo Title</option>
			</select>
		</p>

		<p class="more">
		The above options control how pictures using the tag mode "set:..." are displayed.
		</p>

		<p class="label">Tag Photo Size:</p>
		<p class="field">
			<select size=1 name="flickr_tag_size">
				<option value="s" <?php if($flickr_config['tag_size'] == "s") echo "selected"; ?>>Square (75 x 75 pixels)</option>
				<option value="t" <?php if($flickr_config['tag_size'] == "t") echo "selected"; ?>>Thumbnail (100 x 75 pixels)</option>
				<option value="m" <?php if($flickr_config['tag_size'] == "m") echo "selected"; ?>>Small (240 x 180 pixels)</option>
				<option value="b" <?php if($flickr_config['tag_size'] == "b") echo "selected"; ?>>Large (1024 x 768 pixels)</option>
			</select>
		</p>

		<p class="label">Tag Photo Tooltip:</p>	
		<p class="field">	
			<select size=1 name="flickr_tag_tooltip">
				<option value="description" <?php if($flickr_config['tag_tooltip'] == "description") echo "selected"; ?>>Photo Description</option>
				<option value="title" <?php if($flickr_config['tag_tooltip'] == "title") echo "selected"; ?>>Photo Title</option>
			</select>
		</p>

		<p class="more">
		The above options control how pictures using the tag mode "tag:..." are displayed.
		</p>

		<p>
			<input type="submit" value="Save Changes">
		</p>

		</form>
	</div>
<?
}

function flickr_get_user_tab_content() {
?>	
	<script language="JavaScript">
		function insertIntoEditor(h) {
			var win = window.opener ? window.opener : window.dialogArguments;
                
			if (! win)
				win = top;
		
			tinyMCE = win.tinyMCE;
		
			if(typeof tinyMCE != 'undefined' && tinyMCE.getInstanceById('content')) {
				tinyMCE.selectedInstance.getWin().focus();
				tinyMCE.execCommand('mceInsertContent', false, h);
			} else
				win.edInsertContent(win.edCanvas, h);
                        
			if(! this.ID)
				this.cancelView();
		
			return false;
		}
	</script>

	<div style="padding: 10px; padding-left: 15px;">
		Choose a set from the list below to insert into your post:

		<p style="padding-left: 30px;">
			<?php
				$params = array(
					'method'	=> 'flickr.photosets.getList',
					'format'	=> 'php_serial'
				);

				$r = flickr_api_call($params, false, true);

				if($r) {
					echo '<select id="flickr_sets">';

					foreach($r['photosets']['photoset'] as $number=>$photoset)
						echo '<option value="' . $photoset['id'] . '">' . $photoset['title']['_content'] . '</option>';
					
					echo '</select>';

					echo '<input class="button" type="button" value="Send to editor &raquo;" onClick="insertIntoEditor(\'<flickr>set:\' + document.getElementById(\'flickr_sets\').value + \'</flickr>\');">';
				} else { 
					echo "<em>No sets were found on Flickr. Did you <a href='options-general.php?page=flickr.php' target='_top'>setup the plugin</a> yet?</em>";
				}
			?>
		</p>

		<p>
			Or, click on a thumbnail to insert one of your favorites into your post:
		</p>

		<p style="padding-left: 30px;">
			<?php
				$params = array(
					'method'	=> 'flickr.favorites.getList',
					'format'	=> 'php_serial'
				);

				$r = flickr_api_call($params, false, true);

				if($r) {
					foreach($r['photos']['photo'] as $number=>$photo) {
						$img_url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_s.jpg";

						echo '<a href="#" onClick="insertIntoEditor(\'<flickr>photo:' . $photo['id'] . '</flickr>\'); return false;" style="text-decoration: none; border: none;"><img src="' . $img_url . '" alt="" style="padding-right: 5px; padding-bottom: 5px;"/></a>';
					}
				} else {
					echo "<em>No favorites were found on Flickr. Did you <a href='options-general.php?page=flickr.php' target='_top'>setup the plugin</a> yet?</em>";
				}	
			?>
		</p>

		<p>
			Or, include a Flickr set, tag or photo in your post by using the "flickr" tag in your post's text. The syntax is: 
		</p>

		<p style="font-family: courier;">		
			&lt;flickr [params]&gt;set:set_id|tag:tag1[(,|&)tag2...][@username]|photo:photo_id&lt;/flickr&gt;
		</p>

		<p style="font-style: italic;">
			Any parameters you add to the flickr tag (e.g. "style" or "alt") are added to the inserted image tag(s).<br/>
			If no mode is provided, "photo" is assumed (depricated). 
		<p>

		<p>
			<em>Examples:</em>
		</p>

		<p>
			&lt;flickr style="padding: 10px;"&gt;tag:railcar@anemergencystop&lt;/flickr&gt;
			<br/>
			&lt;flickr&gt;set:72157602128216010&lt;/flickr&gt;
		</p>


	</div>
<?php
}

function flickr_flush_cache() {
        GLOBAL $flickr_config;

        $d = @opendir($flickr_config['cache_dir']);

	if(! $d)
		return 0;

        $f = 0;
        while(($file = readdir($d)) !== false) {
                $p = $flickr_config['cache_dir'] . "/" . $file;

                if(! is_file($p))
                        continue;

                if(is_file($p) && substr($p, strrpos($p, ".") + 1) == "cache")
                        if(@unlink($p))
                                $f++;
        }

        closedir($d);

        return $f;
}
?>
