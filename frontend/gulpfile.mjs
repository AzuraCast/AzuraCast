'use strict';

import gulp from 'gulp';
import babel from 'gulp-babel';
import {deleteAsync as del} from 'del';
import rev from 'gulp-rev';
import concat from 'gulp-concat';
import uglify from 'gulp-uglify';
import gulp_sourcemaps from 'gulp-sourcemaps';
import sass from 'gulp-dart-sass';
import clean_css from 'gulp-clean-css';
import revdel from 'gulp-rev-delete-original';
import gulpmode from 'gulp-mode';
import run_command from 'gulp-run-command';

const { task, src, dest, parallel, watch, series } = gulp;
const { manifest } = rev;
const { init, write } = gulp_sourcemaps;
const mode = gulpmode();
const run = run_command.default;

const jsFiles = {
    'bootstrap': {
        base: null,
        files: [
            'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js'
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
    'webcaster': {
        base: null,
        files: [
            'js/webcaster/*.js'
        ]
    }
};

const defaultTasks = Object.keys(jsFiles);

defaultTasks.forEach(function (libName) {
    task('scripts:' + libName, function () {
        return src(jsFiles[libName].files, {
            base: jsFiles[libName].base
        }).pipe(dest('../web/static/dist/lib/' + libName));
    });
});

task('bundle-deps', parallel(
    defaultTasks.map(function (name) {
        return 'scripts:' + name;
    })
));

task('clean', function () {
    return del([
        '../web/static/dist/**/*',
        '../web/static/webpack_dist/**/*',
        '../web/static/assets.json',
        '../web/static/webpack.json'
    ], {force: true});
});

task('concat-js', function () {
    return src('./js/inc/*.js')
        .pipe(init())
        .pipe(babel({
            presets: ['@babel/env']
        }))
        .pipe(concat('app.js'))
        .pipe(uglify())
        .pipe(write())
        .pipe(dest('../web/static/dist'));
});

task('build-vue', run('webpack -c webpack.config.mjs'));

task('build-css', function () {
    return src(['./scss/style.scss'])
            .pipe(mode.development(init()))
            .pipe(sass({
                includePaths: ['node_modules']
            }))
        .pipe(clean_css())
        .pipe(mode.development(write()))
        .pipe(dest('../web/static/dist'));
});

task('watch', function () {
    watch([
        './vue/**',
        './js/**/*.js',
        './scss/**',
    ], buildAll);
});

const buildAll = series('clean', parallel('concat-js', 'build-vue', 'build-css', 'bundle-deps'), function () {
    return src(['../web/static/dist/**/*.{js,css}'], {base: '../web/static/'})
            .pipe(rev())
            .pipe(revdel())
            .pipe(dest('../web/static/'))
            .pipe(manifest('assets.json'))
            .pipe(dest('../web/static/'));
});

export { buildAll as default };
