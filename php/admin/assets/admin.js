// KineticEV Admin Panel JavaScript - Version 2.4 - DataTable Reinitialization Prevention
// Status rendering and DataTable fixes - August 20, 2025
// Global admin panel instance
window.adminPanelVersion = '2.4';
window.adminPanel = null;

class AdminPanel {
    constructor() {
        this.currentSection = 'dashboard';
        this.dataTables = {};
        this.currentUser = null;
        this.loadedSections = new Set(); // Track which sections have been loaded
        this.init();
    }

    init() {
        console.log('AdminPanel v2.5 initializing - DataTable reinitialization prevention active, detail modals implemented');
        this.setupGlobalFetchHandler();
        this.setupNavigation();
        this.loadCurrentUser();
        this.loadDashboard();
        this.setupLogViewer();
    }

    // Setup global fetch error handler for authentication redirects
    setupGlobalFetchHandler() {
        const originalFetch = window.fetch;
        const self = this;
        
        window.fetch = async (...args) => {
            try {
                const response = await originalFetch(...args);
                
                // Check for 401 Unauthorized responses
                if (response.status === 401) {
                    // Show user-friendly message
                    self.showError('Your session has expired. Redirecting to login...');
                    
                    // Try to parse JSON response for redirect instruction
                    try {
                        const errorData = await response.clone().json();
                        if (errorData.redirect) {
                            setTimeout(() => {
                                window.location.href = errorData.redirect;
                            }, 2000);
                            return;
                        }
                    } catch (e) {
                        // If JSON parsing fails, just redirect to login
                    }
                    
                    // Redirect to login page after short delay
                    setTimeout(() => {
                        window.location.href = 'login';
                    }, 2000);
                    return;
                }
                
                return response;
            } catch (error) {
                throw error;
            }
        };
    }

    setupNavigation() {
        // Handle sidebar navigation
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                // Don't prevent default for logout link - handle both with and without .php extension
                const href = link.getAttribute('href');
                if (href === 'logout' || href === 'logout.php') {
                    console.log('Logout link clicked, navigating to: ' + href);
                    return; // Allow normal navigation for logout
                }

                e.preventDefault();
                const section = link.dataset.section;
                if (section) {
                    this.showSection(section);
                    this.updateActiveNavigation(link);
                }
            });
        });
    }

    updateActiveNavigation(activeLink) {
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.classList.remove('active');
        });
        activeLink.classList.add('active');
    }

    showSection(section) {
        console.log(`Showing section: ${section}`);
        console.trace(`showSection trace for: ${section}`);

        // Hide all sections
        document.querySelectorAll('.content-section').forEach(sec => {
            sec.style.display = 'none';
        });

        // Show target section
        const targetSection = document.getElementById(`${section}-section`);
        console.log(`Target section element:`, targetSection);

        if (targetSection) {
            targetSection.style.display = 'block';
            this.currentSection = section;

            // Load section-specific content
            switch (section) {
                case 'dashboard':
                    this.loadDashboard();
                    break;
                case 'analytics':
                    this.loadAnalytics();
                    break;
                case 'user_management':
                    this.loadUserManagement();
                    break;
                case 'email_logs':
                    this.loadEmailLogs();
                    break;
                case 'system_logs':
                    this.loadSystemLogs();
                    break;
                case 'dealerships':
                    // Handle dealerships section specifically
                    console.log('Loading dealerships section');
                    // Only load if not already loaded unless it's a refresh
                    if (!this.loadedSections.has('dealerships')) {
                        // Directly call the dealership.js function to avoid double delegation
                        if (typeof window.loadDealerships === 'function') {
                            console.log('Calling window.loadDealerships directly');
                            window.loadDealerships().then(() => {
                                this.loadedSections.add('dealerships');
                                console.log('Dealerships loaded successfully, added to loadedSections');
                            }).catch(error => {
                                console.error('Error loading dealerships:', error);
                            });
                        } else {
                            console.error('window.loadDealerships function not available');
                        }
                    } else {
                        console.log('Dealerships section already loaded, skipping duplicate load');
                    }
                    break;
                default:
                    console.log(`Checking if ${section} is a valid table...`);
                    if (this.isValidTable(section)) {
                        console.log(`Loading table data for ${section}`);
                        this.loadTableData(section);
                    } else {
                        console.warn(`Section ${section} is not recognized as a valid table or section`);
                    }
            }
        } else {
            console.error(`Target section element not found: ${section}-section`);
        }
    }

    isValidTable(tableName) {
        const validTables = ['transactions', 'test_drives', 'contacts'];
        console.log(`Checking if ${tableName} is valid. Valid tables:`, validTables);
        return validTables.includes(tableName);
    }

    async loadDashboard() {
        try {
            const response = await fetch('api?action=dashboard_stats');
            const stats = await response.json();

            if (stats.error) {
                throw new Error(stats.error);
            }

            // Update stat cards
            document.getElementById('total-transactions').textContent = stats.total_transactions || 0;
            document.getElementById('total-test-drives').textContent = stats.total_test_drives || 0;
            document.getElementById('total-contacts').textContent = stats.total_contacts || 0;
            document.getElementById('total-revenue').textContent = `₹${this.formatNumber(stats.total_revenue || 0)}`;

            // Make dashboard cards clickable
            this.setupDashboardCardClickHandlers();

            // Update recent activity
            this.updateRecentActivity(stats.recent_activity || []);

        } catch (error) {
            console.error('Error loading dashboard:', error);
            this.showError('Failed to load dashboard data');
        }
    }

    setupDashboardCardClickHandlers() {
        // Make transaction card clickable
        const transactionCard = document.getElementById('total-transactions')?.closest('.stat-card, .card');
        if (transactionCard) {
            transactionCard.classList.add('clickable');
            transactionCard.style.cursor = 'pointer';
            transactionCard.addEventListener('click', () => {
                this.showSection('transactions');
                this.updateActiveNavigation(document.querySelector('[data-section="transactions"]'));
            });
        }

        // Make test drives card clickable
        const testDriveCard = document.getElementById('total-test-drives')?.closest('.stat-card, .card');
        if (testDriveCard) {
            testDriveCard.classList.add('clickable');
            testDriveCard.style.cursor = 'pointer';
            testDriveCard.addEventListener('click', () => {
                this.showSection('test_drives');
                this.updateActiveNavigation(document.querySelector('[data-section="test_drives"]'));
            });
        }

        // Make contacts card clickable
        const contactsCard = document.getElementById('total-contacts')?.closest('.stat-card, .card');
        if (contactsCard) {
            contactsCard.classList.add('clickable');
            contactsCard.style.cursor = 'pointer';
            contactsCard.addEventListener('click', () => {
                this.showSection('contacts');
                this.updateActiveNavigation(document.querySelector('[data-section="contacts"]'));
            });
        }

        // Make revenue card clickable (goes to analytics)
        const revenueCard = document.getElementById('total-revenue')?.closest('.stat-card, .card');
        if (revenueCard) {
            revenueCard.classList.add('clickable');
            revenueCard.style.cursor = 'pointer';
            revenueCard.addEventListener('click', () => {
                this.showSection('analytics');
                this.updateActiveNavigation(document.querySelector('[data-section="analytics"]'));
            });
        }
    }

    updateRecentActivity(activities) {
        const container = document.getElementById('recent-activity');
        if (!activities.length) {
            container.innerHTML = '<p class="text-muted">No recent activity</p>';
            return;
        }

        const html = activities.map(activity => {
            const icon = this.getActivityIcon(activity.type);
            const time = new Date(activity.created_at).toLocaleString();
            return `
                <div class="d-flex align-items-center mb-3 p-3 bg-light rounded">
                    <div class="me-3">
                        <i class="${icon} fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${activity.name}</h6>
                        <small class="text-muted">${activity.type.replace('_', ' ').toUpperCase()} - ${activity.reference}</small>
                        <br><small class="text-muted">${time}</small>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    getActivityIcon(type) {
        const icons = {
            'transaction': 'fas fa-credit-card',
            'test_drive': 'fas fa-motorcycle',
            'contact': 'fas fa-envelope'
        };
        return icons[type] || 'fas fa-circle';
    }

    getDefaultOrderForTable(tableName) {
        // Set default ordering based on table type
        if (tableName === 'transactions') {
            // For transactions, order by first column (created_at) descending to show latest first
            return [[0, 'desc']];
        } else if (tableName === 'test_drives' || tableName === 'contacts') {
            // For other tables, also show latest first (first column is created_at)
            return [[0, 'desc']];
        }
        
        // Default fallback
        return [[0, 'desc']];
    }

    async loadTableData(tableName, page = 1) {
        console.log(`=== LOADING TABLE DATA FOR: ${tableName} ===`);
        console.log(`Current section:`, this.currentSection);
        console.log(`Existing DataTable instance:`, this.dataTables[tableName]);
        console.log(`Table element exists:`, document.getElementById(`${tableName}-table`));

        try {
            // Check if DataTable already exists and is working
            if (this.dataTables[tableName] && $.fn.DataTable.isDataTable(`#${tableName}-table`)) {
                console.log(`DataTable already initialized for ${tableName}, skipping reinitialization`);
                // Just reload the data instead of reinitializing
                this.dataTables[tableName].ajax.reload();
                return;
            }

            // For server-side pagination, we'll let DataTable handle the requests
            this.initializeDataTable(tableName, { columns: [], data: [] });
            this.createAdvancedFilters(tableName, []);

            // We'll load column info separately for filters
            this.loadTableColumns(tableName);

        } catch (error) {
            console.error(`Error loading table ${tableName}:`, error);

            // Show error in result info
            const resultInfo = document.getElementById(`${tableName}-result-info`);
            if (resultInfo) {
                resultInfo.innerHTML = `
                    <i class="fas fa-exclamation-triangle me-2 text-danger"></i>Error loading data: ${error.message}
                `;
            }

            this.showError(`Failed to load ${tableName} data: ${error.message}`);
        }
    }

    async loadTableColumns(tableName) {
        try {
            // Use our hardcoded column definitions instead of API call
            const columns = this.getTableColumns(tableName);
            
            // Extract just the column names for filter creation
            const columnNames = columns
                .map(col => col.data)
                .filter(name => name !== null); // Filter out null data columns (like Actions)
            
            this.createAdvancedFilters(tableName, columnNames);
        } catch (error) {
            console.error(`Error loading columns for ${tableName}:`, error);
        }
    }

    createAdvancedFilters(tableName, columns) {
        const filterContainer = document.getElementById(`${tableName}-filters`);
        if (!filterContainer) return;

        // Clear existing filters
        filterContainer.innerHTML = '';

        // Create filter controls
        const filtersHTML = `
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Search All Columns:</label>
                    <input type="text" class="form-control" id="${tableName}-global-search" 
                           placeholder="Search across all columns...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date Range:</label>
                    <select class="form-select" id="${tableName}-date-filter">
                        <option value="">All Time</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="last7days">Last 7 Days</option>
                        <option value="last30days">Last 30 Days</option>
                        <option value="thismonth">This Month</option>
                        <option value="lastmonth">Last Month</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="col-md-4" id="${tableName}-custom-date" style="display: none;">
                    <label class="form-label">Custom Date Range:</label>
                    <div class="d-flex gap-2">
                        <input type="date" class="form-control" id="${tableName}-start-date">
                        <input type="date" class="form-control" id="${tableName}-end-date">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="adminPanel.applyFilters('${tableName}')">
                            <i class="fas fa-search"></i> Apply
                        </button>
                        <button class="btn btn-outline-secondary" onclick="adminPanel.clearFilters('${tableName}')">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                ${this.createColumnFilters(tableName, columns)}
            </div>
        `;

        filterContainer.innerHTML = filtersHTML;

        // Setup event listeners
        this.setupFilterEventListeners(tableName);
    }

    createColumnFilters(tableName, columns) {
        const importantColumns = columns.filter(col =>
            !col.includes('created_at') &&
            !col.includes('updated_at') &&
            col !== 'id'
        ).slice(0, 4); // Show first 4 non-timestamp columns

        return importantColumns.map(column => {
            const displayName = column.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());

            if (column.includes('status') || column.includes('type') || column.includes('role')) {
                return `
                    <div class="col-md-3">
                        <label class="form-label">${displayName}:</label>
                        <select class="form-select" id="${tableName}-${column}-filter">
                            <option value="">All ${displayName}</option>
                            <option value="loading">Loading...</option>
                        </select>
                    </div>
                `;
            } else {
                return `
                    <div class="col-md-3">
                        <label class="form-label">${displayName}:</label>
                        <input type="text" class="form-control" id="${tableName}-${column}-filter" 
                               placeholder="Filter by ${displayName.toLowerCase()}...">
                    </div>
                `;
            }
        }).join('');
    }

    setupFilterEventListeners(tableName) {
        // Global search with debouncing
        const globalSearch = document.getElementById(`${tableName}-global-search`);
        if (globalSearch) {
            let timeout;
            globalSearch.addEventListener('input', (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    // Update DataTable's built-in search as well
                    if (this.dataTables[tableName]) {
                        this.dataTables[tableName].search(e.target.value).draw();
                    } else {
                        this.applyFilters(tableName);
                    }
                }, 500);
            });
        }

        // Date filter change
        const dateFilter = document.getElementById(`${tableName}-date-filter`);
        const customDateDiv = document.getElementById(`${tableName}-custom-date`);
        if (dateFilter && customDateDiv) {
            dateFilter.addEventListener('change', (e) => {
                if (e.target.value === 'custom') {
                    customDateDiv.style.display = 'block';
                } else {
                    customDateDiv.style.display = 'none';
                    this.applyFilters(tableName);
                }
            });
        }

        // Custom date inputs
        const startDate = document.getElementById(`${tableName}-start-date`);
        const endDate = document.getElementById(`${tableName}-end-date`);
        if (startDate && endDate) {
            startDate.addEventListener('change', () => this.applyFilters(tableName));
            endDate.addEventListener('change', () => this.applyFilters(tableName));
        }

        // Load dropdown options for select filters
        this.loadFilterOptions(tableName);
    }

    async loadFilterOptions(tableName) {
        try {
            const response = await fetch(`api?action=filter_options&table=${tableName}`);
            const options = await response.json();

            Object.keys(options).forEach(column => {
                const select = document.getElementById(`${tableName}-${column}-filter`);
                if (select) {
                    select.innerHTML = `<option value="">All ${column.replace('_', ' ')}</option>`;
                    options[column].forEach(option => {
                        select.innerHTML += `<option value="${option}">${option}</option>`;
                    });
                }
            });
        } catch (error) {
            console.error('Error loading filter options:', error);
        }
    }

    async applyFilters(tableName) {
        try {
            // For server-side processing, we just need to reload the DataTable
            // The filters will be collected in the ajax data function
            if (this.dataTables[tableName]) {
                this.dataTables[tableName].ajax.reload();
            }

        } catch (error) {
            console.error(`Error applying filters for ${tableName}:`, error);
            this.showError(`Failed to apply filters for ${tableName}`);
        }
    }

    collectFilters(tableName) {
        const filters = {};

        // Return empty filters if no filter container exists yet
        const filterContainer = document.getElementById(`${tableName}-filters`);
        if (!filterContainer) {
            return filters;
        }

        // Global search
        const globalSearch = document.getElementById(`${tableName}-global-search`);
        if (globalSearch && globalSearch.value.trim()) {
            filters.search = globalSearch.value.trim();
        }

        // Date filter
        const dateFilter = document.getElementById(`${tableName}-date-filter`);
        if (dateFilter && dateFilter.value) {
            if (dateFilter.value === 'custom') {
                const startDate = document.getElementById(`${tableName}-start-date`);
                const endDate = document.getElementById(`${tableName}-end-date`);
                if (startDate && endDate && startDate.value && endDate.value) {
                    filters.start_date = startDate.value;
                    filters.end_date = endDate.value;
                }
            } else {
                filters.date_range = dateFilter.value;
            }
        }

        // Column-specific filters
        const filterInputs = document.querySelectorAll(`[id^="${tableName}-"][id$="-filter"]:not(#${tableName}-global-search):not(#${tableName}-date-filter)`);
        filterInputs.forEach(input => {
            if (input.value.trim()) {
                const columnName = input.id.replace(`${tableName}-`, '').replace('-filter', '');
                filters[`filter_${columnName}`] = input.value.trim();
            }
        });

        return filters;
    }

    updateDataTable(tableName, data) {
        // Destroy existing DataTable
        if (this.dataTables[tableName]) {
            this.dataTables[tableName].destroy();
        }

        // Reinitialize with new data
        this.initializeDataTable(tableName, data);

        // Show result count
        const resultInfo = document.getElementById(`${tableName}-result-info`);
        if (resultInfo) {
            let infoText = `Showing ${data.data.length} of ${data.totalRecords} total records`;

            if (data.appliedFilters && data.appliedFilters > 0) {
                infoText += ` (${data.appliedFilters} filter${data.appliedFilters > 1 ? 's' : ''} applied)`;
            }

            resultInfo.innerHTML = `
                <i class="fas fa-info-circle me-2"></i>${infoText}
                ${data.appliedFilters > 0 ? '<span class="badge bg-primary ms-2">Filtered</span>' : ''}
            `;
        }
    }

    clearFilters(tableName) {
        // Clear all filter inputs
        const filterInputs = document.querySelectorAll(`[id^="${tableName}-"][id$="-filter"], [id^="${tableName}-"][id$="-search"]`);
        filterInputs.forEach(input => {
            if (input.type === 'select-one') {
                input.selectedIndex = 0;
            } else {
                input.value = '';
            }
        });

        // Hide custom date range
        const customDateDiv = document.getElementById(`${tableName}-custom-date`);
        if (customDateDiv) {
            customDateDiv.style.display = 'none';
        }

        // Clear DataTable's built-in search and reload
        if (this.dataTables[tableName]) {
            this.dataTables[tableName].search('').draw();
        }
    }

    exportFiltered(tableName, format) {
        const filters = this.collectFilters(tableName);
        const queryString = new URLSearchParams(filters).toString();
        const url = `api?action=export&table=${tableName}&format=${format}&${queryString}`;
        window.open(url, '_blank');
    }

    initializeDataTable(tableName, data) {
        const tableId = `${tableName}-table`;
        const table = document.getElementById(tableId);

        console.log(`Attempting to initialize DataTable for: ${tableName}`);
        console.log(`Looking for table element with ID: ${tableId}`);
        console.log(`Table element found:`, table);

        if (!table) {
            console.error(`Table element not found: ${tableId}`);
            this.showError(`Cannot find table element: ${tableId}`);
            return;
        }

        console.log(`Initializing DataTable for: ${tableName}, existing instance:`, this.dataTables[tableName]);
        console.log(`jQuery DataTable check:`, $.fn.DataTable.isDataTable(`#${tableId}`));

        // Enhanced DataTable destruction with better error handling
        try {
            // Step 1: Destroy our own reference
            if (this.dataTables[tableName]) {
                console.log(`Destroying existing DataTable instance for: ${tableName}`);
                try {
                    this.dataTables[tableName].destroy();
                } catch (destroyError) {
                    console.warn(`Error destroying our DataTable instance:`, destroyError);
                }
                delete this.dataTables[tableName];
            }

            // Step 2: Force clear any jQuery DataTable references
            if ($.fn.DataTable.isDataTable(`#${tableId}`)) {
                console.log(`Destroying jQuery DataTable instance for: ${tableId}`);
                try {
                    $(`#${tableId}`).DataTable().destroy();
                } catch (jqueryDestroyError) {
                    console.warn(`Error destroying jQuery DataTable:`, jqueryDestroyError);
                }
            }

            // Step 3: Remove DataTable classes and wrapper
            const $table = $(`#${tableId}`);
            $table.removeClass('dataTable');
            
            // Remove DataTable wrapper if it exists
            if ($table.parent('.dataTables_wrapper').length) {
                console.log(`Removing DataTable wrapper for: ${tableId}`);
                $table.unwrap('.dataTables_wrapper');
            }

        } catch (error) {
            console.warn(`Error during DataTable destruction for ${tableName}:`, error);
        }

        // Clear table content
        table.innerHTML = '';
        
        // Reset table classes
        table.className = 'table table-striped table-hover';

        // Create header structure for server-side processing
        this.createTableHeader(tableName, table);

        // Initialize DataTable with server-side processing
        const dataTableConfig = {
            processing: true,
            serverSide: true,
            pageLength: 10,
            searching: true, // Enable search functionality
            order: this.getDefaultOrderForTable(tableName),
            ajax: {
                url: 'api',
                data: function(d) {
                    console.log('NEW DataTables format:', d);
                    
                    // Base DataTable parameters
                    const requestData = {
                        action: 'table_data',
                        table: tableName,
                        start: d.start,
                        length: d.length,
                        search: d.search.value || '',
                        draw: d.draw,
                        'order[0][column]': d.order[0].column,
                        'order[0][dir]': d.order[0].dir,
                        _timestamp: Date.now(), // Force cache bust
                        _v: 'v2.0' // Version identifier
                    };

                    // Add advanced filters if they exist
                    const filters = window.adminPanel.collectFilters(tableName);
                    
                    // Add search parameter
                    if (filters.search) {
                        requestData.search = filters.search;
                    }
                    
                    // Add date range filters
                    if (filters.date_range) {
                        requestData.date_range = filters.date_range;
                    }
                    if (filters.start_date) {
                        requestData.start_date = filters.start_date;
                    }
                    if (filters.end_date) {
                        requestData.end_date = filters.end_date;
                    }
                    
                    // Add column-specific filters
                    Object.keys(filters).forEach(key => {
                        if (key.startsWith('filter_')) {
                            requestData[key] = filters[key];
                        }
                    });

                    console.log('Request data with filters:', requestData);
                    return requestData;
                },
                dataSrc: function(json) {
                    console.log('DataTable response:', json);
                    
                    // Update result info with pagination details
                    const resultInfo = document.getElementById(`${tableName}-result-info`);
                    if (resultInfo && json.recordsTotal !== undefined) {
                        const start = parseInt(json.start || 0) + 1;
                        const end = Math.min(start + parseInt(json.length || 10) - 1, json.recordsFiltered);
                        const total = json.recordsTotal;
                        const filtered = json.recordsFiltered;
                        
                        let infoText = `<i class="fas fa-info-circle me-2"></i>Showing ${start} to ${end} of ${filtered} entries`;
                        if (filtered !== total) {
                            infoText += ` (filtered from ${total} total entries)`;
                        }
                        
                        resultInfo.innerHTML = infoText;
                    }
                    
                    return json.data || [];
                }
            },
            columns: this.getTableColumns(tableName),
            language: {
                processing: "Loading..."
            }
        };

        try {
            console.log('Initializing DataTable for:', tableName);
            console.log('Final check - is table still a DataTable?', $.fn.DataTable.isDataTable(`#${tableId}`));
            
            // Final safety check
            if ($.fn.DataTable.isDataTable(`#${tableId}`)) {
                console.error(`Table ${tableId} is still detected as DataTable after destruction! Aborting initialization.`);
                this.showError(`Cannot initialize DataTable - table is still active. Please refresh the page.`);
                return;
            }
            
            console.log('DataTable config:', dataTableConfig);
            
            // Initialize DataTable
            this.dataTables[tableName] = $(table).DataTable(dataTableConfig);
            
            // Add event listeners for debugging
            this.dataTables[tableName].on('xhr', function() {
                console.log('DataTable XHR event fired for:', tableName);
            });
            
            this.dataTables[tableName].on('page', function() {
                console.log('DataTable page event fired for:', tableName);
            });
            
            this.dataTables[tableName].on('error.dt', function(e, settings, techNote, message) {
                console.error('DataTable error:', message, techNote);
            });
            
            console.log('DataTable initialized successfully for:', tableName);
        } catch (error) {
            console.error('Error initializing DataTable for', tableName, ':', error);
            this.showError(`Failed to initialize table: ${error.message}`);
        }
    }

    createTableHeader(tableName, table) {
        const thead = document.createElement('thead');
        thead.className = 'table-dark';
        const headerRow = document.createElement('tr');

        const columns = this.getTableColumns(tableName);
        
        columns.forEach(column => {
            const th = document.createElement('th');
            th.textContent = column.title;
            headerRow.appendChild(th);
        });

        thead.appendChild(headerRow);
        table.appendChild(thead);

        // Create empty tbody for DataTable
        const tbody = document.createElement('tbody');
        table.appendChild(tbody);
    }

    getTableColumns(tableName) {
        console.log(`Getting columns for table: ${tableName}`);
        
        if (tableName === 'transactions') {
            return [
                { 
                    data: 'created_at', 
                    title: 'CREATED AT',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            const date = new Date(data);
                            return date.toLocaleString();
                        }
                        return data || '-';
                    }
                },
                { 
                    data: 'transaction_id', 
                    title: 'TRANSACTION ID',
                    render: (data, type, row) => data || '-'
                },
                { 
                    data: 'firstname', 
                    title: 'FIRST NAME',
                    render: (data, type, row) => data || '-'
                },
                { 
                    data: 'pincode', 
                    title: 'PINCODE',
                    render: (data, type, row) => data || '-'
                },
                { 
                    data: 'variant', 
                    title: 'VARIANT',
                    render: (data, type, row) => data || '-'
                },
                { 
                    data: 'color', 
                    title: 'COLOR',
                    render: (data, type, row) => data || '-'
                },
                { 
                    data: 'status', 
                    title: 'STATUS',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            let badgeClass = 'bg-secondary';
                            switch(data.toLowerCase()) {
                                case 'completed':
                                case 'success':
                                    badgeClass = 'bg-success';
                                    break;
                                case 'pending':
                                    badgeClass = 'bg-warning';
                                    break;
                                case 'failed':
                                case 'error':
                                    badgeClass = 'bg-danger';
                                    break;
                            }
                            const result = `<span class="badge ${badgeClass}">${data}</span>`;
                            console.log('Status render result:', result);
                            return result;
                        }
                        return data || '-';
                    }
                },
                {
                    data: null,
                    title: 'ACTIONS',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        console.log('Actions render function called with:', {data, type, row});
                        if (type === 'display') {
                            const status = row.status ? row.status.toLowerCase() : '';
                            const transactionId = row.transaction_id;
                            
                            let actions = `<button type="button" class="btn btn-sm btn-info me-1" 
                                                   onclick="window.adminPanel.showTransactionDetails('${transactionId}')" 
                                                   title="View full details">
                                               <i class="fas fa-eye"></i>
                                           </button>`;
                            
                            if (status === 'pending' && transactionId) {
                                actions += `<button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="window.open('/api/check-status?txnid=${transactionId}', '_blank')" 
                                                    title="Check payment status">
                                                <i class="fas fa-search"></i>
                                            </button>`;
                            }
                            
                            return actions;
                        }
                        return '';
                    }
                }
            ];
        } else if (tableName === 'test_drives') {
            return [
                { 
                    data: 'id', 
                    title: 'ID',
                    render: function(data, type, row) {
                        return data || '-';
                    }
                },
                { 
                    data: 'full_name', 
                    title: 'NAME',
                    render: function(data, type, row) {
                        return data || '-';
                    }
                },
                { 
                    data: 'email', 
                    title: 'EMAIL', 
                    render: function(data, type, row) { 
                        return data ? `<a href="mailto:${data}">${data}</a>` : '-'; 
                    } 
                },
                { 
                    data: 'phone', 
                    title: 'PHONE', 
                    render: function(data, type, row) { 
                        return data ? `<a href="tel:${data}">${data}</a>` : '-'; 
                    } 
                },
                { 
                    data: 'pincode', 
                    title: 'PINCODE',
                    render: function(data, type, row) {
                        return data || '-';
                    }
                },
                { 
                    data: 'date', 
                    title: 'PREFERRED DATE',
                    render: function(data, type, row) {
                        return data || '-';
                    }
                },
                { 
                    data: 'message', 
                    title: 'MESSAGE', 
                    render: function(data, type, row) { 
                        return data && data.length > 50 ? data.substring(0, 50) + '...' : (data || '-'); 
                    } 
                },
                { 
                    data: 'created_at', 
                    title: 'CREATED AT', 
                    render: function(data, type, row) { 
                        return data ? new Date(data).toLocaleString() : '-'; 
                    } 
                },
                {
                    data: null,
                    title: 'ACTIONS',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        console.log('Test Drives Actions render function called with:', {data, type, row});
                        if (type === 'display') {
                            const testDriveId = row.id;
                            const email = row.email;
                            
                            let actions = `<button type="button" class="btn btn-sm btn-info me-1" 
                                                   onclick="window.adminPanel.showTestDriveDetails('${testDriveId}')" 
                                                   title="View full details">
                                               <i class="fas fa-eye"></i>
                                           </button>`;
                            
                            if (email) {
                                actions += `<button type="button" class="btn btn-sm btn-success" 
                                                    onclick="window.location.href='mailto:${email}'" 
                                                    title="Send email">
                                                <i class="fas fa-envelope"></i>
                                            </button>`;
                            }
                            
                            return actions;
                        }
                        return '';
                    }
                }
            ];
        } else if (tableName === 'contacts') {
            console.log('Using contacts table columns with full_name');
            return [
                { 
                    data: 'id', 
                    title: 'ID',
                    render: function(data, type, row) {
                        return data || '-';
                    }
                },
                { 
                    data: 'full_name', 
                    title: 'NAME',
                    render: function(data, type, row) {
                        return data || '-';
                    }
                },
                { 
                    data: 'email', 
                    title: 'EMAIL', 
                    render: function(data, type, row) { 
                        return data ? `<a href="mailto:${data}">${data}</a>` : '-'; 
                    } 
                },
                { 
                    data: 'phone', 
                    title: 'PHONE', 
                    render: function(data, type, row) { 
                        return data ? `<a href="tel:${data}">${data}</a>` : '-'; 
                    } 
                },
                { 
                    data: 'help_type', 
                    title: 'TYPE', 
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            let badgeClass = 'bg-info';
                            switch(data.toLowerCase()) {
                                case 'support':
                                    badgeClass = 'bg-primary';
                                    break;
                                case 'sales':
                                    badgeClass = 'bg-success';
                                    break;
                                case 'complaint':
                                    badgeClass = 'bg-warning';
                                    break;
                                case 'other':
                                    badgeClass = 'bg-secondary';
                                    break;
                            }
                            return `<span class="badge ${badgeClass}">${data.toUpperCase()}</span>`;
                        }
                        return data ? data.toUpperCase() : '-';
                    }
                },
                { 
                    data: 'message', 
                    title: 'MESSAGE', 
                    render: function(data, type, row) { 
                        return data && data.length > 50 ? data.substring(0, 50) + '...' : (data || '-'); 
                    } 
                },
                { 
                    data: 'created_at', 
                    title: 'CREATED AT', 
                    render: function(data, type, row) { 
                        return data ? new Date(data).toLocaleString() : '-'; 
                    } 
                },
                {
                    data: null,
                    title: 'ACTIONS',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        console.log('Contacts Actions render function called with:', {data, type, row});
                        if (type === 'display') {
                            const contactId = row.id;
                            const email = row.email;
                            const phone = row.phone;
                            
                            let actions = `<button type="button" class="btn btn-sm btn-info me-1" 
                                                   onclick="window.adminPanel.showContactDetails('${contactId}')" 
                                                   title="View full details">
                                               <i class="fas fa-eye"></i>
                                           </button>`;
                            
                            if (email) {
                                actions += `<button type="button" class="btn btn-sm btn-success me-1" 
                                                    onclick="window.location.href='mailto:${email}'" 
                                                    title="Send email">
                                                <i class="fas fa-envelope"></i>
                                            </button>`;
                            }
                            
                            if (phone) {
                                actions += `<button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="window.location.href='tel:${phone}'" 
                                                    title="Call">
                                                <i class="fas fa-phone"></i>
                                            </button>`;
                            }
                            
                            return actions;
                        }
                        return '';
                    }
                }
            ];
        } else {
            // Default columns for other tables
            return [
                { data: 'id', title: 'ID' },
                { data: 'name', title: 'NAME' },
                { data: 'created_at', title: 'CREATED AT', render: (data) => data ? new Date(data).toLocaleString() : '-' }
            ];
        }
    }

    async loadAnalytics() {
        try {
            // Destroy existing charts to ensure fresh animation
            this.destroyExistingCharts();

            const response = await fetch('api?action=analytics');
            const analytics = await response.json();

            if (analytics.error) {
                throw new Error(analytics.error);
            }

            // Add slight delay to ensure DOM is ready for animation
            setTimeout(() => {
                this.createStatusChart(analytics.status_distribution || []);
                this.createRevenueChart(analytics.monthly_revenue || []);
                this.createActivityChart(analytics.daily_activity || []);
            }, 100);

        } catch (error) {
            console.error('Error loading analytics:', error);
            this.showError('Failed to load analytics data');
        }
    }

    destroyExistingCharts() {
        // Destroy existing chart instances if they exist
        if (window.statusChartInstance) {
            window.statusChartInstance.destroy();
            window.statusChartInstance = null;
        }
        if (window.revenueChartInstance) {
            window.revenueChartInstance.destroy();
            window.revenueChartInstance = null;
        }
        if (window.activityChartInstance) {
            window.activityChartInstance.destroy();
            window.activityChartInstance = null;
        }
    }

    createStatusChart(statusData) {
        const ctx = document.getElementById('statusChart');
        if (!ctx) return;

        window.statusChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(item => item.status),
                datasets: [{
                    data: statusData.map(item => item.count),
                    backgroundColor: [
                        '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 2000,
                    easing: 'easeOutBounce'
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Transaction Status Distribution'
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }

    createRevenueChart(revenueData) {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;

        // Format month labels for better display
        const formatMonthLabel = (monthStr) => {
            const [year, month] = monthStr.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short'
            });
        };

        window.revenueChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: revenueData.map(item => formatMonthLabel(item.month)),
                datasets: [{
                    label: 'Revenue (₹)',
                    data: revenueData.map(item => parseFloat(item.revenue)),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Revenue Trend'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    createActivityChart(activityData) {
        const ctx = document.getElementById('activityChart');
        if (!ctx) return;

        // Process data by type
        const dates = [...new Set(activityData.map(item => item.date))].sort();
        const types = [...new Set(activityData.map(item => item.type))];

        const datasets = types.map((type, index) => {
            const colors = ['#667eea', '#f093fb', '#84fab0'];
            return {
                label: type.replace('_', ' ').toUpperCase(),
                data: dates.map(date => {
                    const item = activityData.find(d => d.date === date && d.type === type);
                    return item ? item.count : 0;
                }),
                backgroundColor: colors[index % colors.length],
                borderColor: colors[index % colors.length],
                borderWidth: 2
            };
        });

        window.activityChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dates.map(date => new Date(date).toLocaleDateString()),
                datasets: datasets
            },
            options: {
                responsive: true,
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart',
                    delay: (context) => {
                        // Staggered animation for bars
                        return context.dataIndex * 100;
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Daily Activity (Last 30 Days)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    setupLogViewer() {
        // Handle log file selection
        document.querySelectorAll('[data-log]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();

                // Update active state
                document.querySelectorAll('[data-log]').forEach(l => l.classList.remove('active'));
                link.classList.add('active');

                // Load log content
                const logFile = link.dataset.log;
                this.loadLogContent(logFile);
            });
        });
    }

    async loadLogContent(logFile, targetElementId = 'log-content') {
        try {
            console.log('Loading log content:', logFile, 'Target element:', targetElementId);

            // Show loading message
            const targetElement = document.getElementById(targetElementId);
            console.log('Target element found:', targetElement);

            if (targetElement) {
                targetElement.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading logs...</div>';
            }

            const url = `api?action=logs&log_file=${logFile}&lines=200`;
            console.log('Fetching from URL:', url);

            const response = await fetch(url);
            console.log('Response status:', response.status, response.statusText);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('API result:', result);

            if (result.error) {
                throw new Error(result.error);
            }

            if (targetElement) {
                if (result.content && result.content.trim()) {
                    // Format the content properly
                    const formattedContent = result.content
                        .split('\n')
                        .filter(line => line.trim() !== '')
                        .map(line => `<div class="log-line">${this.escapeHtml(line)}</div>`)
                        .join('');

                    targetElement.innerHTML = formattedContent;
                    console.log('Log content loaded successfully, lines:', result.content.split('\n').length);
                } else {
                    targetElement.innerHTML = '<div class="text-muted">No log entries found</div>';
                    console.log('No log content found');
                }
            }

        } catch (error) {
            console.error('Error loading log:', error);
            const targetElement = document.getElementById(targetElementId);
            if (targetElement) {
                targetElement.innerHTML = `<div class="alert alert-danger">Error loading log: ${error.message}</div>`;
            }
        }
    }

    loadEmailLogs() {
        console.log('loadEmailLogs called');
        const emailLogsElement = document.getElementById('email-logs-content');
        console.log('Email logs element found:', emailLogsElement);

        if (!emailLogsElement) {
            console.error('Email logs content element not found!');
            return;
        }

        this.loadLogContent('email_logs.txt', 'email-logs-content');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    loadSystemLogs() {
        this.loadLogContent('debug_logs.txt');
    }

    loadDealerships() {
        console.log('Loading dealerships section - admin.js');
        
        // Wait for DOM to be fully ready and section to be visible
        setTimeout(() => {
            const section = document.getElementById('dealerships-section');
            const tableElement = document.getElementById('dealerships-table');
            
            if (!section) {
                console.error('Dealership section not found');
                return;
            }
            
            if (!tableElement) {
                console.error('Dealership table element not found');
                return;
            }
            
            // Ensure section is visible
            if (section.style.display === 'none') {
                console.error('Dealership section is not visible');
                return;
            }
            
            // Check if table has proper structure
            const tbody = tableElement.querySelector('tbody');
            const thead = tableElement.querySelector('thead');
            
            if (!tbody || !thead) {
                console.error('Table structure incomplete - missing tbody or thead');
                return;
            }
            
            console.log('All DOM checks passed, delegating to dealership.js loadDealerships...');
            
            // Delegate to the dealership.js loadDealerships function which has duplicate call prevention
            if (typeof window.loadDealerships === 'function') {
                try {
                    window.loadDealerships().catch(error => {
                        console.error('Error in delegated loadDealerships:', error);
                    });
                } catch (error) {
                    console.error('Error calling loadDealerships:', error);
                }
            } else {
                console.error('loadDealerships function not available on window');
            }
        }, 200);
    }

    // Method to force refresh dealerships (clears cache and reloads)
    forceRefreshDealerships() {
        // Clear the loaded sections cache for dealerships
        this.loadedSections.delete('dealerships');
        
        // If dealership functions are available, reset their state too
        if (typeof window.loadDealerships === 'function') {
            // Reset dealership loading flags if they exist
            if (window.dealershipsLoading !== undefined) {
                window.dealershipsLoading = false;
            }
            if (window.dealershipsInitialized !== undefined) {
                window.dealershipsInitialized = false;
            }
            
            // Call load dealerships
            return window.loadDealerships();
        } else {
            console.error('loadDealerships function not available for force refresh');
            return Promise.reject('loadDealerships function not available');
        }
    }

    formatNumber(num) {
        return new Intl.NumberFormat('en-IN').format(num);
    }

    showError(message) {
        // Create and show error toast
        const toast = document.createElement('div');
        toast.className = 'alert alert-danger alert-dismissible fade show position-fixed';
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 5000);
    }

    showSuccess(message) {
        // Create and show success toast
        const toast = document.createElement('div');
        toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 5000);
    }

    // Detail view methods for all tables
    async showTransactionDetails(transactionId) {
        try {
            console.log('Loading transaction details for ID:', transactionId);
            
            // Show loading message
            this.showSuccess('Loading transaction details...');
            
            // Fetch complete transaction data from API
            const response = await fetch(`api?action=get_transaction&id=${transactionId}`);
            const result = await response.json();
            
            if (result.error) {
                throw new Error(result.error);
            }
            
            // Create and show modal with the fetched data
            this.createAndShowTransactionModal(result.data);
            
        } catch (error) {
            console.error('Error loading transaction details:', error);
            this.showError('Failed to load transaction details: ' + error.message);
        }
    }

    async showTestDriveDetails(testDriveId) {
        try {
            console.log('Loading test drive details for ID:', testDriveId);
            
            this.showSuccess('Loading test drive details...');
            
            const response = await fetch(`api?action=get_test_drive&id=${testDriveId}`);
            const result = await response.json();
            
            if (result.error) {
                throw new Error(result.error);
            }
            
            this.createAndShowTestDriveModal(result.data);
            
        } catch (error) {
            console.error('Error loading test drive details:', error);
            this.showError('Failed to load test drive details: ' + error.message);
        }
    }

    async showContactDetails(contactId) {
        try {
            console.log('Loading contact details for ID:', contactId);
            
            this.showSuccess('Loading contact details...');
            
            const response = await fetch(`api?action=get_contact&id=${contactId}`);
            const result = await response.json();
            
            if (result.error) {
                throw new Error(result.error);
            }
            
            this.createAndShowContactModal(result.data);
            
        } catch (error) {
            console.error('Error loading contact details:', error);
            this.showError('Failed to load contact details: ' + error.message);
        }
    }

    // Tooltip functionality for table cells
    showTooltip(event, fullText) {
        event.stopPropagation();

        // Remove any existing tooltips
        document.querySelectorAll('.cell-tooltip.show').forEach(tooltip => {
            tooltip.classList.remove('show');
        });

        const tooltip = event.currentTarget.querySelector('.cell-tooltip');
        if (tooltip && fullText && fullText !== '-') {
            tooltip.textContent = fullText;
            tooltip.classList.add('show');

            // Hide tooltip after 3 seconds or on click elsewhere
            setTimeout(() => {
                tooltip.classList.remove('show');
            }, 3000);

            // Hide on document click
            document.addEventListener('click', function hideTooltip(e) {
                if (!tooltip.contains(e.target) && !event.currentTarget.contains(e.target)) {
                    tooltip.classList.remove('show');
                    document.removeEventListener('click', hideTooltip);
                }
            });
        }
    }

    // Modal creation and display methods
    createAndShowTransactionModal(data) {
        try {
            // Create modal if it doesn't exist
            if (!document.getElementById('transactionDetailsModal')) {
                this.createTransactionDetailsModal();
            }

            // Populate modal with data
            this.populateTransactionModal(data);

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('transactionDetailsModal'));
            modal.show();
        } catch (error) {
            console.error('Error showing transaction modal:', error);
            this.showError('Failed to display transaction details');
        }
    }

    createAndShowTestDriveModal(data) {
        try {
            // Create modal if it doesn't exist
            if (!document.getElementById('testDriveDetailsModal')) {
                this.createTestDriveDetailsModal();
            }

            // Populate modal with data
            this.populateTestDriveModal(data);

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('testDriveDetailsModal'));
            modal.show();
        } catch (error) {
            console.error('Error showing test drive modal:', error);
            this.showError('Failed to display test drive details');
        }
    }

    createAndShowContactModal(data) {
        try {
            // Create modal if it doesn't exist
            if (!document.getElementById('contactDetailsModal')) {
                this.createContactDetailsModal();
            }

            // Populate modal with data
            this.populateContactModal(data);

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('contactDetailsModal'));
            modal.show();
        } catch (error) {
            console.error('Error showing contact modal:', error);
            this.showError('Failed to display contact details');
        }
    }

    // Create the transaction details modal
    createTransactionDetailsModal() {
        const modalHtml = `
            <div class="modal fade" id="transactionDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-receipt me-2"></i>Transaction Details
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="transactionDetailsContent">
                                <!-- Content will be populated dynamically -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    // Create the test drive details modal
    createTestDriveDetailsModal() {
        const modalHtml = `
            <div class="modal fade" id="testDriveDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-motorcycle me-2"></i>Test Drive Details
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="testDriveDetailsContent">
                                <!-- Content will be populated dynamically -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    // Create the contact details modal
    createContactDetailsModal() {
        const modalHtml = `
            <div class="modal fade" id="contactDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-envelope me-2"></i>Contact Details
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="contactDetailsContent">
                                <!-- Content will be populated dynamically -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    // Populate modal with transaction data
    populateTransactionModal(rowData) {
        const content = document.getElementById('transactionDetailsContent');

        // Define field labels and organize data
        const fieldLabels = {
            'id': 'ID',
            'transaction_id': 'Transaction ID',
            'firstname': 'First Name',
            'phone': 'Phone Number',
            'email': 'Email Address',
            'address': 'Address',
            'city': 'City',
            'state': 'State',
            'pincode': 'Pincode',
            'ownedBefore': 'Owned EV Before',
            'variant': 'Vehicle Variant',
            'color': 'Vehicle Color',
            'terms': 'Terms Accepted',
            'productinfo': 'Product Information',
            'merchant_id': 'Merchant ID',
            'amount': 'Amount',
            'status': 'Payment Status',
            'payment_details': 'Payment Details',
            'created_at': 'Created At',
            'updated_at': 'Updated At'
        };

        // Organize fields into sections
        const sections = {
            'Customer Information': ['firstname', 'phone', 'email', 'address', 'city', 'state', 'pincode'],
            'Order Details': ['variant', 'color', 'ownedBefore', 'terms', 'productinfo'],
            'Payment Information': ['transaction_id', 'merchant_id', 'amount', 'status', 'payment_details'],
            'System Information': ['id', 'created_at', 'updated_at']
        };

        let html = '';

        Object.entries(sections).forEach(([sectionTitle, fields]) => {
            html += `
                <div class="mb-4">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-${this.getSectionIcon(sectionTitle)} me-2"></i>${sectionTitle}
                    </h6>
                    <div class="row">
            `;

            fields.forEach(field => {
                if (rowData.hasOwnProperty(field) && rowData[field] !== null) {
                    let value = rowData[field];

                    // Format specific fields
                    if (field === 'amount' && value) {
                        value = `₹${this.formatNumber(value)}`;
                    } else if (field.includes('created_at') || field.includes('updated_at')) {
                        value = new Date(value).toLocaleString();
                    } else if (field === 'ownedBefore' || field === 'terms') {
                        value = value == 1 ? 'Yes' : 'No';
                    } else if (field === 'email') {
                        value = `<a href="mailto:${value}">${value}</a>`;
                    } else if (field === 'phone') {
                        value = `<a href="tel:${value}">${value}</a>`;
                    } else if (field === 'status') {
                        // Add Check Status button for pending transactions
                        const isPending = value && value.toLowerCase() === 'pending';
                        const transactionId = rowData.transaction_id;

                        if (isPending && transactionId) {
                            value = `
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-warning text-dark">${value}</span>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            onclick="window.open('/api/check-status?txnid=${transactionId}', '_blank')" 
                                            title="Check payment status">
                                        <i class="fas fa-search me-1"></i>Check Status
                                    </button>
                                </div>
                            `;
                        } else {
                            // Show status with appropriate badge color
                            const badgeClass = this.getStatusBadgeClass(value);
                            value = `<span class="badge ${badgeClass}">${value}</span>`;
                        }
                    } else if (field === 'payment_details' && value) {
                        // Format JSON payment details
                        try {
                            const parsed = JSON.parse(value);
                            value = `<pre class="small">${JSON.stringify(parsed, null, 2)}</pre>`;
                        } catch (e) {
                            value = `<pre class="small">${value}</pre>`;
                        }
                    }

                    html += `
                        <div class="col-md-6 mb-3">
                            <div class="detail-item">
                                <strong class="text-muted small">${fieldLabels[field] || field}:</strong>
                                <div class="detail-value">${value || '-'}</div>
                            </div>
                        </div>
                    `;
                }
            });

            html += `
                    </div>
                </div>
            `;
        });

        content.innerHTML = html;
    }

    // Populate modal with test drive data
    populateTestDriveModal(data) {
        const content = document.getElementById('testDriveDetailsContent');

        const fieldLabels = {
            'id': 'ID',
            'full_name': 'Full Name',
            'email': 'Email Address',
            'phone': 'Phone Number',
            'pincode': 'Pincode',
            'date': 'Preferred Date',
            'time': 'Preferred Time',
            'message': 'Message',
            'created_at': 'Created At',
            'updated_at': 'Updated At'
        };

        const sections = {
            'Customer Information': ['full_name', 'email', 'phone', 'pincode'],
            'Appointment Details': ['date', 'time', 'message'],
            'System Information': ['id', 'created_at', 'updated_at']
        };

        let html = '';

        Object.entries(sections).forEach(([sectionTitle, fields]) => {
            html += `
                <div class="mb-4">
                    <h6 class="text-success border-bottom pb-2 mb-3">
                        <i class="fas fa-${this.getTestDriveSectionIcon(sectionTitle)} me-2"></i>${sectionTitle}
                    </h6>
                    <div class="row">
            `;

            fields.forEach(field => {
                if (data.hasOwnProperty(field) && data[field] !== null) {
                    let value = data[field];

                    if (field.includes('created_at') || field.includes('updated_at')) {
                        value = new Date(value).toLocaleString();
                    } else if (field === 'email' && value) {
                        value = `<a href="mailto:${value}">${value}</a>`;
                    } else if (field === 'phone' && value) {
                        value = `<a href="tel:${value}">${value}</a>`;
                    }

                    html += `
                        <div class="col-md-6 mb-3">
                            <div class="detail-item">
                                <strong class="text-muted small">${fieldLabels[field] || field}:</strong>
                                <div class="detail-value">${value || '-'}</div>
                            </div>
                        </div>
                    `;
                }
            });

            html += `
                    </div>
                </div>
            `;
        });

        content.innerHTML = html;
    }

    // Populate modal with contact data
    populateContactModal(data) {
        const content = document.getElementById('contactDetailsContent');

        const fieldLabels = {
            'id': 'ID',
            'full_name': 'Full Name',
            'email': 'Email Address',
            'phone': 'Phone Number',
            'help_type': 'Help Type',
            'message': 'Message',
            'created_at': 'Created At',
            'updated_at': 'Updated At'
        };

        const sections = {
            'Contact Information': ['full_name', 'email', 'phone'],
            'Inquiry Details': ['help_type', 'message'],
            'System Information': ['id', 'created_at', 'updated_at']
        };

        let html = '';

        Object.entries(sections).forEach(([sectionTitle, fields]) => {
            html += `
                <div class="mb-4">
                    <h6 class="text-info border-bottom pb-2 mb-3">
                        <i class="fas fa-${this.getContactSectionIcon(sectionTitle)} me-2"></i>${sectionTitle}
                    </h6>
                    <div class="row">
            `;

            fields.forEach(field => {
                if (data.hasOwnProperty(field) && data[field] !== null) {
                    let value = data[field];

                    if (field.includes('created_at') || field.includes('updated_at')) {
                        value = new Date(value).toLocaleString();
                    } else if (field === 'email' && value) {
                        value = `<a href="mailto:${value}">${value}</a>`;
                    } else if (field === 'phone' && value) {
                        value = `<a href="tel:${value}">${value}</a>`;
                    } else if (field === 'help_type' && value) {
                        let badgeClass = 'bg-info';
                        switch(value.toLowerCase()) {
                            case 'sales': badgeClass = 'bg-success'; break;
                            case 'support': badgeClass = 'bg-primary'; break;
                            case 'complaint': badgeClass = 'bg-warning text-dark'; break;
                            case 'other': badgeClass = 'bg-secondary'; break;
                        }
                        value = `<span class="badge ${badgeClass}">${value.toUpperCase()}</span>`;
                    }

                    html += `
                        <div class="col-md-6 mb-3">
                            <div class="detail-item">
                                <strong class="text-muted small">${fieldLabels[field] || field}:</strong>
                                <div class="detail-value">${value || '-'}</div>
                            </div>
                        </div>
                    `;
                }
            });

            html += `
                    </div>
                </div>
            `;
        });

        content.innerHTML = html;
    }

    getTestDriveSectionIcon(sectionTitle) {
        const icons = {
            'Customer Information': 'user',
            'Appointment Details': 'calendar-alt',
            'System Information': 'cog'
        };
        return icons[sectionTitle] || 'info-circle';
    }

    getContactSectionIcon(sectionTitle) {
        const icons = {
            'Contact Information': 'address-card',
            'Inquiry Details': 'question-circle',
            'System Information': 'cog'
        };
        return icons[sectionTitle] || 'info-circle';
    }

    // Get icon for section
    getSectionIcon(sectionTitle) {
        const icons = {
            'Customer Information': 'user',
            'Order Details': 'shopping-cart',
            'Payment Information': 'credit-card',
            'System Information': 'cog'
        };
        return icons[sectionTitle] || 'info-circle';
    }

    // Get appropriate badge class for payment status
    getStatusBadgeClass(status) {
        if (!status) return 'bg-secondary';

        const statusLower = status.toLowerCase();
        switch (statusLower) {
            case 'success':
            case 'completed':
            case 'paid':
                return 'bg-success';
            case 'pending':
                return 'bg-warning text-dark';
            case 'failed':
            case 'cancelled':
            case 'error':
                return 'bg-danger';
            case 'processing':
                return 'bg-info';
            default:
                return 'bg-secondary';
        }
    }
}

// Global functions for button actions
function refreshTable(tableName) {
    if (window.adminPanel) {
        window.adminPanel.loadTableData(tableName);
    }
}

function exportTable(tableName) {
    window.open(`api?action=export&table=${tableName}&format=csv`, '_blank');
}

// Initialize admin panel when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    window.adminPanel = new AdminPanel();
});

// Auto-refresh dashboard every 5 minutes
setInterval(() => {
    if (window.adminPanel && window.adminPanel.currentSection === 'dashboard') {
        window.adminPanel.loadDashboard();
    }
}, 5 * 60 * 1000);

// Load current user information
AdminPanel.prototype.loadCurrentUser = async function () {
    try {
        const response = await fetch('api?action=current_user');
        const user = await response.json();

        if (!user.error) {
            this.currentUser = user;
            this.updateUserDisplay(user);
        }
    } catch (error) {
        console.error('Error loading current user:', error);
    }
};

AdminPanel.prototype.updateUserDisplay = function (user) {
    const usernameElement = document.getElementById('currentUsername');
    const roleElement = document.getElementById('currentUserRole');

    if (usernameElement) {
        usernameElement.textContent = user.username;
    }

    if (roleElement) {
        roleElement.textContent = user.role.replace('_', ' ').toUpperCase();
        roleElement.className = `badge ms-2 bg-${this.getRoleBadgeColor(user.role)}`;
    }
};

// User Management Functions
AdminPanel.prototype.loadUserManagement = async function () {
    try {
        // Get current user info first
        const currentUserResponse = await fetch('api?action=current_user');
        const currentUser = await currentUserResponse.json();

        if (currentUser.error) {
            this.showError('Failed to get current user information: ' + currentUser.error);
            return;
        }

        // Get all users
        const response = await fetch('api?action=users');
        const users = await response.json();

        if (Array.isArray(users)) {
            this.renderUsersTable(users, currentUser);
        } else if (users.error) {
            this.showError('Failed to load users: ' + users.error);
        } else {
            this.showError('Invalid response format from users API');
        }
    } catch (error) {
        console.error('Error loading users:', error);
        this.showError('Failed to load users: ' + error.message);
    }
};

AdminPanel.prototype.renderUsersTable = function (users, currentUser) {
    const tbody = document.getElementById('usersTableBody');
    if (!tbody) {
        return;
    }

    const isSuperAdmin = currentUser.role === 'super_admin';
    const isAdminOrAbove = ['admin', 'super_admin'].includes(currentUser.role);

    tbody.innerHTML = '';

    if (!Array.isArray(users) || users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No users found</td></tr>';
        return;
    }

    users.forEach(user => {
        const isDefaultUser = user.username === 'kineticadmin';
        const canDelete = isSuperAdmin && !isDefaultUser && (user.role !== 'super_admin' || users.filter(u => u.role === 'super_admin' && u.is_active).length > 1);
        const canEdit = isSuperAdmin;
        const canChangePassword = isAdminOrAbove;

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${user.id}</td>
            <td>
                ${user.username}
                ${isDefaultUser ? '<span class="badge bg-warning ms-1">Default</span>' : ''}
            </td>
            <td>${user.full_name || '-'}</td>
            <td>${user.email || '-'}</td>
            <td>
                <span class="badge bg-${this.getRoleBadgeColor(user.role)}">${user.role.replace('_', ' ').toUpperCase()}</span>
            </td>
            <td>
                <span class="badge bg-${user.is_active ? 'success' : 'danger'}">${user.is_active ? 'Active' : 'Disabled'}</span>
            </td>
            <td>${user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never'}</td>
            <td>${new Date(user.created_at).toLocaleDateString()}</td>
            <td>
                <div class="btn-group" role="group">
                    ${canEdit ? `
                        <button class="btn btn-sm btn-outline-primary" onclick="editUser(${user.id})" title="Edit User">
                            <i class="fas fa-edit"></i>
                        </button>
                    ` : `
                        <button class="btn btn-sm btn-outline-secondary" disabled title="Only super admins can edit users">
                            <i class="fas fa-edit"></i>
                        </button>
                    `}
                    ${canChangePassword ? `
                        <button class="btn btn-sm btn-outline-warning" onclick="changeUserPassword(${user.id}, '${user.username}')" title="Change Password">
                            <i class="fas fa-key"></i>
                        </button>
                    ` : `
                        <button class="btn btn-sm btn-outline-secondary" disabled title="Only admins can change passwords">
                            <i class="fas fa-key"></i>
                        </button>
                    `}
                    ${canDelete ? `
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id}, '${user.username}')" title="Delete User">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : `
                        <button class="btn btn-sm btn-outline-secondary" disabled title="${!isSuperAdmin ? 'Only super admins can delete users' : (isDefaultUser ? 'Default user cannot be deleted' : 'Last super admin cannot be deleted')}">
                            <i class="fas fa-ban"></i>
                        </button>
                    `}
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Update Add New User button visibility
    this.updateUserManagementPermissions(isAdminOrAbove);
};

AdminPanel.prototype.updateUserManagementPermissions = function (isAdminOrAbove) {
    const addUserBtn = document.querySelector('[onclick="showCreateUserModal()"]');
    if (addUserBtn) {
        if (isAdminOrAbove) {
            addUserBtn.style.display = 'inline-block';
            addUserBtn.disabled = false;
        } else {
            addUserBtn.style.display = 'none';
        }
    }
};

AdminPanel.prototype.getRoleBadgeColor = function (role) {
    switch (role) {
        case 'super_admin': return 'danger';
        case 'admin': return 'primary';
        case 'viewer': return 'info';
        default: return 'secondary';
    }
};

// Global functions for user management
window.showCreateUserModal = function () {
    document.getElementById('createUserForm').reset();
    new bootstrap.Modal(document.getElementById('createUserModal')).show();
};

window.createUser = async function () {
    const form = document.getElementById('createUserForm');
    const formData = new FormData(form);

    try {
        const response = await fetch('api?action=create_user', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('createUserModal')).hide();
            window.adminPanel.loadUserManagement();
            window.adminPanel.showSuccess('User created successfully');
        } else {
            window.adminPanel.showError(result.error);
        }
    } catch (error) {
        console.error('Error creating user:', error);
        window.adminPanel.showError('Failed to create user');
    }
};

window.editUser = async function (userId) {
    try {
        const response = await fetch(`api?action=get_user&user_id=${userId}`);
        const user = await response.json();

        if (user.error) {
            window.adminPanel.showError(user.error);
            return;
        }

        document.getElementById('editUserId').value = user.id;
        document.getElementById('editUsername').value = user.username;
        document.getElementById('editEmail').value = user.email || '';
        document.getElementById('editFullName').value = user.full_name || '';
        document.getElementById('editRole').value = user.role;
        document.getElementById('editIsActive').value = user.is_active;

        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    } catch (error) {
        console.error('Error loading user:', error);
        window.adminPanel.showError('Failed to load user details');
    }
};

window.updateUser = async function () {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);

    try {
        const response = await fetch('api?action=update_user', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
            window.adminPanel.loadUserManagement();
            window.adminPanel.showSuccess('User updated successfully');
        } else {
            window.adminPanel.showError(result.error);
        }
    } catch (error) {
        console.error('Error updating user:', error);
        window.adminPanel.showError('Failed to update user');
    }
};

window.changeUserPassword = function (userId, username) {
    document.getElementById('passwordUserId').value = userId;
    document.getElementById('passwordUsername').value = username;
    document.getElementById('changePasswordForm').reset();
    document.getElementById('passwordUserId').value = userId;
    document.getElementById('passwordUsername').value = username;

    new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
};

window.changePassword = async function () {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (newPassword !== confirmPassword) {
        window.adminPanel.showError('Passwords do not match');
        return;
    }

    if (newPassword.length < 6) {
        window.adminPanel.showError('Password must be at least 6 characters long');
        return;
    }

    const form = document.getElementById('changePasswordForm');
    const formData = new FormData(form);

    try {
        const response = await fetch('api?action=change_password', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
            window.adminPanel.showSuccess('Password changed successfully');
        } else {
            window.adminPanel.showError(result.error);
        }
    } catch (error) {
        console.error('Error changing password:', error);
        window.adminPanel.showError('Failed to change password');
    }
};

window.deleteUser = async function (userId, username) {
    if (!confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('user_id', userId);

        const response = await fetch('api?action=delete_user', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            window.adminPanel.loadUserManagement();
            window.adminPanel.showSuccess('User deleted successfully');
        } else {
            window.adminPanel.showError(result.error);
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        window.adminPanel.showError('Failed to delete user');
    }
};

// Detail view functions for all tables - delegate to AdminPanel instance
window.showTransactionDetails = function(transactionId) {
    if (window.adminPanel) {
        window.adminPanel.showTransactionDetails(transactionId);
    } else {
        console.error('AdminPanel not initialized');
    }
};

window.showTestDriveDetails = function(testDriveId) {
    if (window.adminPanel) {
        window.adminPanel.showTestDriveDetails(testDriveId);
    } else {
        console.error('AdminPanel not initialized');
    }
};

window.showContactDetails = function(contactId) {
    if (window.adminPanel) {
        window.adminPanel.showContactDetails(contactId);
    } else {
        console.error('AdminPanel not initialized');
    }
};

// Initialize the admin panel when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing AdminPanel v2.2 with cache busting and enhanced table rendering');
    window.adminPanel = new AdminPanel();
});
