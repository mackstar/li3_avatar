<?php

namespace li3_avatar\models;

use li3_avatar\extensions\ImageException;

/**
 * The `Avatars` model class is where the resizing and saving of data occurs. The data store uses 
 * MongoDb's Grid FS storage system to save avatar data. When images are found and processed they 
 * are done so with GD. This would be ideally changed to use the Imagine library in the near future.
 */
class Avatars extends \lithium\data\Model {

	/**
	 * Meta data as per Lithium model standards.
	 *
	 * @var array
	 */
	protected $_meta = array('source' => 'fs.files', 'key' => '_id');

	/**
	 * Types of possible file types
	 *
	 * @var array
	 */
	protected static $_types = array(
		'jpg' => 'jpeg', 
		'jpeg' => 'jpeg',
		'png' => 'png',
		'gif' => 'gif',
	);

	/**
	 * Triggers the resize after creating the image resource from data type as prescribed by GD.
	 *
	 * This is actually called once having been uploaded from a form. Using the data params to deal 		
	 * with it. This has a preset image size of 72 pixels.
	 *
	 * @param string $image File parameter from form upload.
	 * @throws ImageException when unable to create image data.
	 * @return object Avatar document object after having been saved in the DB.
	 *         this will be the result from the processResize() method.
	 */
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

	/**
	 * Saves the avatar from image collected from a service.
	 *
	 * @param string $image Byte data of image collected from a service
	 * @return void
	 */
	public static function saveFromService($image) {
		try {
			$image = imagecreatefromstring($image);

			return self::processResize($image, 72);
		} catch (Exception $e) {
			throw new ImageException('Can\'t resize photo');
		}
		return false;
	}

	/**
	 * Process the resizing procedure based on desired size.
	 *
	 * This then goes on to save the data as bytes in the database using Grid FS.
	 *
	 * @param resource $image Image resource data.
	 * @param int $desiredSize Desired image size. This will be squared though.
	 * @throws ImageException when unable to save image data.
	 * @return object The avatar object that has been saved in the database.
	 */
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

	/**
	 * Saves the avatar from an uploaded image, from a form.
	 *
	 * @param array $params Form parameters as a resource.
	 * @return void
	 */
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