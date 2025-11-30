<?php
require_once 'includes/functions.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // In a real application, you would send an email here
        // For now, we'll just show a success message
        $success = "Thank you for your message! We'll get back to you within 24 hours.";
        
        // You can also save to database if needed
        try {
            $db = new Database();
            $connection = $db->getConnection();
            
            $insert_contact = $connection->prepare("
                INSERT INTO contact_messages (name, email, subject, message, status) 
                VALUES (?, ?, ?, ?, 'new')
            ");
            $insert_contact->execute([$name, $email, $subject, $message]);
            
        } catch (PDOException $e) {
            error_log("Contact form error: " . $e->getMessage());
        }
    }
}

// Create contact_messages table if not exists (run this SQL in PHPMyAdmin)
/*
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
*/
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - FreshMart Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container py-12">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-800 mb-4">Contact Us</h1>
                <p class="text-xl text-gray-600">
                    Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- Contact Information -->
                <div class="lg:col-span-1">
                    <div class="card">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">Get in Touch</h2>
                        
                        <div class="space-y-6">
                            <div class="flex items-start space-x-4">
                                <div class="category-icon category-fruits">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800">Address</h3>
                                    <p class="text-gray-600">123 Supermarket Street<br>Jakarta, Indonesia 12345</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start space-x-4">
                                <div class="category-icon category-meat">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800">Phone</h3>
                                    <p class="text-gray-600">(021) 1234-5678</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start space-x-4">
                                <div class="category-icon category-dairy">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800">Email</h3>
                                    <p class="text-gray-600">info@freshmartpro.com</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start space-x-4">
                                <div class="category-icon category-frozen">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800">Business Hours</h3>
                                    <p class="text-gray-600">
                                        Mon-Sun: 6:00 AM - 10:00 PM<br>
                                        24/7 Online Support
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="lg:col-span-2">
                    <div class="card">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">Send us a Message</h2>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success mb-6">
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-error mb-6">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" id="name" name="name" required 
                                           class="form-input"
                                           value="<?php echo $_POST['name'] ?? ''; ?>"
                                           placeholder="Your full name">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" id="email" name="email" required 
                                           class="form-input"
                                           value="<?php echo $_POST['email'] ?? ''; ?>"
                                           placeholder="your.email@example.com">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="subject" class="form-label">Subject *</label>
                                <input type="text" id="subject" name="subject" required 
                                       class="form-input"
                                       value="<?php echo $_POST['subject'] ?? ''; ?>"
                                       placeholder="What is this regarding?">
                            </div>
                            
                            <div class="form-group">
                                <label for="message" class="form-label">Message *</label>
                                <textarea id="message" name="message" required 
                                          class="form-textarea h-32"
                                          placeholder="Please describe your inquiry in detail..."><?php echo $_POST['message'] ?? ''; ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-full">
                                <i class="fas fa-paper-plane mr-2"></i>Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>