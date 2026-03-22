<?php

use CrocoDevs\Http\Router\Router;

/*
|--------------------------------------------------------------------------
| API Routes (CrocoDevs Router)
|--------------------------------------------------------------------------
|
| Public listing search and category routes are registered in
| SearchController::register_routes() and TermController::register_routes()
| (see Plugin::run() on rest_api_init) so callbacks receive WP_REST_Request.
|
| Registering the same paths here caused duplicate merged routes; WordPress
| matched the Router wrapper first and passed Request instead of
| WP_REST_Request, triggering a fatal TypeError on the search endpoint.
|
*/

// Intentionally empty — add Router::get() routes only when handlers accept
// CrocoDevs\Http\Request or pass $request->wpRequest() to typed methods.
