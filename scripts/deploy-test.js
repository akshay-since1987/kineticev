#!/usr/bin/env node

/**
 * KineticEV Test Environment Deployment Script
 * Builds and deploys the application to test server via SFTP
 * Reads configuration from package.json
 */

const Client = require('ssh2-sftp-client');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Load configuration from package.json
const packageJson = JSON.parse(fs.readFileSync('./package.json', 'utf8'));
const sftpConfig = packageJson.KINETICEV?.sftp?.test;

if (!sftpConfig) {
    console.error('❌ SFTP test configuration not found in package.json');
    console.log('Expected structure: KINETICEV.sftp.test in package.json');
    process.exit(1);
}

// Configuration
const config = {
    server: {
        host: sftpConfig.host,
        username: sftpConfig.username,
        password: sftpConfig.password,
        port: sftpConfig.port || 22,
        remotePath: sftpConfig.remotePath || '/public_html/'
    },
    localPaths: {
        php: './php',
        srcPublic: './src/public',
        srcDist: './src/dist'
    },
    excludeFiles: [
        '.git',
        'node_modules',
        '.env',
        '.env.local',
        'composer.lock',
        'package-lock.json',
        'logs/*.log',
        'vendor/bin',
        '*.tmp',
        '*.cache'
    ]
};

class TestDeployer {
    constructor() {
        this.client = new Client();
    }

    async deploy() {
        console.log('🚀 Starting KineticEV Test Deployment...');
        console.log(`📍 Target: ${config.server.host}:${config.server.port}`);
        console.log(`📁 Remote path: ${config.server.remotePath}\n`);
        
        try {
            // Step 1: Build assets
            await this.buildAssets();
            
            // Step 2: Connect to SFTP
            await this.connectSFTP();
            
            // Step 3: Deploy files
            await this.deployFiles();
            
            console.log('\n✅ Test deployment completed successfully!');
            console.log(`📍 Application available at: https://${config.server.host}`);
            
        } catch (error) {
            console.error('\n❌ Deployment failed:', error.message);
            throw error;
        } finally {
            await this.client.end();
        }
    }

    async buildAssets() {
        console.log('📦 Building assets...');
        
        try {
            // Install dependencies if needed
            // if (!fs.existsSync('./node_modules')) {
            //     console.log('Installing npm dependencies...');
            //     execSync('npm install', { stdio: 'inherit' });
            // }
            
            // Build CSS and JS
            // console.log('Building CSS and JS...');
            // execSync('npm run dev', { stdio: 'inherit' });
            
            // Verify build outputs
            this.verifyBuildOutputs();
            
            console.log('✅ Assets built successfully\n');
        } catch (error) {
            throw new Error(`Build failed: ${error.message}`);
        }
    }

    verifyBuildOutputs() {
        const requiredPaths = [
            './src/dist/css',
            './src/dist/js',
            './src/public'
        ];
        
        for (const dirPath of requiredPaths) {
            if (!fs.existsSync(dirPath)) {
                throw new Error(`Build output missing: ${dirPath}`);
            }
        }
    }

    async connectSFTP() {
        console.log('🔗 Connecting to test server via SFTP...');
        
        try {
            await this.client.connect({
                host: config.server.host,
                username: config.server.username,
                password: config.server.password,
                port: config.server.port,
                readyTimeout: 20000,
                retries: 2
            });
            
            console.log('✅ Connected to SFTP server\n');
        } catch (error) {
            throw new Error(`SFTP connection failed: ${error.message}`);
        }
    }

    async deployFiles() {
        console.log('📤 Deploying files...');
        
        // Deploy PHP files
        await this.deployPHPFiles();
        
        // Deploy static assets (src/public -> php/-)
        await this.deployStaticAssets();
        
        // Deploy compiled assets (src/dist -> php/css, php/js)
        await this.deployCompiledAssets();
        
        console.log('✅ All files deployed successfully');
    }

    async deployPHPFiles() {
        console.log('  📄 Deploying PHP files...');
        
        try {
            // Ensure remote directory exists
            await this.client.mkdir(config.server.remotePath, true);
            
            // Upload all PHP files and directories
            await this.uploadDirectory(config.localPaths.php, config.server.remotePath);
            
            console.log('  ✅ PHP files deployed');
        } catch (error) {
            throw new Error(`PHP deployment failed: ${error.message}`);
        }
    }

    async deployStaticAssets() {
        console.log('  🖼️  Deploying static assets...');
        
        try {
            // Create -/ directory (for static assets)
            const staticRemotePath = path.posix.join(config.server.remotePath, '-');
            await this.client.mkdir(staticRemotePath, true);
            
            // Upload src/public contents to php/-/
            const publicPath = config.localPaths.srcPublic;
            if (fs.existsSync(publicPath)) {
                await this.uploadDirectory(publicPath, staticRemotePath);
                console.log('  ✅ Static assets deployed to php/-/');
            } else {
                console.log('  ⚠️  No static assets found in src/public');
            }
        } catch (error) {
            throw new Error(`Static assets deployment failed: ${error.message}`);
        }
    }

    async deployCompiledAssets() {
        console.log('  ⚙️  Deploying compiled assets...');
        
        try {
            const distPath = config.localPaths.srcDist;
            
            // Deploy CSS files
            const cssPath = path.join(distPath, 'css');
            if (fs.existsSync(cssPath)) {
                const cssRemotePath = path.posix.join(config.server.remotePath, 'css');
                await this.client.mkdir(cssRemotePath, true);
                await this.uploadDirectory(cssPath, cssRemotePath);
                console.log('  ✅ CSS files deployed to php/css/');
            }
            
            // Deploy JS files
            const jsPath = path.join(distPath, 'js');
            if (fs.existsSync(jsPath)) {
                const jsRemotePath = path.posix.join(config.server.remotePath, 'js');
                await this.client.mkdir(jsRemotePath, true);
                await this.uploadDirectory(jsPath, jsRemotePath);
                console.log('  ✅ JS files deployed to php/js/');
            }
        } catch (error) {
            throw new Error(`Compiled assets deployment failed: ${error.message}`);
        }
    }

    async uploadDirectory(localDir, remoteDir) {
        const items = fs.readdirSync(localDir);
        
        for (const item of items) {
            const localPath = path.join(localDir, item);
            const stat = fs.statSync(localPath);
            
            // Skip excluded files
            if (this.shouldExclude(item)) {
                console.log(`    Skipping: ${item}`);
                continue;
            }
            
            if (stat.isDirectory()) {
                // Create remote directory and recurse
                const newRemoteDir = path.posix.join(remoteDir, item);
                await this.client.mkdir(newRemoteDir, true);
                await this.uploadDirectory(localPath, newRemoteDir);
            } else {
                // Upload file
                const remotePath = path.posix.join(remoteDir, item);
                await this.client.put(localPath, remotePath);
                console.log(`    Uploaded: ${remotePath}`);
            }
        }
    }

    shouldExclude(filename) {
        return config.excludeFiles.some(pattern => {
            if (pattern.includes('*')) {
                const regex = new RegExp(pattern.replace(/\*/g, '.*'));
                return regex.test(filename);
            }
            return filename === pattern;
        });
    }
}

// Main execution
async function main() {
    console.log('� Configuration loaded from package.json:');
    console.log(`   Host: ${config.server.host}`);
    console.log(`   Port: ${config.server.port}`);
    console.log(`   Username: ${config.server.username}`);
    console.log(`   Remote Path: ${config.server.remotePath}\n`);
    
    const deployer = new TestDeployer();
    
    try {
        await deployer.deploy();
    } catch (error) {
        console.error('\n💥 Deployment Error:', error.message);
        process.exit(1);
    }
}

// Run if called directly
if (require.main === module) {
    main();
}

module.exports = TestDeployer;
