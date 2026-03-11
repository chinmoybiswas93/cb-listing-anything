<?php

use CBListingAnything\Rest\SearchController;
use CBListingAnything\Rest\TermController;
use CrocoDevs\Http\Router\Router;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Define REST API routes for the plugin. Each route is registered under
| the namespace configured in config/app.php → api_prefix.
|
*/

Router::get( '/search', array( SearchController::class, 'search_listings' ), array(
	'keyword'  => array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	),
	'category' => array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 0,
	),
) );

Router::get( '/categories', array( TermController::class, 'get_categories' ) );
