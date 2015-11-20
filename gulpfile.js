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
    sourcemaps = require('gulp-sourcemaps');
    foreach = require('gulp-foreach');
    out = require('gulp-out');
    uglifycss = require('gulp-uglifycss');
    changed = require('gulp-changed');


gulp.task('3dhop', function() {
	return gulp.src(["./assets/3dviewer/js/spidergl.js", "./assets/3dviewer/js/presenter.js", "./assets/3dviewer/js/ply.js", "./assets/3dviewer/js/trackball_pantilt.js", "./assets/3dviewer/js/trackball_sphere.js","./assets/3dviewer/js/trackball_turntable.js","./assets/3dviewer/js/trackball_turntable_pan.js", "./assets/3dviewer/js/init.js"])
		.pipe(sourcemaps.init())
		.pipe(uglify())
		.pipe(concat('3dviewer.min.js'))
		.pipe(sourcemaps.write('./'))
		.pipe(gulp.dest("./assets/3dviewer/js/"));
});


gulp.task('basicFiles', function() {
    return gulp.src(["./assets/js/serializeForm.js", "./assets/js/dateWidget.js", "./assets/js/template.js"])
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(concat('serializeDateTemplate.min.js'))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest("./assets/minifiedjs/"));
});

gulp.task('assetMaster', function() {
    return gulp.src(["./assets/js/assetView.js", "./assets/js/drawers.js"])
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(concat('assetMaster.min.js'))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest("./assets/minifiedjs/"));
});

gulp.task('searchMaster', function() {
    return gulp.src(["./assets/js/search.js", "./assets/js/searchForm.js"])
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(concat('searchMaster.min.js'))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest("./assets/minifiedjs/"));
});


gulp.task("allJSFilesIndividually", function() {
    return gulp.src('./assets/js/*.js')
        .pipe(changed('./assets/minifiedjs/', {extension: '.min.js'}))
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(rename({
            extname: '.min.js'
        }))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest("./assets/minifiedjs/"));

});

gulp.task("allCSSFilesIndividually", function() {
    return gulp.src('./assets/css/*.css')
        .pipe(changed('./assets/minifiedcss/', {extension: '.min.css'}))
        .pipe(sourcemaps.init())
        .pipe(uglifycss())
        .pipe(rename({
            extname: '.min.css'
        }))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest("./assets/minifiedcss/"));

});


gulp.task('default', ['3dhop', 'basicFiles', 'basicFiles', 'assetMaster', 'searchMaster', 'allJSFilesIndividually','allCSSFilesIndividually']);



gulp.task('pre-commit', ['default']);

