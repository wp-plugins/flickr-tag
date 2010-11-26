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

class FlickrTagAdmin extends FlickrTagCommon {
	var $request = array();

	function FlickrTagAdmin() {
		parent::FlickrTagCommon();

		$this->getRequest();

        	add_action("admin_menu", array($this, "getAdminMenu"));
		add_action("admin_print_scripts", array($this, "getAdminHead"));

       		add_action('media_buttons_context', array($this, "getButtonContext"));

		add_action("media_upload_tabs", array($this, "getMediaUploadTabs"), 99); 
		add_action("media_upload_flickr_tag", array($this, "getFlickrUploadContent"));
		add_action("media_upload_flickr_tag_syntax", array($this, "getFlickrUploadContent"));
		add_action("media_upload_flickr_tag_set", array($this, "getFlickrUploadContent"));
		add_action("media_upload_flickr_tag_recent", array($this, "getFlickrUploadContent"));
		add_action("media_upload_flickr_tag_favorites", array($this, "getFlickrUploadContent"));
	}

	function getAdminMenu() {
		add_submenu_page("plugins.php", "Flickr Tag", "Flickr Tag", "administrator", basename(__FILE__), array($this, "getAdminContent"));
	}

	function getButtonContext($html) {
	        global $post_ID, $temp_ID;
        
		return $html . '<a href="media-upload.php?post_id=' . (int)(0 == $post_ID ? $temp_ID : $post_ID) . '&amp;type=flickr_tag&amp;TB_iframe=true&amp;height=650&amp;width=640" class="thickbox" title="Insert an image from Flickr"><img src="' . get_bloginfo("wpurl") . '/wp-content/plugins/flickr-tag/images/flickr-button.gif"/></a>';
	}

	function getMediaUploadTabs($tabs) {
		// do not change tabs for non-flickr-tag pages
		if($_REQUEST['type'] != "flickr_tag")
			return $tabs;
		else
			return array("flickr_tag_recent" => "Insert a Recent Photo", "flickr_tag_favorites" => "Insert a Favorite Photo", "flickr_tag_set" => "Insert a Set", "flickr_tag_syntax" => "Tag Syntax");
	}

	function getFlickrUploadContent() {
		wp_enqueue_style("media");

		// (hide the error from WP about passing in an array to this function)
		return @wp_iframe(array($this, "getIFrameContent"));
	}

	function getDisplayDefaultsOptionsHTML($entity) {
	?>
		<table class="form-table">
		<tbody>
			<tr valign="top">
			<th scope="row">
				Thumbnail size
			</th>
			<td>
				<select size=1 name="flickr_tag_<?php echo $entity; ?>_size">
					<option value="square" <?php if($this->request[$entity . '_size'] == "square") echo "selected"; ?>>Square (75 x 75 pixels)</option>
					<option value="thumbnail" <?php if($this->request[$entity . '_size'] == "thumbnail") echo "selected"; ?>>Thumbnail (100 x 75 pixels)</option>
					<option value="small" <?php if($this->request[$entity . '_size'] == "small") echo "selected"; ?>>Small (240 x 180 pixels)</option>
					<option value="medium" <?php if($this->request[$entity . '_size'] == "medium") echo "selected"; ?>>Medium (500 x 375 pixels)</option>
					<option value="large" <?php if($this->request[$entity . '_size'] == "large") echo "selected"; ?>>Large (1024 x 768 pixels)</option>
					<option value="original" <?php if($this->request[$entity . '_size'] == "original") echo "selected"; ?>>Original (varies in size)</option>
				</select>

				<p class="more">
					The availability of "original" size images is dependent on the author's sharing settings. 
				</p>
			</td>
			</tr>

			<tr valign="top">
			<th scope="row">
				Tooltip/caption
			</th>
			<td>
				<input type="radio" name="flickr_tag_<?php echo $entity; ?>_tooltip" value="description" <?php if($this->request[$entity . '_tooltip'] == "description") echo "checked"; ?>> Use the photo's description.
				<br/>
				<input type="radio" name="flickr_tag_<?php echo $entity; ?>_tooltip" value="title" <?php if($this->request[$entity . '_tooltip'] == "title") echo "checked"; ?>> Use the photo's title.
			</td>
			</tr>

	<?php 
		if(isset($this->config[$entity . "_limit"])) {
	?>
			<tr valign="top">
			<th scope="row">
				Display a maximum of
			</th>
			<td>
				<input type="text" size=3 name="flickr_tag_<?php echo $entity; ?>_limit" value="<?php echo $this->request[$entity . '_limit']; ?>"> photo(s).

				<p class="more">
					The Flickr API limits this value to 100 or less.
				</p>
			</td>
			</tr>
	<?php 
		}
	?>
		</tbody>
		</table>		
	<?php
	}

	function getCurrentUser() {
		if($this->optionGet("token")) {
			$params = array(
				'method'	=> 'flickr.auth.checkToken',
				'auth_token'	=> $this->optionGet("token"),
				'format'	=> 'php_serial'
			);

			$r = $this->apiCall($params, false, true);

			if($r) {
				return $r;
			} else {
				// bad token--erase it
				$this->optionSet("token", null);
				$this->optionSet("nsid", null);

				$this->optionSaveAll();
			}
		} 

		return null;
	}

	// find only request parameters that belong to us
	function getRequest() {
		foreach($_REQUEST as $key=>$value) {
			if(substr($key, 0, 11) == "flickr_tag_")
				$this->request[substr($key, 11)] = trim($value);
		}
	}

	function processRequest() {
		$has_error = false;

		// convert frob into token (auth. step 2)
		if(isset($this->request['frob']) && ! $this->optionGet("nsid") && ! $this->optionGet("token")) { 
			$params = array(
				'method'	=> 'flickr.auth.getToken',
				'frob'		=> $this->request['frob'],
				'format'	=> 'php_serial'
			);

			$r = $this->apiCall($params, false);

			// save auth token to DB for later use
			if($r) {
				$this->optionSet("token", $r['auth']['token']['_content']);
				$this->optionSet("nsid", $r['auth']['user']['nsid']);

				$this->optionSaveAll();
			} else
				echo $this->error("Error converting frob into token; authentication failed.");
		} else

		// logout
		if(isset($this->request["logout"])) {
			$this->optionSet("token", null);
			$this->optionSet("nsid", null);

			$this->optionSaveAll();
		} else

		// flush cache
		if(isset($this->request["flush"])) {
			$c = $this->cacheFlush();

			echo '<div class="updated fade"><p><strong>Removed ' . $c . ' item(s) from the cache.</strong></p></div>';
		} else 

		// save options
		if(isset($this->request["save"])) {
			if($this->isDisplayLimit($this->request["tag_limit"]) === null) {
				echo '<div class="error fade"><p><strong>The display limit for tags must be a number less than or equal to 100.</strong></p></div>';
				$has_error = true;
			}

			if($this->isDisplayLimit($this->request["set_limit"]) === null) {
				echo '<div class="error fade"><p><strong>The display limit for sets must be a number less than or equal to 100.</strong></p></div>';
				$has_error = true;
			}

			if($this->isDisplayLimit($this->request["photostream_limit"]) === null) {
				echo '<div class="error fade"><p><strong>The display limit for photostreams must be a number less than or equal to 100.</strong></p></div>';
				$has_error = true;
			}

			if($this->isDisplayLimit($this->request["group_limit"]) === null) {
				echo '<div class="error fade"><p><strong>The display limit for group pools must be a number less than or equal to 100.</strong></p></div>';
				$has_error = true;
			}

			if(! $has_error) {
				foreach($this->config as $key=>$value)
					if($this->request[$key])
						$this->optionSet($key, $this->request[$key]);

				$this->optionSaveAll();

				echo '<div class="updated fade"><p><strong>Settings successfully saved.</strong></p></div>';
			}
		}

		// initial load; if in error, leave old values there for user to fix
		if(! $has_error)
			foreach($this->config as $key=>$value)
				$this->request[$key] = $value;
	}

	function getAdminHead() {
	?>
		<link href="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/css/flickrTagAdmin.css" type="text/css" rel="stylesheet"/>
		<link href="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/css/flickrTag.css" type="text/css" rel="stylesheet"/>

		<script type="text/javascript" src="<?php bloginfo("wpurl"); ?>/wp-content/plugins/flickr-tag/js/flickrTag.js"></script>
	<?php
	}

	function getAdminContent() {
	?>
		<div class="wrap">
			<h2>Flickr Tag Plugin</h2>

			<?php
				$this->processRequest();
			?>

			<form action="" method="post">

			<h3>Authentication</h3>

			<table class="form-table">
			<tbody>
				<tr valign="top">
				<td>
				<?php
					$current_user = $this->getCurrentUser();

					if($current_user) {
						echo '<p>You are authenticated to Flickr as <a href="http://www.flickr.com/people/' . $current_user['auth']['user']['nsid'] . '" target="_new">' . $current_user['auth']['user']['username'] . '</a> (<a href="' . get_bloginfo("wpurl") . '/wp-admin/plugins.php?page=' . basename(__FILE__) . '&flickr_tag_logout=true">logout</a>).</p>';
					} else {
						$params = array(
							'method'	=> 'flickr.auth.getFrob',
							'format'	=> 'php_serial'
						);

						$r = $this->apiCall($params, false);

						$frob = $r['frob']['_content'];

						if($frob) {
							$flickr_url = "http://www.flickr.com/services/auth/";
							$flickr_url .= "?api_key=" . FLICKR_TAG_API_KEY;
							$flickr_url .= "&perms=read";
							$flickr_url .= "&frob=" . $frob;
							$flickr_url .= "&api_sig=" . md5(FLICKR_TAG_API_KEY_SS . "api_key" . FLICKR_TAG_API_KEY . "frob" . $frob . "permsread");
				?>
					<p>
					You are not authenticated with Flickr, but authorizing this plugin with Flickr is a simple two step process:
					</p>

					<p id="step1" class="current">
					<strong>Step 1:</strong> <a href="<?php echo $flickr_url; ?>" onClick="this.parentNode.className='disabled'; document.getElementById('step2').className='current';" target="_new">Authorize this application to access Flickr</a>. <em>This will open a new window. When you are finished, come back to this page.</em>
					</p>

					<p id="step2" class="disabled">
					<strong>Step 2:</strong> After authorizing this application with Flickr in the popup window, <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo basename(__FILE__); ?>&flickr_tag_frob=<?php echo $frob; ?>">click here to complete the authorization process</a>.
					</p>	
				<?php
						} else
							echo $this->error("Error getting frob; authentication aborted.");
					}
				?>
				</td>
				</tr>
			</tbody>
			</table>			

			<h3>Single Photo Display Options</h3>

			<?php
				$this->getDisplayDefaultsOptionsHTML("photo");
			?>

			<h3>Set Display Options</h3>

			<?php
				$this->getDisplayDefaultsOptionsHTML("set");
			?>

			<h3>Tag Display Options</h3>

			<?php
				$this->getDisplayDefaultsOptionsHTML("tag");
			?>

			<h3>Photostream Display Options</h3>

			<?php
				$this->getDisplayDefaultsOptionsHTML("photostream");
			?>

			<h3>Group Pool Display Options</h3>

			<?php
				$this->getDisplayDefaultsOptionsHTML("group");
			?>

			<h3>Photo Behavior</h3>

			<table class="form-table">
			<tbody>
				<tr valign="top">
				<th scope="row">
					When clicked, photos
				</th>
				<td>
					<input type="radio" name="flickr_tag_link_action" value="flickr" <?php if($this->request['link_action'] == "flickr") echo "checked"; ?>> Link to the photo's Flickr page.
					<br/>

					<input type="radio" name="flickr_tag_link_action" value="lightbox" <?php if($this->request['link_action'] == "lightbox") echo "checked"; ?>> Display a larger version in a <a href="http://www.huddletogether.com/projects/lightbox2/" target="_new">Lightbox</a>.
					<br/>

					<input type="radio" name="flickr_tag_link_action" value="lightbox_plugin" <?php if($this->request['link_action'] == "lightbox_plugin") echo "checked"; ?>> Generate Lightbox-compatable HTML for use by another Lightbox plugin that is installed. 
					<br/>

					<input type="radio" name="flickr_tag_link_action" value="none" <?php if($this->request['link_action'] != "flickr" && $this->request['link_action'] != "lightbox" && $this->request['link_action'] != "lightbox_plugin") echo "checked"; ?>> Do nothing.

					<p class="more">
						If Lightbox display mode is selected, tooltips on inline photos are disabled&mdash;tooltip content is shown as a caption in the Lightbox. Lightbox plugin mode requires another Lightbox Wordpress plugin to be installed.
					</p>
				</td>
				</tr>
			</tbody>
			</table>

			<h3>Caching</h3>

			<table class="form-table">
			<tbody>
				<tr valign="top">
				<th scope="row">
					Cache lifetime
				</th>
				<td>
					<select size=1 name="flickr_tag_cache_ttl">
						<option value="86400" <?php if($this->request['cache_ttl'] == "86400") echo "selected"; ?>>1 day</option>
						<option value="259200" <?php if($this->request['cache_ttl'] == "259200") echo "selected"; ?>>3 days</option>
						<option value="604800" <?php if($this->request['cache_ttl'] == "604800") echo "selected"; ?>>1 week</option>
						<option value="1209600" <?php if($this->request['cache_ttl'] == "1209600") echo "selected"; ?>>2 weeks</option>
						<option value="2592000" <?php if($this->request['cache_ttl'] == "2592000") echo "selected"; ?>>1 month</option>
						<option value="-1" <?php if($this->request['cache_ttl'] == "-1") echo "selected"; ?>>Forever</option>
					</select>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row">
					Manually flush cache
				</th>
				<td>
					<input type="submit" value="Flush cache now" name="flickr_tag_flush" class="button-secondary">

					<p class="more">
						If you have made changes on Flickr, but are not seeing these changes reflected on your blog, you may need to flush the Flickr cache. This will happen automatically after the cache lifetime period expires (set above).
					</p>
				</td>
				</tr>
			</tbody>
			</table>	

			<p class="submit">
				<input type="submit" name="flickr_tag_save" value="Save Changes" default>
			</p>

			</form>
		</div>
	<?php
	}

	function getIFrameContent() {
		$html = media_upload_header();

		$html .= '<div class="wrapper flickrTag_container">';

		switch($_REQUEST['tab']) {
			default:
			case "flickr_tag_recent":
				$html .= $this->getIFrameContent_FromMethod('flickr.people.getPublicPhotos');
				break;

			case "flickr_tag_favorites":
				$html .= $this->getIFrameContent_FromMethod('flickr.favorites.getList');
				break;

			case "flickr_tag_set":
				$html .= $this->getIFrameContent_Set();
				break;
	
			case "flickr_tag_syntax":
				$html .= $this->getIFrameContent_Syntax();
				break;
		}

		$html .= '</div>';

		echo $html;
	}

	function getIFrameContent_Set() {
		$html = "";

		$params = array(
			'method'	=> 'flickr.photosets.getList',
			'format'	=> 'php_serial'
		);

		$r = $this->apiCall($params, false, true);

		if($r) {
			$html .= '<select id="flickr_tag_sets">';

			foreach($r['photosets']['photoset'] as $number=>$photoset) 
				$html .= '<option value="' . $photoset['id'] . '">' . $photoset['title']['_content'] . ' (' . $photoset['photos'] . ' photo' . (($photoset['photos'] != 1) ? "s" : "") . ')</option>';
						
			$html .= '</select>';

			$html .= '<input class="button" type="button" value="Insert" onClick="flickrTag_insertIntoEditor(\'[flickr]set:\' + document.getElementById(\'flickr_tag_sets\').value + \'[/flickr]\');">';
		} else 
			$html .= $this->error("Call to get available sets failed.");

		return $html;
	}

	function getIFrameContent_FromMethod($method = 'flickr.people.getPublicPhotos') {
		$html = "";

		$params = array(
			'method'        => $method,
			'format'        => 'php_serial',
			'per_page'      => '40',
			'user_id'       => $this->optionGet("nsid")
		);

		$r = $this->apiCall($params, false, true);

		if($r) {
			foreach($r['photos']['photo'] as $number=>$photo) {
				$img_url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_s.jpg";
	
				$html .= '<a href="#" onClick="flickrTag_insertIntoEditor(\'[flickr]photo:' . $photo['id'] . '[/flickr]\'); return false;" class="flickr"><img src="' . $img_url . '" alt="" class="flickr square set"/></a>';
			}
		} else
			$html .= $this->error("Call to method '" . $method . "' failed.");

		return $html;
	}

	function getIFrameContent_Syntax() {
		$html = <<<EOF

		<strong>Usage</strong>

		<p class="syntax">
			[flickr <em>[params]</em>]set:set id<em>[(size[,limit])]</em>[/flickr] or <br/>
			[flickr <em>[params]</em>]tag:tag 1<em>[(,|+)tag 2...][@username][(size[,limit])]</em>[/flickr] or <br/>
			[flickr <em>[params]</em>]photo:photo id<em>[(size[,limit])]</em>[/flickr] or <br/>
			[flickr <em>[params]</em>]photostream:<em>[username][(size[,limit])]</em>[/flickr] or <br/>
			[flickr <em>[params]</em>]group:group name<em>[(size[,limit])]</em>[/flickr] <br/>
		</p>

		<strong>Notes</strong>

		<p>
			Any parameters (<em>[params]</em>) you add to the flickr tag (e.g. "style" or "alt") are added to the inserted image tag(s). 
		<p>

		<strong>Examples</strong>

		<p>
			To show "medium" photos tagged with "railcar" OR "train" from anyone, use:
		</p>

		<p class="syntax">
			[flickr]tag:railcar,train(medium)[/flickr]
		</p>

		<p>
			To show a maximum of 20 "large" photos tagged with "railcar" AND "adm" from the user "anemergencystop", padding images with 10 pixels on all sides, use:
		</p>

		<p class="syntax">
			[flickr style="padding: 10px;"]tag:railcar+adm@anemergencystop(large, 20)[/flickr]
		</p>

		<p>
			To show anemergencystop's photostream, use:
		</p>

		<p class="syntax">
			[flickr]photostream:anemergencystop[/flickr]
		</p>

		<p>
			To show 10 photos from the Clearview Signs group pool, use:
		</p>

		<p class="syntax">
			[flickr]group:clearview signs(,10)[/flickr]
		</p>
EOF;

		return $html;
	}
}
