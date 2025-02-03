import gulp from 'gulp';
// import sass from 'gulp-sass';
// import autoprefixer from 'gulp-autoprefixer';
// import cleanCSS from 'gulp-clean-css';
import uglify from 'gulp-uglify';
import rename from 'gulp-rename';
import concat from 'gulp-concat';
import sourcemaps from 'gulp-sourcemaps';
import changed from 'gulp-changed';
import uglifyCSS from 'gulp-uglifycss';
// import imagemin from 'gulp-imagemin';
import { deleteAsync as del } from 'del';
// Minify and concat 3D viewer JS files
gulp.task('3dhop', function () {
    return gulp.src([
        "./assets/3dviewer/js/spidergl.js",
        "./assets/3dviewer/js/presenter.js",
        "./assets/3dviewer/js/ply.js",
        "./assets/3dviewer/js/trackball_pantilt.js",
        "./assets/3dviewer/js/trackball_sphere.js",
        "./assets/3dviewer/js/trackball_turntable.js",
        "./assets/3dviewer/js/trackball_turntable_pan.js",
        "./assets/3dviewer/js/init.js"
    ])
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(concat('3dviewer.min.js'))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest("./assets/3dviewer/js/"));
});

// Minify and concat WebRTI JS files
gulp.task('webRTI', function () {
    return gulp.src([
        "./assets/webViewer/spidergl/spidergl.js",
        "./assets/webViewer/spidergl/multires.js"
    ])
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(concat('webrti.min.js'))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest("./assets/webViewer/js/"));
});

// Minify and concat basic JS files
gulp.task('basicFiles', function () {
    return gulp.src([
        "./assets/js/serializeForm.js",
        "./assets/js/dateWidget.js",
        "./assets/js/template.js",
        "./assets/js/advancedSearchForm.js",
        "./assets/js/multiselectWidget.js"
    ])
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(concat('serializeDateTemplate.min.js'))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest("./assets/minifiedjs/"));
});

// Minify and concat asset-related JS files
gulp.task('assetMaster', function () {
    return gulp.src(["./assets/js/assetView.js", "./assets/js/drawers.js"])
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(concat('assetMaster.min.js'))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest("./assets/minifiedjs/"));
});

// Minify and concat search-related JS files
gulp.task('searchMaster', function () {
    return gulp.src(["./assets/js/search.js", "./assets/js/searchForm.js"])
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(concat('searchMaster.min.js'))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest("./assets/minifiedjs/"));
});

// Minify individual JS files
gulp.task("allJSFilesIndividually", function () {
    return gulp.src('./assets/js/*.js')
        .pipe(changed('./assets/minifiedjs/', { extension: '.min.js' }))
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(rename({ extname: '.min.js' }))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest("./assets/minifiedjs/"));
});

// Minify individual CSS files
gulp.task("allCSSFilesIndividually", function () {
    return gulp.src('./assets/css/*.css')
        .pipe(changed('./assets/minifiedcss/', { extension: '.min.css' }))
        .pipe(sourcemaps.init())
        .pipe(uglifyCSS())
        .pipe(rename({ extname: '.min.css' }))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest("./assets/minifiedcss/"));
});

// Clean task (optional)
gulp.task('clean', function () {
    return del(['./assets/minifiedjs/*', './assets/minifiedcss/*']);
});

// Default task
gulp.task('default', gulp.series(
    '3dhop', 'webRTI', 'basicFiles', 'assetMaster', 'searchMaster', 
    'allJSFilesIndividually', 'allCSSFilesIndividually'
));

