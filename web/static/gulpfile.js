'use strict';

const gulp = require('gulp');
const babel = require('gulp-babel');
const del = require('del');
const rev = require('gulp-rev');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const sourcemaps = require('gulp-sourcemaps');
const sass = require('gulp-sass');
const clean_css = require('gulp-clean-css');
const revdel = require('gulp-rev-delete-original');
const webpack = require('webpack-stream');

var jsFiles = {
    // Core Libraries
    "jquery": [
        "node_modules/jquery/dist/jquery.min.js"
    ],
    "vue": [
        "node_modules/vue/dist/vue.js",
        "node_modules/vue/dist/vue.min.js"
    ],
    "vue-i18n": [
        "node_modules/vue-i18n/dist/vue-i18n.min.js"
    ],
    "lodash": [
        "node_modules/lodash/lodash.min.js"
    ],

    // Main per-layout dependencies
    "bootstrap": [
        "node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"
    ],
    "bootstrap-notify": [
        "node_modules/bootstrap-notify/bootstrap-notify.min.js"
    ],
    "sweetalert": [
        "node_modules/sweetalert/dist/sweetalert.min.js"
    ],
    "autosize": [
        "node_modules/autosize/dist/autosize.min.js"
    ],

    // Individual libraries
    "store": [
        "node_modules/store/store.min.js"
    ],
    "highcharts": [
        "node_modules/highcharts/highcharts.js",
        "node_modules/highcharts/highcharts-more.js",
        "node_modules/highcharts/modules/exporting.js",
        "node_modules/highcharts/modules/map.js"
    ],
    "proj4": [
        "node_modules/proj4/dist/proj4.js"
    ],
    "zxcvbn": [
        "node_modules/zxcvbn/dist/zxcvbn.js"
    ],
    "chosen": [
        "node_modules/chosen-js/chosen.jquery.min.js",
        "node_modules/chosen-js/chosen.min.css",
        "node_modules/chosen-js/chosen-sprite*.png"
    ],
    "moment": [
        "node_modules/moment/min/moment-with-locales.min.js"
    ],
    "moment-timezone": [
        "node_modules/moment-timezone/builds/moment-timezone-with-data.min.js"
    ],
    "daterangepicker": [
        "node_modules/bootstrap-daterangepicker/daterangepicker.*"
    ],
    "codemirror": [
        "node_modules/codemirror/lib/codemirror.*",
        "node_modules/codemirror/mode/css/css.js",
        "node_modules/codemirror/theme/material.css"
    ],
    "clipboard": [
        "node_modules/clipboard/dist/clipboard.min.js"
    ],
    "fancybox": [
        "node_modules/@fancyapps/fancybox/dist/jquery.fancybox.min.*"
    ],
    "flowjs": [
        "node_modules/@flowjs/flow.js/dist/flow.min.js"
    ],
    "fullcalendar": [
        "node_modules/fullcalendar/dist/fullcalendar.min.*",
        "node_modules/fullcalendar/dist/locale-all.js"
    ],
    "jquery-sortable": [
        "node_modules/jquery-sortable/source/js/jquery-sortable-min.js"
    ],
};

var defaultTasks = Object.keys(jsFiles);

defaultTasks.forEach(function (libName) {
    gulp.task('scripts:'+libName, function () {
       return gulp.src(jsFiles[libName])
          .pipe(gulp.dest('dist/lib/'+libName));
    });
});

gulp.task('bundle_deps', gulp.parallel(
    defaultTasks.map(function(name) {
        return 'scripts:'+name;
    })
));

gulp.task('clean', function() {
    return del([
        './dist/**/*',
        './assets.json'
    ]);
});

gulp.task('concat-js', function() {
    return gulp.src('./js/inc/*.js')
        .pipe(sourcemaps.init())
            .pipe(babel({
                presets: ['@babel/env']
            }))
            .pipe(concat('app.js'))
            .pipe(uglify())
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('./dist'));
});

gulp.task('build-vue', function() {
    return gulp.src('vue/webcaster.vue')
        .pipe(sourcemaps.init())
            .pipe(webpack({
                mode: 'production',
                output: {
                    publicPath: '/static/dist',
                    filename: 'webcaster.js',
                    library: 'Webcaster'
                },
                module: {
                    rules: [
                        {
                            test: /\.vue$/,
                            loader: 'vue-loader',
                            options: {}
                        }
                    ]
                }
            }))
            .pipe(babel({
                presets: ['@babel/env']
            }))
            .pipe(uglify())
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('./dist'));
});

gulp.task('build-js', function() {
    return gulp.src(['./js/*.js'])
        .pipe(sourcemaps.init())
            .pipe(uglify())
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('./dist'));
});

gulp.task('build-css', function() {
    return gulp.src(['./scss/dark.scss', './scss/light.scss'])
        .pipe(sourcemaps.init())
            .pipe(sass())
            .pipe(clean_css())
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('./dist'));
});

gulp.task('default', gulp.series('clean', gulp.parallel('concat-js', 'build-vue', 'build-js', 'build-css', 'bundle_deps'), function() {
    return gulp.src(['./dist/**/*.{js,css}'], { base: '.' })
        .pipe(rev())
        .pipe(revdel())
        .pipe(gulp.dest('.'))
        .pipe(rev.manifest('assets.json'))
        .pipe(gulp.dest('./'));
}));
