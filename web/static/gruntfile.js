module.exports = function(grunt) {

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        less: {
            development: {
                options: {
                    paths: ["/var/azuracast/www/web/static/css"]
                },
                files: {
                    "/var/azuracast/www/web/static/css/app.css": "/var/azuracast/www/web/static/less/app.less",
                },
                cleancss: true
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
            //styles: {
                files: ['/var/azuracast/www/web/static/less/**/*.less'], // which files to watch
                tasks: ['less', 'csssplit'],
                options: {
                    nospawn: true,
                    livereload: 8122,
                } 
            //}
        }
    });
  
    // Load the plugin that provides the "less" task.
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-csssplit');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-uglify');
  
    // Default task(s).
    grunt.registerTask('default', ['less']);
    
};