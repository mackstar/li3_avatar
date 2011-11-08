<?php

namespace li3_avatar\extensions;

use li3_avatar\models\Avatars;
use lithium\net\socket\Curl;

class Avatar extends \lithium\core\StaticObject {

	protected static $_record = null;

	protected static $_options = array();

	public static function __init() {
		$default = array(
			'size' => 72
		);
		static::$_options = $default;
	}

	public static function find($record, $service=null) {
		static::$_record = $record->data();
		if ($service){
			$method = 'get' . $service;
			return self::$method();
		}
		$result = false;
		$order = static::order();
		for($i = 0; !$result && isset($order[$i]); $i++) {
			$method = 'get' . $order[$i];
			$result = self::$method();
		}
		return $result;
	}
	
	public static function order() {
		return array('Gravatar', 'Twitter', 'Facebook');
	}

	public static function getTwitter(){
		if (isset(static::$_record['services']['twitter'])) {
			$xml = @simplexml_load_file(
				'http://twitter.com/users/'.static::$_record['services']['twitter'].'.xml'
			);
			if ($xml) {
				return str_replace(array('normal'), array('bigger'), $xml->profile_image_url);
			}
		}
		return null;
	}

	public static function getFacebook(){
		if (isset(static::$_record['services']['facebook']) 
			&& static::$_record['services']['facebook'] != '') {
			$return  = 'http://graph.facebook.com/';
			$return .= static::$_record['services']['facebook'].'/picture?type=large';
			return $return;
		}
		return false;
	}

	public static function getGravatar(){
		if (isset(static::$_record['email'])) {
			$image  = 'http://www.gravatar.com/avatar/' . md5(static::$_record['email']) . '?d=404&s=';
			$image .= static::$_options['size'] ?:72;
			return $image;
		}
		return false;
	}

	public static function getAvatar(){

		if (static::$_record['avatar']) {
			return '/avatar/' . static::$_record['avatar'] . 'jpg';
		}
		return false;
	}

	public static function grab($record) {
		if ($record) {
			static::$_record = $record->data();
		}
		if (isset(static::$_record['avatar'])) {
			return Avatars::find(static::$_record['avatar'])->file->getBytes();
		}

		if ($result = static::loopPossiblities()) {
			$avatar = Avatars::saveFromService($result);
			$record->avatar = (string) $avatar->_id;
			return $avatar->file;
		}
		return false;
	}

	public static function loopPossiblities() {
		foreach (static::order() as $source) {
			$method = 'get' . $source;
			if ($result = static::grabSource(static::$method())) {
				return $result;
			}
		}
		return false;
	}

	public static function grabSource($url){
		if (!$url) {
			return false;
		}
		return @file_get_contents($url);
	}

	public static function checkSources($params){
		static::$_record = $params;
		foreach (static::order() as $source) {
			$method = 'get' . $source;
			if ($result = static::grabSource(static::$method())) {
				$json = json_decode($result);
				if($json && is_object($json)){
					return false;
				}
				return static::$method();
			}
		}
		return false;
	}
}

?>