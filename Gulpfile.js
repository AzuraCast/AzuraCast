var gulp        = require('gulp'),
    sourcemaps  = require('gulp-sourcemaps'),
    concat      = require('gulp-concat'),
    sass        = require('gulp-sass'),
    del         = require('del');

var paths = {
    sass: [
        'web/static/sass/*.scss'
    ]
};

gulp.task('build', [
    'sass'
]);

gulp.task('clean', function(cb) {
    del(['compiled'], cb);
});

gulp.task('watch', function() {
    for(var task in paths) {
        gulp.watch(paths[task], [task]);
    }
});

gulp.task('sass', function() {
    return gulp.src(paths.sass)
        .pipe(sourcemaps.init())
        .pipe(sass({ outputStyle: 'compressed' }))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('web/static/compiled'));
});