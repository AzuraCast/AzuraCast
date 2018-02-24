'use strict';

const gulp = require('gulp');
const clean = require('gulp-clean');
const rev = require('gulp-rev');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const sourcemaps = require('gulp-sourcemaps');
const less = require('gulp-less');
const path = require('path');

gulp.task('clean', function() {
    return gulp.src('./dist/**/*', { read: false })
        .pipe(clean());
});

gulp.task('cachebust', ['clean'], function() {
    return gulp.src(['./css/*.css', './js/app.js'])
        .pipe(rev())
        .pipe(gulp.dest('dist'))
        .pipe(rev.manifest())
        .pipe(gulp.dest(''));
});

gulp.task('build-js', function() {
    return gulp.src('./js/inc/*.js')
        .pipe(sourcemaps.init())
            .pipe(concat('app.js'))
            .pipe(uglify())
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('./js/'));
});

gulp.task('build-css', function() {
    return gulp.src(['./less/light.less', './less/dark.less'])
        .pipe(sourcemaps.init())
            .pipe(less())
            .pipe(uglify())
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('./css/'));
});

gulp.task('default', ['build-css', 'build-js', 'cachebust']);