'use strict';

/**
 * @file
 * Gulp configuration for the My Places theme.
 *
 * CSS: Compiles SCSS from components/ and libraries/ to build/.
 * JS:  Copies JS from components/ and libraries/ to build/.
 *
 * Run:
 *   npm install
 *   npm run compile       → one-time build
 *   npm run compile:watch → watch mode
 */

const { src, dest, watch, series, parallel } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const autoprefixer = require('gulp-autoprefixer');
const rename = require('gulp-rename');
const argv = require('process').argv;
const isWatch = argv.includes('--watch');

// ---------------------------------------------------------------------------
// CSS
// ---------------------------------------------------------------------------
const cssInputs = [
  'components/**/*.scss',
  'libraries/**/*.scss',
  '!components/**/_*.scss',
  '!libraries/**/_*.scss',
  '!partials/**/*.scss',
];

function cssTask() {
  return src(cssInputs, { base: '.' })
    .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(dest('build'));
}

function cssWatch() {
  cssTask();
  return watch(
    ['components/**/*.scss', 'libraries/**/*.scss', 'partials/**/*.scss'],
    cssTask
  );
}

// ---------------------------------------------------------------------------
// JS
// ---------------------------------------------------------------------------
const jsInputs = [
  'components/**/*.js',
  'libraries/**/*.js',
];

function jsTask() {
  return src(jsInputs, { base: '.' })
    .pipe(dest('build'));
}

function jsWatch() {
  jsTask();
  return watch(jsInputs, jsTask);
}

// ---------------------------------------------------------------------------
// Tasks
// ---------------------------------------------------------------------------
exports.css = isWatch ? cssWatch : cssTask;
exports.js  = isWatch ? jsWatch  : jsTask;
exports.compile = isWatch
  ? parallel(cssWatch, jsWatch)
  : parallel(cssTask, jsTask);
exports.default = exports.compile;
