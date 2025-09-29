// scripts/js-watch.js
const esbuild = require("esbuild");
const chokidar = require("chokidar");
const fs = require("fs");
const path = require("path");
const { exec } = require("child_process");

const inputDir = path.resolve(__dirname, "../src/scripts");
const outputDir = path.resolve(__dirname, "../src/dist/js");

function build(filePath) {
    const fileName = path.basename(filePath, ".js");
    const outFile = path.join(outputDir, "main.js");

    esbuild.build({
        entryPoints: [filePath],
        outfile: outFile,
        bundle: true,
        minify: true,
        sourcemap: true,
        target: "es2017",
    }).then(() => {
        console.log(`✅ JS → ${fileName}.min.js`);
    }).catch((err) => {
        console.error(`❌ JS Error in ${filePath}\n`, err.message);
    });
}



if (fs.existsSync(inputDir + "/main.js")) {
    build(path.join(inputDir, "main.js"));
}
// Initial build
// fs.readdirSync(inputDir).forEach((file) => {
//     if (file.endsWith(".js")) {
//         build(path.join(inputDir, file));
//     }
// });

// Watch for changes to any JS file in src/scripts
chokidar.watch(`${inputDir}/**/*.js`).on("change", (changedPath) => {
    console.log(`File changed: ${changedPath}`);
    
    // If main.js changed, build it directly
    if (path.basename(changedPath) === 'main.js') {
        build(changedPath);
    } 
    // If any other file changed but it's imported by main.js, build main.js
    else {
        const mainJsPath = path.join(inputDir, 'main.js');
        if (fs.existsSync(mainJsPath)) {
            build(mainJsPath);
        }
    }
});
