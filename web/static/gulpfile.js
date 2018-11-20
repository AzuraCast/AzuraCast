'use strict';

const gulp = require('gulp');
const clean = require('gulp-clean');
const rev = require('gulp-rev');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const sourcemaps = require('gulp-sourcemaps');
const sass = require('gulp-sass');
const clean_css = require('gulp-clean-css');
const revdel = require('gulp-rev-delete-original');
const coffee = require('gulp-coffee');

gulp.task('clean', function() {
    return gulp.src(['./dist/**/*', './assets.json'], { read: false })
        .pipe(clean());
});

gulp.task('concat-js', ['clean'], function() {
    return gulp.src('./js/inc/*.js')
        .pipe(sourcemaps.init())
            .pipe(concat('app.js'))
            .pipe(uglify())
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('./dist'));
});

gulp.task('build-webcaster', ['clean'], function() {
    gulp.src('./webcaster/**/*.coffee')
        .pipe(sourcemaps.init())
            .pipe(coffee())
            .pipe(concat('webcaster.js'))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('./dist'));
});

gulp.task('build-js', ['clean'], function() {
    return gulp.src(['./js/*.js'])
        .pipe(sourcemaps.init())
            .pipe(uglify())
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('./dist'));
});

gulp.task('build-css', ['clean'], function() {
    return gulp.src(['./scss/dark.scss', './scss/light.scss'])
        .pipe(sourcemaps.init())
            .pipe(sass())
            .pipe(clean_css())
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('./dist'));
});

gulp.task('default', ['concat-js', 'build-webcaster', 'build-js', 'build-css'], function() {
    return gulp.src(['./dist/*'], { base: '.' })
        .pipe(rev())
        .pipe(revdel())
        .pipe(gulp.dest('.'))
        .pipe(rev.manifest('assets.json'))
        .pipe(gulp.dest('./'));
});
