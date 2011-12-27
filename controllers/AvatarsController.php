<?php

namespace li3_avatar\controllers;

use li3_avatar\extensions\Avatar;

/**
 * A controller class for various avatar based actions.
 */
class AvatarsController extends \lithium\action\Controller {

	/**
	 * A controller class for various avatar based actions.
	 *
	 * @return json A response containing whether the search was successful and image data when a 
	 *                success.
	 */
	public function search(){
		if($image = Avatar::checkSources($this->request->query)) {
			return json_encode(array('response' => 'success', 'image' => $image));
		}
		return json_encode(array('response' => 'fail'));
	}
}

?>