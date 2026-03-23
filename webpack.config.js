/**
 * Single webpack config for blocks + admin app.
 *
 * @wordpress/scripts discovers entries from src/blocks (via --webpack-src-dir) and
 * copies block render PHP per block.json. This file adds the admin SPA entry so
 * one `wp-scripts build` / `wp-scripts start` compiles everything — no second
 * process, no manual file copies, and fewer open file watchers (macOS EMFILE).
 *
 * @wordpress/scripts uses entry: () => ({ ... }) — wrap that function, do not spread it.
 */
const path = require( 'path' );
const wpConfig = require( '@wordpress/scripts/config/webpack.config' );

const adminEntry = path.resolve( __dirname, 'src/admin/index.js' );

/**
 * @param {import('webpack').Configuration} config
 * @return {import('webpack').Configuration | import('webpack').Configuration[]}
 */
function addAdminEntry( config ) {
	if ( Array.isArray( config ) ) {
		return config.map( ( c ) => addAdminEntry( c ) );
	}

	const { entry: originalEntry } = config;

	if ( typeof originalEntry === 'function' ) {
		return {
			...config,
			entry: () => {
				const base = originalEntry();
				return {
					...base,
					'admin/index': adminEntry,
				};
			},
		};
	}

	if (
		typeof originalEntry === 'object' &&
		originalEntry !== null &&
		! Array.isArray( originalEntry )
	) {
		return {
			...config,
			entry: {
				...originalEntry,
				'admin/index': adminEntry,
			},
		};
	}

	return config;
}

module.exports = addAdminEntry( wpConfig );
