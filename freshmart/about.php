<?php
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - FreshMart Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container py-12">
        <!-- Hero Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">About FreshMart Pro</h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Your trusted partner for fresh groceries and daily needs. We bring quality products 
                directly to your doorstep with speed and reliability.
            </p>
        </div>

        <!-- Mission & Vision -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-16">
            <div class="card">
                <div class="text-center mb-6">
                    <div class="category-icon category-fruits mx-auto mb-4">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Our Mission</h2>
                </div>
                <p class="text-gray-600 leading-relaxed">
                    To provide fresh, high-quality groceries with convenient online shopping 
                    and fast delivery. We're committed to making your shopping experience 
                    seamless and enjoyable while supporting local producers and sustainable practices.
                </p>
            </div>

            <div class="card">
                <div class="text-center mb-6">
                    <div class="category-icon category-meat mx-auto mb-4">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Our Vision</h2>
                </div>
                <p class="text-gray-600 leading-relaxed">
                    To become the leading online supermarket in the region, known for 
                    exceptional quality, customer service, and innovation in grocery retail. 
                    We aim to transform how people shop for their daily essentials.
                </p>
            </div>
        </div>

        <!-- Values -->
        <div class="mb-16">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Our Values</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="category-icon category-dairy mx-auto mb-4">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-3">Quality First</h3>
                    <p class="text-gray-600">We never compromise on the quality of our products and services.</p>
                </div>
                
                <div class="text-center">
                    <div class="category-icon category-frozen mx-auto mb-4">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-3">Customer Focus</h3>
                    <p class="text-gray-600">Your satisfaction is our top priority in everything we do.</p>
                </div>
                
                <div class="text-center">
                    <div class="category-icon category-snacks mx-auto mb-4">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-3">Trust & Reliability</h3>
                    <p class="text-gray-600">We build lasting relationships based on trust and reliability.</p>
                </div>
            </div>
        </div>

        <!-- Team Stats -->
        <div class="card">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">By The Numbers</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-3xl font-bold text-blue-600 mb-2">1000+</div>
                    <div class="text-gray-600">Happy Customers</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-green-600 mb-2">500+</div>
                    <div class="text-gray-600">Quality Products</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-orange-600 mb-2">24/7</div>
                    <div class="text-gray-600">Customer Support</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-purple-600 mb-2">30min</div>
                    <div class="text-gray-600">Fast Delivery</div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>