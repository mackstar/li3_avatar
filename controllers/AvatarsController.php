<?php

namespace li3_avatar\controllers;

use li3_avatar\extensions\Avatar;

class AvatarsController extends \lithium\action\Controller {

	public function search(){
		if($image = Avatar::checkSources($this->request->query)) {
			return json_encode(array('response' => 'success', 'image' => $image));
		}
		return json_encode(array('response' => 'fail'));
	}

}

?>