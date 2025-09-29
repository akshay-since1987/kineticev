// scripts/sass-watch.js
const sass = require("sass");
const chokidar = require("chokidar");
const fs = require("fs");
const path = require("path");

const inputDir = path.resolve(__dirname, "../src/scss");
const outputDir = path.resolve(__dirname, "../src/dist/css");

function compile(filePath) {
    const fileName = path.basename(filePath, ".scss");
    const outFile = path.join(outputDir, fileName + ".css");

    try {
        const result = sass.compile(filePath, {
            style: "compressed",
            sourceMap: true,
            quietDeps: true, // Suppress warnings from dependencies
            silenceDeprecations: ['slash-div', 'import'], // Suppress slash-div and import deprecation warnings
            loadPaths: ['node_modules'], // Allow importing from node_modules
            charset: false, // Don't add @charset to output
        });

        fs.mkdirSync(outputDir, { recursive: true });
        fs.writeFileSync(outFile, result.css);
        if (result.sourceMap) {
            fs.writeFileSync(outFile + ".map", JSON.stringify(result.sourceMap));
        }
        console.log(`✅ SCSS → ${fileName}.css`);
    } catch (err) {
        console.error(`❌ SCSS Error in ${filePath}\n`, err.formatted || err.message);
    }
}

// Initial build
// fs.readdirSync(inputDir).forEach((file) => {
//     if (file.endsWith(".scss")) {
//         compile(path.join(inputDir, file));
//     }
// });


if (fs.existsSync(inputDir + "/main.scss")) {
    compile(path.join(inputDir, "main.scss"));
}

chokidar.watch(`${inputDir}/**/*.scss`).on("change", function (filePath) {
    if (filePath.endsWith(".scss")) {
        compile(path.join(inputDir, "main.scss"));
    }
});
