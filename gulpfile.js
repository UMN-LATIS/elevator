var gulp = require('gulp'),
    sass = require('gulp-ruby-sass'),
    autoprefixer = require('gulp-autoprefixer'),
    minifycss = require('gulp-minify-css'),
    jshint = require('gulp-jshint'),
    uglify = require('gulp-uglify'),
    imagemin = require('gulp-imagemin'),
    rename = require('gulp-rename'),
    concat = require('gulp-concat'),
    notify = require('gulp-notify'),
    cache = require('gulp-cache'),
    livereload = require('gulp-livereload'),
    del = require('del');
    del = require('gulp-sourcemaps');
var sourcemaps = require('gulp-sourcemaps');

gulp.task('3dhop', function() {
	return gulp.src(["./assets/3dviewer/js/spidergl.js", "./assets/3dviewer/js/presenter.js", "./assets/3dviewer/js/ply.js", "./assets/3dviewer/js/trackball_pantilt.js", "./assets/3dviewer/js/trackball_sphere.js","./assets/3dviewer/js/trackball_turntable.js","./assets/3dviewer/js/trackball_turntable_pan.js", "./assets/3dviewer/js/init.js"])
		.pipe(sourcemaps.init())
		.pipe(uglify())
		.pipe(concat('3dviewer.js'))
		.pipe(sourcemaps.write('./'))
		.pipe(gulp.dest("./assets/3dviewer/js/"));
});