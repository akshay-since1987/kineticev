/**
 * System Resource Monitor
 * Monitors server performance during load tests
 */

const si = require('systeminformation');
const fs = require('fs');
const chalk = require('chalk');

class SystemMonitor {
  constructor(options = {}) {
    this.interval = options.interval || 5000; // 5 seconds
    this.logFile = options.logFile || `./monitoring-${Date.now()}.json`;
    this.monitoring = false;
    this.data = [];
    this.thresholds = {
      cpuLoad: options.cpuThreshold || 80,
      memoryUsed: options.memoryThreshold || 85,
      diskUsed: options.diskThreshold || 90
    };
  }

  async start() {
    console.log(chalk.green('🔍 Starting system monitoring...'));
    console.log(chalk.blue(`📊 Monitoring interval: ${this.interval}ms`));
    console.log(chalk.blue(`📝 Log file: ${this.logFile}`));
    
    this.monitoring = true;
    this.monitorLoop();
  }

  stop() {
    this.monitoring = false;
    this.saveData();
    console.log(chalk.green('✅ System monitoring stopped'));
    this.generateReport();
  }

  async monitorLoop() {
    while (this.monitoring) {
      try {
        const data = await this.collectMetrics();
        this.data.push(data);
        this.displayRealTime(data);
        this.checkThresholds(data);
        
        await this.sleep(this.interval);
      } catch (error) {
        console.error(chalk.red('❌ Monitoring error:'), error.message);
      }
    }
  }

  async collectMetrics() {
    const timestamp = new Date().toISOString();
    
    // Get CPU info
    const cpu = await si.currentLoad();
    
    // Get memory info
    const mem = await si.mem();
    
    // Get disk info
    const disk = await si.fsSize();
    const mainDisk = disk[0] || {};
    
    // Get network info
    const networkStats = await si.networkStats();
    const mainNetwork = networkStats[0] || {};
    
    // Get process info (if available)
    let processes = [];
    try {
      processes = await si.processes();
    } catch (e) {
      // Process info might not be available on all systems
    }

    return {
      timestamp,
      cpu: {
        load: Math.round(cpu.currentLoad * 100) / 100,
        loadUser: Math.round(cpu.currentLoadUser * 100) / 100,
        loadSystem: Math.round(cpu.currentLoadSystem * 100) / 100,
        cores: cpu.cpus?.length || 1
      },
      memory: {
        total: Math.round(mem.total / 1024 / 1024), // MB
        used: Math.round(mem.used / 1024 / 1024), // MB
        free: Math.round(mem.free / 1024 / 1024), // MB
        usedPercent: Math.round((mem.used / mem.total) * 100 * 100) / 100
      },
      disk: {
        size: Math.round((mainDisk.size || 0) / 1024 / 1024 / 1024), // GB
        used: Math.round((mainDisk.used || 0) / 1024 / 1024 / 1024), // GB
        available: Math.round((mainDisk.available || 0) / 1024 / 1024 / 1024), // GB
        usedPercent: mainDisk.use || 0
      },
      network: {
        rx: mainNetwork.rx_bytes || 0,
        tx: mainNetwork.tx_bytes || 0,
        rxSec: mainNetwork.rx_sec || 0,
        txSec: mainNetwork.tx_sec || 0
      },
      processCount: processes.list?.length || 0
    };
  }

  displayRealTime(data) {
    // Clear console and display current metrics
    process.stdout.write('\x1B[2J\x1B[0f');
    
    console.log(chalk.bold.blue('='.repeat(60)));
    console.log(chalk.bold.blue('         KINETICEV LOAD TEST - SYSTEM MONITOR'));
    console.log(chalk.bold.blue('='.repeat(60)));
    console.log();
    
    // Timestamp
    console.log(chalk.gray(`📅 ${data.timestamp}`));
    console.log();
    
    // CPU
    const cpuColor = data.cpu.load > this.thresholds.cpuLoad ? chalk.red : 
                     data.cpu.load > this.thresholds.cpuLoad * 0.8 ? chalk.yellow : chalk.green;
    console.log(chalk.bold('🔥 CPU Usage:'));
    console.log(`   Overall Load: ${cpuColor(data.cpu.load.toFixed(2))}%`);
    console.log(`   User Load:    ${data.cpu.loadUser.toFixed(2)}%`);
    console.log(`   System Load:  ${data.cpu.loadSystem.toFixed(2)}%`);
    console.log(`   CPU Cores:    ${data.cpu.cores}`);
    console.log();
    
    // Memory
    const memColor = data.memory.usedPercent > this.thresholds.memoryUsed ? chalk.red : 
                     data.memory.usedPercent > this.thresholds.memoryUsed * 0.8 ? chalk.yellow : chalk.green;
    console.log(chalk.bold('💾 Memory Usage:'));
    console.log(`   Total:        ${data.memory.total} MB`);
    console.log(`   Used:         ${memColor(data.memory.used)} MB (${memColor(data.memory.usedPercent.toFixed(2))}%)`);
    console.log(`   Free:         ${data.memory.free} MB`);
    console.log();
    
    // Disk
    const diskColor = data.disk.usedPercent > this.thresholds.diskUsed ? chalk.red : 
                      data.disk.usedPercent > this.thresholds.diskUsed * 0.8 ? chalk.yellow : chalk.green;
    console.log(chalk.bold('💿 Disk Usage:'));
    console.log(`   Total:        ${data.disk.size} GB`);
    console.log(`   Used:         ${diskColor(data.disk.used)} GB (${diskColor(data.disk.usedPercent.toFixed(2))}%)`);
    console.log(`   Available:    ${data.disk.available} GB`);
    console.log();
    
    // Network
    console.log(chalk.bold('🌐 Network:'));
    console.log(`   RX (current): ${(data.network.rxSec / 1024).toFixed(2)} KB/s`);
    console.log(`   TX (current): ${(data.network.txSec / 1024).toFixed(2)} KB/s`);
    console.log(`   RX (total):   ${(data.network.rx / 1024 / 1024).toFixed(2)} MB`);
    console.log(`   TX (total):   ${(data.network.tx / 1024 / 1024).toFixed(2)} MB`);
    console.log();
    
    // Process count
    console.log(chalk.bold('⚡ Processes:'));
    console.log(`   Total:        ${data.processCount}`);
    console.log();
    
    console.log(chalk.gray('Press Ctrl+C to stop monitoring'));
  }

  checkThresholds(data) {
    const alerts = [];
    
    if (data.cpu.load > this.thresholds.cpuLoad) {
      alerts.push(`High CPU load: ${data.cpu.load.toFixed(2)}%`);
    }
    
    if (data.memory.usedPercent > this.thresholds.memoryUsed) {
      alerts.push(`High memory usage: ${data.memory.usedPercent.toFixed(2)}%`);
    }
    
    if (data.disk.usedPercent > this.thresholds.diskUsed) {
      alerts.push(`High disk usage: ${data.disk.usedPercent.toFixed(2)}%`);
    }
    
    if (alerts.length > 0) {
      console.log();
      console.log(chalk.red.bold('⚠️  ALERTS:'));
      alerts.forEach(alert => console.log(chalk.red(`   • ${alert}`)));
    }
  }

  saveData() {
    try {
      fs.writeFileSync(this.logFile, JSON.stringify(this.data, null, 2));
      console.log(chalk.green(`📊 Monitoring data saved to: ${this.logFile}`));
    } catch (error) {
      console.error(chalk.red('❌ Failed to save monitoring data:'), error.message);
    }
  }

  generateReport() {
    if (this.data.length === 0) return;

    const report = this.analyzeData();
    const reportFile = this.logFile.replace('.json', '-report.txt');
    
    try {
      fs.writeFileSync(reportFile, report);
      console.log(chalk.green(`📋 Performance report saved to: ${reportFile}`));
    } catch (error) {
      console.error(chalk.red('❌ Failed to save report:'), error.message);
    }
  }

  analyzeData() {
    const cpuLoads = this.data.map(d => d.cpu.load);
    const memoryUsages = this.data.map(d => d.memory.usedPercent);
    const diskUsages = this.data.map(d => d.disk.usedPercent);

    const avgCpu = cpuLoads.reduce((a, b) => a + b, 0) / cpuLoads.length;
    const maxCpu = Math.max(...cpuLoads);
    const avgMemory = memoryUsages.reduce((a, b) => a + b, 0) / memoryUsages.length;
    const maxMemory = Math.max(...memoryUsages);

    return `
KINETICEV LOAD TEST - PERFORMANCE REPORT
=========================================

Test Duration: ${this.data.length * (this.interval / 1000)} seconds
Data Points: ${this.data.length}
Monitoring Interval: ${this.interval}ms

CPU PERFORMANCE:
- Average Load: ${avgCpu.toFixed(2)}%
- Peak Load: ${maxCpu.toFixed(2)}%
- Load > 80%: ${cpuLoads.filter(load => load > 80).length} times
- Load > 90%: ${cpuLoads.filter(load => load > 90).length} times

MEMORY PERFORMANCE:
- Average Usage: ${avgMemory.toFixed(2)}%
- Peak Usage: ${maxMemory.toFixed(2)}%
- Usage > 85%: ${memoryUsages.filter(usage => usage > 85).length} times
- Usage > 95%: ${memoryUsages.filter(usage => usage > 95).length} times

RECOMMENDATIONS:
${avgCpu > 70 ? '- Consider CPU optimization or scaling' : '✓ CPU performance acceptable'}
${maxCpu > 90 ? '- Peak CPU usage indicates potential bottlenecks' : '✓ CPU peaks within acceptable range'}
${avgMemory > 80 ? '- Consider memory optimization or increase RAM' : '✓ Memory usage acceptable'}
${maxMemory > 95 ? '- Peak memory usage may cause performance issues' : '✓ Memory peaks within acceptable range'}

Generated: ${new Date().toISOString()}
    `.trim();
  }

  sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}

// CLI usage
if (require.main === module) {
  const monitor = new SystemMonitor({
    interval: process.argv.includes('--fast') ? 2000 : 5000,
    cpuThreshold: 80,
    memoryThreshold: 85,
    diskThreshold: 90
  });

  // Handle graceful shutdown
  process.on('SIGINT', () => {
    console.log(chalk.yellow('\n🛑 Stopping monitoring...'));
    monitor.stop();
    process.exit(0);
  });

  monitor.start();
}

module.exports = SystemMonitor;