var gulp = require('gulp');
var gulputil = require('gulp-util');
var concat = require('gulp-concat');  
var uglify = require('gulp-uglify');  
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var rename = require('gulp-rename');


gulp.task('styles-admin', function() {
	return [
    gulp.src('./src/scss/admin/admin.scss')
		.pipe( sourcemaps.init() )
        .pipe( sass( { outputStyle: 'compressed' } ).on('error', sass.logError) )
        .pipe( sourcemaps.write() )
        .pipe( gulp.dest('./css/admin/') )
	]
});


gulp.task('scripts-admin', function() {
    return [ 
    	gulp.src( [
			'./src/js/admin/nav-menus.js',
		] )
			.pipe( sourcemaps.init() )
			.pipe( uglify().on('error', gulputil.log ) )
//			.pipe( concat('admin.js') )
			.pipe( sourcemaps.write() )
			.pipe( gulp.dest( './js/admin/' ) ),
    ];

});

gulp.task('build', ['styles-admin','scripts-admin'] );


gulp.task('watch', function() {
	// place code for your default task here
	gulp.watch('./src/scss/**/*.scss',['styles-admin']);
	gulp.watch('./src/js/**/*.js',['scripts-admin']);
});

gulp.task('default', ['build','watch']);



