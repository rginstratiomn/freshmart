<?php
// Check if system is already installed
if (file_exists('config/database.php')) {
    header('Location: index.php');
    exit();
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // Database configuration
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_name = $_POST['db_name'] ?? 'freshmart_db';
        $db_user = $_POST['db_user'] ?? 'root';
        $db_pass = $_POST['db_pass'] ?? '';
        
        // Test database connection
        try {
            $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
            
            // Save database configuration
            $config_content = "<?php
class Database {
    private \$host = \"$db_host\";
    private \$db_name = \"$db_name\";
    private \$username = \"$db_user\";
    private \$password = \"$db_pass\";
    public \$conn;

    public function getConnection() {
        \$this->conn = null;
        try {
            \$this->conn = new PDO(
                \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name, 
                \$this->username, 
                \$this->password,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
            \$this->conn->exec(\"set names utf8\");
        } catch(PDOException \$exception) {
            error_log(\"Connection error: \" . \$exception->getMessage());
            return false;
        }
        return \$this->conn;
    }
}
?>";
            
            // Create config directory if not exists
            if (!file_exists('config')) {
                mkdir('config', 0755, true);
            }
            
            file_put_contents('config/database.php', $config_content);
            $step = 2;
            
        } catch (PDOException $e) {
            $error = "Database connection failed: " . $e->getMessage();
        }
    }
    
    if ($step == 2) {
        // Create admin account
        $admin_username = $_POST['admin_username'] ?? 'admin';
        $admin_email = $_POST['admin_email'] ?? 'admin@freshmart.com';
        $admin_password = $_POST['admin_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $store_name = $_POST['store_name'] ?? 'FreshMart Pro';
        
        if (empty($admin_password)) {
            $error = "Password is required";
        } elseif ($admin_password !== $confirm_password) {
            $error = "Passwords do not match";
        } else {
            try {
                // Include database configuration
                require_once 'config/database.php';
                
                $db = new Database();
                $connection = $db->getConnection();
                
                // Read and execute SQL file
                $sql = file_get_contents('database/freshmart_db.sql');
                $connection->exec($sql);
                
                // Create admin user
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                $insert_admin = $connection->prepare("
                    INSERT INTO users (username, email, password, full_name, role) 
                    VALUES (?, ?, ?, 'Administrator', 'admin')
                ");
                $insert_admin->execute([$admin_username, $admin_email, $hashed_password]);
                
                // Update store settings
                $update_store = $connection->prepare("
                    UPDATE system_settings SET setting_value = ? WHERE setting_key = 'store_name'
                ");
                $update_store->execute([$store_name]);
                
                // Create uploads directory
                if (!file_exists('uploads')) {
                    mkdir('uploads', 0755, true);
                }
                
                $success = "Installation completed successfully!";
                $step = 3;
                
            } catch (PDOException $e) {
                $error = "Installation failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshMart Pro - Installation</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-8">
    <div class="max-w-2xl w-full bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800">FreshMart<span class="text-blue-600">Pro</span></h1>
            <p class="text-gray-600 mt-2">Installation Wizard</p>
        </div>
        
        <!-- Progress Steps -->
        <div class="flex justify-center mb-8">
            <div class="flex items-center">
                <div class="flex items-center <?php echo $step >= 1 ? 'text-blue-600' : 'text-gray-400'; ?>">
                    <div class="w-8 h-8 rounded-full border-2 <?php echo $step >= 1 ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300'; ?> flex items-center justify-center text-sm">
                        1
                    </div>
                    <span class="ml-2 text-sm font-medium">Database</span>
                </div>
                
                <div class="w-16 h-1 mx-2 <?php echo $step >= 2 ? 'bg-blue-600' : 'bg-gray-300'; ?>"></div>
                
                <div class="flex items-center <?php echo $step >= 2 ? 'text-blue-600' : 'text-gray-400'; ?>">
                    <div class="w-8 h-8 rounded-full border-2 <?php echo $step >= 2 ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300'; ?> flex items-center justify-center text-sm">
                        2
                    </div>
                    <span class="ml-2 text-sm font-medium">Admin Setup</span>
                </div>
                
                <div class="w-16 h-1 mx-2 <?php echo $step >= 3 ? 'bg-blue-600' : 'bg-gray-300'; ?>"></div>
                
                <div class="flex items-center <?php echo $step >= 3 ? 'text-blue-600' : 'text-gray-400'; ?>">
                    <div class="w-8 h-8 rounded-full border-2 <?php echo $step >= 3 ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300'; ?> flex items-center justify-center text-sm">
                        3
                    </div>
                    <span class="ml-2 text-sm font-medium">Complete</span>
                </div>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success mb-6">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <!-- Step 1: Database Configuration -->
        <?php if ($step == 1): ?>
            <form method="POST" class="space-y-6">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Database Configuration</h2>
                    <p class="text-gray-600">Enter your database connection details</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Database Host</label>
                        <input type="text" name="db_host" value="localhost" required 
                               class="form-input" placeholder="localhost">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Database Name</label>
                        <input type="text" name="db_name" value="freshmart_db" required 
                               class="form-input" placeholder="freshmart_db">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Database Username</label>
                        <input type="text" name="db_user" value="root" required 
                               class="form-input" placeholder="root">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Database Password</label>
                        <input type="password" name="db_pass" 
                               class="form-input" placeholder="Database password">
                    </div>
                </div>
                
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-blue-800 mb-2">Requirements:</h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• MySQL 5.7 or higher</li>
                        <li>• PHP 7.4 or higher</li>
                        <li>• PDO MySQL extension</li>
                        <li>• Write permissions for config and uploads directories</li>
                    </ul>
                </div>
                
                <button type="submit" class="btn btn-primary w-full">
                    Continue to Next Step <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </form>
        <?php endif; ?>
        
        <!-- Step 2: Admin Setup -->
        <?php if ($step == 2): ?>
            <form method="POST" class="space-y-6">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Admin Account Setup</h2>
                    <p class="text-gray-600">Create your administrator account</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Store Name</label>
                    <input type="text" name="store_name" value="FreshMart Pro" required 
                           class="form-input" placeholder="Your Store Name">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Admin Username</label>
                        <input type="text" name="admin_username" value="admin" required 
                               class="form-input" placeholder="admin">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Admin Email</label>
                        <input type="email" name="admin_email" value="admin@freshmart.com" required 
                               class="form-input" placeholder="admin@yourstore.com">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Admin Password</label>
                        <input type="password" name="admin_password" required 
                               class="form-input" placeholder="Enter a strong password">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" required 
                               class="form-input" placeholder="Confirm your password">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-full">
                    Complete Installation <i class="fas fa-check ml-2"></i>
                </button>
            </form>
        <?php endif; ?>
        
        <!-- Step 3: Installation Complete -->
        <?php if ($step == 3): ?>
            <div class="text-center space-y-6">
                <div class="text-green-500 text-6xl mb-4">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-800">Installation Complete!</h2>
                <p class="text-gray-600">FreshMart Pro has been successfully installed on your server.</p>
                
                <div class="bg-gray-50 p-4 rounded-lg text-left">
                    <h3 class="font-semibold text-gray-800 mb-2">Next Steps:</h3>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li>• <strong>Visit your store:</strong> <a href="index.php" class="text-blue-600 hover:text-blue-800">Go to Website</a></li>
                        <li>• <strong>Access admin panel:</strong> <a href="admin/dashboard.php" class="text-blue-600 hover:text-blue-800">Admin Dashboard</a></li>
                        <li>• <strong>Default login:</strong> Use the admin credentials you just created</li>
                        <li>• <strong>Security:</strong> Change default passwords and configure your store settings</li>
                    </ul>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-lg text-left">
                    <h3 class="font-semibold text-yellow-800 mb-2">Important Security Notes:</h3>
                    <ul class="text-sm text-yellow-700 space-y-1">
                        <li>• Delete or rename the <code>install.php</code> file</li>
                        <li>• Change default admin password immediately</li>
                        <li>• Configure proper file permissions</li>
                        <li>• Set up SSL certificate for production use</li>
                    </ul>
                </div>
                
                <div class="flex space-x-4">
                    <a href="index.php" class="btn btn-primary flex-1">
                        <i class="fas fa-store mr-2"></i>Visit Store
                    </a>
                    <a href="admin/dashboard.php" class="btn btn-outline flex-1">
                        <i class="fas fa-cog mr-2"></i>Admin Panel
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="mt-8 pt-6 border-t border-gray-200 text-center text-sm text-gray-500">
            <p>FreshMart Pro &copy; 2024 - Supermarket Management System</p>
        </div>
    </div>
</body>
</html>