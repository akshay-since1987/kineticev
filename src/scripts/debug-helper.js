/**
 * Debug helper for Kinetic JavaScript initialization
 * This script adds visibility to the initialization process
 */

// Create debug tracker
window.K2Debug = {
    loadedScripts: [],
    initializedModules: [],
    errors: [],
    
    /**
     * Track script loading
     */
    trackScript: function(scriptName) {
        this.loadedScripts.push({
            name: scriptName,
            time: new Date().toISOString()
        });
        console.log(`✅ Script loaded: ${scriptName}`);
    },
    
    /**
     * Track module initialization
     */
    trackModule: function(moduleName) {
        this.initializedModules.push({
            name: moduleName,
            time: new Date().toISOString()
        });
        console.log(`🚀 Module initialized: ${moduleName}`);
    },
    
    /**
     * Track error
     */
    trackError: function(moduleName, error) {
        this.errors.push({
            module: moduleName,
            error: error,
            time: new Date().toISOString()
        });
        console.error(`❌ Error in ${moduleName}:`, error);
    },
    
    /**
     * Print debug summary
     */
    printSummary: function() {
        console.group('Kinetic JS Debug Summary');
        console.log(`Loaded scripts: ${this.loadedScripts.length}`);
        console.log(`Initialized modules: ${this.initializedModules.length}`);
        console.log(`Errors: ${this.errors.length}`);
        
        if (this.errors.length > 0) {
            console.group('Errors:');
            this.errors.forEach((err, i) => {
                console.log(`${i+1}. ${err.module}: ${err.error}`);
            });
            console.groupEnd();
        }
        
        console.groupEnd();
    }
};

// Add global error handling to catch initialization errors
window.addEventListener('error', function(event) {
    if (window.K2Debug) {
        window.K2Debug.trackError('global', {
            message: event.message,
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno
        });
    }
});

// Track this script
if (window.K2Debug) {
    window.K2Debug.trackScript('debug-helper.js');
}

// Print summary after 3 seconds (after page load)
setTimeout(function() {
    if (window.K2Debug) {
        window.K2Debug.printSummary();
    }
}, 3000);

// Export for ES modules
export const DebugHelper = window.K2Debug;
