// Dealership Management JavaScript

// Global variable for the dealership table
let dealershipsTable;

// Initialize the dealership table
function initDealershipTable() {
    // If the table is already initialized, destroy it first
    if ($.fn.DataTable.isDataTable('#dealerships-table')) {
        $('#dealerships-table').DataTable().destroy();
    }
    
    // Initialize the DataTable
    dealershipsTable = $('#dealerships-table').DataTable({
        responsive: true,
        processing: true,
        columns: [
            { data: 'id', visible: false }, // ID column (hidden)
            { data: 'name' },              // Name
            { data: 'address' },           // Address
            { data: 'city' },              // City
            { data: 'state' },             // State
            { data: 'phone' },             // Phone
            { data: 'email' },             // Email
            { 
                data: null,                // Actions column
                orderable: false,
                render: function(data, type, row) {
                    return `<div class="btn-group" role="group">
                        <a href="dealership_form.php?id=${row.id}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                            onclick="deleteDealership(${row.id}, '${escapeHtml(row.name)}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>`;
                }
            }
        ]
    });
    
    return dealershipsTable;
}

// Load dealerships from the server
function loadDealerships() {
    // Initialize table if not already done
    if (!dealershipsTable) {
        dealershipsTable = initDealershipTable();
    }
    
    // Show loading indicator
    $('#dealerships-table tbody').html('<tr><td colspan="8" class="text-center">Loading dealerships...</td></tr>');
    
    // Fetch data from the server
    fetch('dealership.php')
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Format the data for DataTables
            const formattedData = data.data.map(dealership => {
                return {
                    id: dealership.id,
                    name: dealership.name,
                    address: dealership.address,
                    city: dealership.city,
                    state: dealership.state,
                    phone: dealership.phone || '-',
                    email: dealership.email || '-'
                };
            });
            
            // Clear and add new data
            dealershipsTable.clear();
            dealershipsTable.rows.add(formattedData).draw();
        } else {
            showAlert('error', 'Error loading dealerships', data.message || 'Unknown error');
        }
    })
    .catch(error => {
        console.error('Error loading dealerships:', error);
        showAlert('error', 'Error loading dealerships', error.message);
    });
}

function refreshDealerships() {
    $('#dealerships-table tbody').html('<tr><td colspan="8" class="text-center">Loading dealerships...</td></tr>');
    loadDealerships();
}

function deleteDealership(id, name) {
    if (confirm(`Are you sure you want to delete the dealership "${name}"?`)) {
        fetch(`dealership.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showAlert('success', 'Success', 'Dealership deleted successfully');
                refreshDealerships();
            } else {
                showAlert('error', 'Error deleting dealership', data.message || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error deleting dealership:', error);
            showAlert('error', 'Error deleting dealership', error.message);
        });
    }
}

function exportDealerships(format) {
    if (!dealershipsTable) {
        showAlert('error', 'Error', 'Table not initialized');
        return;
    }
    
    if (format === 'csv') {
        let headers = ['Name', 'Address', 'City', 'State', 'Phone', 'Email'];
        let data = [];
        
        dealershipsTable.rows().every(function() {
            const rowData = this.data();
            data.push([
                rowData.name,
                rowData.address,
                rowData.city,
                rowData.state,
                rowData.phone,
                rowData.email
            ]);
        });
        
        exportTableToCSV(data, headers, 'dealerships');
    } else {
        // Add other export formats as needed
        showAlert('info', 'Export', `Export format "${format}" not yet implemented`);
    }
}

// Helper function to escape HTML to prevent XSS
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Initialize dealerships datatable
$(document).ready(function() {
    // Load dealerships when the tab is shown
    $('a[data-section="dealerships"]').on('click', function() {
        // Small delay to ensure the DOM is ready
        setTimeout(function() {
            if (!dealershipsTable) {
                initDealershipTable();
            }
            loadDealerships();
        }, 100);
    });
});
