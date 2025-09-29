// scripts/dev.js
const browserSync = require("browser-sync").create();
const { spawn } = require("child_process");
const fs = require("fs");
const path = require("path");
const https = require("https");

// Function to copy Font Awesome fonts
function copyFontAwesomeFonts() {
    const sourceDir = path.resolve(__dirname, "../node_modules/font-awesome/fonts");
    const targetDir = path.resolve(__dirname, "../src/public/fonts");

    // Check if source directory exists
    if (!fs.existsSync(sourceDir)) {
        console.log("⚠️  Font Awesome fonts not found in node_modules");
        return;
    }

    // Create target directory if it doesn't exist
    if (!fs.existsSync(targetDir)) {
        fs.mkdirSync(targetDir, { recursive: true });
        console.log("📁 Created public/fonts directory");
    }

    // Copy all font files
    try {
        const fontFiles = fs.readdirSync(sourceDir);
        fontFiles.forEach(file => {
            const sourcePath = path.join(sourceDir, file);
            const targetPath = path.join(targetDir, file);

            fs.copyFileSync(sourcePath, targetPath);
            console.log(`✅ Copied font: ${file}`);
        });
        console.log("🎯 Font Awesome fonts copied successfully!");
    } catch (error) {
        console.error("❌ Error copying fonts:", error.message);
    }
}

// Copy fonts on startup
copyFontAwesomeFonts();

// Function to handle pincode API requests
function handlePincodeAPI(req, res) {
    const url = new URL(req.url, `http://${req.headers.host}`);
    const pincode = url.searchParams.get('pincode');

    console.log('📍 Pincode lookup request:', pincode);

    if (!pincode) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: false, message: 'Pincode parameter required' }));
        return;
    }

    // Try multiple geocoding services
    tryGeocoding(pincode)
        .then(result => {
            console.log('✅ Geocoding result:', result);
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify(result));
        })
        .catch(error => {
            console.error('❌ Geocoding error:', error);
            res.writeHead(500, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, message: 'Internal server error' }));
        });
}

// Geocoding functions
async function tryGeocoding(pincode) {
    // Try OpenCage first (most reliable for Indian pincodes)
    try {
        return await tryOpenCageGeocoding(pincode);
    } catch (error) {
        console.log('OpenCage failed, trying Google...');
    }

    // Try Google Geocoding
    try {
        return await tryGoogleGeocoding(pincode);
    } catch (error) {
        console.log('Google failed, trying Nominatim...');
    }

    // Try Nominatim (free fallback)
    try {
        return await tryNominatimGeocoding(pincode);
    } catch (error) {
        console.log('All geocoding services failed');
    }

    // If all fail, return partial matches
    return getPartialPincodeMatches(pincode);
}

async function tryOpenCageGeocoding(pincode) {
    // For demo purposes, we'll use a simple approach
    // In production, you'd want to use an API key
    const OPENCAGE_API_KEY = process.env.OPENCAGE_API_KEY || '';

    if (!OPENCAGE_API_KEY) {
        throw new Error('OpenCage API key not available');
    }

    return new Promise((resolve, reject) => {
        const url = `https://api.opencagedata.com/geocode/v1/json?q=${pincode},India&key=${OPENCAGE_API_KEY}&limit=5`;

        https.get(url, (response) => {
            let data = '';
            response.on('data', chunk => data += chunk);
            response.on('end', () => {
                try {
                    const result = JSON.parse(data);
                    if (result.results && result.results.length > 0) {
                        const suggestions = result.results.map(item => {
                            const components = item.components;
                            return {
                                pincode: components.postcode || pincode,
                                city: components.city || components.town || components.village || 'Unknown',
                                state: components.state || 'Unknown',
                                area: components.suburb || components.neighbourhood || components.village || 'Main Area'
                            };
                        });
                        resolve({ success: true, data: suggestions });
                    } else {
                        reject(new Error('No results found'));
                    }
                } catch (error) {
                    reject(error);
                }
            });
        }).on('error', reject);
    });
}

async function tryGoogleGeocoding(pincode) {
    const GOOGLE_API_KEY = 'AIzaSyA-2N9fbAPu2cWVLNGYu0qWL8Gs1Xu3QTw'; // Your existing API key

    return new Promise((resolve, reject) => {
        const url = `https://maps.googleapis.com/maps/api/geocode/json?address=${pincode},India&key=${GOOGLE_API_KEY}`;

        https.get(url, (response) => {
            let data = '';
            response.on('data', chunk => data += chunk);
            response.on('end', () => {
                try {
                    const result = JSON.parse(data);
                    if (result.results && result.results.length > 0) {
                        const suggestions = result.results.map(item => {
                            const components = {};
                            item.address_components.forEach(comp => {
                                if (comp.types.includes('postal_code')) {
                                    components.postcode = comp.long_name;
                                }
                                if (comp.types.includes('locality') || comp.types.includes('administrative_area_level_2')) {
                                    components.city = comp.long_name;
                                }
                                if (comp.types.includes('administrative_area_level_1')) {
                                    components.state = comp.long_name;
                                }
                                if (comp.types.includes('sublocality') || comp.types.includes('neighborhood')) {
                                    components.area = comp.long_name;
                                }
                            });

                            return {
                                pincode: components.postcode || pincode,
                                city: components.city || 'Unknown',
                                state: components.state || 'Unknown',
                                area: components.area || 'Main Area'
                            };
                        });
                        resolve({ success: true, data: suggestions });
                    } else {
                        reject(new Error('No results found'));
                    }
                } catch (error) {
                    reject(error);
                }
            });
        }).on('error', reject);
    });
}

async function tryNominatimGeocoding(pincode) {
    return new Promise((resolve, reject) => {
        const url = `https://nominatim.openstreetmap.org/search?q=${pincode},India&format=json&addressdetails=1&limit=5`;

        https.get(url, (response) => {
            let data = '';
            response.on('data', chunk => data += chunk);
            response.on('end', () => {
                try {
                    const result = JSON.parse(data);
                    if (result && result.length > 0) {
                        const suggestions = result.map(item => {
                            const addr = item.address || {};
                            return {
                                pincode: addr.postcode || pincode,
                                city: addr.city || addr.town || addr.village || 'Unknown',
                                state: addr.state || 'Unknown',
                                area: addr.suburb || addr.neighbourhood || 'Main Area'
                            };
                        });
                        resolve({ success: true, data: suggestions });
                    } else {
                        reject(new Error('No results found'));
                    }
                } catch (error) {
                    reject(error);
                }
            });
        }).on('error', reject);
    });
}

function getPartialPincodeMatches(pincode) {
    // Fallback: provide some sample suggestions based on pincode pattern
    const suggestions = [];

    // For Maharashtra pincodes (4xxxxx)
    if (pincode.startsWith('4')) {
        suggestions.push({
            pincode: pincode,
            city: 'Mumbai',
            state: 'Maharashtra',
            area: 'Central Mumbai'
        });
    }
    // For Delhi pincodes (1xxxxx)
    else if (pincode.startsWith('1')) {
        suggestions.push({
            pincode: pincode,
            city: 'New Delhi',
            state: 'Delhi',
            area: 'Central Delhi'
        });
    }
    // For Bangalore pincodes (5xxxxx)
    else if (pincode.startsWith('5')) {
        suggestions.push({
            pincode: pincode,
            city: 'Bangalore',
            state: 'Karnataka',
            area: 'Central Bangalore'
        });
    }
    // Generic fallback
    else {
        suggestions.push({
            pincode: pincode,
            city: 'Unknown City',
            state: 'Unknown State',
            area: 'Main Area'
        });
    }

    return { success: true, data: suggestions };
}

// Watch & build SCSS
spawn("node", ["scripts/sass-watch.js"], {
    stdio: "inherit"
});

// Watch & build JS
spawn("node", ["scripts/js-watch.js"], {
    stdio: "inherit"
});

// Serve using BrowserSync
browserSync.init({
    proxy: "http://local.kineticev.in",
    port: 3000,
    files: [
        "src/dist/**/*.{css,js}",
        "php/**/*.php"
    ],
    rewriteRules: [
        {
            match: /\/(css|js)\/(main)\.(css|js)\/(.*)/,
            replacement: "http://localhost:3000/$1/$2.$3"
        }
    ],
    open: 'external',
    notify: true,
    reloadDelay: 200,
    ghostMode: false,
    serveStatic: [
        {
            route: "/-",
            dir: "src/public"
        },
        {
            route: "/css",
            dir: "src/dist/css"
        },
        {
            route: "/js",
            dir: "src/dist/js"
        }
    ],
    middleware: [
        {
            route: "/api/pincode-lookup.php",
            handle: handlePincodeAPI
        }
    ]
});
