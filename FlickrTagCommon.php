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

define("FLICKR_TAG_API_KEY", "3527841ff32cd1dc7a1d7dbe710ee882");
define("FLICKR_TAG_API_KEY_SS", "e2d9cfb5fdb717b2");

define("FLICKR_TAG_CACHE_DIR", dirname(__FILE__) . "/cache");

class FlickrTagCommon {
	var $error_state = null;
	
	var $config = array(
				"token" => null,
				"nsid" => null,

				"cache_ttl" => 604800,

				"link_action" => "flickr",

				"photo_size" => "small",
				"photo_tooltip" => "description",

				"set_size" => "square",
				"set_tooltip" => "description",
				"set_limit" => 50,

				"photostream_size" => "square",
				"photostream_tooltip" => "description",
				"photostream_limit" => 50,

				"group_size" => "square",
				"group_tooltip" => "description",
				"group_limit" => 50,

				"tag_size" => "square",
				"tag_tooltip" => "description",
				"tag_limit" => 50
			);

	var $size = array(
				"square" => "_s",
				"thumbnail" => "_t",
				"small" => "_m",
				"medium" => "",
				"large" => "_b",
				"original" => "_o"
			);

	function FlickrTagCommon() {
		$this->optionLoadAll();
	}

	function optionGet($key) {
		return $this->config[$key];
	}

	function optionSet($key, $value) {
		$this->config[$key] = $value;
	}

	function optionSaveAll() {
		foreach($this->config as $key=>$value)
			update_option("flickr_tag_" . $key, $value);
	}

	function optionLoadAll() {
		foreach($this->config as $key=>$default) {
			$v = get_option("flickr_tag_" . $key);

			if($v)
				$this->config[$key] = $v;
		}
	}

	function isDisplayLimit($value) {
		if(ctype_digit($value) && $value <= 100)
			return $value;
		else
			return null;
	}

	function isPhotoSize($value) {
		foreach($this->size as $k=>$v) {
			if($value == $k)
				return $v;
		}

		return null;
	}

	function cacheFlush() {
	        $d = @opendir(FLICKR_TAG_CACHE_DIR);

		if(! $d)
			return 0;

	        $f = 0;
        	while(($file = readdir($d)) !== false) {
                	$p = FLICKR_TAG_CACHE_DIR . "/" . $file;

	                if(! is_file($p))
        	                continue;

	                if(is_file($p) && substr($p, strrpos($p, ".") + 1) == "cache")
        	                if(@unlink($p))
                	                $f++;
	        }

	        closedir($d);

	        return $f;
	}

	function apiCall($params, $cache = true, $sign = true) {
		// canonicalize parameters
		$params['api_key'] = FLICKR_TAG_API_KEY;

		if($this->optionGet('token') && $sign) 
			$params['auth_token'] = $this->optionGet('token');

		ksort($params);

		// construct signature and encode parameters
		$signature_raw = "";
		$encoded_params = array();
		foreach($params as $k=>$v) {
			$encoded_params[] = urlencode($k) . '=' . urlencode($v);

			if($sign)
				$signature_raw .= $k . $v;
		}

		if($sign) 
			array_push($encoded_params, 'api_sig=' . md5(FLICKR_TAG_API_KEY_SS . $signature_raw));

		// check cache
		$cache_key = md5(join($params, " "));
		$cache_file = FLICKR_TAG_CACHE_DIR . "/" . $cache_key . ".cache";

		if($cache && file_exists($cache_file) && ((time() - filemtime($cache_file)) < $this->optionGet('cache_ttl') || $this->optionGet('cache_ttl') == -1)) {
			$o = unserialize(file_get_contents($cache_file));

		// cache miss: make request
		} else {
			$c = null;

			if(function_exists('curl_init'))
				$c = curl_init();

			if($c) {
				curl_setopt($c, CURLOPT_URL, "http://api.flickr.com/services/rest/");
				curl_setopt($c, CURLOPT_POST, 1);
				curl_setopt($c, CURLOPT_POSTFIELDS, implode('&', $encoded_params));
				curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 10);

				$r = curl_exec($c);

			} else	// no curl available... ugh!
				@$r = file_get_contents("http://api.flickr.com/services/rest/?" . implode('&', $encoded_params));

			if(! $r) {
				if(function_exists("error_get_last"))
					$this->error_state = error_get_last();
				else
					$this->error_state = array("message" => "libcurl or URL fopen() wrappers were not found!");

				return null;
			}

		 	$o = unserialize($r);

			if($o['stat'] != "ok") {
				$this->error_state = $o;
				
				return null;
			}

			// save serialized response to cache
			if($cache) {
				if(! is_dir(FLICKR_TAG_CACHE_DIR)) {
					@mkdir(FLICKR_TAG_CACHE_DIR);
					@chmod(FLICKR_TAG_CACHE_DIR, 0755);
				}

				if(file_put_contents($cache_file, $r, LOCK_EX) === FALSE) {
					$this->error_state = array("message" => "Writing to " . $cache_file . " failed. Make sure the cache directory is writable by the webserver.");
	
					return null;
				}
			}
		}

		return $o;
	}

	function error($message) {
	        $s .= '<div class="flickrTag_error">';

		$s .= '<p>Flickr Tag Error: ' . $message . '</p>';

		if($this->error_state) {
			$s .= "<p>Error state follows:</p><ul>";

			foreach($this->error_state as $l=>$m)
				$s .= "<li>" . $l . ": " . $m . "</li>";

			$s .= "</ul>";
		}

		$s .= '</div>';

		return $s;
	}
}
