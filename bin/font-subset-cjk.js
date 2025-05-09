#!/usr/bin/env node
/* eslint-disable no-console */
/**
 * Prerequisite:
 * 1. `yarn` to make sure you have the current packages.
 * Usage:
 * 1. Add {"type": "module"} to package.json.
 * 2. Add the `.ttf` font to /fonts/.
 * 3. Update the fontNames value.
 * 4. Run 'yarn font-subset-cjk'.
 * 5. Remove {"type": "module"} from package.json or the linter would prompt an error.
 */
import { readFileSync, unlinkSync } from 'node:fs';
import path from 'node:path';
import { fontSplit } from 'cn-font-split';

const fontNames = [ 'NotoSerifJP', 'NotoSerifKR', 'NotoSerifSC' ];

for ( let i = 0; i < fontNames.length; i++ ) {
	const fontName = fontNames[ i ];
	const fontPath = path.resolve( `./fonts/${ fontName }.ttf` );
	const inputBuffer = new Uint8Array( readFileSync( fontPath ).buffer );
	const outputDir = `./mu-plugins/global-fonts/NotoSerif/${ fontName }`;

	await fontSplit( {
		input: inputBuffer,
		outDir: outputDir,
		// This seems to be a maximum, not average. Subset files won't exceed this size.
		chunkSize: 5 * 1024 * 1024, // 5MB
		reduceMins: false,
		renameOutputFont: `${ fontName }-[index].[ext]`,
		testHtml: false,
		reporter: false,
		css: {
			compress: false,
			fileName: 'style.css',
		},
		silent: false,
	} );

	// Remove unnecessary file.
	unlinkSync( `${ outputDir }/index.proto` );
}
