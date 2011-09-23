<?php

namespace li3_avatar\controllers;

use li3_avatar\extensions\Avatar;
use app\models\Users;

class AvatarsController extends \lithium\action\Controller {
	
	
	public function user() {
		$user = Users::find($this->request->id);
		$result = Avatar::grab($user);
		header('Content-Type: image/png');
		if ($result) {
			echo $result;
		}
		echo file_get_contents(dirname(__DIR__).'/webroot/img/icon.png');
		
		exit;
	}
	
	
}

?>