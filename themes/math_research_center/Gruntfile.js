/**
 * @file
 */
module.exports = function(grunt) {

  // This is where we configure each task that we'd like to run.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    watch: {
      // This is where we set up all the tasks we'd like grunt to watch for changes.
      scripts: {
        files: ['js/source/**/*.js'],
        tasks: ['uglify', 'drush:ccall'],
        options: {
          spawn: false,
        },
      },
      images: {
        files: ['images/source/*.{png,jpg,gif}'],
        tasks: ['imagemin'],
        options: {
          spawn: false,
        }
      },
      vector: {
        files: ['images/source/**/*.svg'],
        tasks: ['svgmin'],
        options: {
          spawn: false,
        }
      },
      css: {
        files: ['scss/**/*.scss', 'patterns/**/**/scss/*.scss', 'examples/**/**/scss/*.scss'],
        tasks: ['sass'],
        options: {
          interrupt: true
        }
      },
      twig: {
        files: ['templates/**/*.html.twig'],
        tasks: ['uglify', 'svgmin', 'imagemin', 'sass', 'drush:ccall']
      }
    },
    uglify: {
      // This is for minifying all of our scripts.
      options: {
        sourceMap: true,
        mangle: false
      },
      my_target: {
        files: [{
          expand: true,
          cwd: 'js/source',
          src: '{,*/}*.js',
          dest: 'js/build'
        }]
      }
    },
    imagemin: {
      // This will optimize all of our images for the web.
      dynamic: {
        files: [{
          expand: true,
          cwd: 'img/source/',
          src: ['{,*/}*.{png,jpg,gif}' ],
          dest: 'img/optimized/'
        }]
      }
    },
    svgmin: {
      options: {
        plugins: [{
          removeViewBox: false
        }, {
          removeUselessStrokeAndFill: false
        }]
      },
      dist: {
        files: [{
          expand: true,
          cwd: 'images/source/',
          src: ['{,*/}*.svg' ],
          dest: 'images/optimized/'
        }]
      }
    },
    sass: {
      // This will compile all of our sass files
      // Additional configuration options can be found at https://github.com/sindresorhus/grunt-sass
      options: {
        includePaths: [
          "scss",
          "node_modules/bourbon/core",
          "node_modules/bourbon-neat/core",
          "node_modules/font-awesome/scss",
          "node_modules/neat-omega",
          "node_modules/decanter/core",
          "node_modules",
          // Parent theme directory.
          "../",
          "../stanford_basic/scss"
        ],
        sourceMap: true,
        // This controls the compiled css and can be changed to nested, compact or compressed.
        outputStyle: 'compressed',
        precision: 10
      },
      dist: {
        files: {
          // Files are compiled individually so they may be included
          // conditionally using logic built in to the theme template or module.

          // BASE
          'scss/node/stanford_visitor.css':                     'css/node/stanford_visitor',

          // PATTERNS
          'patterns/atoms/date-stacked/css/date-stacked.css':                   'patterns/atoms/date-stacked/scss/date-stacked.scss',
          'patterns/molecules/event-card/css/event-card.css':                   'patterns/molecules/event-card/scss/event-card.scss',
          'patterns/molecules/featured-event-card/css/featured-event-card.css': 'patterns/molecules/featured-event-card/scss/featured-event-card.scss',
          'patterns/molecules/news-card/css/news-card.css':                     'patterns/molecules/news-card/scss/news-card.scss',
          'patterns/molecules/postcard/css/postcard.css':                       'patterns/molecules/postcard/scss/postcard.scss',
          'patterns/molecules/postcard-horizontal/css/postcard-horizontal.css': 'patterns/molecules/postcard-horizontal/scss/postcard-horizontal.scss',
          'patterns/templates/node-event/css/node-event.css':                   'patterns/templates/node-event/scss/node-event.scss',
          'patterns/templates/node-news/css/node-news.css':                     'patterns/templates/node-news/scss/node-news.scss',
          'patterns/templates/node-news/css/node-visitor.css':                     'patterns/templates/node-news/scss/node-visitor.scss',

          // EXAMPLES
          'examples/atoms/date-stacked/css/date-stacked.css':                   'examples/atoms/date-stacked/scss/date-stacked.scss',
          'examples/molecules/event-card/css/event-card.css':                   'examples/molecules/event-card/scss/event-card.scss',
          'examples/molecules/featured-event-card/css/featured-event-card.css': 'examples/molecules/featured-event-card/scss/featured-event-card.scss',
          'examples/molecules/news-card/css/news-card.css':                     'examples/molecules/news-card/scss/news-card.scss',
          'examples/molecules/postcard/css/postcard.css':                       'examples/molecules/postcard/scss/postcard.scss',
          'examples/molecules/postcard-horizontal/css/postcard-horizontal.css': 'examples/molecules/postcard-horizontal/scss/postcard-horizontal.scss',
          'examples/templates/event/css/event.css':                             'examples/templates/event/scss/event.scss',
          'examples/templates/news/css/news.css':                               'examples/templates/news/scss/news.scss'
        }
      }
    },
    drush: {
      ccall: {
        args: ['cache-rebuild', 'all']
      }
    },
    browserSync: {
      dev: {
        bsFiles: {
          src : [
            'css/**/*.css',
            'templates/**/*.twig',
            'images/optimized/**/*.{png,jpg,gif,svg}',
            'js/build/**/*.js',
            '*.theme'
          ]
        },
        options: {
          watchTask: true,
          // reloadDelay: 1000,
          // reloadDebounce: 500,
          reloadOnRestart: true,
          logConnections: true,
          injectChanges: false // Depends on enabling the link_css module
        }
      }
    },
    availabletasks: {
      tasks: {
        options: {
          filter: "include",
          tasks: [
            'browserSync', 'imagemin', 'sass', 'svgmin', 'uglify', 'watch', 'devmode'
          ]
        }
      }
    }
  });

  // This is where we tell Grunt we plan to use this plug-in.
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-imagemin');
  grunt.loadNpmTasks('grunt-svgmin');
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-browser-sync');
  grunt.loadNpmTasks('grunt-available-tasks');
  grunt.loadNpmTasks('grunt-drush');

  // My tasks.
  grunt.registerTask('devmode', "Watch and BrowserSync all in one.", ['drush', 'browserSync', 'watch']);

  // This is where we tell Grunt what to do when we type "grunt" into the terminal.
  // Note: if you'd like to run and of the tasks individually you can do so by typing 'grunt mytaskname' alternatively
  // you can type 'grunt watch' to automatically track your files for changes.
  grunt.registerTask('default', ['availabletasks']);
};
