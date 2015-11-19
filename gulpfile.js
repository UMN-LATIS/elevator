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


gulp.task('3dhop', function() {
	return gulp.src(["./assets/3dviewer/js/spidergl.js", "./assets/3dviewer/js/presenter.js", "./assets/3dviewer/js/ply.js", "./assets/3dviewer/js/trackball_pantilt.js", "./assets/3dviewer/js/trackball_sphere.js","./assets/3dviewer/js/trackball_turntable.js","./assets/3dviewer/js/trackball_turntable_pan.js", "./assets/3dviewer/js/init.js"])
		.pipe(sourcemaps.init())
		.pipe(uglify())
		.pipe(concat('3dviewer.js'))
		.pipe(sourcemaps.write('./'))
		.pipe(gulp.dest("./assets/3dviewer/js/"));
});


gulp.task('basicFiles', function() {
    return gulp.src(["./assets/js/serializeForm.js", "./assets/js/dateWidget.js", "./assets/js/template.js"])
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(concat('serializeDateTemplate.js'))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest("./assets/minified/"));
});


gulp.task("allJSFilesIndividually", function() {
    return gulp.src('./assets/js/*.js')
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(out('./assets/minifiedjs/{basename}.min{extension}'));

});

gulp.task("allCSSFilesIndividually", function() {
    return gulp.src('./assets/css/*.css')
        // .pipe(sourcemaps.init())
        .pipe(uglifycss())
        // .pipe(sourcemaps.write('./'))
        .pipe(out('./assets/minifiedcss/{basename}.min{extension}'));

});