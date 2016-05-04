var fs = require('fs');

module.exports = function(grunt) {
    grunt.initConfig({

        less: {
            build: {
                options: {},
                files: {
                    'dist/waves.css': 'src/less/waves.less'
                }
            },
            minified: {
                options: {
                    cleancss:true
                },
                files: {
                    'dist/waves.min.css': 'src/less/waves.less'
                }
            },
            // re-minify everything in tests/ so that they all
            // have the same minification for comparision
            test: {
                options: {
                    cleancss:true,
                    cleancssOptions: {
                        keepSpecialComments:'0'
                    }
                },
                files: {
                    'tests/less/waves.min.css': 'src/less/waves.less',
                    'tests/sass/waves.min.css': 'tests/sass/waves.css',
                    'tests/scss/waves.min.css': 'tests/scss/waves.css',
                    'tests/stylus/waves.min.css': 'tests/stylus/waves.css'
                }
            }
        },
        
        jshint: {
            
            files: [
                'gruntfile.js', 
                'src/js/*.js',
            ],
            
            options: {
                globals: {
                    console: true
                }
            }
        },
   
        uglify: {
            options: {
                mangle: true,  // false when debugging
                compress: {
                    sequences: true,
                    dead_code: true,
                    conditionals: true,
                    booleans: true,
                    unused: true,
                    if_return: true,
                    join_vars: true,
                    drop_console: true
                },
                sourceMap: true,
                sourceMapName: 'dist/waves.min.js.map',
                preserveComments: 'some'
            },
            js: {
                files: {
                    'dist/waves.min.js': ['src/js/waves.js']
                }
            }
        },

        // Copy to dist
        copy: {
			js: {
                expand: true,
                cwd: 'src/js',
				src: 'waves.js',
				dest: 'dist/'
			},
            docs: {
                expand: true,
                cwd: 'dist',
                src: ['waves.min.css', 'waves.min.js'],
                dest: 'docs/static'
            }
		},
        
        //convert less to stylus
        execute: {
            less2stylus: {
                call: function(grunt, options, async) {
                    var done = async();
                    var exec = require('child_process').exec;
                    exec('cd node_modules/less2stylus && node ./less2stylus ../../src/less/waves.less', function (error, stdout, stderr) {
                        grunt.log.writeln('Executing less2styus...');

                        if (error) {
                            grunt.log.writeln('Error! ' + error);
                        }

                        var fs = require('fs');
                        fs.writeFile("src/stylus/waves.styl", stdout, function(err) {
                            if(err) {
                                grunt.log.writeln(err);
                            } else {
                                grunt.log.writeln("Stylus file was saved!");
                            }

                            done(); // let grunt resume
                        });
                    });
                }
            },

            less2scss: {
                //FUTURE: Put less2scss as it's own script
                call: function(grunt, options, async) {
                    var done = async();
                    var text = fs.readFileSync('src/less/waves.less', {encoding:'utf8'});

                    //replace @ with $
                    text = text.replace(/@(?!import|media|keyframes|-)/g, '$');
                    //replace mixins
                    text = text.replace(/\.([\w\-]*)\s*\((.*)\)\s*\{/g, '@mixin $1($2){');
                    //replace includes
                    text = text.replace(/\.([\w\-]*\(.*\)\s*;)/g, '@include $1');
                    //replace string literals
                    //eg. ~'!important' -> #{"!important"}
                    text = text.replace(/~(?:\"|\')(.*)(?:\"|\')/g, '#{"$1"}');

                    //NOTE: for true less->scss transpiling we'd need to replace spin to adjust-hue (not needed but anyway)

                    fs.writeFileSync('src/scss/waves.scss', text);

                    done();
                }
            },
            
            test: {
                call: function(grunt, options, async) {
                    var done = async();
                    var lessTest = fs.readFileSync('tests/less/waves.min.css', {encoding:'utf8'});
                    var sassTest = fs.readFileSync('tests/sass/waves.min.css', {encoding:'utf8'});
                    var scssTest = fs.readFileSync('tests/scss/waves.min.css', {encoding:'utf8'});
                    var stylusTest = fs.readFileSync('tests/stylus/waves.min.css', {encoding:'utf8'});
                    
                    var failure = false;
                    if (lessTest != sassTest) {
                        grunt.log.writeln('ERROR: sass failed test.');
                        failure = true;
                    }
                    
                    if (lessTest != scssTest) {
                        grunt.log.writeln('ERROR: scss failed test.');
                        failure = true;
                    }
                    
                    if (lessTest != stylusTest) {
                        grunt.log.writeln('ERROR: stylus failed test.');
                        failure = true;
                    }
                    
                    if (sassTest != scssTest) {
                        grunt.log.writeln('WARNING: sass files aren\'t equal?');
                        failure = true;
                    }
                    
                    if (!failure) grunt.log.writeln('PASS: conversions generated same CSS');
                    
                    done();
                }
            }
        },

        'sass-convert': {
            options: {
                from: 'scss',
                to: 'sass',
                indent: 2
            },
            files: {
                cwd: 'src/scss',
                src: '*.scss',
                dest: 'src/sass'
            }
        },
        
        sass: {
            test: {
                files: [{
                    expand: true,
                    cwd: 'src',
                    src: ['**/*.sass', '**/*.scss'],
                    dest: 'tests/',
                    ext: '.css'
                }]
            }
        },
        
        stylus: {
            test: {
                files: {
                    'tests/stylus/waves.css': 'src/stylus/waves.styl'
                }
            }
        },
        
        clean: {
            test: ['tests/*']
        },

        watch: {
            script: {
               options: {
                    spawn: false,
                    event: ['added', 'deleted', 'changed']
                },
                files: ['src/**/*.js', 'src/**/*.less'],
                tasks: ['build']
            },
            grunt: {
                files: ['Gruntfile.js']
            }
        }
    });
    
    // Load module
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-stylus');
    grunt.loadNpmTasks('grunt-execute');
    grunt.loadNpmTasks('grunt-sass-convert');
    grunt.loadNpmTasks('grunt-contrib-clean');
    
    // Create grunt task
    grunt.registerTask('build', [
        'less:build', 
        'less:minified', 
        'jshint', 
        'uglify', 
        'copy', 
        'execute:less2stylus', 
        'execute:less2scss', 
        'sass-convert', 
        'sass:test', 
        'stylus:test', 
        'less:test', 
        'execute:test',
        'clean:test'
    ]);
    
    grunt.registerTask('default', ['build', 'watch']);
};
