module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        sass: {
            options: {
                sourceMap: true,
                outputStyle: 'compressed'
            },
            dist: {
                files: {
                    'web/static/compiled/style.css' : 'web/static/sass/style.scss'
                }
            }
        },
        watch: {
            css: {
                files: 'web/static/sass/*.scss',
                tasks: ['sass']
            }
        }
    });

    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.registerTask('default',['watch','sass:dist']);
};