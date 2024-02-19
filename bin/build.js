#!/usr/bin/env node
/* eslint-disable no-console */
/**
 * External dependencies.
 */
const chalk = require( 'chalk' );
const fs = require( 'fs' ); // eslint-disable-line id-length
const { sync: glob } = require( 'fast-glob' );
const { hashElement } = require( 'folder-hash' );
const path = require( 'path' );
const postcss = require( 'postcss' );
const rtlcss = require( 'rtlcss' );
const { sync: resolveBin } = require( 'resolve-bin' );
const { sync: spawn } = require( 'cross-spawn' );
const postCssConfig = require( '../postcss.config.js' );

/**
 * Build the files, if the `src` directory exists.
 *
 * This builds JS and any SCSS, using wp-scripts. We can't use wp-scripts
 * directly due to the folder structure of the blocks, but can pass through
 * the folders with CLI args.
 *
 * @param {string} inputDir
 * @param {string} outputDir
 */
async function maybeBuildBlock( inputDir, outputDir ) {
	const project = path.basename( path.dirname( inputDir ) );
	if ( fs.existsSync( inputDir ) ) {
		// Run wp-scripts with a specific input and output directory.
		const { status, output } = spawn(
			resolveBin( '@wordpress/scripts', { executable: 'wp-scripts' } ),
			[
				'build',
				'--experimental-modules',
				'--webpack-src-dir=' + path.relative( path.dirname( __dirname ), inputDir ),
				'--output-path=' + outputDir,
				'--color', // Enables colors in `stdout`.
			],
			{
				stdio: 'pipe',
			}
		);
		// Only output the webpack result if there was an issue.
		if ( 0 !== status ) {
			console.log( output.toString() );
			console.log( chalk.red( `Error in block for ${ project }` ) );
		} else {
			console.log( chalk.green( `Block built for ${ project }` ) );
		}
	}
}

/**
 * Build the CSS files using PostCSS, if the `postcss` directory exists.
 *
 * @param {string} inputDir
 * @param {string} outputDir
 */
async function maybeBuildPostCSS( inputDir, outputDir ) {
	const project = path.basename( path.dirname( inputDir ) );
	if ( fs.existsSync( inputDir ) ) {
		if ( ! fs.existsSync( outputDir ) ) {
			fs.mkdirSync( outputDir );
		}

		const pcssRe = /^[^_].*\.pcss$/i;
		const files = fs.readdirSync( inputDir ).filter( ( name ) => pcssRe.test( name ) );

		for ( let i = 0; i < files.length; i++ ) {
			const inputFile = path.resolve( inputDir, files[ i ] );
			const outputFile = path.resolve( outputDir, files[ i ].replace( '.pcss', '.css' ) );
			const css = fs.readFileSync( inputFile );

			const result = await postcss( postCssConfig.plugins ).process( css, { from: inputFile } );
			result.warnings().forEach( ( warn ) => {
				console.log( chalk.yellow( `Warning in ${ project }:` ), warn.toString() );
			} );
			fs.writeFileSync( outputFile, result.css );

			const rtlResult = await postcss( [ ...postCssConfig.plugins, rtlcss ] ).process( css, {
				from: inputFile,
			} );
			rtlResult.warnings().forEach( ( warn ) => {
				console.log( chalk.yellow( `Warning in ${ project }:` ), warn.toString() );
			} );
			fs.writeFileSync( outputFile.replace( '.css', '-rtl.css' ), rtlResult.css );
		}
		console.log( chalk.green( `CSS built for ${ project }` ) );
	}
}

/**
 * Update the block.json version field with the hash of the build.
 *
 * @param {string} basePath
 */
async function setBlockVersion( basePath ) {
	const project = path.basename( basePath );

	const files = glob( '**/block.json', {
		absolute: true,
		cwd: basePath,
	} );

	if ( ! files.length ) {
		console.log( chalk.red( `Couldn't find block.json for ${ project }` ) );
		return;
	}

	const options = {
		algo: 'sha1',
		encoding: 'hex',
	};

	const hash = await hashElement( basePath, options );

	files.forEach( ( blockJson ) => {
		const blockJsonContents = require( blockJson );
		blockJsonContents.version = blockJsonContents.version?.replace( /(^|-)[0-9a-f]{40}$/, '' ) || '';
		blockJsonContents.version += ( blockJsonContents.version ? '-' : '' ) + hash.hash;

		fs.writeFileSync( blockJson, JSON.stringify( blockJsonContents, null, '\t' ) );
	} );

	console.log( chalk.green( `block.json version set for ${ project } to ${ hash.hash }` ) );
}

// If we have more paths that need building, we could switch this to an array.
const projectPath = path.join( path.dirname( __dirname ), 'mu-plugins/blocks' );
const cliProjects = process.argv.slice( 2 );
const projects = cliProjects.length
	? cliProjects
	: fs
			.readdirSync( projectPath )
			.filter( ( file ) => fs.statSync( path.join( projectPath, file ) ).isDirectory() );

/**
 * Build the files.
 * For each subfolder in `mu-plugins/blocks`…
 * 1. If there is a `src` folder, run the JS build.
 * 2. If there is a `postcss` folder, run the CSS build.
 *      Will build any top-level Sass files (unless they start with `_`).
 */
projects.forEach( async ( file ) => {
	const basePath = path.join( projectPath, file );

	try {
		const outputDir = path.resolve( path.join( basePath, 'build' ) );

		// We `await` because JS needs to be built first— the first webpack step deletes the build
		// directory, and could remove the built CSS if it was truely async.
		await maybeBuildBlock( path.resolve( path.join( basePath, 'src' ) ), outputDir );

		await maybeBuildPostCSS( path.resolve( path.join( basePath, 'postcss' ) ), outputDir );

		await setBlockVersion( basePath );
	} catch ( error ) {
		console.log( chalk.red( `Error in ${ file }:` ), error.message );
	}
} );
