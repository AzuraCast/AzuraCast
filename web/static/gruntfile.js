module.exports = function(grunt) {

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        less: {
            development: {
                options: {
                    paths: ["/var/azuracast/www/web/static/css"],
                    compress: true,
                    sourceMap: true,
                    sourceMapRootpath: '/',
                    sourceMapBasepath: function (f) {
                        this.sourceMapURL = this.sourceMapFilename.substr(this.sourceMapFilename.lastIndexOf('/') + 1);
                        return "wwwroot/";
                    }
                },
                files: {
                    "/var/azuracast/www/web/static/css/light.css": "/var/azuracast/www/web/static/less/light.less",
                    "/var/azuracast/www/web/static/css/dark.css": "/var/azuracast/www/web/static/less/dark.less"
                },
                cleancss: true
            }
        },
        concat: {
            dist: {
                src: ['/var/azuracast/www/web/static/js/inc/**/*.js'],
                dest: '/var/azuracast/www/web/static/js/app.js'
            }
        },
        uglify: {
            options: {
                mangle: false
            },
            my_target: {
                files: {
                    '/var/azuracast/www/web/static/js/app.min.js': ['/var/azuracast/www/web/static/js/app.js']
                }
            }
        },
        csssplit: {
            your_target: {
                src: ['/var/azuracast/www/web/static/css/app.css'],
                dest: '/var/azuracast/www/web/static/css/app.min.css',
                options: {
                    maxSelectors: 4095,
                    suffix: '.'
                }
            }
        },
        watch: {
            less: {
                files: ['/var/azuracast/www/web/static/less/**/*.less'], // which files to watch
                tasks: ['less'],
                options: {
                    nospawn: true,
                    livereload: 8122
                } 
            },
            js: {
                files: ['/var/azuracast/www/web/static/js/inc/**/*.js'], // which files to watch
                tasks: ['concat', 'uglify']
            }
        }
    });
  
    // Load the plugin that provides the "less" task.
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-csssplit');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-concat');
  
    // Default task(s).
    grunt.registerTask('default', ['less', 'concat', 'uglify']);
    grunt.registerTask('js', ['concat', 'uglify']);
    
};