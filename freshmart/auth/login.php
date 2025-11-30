<?php
require_once '../includes/functions.php';

if(isLoggedIn()) {
    // Redirect based on role
    if(isAdmin()) {
        redirect('../admin/dashboard.php');
    } elseif(isKasir()) {
        redirect('../pos/index.php');
    } else {
        redirect('../index.php');
    }
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new Database();
    $connection = $db->getConnection();
    
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    // DEBUG: Log the attempt
    error_log("Login attempt for email: $email");
    
    try {
        $query = "SELECT u.*, c.id as customer_id 
                 FROM users u 
                 LEFT JOIN customers c ON u.id = c.user_id 
                 WHERE u.email = ? AND u.status = 'active'";
        $stmt = $connection->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // DEBUG: Log user data
        error_log("User found: " . ($user ? 'YES' : 'NO'));
        if ($user) {
            error_log("User password hash: " . $user['password']);
            error_log("Input password: $password");
            error_log("Password verify: " . (password_verify($password, $user['password']) ? 'TRUE' : 'FALSE'));
        }
        
        if($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['customer_id'] = $user['customer_id'] ?? null;
            
            // Update last login
            $update_login = $connection->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
            $update_login->execute([$user['id']]);
            
            // Log activity
            logActivity($user['id'], 'login', 'User', $user['id'], 'User logged in successfully');
            
            // Set success message
            $_SESSION['success'] = "Welcome back, " . $user['full_name'] . "!";
            
            // Redirect based on role
            if($user['role'] === 'admin' || $user['role'] === 'manager') {
                redirect('../admin/dashboard.php');
            } elseif($user['role'] === 'kasir') {
                redirect('../pos/index.php');
            } else {
                redirect('../index.php');
            }
        } else {
            $error = "Invalid email or password. Please try again.";
        }
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "System error. Please try again later.";
    }
}

// Check for success message from registration
if(isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FreshMart Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">FreshMart<span class="text-blue-600">Pro</span></h1>
            <p class="text-gray-600 mt-2">Sign in to your account</p>
        </div>
        
        <?php if($success): ?>
            <div class="alert alert-success mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" required 
                       class="form-input"
                       value="<?php echo $_POST['email'] ?? ''; ?>"
                       placeholder="Enter your email">
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" required 
                       class="form-input"
                       placeholder="Enter your password">
            </div>
            
            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-600">Remember me</span>
                </label>
                
                <a href="forgot-password.php" class="text-sm text-blue-600 hover:text-blue-800">
                    Forgot password?
                </a>
            </div>
            
            <button type="submit" class="btn btn-primary w-full">
                <i class="fas fa-sign-in-alt mr-2"></i>
                Sign In
            </button>
        </form>
        
        <div class="text-center mt-6">
            <p class="text-gray-600">
                Don't have an account? 
                <a href="register.php" class="text-blue-600 hover:text-blue-800 font-semibold">
                    Create one here
                </a>
            </p>
        </div>
        
        <!-- Back to Home Button -->
        <div class="text-center mt-4">
            <a href="../index.php" class="btn btn-outline w-full">
                <i class="fas fa-arrow-left mr-2"></i>Back to Home
            </a>
        </div>
        
        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="text-center">
                <p class="text-sm text-gray-500">Demo Accounts:</p>
                <div class="mt-2 space-y-1 text-xs text-gray-600">
                    <div><strong>Admin:</strong> admin@freshmart.com / password123</div>
                    <div><strong>Kasir:</strong> kasir@freshmart.com / password123</div>
                    <div><strong>Customer:</strong> customer@example.com / password123</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>