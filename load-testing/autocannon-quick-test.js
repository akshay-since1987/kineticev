/**
 * Autocannon Quick Test
 * Fast HTTP benchmarking for specific endpoints
 */

const autocannon = require('autocannon');
const chalk = require('chalk');

class QuickBenchmark {
  constructor() {
    this.baseUrl = 'https://www.kineticev.in';
    this.results = [];
  }

  async runBenchmarks() {
    console.log(chalk.blue.bold('🚀 Starting Quick Benchmark Tests'));
    console.log(chalk.blue('='.repeat(50)));

    // Test endpoints
    const endpoints = [
      { path: '/', name: 'Homepage' },
      { path: '/range-x', name: 'Product Page' },
      { path: '/contact-us', name: 'Contact Page' },
      { path: '/css/main.css', name: 'CSS Asset' },
      { path: '/js/main.js', name: 'JS Asset' }
    ];

    for (const endpoint of endpoints) {
      console.log(chalk.yellow(`\n📊 Testing: ${endpoint.name} (${endpoint.path})`));
      
      const result = await this.benchmarkEndpoint(endpoint.path, endpoint.name);
      this.results.push(result);
      
      // Short pause between tests
      await this.sleep(2000);
    }

    this.generateSummary();
  }

  async benchmarkEndpoint(path, name) {
    const url = `${this.baseUrl}${path}`;
    
    const options = {
      url,
      connections: 10,
      duration: 30, // 30 seconds
      pipelining: 1,
      headers: {
        'User-Agent': 'KineticEV-Autocannon/1.0'
      }
    };

    try {
      console.log(chalk.gray(`   URL: ${url}`));
      console.log(chalk.gray(`   Connections: ${options.connections}, Duration: ${options.duration}s`));
      
      const result = await autocannon(options);
      
      console.log(chalk.green(`   ✅ Completed: ${result.requests.total} requests`));
      console.log(chalk.green(`   📈 RPS: ${result.requests.average}`));
      console.log(chalk.green(`   ⏱️  Avg Latency: ${result.latency.average}ms`));
      
      return {
        name,
        path,
        url,
        ...result
      };
      
    } catch (error) {
      console.log(chalk.red(`   ❌ Error: ${error.message}`));
      return {
        name,
        path,
        url,
        error: error.message
      };
    }
  }

  generateSummary() {
    console.log(chalk.blue.bold('\n📋 BENCHMARK SUMMARY'));
    console.log(chalk.blue('='.repeat(80)));

    const summary = [];

    this.results.forEach(result => {
      if (result.error) {
        console.log(chalk.red(`❌ ${result.name}: ERROR - ${result.error}`));
        return;
      }

      const rps = Math.round(result.requests.average);
      const avgLatency = Math.round(result.latency.average);
      const p95Latency = Math.round(result.latency.p95);
      const errors = result.errors || 0;
      const errorRate = result.requests.total > 0 ? (errors / result.requests.total * 100).toFixed(2) : 0;

      console.log(chalk.green(`✅ ${result.name}:`));
      console.log(`   RPS: ${rps} req/sec`);
      console.log(`   Avg Latency: ${avgLatency}ms`);
      console.log(`   P95 Latency: ${p95Latency}ms`);
      console.log(`   Error Rate: ${errorRate}%`);
      console.log();

      summary.push({
        name: result.name,
        rps,
        avgLatency,
        p95Latency,
        errorRate: parseFloat(errorRate)
      });
    });

    // Generate recommendations
    console.log(chalk.yellow.bold('📊 PERFORMANCE ANALYSIS:'));
    summary.forEach(item => {
      const recommendations = [];
      
      if (item.avgLatency > 1000) {
        recommendations.push('High average latency - check server performance');
      }
      
      if (item.p95Latency > 2000) {
        recommendations.push('High P95 latency - investigate slow requests');
      }
      
      if (item.rps < 50) {
        recommendations.push('Low throughput - consider server optimization');
      }
      
      if (item.errorRate > 1) {
        recommendations.push('High error rate - check application logs');
      }

      if (recommendations.length === 0) {
        console.log(chalk.green(`✓ ${item.name}: Performance looks good`));
      } else {
        console.log(chalk.yellow(`⚠️  ${item.name}:`));
        recommendations.forEach(rec => console.log(chalk.yellow(`   • ${rec}`)));
      }
    });

    // Save results
    const resultsFile = `./autocannon-results-${Date.now()}.json`;
    require('fs').writeFileSync(resultsFile, JSON.stringify(this.results, null, 2));
    console.log(chalk.blue(`\n💾 Results saved to: ${resultsFile}`));
  }

  sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}

// CLI usage
if (require.main === module) {
  const benchmark = new QuickBenchmark();
  
  console.log(chalk.green('Starting KineticEV Quick Benchmark...'));
  
  benchmark.runBenchmarks()
    .then(() => {
      console.log(chalk.green('\n✅ Benchmark completed!'));
      process.exit(0);
    })
    .catch(error => {
      console.error(chalk.red('\n❌ Benchmark failed:'), error);
      process.exit(1);
    });
}

module.exports = QuickBenchmark;