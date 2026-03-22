#!/usr/bin/env node
/**
 * Run block + admin webpack watch in one process (cross-platform; no concurrently).
 */
'use strict';

const { spawn } = require( 'child_process' );
const path = require( 'path' );

const root = path.resolve( __dirname, '..' );
const wpScripts = path.join(
	root,
	'node_modules',
	'@wordpress',
	'scripts',
	'bin',
	'wp-scripts.js'
);
const node = process.execPath;

function launch( label, wpArgs ) {
	const child = spawn( node, [ wpScripts, ...wpArgs ], {
		cwd: root,
		stdio: 'inherit',
		env: {
			...process.env,
			FORCE_COLOR: process.env.FORCE_COLOR || '1',
		},
	} );

	child.on( 'exit', ( code, signal ) => {
		if ( signal ) {
			process.stderr.write( `[${ label }] stopped (${ signal })\n` );
		} else if ( code !== 0 && code !== null ) {
			process.stderr.write( `[${ label }] exited with code ${ code }\n` );
			if ( process.exitCode === undefined || process.exitCode === 0 ) {
				process.exitCode = code;
			}
		}
	} );

	return child;
}

const children = [
	launch( 'blocks', [
		'start',
		'--webpack-src-dir=src/blocks',
		'--output-path=build',
	] ),
	launch( 'admin', [
		'start',
		'src/admin/index.js',
		'--output-path=build/admin',
	] ),
];

function shutdown() {
	children.forEach( ( c ) => {
		if ( c && ! c.killed ) {
			c.kill( 'SIGINT' );
		}
	} );
}

process.on( 'SIGINT', shutdown );
process.on( 'SIGTERM', shutdown );
