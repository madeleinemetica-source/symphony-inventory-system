-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 07, 2025 at 03:48 PM
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
-- Database: `inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `brand_id` int(11) NOT NULL,
  `brand_name` varchar(100) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `brand_logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`brand_id`, `brand_name`, `supplier_id`, `description`, `created_at`, `status`, `brand_logo`) VALUES
(2, 'Coca-Cola', 102, 'International beverage company', '2025-11-19 21:50:42', 'active', NULL),
(3, 'Nescafe', 102, 'Coffee and beverage brand', '2025-11-19 21:50:42', 'active', NULL),
(4, 'Zesto', 102, 'Juice and drink brand', '2025-11-19 21:50:42', 'active', NULL),
(7, 'Doublemint', 103, 'Chewing gum brand', '2025-11-19 21:50:42', 'active', NULL),
(8, 'Fisherman s Friend', 103, 'Strong mint lozenges', '2025-11-19 21:50:42', 'active', NULL),
(9, 'Jack n Jill', 103, 'Snack food company', '2025-11-19 21:50:42', 'active', NULL),
(10, 'Rebisco', 103, 'Biscuit and snack manufacturer', '2025-11-19 21:50:42', 'active', NULL),
(11, 'Oreo', 103, 'Cookie brand', '2025-11-19 21:50:42', 'active', NULL),
(12, 'Lucky Me!', 107, 'Instant noodle brand', '2025-11-19 21:50:42', 'active', NULL),
(13, 'Purefoods', 107, 'Food manufacturing company', '2025-11-19 21:50:42', 'active', NULL),
(14, 'Selecta', 104, 'Ice cream and dairy products', '2025-11-19 21:50:42', 'active', NULL),
(15, 'Nestle', 104, 'Dairy and food products', '2025-11-19 21:50:42', 'active', NULL),
(16, 'Kleenex', 105, 'Tissue and paper products', '2025-11-19 21:50:42', 'active', NULL),
(17, 'Modess', 105, 'Feminine care products', '2025-11-19 21:50:42', 'active', NULL),
(18, 'Sisters', 105, 'Feminine hygiene brand', '2025-11-19 21:50:42', 'active', NULL),
(20, 'Gardenia', 106, 'Bread and bakery products', '2025-11-19 21:50:42', 'active', NULL),
(22, 'Pan de Manila', 106, 'Traditional Filipino bakery', '2025-11-19 21:50:42', 'active', NULL),
(27, 'Biogenic', 105, 'Biogenic Alcohol and sanitizers', '2025-12-02 11:15:21', 'active', 'uploads/brands/brands_1764645321_692e59c96228b.png'),
(28, 'Nature Spring', 108, 'Water beverages products from Nature Spring', '2025-12-02 12:03:22', 'active', 'uploads/brands/brands_1764648202_692e650ac7238.jpg'),
(30, 'Bear Brand', 104, 'milk, powdered milk, and other dairy products by Nestle Bear Brand', '2025-12-07 22:42:11', 'active', 'uploads/brands/brands_1765118531_69359243cfecb.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `created_at`, `parent_id`) VALUES
(1, 'Non-alcoholic Beverages', 'Various non-alcoholic drinks', '2025-11-19 21:37:06', NULL),
(3, 'Packaged Snacks', 'Ready-to-eat snack items', '2025-11-19 21:37:06', NULL),
(4, 'Hot Food Items', 'Prepared hot meals', '2025-11-19 21:37:06', NULL),
(5, 'Dairy Items', 'Dairy and related products', '2025-11-19 21:37:06', NULL),
(6, 'Hygiene Products', 'Personal care and hygiene items', '2025-11-19 21:37:06', NULL),
(7, 'Bread Product', 'Baked bread, pastries, and loaves', '2025-11-19 21:37:06', NULL),
(8, 'Water', 'Bottled and purified water', '2025-11-19 21:41:59', 1),
(10, 'Coffee', 'Ready-to-drink coffee', '2025-11-19 21:41:59', 1),
(11, 'Juice', 'Fruit juices and drinks', '2025-11-19 21:41:59', 1),
(12, 'Milk Drinks', 'Flavored milk and dairy drinks', '2025-11-19 21:41:59', 1),
(16, 'Chips', 'Potato chips and crisps', '2025-11-19 21:41:59', 3),
(17, 'Cookies', 'Biscuits and cookies', '2025-11-19 21:41:59', 3),
(18, 'Soft Snacks', 'Cakes and soft baked snacks', '2025-11-19 21:41:59', 3),
(19, 'Noodles', 'Instant and cup noodles', '2025-11-19 21:41:59', 4),
(20, 'Meals', 'Ready-to-eat meals', '2025-11-19 21:41:59', 4),
(21, 'Ice Cream', 'Ice cream and frozen desserts', '2025-11-19 21:41:59', 5),
(22, 'Yoghurt', 'Yogurt drinks and cups', '2025-11-19 21:41:59', 5),
(23, 'Milk', 'Fresh and UHT milk', '2025-11-19 21:41:59', 5),
(24, 'Tissue and Wipes', 'Facial tissue and wet wipes', '2025-11-19 21:41:59', 6),
(25, 'Napkins', 'Feminine hygiene products', '2025-11-19 21:41:59', 6),
(26, 'Alcohol and Sanitizers', 'Hand sanitizers and rubbing alcohol', '2025-11-19 21:41:59', 6),
(27, 'Loaf bread', 'Sliced loaf bread', '2025-11-19 21:41:59', 7),
(28, 'Pastries', 'Baked pastries and cakes', '2025-11-19 21:41:59', 7),
(29, 'Pandesal', 'Traditional Filipino bread rolls', '2025-11-19 21:41:59', 7),
(39, 'Carbonated Drinks', 'Soft drinks and sodas', '2025-12-02 11:25:09', 1);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` text NOT NULL,
  `product_image` varchar(255) DEFAULT NULL COMMENT 'Stores product image file paths or URLs',
  `category_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `quantity_stock` int(11) NOT NULL CHECK (`quantity_stock` >= 0),
  `cost_price` decimal(10,2) NOT NULL CHECK (`cost_price` >= 0),
  `selling_price` decimal(10,2) NOT NULL CHECK (`selling_price` >= 0),
  `expiration_date` date DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `product_image`, `category_id`, `brand_id`, `sku`, `unit`, `quantity_stock`, `cost_price`, `selling_price`, `expiration_date`, `supplier_id`, `created_at`, `updated_at`, `status`) VALUES
(5, 'Nescafe Original 200g', NULL, 10, 3, 'NESCAF200', 'jar', 15, 120.00, 180.00, '2025-01-01', 102, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(6, 'Nescafe 3in1 10s', NULL, 10, 3, 'NESCAF3IN1', 'pack', 8, 45.00, 70.00, '2024-11-01', 102, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(7, 'Zesto Orange 1L', NULL, 11, 4, 'ZESTOOR1', 'bottle', 22, 40.00, 65.00, '2024-09-01', 102, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(8, 'Zesto Mango 250ml', NULL, 11, 4, 'ZESTOM250', 'tetra', 0, 15.00, 25.00, '2024-09-01', 102, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(14, 'Chippy BBQ 27g', NULL, 16, 9, 'CHIPPY27', 'pack', 38, 8.00, 15.00, '2024-10-01', 103, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(16, 'Rebisco Sandwich 10pcs', NULL, 17, 10, 'REBISCO10', 'pack', 25, 35.00, 55.00, '2024-11-01', 103, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(17, 'Oreo Original 137g', NULL, 17, 11, 'OREO137', 'pack', 0, 40.00, 65.00, '2024-11-01', 103, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(18, 'Hansel Mocha 200g', NULL, 18, 10, 'HANSEL200', 'pack', 20, 45.00, 75.00, '2024-10-01', 103, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(19, 'Lucky Me Pancit Canton 40g', NULL, 19, 12, 'LUCKYPC40', 'pack', 50, 10.00, 18.00, '2024-09-01', 107, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(20, 'Lucky Me Beef 55g', NULL, 19, 12, 'LUCKYB55', 'pack', 8, 12.00, 20.00, '2024-09-01', 107, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(21, 'Purefoods Corned Beef 150g', NULL, 20, 13, 'PURE150', 'can', 15, 45.00, 75.00, '2024-12-01', 107, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(22, 'Selecta Cookies & Cream 1L', NULL, 21, 14, 'SELCC1L', 'tub', 10, 120.00, 180.00, '2024-07-01', 104, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(23, 'Selecta Rocky Road 500ml', NULL, 21, 14, 'SELRR500', 'tub', 25, 80.00, 120.00, '2024-07-01', 104, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(24, 'Nestle Yogurt Strawberry 100g', NULL, 22, 15, 'NESTYOG100', 'cup', 18, 25.00, 40.00, '2024-08-01', 104, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(25, 'Nestle Fresh Milk 1L', NULL, 23, 15, 'NESTMILK1', 'carton', 22, 60.00, 95.00, '2024-08-01', 104, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(26, 'Kleenex Facial Tissue 100s', NULL, 24, 16, 'KLEENEX100', 'box', 35, 40.00, 65.00, '2026-01-01', 105, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(27, 'Modess Cottony 8s', NULL, 25, 17, 'MODESS8', 'pack', 28, 45.00, 75.00, '2025-12-01', 105, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(28, 'Sisters Ultra Thin 10s', NULL, 25, 18, 'SISTERS10', 'pack', 15, 50.00, 85.00, '2025-12-01', 105, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(30, 'Gardenia White Bread 600g', NULL, 27, 20, 'GARD600', 'loaf', 20, 55.00, 85.00, '2024-06-15', 106, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(31, 'Gardenia Wheat Bread 450g', NULL, 27, 20, 'GARD450', 'loaf', 0, 50.00, 80.00, '2024-06-15', 106, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(33, 'Pan de Manila Pandesal 6s', NULL, 29, 22, 'PANDE6', 'pack', 40, 25.00, 40.00, '2024-06-08', 106, '2025-11-19 13:56:58', '2025-11-19 13:56:58', 'active'),
(34, 'Kleenex Facial Tissue Unscented', NULL, 26, 16, 'KLFT100', 'pack', 100, 30.00, 45.00, '2026-08-29', 105, '2025-11-24 09:18:48', '2025-11-24 09:18:48', 'active'),
(36, 'Pampanga Bakery Pandesal 12 pcs', 'uploads/products/product_1764001868_6924884c7b31f.jpg', 29, 22, 'PAMPAN12', 'pack', 25, 500.00, 450.00, '2026-12-05', 107, '2025-11-24 16:31:08', '2025-11-24 16:31:36', 'active'),
(41, 'Biogenic Alcohol 500g', 'uploads/products/product_1764645562_692e5ababc077.jpg', 26, 27, 'BIO500', 'bottle', 55, 75.00, 70.00, '2026-12-20', 105, '2025-12-02 03:19:22', '2025-12-02 03:19:22', 'active'),
(42, 'Coke regular 355 ml', 'uploads/products/product_1764646129_692e5cf1d1648.jpg', 39, 2, 'COKE355', 'cans', 54, 52.00, 56.00, '2026-12-20', 102, '2025-12-02 03:28:49', '2025-12-02 03:28:49', 'active'),
(43, 'Nature Spring 1 Liter', 'uploads/products/product_1764648485_692e6625aebf1.webp', 8, 28, 'NATSPRI1', 'bottle', 58, 42.00, 45.00, '2026-12-05', 108, '2025-12-02 04:08:05', '2025-12-02 04:08:05', 'active'),
(44, 'Nature Spring 500 ml', 'uploads/products/product_1764648587_692e668bae62b.png', 8, 28, 'NATSPRI500', 'bottle', 43, 29.00, 32.00, '2026-12-10', 102, '2025-12-02 04:09:47', '2025-12-02 04:09:47', 'active'),
(45, 'Bear Brand 180g', 'uploads/products/product_1765118629_693592a55e2ce.jpg', 12, 30, 'BB180', 'sachet', 53, 75.00, 71.00, '2027-02-20', 104, '2025-12-07 14:43:49', '2025-12-07 14:43:49', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `phone`, `email`, `address`, `status`, `created_at`) VALUES
(102, 'Cebu Beverage Corp', 'Maria Santos', '+63 917 654 3210', 'maria@cebubeverage.com', '456 Cebu Business Park, Cebu City', 'active', '2025-11-19 13:34:19'),
(103, 'Davao Snack Suppliers', 'Pedro Reyes', '+63 918 777 8888', 'pedro@davaosnacks.com', '789 Davao Coastal Rd, Davao City', 'active', '2025-11-19 13:34:19'),
(104, 'Ilocos Dairy Products', 'Ana Gonzales', '+63 919 555 4444', 'ana@ilocosdairy.com', '321 Laoag City, Ilocos Norte', 'active', '2025-11-19 13:34:19'),
(105, 'Metro Hygiene Supplies', 'Carlos Lim', '+63 920 333 2222', 'carlos@metrohygiene.com', '654 Makati City, Metro Manila', 'active', '2025-11-19 13:34:19'),
(106, 'Batangas Bakery Co.', 'Sofia deLeon', '+63 921 111 0210', 'sofia@batangasbakery.com', '987 Bakery Street Batangas City', 'active', '2025-11-19 13:34:19'),
(107, 'Pampanga Food Inc', 'Miguel Tan', '+63 922 666 9999', 'miguel@pampangafood.com', '147 Angeles City, Pampanga', 'active', '2025-11-19 13:34:19'),
(108, 'Manila Water Distribution', 'Bernardo Carlos', '+63 962 311 0930', 'carlost@gmail.com', '217 Manila Street Bagong Barrio City', 'active', '2025-12-02 04:01:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `profile_picture`, `created_at`, `last_login`) VALUES
(1, 'Charlotte Green Olives', 'charlotte@gmail.com', '$2y$10$fukNFW0F8DS1GRCRbW4gjOpudMPaQFn3PaQTvwQzKosVNA32yAjFK', 'uploads/profiles/profile_1_1765115333.webp', '2025-11-12 12:40:56', '2025-12-07 21:06:53');

-- --------------------------------------------------------

--
-- Table structure for table `user_activities`
--

CREATE TABLE `user_activities` (
  `activity_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(50) DEFAULT NULL,
  `activity_description` text DEFAULT NULL,
  `activity_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activities`
--

INSERT INTO `user_activities` (`activity_id`, `user_id`, `activity_type`, `activity_description`, `activity_date`) VALUES
(1, 1, 'product_update', 'Updated product: Bear Brand Adult Plus Choco 180g (ID 39)', '2025-12-01 14:42:07'),
(2, 1, 'product_add', 'Added product: Doublemint Chewing Gum 5 Sticks (ID 41)', '2025-12-01 14:45:42'),
(3, 1, 'category_add', 'Added category: Candies', '2025-12-02 03:01:48'),
(4, 1, 'category_add', 'Added category: Popping Candies', '2025-12-02 03:02:02'),
(5, 1, 'category_add', 'Added category: Mint', '2025-12-02 03:02:24'),
(6, 1, 'product_add', 'Added product: Doublemint Chewing Gum 5 Sticks (ID 40)', '2025-12-02 03:04:17'),
(7, 1, 'product_add', 'Added product: Biogenic Alcohol 500g (ID 41)', '2025-12-02 03:19:22'),
(8, 1, 'category_update', 'Updated category: Bread Product (ID 7)', '2025-12-02 03:21:50'),
(9, 1, 'category_update', 'Updated category: Carbonated Drinks (ID 9)', '2025-12-02 03:23:04'),
(10, 1, 'category_add', 'Added category: Carbonated Drinks', '2025-12-02 03:25:09'),
(11, 1, 'product_add', 'Added product: Coke regular 355 ml (ID 42)', '2025-12-02 03:28:49'),
(12, 1, 'product_add', 'Added product: Nature Spring 1 Liter (ID 43)', '2025-12-02 04:08:05'),
(13, 1, 'product_add', 'Added product: Nature Spring 500 ml (ID 44)', '2025-12-02 04:09:47'),
(14, 1, 'product_delete', 'Deleted product: Bear Brand Adult Plus Choco 180g', '2025-12-07 13:31:42'),
(15, 1, 'brand_update', 'Updated brand: Bear Brand Nestle (ID 5)', '2025-12-07 13:35:03'),
(16, 1, 'brand_delete', 'Deleted brand: Unknown (with 1 product(s))', '2025-12-07 13:35:17'),
(17, 1, 'brand_add', 'Added brand: Bear Brand', '2025-12-07 13:35:57'),
(18, 1, 'category_add', 'Added category: Sample category', '2025-12-07 13:37:47'),
(19, 1, 'category_delete', 'Deleted main category: Unknown', '2025-12-07 13:37:54'),
(20, 1, 'category_add', 'Added category: Sample sub category', '2025-12-07 13:38:20'),
(21, 1, 'category_delete', 'Deleted sub-category: Unknown', '2025-12-07 13:38:37'),
(22, 1, 'inventory_print', 'Generated inventory report', '2025-12-07 13:46:40'),
(23, 1, 'user_profile_update', 'Updated profile (changed photo and details)', '2025-12-07 13:48:18'),
(24, 1, 'user_profile_update', 'Updated profile (changed photo and details)', '2025-12-07 13:48:53'),
(25, 1, 'brand_delete', 'Deleted brand: Bear Brand', '2025-12-07 14:06:09'),
(26, 1, 'category_add', 'Added category: Pastries products', '2025-12-07 14:07:08'),
(27, 1, 'category_add', 'Added category: Cakes and cupcakes', '2025-12-07 14:07:30'),
(28, 1, 'category_delete', 'Deleted sub-category: Cakes and cupcakes', '2025-12-07 14:07:52'),
(29, 1, 'category_delete', 'Deleted main category: Pastries products', '2025-12-07 14:07:57'),
(30, 1, 'supplier_add', 'Added supplier: Manila Water Distribution', '2025-12-07 14:14:53'),
(31, 1, 'supplier_update', 'Updated supplier: Manila Water Distribution Corp. (ID 109)', '2025-12-07 14:15:20'),
(32, 1, 'supplier_delete', 'Deleted supplier: Manila Water Distribution Corp.', '2025-12-07 14:15:26'),
(33, 1, 'supplier_delete', 'Deleted supplier: Unknown', '2025-12-07 14:15:39'),
(34, 1, 'supplier_add', 'Added supplier: Batangas Bakery Co.', '2025-12-07 14:28:10'),
(35, 1, 'supplier_delete', 'Deleted supplier: Batangas Bakery Co.', '2025-12-07 14:28:16'),
(36, 1, 'supplier_delete', 'Deleted supplier: Unknown', '2025-12-07 14:28:27'),
(37, 1, 'supplier_add', 'Added supplier: Sample Supplier', '2025-12-07 14:39:19'),
(38, 1, 'supplier_delete', 'Deleted supplier: Sample Supplier', '2025-12-07 14:39:30'),
(39, 1, 'inventory_print', 'Generated inventory report', '2025-12-07 14:40:49'),
(40, 1, 'brand_add', 'Added brand: Nestle Bear Brand', '2025-12-07 14:42:11'),
(41, 1, 'product_add', 'Added product: Bear Brand 180g (ID 45)', '2025-12-07 14:43:49'),
(42, 1, 'brand_update', 'Updated brand: Bear Brand (ID 30)', '2025-12-07 14:44:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`brand_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `product_name` (`product_name`) USING HASH,
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `brand_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `brands`
--
ALTER TABLE `brands`
  ADD CONSTRAINT `brands_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD CONSTRAINT `user_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
