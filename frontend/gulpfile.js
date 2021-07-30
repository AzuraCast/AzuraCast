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
const webpackStream = require('webpack-stream');
const gulpIgnore = require('gulp-ignore');
const mode = require('gulp-mode')();

var jsFiles = {
  // Core Libraries
  'jquery': {
    base: 'node_modules/jquery/dist',
    files: [
      'node_modules/jquery/dist/jquery.min.js'
    ]
  },
  'vue': {
    base: 'node_modules/vue/dist',
    files: [
      'node_modules/vue/dist/vue.js',
      'node_modules/vue/dist/vue.min.js'
    ]
  },
  'lodash': {
    base: 'node_modules/lodash',
    files: [
      'node_modules/lodash/lodash.min.js'
    ]
  },

  // Main per-layout dependencies
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
  'autosize': {
    base: 'node_modules/autosize/dist',
    files: [
      'node_modules/autosize/dist/autosize.min.js'
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
  'dirrty': {
    base: null,
    files: [
      'node_modules/dirrty/dist/jquery.dirrty.js'
    ]
  },

  // Individual libraries
  'store': {
    base: 'node_modules/store',
    files: [
      'node_modules/store/store.min.js'
    ]
  },
  'zxcvbn': {
    base: 'node_modules/zxcvbn/dist',
    files: [
      'node_modules/zxcvbn/dist/zxcvbn.js'
    ]
  },
  'chartjs': {
    base: null,
    files: [
      'node_modules/chart.js/dist/Chart.min.js',
      'node_modules/chart.js/dist/Chart.min.css',
      'node_modules/chartjs-plugin-colorschemes/dist/chartjs-plugin-colorschemes.min.js',
      'node_modules/hammerjs/hammer.min.js',
      'node_modules/chartjs-plugin-zoom/chartjs-plugin-zoom.min.js'
    ]
  },
  'select2': {
    files: [
      'node_modules/select2/dist/css/select2.min.css',
      'node_modules/select2/dist/js/select2.full.min.js'
    ]
  },
  'moment': {
    base: 'node_modules/moment/min',
    files: [
      'node_modules/moment/min/moment.min.js',
      'node_modules/moment/min/locales.min.js'
    ]
  },
  'moment-timezone': {
    base: 'node_modules/moment-timezone/builds',
    files: [
      'node_modules/moment-timezone/builds/moment-timezone-with-data-10-year-range.min.js'
    ]
  },
  'daterangepicker': {
    base: 'node_modules/bootstrap-daterangepicker',
    files: [
      'node_modules/bootstrap-daterangepicker/daterangepicker.*'
    ]
  },
  'codemirror': {
    base: null,
    files: [
      'node_modules/codemirror/lib/codemirror.*',
      'node_modules/codemirror/mode/css/css.js',
      'node_modules/codemirror/mode/javascript/javascript.js'
    ]
  },
  'clipboard': {
    base: 'node_modules/clipboard/dist',
    files: [
      'node_modules/clipboard/dist/clipboard.min.js'
    ]
  },
  'fancybox': {
    base: 'node_modules/@fancyapps/fancybox/dist',
    files: [
      'node_modules/@fancyapps/fancybox/dist/jquery.fancybox.min.*'
    ]
  },
  'flowjs': {
    base: 'node_modules/@flowjs/flow.js/dist',
    files: [
      'node_modules/@flowjs/flow.js/dist/flow.min.js'
    ]
  },
  'leaflet': {
    base: 'node_modules/leaflet/dist',
    files: [
      'node_modules/leaflet/dist/leaflet.js',
      'node_modules/leaflet/dist/leaflet.css',
      'node_modules/leaflet/dist/images/*'
    ]
  },
  'leaflet-fullscreen': {
    base: 'node_modules/leaflet.fullscreen',
    files: [
      'node_modules/leaflet.fullscreen/Control.FullScreen.js',
      'node_modules/leaflet.fullscreen/Control.FullScreen.css',
      'node_modules/leaflet.fullscreen/icon-*.png'
    ]
  },
  'nchan': {
    base: null,
    files: [
      'node_modules/nchan/NchanSubscriber.js'
    ]
  },
  'webcaster': {
    base: null,
    files: [
      'js/webcaster/*.js'
    ]
  },
  'bootgrid': {
    base: null,
    files: [
      'js/bootgrid/jquery.bootgrid.min.css',
      'js/bootgrid/jquery.bootgrid.updated.js'
    ]
  },
  'bootstrap-vue': {
    base: null,
    files: [
      'node_modules/bootstrap-vue/dist/bootstrap-vue.min.js',
      'node_modules/bootstrap-vue/dist/bootstrap-vue.min.css'
    ]
  }
};

var defaultTasks = Object.keys(jsFiles);

defaultTasks.forEach(function (libName) {
  gulp.task('scripts:' + libName, function () {
    return gulp.src(jsFiles[libName].files, {
      base: jsFiles[libName].base
    }).pipe(gulp.dest('../web/static/dist/lib/' + libName));
  });
});

gulp.task('bundle_deps', gulp.parallel(
  defaultTasks.map(function (name) {
    return 'scripts:' + name;
  })
));

gulp.task('clean', function () {
  return del([
    '../web/static/dist/**/*',
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

gulp.task('build-vue', function () {
  return gulp.src(['vue/*.js', 'vue/*.vue'])
    .pipe(webpackStream(require('./webpack.config.js')))
    .pipe(gulpIgnore.exclude('webpack.json'))
    .pipe(babel({
      presets: ['@babel/env']
    }))
    .pipe(uglify())
    .pipe(gulp.dest('../web/static/dist'));
});

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

gulp.task('default', gulp.series('clean', gulp.parallel('concat-js', 'build-vue', 'build-js', 'build-css', 'bundle_deps'), function () {
  return gulp.src(['../web/static/dist/**/*.{js,css}'], { base: '../web/static/' })
    .pipe(rev())
    .pipe(revdel())
    .pipe(gulp.dest('../web/static/'))
    .pipe(rev.manifest('assets.json'))
    .pipe(gulp.dest('../web/static/'));
}));
