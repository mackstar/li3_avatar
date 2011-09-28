<?php

use lithium\net\http\Router;
use lithium\action\Response;
use li3_avatar\models\Avatars;
use li3_avatar\extensions\Avatar;
use lithium\util\Inflector;

Router::connect('/avatars/{:model}/{:id:[0-9a-f]{24}}.png', array(), function($request) {
	$class = 'app\models\\' . Inflector::camelize($request->model);
	$record = $class::find($request->id);
	$result = Avatar::grab($record);
	return new Response(array(
		'headers' => array('Content-type' => 'image/png'),
		'body' => $result ?: file_get_contents(dirname(__DIR__).'/webroot/img/icon.png')
	));
});


Router::connect('/avatar/{:id:[0-9a-f]{24}}.jpg', array(), function($request) {
	return new Response(array(
		'headers' => array('Content-type' => 'image/jpeg'),
		'body' => Avatars::first($request->id)->file->getBytes()
	));
});

Router::connect('/avatars/search', array('Avatars::search'));


