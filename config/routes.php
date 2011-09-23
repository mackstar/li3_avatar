<?php

use lithium\net\http\Router;
use lithium\action\Response;
use li3_avatar\models\Avatars;

Router::connect('/avatar/{:id:[0-9a-f]{24}}.jpg', array(), function($request) {
	return new Response(array(
		'headers' => array('Content-type' => 'image/jpeg'),
		'body' => Avatars::first($request->id)->file->getBytes()
	));
});


