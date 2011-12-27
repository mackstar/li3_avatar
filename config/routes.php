<?php

use lithium\net\http\Router;
use lithium\action\Response;
use li3_avatar\models\Avatars;
use li3_avatar\extensions\Avatar;
use lithium\util\Inflector;

/**
 * Route for image defined by record type and id. Image will return a default image if an avatar
 * has not been found.
 */
Router::connect('/avatars/{:model}/{:id}.png', array(), function($request) {
	$class = 'app\models\\' . Inflector::camelize($request->model);
	$record = $class::find($request->id);
	$result = Avatar::grab($record);
	return new Response(array(
		'headers' => array('Content-type' => 'image/png'),
		'body' => $result ?: file_get_contents(Avatar::config('default_image'))
	));
});

/**
 * Route for image defined by avatar ID.
 */
Router::connect('/avatar/{:id:[0-9a-f]{24}}.jpg', array(), function($request) {
	return new Response(array(
		'headers' => array('Content-type' => 'image/jpeg'),
		'body' => Avatars::first($request->id)->file->getBytes()
	));
});

/**
 * Route for search which directly forwards to `Avatars::search` - A controller method which is part 
 * of this plugin.
 */
Router::connect('/avatar/{:date:\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}}.jpg', array(), function($request) {
	return new Response(array(
		'headers' => array('Content-type' => 'image/jpeg'),
		'body' => file_get_contents(Avatar::config('default_image'))
	));
});

/**
 * Route for search which directly forwards to `Avatars::search` - A controller method which is part 
 * of this plugin.
 */
Router::connect('/avatars/search', array('Avatars::search'));
