'use strict';

const gulp = require('gulp');
const babel = require('gulp-babel');
const del = require('del');
const rev = require('gulp-rev');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const sourcemaps = require('gulp-sourcemaps');
const sass = require('gulp-dart-sass');
const clean_css = require('gulp-clean-css');
const revdel = require('gulp-rev-delete-original');
const mode = require('gulp-mode')();
const run = require('gulp-run-command').default;

var jsFiles = {
  'jquery': {
    base: 'node_modules/jquery/dist',
    files: [
      'node_modules/jquery/dist/jquery.min.js'
    ]
  },
  'bootstrap': {
    base: null,
    files: [
      'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js'
    ]
  },
  'bootstrap-notify': {
    base: 'node_modules/bootstrap-notify',
    files: [
      'node_modules/bootstrap-notify/bootstrap-notify.min.js'
    ]
  },
  'sweetalert2': {
    base: 'node_modules/sweetalert2/dist',
    files: [
      'node_modules/sweetalert2/dist/sweetalert2.min.js'
    ]
  },
  'material-icons': {
    files: [
      'font/*'
    ]
  },
  'roboto-fontface': {
    base: 'node_modules/roboto-fontface',
    files: [
      'node_modules/roboto-fontface/css/roboto/roboto-fontface.css',
      'node_modules/roboto-fontface/fonts/roboto/*'
    ]
  },
  'luxon': {
    files: [
      'node_modules/luxon/build/global/luxon.min.js'
    ]
  },
  'humanize-duration': {
    files: [
      'node_modules/humanize-duration/humanize-duration.js'
    ]
  },
  'clipboard': {
    base: 'node_modules/clipboard/dist',
    files: [
      'node_modules/clipboard/dist/clipboard.min.js'
    ]
  },
  'webcaster': {
    base: null,
    files: [
      'js/webcaster/*.js'
    ]
  },
};

var defaultTasks = Object.keys(jsFiles);

defaultTasks.forEach(function (libName) {
  gulp.task('scripts:' + libName, function () {
    return gulp.src(jsFiles[libName].files, {
      base: jsFiles[libName].base
    }).pipe(gulp.dest('../web/static/dist/lib/' + libName));
  });
});

gulp.task('bundle-deps', gulp.parallel(
  defaultTasks.map(function (name) {
    return 'scripts:' + name;
  })
));

gulp.task('clean', function () {
  return del([
    '../web/static/dist/**/*',
    '../web/static/webpack_dist/**/*',
    '../web/static/assets.json',
    '../web/static/webpack.json'
  ], { force: true });
});

gulp.task('concat-js', function () {
  return gulp.src('./js/inc/*.js')
    .pipe(sourcemaps.init())
    .pipe(babel({
      presets: ['@babel/env']
    }))
    .pipe(concat('app.js'))
    .pipe(uglify())
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('../web/static/dist'));
});

gulp.task('build-vue', run('webpack -c webpack.config.js'));

gulp.task('build-js', function () {
  return gulp.src(['./js/*.js'])
    .pipe(sourcemaps.init())
    .pipe(uglify())
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('../web/static/dist'));
});

gulp.task('build-css', function () {
  return gulp.src(['./scss/style.scss'])
    .pipe(mode.development(sourcemaps.init()))
    .pipe(sass())
    .pipe(clean_css())
    .pipe(mode.development(sourcemaps.write()))
    .pipe(gulp.dest('../web/static/dist'));
});

gulp.task('watch', function () {
    gulp.watch([
        './vue/**',
        './js/**/*.js',
        './scss/**',
    ], buildAll);
});

const buildAll = gulp.series('clean', gulp.parallel('concat-js', 'build-vue', 'build-js', 'build-css', 'bundle-deps'), function () {
  return gulp.src(['../web/static/dist/**/*.{js,css}'], { base: '../web/static/' })
    .pipe(rev())
    .pipe(revdel())
    .pipe(gulp.dest('../web/static/'))
    .pipe(rev.manifest('assets.json'))
    .pipe(gulp.dest('../web/static/'));
});

exports.default = buildAll
