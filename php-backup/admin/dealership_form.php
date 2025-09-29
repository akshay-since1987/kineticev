<?php
// Include required files
require_once '../config.php';
require_once 'AdminHandler.php';

// Check if session is active and user is logged in
session_start();

// Debug session info
error_log("SESSION DATA IN DEALERSHIP_FORM: " . json_encode($_SESSION));

// Updated check that matches login.php's session variable
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Create AdminHandler instance
$adminHandler = new AdminHandler($config);

// Handle POST request for creating dealership
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get dealership data
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $city = isset($_POST['city']) ? $_POST['city'] : '';
    $state = isset($_POST['state']) ? $_POST['state'] : '';
    $pincode = isset($_POST['pincode']) ? $_POST['pincode'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $latitude = isset($_POST['latitude']) ? $_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) ? $_POST['longitude'] : null;
    
    // Validation
    $errors = [];
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($city)) $errors[] = 'City is required';
    if (empty($state)) $errors[] = 'State is required';
    if (empty($pincode)) $errors[] = 'PIN Code is required';
    
    // If no errors, create dealership
    if (empty($errors)) {
        try {
            // Prepare data
            $data = [
                'name' => $name,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'pincode' => $pincode,
                'phone' => $phone,
                'email' => $email
            ];
            
            // Add latitude and longitude if provided
            if (!empty($latitude)) $data['latitude'] = $latitude;
            if (!empty($longitude)) $data['longitude'] = $longitude;
            
            // If ID is provided, update existing dealership
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $data['id'] = $_POST['id'];
                $adminHandler->updateDealership($data);
                $_SESSION['success_message'] = 'Dealership updated successfully';
            } else {
                // Create new dealership
                $adminHandler->createDealership($data);
                $_SESSION['success_message'] = 'Dealership created successfully';
            }
            
            // Redirect back to admin panel
            header("Location: index.php#dealerships");
            exit;
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// If ID is provided in GET, we're editing a dealership
$dealership = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    try {
        $dealership = $adminHandler->getDealership($_GET['id']);
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Dealership not found';
        header("Location: index.php#dealerships");
        exit;
    }
}

// Page title
$pageTitle = isset($dealership) ? 'Edit Dealership' : 'Create Dealership';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - KineticEV Admin</title>
    <link rel="icon" type="image/x-icon" href="/-/images/logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/admin.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 col-lg-8 mx-auto mt-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-store me-2"></i> <?php echo $pageTitle; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <strong>Error:</strong> 
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form action="dealership_form.php" method="post">
                            <?php if (isset($dealership)): ?>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($dealership['id']); ?>">
                            <?php endif; ?>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="name" class="form-label">Dealership Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required
                                           value="<?php echo isset($dealership) ? htmlspecialchars($dealership['name']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address *</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo isset($dealership) ? htmlspecialchars($dealership['address']) : ''; ?></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="city" class="form-label">City *</label>
                                    <input type="text" class="form-control" id="city" name="city" required
                                           value="<?php echo isset($dealership) ? htmlspecialchars($dealership['city']) : ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="state" class="form-label">State *</label>
                                    <input type="text" class="form-control" id="state" name="state" required
                                           value="<?php echo isset($dealership) ? htmlspecialchars($dealership['state']) : ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="pincode" class="form-label">PIN Code *</label>
                                    <input type="text" class="form-control" id="pincode" name="pincode" required
                                           value="<?php echo isset($dealership) ? htmlspecialchars($dealership['pincode']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                           value="<?php echo isset($dealership) ? htmlspecialchars($dealership['phone']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo isset($dealership) ? htmlspecialchars($dealership['email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <input type="number" class="form-control" id="latitude" name="latitude" step="0.00000001"
                                           value="<?php echo isset($dealership) && isset($dealership['latitude']) ? htmlspecialchars($dealership['latitude']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <input type="number" class="form-control" id="longitude" name="longitude" step="0.00000001"
                                           value="<?php echo isset($dealership) && isset($dealership['longitude']) ? htmlspecialchars($dealership['longitude']) : ''; ?>">
                                </div>
                                <div class="col-12 mt-2">
                                    <small class="text-muted">
                                        You can use <a href="https://www.latlong.net/" target="_blank">latlong.net</a> to find coordinates for an address
                                    </small>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="index.php#dealerships" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Dealership
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
