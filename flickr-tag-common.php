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

define("FLICKR_TAG_API_KEY", "ab3d8caa418c7e03aeda35edb756d223");
define("FLICKR_TAG_API_KEY_SS", "2406fd6c36b852fd");

flickr_load_config();

// compatability stuff
if(! function_exists("file_put_contents")) {
	function file_put_contents($file, $contents, $flag) {
		$r = fopen($file, "w+");
		fwrite($r, $contents);
		fclose($r);
	}
}

function flickr_get_option($key, $default = null) {
	$v = get_option($key);

	if($v == null)
		return $default;
	else
		return $v;
}

function flickr_load_config() {
	GLOBAL $flickr_config;

	$flickr_config['token'] = flickr_get_option("flickr_token");
	$flickr_config['cache_ttl'] = flickr_get_option("flickr_cache_ttl", 604800);
	$flickr_config['cache_dir'] = dirname(__FILE__) . "/cache";

	$flickr_config['photo_size'] = flickr_get_option("flickr_photo_size", "_m");
	$flickr_config['photo_tooltip'] = flickr_get_option("flickr_photo_tooltip", "description");

	$flickr_config['set_size'] = flickr_get_option("flickr_set_size", "_s");
	$flickr_config['set_tooltip'] = flickr_get_option("flickr_set_tooltip", "description");
	$flickr_config['set_limit'] = flickr_get_option("flickr_set_limit", "50");

	$flickr_config['tag_size'] = flickr_get_option("flickr_tag_size", "_s");
	$flickr_config['tag_tooltip'] = flickr_get_option("flickr_tag_tooltip", "description");
	$flickr_config['tag_limit'] = flickr_get_option("flickr_tag_limit", "50");
}

function flickr_api_call($params, $cache = true, $sign = true) {
	GLOBAL $flickr_config;

	$params['api_key'] = FLICKR_TAG_API_KEY;
	if($flickr_config['token']) $params['auth_token'] = $flickr_config['token'];

	ksort($params);

	$cache_key = md5(join($params, " "));

	$signature_raw = "";
	$encoded_params = array();
	foreach($params as $k=>$v) {
		$encoded_params[] = urlencode($k) . '=' . urlencode($v);

		if($sign)
			$signature_raw .= $k . $v;
	}

	if($sign) 
		array_push($encoded_params, 'api_sig=' . md5(FLICKR_TAG_API_KEY_SS . $signature_raw));

	if($cache && file_exists($flickr_config['cache_dir'] . "/" . $cache_key . ".cache") && (time() - filemtime($flickr_config['cache_dir'] . "/" . $cache_key . ".cache")) < $flickr_config['cache_ttl'])
		$o = unserialize(file_get_contents($flickr_config['cache_dir'] . "/" . $cache_key . ".cache"));
	else {
		@$c = curl_init();

		if($c) {
			curl_setopt($c, CURLOPT_URL, "http://api.flickr.com/services/rest/");
			curl_setopt($c, CURLOPT_POST, 1);
			curl_setopt($c, CURLOPT_POSTFIELDS, implode('&', $encoded_params));
			curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 10);

			$r = curl_exec($c);
		} else	// no curl available... 
			$r = file_get_contents("http://api.flickr.com/services/rest/?" . implode('&', $encoded_params));

		if(! $r)
			return null;

		$o = unserialize($r);

		if($o['stat'] != "ok")
			return null;

		// save serialized response to cache
		if($cache) {
			if(! is_dir($flickr_config['cache_dir']))
				mkdir($flickr_config['cache_dir']);

			file_put_contents($flickr_config['cache_dir'] . "/" . $cache_key . ".cache", $r, LOCK_EX);
		}
	}

	return $o;
}
?>
