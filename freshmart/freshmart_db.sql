-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 29, 2025 at 05:29 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `freshmart_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GenerateOrderNumber` (OUT `new_order_number` VARCHAR(50))   BEGIN
    DECLARE today_date VARCHAR(8);
    DECLARE last_number INT;
    
    SET today_date = DATE_FORMAT(NOW(), '%Y%m%d');
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(order_number, 13) AS UNSIGNED)), 0) + 1 
    INTO last_number
    FROM orders 
    WHERE order_number LIKE CONCAT('ORD-', today_date, '-%');
    
    SET new_order_number = CONCAT('ORD-', today_date, '-', LPAD(last_number, 4, '0'));
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GeneratePONumber` (OUT `new_po_number` VARCHAR(50))   BEGIN
    DECLARE today_date VARCHAR(8);
    DECLARE last_number INT;
    
    SET today_date = DATE_FORMAT(NOW(), '%Y%m%d');
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(po_number, 12) AS UNSIGNED)), 0) + 1 
    INTO last_number
    FROM purchase_orders 
    WHERE po_number LIKE CONCAT('PO-', today_date, '-%');
    
    SET new_po_number = CONCAT('PO-', today_date, '-', LPAD(last_number, 4, '0'));
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GenerateTransactionCode` (OUT `new_transaction_code` VARCHAR(50))   BEGIN
    DECLARE today_date VARCHAR(8);
    DECLARE last_number INT;
    
    SET today_date = DATE_FORMAT(NOW(), '%Y%m%d');
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(transaction_code, 13) AS UNSIGNED)), 0) + 1 
    INTO last_number
    FROM transactions 
    WHERE transaction_code LIKE CONCAT('TRX-', today_date, '-%');
    
    SET new_transaction_code = CONCAT('TRX-', today_date, '-', LPAD(last_number, 4, '0'));
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `CalculateLoyaltyPoints` (`order_total` DECIMAL(12,2)) RETURNS INT(11) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE points INT;
    SET points = FLOOR(order_total / 1000); -- 1 point per Rp 1.000
    RETURN points;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `model_type` varchar(100) DEFAULT NULL,
  `model_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `model_type`, `model_id`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'User', 1, 'User logged in successfully', '127.0.0.1', NULL, '2025-11-29 14:00:50'),
(2, 1, 'create', 'Product', 1, 'Created new product: Apel Fuji Premium', '127.0.0.1', NULL, '2025-11-29 14:00:50');

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `label` varchar(50) DEFAULT 'Rumah',
  `recipient_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `customer_id`, `label`, `recipient_name`, `phone`, `address_line1`, `address_line2`, `city`, `province`, `postal_code`, `is_default`, `latitude`, `longitude`, `created_at`, `updated_at`) VALUES
(1, 1, 'Rumah', 'John Customer', '081234567890', 'Jl. Merdeka No. 123', NULL, 'Jakarta', 'DKI Jakarta', '12345', 1, NULL, NULL, '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(2, 1, 'Kantor', 'John Customer', '081234567891', 'Jl. Sudirman No. 456', NULL, 'Jakarta', 'DKI Jakarta', '67890', 0, NULL, NULL, '2025-11-29 14:00:50', '2025-11-29 14:00:50');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price_at_add` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `nama_kategori`, `slug`, `deskripsi`, `icon`, `banner_image`, `parent_id`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Buah & Sayur', 'buah-sayur', 'Segar dan sehat langsung dari petani', 'apple-alt', NULL, NULL, 1, 1, '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(2, 'Daging & Ikan', 'daging-ikan', 'Daging dan ikan segar berkualitas', 'drumstick-bite', NULL, NULL, 2, 1, '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(3, 'Susu & Telur', 'susu-telur', 'Produk dairy dan telur segar', 'egg', NULL, NULL, 3, 1, '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(4, 'Makanan Beku', 'makanan-beku', 'Makanan beku praktis', 'snowflake', NULL, NULL, 4, 1, '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(5, 'Snack & Minuman', 'snack-minuman', 'Camilan dan minuman ringan', 'cookie', NULL, NULL, 5, 1, '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(6, 'Kebutuhan Rumah', 'kebutuhan-rumah', 'Produk kebersihan dan rumah tangga', 'home', NULL, NULL, 6, 1, '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(7, 'Buah Segar', 'buah-segar', 'Buah-buahan segar', NULL, NULL, 1, 1, 1, '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(8, 'Sayuran', 'sayuran', 'Sayuran segar', NULL, NULL, 1, 2, 1, '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(9, 'Daging Sapi', 'daging-sapi', 'Daging sapi segar', NULL, NULL, 2, 1, 1, '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(10, 'Daging Ayam', 'daging-ayam', 'Daging ayam segar', NULL, NULL, 2, 2, 1, '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(11, 'Ikan Segar', 'ikan-segar', 'Ikan laut dan air tawar', NULL, NULL, 2, 3, 1, '2025-11-29 14:00:50', '2025-11-29 14:00:50');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `loyalty_points` int(11) DEFAULT 0,
  `total_spending` decimal(12,2) DEFAULT 0.00,
  `customer_tier` enum('bronze','silver','gold','platinum') DEFAULT 'bronze',
  `referral_code` varchar(20) DEFAULT NULL,
  `referred_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `user_id`, `date_of_birth`, `gender`, `loyalty_points`, `total_spending`, `customer_tier`, `referral_code`, `referred_by`, `created_at`, `updated_at`) VALUES
(1, 4, '1990-01-15', 'male', 1500, 2500000.00, 'gold', 'REF123456', NULL, '2025-11-29 14:00:50', '2025-11-29 14:00:50');

-- --------------------------------------------------------

--
-- Stand-in structure for view `customer_analytics`
-- (See below for the actual view)
--
CREATE TABLE `customer_analytics` (
`id` int(11)
,`full_name` varchar(100)
,`email` varchar(100)
,`customer_tier` enum('bronze','silver','gold','platinum')
,`total_spending` decimal(12,2)
,`loyalty_points` int(11)
,`total_orders` bigint(21)
,`last_order_date` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `daily_sales_summary`
-- (See below for the actual view)
--
CREATE TABLE `daily_sales_summary` (
`sale_date` date
,`total_orders` bigint(21)
,`total_revenue` decimal(34,2)
,`avg_order_value` decimal(16,6)
);

-- --------------------------------------------------------

--
-- Table structure for table `employees_shifts`
--

CREATE TABLE `employees_shifts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `shift_type` enum('pagi','siang','malam') NOT NULL,
  `shift_start` time NOT NULL,
  `shift_end` time NOT NULL,
  `clock_in` datetime DEFAULT NULL,
  `clock_out` datetime DEFAULT NULL,
  `status` enum('scheduled','completed','absent','late') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `inventory_alert`
-- (See below for the actual view)
--
CREATE TABLE `inventory_alert` (
`id` int(11)
,`nama_produk` varchar(255)
,`sku` varchar(50)
,`stok` int(11)
,`minimum_stok` int(11)
,`nama_kategori` varchar(100)
,`stock_status` varchar(12)
);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `data`, `read_at`, `created_at`) VALUES
(1, 1, 'order_placed', 'Pesanan Baru', 'Pesanan ORD-20240115-0001 telah diterima', NULL, NULL, '2025-11-29 14:00:50'),
(2, 4, 'order_confirmed', 'Pesanan Dikonfirmasi', 'Pesanan Anda ORD-20240115-0001 telah dikonfirmasi', NULL, NULL, '2025-11-29 14:00:50');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `address_id` int(11) DEFAULT NULL,
  `shipping_recipient_name` varchar(100) DEFAULT NULL,
  `shipping_phone` varchar(20) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_province` varchar(100) DEFAULT NULL,
  `shipping_postal_code` varchar(10) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `voucher_code` varchar(50) DEFAULT NULL,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `shipping_cost` decimal(12,2) DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL,
  `payment_method` enum('COD','transfer_bank','ewallet','credit_card') DEFAULT 'COD',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_proof` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `order_status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `shipping_method` varchar(50) DEFAULT 'regular',
  `tracking_number` varchar(100) DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `loyalty_points_earned` int(11) DEFAULT 0,
  `loyalty_points_used` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `address_id`, `shipping_recipient_name`, `shipping_phone`, `shipping_address`, `shipping_city`, `shipping_province`, `shipping_postal_code`, `subtotal`, `discount_amount`, `voucher_code`, `tax_amount`, `shipping_cost`, `grand_total`, `payment_method`, `payment_status`, `payment_proof`, `paid_at`, `order_status`, `shipping_method`, `tracking_number`, `shipped_at`, `delivered_at`, `notes`, `admin_notes`, `cancellation_reason`, `loyalty_points_earned`, `loyalty_points_used`, `created_at`, `updated_at`) VALUES
(1, 'ORD-20240115-0001', 1, 1, 'John Customer', '081234567890', 'Jl. Merdeka No. 123', 'Jakarta', 'DKI Jakarta', '12345', 75000.00, 0.00, NULL, 7500.00, 15000.00, 97500.00, 'COD', 'pending', NULL, NULL, 'pending', 'regular', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(2, 'ORD-20240115-0002', 1, 1, 'John Customer', '081234567890', 'Jl. Merdeka No. 123', 'Jakarta', 'DKI Jakarta', '12345', 120000.00, 10000.00, NULL, 11000.00, 15000.00, 136000.00, 'transfer_bank', 'paid', NULL, NULL, 'delivered', 'regular', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-29 14:00:50', '2025-11-29 14:00:50');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `after_order_completed` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    IF NEW.order_status = 'delivered' AND OLD.order_status != 'delivered' THEN
        -- Update total spending dan loyalty points
        UPDATE customers 
        SET total_spending = total_spending + NEW.grand_total,
            loyalty_points = loyalty_points + NEW.loyalty_points_earned
        WHERE id = NEW.customer_id;
        
        -- Update customer tier berdasarkan spending
        UPDATE customers 
        SET customer_tier = CASE 
            WHEN total_spending >= 5000000 THEN 'platinum'
            WHEN total_spending >= 2000000 THEN 'gold' 
            WHEN total_spending >= 500000 THEN 'silver'
            ELSE 'bronze'
        END
        WHERE id = NEW.customer_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_order_insert` BEFORE INSERT ON `orders` FOR EACH ROW BEGIN
    IF NEW.order_number IS NULL THEN
        CALL GenerateOrderNumber(@new_order_number);
        SET NEW.order_number = @new_order_number;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_sku` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `product_name`, `product_sku`, `quantity`, `price`, `subtotal`, `created_at`) VALUES
(1, 1, 1, 'Apel Fuji Premium', 'PRD-APL001', 2, 25000.00, 50000.00, '2025-11-29 14:00:50'),
(2, 1, 7, 'Telur Ayam Negeri', 'PRD-TEL001', 1, 25000.00, 25000.00, '2025-11-29 14:00:50'),
(3, 2, 3, 'Daging Sapi Premium', 'PRD-DGS001', 1, 120000.00, 120000.00, '2025-11-29 14:00:50');

--
-- Triggers `order_details`
--
DELIMITER $$
CREATE TRIGGER `after_order_detail_insert` AFTER INSERT ON `order_details` FOR EACH ROW BEGIN
    UPDATE products 
    SET sales_count = sales_count + NEW.quantity,
        stok = stok - NEW.quantity
    WHERE id = NEW.product_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `sku` varchar(50) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `nama_produk` varchar(255) NOT NULL,
  `slug` varchar(300) NOT NULL,
  `deskripsi_pendek` varchar(255) DEFAULT NULL,
  `deskripsi_lengkap` text DEFAULT NULL,
  `harga_beli` decimal(12,2) NOT NULL,
  `harga_jual` decimal(12,2) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `minimum_stok` int(11) DEFAULT 5,
  `satuan` varchar(20) DEFAULT 'pcs',
  `berat` decimal(8,2) DEFAULT 0.00,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_available_online` tinyint(1) DEFAULT 1,
  `foto_utama` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `views` int(11) DEFAULT 0,
  `sales_count` int(11) DEFAULT 0,
  `rating_average` decimal(2,1) DEFAULT 0.0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `supplier_id`, `sku`, `barcode`, `nama_produk`, `slug`, `deskripsi_pendek`, `deskripsi_lengkap`, `harga_beli`, `harga_jual`, `stok`, `minimum_stok`, `satuan`, `berat`, `is_featured`, `is_available_online`, `foto_utama`, `status`, `views`, `sales_count`, `rating_average`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 6, 1, 'PRD-APL001', '8991002101011', 'Apel Fuji Premium', 'apel-fuji-premium', 'Apel Fuji segar import dengan rasa manis', 'Apel Fuji premium import dengan tekstur renyah dan rasa manis alami. Cocok untuk konsumsi langsung atau bahan salad buah.', 15000.00, 25000.00, 50, 10, 'kg', 1000.00, 1, 1, NULL, 'active', 0, 0, 0.0, '2025-11-29 14:00:50', '2025-11-29 14:00:50', NULL),
(2, 6, 1, 'PRD-PIS001', '8991002101028', 'Pisang Ambon', 'pisang-ambon', 'Pisang Ambon segar lokal', 'Pisang Ambon segar dari petani lokal dengan rasa manis dan tekstur lembut.', 8000.00, 15000.00, 100, 20, 'kg', 1000.00, 0, 1, NULL, 'active', 0, 0, 0.0, '2025-11-29 14:00:50', '2025-11-29 14:00:50', NULL),
(3, 7, 2, 'PRD-DGS001', '8991002101035', 'Daging Sapi Premium', 'daging-sapi-premium', 'Daging sapi segar kualitas premium', 'Daging sapi segar dengan marbling yang baik, cocok untuk steak atau rendang.', 85000.00, 120000.00, 25, 5, 'kg', 1000.00, 1, 1, NULL, 'active', 0, 0, 0.0, '2025-11-29 14:00:50', '2025-11-29 14:00:50', NULL),
(4, 8, 2, 'PRD-DGA001', '8991002101042', 'Daging Ayam Fillet', 'daging-ayam-fillet', 'Daging ayam fillet tanpa tulang', 'Daging ayam fillet segar tanpa tulang, praktis untuk berbagai masakan.', 35000.00, 45000.00, 40, 10, 'kg', 1000.00, 0, 1, NULL, 'active', 0, 0, 0.0, '2025-11-29 14:00:50', '2025-11-29 14:00:50', NULL),
(5, 9, 3, 'PRD-IKN001', '8991002101059', 'Ikan Salmon Fillet', 'ikan-salmon-fillet', 'Ikan salmon fillet segar import', 'Ikan salmon fillet segar import Norwegia, kaya omega-3 dan nutrisi.', 120000.00, 180000.00, 15, 5, 'kg', 1000.00, 1, 1, NULL, 'active', 0, 0, 0.0, '2025-11-29 14:00:50', '2025-11-29 14:00:50', NULL),
(6, 3, 1, 'PRD-SUS001', '8991002101066', 'Susu Ultra Milk', 'susu-ultra-milk', 'Susu UHT full cream 1 liter', 'Susu UHT Ultra Milk full cream kemasan 1 liter, pasteurisasi untuk menjaga nutrisi.', 15000.00, 22000.00, 60, 15, 'pcs', 1000.00, 0, 1, NULL, 'active', 0, 0, 0.0, '2025-11-29 14:00:50', '2025-11-29 14:00:50', NULL),
(7, 3, 1, 'PRD-TEL001', '8991002101073', 'Telur Ayam Negeri', 'telur-ayam-negeri', 'Telur ayam negeri segar isi 10', 'Telur ayam negeri segar ukuran besar, isi 10 butir per pack.', 18000.00, 25000.00, 80, 20, 'pack', 600.00, 1, 1, NULL, 'active', 0, 0, 0.0, '2025-11-29 14:00:50', '2025-11-29 14:00:50', NULL),
(8, 5, 1, 'PRD-CHP001', '8991002101080', 'Chips Kentang', 'chips-kentang', 'Chips kentang rasa original', 'Chips kentang renyah dengan rasa original, kemasan 150gr.', 8000.00, 15000.00, 100, 25, 'pcs', 150.00, 0, 1, NULL, 'active', 0, 0, 0.0, '2025-11-29 14:00:50', '2025-11-29 14:00:50', NULL),
(9, 5, 1, 'PRD-MNZ001', '8991002101097', 'Minuman Soda', 'minuman-soda', 'Minuman soda kaleng 330ml', 'Minuman soda berkarbonasi dalam kemasan kaleng 330ml.', 5000.00, 8000.00, 120, 30, 'pcs', 330.00, 0, 1, NULL, 'active', 0, 0, 0.0, '2025-11-29 14:00:50', '2025-11-29 14:00:50', NULL),
(10, 6, 1, 'PRD-SBN001', '8991002101103', 'Sabun Mandi', 'sabun-mandi', 'Sabun mandi anti bakteri', 'Sabun mandi dengan formula anti bakteri, wangi segar.', 6000.00, 10000.00, 75, 15, 'pcs', 100.00, 0, 1, NULL, 'active', 0, 0, 0.0, '2025-11-29 14:00:50', '2025-11-29 14:00:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `product_id`, `category_id`, `created_at`) VALUES
(1, 6, 3, '2025-11-29 16:01:02'),
(2, 7, 3, '2025-11-29 16:01:02'),
(3, 8, 5, '2025-11-29 16:01:02'),
(4, 9, 5, '2025-11-29 16:01:02'),
(5, 1, 6, '2025-11-29 16:01:02'),
(6, 2, 6, '2025-11-29 16:01:02'),
(7, 10, 6, '2025-11-29 16:01:02'),
(8, 3, 7, '2025-11-29 16:01:02'),
(9, 4, 8, '2025-11-29 16:01:02'),
(10, 5, 9, '2025-11-29 16:01:02'),
(16, 1, 1, '2025-11-29 16:01:02'),
(17, 2, 1, '2025-11-29 16:01:02'),
(18, 3, 2, '2025-11-29 16:01:02'),
(19, 4, 2, '2025-11-29 16:01:02'),
(20, 5, 2, '2025-11-29 16:01:02');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `product_performance`
-- (See below for the actual view)
--
CREATE TABLE `product_performance` (
`id` int(11)
,`nama_produk` varchar(255)
,`sku` varchar(50)
,`nama_kategori` varchar(100)
,`harga_jual` decimal(12,2)
,`stok` int(11)
,`sales_count` int(11)
,`rating_average` decimal(2,1)
,`views` int(11)
,`conversion_rate` decimal(16,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_detail_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 0,
  `admin_reply` text DEFAULT NULL,
  `helpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `product_reviews`
--
DELIMITER $$
CREATE TRIGGER `after_product_review_insert` AFTER INSERT ON `product_reviews` FOR EACH ROW BEGIN
    UPDATE products 
    SET rating_average = (
        SELECT AVG(rating) 
        FROM product_reviews 
        WHERE product_id = NEW.product_id AND is_approved = TRUE
    )
    WHERE id = NEW.product_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `nama_promo` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `promo_type` enum('discount','buy_x_get_y','bundle','flash_sale') NOT NULL,
  `discount_type` enum('percentage','fixed') DEFAULT NULL,
  `discount_value` decimal(12,2) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `banner_image` varchar(255) DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotion_products`
--

CREATE TABLE `promotion_products` (
  `id` int(11) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `special_price` decimal(12,2) DEFAULT NULL,
  `stock_limit` int(11) DEFAULT NULL,
  `stock_sold` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `shipping_cost` decimal(12,2) DEFAULT NULL,
  `grand_total` decimal(12,2) NOT NULL,
  `status` enum('draft','submitted','approved','received','cancelled') DEFAULT 'draft',
  `payment_status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `payment_due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_details`
--

CREATE TABLE `purchase_order_details` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_ordered` int(11) NOT NULL,
  `quantity_received` int(11) DEFAULT 0,
  `price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `sales_report`
-- (See below for the actual view)
--
CREATE TABLE `sales_report` (
`sale_date` date
,`total_orders` bigint(21)
,`total_revenue` decimal(34,2)
,`average_order_value` decimal(16,6)
,`unique_customers` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movement_type` enum('in','out','adjustment','return','damage') NOT NULL,
  `quantity` int(11) NOT NULL,
  `stock_before` int(11) NOT NULL,
  `stock_after` int(11) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `user_id`, `movement_type`, `quantity`, `stock_before`, `stock_after`, `reference_type`, `reference_id`, `reference_number`, `notes`, `created_at`) VALUES
(1, 1, 1, 'in', 100, 0, 100, 'purchase_order', 1, 'PO-001', 'Stok awal', '2025-11-29 14:00:50'),
(2, 2, 1, 'in', 200, 0, 200, 'purchase_order', 1, 'PO-001', 'Stok awal', '2025-11-29 14:00:50'),
(3, 1, 2, 'out', 2, 100, 98, 'order', 1, 'ORD-20240115-0001', 'Penjualan online', '2025-11-29 14:00:50'),
(4, 2, 2, 'out', 3, 200, 197, 'transaction', 1, 'TRX-20240115-0001', 'Penjualan POS', '2025-11-29 14:00:50');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `kode_supplier` varchar(20) NOT NULL,
  `nama_supplier` varchar(100) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `tax_number` varchar(30) DEFAULT NULL,
  `payment_terms` varchar(50) DEFAULT 'Cash',
  `status` enum('active','inactive') DEFAULT 'active',
  `rating` decimal(2,1) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `kode_supplier`, `nama_supplier`, `company_name`, `alamat`, `city`, `phone`, `email`, `contact_person`, `tax_number`, `payment_terms`, `status`, `rating`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'SUP001', 'PT Sumber Segar', 'PT Sumber Segar Abadi', 'Jl. Raya Bogor KM 25', 'Jakarta', '021-1234567', 'supplier@sumbersegar.com', 'Budi Santoso', NULL, 'Cash', 'active', NULL, NULL, '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(2, 'SUP002', 'CV Daging Premium', 'CV Daging Premium Quality', 'Jl. Pemuda No. 45', 'Bandung', '022-7654321', 'info@dagingpremium.com', 'Sari Wijaya', NULL, 'Cash', 'active', NULL, NULL, '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(3, 'SUP003', 'UD Laut Sejahtera', 'UD Laut Sejahtera Makmur', 'Jl. Pelabuhan No. 12', 'Surabaya', '031-9876543', 'contact@lautsejahtera.com', 'Ahmad Fauzi', NULL, 'Cash', 'active', NULL, NULL, '2025-11-29 14:00:50', '2025-11-29 14:00:50');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'store_name', 'FreshMart Pro', 'string', 'Nama toko', '2025-11-29 14:00:50'),
(2, 'store_email', 'info@freshmartpro.com', 'string', 'Email toko', '2025-11-29 14:00:50'),
(3, 'store_phone', '(021) 1234-5678', 'string', 'Telepon toko', '2025-11-29 14:00:50'),
(4, 'store_address', 'Jl. Supermarket No. 123, Jakarta', 'string', 'Alamat toko', '2025-11-29 14:00:50'),
(5, 'tax_rate', '10', 'integer', 'Persentase pajak', '2025-11-29 14:00:50'),
(6, 'shipping_cost', '15000', 'integer', 'Biaya pengiriman standar', '2025-11-29 14:00:50'),
(7, 'currency', 'IDR', 'string', 'Mata uang', '2025-11-29 14:00:50'),
(8, 'maintenance_mode', 'false', 'boolean', 'Mode maintenance', '2025-11-29 14:00:50');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `transaction_code` varchar(50) NOT NULL,
  `kasir_id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `discount_type` enum('percentage','nominal') DEFAULT NULL,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','debit','credit','ewallet') DEFAULT 'cash',
  `payment_amount` decimal(12,2) NOT NULL,
  `change_amount` decimal(12,2) DEFAULT 0.00,
  `loyalty_points_earned` int(11) DEFAULT 0,
  `loyalty_points_used` int(11) DEFAULT 0,
  `status` enum('completed','cancelled') DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_code`, `kasir_id`, `customer_name`, `customer_id`, `subtotal`, `discount_amount`, `discount_type`, `tax_amount`, `grand_total`, `payment_method`, `payment_amount`, `change_amount`, `loyalty_points_earned`, `loyalty_points_used`, `status`, `notes`, `transaction_date`, `created_at`, `updated_at`) VALUES
(1, 'TRX-20240115-0001', 2, 'Walk-in Customer', NULL, 45000.00, 0.00, NULL, 4500.00, 49500.00, 'cash', 50000.00, 500.00, 0, 0, 'completed', NULL, '2025-11-29', '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(2, 'TRX-20240115-0002', 2, 'Budi Santoso', NULL, 180000.00, 5000.00, NULL, 17500.00, 192500.00, 'debit', 200000.00, 7500.00, 0, 0, 'completed', NULL, '2025-11-29', '2025-11-29 14:00:50', '2025-11-29 14:00:50');

--
-- Triggers `transactions`
--
DELIMITER $$
CREATE TRIGGER `before_transaction_insert` BEFORE INSERT ON `transactions` FOR EACH ROW BEGIN
    IF NEW.transaction_code IS NULL THEN
        CALL GenerateTransactionCode(@new_transaction_code);
        SET NEW.transaction_code = @new_transaction_code;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `transaction_details`
--

CREATE TABLE `transaction_details` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_details`
--

INSERT INTO `transaction_details` (`id`, `transaction_id`, `product_id`, `product_name`, `quantity`, `price`, `subtotal`, `created_at`) VALUES
(1, 1, 2, 'Pisang Ambon', 3, 15000.00, 45000.00, '2025-11-29 14:00:50'),
(2, 2, 4, 'Daging Ayam Fillet', 2, 45000.00, 90000.00, '2025-11-29 14:00:50'),
(3, 2, 5, 'Ikan Salmon Fillet', 1, 180000.00, 90000.00, '2025-11-29 14:00:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','manager','kasir','customer') DEFAULT 'customer',
  `foto_profil` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `role`, `foto_profil`, `status`, `email_verified_at`, `remember_token`, `last_login_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'admin', 'admin@freshmart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', NULL, 'admin', NULL, 'active', NULL, NULL, NULL, '2025-11-29 14:00:50', '2025-11-29 14:00:50', NULL),
(2, 'kasir1', 'kasir@freshmart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Utama', NULL, 'kasir', NULL, 'active', NULL, NULL, NULL, '2025-11-29 14:00:50', '2025-11-29 14:00:50', NULL),
(3, 'manager1', 'manager@freshmart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager Toko', NULL, 'manager', NULL, 'active', NULL, NULL, NULL, '2025-11-29 14:00:50', '2025-11-29 14:00:50', NULL),
(4, 'customer1', 'customer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Customer', NULL, 'customer', NULL, 'active', NULL, NULL, NULL, '2025-11-29 14:00:50', '2025-11-29 14:00:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `nama_voucher` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(12,2) NOT NULL,
  `min_purchase` decimal(12,2) DEFAULT 0.00,
  `max_discount` decimal(12,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_per_customer` int(11) DEFAULT 1,
  `usage_count` int(11) DEFAULT 0,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `applicable_to` enum('all','categories','products') DEFAULT 'all',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `code`, `nama_voucher`, `description`, `discount_type`, `discount_value`, `min_purchase`, `max_discount`, `usage_limit`, `usage_per_customer`, `usage_count`, `start_date`, `end_date`, `is_active`, `applicable_to`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', 'Welcome Discount 10%', 'Diskon 10% untuk pembelian pertama', 'percentage', 10.00, 100000.00, 50000.00, 1000, 1, 0, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 1, 'all', '2025-11-29 14:00:50', '2025-11-29 14:00:50'),
(2, 'FREESHIP', 'Free Shipping', 'Gratis ongkos kirim tanpa minimum', 'fixed', 15000.00, 0.00, 15000.00, 500, 1, 0, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 1, 'all', '2025-11-29 14:00:50', '2025-11-29 14:00:50');

-- --------------------------------------------------------

--
-- Table structure for table `voucher_usage`
--

CREATE TABLE `voucher_usage` (
  `id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_amount` decimal(12,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `customer_analytics`
--
DROP TABLE IF EXISTS `customer_analytics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `customer_analytics`  AS SELECT `c`.`id` AS `id`, `u`.`full_name` AS `full_name`, `u`.`email` AS `email`, `c`.`customer_tier` AS `customer_tier`, `c`.`total_spending` AS `total_spending`, `c`.`loyalty_points` AS `loyalty_points`, count(`o`.`id`) AS `total_orders`, max(`o`.`created_at`) AS `last_order_date` FROM ((`customers` `c` left join `users` `u` on(`c`.`user_id` = `u`.`id`)) left join `orders` `o` on(`c`.`id` = `o`.`customer_id`)) GROUP BY `c`.`id`, `u`.`full_name`, `u`.`email`, `c`.`customer_tier`, `c`.`total_spending`, `c`.`loyalty_points` ;

-- --------------------------------------------------------

--
-- Structure for view `daily_sales_summary`
--
DROP TABLE IF EXISTS `daily_sales_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `daily_sales_summary`  AS SELECT cast(`orders`.`created_at` as date) AS `sale_date`, count(0) AS `total_orders`, sum(`orders`.`grand_total`) AS `total_revenue`, avg(`orders`.`grand_total`) AS `avg_order_value` FROM `orders` WHERE `orders`.`order_status` = 'delivered' GROUP BY cast(`orders`.`created_at` as date) ;

-- --------------------------------------------------------

--
-- Structure for view `inventory_alert`
--
DROP TABLE IF EXISTS `inventory_alert`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `inventory_alert`  AS SELECT `p`.`id` AS `id`, `p`.`nama_produk` AS `nama_produk`, `p`.`sku` AS `sku`, `p`.`stok` AS `stok`, `p`.`minimum_stok` AS `minimum_stok`, `c`.`nama_kategori` AS `nama_kategori`, CASE WHEN `p`.`stok` = 0 THEN 'out_of_stock' WHEN `p`.`stok` <= `p`.`minimum_stok` THEN 'low_stock' ELSE 'adequate' END AS `stock_status` FROM (`products` `p` left join `categories` `c` on(`p`.`category_id` = `c`.`id`)) WHERE `p`.`status` = 'active' AND (`p`.`stok` = 0 OR `p`.`stok` <= `p`.`minimum_stok`) ;

-- --------------------------------------------------------

--
-- Structure for view `product_performance`
--
DROP TABLE IF EXISTS `product_performance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `product_performance`  AS SELECT `p`.`id` AS `id`, `p`.`nama_produk` AS `nama_produk`, `p`.`sku` AS `sku`, `c`.`nama_kategori` AS `nama_kategori`, `p`.`harga_jual` AS `harga_jual`, `p`.`stok` AS `stok`, `p`.`sales_count` AS `sales_count`, `p`.`rating_average` AS `rating_average`, `p`.`views` AS `views`, CASE WHEN `p`.`views` = 0 THEN 0 ELSE round(`p`.`sales_count` / `p`.`views` * 100,2) END AS `conversion_rate` FROM (`products` `p` left join `categories` `c` on(`p`.`category_id` = `c`.`id`)) WHERE `p`.`status` = 'active' ;

-- --------------------------------------------------------

--
-- Structure for view `sales_report`
--
DROP TABLE IF EXISTS `sales_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `sales_report`  AS SELECT cast(`o`.`created_at` as date) AS `sale_date`, count(`o`.`id`) AS `total_orders`, sum(`o`.`grand_total`) AS `total_revenue`, avg(`o`.`grand_total`) AS `average_order_value`, count(distinct `o`.`customer_id`) AS `unique_customers` FROM `orders` AS `o` WHERE `o`.`order_status` = 'delivered' GROUP BY cast(`o`.`created_at` as date) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_model` (`model_type`,`model_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_default` (`is_default`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cart` (`cart_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `referral_code` (`referral_code`),
  ADD KEY `referred_by` (`referred_by`),
  ADD KEY `idx_tier` (`customer_tier`),
  ADD KEY `idx_referral` (`referral_code`);

--
-- Indexes for table `employees_shifts`
--
ALTER TABLE `employees_shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_date` (`shift_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_read` (`read_at`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `address_id` (`address_id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_order_status` (`order_status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_supplier` (`supplier_id`),
  ADD KEY `idx_sku` (`sku`),
  ADD KEY `idx_barcode` (`barcode`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_featured` (`is_featured`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_online` (`is_available_online`),
  ADD KEY `idx_stok` (`stok`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_category` (`product_id`,`category_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_category` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_primary` (`is_primary`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_approved` (`is_approved`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_dates` (`start_date`,`end_date`),
  ADD KEY `idx_type` (`promo_type`);

--
-- Indexes for table `promotion_products`
--
ALTER TABLE `promotion_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_promotion_product` (`promotion_id`,`product_id`),
  ADD KEY `idx_promotion` (`promotion_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_supplier` (`supplier_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_po_number` (`po_number`);

--
-- Indexes for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_po` (`purchase_order_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`movement_type`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_supplier` (`kode_supplier`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_kode` (`kode_supplier`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`),
  ADD KEY `idx_kasir` (`kasir_id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_transaction_code` (`transaction_code`),
  ADD KEY `idx_date` (`transaction_date`);

--
-- Indexes for table `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaction` (`transaction_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indexes for table `voucher_usage`
--
ALTER TABLE `voucher_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_voucher` (`voucher_id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_order` (`order_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `employees_shifts`
--
ALTER TABLE `employees_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotion_products`
--
ALTER TABLE `promotion_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transaction_details`
--
ALTER TABLE `transaction_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `voucher_usage`
--
ALTER TABLE `voucher_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customers_ibfk_2` FOREIGN KEY (`referred_by`) REFERENCES `customers` (`id`);

--
-- Constraints for table `employees_shifts`
--
ALTER TABLE `employees_shifts`
  ADD CONSTRAINT `employees_shifts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`);

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `promotion_products`
--
ALTER TABLE `promotion_products`
  ADD CONSTRAINT `promotion_products_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  ADD CONSTRAINT `purchase_order_details_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`kasir_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD CONSTRAINT `transaction_details_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `voucher_usage`
--
ALTER TABLE `voucher_usage`
  ADD CONSTRAINT `voucher_usage_ibfk_1` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`),
  ADD CONSTRAINT `voucher_usage_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `voucher_usage_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;