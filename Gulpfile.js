// Require our dependencies
const babel = require( 'gulp-babel' );
const browserSync = require( 'browser-sync' );
const cheerio = require( 'gulp-cheerio' );
const concat = require( 'gulp-concat' );
const del = require( 'del' );
const eslint = require( 'gulp-eslint' );
const fs = require( 'fs' );
const gulp = require( 'gulp' );
const gutil = require( 'gulp-util' );
const notify = require( 'gulp-notify' );
const plumber = require( 'gulp-plumber' );
const reload = browserSync.reload;
const rename = require( 'gulp-rename' );
const sort = require( 'gulp-sort' );
const sourcemaps = require( 'gulp-sourcemaps' );
const uglify = require( 'gulp-uglify' );
const wpPot = require( 'gulp-wp-pot' );

// Set assets paths.
const paths = {
	'php': [ './*.php', './**/*.php' ],
	'concat_scripts': 'assets/scripts/concat/*.js',
	'scripts': [ 'assets/scripts/*.js', '!assets/scripts/*.min.js' ],
};

/**
 * Handle errors and alert the user.
 */
function handleErrors() {
	const args = Array.prototype.slice.call( arguments );

	notify.onError( {
		'title': 'Task Failed [<%= error.message %>',
		'message': 'See console.',
		'sound': 'Sosumi' // See: https://github.com/mikaelbr/node-notifier#all-notification-options-with-their-defaults
	} ).apply( this, args );

	gutil.beep(); // Beep 'sosumi' again.

	// Prevent the 'watch' task from stopping.
	this.emit( 'end' );
}

/**
 * Concatenate and transform JavaScript.
 *
 * https://www.npmjs.com/package/gulp-concat
 * https://github.com/babel/gulp-babel
 * https://www.npmjs.com/package/gulp-sourcemaps
 */
gulp.task( 'concat', () =>
	gulp.src( paths.concat_scripts )

		// Deal with errors.
		.pipe( plumber(
			{'errorHandler': handleErrors}
		) )

		// Start a sourcemap.
		.pipe( sourcemaps.init() )

		// Convert ES6+ to ES2015.
		.pipe( babel( {
			'presets': [
				[ 'env', {
					'targets': {
						'browsers': [ 'last 2 versions' ]
					}
				} ]
			]
		} ) )

		// Concatenate partials into a single script.
		.pipe( concat( 'project.js' ) )

		// Append the sourcemap to project.js.
		.pipe( sourcemaps.write() )

		// Save project.js
		.pipe( gulp.dest( 'assets/scripts' ) )
		.pipe( browserSync.stream() )
);

/**
  * Minify compiled JavaScript.
  *
  * https://www.npmjs.com/package/gulp-uglify
  */
gulp.task( 'uglify', [ 'concat' ], () =>
	gulp.src( paths.scripts )
		.pipe( plumber( {'errorHandler': handleErrors} ) )
		.pipe( rename( {'suffix': '.min'} ) )
		.pipe( babel( {
			'presets': [
				[ 'env', {
					'targets': {
						'browsers': [ 'last 2 versions' ]
					}
				} ]
			]
		} ) )
		.pipe( uglify( {
			'mangle': false
		} ) )
		.pipe( gulp.dest( 'assets/scripts' ) )
);

/**
 * Delete the theme's .pot before we create a new one.
 */
gulp.task( 'clean:pot', () =>
	del( [ 'languages/bbp-dash.pot' ] )
);

/**
 * Scan the theme and create a POT file.
 *
 * https://www.npmjs.com/package/gulp-wp-pot
 */
gulp.task( 'wp-pot', [ 'clean:pot' ], () =>
	gulp.src( paths.php )
		.pipe( plumber( {'errorHandler': handleErrors} ) )
		.pipe( sort() )
		.pipe( wpPot( {
			'domain': 'bbp-dash',
			'package': 'bbp-dash'
		} ) )
		.pipe( gulp.dest( 'languages/bbp-dash.pot' ) )
);

/**
 * JavaScript linting.
 *
 * https://www.npmjs.com/package/gulp-eslint
 */
gulp.task( 'js:lint', () =>
	gulp.src( [
		'assets/scripts/concat/*.js',
		'assets/scripts/*.js',
		'!assets/scripts/project.js',
		'!assets/scripts/*.min.js',
		'!Gruntfile.js',
		'!Gulpfile.js',
		'!node_modules/**'
	] )
		.pipe( eslint() )
		.pipe( eslint.format() )
		.pipe( eslint.failAfterError() )
);

/**
 * Process tasks and reload browsers on file changes.
 *
 * https://www.npmjs.com/package/browser-sync
 */
gulp.task( 'watch', function() {

	// Kick off BrowserSync.
	browserSync( {
		'open': false,             // Open project in a new tab?
		'injectChanges': true,     // Auto inject changes instead of full reload.
		'proxy': 'bbp-dash.dev',         // Use http://_s.dev:3000 to use BrowserSync.
		'watchOptions': {
			'debounceDelay': 1000  // Wait 1 second before injecting.
		}
	} );

	// Run tasks when files change.
	gulp.watch( paths.scripts, [ 'scripts' ] );
	gulp.watch( paths.concat_scripts, [ 'scripts' ] );
	gulp.watch( paths.php, [ 'markup' ] );
} );

/**
 * Create individual tasks.
 */
gulp.task( 'markup', browserSync.reload );
gulp.task( 'i18n', [ 'wp-pot' ] );
gulp.task( 'scripts', [ 'uglify' ] );
gulp.task( 'lint', [ 'js:lint' ] );
gulp.task( 'default', [ 'i18n', 'scripts', ] );
