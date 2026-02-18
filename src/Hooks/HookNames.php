<?php

namespace CBListingAnything\Hooks;

/**
 * Constants for custom hook names (do_action / apply_filters).
 * Use these when adding or documenting plugin hooks.
 */
final class HookNames {

	/** Example: before block registration. */
	public const BEFORE_REGISTER_BLOCKS = 'cb_listing_anything_before_register_blocks';

	/** Example: after a listing is saved. */
	public const LISTING_SAVED = 'cb_listing_anything_listing_saved';
}
