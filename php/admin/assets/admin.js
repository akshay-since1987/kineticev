class AdminPanel {
    constructor() {
        this.dataTables = {};
        this.loadedSections = new Set();
        this.currentSection = null;
    }

    init() {
        this.setupGlobalFetchHandler();
        this.setupNavigation();
        this.setupEventHandlers();

        // Load initial section based on hash or default to dashboard
        const hash = window.location.hash.substring(1);
        const section = hash ? hash.replace('-', '_') : 'dashboard';
        this.switchSection(section);
    }

    setupEventHandlers() {
        // Cities management event handlers
        $(document).on('click', '#saveCityBtn', () => this.saveCity());
        $('#cityModal').on('show.bs.modal', () => {
            console.log('City modal opening');
        });
        
        $('.btn-refresh-cities').on('click', () => {
            if (this.dataTables.cities) {
                this.dataTables.cities.ajax.reload();
            }
        });
    }

    switchSection(section) {
        console.log('Switching to section:', section);
        
        // Hide all sections first
        $('.content-section').hide();
        
        // Show the selected section
        const sectionElement = $(`#${section}-section`);
        console.log('Looking for section:', `#${section}-section`);
        
        if (sectionElement.length) {
            sectionElement.show();
            
            // Initialize section if needed
            switch(section) {
                case 'allowed_cities':
                    console.log('Initializing cities section');
                    this.loadCitiesSection();
                    break;
                case 'users':
                    console.log('Initializing users section');
                    this.loadUsersTable();
                    break;
                case 'dashboard':
                    console.log('Loading dashboard');
                    this.loadDashboard();
                    break;
                case 'email_logs':
                    console.log('Loading email logs');
                    this.loadEmailLogs();
                    break;
                case 'system_logs':
                    console.log('Loading system logs');
                    this.loadSystemLogs();
                    break;
                case 'transactions':
                    console.log('Loading transactions section');
                    if (typeof this.loadTransactionsSection === 'function') {
                        this.loadTransactionsSection();
                    } else {
                        console.error('loadTransactionsSection not implemented');
                    }
                    break;
                case 'test_drives':
                    console.log('Loading test drives section');
                    if (typeof this.loadTestDrivesSection === 'function') {
                        this.loadTestDrivesSection();
                    } else {
                        console.error('loadTestDrivesSection not implemented');
                    }
                    break;
                case 'contacts':
                    console.log('Loading contacts section');
                    if (typeof this.loadContactsSection === 'function') {
                        this.loadContactsSection();
                    } else {
                        console.error('loadContactsSection not implemented');
                    }
                    break;
                case 'dealerships':
                    console.log('Loading dealerships section');
                    if (typeof this.loadDealershipsSection === 'function') {
                        this.loadDealershipsSection();
                    } else {
                        console.error('loadDealershipsSection not implemented');
                    }
                    break;
                default:
                    console.log('No initialization needed for section:', section);
            }
            
            // Update navigation
            this.updateActiveNavigation(section);
        } else {
            console.error('Section not found:', section);
            // Optionally, redirect to dashboard or show error
        }
        
        // Update URL hash
        window.location.hash = '#' + section.replace('_', '-');
        this.currentSection = section;
    }

    loadCitiesSection() {
        console.log('Loading cities section');

        // Prevent duplicate initialization
        if (this.loadedSections.has('cities')) {
            console.log('Cities section already loaded, reloading data');
            if (this.dataTables.cities) {
                this.dataTables.cities.ajax.reload();
            }
            return;
        }

        let tableElement = $('#allowed_cities-table');
        
        // If DataTable instance exists, destroy it properly
        if ($.fn.DataTable.isDataTable('#allowed_cities-table')) {
            $('#allowed_cities-table').DataTable().destroy();
        }

        // Initialize DataTable
        try {
            console.log('Initializing cities DataTable');
            tableElement = $('#allowed_cities-table');
            this.dataTables.cities = tableElement.DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [[1, 'asc']],
                ajax: {
                    url: 'api/allowed_cities.php?action=list',
                    type: 'GET',
                    error: (xhr, error, thrown) => {
                        console.error('DataTable AJAX error:', error, thrown);
                        console.log('Server response:', xhr.responseText);
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'city_name' },
                    { data: 'coordinates' },
                    { 
                        data: 'is_allowed',
                        render: function(data) {
                            return parseInt(data) === 1 
                                ? '<span class="badge bg-success">Active</span>' 
                                : '<span class="badge bg-danger">Inactive</span>';
                        }
                    },
                    { data: 'updated_at' },
                    {
                        data: null,
                        orderable: false,
                        render: (data) => this.renderCityActions(data)
                    }
                ],
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                language: {
                    processing: "Loading cities...",
                    emptyTable: "No cities found",
                    zeroRecords: "No matching cities found"
                }
            });

            console.log('Cities DataTable initialized');
            this.loadedSections.add('cities');

            // Add error handler for DataTable
            this.dataTables.cities.on('error.dt', (e, settings, techNote, message) => {
                console.error('DataTable error:', message, techNote);
                this.showError('Error loading cities data: ' + message);
            });

        } catch (error) {
            console.error('Error initializing cities DataTable:', error);
            this.showError('Failed to initialize cities table: ' + error.message);
        }
    }

    renderStatus(status) {
        return status == 1 
            ? '<span class="badge bg-success">Active</span>' 
            : '<span class="badge bg-danger">Inactive</span>';
    }

    renderCityActions(data) {
        return `
            <div class="btn-group btn-group-sm">
                <button class="btn btn-primary" onclick="adminPanel.editCity(${data.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger" onclick="adminPanel.deleteCity(${data.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    }

    showAddCityModal() {
        $('#cityModalTitle').text('Add New City');
        $('#cityForm')[0].reset();
        $('#cityId').val('');
        $('#cityModal').modal('show');
    }

    editCity(id) {
        console.log('Editing city:', id);
        // City editing logic here
    }

    loadTestDrivesSection() {
        if (this.loadedSections.has('test_drives')) {
            console.log('Test drives section already loaded');
            if (this.dataTables.testDrives) {
                this.dataTables.testDrives.ajax.reload();
            }
            return;
        }

        console.log('Loading test drives section');
        const tableElement = $('#test-drives-table');
        if (tableElement.length) {
            this.dataTables.testDrives = tableElement.DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [[0, 'desc']],
                ajax: {
                    url: 'api/test-drives.php?action=list',
                    type: 'GET',
                    error: (xhr, error, thrown) => {
                        console.error('Test Drives DataTable error:', error, thrown);
                        console.log('Server response:', xhr.responseText);
                        this.showError('Failed to load test drive data');
                    }
                },
                columns: [
                    { data: 'id', title: 'ID' },
                    { data: 'user_name', title: 'User' },
                    { data: 'vehicle', title: 'Vehicle' },
                    { data: 'dealer', title: 'Dealer' },
                    { data: 'scheduled_date', title: 'Date' },
                    { data: 'status', title: 'Status' },
                    { data: 'created_at', title: 'Created' }
                ]
            });
            this.loadedSections.add('test_drives');
        } else {
            console.error('Test drives table element not found');
        }
    }

    loadTransactionsSection() {
        if (this.loadedSections.has('transactions')) {
            console.log('Transactions section already loaded');
            if (this.dataTables.transactions) {
                this.dataTables.transactions.ajax.reload();
            }
            return;
        }

        console.log('Loading transactions section');
        const tableElement = $('#transactions-table');
        if (tableElement.length) {
            this.dataTables.transactions = tableElement.DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [[0, 'desc']],
                ajax: {
                    url: 'api/transactions.php?action=list',
                    type: 'GET',
                    error: (xhr, error, thrown) => {
                        console.error('Transactions DataTable error:', error, thrown);
                        console.log('Server response:', xhr.responseText);
                        this.showError('Failed to load transaction data');
                    }
                },
                columns: [
                    { data: 'id', title: 'ID' },
                    { data: 'user_name', title: 'User' },
                    { data: 'vehicle', title: 'Vehicle' },
                    { data: 'dealer', title: 'Dealer' },
                    { data: 'amount', title: 'Amount' },
                    { data: 'status', title: 'Status' },
                    { data: 'created_at', title: 'Created' }
                ]
            });
            this.loadedSections.add('transactions');
        } else {
            console.error('Transactions table element not found');
        }
    }

    loadContactsSection() {
        if (this.loadedSections.has('contacts')) {
            console.log('Contacts section already loaded');
            if (this.dataTables.contacts) {
                this.dataTables.contacts.ajax.reload();
            }
            return;
        }

        console.log('Loading contacts section');
        const tableElement = $('#contacts-table');
        if (tableElement.length) {
            this.dataTables.contacts = tableElement.DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [[0, 'desc']],
                ajax: {
                    url: 'api/contacts.php?action=list',
                    type: 'GET',
                    error: (xhr, error, thrown) => {
                        console.error('Contacts DataTable error:', error, thrown);
                        console.log('Server response:', xhr.responseText);
                        this.showError('Failed to load contacts data');
                    }
                },
                columns: [
                    { data: 'id', title: 'ID' },
                    { data: 'name', title: 'Name' },
                    { data: 'email', title: 'Email' },
                    { data: 'message', title: 'Message' },
                    { data: 'status', title: 'Status' },
                    { data: 'created_at', title: 'Created' }
                ]
            });
            this.loadedSections.add('contacts');
        } else {
            console.error('Contacts table element not found');
        }
    }

    editCity(id) {
        console.log('Editing city:', id);
        $.ajax({
            url: '/admin/api/allowed_cities.php',
            type: 'GET',
            data: { action: 'get', id: id },
            success: response => {
                if (response.success) {
                    const city = response.data;
                    $('#cityId').val(city.id);
                    $('#cityName').val(city.city_name);
                    $('#state').val(city.state);
                    $('#coordinates').val(city.coordinates || '');
                    $('#maxDistance').val(city.max_distance_km || '50');
                    $('#isAllowed').prop('checked', city.is_allowed == 1);
                    $('#description').val(city.description || '');
                    $('#cityModalTitle').text('Edit City');
                    $('#cityModal').modal('show');
                } else {
                    this.showError(response.message || 'Failed to load city data');
                }
            },
            error: () => this.showError('Failed to load city data')
        });
    }

    saveCity() {
        const form = $('#cityForm')[0];
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const cityId = $('#cityId').val();
        const isUpdate = !!cityId;
        const action = isUpdate ? 'update' : 'add';

        const formData = {
            city_name: $('#cityName').val(),
            state: $('#state').val(),
            coordinates: $('#coordinates').val(),
            max_distance_km: $('#maxDistance').val() || '50',
            is_allowed: $('#isAllowed').is(':checked') ? 1 : 0,
            description: $('#description').val()
        };

        const url = `/admin/api/allowed_cities.php?action=${action}${isUpdate ? '&id=' + cityId : ''}`;

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: response => {
                if (response.success) {
                    $('#cityModal').modal('hide');
                    this.dataTables.cities.ajax.reload();
                    this.showSuccess(response.message || 'City saved successfully');
                } else {
                    this.showError(response.error || 'Failed to save city');
                }
            },
            error: (jqXHR) => {
                let error = 'Failed to save city';
                try {
                    const response = JSON.parse(jqXHR.responseText);
                    error = response.error || error;
                } catch (e) {}
                this.showError(error);
            }
        });
    }

    deleteCity(id) {
        if (!confirm('Are you sure you want to delete this city?')) {
            return;
        }

        $.ajax({
            url: '/admin/api/allowed_cities.php',
            type: 'DELETE',
            data: JSON.stringify({ id }),
            contentType: 'application/json',
            success: response => {
                if (response.success) {
                    this.dataTables.cities.ajax.reload();
                    this.showSuccess('City deleted successfully');
                } else {
                    this.showError(response.message || 'Failed to delete city');
                }
            },
            error: () => this.showError('Failed to delete city')
        });
    }

    refreshCitiesTable() {
        if (this.dataTables.cities) {
            this.dataTables.cities.ajax.reload();
            this.showInfo('Cities table refreshed');
        }
    }

    showSuccess(message) {
        toastr.success(message);
    }

    showError(message) {
        toastr.error(message);
    }

    showInfo(message) {
        toastr.info(message);
    }

    showError(message) {
        console.error('Error:', message);
        toastr.error(message);
    }

    showInfo(message) {
        toastr.info(message);
    }

    // Setup global fetch error handler for authentication redirects
    setupGlobalFetchHandler() {
        $(document).ajaxError((event, jqXHR) => {
            if (jqXHR.status === 401) {
                window.location.href = '/admin/login.php';
            }
        });
    }

    setupNavigation() {
        // Use event delegation for nav link clicks
        $('.sidebar').on('click', '.nav-link', (e) => {
            e.preventDefault();
            const section = $(e.currentTarget).data('section');
            this.switchSection(section);
        });

        // Handle initial navigation from URL hash
        const hash = window.location.hash.substring(1);
        if (hash) {
            this.switchSection(hash.replace('-', '_'));
        }
    }

    updateActiveNavigation(activeLink) {
        $('.nav-link').removeClass('active');
        $(`.nav-link[data-section="${activeLink}"]`).addClass('active');
    }

    showSection(section) {
        $('.content-section').hide();
        $(`#${section}-section`).show();
    }

    isValidTable(tableName) {
        return ['users', 'cities', 'pincodes'].includes(tableName);
    }

    async loadDashboard() {
        try {
            const response = await fetch('api/dashboard.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse dashboard response:', text);
                throw new Error('Invalid JSON response from server');
            }
            
            if (data.success) {
                this.updateDashboardStats(data.stats);
            } else {
                this.showError(data.error || 'Failed to load dashboard data');
            }
        } catch (error) {
            console.error('Dashboard load error:', error);
            this.showError('Failed to load dashboard data: ' + error.message);
        }
    }

    updateDashboardStats(stats) {
        $('#totalUsersCount').text(stats.total_users || 0);
        $('#activeUsersCount').text(stats.active_users || 0);
        $('#totalCitiesCount').text(stats.total_cities || 0);
        $('#activeCitiesCount').text(stats.active_cities || 0);
    }

    loadUsersTable() {
        if (this.loadedSections.has('users')) {
            if (this.dataTables.users) {
                this.dataTables.users.ajax.reload();
            }
            return;
        }

        const tableElement = $('#usersTable');
        if ($.fn.DataTable.isDataTable(tableElement)) {
            tableElement.DataTable().destroy();
        }

        try {
            this.dataTables.users = tableElement.DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [[1, 'asc']],
                ajax: {
                    url: 'api/users.php?action=list',
                    type: 'GET',
                    error: (xhr, error, thrown) => {
                        console.error('DataTable AJAX error:', error, thrown);
                        console.log('Server response:', xhr.responseText);
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'username' },
                    { data: 'full_name' },
                    { data: 'email' },
                    { data: 'role' },
                    { 
                        data: 'status',
                        render: (data) => this.renderStatus(data)
                    },
                    { data: 'last_login' },
                    { data: 'created_at' },
                    {
                        data: null,
                        orderable: false,
                        render: (data) => `
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-primary" onclick="adminPanel.editUser(${data.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger" onclick="adminPanel.deleteUser(${data.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `
                    }
                ],
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                language: {
                    processing: "Loading users...",
                    emptyTable: "No users found",
                    zeroRecords: "No matching users found"
                }
            });

            this.loadedSections.add('users');
        } catch (error) {
            console.error('Error initializing users DataTable:', error);
            this.showError('Failed to initialize users table: ' + error.message);
        }
    }

    loadEmailLogs() {
        if (!this.loadedSections.has('email_logs')) {
            this.loadedSections.add('email_logs');
            this.refreshEmailLogs();
        }
    }

    loadSystemLogs() {
        if (!this.loadedSections.has('system_logs')) {
            this.loadedSections.add('system_logs');
            this.refreshSystemLogs();
        }
    }

    refreshEmailLogs() {
        $.get('api/email_logs.php')
            .done(data => {
                $('#email-logs-content').html(data);
            })
            .fail(error => {
                console.error('Failed to load email logs:', error);
                this.showError('Failed to load email logs');
            });
    }

    refreshSystemLogs() {
        const logFile = $('.list-group-item.active').data('log') || 'debug_logs.txt';
        $.get(`api/system_logs.php?file=${logFile}`)
            .done(data => {
                $('#log-content').html(data);
            })
            .fail(error => {
                console.error('Failed to load system logs:', error);
                this.showError('Failed to load system logs');
            });
    }

    loadTransactionsSection() {
        console.log('Initializing transactions section');
        if (this.loadedSections.has('transactions')) {
            console.log('Transactions section already loaded, reloading data');
            if (this.dataTables.transactions) {
                this.dataTables.transactions.ajax.reload();
            }
            return;
        }

        const tableElement = $('#transactions-table');
        console.log('Found transactions table:', tableElement.length > 0);
        if ($.fn.DataTable.isDataTable(tableElement)) {
            tableElement.DataTable().destroy();
        }

        try {
            this.dataTables.transactions = tableElement.DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [[0, 'desc']],
                ajax: {
                    url: 'api/transactions.php?action=list',
                    type: 'GET',
                    error: (xhr, error, thrown) => {
                        console.error('Transaction DataTable error:', error, thrown);
                        console.log('Server response:', xhr.responseText);
                        this.showError('Failed to load transactions data');
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'transaction_date' },
                    { data: 'customer_name' },
                    { data: 'amount' },
                    { data: 'status' },
                    { 
                        data: null,
                        orderable: false,
                        render: (data) => `
                            <button class="btn btn-sm btn-info" onclick="adminPanel.viewTransaction(${data.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                        `
                    }
                ]
            });
            this.loadedSections.add('transactions');
        } catch (error) {
            console.error('Error initializing transactions table:', error);
            this.showError('Failed to initialize transactions table');
        }
    }

    loadTestDrivesSection() {
        console.log('Initializing test drives section');
        if (this.loadedSections.has('test_drives')) {
            console.log('Test drives section already loaded, reloading data');
            if (this.dataTables.testDrives) {
                this.dataTables.testDrives.ajax.reload();
            }
            return;
        }

        const tableElement = $('#test_drives-table');
        console.log('Found test drives table:', tableElement.length > 0);
        if ($.fn.DataTable.isDataTable(tableElement)) {
            tableElement.DataTable().destroy();
        }

        try {
            this.dataTables.testDrives = tableElement.DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [[0, 'desc']],
                ajax: {
                    url: 'api/test_drives.php?action=list',
                    type: 'GET',
                    error: (xhr, error, thrown) => {
                        console.error('Test Drives DataTable error:', error, thrown);
                        console.log('Server response:', xhr.responseText);
                        this.showError('Failed to load test drives data');
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'booking_date' },
                    { data: 'customer_name' },
                    { data: 'vehicle_model' },
                    { data: 'status' },
                    {
                        data: null,
                        orderable: false,
                        render: (data) => `
                            <button class="btn btn-sm btn-info" onclick="adminPanel.viewTestDrive(${data.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                        `
                    }
                ]
            });
            this.loadedSections.add('test_drives');
        } catch (error) {
            console.error('Error initializing test drives table:', error);
            this.showError('Failed to initialize test drives table');
        }
    }

    loadContactsSection() {
        console.log('Initializing contacts section');
        if (this.loadedSections.has('contacts')) {
            console.log('Contacts section already loaded, reloading data');
            if (this.dataTables.contacts) {
                this.dataTables.contacts.ajax.reload();
            }
            return;
        }

        const tableElement = $('#contacts-table');
        console.log('Found contacts table:', tableElement.length > 0);
        if ($.fn.DataTable.isDataTable(tableElement)) {
            tableElement.DataTable().destroy();
        }

        try {
            this.dataTables.contacts = tableElement.DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [[0, 'desc']],
                ajax: {
                    url: 'api/contacts.php?action=list',
                    type: 'GET',
                    error: (xhr, error, thrown) => {
                        console.error('Contacts DataTable error:', error, thrown);
                        console.log('Server response:', xhr.responseText);
                        this.showError('Failed to load contacts data');
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'submission_date' },
                    { data: 'name' },
                    { data: 'email' },
                    { data: 'subject' },
                    {
                        data: null,
                        orderable: false,
                        render: (data) => `
                            <button class="btn btn-sm btn-info" onclick="adminPanel.viewContact(${data.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                        `
                    }
                ]
            });
            this.loadedSections.add('contacts');
        } catch (error) {
            console.error('Error initializing contacts table:', error);
            this.showError('Failed to initialize contacts table');
        }
    }

    loadDealershipsSection() {
        if (this.loadedSections.has('dealerships')) {
            if (this.dataTables.dealerships) {
                this.dataTables.dealerships.ajax.reload();
            }
            return;
        }

        const tableElement = $('#dealerships-table');
        if ($.fn.DataTable.isDataTable(tableElement)) {
            tableElement.DataTable().destroy();
        }

        try {
            this.dataTables.dealerships = tableElement.DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [[1, 'asc']],
                ajax: {
                    url: 'api/dealerships.php?action=list',
                    type: 'GET'
                },
                columns: [
                    { data: 'id' },
                    { data: 'name' },
                    { data: 'city' },
                    { data: 'state' },
                    { data: 'phone' },
                    {
                        data: null,
                        orderable: false,
                        render: (data) => `
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-primary" onclick="adminPanel.editDealership(${data.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger" onclick="adminPanel.deleteDealership(${data.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `
                    }
                ]
            });
            this.loadedSections.add('dealerships');
        } catch (error) {
            console.error('Error initializing dealerships table:', error);
            this.showError('Failed to initialize dealerships table');
        }
    }
}

// Initialize the admin panel
const adminPanel = new AdminPanel();
$(document).ready(() => adminPanel.init());