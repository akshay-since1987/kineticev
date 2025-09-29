<?php
require_once 'components/layout.php';

// Get URL parameters for pre-filling the form
$loan_amount = $_GET['loan_amount'] ?? '111499';
$interest_rate = $_GET['interest_rate'] ?? '12';
$tenure = $_GET['tenure'] ?? '36';

// Validate and sanitize inputs
$loan_amount = max(50000, min(2000000, (int)$loan_amount)); // Between 50k and 20L
$interest_rate = max(5, min(30, (float)$interest_rate)); // Between 5% and 30%
$tenure = max(6, min(84, (int)$tenure)); // Between 6 months and 7 years

startLayout("EMI Calculator - Calculate Your Monthly Instalments | Kinetic Electric Scooter", [
    'description' => 'Calculate your monthly EMI for Kinetic EV scooters with our comprehensive EMI calculator. Get detailed breakdown with interest rates, tenure options and total amount payable.',
    'canonical' => 'https://kineticev.in/emi-calculator'
]);

?>

<div class="emi-calculator-page">
    <div class="container">
        <div class="page-header">
            <div class="header-content">
                <h1>EMI Calculator</h1>
                <p>Calculate your monthly instalments for your Kinetic EV purchase</p>
            </div>
        </div>

        <div class="calculator-container">
            <div class="calculator-inputs">
                <div class="input-section">
                    <h2>Loan Details</h2>
                    
                    <div class="form-group">
                        <label for="loan-amount">Loan Amount (₹)</label>
                        <input type="range" id="loan-amount-range" min="50000" max="2000000" step="5000" value="<?php echo $loan_amount; ?>">
                        <input type="number" id="loan-amount" min="50000" max="2000000" step="1000" value="<?php echo $loan_amount; ?>">
                        <div class="range-labels">
                            <span>₹50,000</span>
                            <span>₹20,00,000</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="interest-rate">Interest Rate (% per annum)</label>
                        <input type="range" id="interest-rate-range" min="5" max="30" step="0.25" value="<?php echo $interest_rate; ?>">
                        <input type="number" id="interest-rate" min="5" max="30" step="0.25" value="<?php echo $interest_rate; ?>">
                        <div class="range-labels">
                            <span>5%</span>
                            <span>30%</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tenure">Tenure (Months)</label>
                        <input type="range" id="tenure-range" min="6" max="84" step="6" value="<?php echo $tenure; ?>">
                        <select id="tenure">
                            <option value="6" <?php echo $tenure == 6 ? 'selected' : ''; ?>>6 months</option>
                            <option value="12" <?php echo $tenure == 12 ? 'selected' : ''; ?>>1 year</option>
                            <option value="18" <?php echo $tenure == 18 ? 'selected' : ''; ?>>1.5 years</option>
                            <option value="24" <?php echo $tenure == 24 ? 'selected' : ''; ?>>2 years</option>
                            <option value="30" <?php echo $tenure == 30 ? 'selected' : ''; ?>>2.5 years</option>
                            <option value="36" <?php echo $tenure == 36 ? 'selected' : ''; ?>>3 years</option>
                            <option value="42" <?php echo $tenure == 42 ? 'selected' : ''; ?>>3.5 years</option>
                            <option value="48" <?php echo $tenure == 48 ? 'selected' : ''; ?>>4 years</option>
                            <option value="54" <?php echo $tenure == 54 ? 'selected' : ''; ?>>4.5 years</option>
                            <option value="60" <?php echo $tenure == 60 ? 'selected' : ''; ?>>5 years</option>
                            <option value="66" <?php echo $tenure == 66 ? 'selected' : ''; ?>>5.5 years</option>
                            <option value="72" <?php echo $tenure == 72 ? 'selected' : ''; ?>>6 years</option>
                            <option value="78" <?php echo $tenure == 78 ? 'selected' : ''; ?>>6.5 years</option>
                            <option value="84" <?php echo $tenure == 84 ? 'selected' : ''; ?>>7 years</option>
                        </select>
                        <div class="range-labels">
                            <span>6 months</span>
                            <span>7 years</span>
                        </div>
                    </div>

                    <div class="processing-fee-section">
                        <h3>Additional Charges (Optional)</h3>
                        <div class="form-group">
                            <label for="processing-fee">Processing Fee (₹)</label>
                            <input type="number" id="processing-fee" min="0" max="50000" value="0" placeholder="Enter processing fee">
                        </div>
                        <div class="form-group">
                            <label for="insurance">Insurance (₹ per year)</label>
                            <input type="number" id="insurance" min="0" max="25000" value="0" placeholder="Enter annual insurance">
                        </div>
                    </div>
                </div>
            </div>

            <div class="calculator-results">
                <div class="results-section">
                    <h2>EMI Breakdown</h2>
                    
                    <div class="emi-display">
                        <div class="monthly-emi">
                            <span class="label">Monthly EMI</span>
                            <span class="amount" id="monthly-emi">₹0</span>
                        </div>
                    </div>

                    <div class="breakdown-cards">
                        <div class="breakdown-card">
                            <div class="card-header">Loan Summary</div>
                            <div class="card-content">
                                <div class="breakdown-item">
                                    <span>Principal Amount</span>
                                    <span id="principal-amount">₹0</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Total Interest</span>
                                    <span id="total-interest">₹0</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Processing Fee</span>
                                    <span id="total-processing-fee">₹0</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Total Insurance</span>
                                    <span id="total-insurance">₹0</span>
                                </div>
                                <div class="breakdown-item total">
                                    <span>Total Amount Payable</span>
                                    <span id="total-amount">₹0</span>
                                </div>
                            </div>
                        </div>

                        <div class="breakdown-card">
                            <div class="card-header">Payment Analysis</div>
                            <div class="card-content">
                                <div class="breakdown-item">
                                    <span>Interest Rate (Monthly)</span>
                                    <span id="monthly-rate">0%</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Interest vs Principal</span>
                                    <span id="interest-ratio">0%</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Effective Interest Rate</span>
                                    <span id="effective-rate">0%</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Total Payments</span>
                                    <span id="total-payments">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="chart-container">
                        <canvas id="emi-chart" width="400" height="200"></canvas>
                    </div>

                    <div class="action-buttons">
                        <!-- <a href="/book-now" class="btn btn-primary">Apply for Loan</a> -->
                        <button id="share-calculation" class="btn btn-secondary">Share Calculation</button>
                        <button id="download-schedule" class="btn btn-secondary">Download Schedule</button>
                    </div>
                </div>

                <div class="amortization-table">
                    <h3>Amortization Schedule</h3>
                    <div class="table-container">
                        <table id="amortization-schedule">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>EMI</th>
                                    <th>Principal</th>
                                    <th>Interest</th>
                                    <th>Outstanding</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="calculator-info">
            <div class="info-section">
                <h2>Understanding EMI Calculation</h2>
                <p>EMI (Equated Monthly Installment) is calculated using the compound interest formula:</p>
                <div class="formula">
                    <code>EMI = [P × R × (1+R)^N] / [(1+R)^N - 1]</code>
                </div>
                <div class="formula-explanation">
                    <ul>
                        <li><strong>P</strong> = Principal loan amount</li>
                        <li><strong>R</strong> = Monthly interest rate (Annual rate ÷ 12)</li>
                        <li><strong>N</strong> = Number of monthly installments (tenure in months)</li>
                    </ul>
                </div>
            </div>

            <div class="info-section">
                <h2>Kinetic EV Financing Options</h2>
                <div class="financing-options">
                    <div class="option-card">
                        <h3>Bank Financing</h3>
                        <p>Partner banks offer competitive rates starting from 8.5% per annum</p>
                        <ul>
                            <li>Quick approval process</li>
                            <li>Flexible tenure options</li>
                            <li>Minimal documentation</li>
                        </ul>
                    </div>
                    <div class="option-card">
                        <h3>NBFC Financing</h3>
                        <p>Fast processing with attractive interest rates for EV purchases</p>
                        <ul>
                            <li>Same-day approval</li>
                            <li>Up to 90% financing</li>
                            <li>Special EV loan schemes</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include modals before the footer
require_once 'components/modals.php';
renderModals();
?>
<?php endLayout(); ?>