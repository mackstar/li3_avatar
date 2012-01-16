<?php

namespace li3_avatar\extensions;

use li3_avatar\models\Avatars;
use lithium\net\socket\Curl;
use lithium\core\Environment;

/**
 * A service class to process avatar availability for various services.
 * At the moment these services are Gravatar, Facebook and Twitter
 */
class Avatar extends \lithium\core\StaticObject {

	/**
	 * The entity document exported from $entity->data();
	 *
	 * @var array
	 */ 
	protected static $_record = null;

	/**
	 * Options stored for use in this class
	 *
	 * @var array
	 */
	protected static $_options = array();

	/**
	 * The order of which to search services for avatar availability.
	 *
	 * @param array $config Configuration options.
	 * @return array Full set of options.
	 */
	public static function config($options = false) {
		if (!$options) {
			return static::$_options;
		}
		if (is_array($options)) {
			static::$_options = $options + static::$_options;
			return true;
		}
		return static::$_options[$options];
	}

	/**
	 * Class initializer - sets initial default options.
	 *
	 * @return void
	 */
	public static function __init() {
		$default = array(
			'size' => 72,
			'default_image' => dirname(__DIR__).'/webroot/img/icon.png',
		);
		static::$_options = $default;
	}

	/**
	 * Calls a method to get the data from a particular service when set.
	 * if not set searches through services on order.
	 *
	 * @param object $record The current lithium document object.
	 * @param string $service The type of service to search for an avatar on.
	 * @return mixed getAvatar style of result. Either the image data or false.
	 */
	public static function find($record, $service = null) {
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

	/**
	 * The order of which to search services for avatar availability.
	 *
	 * @return array order to search in.
	 */
	public static function order() {
		return array('Gravatar', 'Twitter', 'Facebook');
	}

	/**
	 * Gets Twitter avatar from services->facebook property in the record where available.
	 *
	 * @return mixed Either the url of the service image or false.
	 */
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

	/**
	 * Gets Facebook avatar from services->facebook property in the record where available.
	 *
	 * @return mixed Either the url of the service image or false.
	 */
	public static function getFacebook(){
		if (isset(static::$_record['services']['facebook']) 
			&& static::$_record['services']['facebook'] != '') {
			$return  = 'http://graph.facebook.com/';
			$return .= static::$_record['services']['facebook'].'/picture?type=large';
			return $return;
		}
		return false;
	}

	/**
	 * Gets Gravatar from email adress stored in the record where available.
	 *
	 * @return mixed Either the url of the service image or false.
	 */
	public static function getGravatar(){
		if (isset(static::$_record['email'])) {
			$image  = 'http://www.gravatar.com/avatar/' . md5(static::$_record['email']) . '?d=404&s=';
			$image .= static::$_options['size'] ?:72;
			return $image;
		}
		return false;
	}

	/**
	 * Gets the avatar url when stored in the database record.
	 *
	 * @return array Either the file data or false.
	 */
	public static function getAvatar(){

		if (static::$_record['avatar_id']) {
			return '/avatar/' . static::$_record['avatar_id'] . 'jpg';
		}
		return false;
	}

	/**
	 * Grabs the avatar from the record where available. Returns false where not available.
	 * 
	 * This also returns false if it has been checked within the last 24 hrs.
	 *
	 * @param object $record A lithium model.
	 * @return mixed Either the file data or false.
	 */
	public static function grab($record, $skipCache = false) {
		if (Environment::get() == 'test') {
			return false;
		}
		if ($record) {
			$regex = '/\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}/';
			if ($record->avatar_id && preg_match($regex, $record->avatar_id) && !$skipCache) {
				$checked = strtotime($record->avatar_id);
				$now = strtotime(date('Y-m-d H:i:s'));
				if ($now - $checked < (60 * 60 * 24)) {
					return false;
				}
			}
			static::$_record = $record->data();
		}

		if ($record->hasAvatar()) {
			if($avatar = Avatars::find(static::$_record['avatar_id'])) {
				return $avatar->file->getBytes();
			}
		}

		if ($result = static::loopPossiblities()) {
			$avatar = Avatars::saveFromService($result);
			$record->avatar_id = (string) $avatar->_id;
			$record->save(null, array('validate' => false));
			return $avatar->file;
		}
		$record->avatar_id = date('Y-m-d H:i:s');
		$record->save(null, array('validate' => false));
		return false;
	}

	/**
	 * Loops the possibilities to find a match for various avatar services.
	 *
	 * @return mixed Either the file data or false.
	 */
	public static function loopPossiblities() {
		foreach (static::order() as $source) {
			$method = 'get' . $source;
			if ($result = static::grabSource(static::$method())) {
				return $result;
			}
		}
		return false;
	}

	/**
	 * Grabs the contents of file from url when available.
	 *
	 * @param string $url The url of the image.
	 * @return mixed Either the file content data or false.
	 */
	public static function grabSource($url){
		if (!$url) {
			return false;
		}
		return @file_get_contents($url);
	}

	/**
	 * Searches for an images availability of an avatar based on form parameters.
	 * This does actually check the avatar itself exists by using grabSource.
	 *
	 * @param array $params The parameters containing
	 *              - `email`
	 *              - `services[facebook]`
	 *              - `services[twitter]`
	 * @return mixed Either the url for image or false when not available.
	 */
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