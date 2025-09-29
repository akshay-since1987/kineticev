/**
 * =======================
 * EMI Calculator Module
 * =======================
 * Handles EMI calculation functionality on homepage
 */

export const EmiCalculator = {
    init: function() {
        // Only run if EMI calculator elements exist
        const loanAmountInput = document.getElementById('loan-amount');
        const interestRateInput = document.getElementById('interest-rate');
        const tenureSelect = document.getElementById('tenure');
        // const emiResult = document.getElementById('emi-result');
        const calculateEmiBtn = document.getElementById('calculate-emi-btn');

        if (!loanAmountInput || !interestRateInput || !tenureSelect || !calculateEmiBtn) {
            return; // Exit if EMI calculator elements not found
        }

        this.loanAmountInput = loanAmountInput;
        this.interestRateInput = interestRateInput;
        this.tenureSelect = tenureSelect;
        // this.emiResult = emiResult;
        this.calculateEmiBtn = calculateEmiBtn;

        this.bindEvents();
        // this.calculateEMI(); // Initial calculation
        this.updateButtonUrl(); // Set initial URL
    },

    // calculateEMI: function() {
    //     const principal = parseFloat(this.loanAmountInput.value) || 0;
    //     const rate = parseFloat(this.interestRateInput.value) / 100 / 12; // Monthly interest rate
    //     const tenure = parseInt(this.tenureSelect.value) || 1;

    //     if (principal > 0 && rate > 0 && tenure > 0) {
    //         const emi = (principal * rate * Math.pow(1 + rate, tenure)) / (Math.pow(1 + rate, tenure) - 1);
    //         this.emiResult.textContent = '₹' + Math.round(emi).toLocaleString('en-IN');
    //     } else {
    //         this.emiResult.textContent = '₹0';
    //     }
    // },

    buildEmiCalculatorUrl: function() {
        const loanAmount = this.loanAmountInput.value || '111499';
        const interestRate = this.interestRateInput.value || '12';
        const tenure = this.tenureSelect.value || '36';
        
        const params = new URLSearchParams({
            loan_amount: loanAmount,
            interest_rate: interestRate,
            tenure: tenure
        });
        
        return `/emi-calculator?${params.toString()}`;
    },

    updateButtonUrl: function() {
        this.calculateEmiBtn.href = this.buildEmiCalculatorUrl();
    },

    bindEvents: function() {
        // Add event listeners for URL updates only (no calculations)
        this.loanAmountInput.addEventListener('input', () => {
            // this.calculateEMI();
            this.updateButtonUrl();
        });
        
        this.interestRateInput.addEventListener('input', () => {
            // this.calculateEMI();
            this.updateButtonUrl();
        });
        
        this.tenureSelect.addEventListener('change', () => {
            // this.calculateEMI();
            this.updateButtonUrl();
        });

        // Handle button click - redirect to EMI calculator page with parameters
        this.calculateEmiBtn.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = this.buildEmiCalculatorUrl();
        });
    }
};