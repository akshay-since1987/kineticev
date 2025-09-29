// scripts/check-sass-compile.js
const sass = require("sass");
const path = require("path");
const fs = require("fs");

const inputFile = path.resolve(__dirname, "../src/scss/main.scss");
const outputFile = path.resolve(__dirname, "../check-main.css");

try {
  console.log(`Attempting to compile ${inputFile}...`);
  
  const result = sass.compile(inputFile, {
    style: "expanded", // Use expanded style for easier debugging
    sourceMap: false,
    quietDeps: false, // Show all warnings
    loadPaths: ['node_modules']
  });
  
  fs.writeFileSync(outputFile, result.css);
  console.log(`✅ Successfully compiled to ${outputFile}`);
} catch (err) {
  console.error(`❌ SCSS Compilation Error:`);
  console.error(err.formatted || err.message);
}
