<?php
require_once '../includes/functions.php';

if(isLoggedIn()) {
    redirect('../index.php');
}

$errors = [];
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new Database();
    $connection = $db->getConnection();
    
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if(empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if(empty($email)) {
        $errors[] = "Email is required";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if(empty($password)) {
        $errors[] = "Password is required";
    } elseif(strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if email exists
    if(empty($errors)) {
        try {
            $check_email = $connection->prepare("SELECT id FROM users WHERE email = ?");
            $check_email->execute([$email]);
            if($check_email->rowCount() > 0) {
                $errors[] = "Email already registered";
            }
        } catch (PDOException $e) {
            error_log("Email check error: " . $e->getMessage());
            $errors[] = "System error. Please try again.";
        }
    }
    
    if(empty($errors)) {
        try {
            $connection->beginTransaction();
            
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_user = $connection->prepare("
                INSERT INTO users (username, email, password, full_name, phone, role) 
                VALUES (?, ?, ?, ?, ?, 'customer')
            ");
            
            $insert_success = $insert_user->execute([
                $email, 
                $email, 
                $hashed_password, 
                $full_name, 
                $phone
            ]);
            
            if (!$insert_success) {
                throw new Exception("Failed to create user account");
            }
            
            $user_id = $connection->lastInsertId();
            
            // Create customer record
            $referral_code = strtoupper(substr(md5(uniqid()), 0, 8));
            $insert_customer = $connection->prepare("
                INSERT INTO customers (user_id, referral_code) 
                VALUES (?, ?)
            ");
            
            $customer_success = $insert_customer->execute([$user_id, $referral_code]);
            
            if (!$customer_success) {
                throw new Exception("Failed to create customer profile");
            }
            
            // Log activity
            logActivity($user_id, 'register', 'User', $user_id, 'New user registration');
            
            $connection->commit();
            
            $_SESSION['success'] = "Registration successful! Please login to continue.";
            redirect('login.php');
            
        } catch(PDOException $e) {
            $connection->rollBack();
            error_log("Registration PDO error: " . $e->getMessage());
            $errors[] = "Registration failed. Please try again. Error: " . $e->getMessage();
        } catch(Exception $e) {
            $connection->rollBack();
            error_log("Registration error: " . $e->getMessage());
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FreshMart Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-8">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">FreshMart<span class="text-blue-600">Pro</span></h1>
            <p class="text-gray-600 mt-2">Create your account</p>
        </div>
        
        <?php if(!empty($errors)): ?>
            <div class="alert alert-error mb-4">
                <?php foreach($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <div class="form-group">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" id="full_name" name="full_name" required 
                       class="form-input"
                       value="<?php echo $_POST['full_name'] ?? ''; ?>"
                       placeholder="Enter your full name">
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" required 
                       class="form-input"
                       value="<?php echo $_POST['email'] ?? ''; ?>"
                       placeholder="Enter your email">
            </div>
            
            <div class="form-group">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="tel" id="phone" name="phone" 
                       class="form-input"
                       value="<?php echo $_POST['phone'] ?? ''; ?>"
                       placeholder="Enter your phone number">
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" required 
                       class="form-input"
                       placeholder="Enter your password (min. 6 characters)">
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       class="form-input"
                       placeholder="Confirm your password">
            </div>
            
            <div class="form-group">
                <label class="flex items-center">
                    <input type="checkbox" name="terms" required 
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-600">
                        I agree to the 
                        <a href="#" class="text-blue-600 hover:text-blue-800">Terms of Service</a>
                        and 
                        <a href="#" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>
                    </span>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary w-full">
                <i class="fas fa-user-plus mr-2"></i>
                Create Account
            </button>
        </form>
        
        <div class="text-center mt-6">
            <p class="text-gray-600">
                Already have an account? 
                <a href="login.php" class="text-blue-600 hover:text-blue-800 font-semibold">
                    Sign in here
                </a>
            </p>
        </div>
        
        <!-- Back to Home Button -->
        <div class="text-center mt-4">
            <a href="../index.php" class="btn btn-outline w-full">
                <i class="fas fa-arrow-left mr-2"></i>Back to Home
            </a>
        </div>
    </div>
</body>
</html>