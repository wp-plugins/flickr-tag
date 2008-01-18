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

class FlickrTagAdmin extends FlickrTagCommon {
	var $request = array();

	function FlickrTagAdmin() {
		parent::FlickrTagCommon();

		$this->getRequest();

		add_action("admin_head", array($this, "getAdminHead"));
	}

	// find only request parameters that belong to us
	function getRequest() {
		foreach($_REQUEST as $key=>$value) {
			if(substr($key, 0, 11) == "flickr_tag_")
				$this->request[substr($key, 11)] = $value;
		}
	}

	// called by wordpress add_action()
	function setupAdminMenu() {
		add_options_page("Flickr Tag", "Flickr Tag", "administrator", basename(__FILE__), array($this, "getAdminContent"));
	}

	// called by wordpress add_action()
	function setupUserMenu() {
		GLOBAL $post_id;

		if(! $post_id)	// only show on post edit/create page
			return array();

		return array("flickr" => array("Flickr", "upload_files", array($this, "getUserContent"), null, null));
	}

	function getDisplayDefaultsOptionsHTML($entity) {
	?>
		<p class="label">Photo Size:</p>
		<p class="field">
			<select size=1 name="flickr_tag_<?php echo $entity; ?>_size">
				<option value="square" <?php if($this->request[$entity . '_size'] == "square") echo "selected"; ?>>Square (75 x 75 pixels)</option>
				<option value="thumbnail" <?php if($this->request[$entity . '_size'] == "thumbnail") echo "selected"; ?>>Thumbnail (100 x 75 pixels)</option>
				<option value="small" <?php if($this->request[$entity . '_size'] == "small") echo "selected"; ?>>Small (240 x 180 pixels)</option>
				<option value="medium" <?php if($this->request[$entity . '_size'] == "medium") echo "selected"; ?>>Medium (500 x 375 pixels)</option>
				<option value="large" <?php if($this->request[$entity . '_size'] == "large") echo "selected"; ?>>Large (1024 x 768 pixels)</option>
			</select>
		</p>

		<p class="label">Tooltip/Caption Contents:</p>
		<p class="field">	
			<select size=1 name="flickr_tag_<?php echo $entity; ?>_tooltip">
				<option value="description" <?php if($this->request[$entity . '_tooltip'] == "description") echo "selected"; ?>>Photo Description</option>
				<option value="title" <?php if($this->request[$entity . '_tooltip'] == "title") echo "selected"; ?>>Photo Title</option>
			</select>
		</p>

	<?php 
		if($entity != "photo") { 
	?>
			<p class="label">Display Limit:</p>
			<p class="field">
				<input type="text" size=3 name="flickr_tag_<?php echo $entity; ?>_limit" value="<?php echo $this->request[$entity . '_limit']; ?>"> photo(s)
			</p>
	<?php 
		}
	?>

		<p class="more">
			The above options control how Flickr photos are displayed when using the Flickr tag mode "<?php echo $entity; ?>".
		</p>
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

			if($r)
				return $r;
			else {
				// bad token--erase it
				$this->optionSet("token", null);
				$this->optionSet("nsid", null);

				$this->optionSaveAll();
			}
		} 

		return null;
	}

	function migrate() {
		GLOBAL $wpdb, $table_prefix;

		$migrate_posts = $wpdb->get_results("SELECT * FROM " . $table_prefix . "posts WHERE post_content LIKE '%<flickr%>%</flickr>%';");

		foreach($migrate_posts as $post) {
			$id = $post->ID;
			$content = $post->post_content;

			// FIXME use regexes?
			while(true) {
				$p = strpos($content, "<flickr");

				if($p === false)
					break;

				$content = substr($content, 0, $p) . "[flickr" . substr($content, $p + 7);

				$p2 = strpos($content, ">", $p);
				$content[$p2] = "]";

				$p4 = strpos($content, "</flickr>", $p2);

				while(true) {
					$p3 = strpos($content, "&", $p2);
		
					if(! $p3 || $p3 > $p4)
						break;

					$content[$p3] = "+";
				}

				$content = str_replace("</flickr>", "[/flickr]", $content);
			}

			$wpdb->query("UPDATE wp_posts SET post_content='" . $wpdb->escape($content) . "' WHERE id=$id;");
		}

		$migrate_options = $wpdb->get_results("DELETE FROM " . $table_prefix . "options WHERE option_name LIKE 'flickr_%';");
	}

	function migrateNeeded() {
		GLOBAL $wpdb, $table_prefix;

		return $wpdb->get_var("SELECT count(*) FROM " . $table_prefix . "posts WHERE post_content LIKE '%<flickr%>%</flickr>%';") + $wpdb->get_var("SELECT count(*) FROM " . $table_prefix . "options WHERE option_name LIKE 'flickr_%' AND option_name NOT LIKE 'flickr_tag_%';");
	}

	function processRequest() {
		// migration of old data to new format
		if(isset($this->request['migrate']) && $this->migrateNeeded()) { 
			$this->migrate();

		} else

		// convert frob into token (auth. step 2)
		if(isset($this->request['frob'])) { 
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
				echo $this->error("Error converting frob into token");
					
			header("Location: /wp-admin/options-general.php?page=" . basename(__FILE__));
		} else

		// logout
		if(isset($this->request["logout"])) {
			$this->optionSet("token", null);
			$this->optionSet("nsid", null);

			$this->optionSaveAll();

			header("Location: /wp-admin/options-general.php?page=" . basename(__FILE__));
		} else

		// flush cache
		if(isset($this->request["flush"])) {
			$c = $this->cacheFlush();

			echo '<div class="updated fade"><p><strong>Removed ' . $c . ' item(s) from the cache.</strong></p></div>';
		} else 

		// save options
		if(isset($this->request["save"])) {
			$has_error = false;

			if($this->isDisplayLimit($this->request["tag_limit"]) === null) {
				echo '<div class="error fade"><p><strong>The display limit for tags must be a number.</strong></p></div>';
				$has_error = true;
			}

			if($this->isDisplayLimit($this->request["set_limit"]) === null) {
				echo '<div class="error fade"><p><strong>The display limit for sets must be a number.</strong></p></div>';
				$has_error = true;
			}

			if(! $has_error) {
				foreach($this->config as $key=>$value)
					if($this->request[$key])
						$this->optionSet($key, $this->request[$key]);

				$this->optionSaveAll();

				echo '<div class="updated fade"><p><strong>Settings successfully saved.</strong></p></div>';
			}

		// initial load
		} else {
			// set request variables equal to config to populate form initially
			foreach($this->config as $key=>$value)
				$this->request[$key] = $value;
		}
	}

	function getAdminHead() {
	?>
		<link href="/wp-content/plugins/flickr-tag/css/flickrTagAdmin.css" type="text/css" rel="stylesheet"/>
		<link href="/wp-content/plugins/flickr-tag/css/flickrTag.css" type="text/css" rel="stylesheet"/>
	<?php
	}

	function getAdminContent() {
		$this->processRequest();

		if($this->migrateNeeded())
			echo '<div class="updated fade"><p>To maximize compatability with Wordpress (and in response to user demand), this version of Flickr Tag changes the way the "flickr" tag works. Instead of "&lt;flickr&gt;", this version uses [flickr], with some other backward-compatable syntax changes. Some configuration options have also been renamed to avoid collision with future plugins that may also use Flickr.<p><strong>Would you like to <a href="/wp-admin/options-general.php?page=' . basename(__FILE__) . '&flickr_tag_migrate=true">migrate your posts and reset your settings</a> now?</strong> <em>It is recommended that you <a href="/wp-admin/export.php">backup your Wordpress database</a> before you do so.</em></p></div>';

	?>
		<div class="wrap">
			<form action="" method="post">
			<h2>Flickr Tag Plugin</h2>



			<p class="header">Authentication</p>
			<?php
				$current_user = $this->getCurrentUser();

				if($current_user) {
					echo 'Currently logged in to Flickr as <a href="http://www.flickr.com/people/' . $current_user['auth']['user']['nsid'] . '" target="_new">' . $current_user['auth']['user']['username'] . '</a> (<a href="/wp-admin/options-general.php?page=' . basename(__FILE__) . '&flickr_tag_logout=true">logout</a>)';
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
				Authorizing this plugin with Flickr is a simple, two step process:

				<p id="step1" class="current">
				<strong>Step 1:</strong> <a href="<?php echo $flickr_url; ?>" onClick="this.parentNode.className='disabled'; document.getElementById('step2').className='current';" target="_new">Authorize this application to access Flickr</a>. <em>This will open a new window. When you are finished, come back to this page.</em>
				</p>

				<p id="step2" class="disabled">
				<strong>Step 2:</strong> After authorizing this application with Flickr in the popup window, <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo basename(__FILE__); ?>&flickr_tag_frob=<?php echo $frob; ?>">click here to complete the authorization process</a>.
				</p>

			<?php
				} else
					echo $this->error("Error getting frob");
			}
			?>




			<p class="header">Inline Photo Display Options</p>

			<p class="subheader">(Single) Photos</p>
			<?php
				$this->getDisplayDefaultsOptionsHTML("photo");
			?>

			<p class="subheader">Sets</p>
			<?php
				$this->getDisplayDefaultsOptionsHTML("set");
			?>

			<p class="subheader">Tags</p>
			<?php
				$this->getDisplayDefaultsOptionsHTML("tag");
			?>




			<p class="header">Inline Photo Behavior Options</p>

			<p class="label" style="height: 50px;">When clicked, inline photos:</p>
			<p class="field">
				<input type="radio" name="flickr_tag_link_action" value="flickr" <?php if($this->request['link_action'] == "flickr") echo "checked"; ?>> link to the photo's Flickr page.
				<br/>
				<input type="radio" name="flickr_tag_link_action" value="lightbox" <?php if($this->request['link_action'] == "lightbox") echo "checked"; ?>> display a larger version in a <a href="http://www.huddletogether.com/projects/lightbox2/" target="_new">Lightbox</a>.
				<br/>
				<input type="radio" name="flickr_tag_link_action" value="none" <?php if($this->request['link_action'] != "flickr" && $this->request['link_action'] != "lightbox") echo "checked"; ?>> do nothing
			</p>

			<p class="more">
				This option controls what happens when a user clicks on an inline photo.
 
				<strong>Note that if Lightbox display mode is selected, tooltips on inline photos are disabled--tooltip content is shown as a caption in the Lightbox.</strong> Sorry, both cannot be enabled at the same time due to technical limitations.
			</p>




			<p class="header">Caching</p>

			<p class="label">Cache Lifetime:</p>
			<p class="field">
				<select size=1 name="flickr_tag_cache_ttl">
					<option value="86400" <?php if($this->request['cache_ttl'] == "86400") echo "selected"; ?>>1 day</option>
					<option value="259200" <?php if($this->request['cache_ttl'] == "259200") echo "selected"; ?>>3 days</option>
					<option value="604800" <?php if($this->request['cache_ttl'] == "604800") echo "selected"; ?>>1 week</option>
					<option value="1209600" <?php if($this->request['cache_ttl'] == "1209600") echo "selected"; ?>>2 weeks</option>
					<option value="2592000" <?php if($this->request['cache_ttl'] == "2592000") echo "selected"; ?>>1 month</option>
				</select>
			</p>

			<p class="more">
				To save bandwidth and minimize queries of Flickr, this plugin saves responses from Flickr for later use in a cache. The cache lifetime specifies how long the saved copy should be used before it is considered to be "expired".
			</p>

			<p class="label">Flush Cache:</p>
			<p class="field">
				<input type="submit" value="Flush Cache Now" name="flickr_tag_flush">
			</p>

			<p class="more">
				If you have made changes on Flickr, but are not seeing these changes reflected on your blog, you may need to flush the Flickr cache. This will happen automatically after the cache lifetime period expires (set above).
			</p>




			<p class="header"></p>

			<p>
			<input type="submit" name="flickr_tag_save" value="Save Changes">
			</p>

			</form>
		</div>
	<?php
	}

	function getUserContent() {
	?>
		<script type="text/javascript" src="/wp-content/plugins/flickr-tag/js/flickrTag.js"></script>

		<div style="padding: 10px; padding-left: 15px;">
			Choose a set from the list below to insert into your post:

			<p style="padding-left: 30px;">
			<?php
				$params = array(
					'method'	=> 'flickr.photosets.getList',
					'format'	=> 'php_serial'
				);

				$r = $this->apiCall($params, false, true);

				if($r) {
					echo '<select id="flickr_tag_sets">';

					foreach($r['photosets']['photoset'] as $number=>$photoset) 
						echo '<option value="' . $photoset['id'] . '">' . $photoset['title']['_content'] . ' (' . $photoset['photos'] . ' photo' . (($photoset['photos'] != 1) ? "s" : "") . ')</option>';
						
					echo '</select>';

					echo '<input class="button" type="button" value="Send to editor &raquo;" onClick="flickrTag_insertIntoEditor(\'[flickr]set:\' + document.getElementById(\'flickr_tag_sets\').value + \'[/flickr]\');">';
				} else { 
					echo "<em>No sets were found on Flickr. Did you <a href='options-general.php?page=FlickrTagAdmin.php' target='_top'>setup the plugin</a> yet?</em>";
				}
			?>
			</p>

			<p>
				Or, click on a thumbnail to insert a photo from your photostream into your post:
			</p>

			<p style="padding-left: 30px;">
			<?php
				$params = array(
                                        'method'        => 'flickr.people.getPublicPhotos',
                               	        'format'        => 'php_serial',
                                       	'per_page'      => '48',
                                        'user_id'       => $this->optionGet("nsid")
       	                        );

				$r = $this->apiCall($params, false, true);

				if($r) {
					foreach($r['photos']['photo'] as $number=>$photo) {
						$img_url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_s.jpg";
	
						echo '<a href="#" onClick="flickrTag_insertIntoEditor(\'[flickr]photo:' . $photo['id'] . '[/flickr]\'); return false;" style="text-decoration: none; border: none;"><img src="' . $img_url . '" alt="" style="padding-right: 5px; padding-bottom: 5px;"/></a>';
					}
				} else {
					echo "<em>No photos were found on Flickr. Did you <a href='options-general.php?page=FlickrTagAdmin.php' target='_top'>setup the plugin</a> yet?</em>";
				}	
			?>
			</p>

			<p>
				Or, include a set, tag or photo in your post by using the "flickr" tag in your post's text. The syntax is: 
			</p>

			<p style="font-family: courier; padding: 3px; background-color: #EFEFEF;">
				[flickr <em>[params]</em>]set:set id<em>[(size[,limit])]</em>[/flickr] or <br/>
				[flickr <em>[params]</em>]tag:tag1<em>[(,|+)tag2...][@username][(size[,limit])]</em>[/flickr] or <br/>
				[flickr <em>[params]</em>]photo:photo id<em>[(size[,limit])]</em>[/flickr] <br/>
			</p>

			<p>
				Any parameters (<em>[params]</em>) you add to the flickr tag (e.g. "style" or "alt") are added to the inserted image tag(s).<br/>
				If no mode is provided, "photo" is assumed (depricated). 
			<p>

			<p>
				<em>Examples of use:</em>
			</p>

			<p>
				To show the set with ID 72157602128216010, use:<br/>
			</p>

			<p style="font-family: courier; padding: 3px; background-color: #EFEFEF;">
				[flickr]set:72157602128216010[/flickr]
			</p>

			<p>
				To show "medium" photos tagged with "railcar" OR "train" from anyone, use:
			</p>

			<p style="font-family: courier; padding: 3px; background-color: #EFEFEF;">
				[flickr]tag:railcar,train(medium)[/flickr]
			</p>

			<p>
				To show a maximum of 20 "large" photos tagged with "railcar" AND "adm" from the user "anemergencystop", padding images with 10 pixels on all sides, use:
			</p>

			<p style="font-family: courier; padding: 3px; background-color: #EFEFEF;">
				[flickr style="padding: 10px;"]tag:railcar+adm@anemergencystop(large, 20)[/flickr]
			</p>
		</div>
	<?php
	}
}
