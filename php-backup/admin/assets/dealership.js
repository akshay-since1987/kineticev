// Dealership Management JavaScript

// Global variables for the dealership table
let dealershipsTable;
let dealershipsLoading = false;
let dealershipsInitialized = false;

// Update result info with dealership count
function updateResultInfo(count) {
    const resultText = count === 0 ? 'No dealerships found' : 
                      count === 1 ? 'Showing 1 dealership' : 
                      `Showing ${count} dealerships`;
    $('#dealerships-result-info').html(resultText);
}

// Show loading state for result info
function showLoadingState() {
    $('#dealerships-result-info').html('<i class="fas fa-spinner fa-spin"></i> Loading data...');
}

// Initialize the dealership table
function initDealershipTable() {
    console.log('initDealershipTable called');
    
    if (dealershipsInitialized && dealershipsTable) {
        console.log('Table already initialized');
        return Promise.resolve(dealershipsTable);
    }
    
    return new Promise((resolve) => {
        setTimeout(() => {
            try {
                const tableElement = $('#dealerships-table');
                if (tableElement.length === 0) {
                    console.error('Dealership table element not found');
                    resolve(null);
                    return;
                }
                
                const section = $('#dealerships-section');
                if (section.length === 0 || section.css('display') === 'none') {
                    console.error('Dealership section is not visible');
                    resolve(null);
                    return;
                }
                
                if ($.fn.DataTable.isDataTable('#dealerships-table')) {
                    console.log('Destroying existing DataTable');
                    $('#dealerships-table').DataTable().destroy();
                }
                
                const table = $('#dealerships-table').DataTable({
                    data: [],
                    columns: [
                        { title: 'Name' },
                        { title: 'Address' },
                        { title: 'City' },
                        { title: 'State' },
                        { title: 'Phone' },
                        { title: 'Email' },
                        { title: 'Actions', orderable: false }
                    ],
                    destroy: true,
                    paging: false,
                    searching: false,
                    info: false
                });
                
                console.log('DataTable initialized successfully');
                dealershipsTable = table;
                dealershipsInitialized = true;
                resolve(table);
                
            } catch (error) {
                console.error('Error in table init:', error);
                resolve(null);
            }
        }, 300);
    });
}

// Load dealerships from the server
async function loadDealerships() {
    console.log('loadDealerships called');
    
    if (dealershipsLoading) {
        console.log('Dealerships already loading, skipping duplicate call');
        return;
    }
    
    dealershipsLoading = true;
    console.log('Starting dealership load');
    
    try {
        if (!dealershipsTable || !dealershipsInitialized) {
            console.log('Initializing dealership table...');
            dealershipsTable = await initDealershipTable();
            if (!dealershipsTable) {
                console.error('Failed to initialize dealership table');
                return;
            }
        }
        
        showLoadingState();
        
        console.log('Making API call for dealerships...');
        const response = await fetch('api.php?action=get_dealerships');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        
        console.log('API response received:', data);
        
        if (data.success) {
            const formattedData = data.data.map(dealership => {
                const escapeHtml = (unsafe) => {
                    return unsafe
                         .replace(/&/g, "&amp;")
                         .replace(/</g, "&lt;")
                         .replace(/>/g, "&gt;")
                         .replace(/"/g, "&quot;")
                         .replace(/'/g, "&#039;");
                };
                
                return [
                    escapeHtml(dealership.name || ''),
                    escapeHtml(dealership.address || ''),
                    escapeHtml(dealership.city || ''),
                    escapeHtml(dealership.state || ''),
                    escapeHtml(dealership.phone || ''),
                    escapeHtml(dealership.email || ''),
                    `<div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editDealership(${dealership.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDealership(${dealership.id}, '${escapeHtml(dealership.name)}')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>`
                ];
            });
            
            dealershipsTable.clear();
            dealershipsTable.rows.add(formattedData).draw();
            updateResultInfo(formattedData.length);
            console.log('Dealerships table updated successfully');
        } else {
            console.error('API returned error:', data);
            updateResultInfo(0);
        }
    } catch (error) {
        console.error('Error in loadDealerships:', error);
        updateResultInfo(0);
    } finally {
        dealershipsLoading = false;
        console.log('Dealership load completed');
    }
}

function refreshDealerships() {
    console.log('Refresh dealerships called');
    
    if (window.adminPanel && window.adminPanel.loadedSections) {
        window.adminPanel.loadedSections.delete('dealerships');
        console.log('Cleared dealerships from loadedSections');
    }
    
    dealershipsLoading = false;
    dealershipsInitialized = false;
    
    loadDealerships();
}

// Make functions available globally immediately
window.loadDealerships = loadDealerships;
window.refreshDealerships = refreshDealerships;

console.log('Dealership.js: loadDealerships function assigned to window:', typeof window.loadDealerships);

// IMPORTANT: Additional functions will be assigned at the end of file
// This ensures loadDealerships is available immediately for admin.js

$(document).ready(function() {
    console.log('Dealership.js ready - delegation to admin.js for section loading');
});

// Edit dealership function
function editDealership(dealershipId) {
    console.log('Editing dealership:', dealershipId);
    
    $('#editDealershipFormAlert').hide();
    
    fetch(`api.php?action=get_dealership&id=${dealershipId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const dealership = data.data;
                
                $('#edit_dealership_id').val(dealership.id);
                $('#edit_dealership_name').val(dealership.name);
                $('#edit_dealership_email').val(dealership.email);
                $('#edit_dealership_phone').val(dealership.phone);
                $('#edit_dealership_pincode').val(dealership.pincode);
                $('#edit_dealership_address').val(dealership.address);
                $('#edit_dealership_city').val(dealership.city);
                $('#edit_dealership_state').val(dealership.state);
                $('#edit_dealership_latitude').val(dealership.latitude);
                $('#edit_dealership_longitude').val(dealership.longitude);
                
                $('#editDealershipModal').modal('show');
            } else {
                showAlert('danger', 'Error', data.error || 'Failed to load dealership data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Error', 'An error occurred while loading dealership data');
        });
}

// Update dealership function
function updateDealership() {
    const form = document.getElementById('editDealershipForm');
    const formData = new FormData(form);
    
    const dealershipData = {
        id: formData.get('id'),
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        pincode: formData.get('pincode'),
        address: formData.get('address'),
        city: formData.get('city'),
        state: formData.get('state'),
        latitude: formData.get('latitude'),
        longitude: formData.get('longitude')
    };
    
    if (!dealershipData.name || !dealershipData.address || !dealershipData.city || 
        !dealershipData.state || !dealershipData.pincode) {
        $('#editDealershipFormAlert').show().text('Please fill in all required fields.');
        return;
    }
    
    fetch('api.php?action=update_dealership', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dealershipData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            $('#editDealershipModal').modal('hide');
            showAlert('success', 'Success', 'Dealership updated successfully');
            refreshDealerships();
        } else {
            $('#editDealershipFormAlert').show().text(data.error || 'Failed to update dealership');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        $('#editDealershipFormAlert').show().text('An error occurred while updating the dealership');
    });
}

// Save new dealership function
function saveDealership() {
    const form = document.getElementById('dealershipForm');
    const formData = new FormData(form);
    
    const dealershipData = {
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        pincode: formData.get('pincode'),
        address: formData.get('address'),
        city: formData.get('city'),
        state: formData.get('state'),
        latitude: formData.get('latitude'),
        longitude: formData.get('longitude')
    };
    
    if (!dealershipData.name || !dealershipData.address || !dealershipData.city || 
        !dealershipData.state || !dealershipData.pincode) {
        $('#dealershipFormAlert').show().text('Please fill in all required fields.');
        return;
    }
    
    fetch('api.php?action=create_dealership', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dealershipData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            $('#addDealershipModal').modal('hide');
            form.reset();
            showAlert('success', 'Success', 'Dealership created successfully');
            refreshDealerships();
        } else {
            $('#dealershipFormAlert').show().text(data.error || 'Failed to create dealership');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        $('#dealershipFormAlert').show().text('An error occurred while creating the dealership');
    });
}

function deleteDealership(id, name) {
    if (confirm(`Are you sure you want to delete the dealership "${name}"?`)) {
        fetch(`api.php?action=delete_dealership&id=${id}`, {
            method: 'POST'
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
                showAlert('danger', 'Error', data.error || 'Failed to delete dealership');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Error', 'An error occurred while deleting the dealership');
        });
    }
}

// Show alert function
function showAlert(type, title, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <strong>${title}:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    let container = $('#dealerships-section .p-4').first();
    if (container.length === 0) {
        container = $('body');
    }
    
    container.find('.alert').remove();
    container.prepend(alertHtml);
    
    setTimeout(() => {
        container.find('.alert').fadeOut();
    }, 5000);
}

$(document).ready(function() {
    $('#updateDealershipBtn').on('click', updateDealership);
    $('#saveDealershipBtn').on('click', saveDealership);
});

// Make remaining functions available globally
window.editDealership = editDealership;
window.updateDealership = updateDealership;
window.saveDealership = saveDealership;
window.deleteDealership = deleteDealership;
