/*jslint node: true */
var gulp = require('gulp'),
    plugins = require('gulp-load-plugins')(),
    del = require('del'),
    watch = require('gulp-watch'),
    runSequence = require('run-sequence'),
    Github = require('github'),
    minimist = require('minimist'),
    Q = require('q'),
    prompt = require('prompt'),
    dateFormat = require('dateformat'),
    babel = require('gulp-babel'),
    p = {
        allFiles: [
            './laterpay/**/*.php',
            './assets_sources/scss/**/*.scss',
            './assets_sources/js/*.js'
        ],
        mainPhpFile: './laterpay/laterpay.php',
        changelogFile: './laterpay/README.txt',
        jsonFiles: ['./composer.json', './package.json'],
        phpFiles: ['./laterpay/**/*.php', '!./laterpay/library/**/*.php'],
        src: {
            scss: './assets_sources/scss/*.scss',
            cssVendor: './assets_sources/css/vendor/*.css',
            js: './assets_sources/js/',
            jsVendor: './assets_sources/js/vendor/',
            svg: './assets_sources/img/**/*.svg',
            png: './assets_sources/img/**/*.png',
            fonts: './assets_sources/fonts/'
        },
        dist: {
            css: './laterpay/assets/css/',
            cssVendor: './laterpay/assets/css/vendor/',
            js: './laterpay/assets/js/',
            jsVendor: './laterpay/assets/js/vendor/',
            img: './laterpay/assets/img/',
            fonts: './laterpay/assets/fonts/'
        },
        distPlugin: './laterpay/',
        distSVN: './svn-working-copy/',
        svnURL: 'http://plugins.svn.wordpress.org/laterpay'
    };
// OPTIONS -------------------------------------------------------------------------------------------------------------
var gulpKnownOptions = {
    string: 'version',
    default: {version: '1.0'}
};
var gulpOptions = minimist(process.argv.slice(2), gulpKnownOptions);
gulpOptions.svn = {};
gulpOptions.git = {};

// TASKS ---------------------------------------------------------------------------------------------------------------
// clean up all files in the target directories
gulp.task('clean', function (cb) {
    return del([
        p.dist.js,
        p.dist.css,
        p.dist.img,
        p.dist.fonts,
        p.distPlugin + 'vendor'
    ], cb);
});

// CSS-related tasks
gulp.task('css-watch', function () {
    gulp.src(p.src.scss)
        .pipe(plugins.sourcemaps.init())
        .pipe(plugins.sass({
            errLogToConsole: true,
            sourceComments: 'normal'
        }))
        // vendorize properties for supported browsers
        .pipe(plugins.autoprefixer('last 3 versions', '> 2%', 'ff > 23', 'ie > 8'))
        .on('error', plugins.notify.onError())
        .pipe(plugins.sourcemaps.write('./maps'))                               // write sourcemaps
        .pipe(gulp.dest(p.dist.css));                                            // move to target folder
});

gulp.task('css-build', function () {
    // build vendor styles
    gulp.src(p.src.cssVendor)
        .pipe(plugins.csso())
        .pipe(gulp.dest(p.dist.cssVendor));

    gulp.src(p.src.scss)
    //     .pipe(plugins.sourcemaps.init())
        .pipe(plugins.sass({
            errLogToConsole: true,
            sourceComments: 'normal'
        }).on('error', plugins.notify.onError()))
    //     // vendorize properties for supported browsers
        .pipe(plugins.autoprefixer('last 3 versions', '> 2%', 'ff > 23', 'ie > 8'))
        .pipe(plugins.csso())                                            // compress
        .pipe(gulp.dest(p.dist.css));                                            // move to target folder
});

// Javascript-related tasks
gulp.task('js-watch', function () {
    gulp.src(p.src.js + '*.js')
        .pipe(plugins.cached('hinting'))                                        // only process modified files
        .pipe(plugins.jshint('.jshintrc'))
        .pipe(plugins.jshint.reporter(plugins.stylish))
        .pipe(plugins.sourcemaps.init())
        .pipe(plugins.sourcemaps.write('./maps'))                               // write sourcemaps
        .pipe(gulp.dest(p.dist.js));                                             // move to target folder
});

gulp.task('js-build', function () {
    // build vendor files
    gulp.src(p.src.jsVendor + '*.js')
    //    .pipe(plugins.uglify())
        .pipe(gulp.dest(p.dist.jsVendor));

    gulp.src(p.src.js + '*.js')
        .pipe(plugins.jshint('.jshintrc'))
        .pipe(plugins.jshint.reporter(plugins.stylish))
        .pipe(babel({
            presets: ['@babel/env']
        }))
        .pipe(plugins.uglify())                                                 // compress with uglify
        .pipe(gulp.dest(p.dist.js));                                             // move to target folder
});

gulp.task('js-format', function () {
    gulp.src(p.src.js + '*.js')
        .pipe(plugins.sourcemaps.init())
        .pipe(plugins.prettify({
            config: '.jsbeautifyrc',
            mode: 'VERIFY_AND_WRITE'
        }))
        .pipe(plugins.sourcemaps.write('./maps'))                           // write sourcemaps
        .pipe(gulp.dest(p.src.js));
});

// Image-related tasks
gulp.task('img-build-svg', function () {
    gulp.src(p.src.svg)
        .pipe(plugins.svgmin())                                                 // compress with svgmin
        .pipe(gulp.dest(p.dist.img));                                            // move to target folder
});

gulp.task('img-build-png', function () {
    gulp.src(p.src.png)
        .pipe(plugins.tinypng('5Y0XuX5OMOhgB-vRqRc8i41ABKv3amul'))              // compress with TinyPNG
        .pipe(gulp.dest(p.dist.img));                                            // move to target folder
});

gulp.task('img-build', function () {
    var deferred = Q.defer();
    runSequence(['img-build-svg', 'img-build-png'], function (error) {
        if (error) {
            deferred.reject(error);
            console.log(error.message);
        } else {
            deferred.resolve();
        }
    });

    return deferred.promise;
});

gulp.task('fonts-build', function () {
    gulp.src(p.src.fonts + '*')
        .pipe(gulp.dest(p.dist.fonts));
});

// ensure consistent whitespace etc. in files
gulp.task('fileformat', function () {
    return gulp.src(p.allFiles)
        .pipe(plugins.lintspaces({
            indentation: 'spaces',
            spaces: 4,
            trailingspaces: true,
            newline: true,
            newlineMaximum: 2
        }))
        .pipe(plugins.lintspaces.reporter());
});

// check PHP coding standards
gulp.task('sniffphp', function () {
    return gulp.src(p.phpFiles)
        .pipe(plugins.phpcs({
            bin: '/usr/local/bin/phpcs',
            standard: 'WordPress',
            warningSeverity: 0
        }))
        .pipe(plugins.phpcs.reporter('log'));
});


// COMMANDS ------------------------------------------------------------------------------------------------------------
gulp.task('default', ['clean', 'img-build', 'css-watch', 'js-watch'], function () {
    // watch for changes
    gulp.watch(p.allFiles, ['fileformat']);
    gulp.watch(p.src.scss, ['css-watch']);
    gulp.watch(p.src.js + '*.js', ['js-watch']);
});

gulp.task('watch', function () {
    watch([p.src.js, p.src.jsVendor], function (event, cb) {
        gulp.start('js-build');
    });
    watch([p.src.scss, p.src.cssVendor], function (event, cb) {
        gulp.start('style-build');
    });
});

// check code quality before git commit
gulp.task('precommit-css', function () {
    return gulp.src(p.dist.css + '*.css')
        .pipe(plugins.csslint())
        .pipe(plugins.csslint.reporter());
});

gulp.task('precommit-js', function () {
    return gulp.src(p.src.js + '*.js')
        .pipe(plugins.jshint('.jshintrc'))
        .pipe(plugins.jshint.reporter(plugins.stylish));
});

gulp.task('precommit', ['sniffphp', 'js-format'], function () {
    var deferred = Q.defer();
    runSequence(['precommit-css', 'precommit-js'], function (error) {
        if (error) {
            deferred.reject(error.message);
            console.log(error.message);
        } else {
            deferred.resolve();
        }
    });
    return deferred.promise;
});

// build project for release
gulp.task('build', ['clean'], function () {
    var deferred = Q.defer();
    runSequence(['img-build', 'css-build', 'js-build', 'fonts-build'], function (error) {
        if (error) {
            deferred.reject(error.message);
            console.log(error.message);
        } else {
            deferred.resolve();
        }
    });
    return deferred.promise;
});

// RELEASE -------------------------------------------------------------------------------------------------------------

// common functions
var getMilestoneNumber = function () {
        var github = new Github({
                version: '3.0.0'
            }),
            deferred = Q.defer(),
            options = {
                'user': 'laterpay',
                'repo': 'laterpay-wordpress-plugin',
                'state': 'open'
            };
        github.issues.getAllMilestones(options, function (error, data) {
            if (!error) {
                if (data[0]) {
                    deferred.resolve({milestone: data[0]});
                    return;
                }
            }
            var err = 'Error has been appeared while getting milestone';
            console.log(error);
            deferred.reject(err);
        });
        return deferred.promise;
    },
    getMilestoneIssues = function (result) {
        var github = new Github({
                version: '3.0.0'
            }),
            deferred = Q.defer(),
            options = {
                'user': 'laterpay',
                'repo': 'laterpay-wordpress-plugin',
                'milestone': result.milestone.number,
                'state': 'all'
            };
        github.issues.repoIssues(options, function (error, data) {
            if (!error) {
                result.issues = data;
                deferred.resolve(result);
            } else {
                var err = 'Error has been appeared while getting issues';
                console.log(error);
                deferred.reject(err);
            }
        });
        return deferred.promise;
    },
    promptUsernamePassword = function (namespace) {
        var schema = {
                properties: {
                    username: {
                        'default': 'laterpay',
                        'required': true
                    },
                    password: {
                        'hidden': true
                    }
                }
            },
            deferred = Q.defer();
        prompt.get(schema, function (error, result) {
            if (!error) {
                gulpOptions[namespace].username = result.username;
                gulpOptions[namespace].password = result.password;
                deferred.resolve();
            } else {
                var err = 'Error has been appeared while getting ' + namespace + ' Username and Password';
                console.log(error);
                deferred.reject(err);
            }
        });
        return deferred.promise;
    },
    svnPropset = function (type, path) {
        var deferred = Q.defer();
        plugins.svn.exec({
            cwd: p.distSVN,
            args: 'propset svn:mime-type ' + type + ' ' + path
        }, function (err) {
            if (err) {
                console.log(err);
                deferred.reject(err);
            } else {
                deferred.resolve();
            }
        });
        return deferred.promise;
    };
// RELEASE TASKS
gulp.task('changelog', function () {
    return getMilestoneNumber()
        .then(getMilestoneIssues)
        .then(function (result) {
            if (result.issues) {
                result.formated = result.issues.map(function (issue) {
                    return '* ' + issue.title;
                });
                result.formated = result.formated.join('\n');
                return result;
            }
        })
        .then(function (result) {
            var changelog = [
                '$1== ',
                gulpOptions.version,
                '( ', dateFormat(new Date(), 'mmmm d, yyyy'), ' )',
                ': ' + result.milestone.description,
                ' ==\n',
                result.formated,
                '\n\n'];
            return gulp.src(p.changelogFile)
                .pipe(plugins.replace(/(==\s*Changelog\s*==\n*)/g, changelog.join('')))
                .pipe(gulp.dest(p.distPlugin));
        });

});

gulp.task('bump-version-json', function () {
    return gulp.src(p.jsonFiles)
        .pipe(plugins.bump({version: gulpOptions.version}).on('error', plugins.util.log))
        .pipe(gulp.dest('./'));
});

gulp.task('bump-version-php', function () {
    return gulp.src([p.mainPhpFile])
        .pipe(plugins.replace(/Version:\s*(.*)/g, 'Version: ' + gulpOptions.version))
        .pipe(gulp.dest(p.distPlugin));
});

gulp.task('bump-version', function () {
    var deferred = Q.defer();
    runSequence(['bump-version-json', 'bump-version-php'], function (error) {
        if (error) {
            deferred.reject(error.message);
            console.log(error.message);
        } else {
            deferred.resolve();
        }
    });
    return deferred.promise;
});

gulp.task('composer', function () {
    return plugins.composer('update', {'no-autoloader': true, 'no-dev': true});
});

gulp.task('github-release', function () {
    return promptUsernamePassword('git')
        .then(getMilestoneNumber)
        .then(getMilestoneIssues)
        .then(function (result) {
            if (result.issues) {
                result.formated = result.issues.map(function (issue) {
                    return '* ' + issue.title;
                });
                result.formated = result.formated.join('\n');
                return result;
            }
        })
        .then(function (result) {
            var github = new Github({
                    version: '3.0.0'
                }),
                deferred = Q.defer(),
                options = {
                    'owner': 'laterpay',
                    'repo': 'laterpay-wordpress-plugin',
                    'tag_name': 'v' + gulpOptions.version,
                    'name': result.milestone.description,
                    'body': result.formated
                };
            prompt.start();

            github.authenticate({
                type: 'basic',
                username: gulpOptions.git.username,
                password: gulpOptions.git.password
            });
            github.releases.createRelease(options, function (error, data) {
                if (!error) {
                    result.issues = data;
                    deferred.resolve(result);
                } else {
                    var err = 'Error has been appeared while creating github release';
                    console.log(error);
                    deferred.reject(err);
                }
            });
        });
});

gulp.task('git-commit-changes', function () {
    return gulp.src('.')
        .pipe(plugins.git.commit('[Prerelease] Bumped version number ' + gulpOptions.version));
});

gulp.task('git-push-changes', function (cb) {
    plugins.git.push('origin', 'master', cb);
});

gulp.task('git-create-new-tag', function (cb) {
    plugins.git.tag('v' + gulpOptions.version, 'Created Tag for version: ' + gulpOptions.version, function (error) {
        if (error) {
            return cb(error);
        }
        plugins.git.push('origin', 'master', {args: '--tags'}, cb);
    });
});

// SVN tasks
// Run svn add
gulp.task('svn-add', function () {
    var svnChangeList = function (types) {
            var deferred = Q.defer();
            console.log('Started SVN changelist...');
            plugins.svn.exec({
                args: 'st | grep "^[' + types + ']" | cut -c9-',
                cwd: p.distSVN
            }, function (err, response) {
                if (err) {
                    console.log(err);
                    deferred.reject(err);
                } else {
                    var data = [];
                    if (response) {
                        data = response.split(/\r\n|\r|\n/g);
                    }
                    deferred.resolve(data);
                }
            });
            return deferred.promise;
        },
        svnAdd = function () {
            var deferred = Q.defer();
            console.log('Started SVN adding of the new and modified files...');
            plugins.svn.add('*', {
                args: '--force',
                cwd: p.distSVN
            }, function (err) {
                if (err) {
                    console.log(err);
                    deferred.reject(err);
                } else {
                    deferred.resolve();
                }
            });
            return deferred.promise;
        },
        svnDelete = function (file) {
            var deferred = Q.defer();
            plugins.svn.delete(file, {
                cwd: p.distSVN
            }, function (err) {
                if (err) {
                    console.log(err);
                    deferred.reject(err);
                } else {
                    deferred.resolve();
                }
            });
            return deferred.promise;
        },
        svnDeleteMissed = function (data) {
            var deferred = Q.defer(),
                chain;
            console.log('Started SVN removing of the missed files...');
            data.forEach(function (file) {
                if (!file) {
                    return;
                }
                if (!chain) {
                    chain = svnDelete(file);
                } else {
                    chain = chain.then(function (file) {
                        return function () {
                            return svnDelete(file);
                        };
                    }(file));
                }
            });
            if (chain) {
                chain.done(function () {
                    deferred.resolve();
                });
                chain.catch(function () {
                    deferred.reject('Error while removing missed files!');
                });
            } else {
                deferred.resolve();
            }

            return deferred.promise;
        };
    return svnAdd()
        .then(function () {
            return svnChangeList('!');
        })
        .then(svnDeleteMissed);

});

// Run svn commit
gulp.task('svn-commit', ['svn-prompt-credentials'], function () {
    var deferred = Q.defer();
    plugins.svn.commit('Release ' + gulpOptions.version, {
        cwd: p.distSVN,
        username: gulpOptions.svn.username,
        password: gulpOptions.svn.password
    }, function (err) {
        if (err) {
            console.log(err);
            deferred.reject(err);
        } else {
            deferred.resolve();
        }
    });
    return deferred.promise;
});

// Run svn tag
gulp.task('svn-tag', ['svn-prompt-credentials'], function () {
    var deferred = Q.defer();
    plugins.svn.tag('v' + gulpOptions.version, 'Release ' + gulpOptions.version, {
        cwd: p.distSVN,
        projectRoot: p.svnURL,
        username: gulpOptions.svn.username,
        password: gulpOptions.svn.password
    }, function (err) {
        if (err) {
            console.log(err);
            deferred.reject(err);
        } else {
            deferred.resolve();
        }
    });
    return deferred.promise;
});

gulp.task('svn-prompt-credentials', function () {
    if (!gulpOptions.svn.username || !gulpOptions.svn.password) {
        return promptUsernamePassword('svn');
    }
});

// clean up all files in the target directories
gulp.task('svn-clean', function (cb) {
    return del([
        p.distSVN
    ], cb);
});
// clean up all files in the target directories
gulp.task('svn-clean-trunk', function (cb) {
    return del([
        p.distSVN + 'trunk'
    ], cb);
});
gulp.task('svn-copy-laterpay', function () {
    return gulp.src(p.distPlugin + '**/*')
        .pipe(gulp.dest(p.distSVN + 'trunk'));
});
gulp.task('svn-checkout', function () {
    var deferred = Q.defer();
    console.log('Fetching SVN repo[' + p.svnURL + ']...');
    plugins.svn.checkout(p.svnURL, p.distSVN, {
        username: gulpOptions.svn.username,
        password: gulpOptions.svn.password
    }, function (err) {
        if (err) {
            console.log(err);
            deferred.reject(err);
        } else {
            deferred.resolve();
        }
    });
    return deferred.promise;
});

gulp.task('svn-fix-assets', function () {
    return svnPropset('image/jpeg', p.distSVN + 'assets/*.jpg')
        .then(function () {
            return svnPropset('image/jpeg', p.distSVN + 'assets/*.jpeg');
        })
        .then(function () {
            return svnPropset('image/png', p.distSVN + 'assets/*.png');
        });
});

gulp.task('svn-release', function () {
    var deferred = Q.defer();
    runSequence(
        'svn-clean',
        'svn-checkout',
        'svn-clean-trunk',
        'svn-copy-laterpay',
        'svn-add',
        'svn-commit',
        'svn-tag',
        'svn-clean',
        function (error) {
            if (error) {
                deferred.reject(error.message);
                console.log(error.message);
            } else {
                deferred.resolve();
                console.log('SVN deployment FINISHED');
            }
        });
    return deferred.promise;
});

gulp.task('release:production', function () {
    var deferred = Q.defer();
    runSequence(
        'build',
        'bump-version',
        'changelog',
        'composer',
        'git-commit-changes',
        'git-push-changes',
        'git-create-new-tag',
        'github-release',
        'svn-release',
        function (error) {
            if (error) {
                deferred.reject(error.message);
                console.log(error.message);
            } else {
                deferred.resolve();
                console.log('RELEASE FINISHED SUCCESSFULLY');
            }
        });
    return deferred.promise;
});
