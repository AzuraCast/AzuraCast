module.exports = function(grunt) {

    var static_dir = '/var/azuracast/www/web/static';

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        less: {
            development: {
                options: {
                    paths: [static_dir+"/css"],
                    compress: true,
                    sourceMap: true,
                    sourceMapRootpath: '/',
                    sourceMapBasepath: function (f) {
                        this.sourceMapURL = this.sourceMapFilename.substr(this.sourceMapFilename.lastIndexOf('/') + 1);
                        return "wwwroot/";
                    }
                },
                files: {
                    [static_dir+"/css/light.css"]: static_dir+"/less/light.less",
                    [static_dir+"/css/dark.css"]: static_dir+"/less/dark.less"
                },
                cleancss: true
            }
        },
        concat: {
            dist: {
                src: [static_dir+'/js/inc/**/*.js'],
                dest: static_dir+'/js/app.js'
            }
        },
        uglify: {
            options: {
                mangle: false
            },
            my_target: {
                files: {
                    [static_dir+'/js/app.min.js']: [static_dir+'/js/app.js']
                }
            }
        },
        csssplit: {
            your_target: {
                src: [static_dir+'/css/app.css'],
                dest: static_dir+'/css/app.min.css',
                options: {
                    maxSelectors: 4095,
                    suffix: '.'
                }
            }
        },
        watch: {
            less: {
                files: [static_dir+'/less/**/*.less'], // which files to watch
                tasks: ['less'],
                options: {
                    nospawn: true,
                    livereload: 8122
                } 
            },
            js: {
                files: [static_dir+'/js/inc/**/*.js'], // which files to watch
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