module.exports = function (grunt) {
	'use strict';

	require('load-grunt-tasks')(grunt);

	var pkg = grunt.file.readJSON('package.json');
	var config = {};

	config.pkg = pkg;

	config.version = {
		main: {
			options: {
				prefix: 'Version:[\\s]+'
			},
			src: [
				'<%= pkg.name %>.php'
			]
		},
		readme: {
			options: {
				prefix: 'Stable tag:[\\s]+'
			},
			src: [
				'readme.md'
			]
		},
		pkg: {
			src: [
				'package.json'
			]
		}
	};

	grunt.initConfig(config);

	grunt.registerTask('bump', function(version) {
		if ( ! version ) {
			grunt.fail.fatal( 'No version specified. Usage: bump:major, bump:minor, bump:patch, bump:x.y.z' );
		}

		grunt.task.run([
			'version::' + version
		]);
	});
};
