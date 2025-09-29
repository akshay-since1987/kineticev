/**
 * =======================
 * EMI Calculator Page Module
 * =======================
 * Comprehensive EMI calculator with industry-standard compound interest calculations
 * Includes amortization schedule, charts, and advanced features
 */

export const EmiCalculatorPage = {
    init: function() {
        // Check if we're on the EMI calculator page
        if (!document.querySelector('.emi-calculator-page')) {
            return;
        }

        console.log('Initializing EMI Calculator Page');

        // Get all input elements
        this.initializeElements();
        
        // Bind events
        this.bindEvents();
        
        // Initial calculation
        this.calculateEMI();
        
        // Initialize chart
        this.initializeChart();
        
        // Read URL parameters
        this.readUrlParameters();
    },

    initializeElements: function() {
        // Input elements
        this.loanAmountInput = document.getElementById('loan-amount');
        this.loanAmountRange = document.getElementById('loan-amount-range');
        this.interestRateInput = document.getElementById('interest-rate');
        this.interestRateRange = document.getElementById('interest-rate-range');
        this.tenureInput = document.getElementById('tenure');
        this.tenureRange = document.getElementById('tenure-range');
        this.processingFeeInput = document.getElementById('processing-fee');
        this.insuranceInput = document.getElementById('insurance');

        // Result elements
        this.monthlyEmiElement = document.getElementById('monthly-emi');
        this.principalAmountElement = document.getElementById('principal-amount');
        this.totalInterestElement = document.getElementById('total-interest');
        this.totalProcessingFeeElement = document.getElementById('total-processing-fee');
        this.totalInsuranceElement = document.getElementById('total-insurance');
        this.totalAmountElement = document.getElementById('total-amount');
        this.monthlyRateElement = document.getElementById('monthly-rate');
        this.interestRatioElement = document.getElementById('interest-ratio');
        this.effectiveRateElement = document.getElementById('effective-rate');
        this.totalPaymentsElement = document.getElementById('total-payments');

        // Chart and table
        this.chartCanvas = document.getElementById('emi-chart');
        this.amortizationTable = document.getElementById('amortization-schedule');

        // Action buttons
        this.shareBtn = document.getElementById('share-calculation');
        this.downloadBtn = document.getElementById('download-schedule');

        // Chart instance
        this.chart = null;
    },

    bindEvents: function() {
        // Sync range sliders with number inputs
        this.loanAmountRange.addEventListener('input', () => {
            this.loanAmountInput.value = this.loanAmountRange.value;
            this.calculateEMI();
        });

        this.loanAmountInput.addEventListener('input', () => {
            this.loanAmountRange.value = this.loanAmountInput.value;
            this.calculateEMI();
        });

        this.interestRateRange.addEventListener('input', () => {
            this.interestRateInput.value = this.interestRateRange.value;
            this.calculateEMI();
        });

        this.interestRateInput.addEventListener('input', () => {
            this.interestRateRange.value = this.interestRateInput.value;
            this.calculateEMI();
        });

        this.tenureRange.addEventListener('input', () => {
            this.tenureInput.value = this.tenureRange.value;
            this.calculateEMI();
        });

        this.tenureInput.addEventListener('change', () => {
            this.tenureRange.value = this.tenureInput.value;
            this.calculateEMI();
        });

        // Additional charges
        this.processingFeeInput.addEventListener('input', () => {
            this.calculateEMI();
        });

        this.insuranceInput.addEventListener('input', () => {
            this.calculateEMI();
        });

        // Action buttons
        if (this.shareBtn) {
            this.shareBtn.addEventListener('click', () => {
                this.shareCalculation();
            });
        }

        if (this.downloadBtn) {
            this.downloadBtn.addEventListener('click', () => {
                this.downloadSchedule();
            });
        }
    },

    readUrlParameters: function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Set values from URL parameters if they exist
        if (urlParams.get('loan_amount')) {
            const loanAmount = Math.max(50000, Math.min(2000000, parseInt(urlParams.get('loan_amount'))));
            this.loanAmountInput.value = loanAmount;
            this.loanAmountRange.value = loanAmount;
        }

        if (urlParams.get('interest_rate')) {
            const interestRate = Math.max(5, Math.min(30, parseFloat(urlParams.get('interest_rate'))));
            this.interestRateInput.value = interestRate;
            this.interestRateRange.value = interestRate;
        }

        if (urlParams.get('tenure')) {
            const tenure = Math.max(6, Math.min(84, parseInt(urlParams.get('tenure'))));
            this.tenureInput.value = tenure;
            this.tenureRange.value = tenure;
        }

        // Recalculate with URL parameters
        this.calculateEMI();
    },

    calculateEMI: function() {
        // Get input values
        const principal = parseFloat(this.loanAmountInput.value) || 0;
        const annualRate = parseFloat(this.interestRateInput.value) || 0;
        const tenure = parseInt(this.tenureInput.value) || 1;
        const processingFee = parseFloat(this.processingFeeInput.value) || 0;
        const annualInsurance = parseFloat(this.insuranceInput.value) || 0;

        // Calculate monthly rate
        const monthlyRate = annualRate / 100 / 12;

        // Calculate EMI using compound interest formula
        let emi = 0;
        if (principal > 0 && annualRate > 0 && tenure > 0) {
            emi = (principal * monthlyRate * Math.pow(1 + monthlyRate, tenure)) / 
                  (Math.pow(1 + monthlyRate, tenure) - 1);
        }

        // Calculate totals
        const totalEmiAmount = emi * tenure;
        const totalInterest = totalEmiAmount - principal;
        const totalInsurance = annualInsurance * (tenure / 12);
        const totalAmount = totalEmiAmount + processingFee + totalInsurance;

        // Calculate analysis metrics
        const monthlyInsurance = annualInsurance / 12;
        const totalMonthlyPayment = emi + monthlyInsurance;
        const interestRatio = totalInterest > 0 ? (totalInterest / principal) * 100 : 0;
        const effectiveRate = totalAmount > 0 ? ((totalAmount - principal) / principal) * (12 / tenure) * 100 : 0;

        // Update display
        this.updateResults({
            monthlyEmi: emi,
            principal: principal,
            totalInterest: totalInterest,
            processingFee: processingFee,
            totalInsurance: totalInsurance,
            totalAmount: totalAmount,
            monthlyRate: monthlyRate * 100,
            interestRatio: interestRatio,
            effectiveRate: effectiveRate,
            totalPayments: tenure,
            monthlyInsurance: monthlyInsurance,
            totalMonthlyPayment: totalMonthlyPayment
        });

        // Generate amortization schedule
        this.generateAmortizationSchedule(principal, monthlyRate, tenure, emi);

        // Update chart
        this.updateChart(principal, totalInterest);
    },

    updateResults: function(results) {
        // Format currency
        const formatCurrency = (amount) => {
            return '₹' + Math.round(amount).toLocaleString('en-IN');
        };

        // Format percentage
        const formatPercentage = (rate) => {
            return rate.toFixed(2) + '%';
        };

        // Update elements
        this.monthlyEmiElement.textContent = formatCurrency(results.monthlyEmi);
        this.principalAmountElement.textContent = formatCurrency(results.principal);
        this.totalInterestElement.textContent = formatCurrency(results.totalInterest);
        this.totalProcessingFeeElement.textContent = formatCurrency(results.processingFee);
        this.totalInsuranceElement.textContent = formatCurrency(results.totalInsurance);
        this.totalAmountElement.textContent = formatCurrency(results.totalAmount);
        this.monthlyRateElement.textContent = formatPercentage(results.monthlyRate);
        this.interestRatioElement.textContent = formatPercentage(results.interestRatio);
        this.effectiveRateElement.textContent = formatPercentage(results.effectiveRate);
        this.totalPaymentsElement.textContent = results.totalPayments + ' months';
    },

    generateAmortizationSchedule: function(principal, monthlyRate, tenure, emi) {
        const tbody = this.amortizationTable.querySelector('tbody');
        tbody.innerHTML = '';

        let remainingPrincipal = principal;
        
        for (let month = 1; month <= tenure; month++) {
            const interestPayment = remainingPrincipal * monthlyRate;
            const principalPayment = emi - interestPayment;
            remainingPrincipal -= principalPayment;

            // Create row
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${month}</td>
                <td>₹${Math.round(emi).toLocaleString('en-IN')}</td>
                <td>₹${Math.round(principalPayment).toLocaleString('en-IN')}</td>
                <td>₹${Math.round(interestPayment).toLocaleString('en-IN')}</td>
                <td>₹${Math.round(Math.max(0, remainingPrincipal)).toLocaleString('en-IN')}</td>
            `;

            tbody.appendChild(row);

            // Prevent negative remaining principal
            if (remainingPrincipal <= 0) {
                break;
            }
        }
    },

    initializeChart: function() {
        if (!this.chartCanvas) return;

        const ctx = this.chartCanvas.getContext('2d');
        
        // Simple chart implementation (you can replace with Chart.js if preferred)
        this.chart = {
            canvas: this.chartCanvas,
            ctx: ctx,
            data: { principal: 0, interest: 0 }
        };
    },

    updateChart: function(principal, totalInterest) {
        if (!this.chart) return;

        const ctx = this.chart.ctx;
        const canvas = this.chart.canvas;
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Simple pie chart
        const total = principal + totalInterest;
        if (total <= 0) return;

        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = Math.min(centerX, centerY) - 20;

        let currentAngle = -Math.PI / 2;

        // Principal slice
        const principalAngle = (principal / total) * 2 * Math.PI;
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + principalAngle);
        ctx.closePath();
        ctx.fillStyle = '#d92128';
        ctx.fill();

        // Interest slice
        currentAngle += principalAngle;
        const interestAngle = (totalInterest / total) * 2 * Math.PI;
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + interestAngle);
        ctx.closePath();
        ctx.fillStyle = '#ff6b7a';
        ctx.fill();

        // Add labels
        ctx.fillStyle = '#333';
        ctx.font = '14px Inter';
        ctx.textAlign = 'center';
        ctx.fillText('Principal vs Interest', centerX, canvas.height - 5);
    },

    shareCalculation: function() {
        const url = new URL(window.location.href);
        url.searchParams.set('loan_amount', this.loanAmountInput.value);
        url.searchParams.set('interest_rate', this.interestRateInput.value);
        url.searchParams.set('tenure', this.tenureInput.value);

        if (navigator.share) {
            navigator.share({
                title: 'Kinetic EV EMI Calculator',
                text: 'Check out my EMI calculation for Kinetic Electric Scooter',
                url: url.toString()
            });
        } else {
            // Fallback - copy to clipboard
            navigator.clipboard.writeText(url.toString()).then(() => {
                alert('Calculation link copied to clipboard!');
            });
        }
    },

    downloadSchedule: function() {
        // Simple CSV download
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Month,EMI,Principal,Interest,Outstanding\n";

        const rows = this.amortizationTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const rowData = Array.from(cells).map(cell => 
                cell.textContent.replace(/₹|,/g, '')
            ).join(',');
            csvContent += rowData + "\n";
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "kinetic_emi_schedule.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};