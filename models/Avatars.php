<?php

namespace li3_avatar\models;

use li3_avatar\extensions\ImageException;

class Avatars extends \lithium\data\Model {

	protected $_meta = array('source' => 'fs.files', 'key' => '_id');

	protected static $_types = array(
		'jpg' => 'jpeg', 
		'jpeg' => 'jpeg',
		'png' => 'png',
		'gif' => 'gif',
	);

	public static function resize($image) {

		try {
			$imageName  = explode('.', $image['name']);
			$type = $imageName[(count($imageName)-1)];
			$method = 'imagecreatefrom' . static::$_types[strtolower($type)];
			$image = $method($image['tmp_name']);
			return self::processResize($image, 72);
		} catch (Exception $e) {
			throw new ImageException('Can\'t resize photo');
		}	

	}

	public static function saveFromService($image) {
		try {
			$image = imagecreatefromstring($image);

			return self::processResize($image, 72);
		} catch (Exception $e) {
			throw new ImageException('Can\'t resize photo');
		}
		return false;
	}

	public static function processResize($image, $desiredSize) {
		$x = imagesx($image);
		$y = imagesy($image);

		$pixels = ($y < $x)? $y : $x;

		$startY = (($y/2) - ($pixels/2));
		$large = imagecreatetruecolor($pixels, $pixels);
		imagecopy($large, $image, 0, 0, 0, $startY, $pixels, $pixels);

		$small = imagecreatetruecolor($desiredSize, $desiredSize);
		imagecopyresampled($small, $large, 0, 0, 0, 0, $desiredSize, $desiredSize, $pixels, $pixels);

		ob_start();
		imagepng($small);
		$bytes = ob_get_contents();
		ob_end_clean();
		$avatar = self::create(array('file' => $bytes));
		if ($avatar->save()) {
			return $avatar;
		}
		throw new ImageException('Can\'t save bytes.');
	}

	public static function saveFromForm(&$params) {
		if ($params['data'] != null) {
			if (!empty($params['data']['avatar']['name'])) {
				$avatar = Avatars::resize($params['data']['avatar']);
				$params['data']['avatar_id'] = (string) $avatar->_id;
				unset($params['data']['avatar']);
			}
		}
	}

}

?>