module.exports = function(grunt) {

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        less: {
            development: {
                options: {
                    paths: ["css"],
                    compress: true,
                    sourceMap: true,
                    sourceMapRootpath: '/static',
                    sourceMapBasepath: function (f) {
                        this.sourceMapURL = this.sourceMapFilename.substr(this.sourceMapFilename.lastIndexOf('/') + 1);
                        return "wwwroot/";
                    }
                },
                files: {
                    "css/light.css": "less/light.less",
                    "css/dark.css": "less/dark.less"
                },
                cleancss: true
            }
        },
        concat: {
            dist: {
                src: ['js/inc/**/*.js'],
                dest: 'js/app.js'
            }
        },
        uglify: {
            options: {
                mangle: false
            },
            my_target: {
                files: {
                    'js/app.min.js': 'js/app.js'
                }
            }
        },
        csssplit: {
            your_target: {
                src: ['css/app.css'],
                dest: 'css/app.min.css',
                options: {
                    maxSelectors: 4095,
                    suffix: '.'
                }
            }
        },
        watch: {
            less: {
                files: ['less/**/*.less'], // which files to watch
                tasks: ['less'],
                options: {
                    nospawn: true,
                    livereload: 8122
                } 
            },
            js: {
                files: ['js/inc/**/*.js'], // which files to watch
                tasks: ['concat', 'uglify']
            }
        },
        cacheBust: {
            core: {
                options: {
                    assets: ['js/*.js', 'css/*.css'],
                    queryString: true,
                    jsonOutput: true,
                    jsonOutputFilename: 'assets.json'
                },
                src: ['index.html']
            }
        }
    });
  
    // Load the plugin that provides the "less" task.
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-csssplit');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-cache-bust');
  
    // Default task(s).
    grunt.registerTask('default', ['less', 'concat', 'uglify', 'cacheBust']);
    grunt.registerTask('js', ['concat', 'uglify']);
    
};