// copy-main-js.js
const fs = require('fs');
const path = require('path');

// Source and destination paths
const srcFile = path.resolve(__dirname, '../src/dist/js/main.js');
const destDir = path.resolve(__dirname, '../php/js');
const destFile = path.resolve(destDir, 'main.js');

// Create destination directory if it doesn't exist
if (!fs.existsSync(destDir)) {
    fs.mkdirSync(destDir, { recursive: true });
}

// Copy the file
try {
    fs.copyFileSync(srcFile, destFile);
    console.log(`✅ Copied main.js from ${srcFile} to ${destFile}`);
    
    // Also copy the source map if it exists
    const srcMapFile = srcFile + '.map';
    const destMapFile = destFile + '.map';
    
    if (fs.existsSync(srcMapFile)) {
        fs.copyFileSync(srcMapFile, destMapFile);
        console.log(`✅ Copied main.js.map from ${srcMapFile} to ${destMapFile}`);
    }
} catch (err) {
    console.error(`❌ Error copying main.js: ${err.message}`);
}
