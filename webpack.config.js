/**
 * Extends @wordpress/scripts to compile blocks + admin app in one webpack process.
 * Avoids two `wp-scripts start` watchers (fixes EMFILE / "too many open files" on macOS).
 *
 * @wordpress/scripts uses entry: () => ({ ... }) — we must wrap that function, not spread it.
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
