#!/usr/bin/env node
/**
 * Load Testing Runner Script
 * Easy way to run different types of load tests
 */

const { spawn } = require('child_process');
const chalk = require('chalk');
const path = require('path');

class LoadTestRunner {
  constructor() {
    this.testTypes = {
      quick: {
        name: 'Quick Test (Autocannon)',
        command: 'node',
        args: ['autocannon-quick-test.js'],
        description: 'Fast 30-second test of main endpoints'
      },
      light: {
        name: 'Light Load Test (Artillery)',
        command: 'npx',
        args: ['artillery', 'run', 'artillery-light-test.js'],
        description: '2-minute test with 10-50 virtual users'
      },
      moderate: {
        name: 'Moderate Load Test (Artillery)',
        command: 'npx',
        args: ['artillery', 'run', 'artillery-moderate-test.js'],
        description: '7-minute test with 50-200 virtual users'
      },
      api: {
        name: 'API Load Test (Artillery)',
        command: 'npx',
        args: ['artillery', 'run', 'artillery-api-test.js'],
        description: 'API-focused test for backend endpoints'
      },
      k6: {
        name: 'K6 Load Test',
        command: 'k6',
        args: ['run', 'k6-load-test.js'],
        description: 'JavaScript-based load test with K6'
      }
    };
  }

  displayMenu() {
    console.log(chalk.blue.bold('\n🚀 KineticEV Load Testing Suite'));
    console.log(chalk.blue('=' .repeat(50)));
    console.log('\nAvailable tests:');
    
    Object.entries(this.testTypes).forEach(([key, test], index) => {
      console.log(chalk.yellow(`${index + 1}. ${test.name}`));
      console.log(chalk.gray(`   ${test.description}`));
    });
    
    console.log(chalk.yellow(`${Object.keys(this.testTypes).length + 1}. Monitor System Resources`));
    console.log(chalk.gray(`   Real-time system monitoring during tests`));
    
    console.log(chalk.yellow(`${Object.keys(this.testTypes).length + 2}. Exit`));
    console.log();
  }

  async runTest(testType) {
    const test = this.testTypes[testType];
    if (!test) {
      console.log(chalk.red('❌ Invalid test type'));
      return;
    }

    console.log(chalk.green(`\n🏃 Running: ${test.name}`));
    console.log(chalk.blue(`📋 ${test.description}`));
    console.log(chalk.gray(`💻 Command: ${test.command} ${test.args.join(' ')}\n`));

    return new Promise((resolve, reject) => {
      const process = spawn(test.command, test.args, {
        cwd: __dirname,
        stdio: 'inherit',
        shell: true
      });

      process.on('close', (code) => {
        if (code === 0) {
          console.log(chalk.green('\n✅ Test completed successfully!'));
          resolve();
        } else {
          console.log(chalk.red(`\n❌ Test failed with code ${code}`));
          reject(new Error(`Test failed with code ${code}`));
        }
      });

      process.on('error', (error) => {
        console.log(chalk.red(`\n❌ Test error: ${error.message}`));
        reject(error);
      });
    });
  }

  async runMonitoring() {
    console.log(chalk.green('\n📊 Starting system monitoring...'));
    console.log(chalk.blue('Press Ctrl+C to stop monitoring\n'));

    return new Promise((resolve, reject) => {
      const process = spawn('node', ['monitor-server.js'], {
        cwd: __dirname,
        stdio: 'inherit',
        shell: true
      });

      process.on('close', (code) => {
        console.log(chalk.green('\n✅ Monitoring stopped'));
        resolve();
      });

      process.on('error', (error) => {
        console.log(chalk.red(`\n❌ Monitoring error: ${error.message}`));
        reject(error);
      });
    });
  }

  async promptUser() {
    const readline = require('readline').createInterface({
      input: process.stdin,
      output: process.stdout
    });

    return new Promise((resolve) => {
      readline.question('Select test (1-7): ', (answer) => {
        readline.close();
        resolve(answer.trim());
      });
    });
  }

  async run() {
    while (true) {
      this.displayMenu();
      
      try {
        const choice = await this.promptUser();
        const choiceNum = parseInt(choice);
        const testKeys = Object.keys(this.testTypes);

        if (choiceNum >= 1 && choiceNum <= testKeys.length) {
          const testType = testKeys[choiceNum - 1];
          await this.runTest(testType);
        } else if (choiceNum === testKeys.length + 1) {
          await this.runMonitoring();
        } else if (choiceNum === testKeys.length + 2) {
          console.log(chalk.blue('👋 Goodbye!'));
          break;
        } else {
          console.log(chalk.red('❌ Invalid choice. Please try again.'));
        }

        // Pause before showing menu again
        console.log(chalk.gray('\nPress Enter to continue...'));
        await this.promptUser();

      } catch (error) {
        console.error(chalk.red(`\n❌ Error: ${error.message}`));
        console.log(chalk.gray('Press Enter to continue...'));
        await this.promptUser();
      }
    }
  }
}

// CLI usage
if (require.main === module) {
  const runner = new LoadTestRunner();
  
  // Handle command line arguments
  const args = process.argv.slice(2);
  
  if (args.length > 0) {
    const testType = args[0];
    
    if (testType === 'monitor') {
      runner.runMonitoring().catch(console.error);
    } else if (runner.testTypes[testType]) {
      runner.runTest(testType)
        .then(() => process.exit(0))
        .catch(() => process.exit(1));
    } else {
      console.log(chalk.red(`❌ Unknown test type: ${testType}`));
      console.log(chalk.blue('Available types:'), Object.keys(runner.testTypes).join(', '));
      process.exit(1);
    }
  } else {
    // Interactive mode
    runner.run().catch(console.error);
  }
}

module.exports = LoadTestRunner;