module.exports = function( grunt ) {

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		watch: {
			grunt: {
				files: [
					'Gruntfile.js',
					'package.json'
				]
			},

			sass: {
				files: 'scss/**/*.scss',
				tasks: [ 'scsslint', 'styles' ]
			},
			scripts: {
				files: 'js/*.js',
				tasks: [ 'jshint', 'jscs' ]
			}
		},

		jshint: {
			options: {
				curly: true,
				forin: true,
				freeze: true,
				futurehostile: true,
				latedef: true,
				noarg: true,
				nocomma: true,
				nonbsp: true,
				nonew: true,
				singleGroups: true,
				undef: true,
				browser: true,
				jquery: true,
				node: true,
				predef: [
				]
			},
			tsu: ['js/*.js' ],
			gruntfiles: [ 'Gruntfile.js' ]
		},

		jscs: {
			options: {
				preset: 'wordpress'
			},
			tsu: [ 'js/*.js' ],
			gruntfiles: [ 'Gruntfile.js' ]
		},

		phpcs: {
			options: {
				bin: 'vendor/bin/phpcs --report-diff=phpcbf.diff --exclude=WordPress.Files.FileName,Generic.Formatting.DisallowMultipleStatements,WordPress.WP.EnqueuedResources,WordPress.VIP.RestrictedFunctions,WordPress.WP.AlternativeFunctions,WordPress.WP.DiscouragedFunctions,WordPress.VIP.SuperGlobalInputUsage,WordPress.NamingConventions.ValidHookName',
				standard: 'Wordpress'
			},
			tsu: {
				src: [
					'*.php'
				]
			}
		}
	} );

	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-phpcs' );
	grunt.loadNpmTasks( 'grunt-jscs' );

	grunt.registerTask( 'lint', [ 'jshint', 'jscs', 'phpcs' ] );
	grunt.registerTask( 'default', [ 'lint', 'watch' ] );
};
