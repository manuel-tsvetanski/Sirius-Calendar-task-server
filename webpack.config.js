const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured by the "encore" command.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
// Directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // Public path used by the web server to access the output path
    .setPublicPath('/build')

/*
 * ENTRY CONFIGURATION
 *
 * Each entry will result in one JavaScript file (e.g. app.js)
 * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
 */
// Main application entry point
.addEntry('app', './assets/app.js')
    // Calendar logic entry point
    .addEntry('calendar', './assets/js/calendar.js')

// Enable splitting of entry chunks for greater optimization
.splitEntryChunks()

// Enables a single runtime chunk for optimal performance
.enableSingleRuntimeChunk()

/*
 * FEATURE CONFIGURATION
 *
 * Enable and configure other features below.
 */
.cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // Enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

// Babel configuration for JavaScript compatibility
.configureBabelPresetEnv((config) => {
    config.useBuiltIns = 'usage';
    config.corejs = '3.23';
})

// Uncomment to enable Sass/SCSS support
//.enableSassLoader()

// Uncomment to enable TypeScript support
//.enableTypeScriptLoader()

// Uncomment to enable React support
//.enableReactPreset()

// Uncomment to add integrity="..." attributes on your script & link tags
//.enableIntegrityHashes(Encore.isProduction())

// Uncomment if you’re having problems with a jQuery plugin
//.autoProvidejQuery()
;

module.exports = Encore.getWebpackConfig();