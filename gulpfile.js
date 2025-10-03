const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const sourcemaps = require('gulp-sourcemaps');
const autoprefixer = require('gulp-autoprefixer').default;
const cleanCSS = require('gulp-clean-css');

const paths = {
  scss: 'src/scss/**/*.scss',
  cssDest: 'assets/css',
};

function styles(){
  return gulp.src('src/scss/main.scss')
    .pipe(sourcemaps.init())
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(cleanCSS())
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.cssDest));
}

function watch(){
  gulp.watch(paths.scss, styles);
}

exports.styles = styles;
exports.watch = watch;
exports.default = gulp.series(styles, watch);