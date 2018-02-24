'use strict';

const gulp = require('gulp');
const clean = require('gulp-clean');
const rev = require('gulp-rev');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const sourcemaps = require('gulp-sourcemaps');
const less = require('gulp-less');
const clean_css = require('gulp-clean-css');
var revdel = require('gulp-rev-delete-original');

gulp.task('clean', function() {
    return gulp.src(['./dist/**/*', './assets.json'], { read: false })
        .pipe(clean());
});

gulp.task('build-js', ['clean'], function() {
    return gulp.src('./js/inc/*.js')
        .pipe(sourcemaps.init())
            .pipe(concat('app.min.js'))
            .pipe(uglify())
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('./dist'));
});

gulp.task('build-css', ['clean'], function() {
    return gulp.src(['./less/light.less', './less/dark.less'])
        .pipe(sourcemaps.init())
            .pipe(less())
            .pipe(clean_css())
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('./dist'));
});

gulp.task('default', ['build-js', 'build-css'], function() {
    return gulp.src(['./dist/*'], { base: '.' })
        .pipe(rev())
        .pipe(revdel())
        .pipe(gulp.dest('.'))
        .pipe(rev.manifest('assets.json'))
        .pipe(gulp.dest('./'));
});