<?php

return array(
	'name'        => 'CB Listing Anything',
	'text_domain' => 'cb-listing-anything',
	'api_prefix'  => 'cb-listing-anything/v1',
	'use_router'  => true,
	'providers'   => array(
		'CBListingAnything\\Providers\\ListingServiceProvider',
	),
);
