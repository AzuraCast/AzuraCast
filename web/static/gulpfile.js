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
    "jquery": {
        base: 'node_modules/jquery/dist',
        files: [
            "node_modules/jquery/dist/jquery.min.js"
        ]
    },
    "vue": {
        base: 'node_modules/vue/dist',
        files: [
            "node_modules/vue/dist/vue.js",
            "node_modules/vue/dist/vue.min.js",
        ]
    },
    "vue-i18n": {
        base: 'node_modules/vue-i18n/dist',
        files: [
            "node_modules/vue-i18n/dist/vue-i18n.min.js"
        ]
    },
    "lodash": {
        base: 'node_modules/lodash',
        files: [
            "node_modules/lodash/lodash.min.js"
        ]
    },

    // Main per-layout dependencies
    "bootstrap": {
        base: null,
        files: [
            "node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"
        ]
    },
    "bootstrap-notify": {
        base: 'node_modules/bootstrap-notify',
        files: [
            "node_modules/bootstrap-notify/bootstrap-notify.min.js"
        ]
    },
    "sweetalert": {
        base: 'node_modules/sweetalert/dist',
        files: [
            "node_modules/sweetalert/dist/sweetalert.min.js"
        ]
    },
    "autosize": {
        base: 'node_modules/autosize/dist',
        files: [
            "node_modules/autosize/dist/autosize.min.js"
        ]
    },

    // Individual libraries
    "store": {
        base: 'node_modules/store',
        files: [
            "node_modules/store/store.min.js"
        ]
    },
    "zxcvbn": {
        base: 'node_modules/zxcvbn/dist',
        files: [
            "node_modules/zxcvbn/dist/zxcvbn.js"
        ]
    },
    "chartjs": {
        base: null,
        files: [
            "node_modules/chart.js/dist/Chart.min.js",
            "node_modules/chart.js/dist/Chart.min.css",
            "node_modules/chartjs-plugin-colorschemes/dist/chartjs-plugin-colorschemes.min.js"
        ]
    },
    "chosen": {
        base: 'node_modules/chosen-js',
        files: [
            "node_modules/chosen-js/chosen.jquery.min.js",
            "node_modules/chosen-js/chosen.min.css",
            "node_modules/chosen-js/chosen-sprite*.png"
        ]
    },
    "moment": {
        base: 'node_modules/moment/min',
        files: [
            "node_modules/moment/min/moment.min.js",
            "node_modules/moment/min/locales.min.js"
        ]
    },
    "moment-timezone": {
        base: 'node_modules/moment-timezone/builds',
        files: [
            "node_modules/moment-timezone/builds/moment-timezone-with-data.min.js"
        ]
    },
    "daterangepicker": {
        base: 'node_modules/bootstrap-daterangepicker',
        files: [
            "node_modules/bootstrap-daterangepicker/daterangepicker.*"
        ]
    },
    "codemirror": {
        base: null,
        files: [
            "node_modules/codemirror/lib/codemirror.*",
            "node_modules/codemirror/mode/css/css.js",
            "node_modules/codemirror/theme/material.css"
        ]
    },
    "clipboard": {
        base: 'node_modules/clipboard/dist',
        files: [
            "node_modules/clipboard/dist/clipboard.min.js"
        ]
    },
    "fancybox": {
        base: 'node_modules/@fancyapps/fancybox/dist',
        files: [
            "node_modules/@fancyapps/fancybox/dist/jquery.fancybox.min.*"
        ]
    },
    "flowjs": {
        base: 'node_modules/@flowjs/flow.js/dist',
        files: [
            "node_modules/@flowjs/flow.js/dist/flow.min.js"
        ]
    },
    "fullcalendar": {
        base: 'node_modules/fullcalendar/dist',
        files: [
            "node_modules/fullcalendar/dist/fullcalendar.min.*",
            "node_modules/fullcalendar/dist/locale-all.js"
        ]
    },
    "sortable": {
        base: null,
        files: [
            "node_modules/sortablejs/Sortable.min.js"
        ]
    },
    "leaflet": {
        base: 'node_modules/leaflet/dist',
        files: [
            "node_modules/leaflet/dist/leaflet.js",
            "node_modules/leaflet/dist/leaflet.css",
            "node_modules/leaflet/dist/images/*",
        ]
    }
};

var defaultTasks = Object.keys(jsFiles);

defaultTasks.forEach(function (libName) {
    gulp.task('scripts:'+libName, function () {
       return gulp.src(jsFiles[libName].files, {
           base: jsFiles[libName].base
       }).pipe(gulp.dest('dist/lib/'+libName));
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

var vueProjects = {
    "webcaster": {
        "src_file": 'vue/webcaster.vue',
        "filename": 'webcaster.js',
        "library": 'Webcaster'
    },
    "radio_player": {
        "src_file": 'vue/radio_player.vue',
        "filename": 'radio_player.js',
        "library": 'RadioPlayer'
    },
    "inline_player": {
        "src_file": 'vue/inline_player.vue',
        "filename": 'inline_player.js',
        "library": 'InlinePlayer'
    }
};

var vueTasks = Object.keys(vueProjects);

vueTasks.forEach(function (libName) {
    gulp.task('vue:'+libName, function () {
        var vueProject = vueProjects[libName];
        return gulp.src(vueProject.src_file)
            .pipe(sourcemaps.init())
            .pipe(webpack({
                mode: 'production',
                output: {
                    publicPath: '/static/dist',
                    filename: vueProject.filename,
                    library: vueProject.library
                },
                module: {
                    rules: [
                        {
                            test: /\.vue$/,
                            loader: 'vue-loader',
                            options: {}
                        },
                        {
                            test: /\.scss$/,
                            use: [
                                'vue-style-loader',
                                'css-loader',
                                'sass-loader'
                            ]
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
});

gulp.task('build-vue', gulp.series(
    vueTasks.map(function(name) {
        return 'vue:'+name;
    })
));

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
