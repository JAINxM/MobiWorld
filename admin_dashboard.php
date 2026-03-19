<?php
require_once __DIR__ . '/includes/config/db.php';
ensureSessionStarted();

if (!function_exists('isAdminLoggedIn')) {
    function isAdminLoggedIn(): bool {
        ensureSessionStarted();
        return isset($_SESSION['admin_logged_in']);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }
}

if (!isAdminLoggedIn()) {
    redirect('admin_login.php');
}

$validSections = ['dashboard', 'products', 'customers', 'orders', 'add-product', 'edit-product'];
$section = isset($_GET['section']) ? (string) $_GET['section'] : 'dashboard';
if (!in_array($section, $validSections, true)) {
    $section = 'dashboard';
}

$validFilters = ['7days', '30days', 'this_month', 'all'];
$filter = isset($_GET['filter']) ? (string) $_GET['filter'] : '7days';
if (!in_array($filter, $validFilters, true)) {
    $filter = '7days';
}

$rangeSql = '';
$rangeLabel = 'Last 7 Days';
switch ($filter) {
    case '30days':
        $rangeSql = ' WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
        $rangeLabel = 'Last 30 Days';
        break;
    case 'this_month':
        $rangeSql = ' WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())';
        $rangeLabel = 'This Month';
        break;
    case 'all':
        $rangeSql = '';
        $rangeLabel = 'All Time';
        break;
    default:
        $rangeSql = ' WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        $rangeLabel = 'Last 7 Days';
        break;
}

$message = '';
$error = '';
$allowedOrderStatuses = ['pending', 'confirmed', 'out_for_delivery', 'delivered', 'cancelled'];
$orderStatusLabels = [
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'out_for_delivery' => 'Out for Delivery',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];

try {
    $orderStatusColumn = $pdo->query("SHOW COLUMNS FROM orders LIKE 'order_status'")->fetch();
    if ($orderStatusColumn && isset($orderStatusColumn['Type'])) {
        $orderStatusType = (string) $orderStatusColumn['Type'];
        foreach ($allowedOrderStatuses as $statusValue) {
            if (strpos($orderStatusType, "'" . $statusValue . "'") === false) {
                $pdo->exec("ALTER TABLE orders MODIFY COLUMN order_status ENUM('pending','confirmed','out_for_delivery','delivered','cancelled') DEFAULT 'pending'");
                break;
            }
        }
    }
} catch (Throwable $e) {
}

if ($section === 'orders' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['update_order_status'])) {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $newStatus = (string) ($_POST['order_status'] ?? '');

    if ($orderId <= 0 || !in_array($newStatus, $allowedOrderStatuses, true)) {
        $error = 'Invalid order status update request.';
    } else {
        try {
            $statusStmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE order_id = ?');
            $statusStmt->execute([$newStatus, $orderId]);
            $message = 'Order #' . $orderId . ' updated to ' . ($orderStatusLabels[$newStatus] ?? ucfirst($newStatus)) . '.';
        } catch (Throwable $e) {
            $error = defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Failed to update order status.';
        }
    }
}

if ($section === 'add-product' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $brand = trim((string) ($_POST['brand'] ?? ''));
    $regularPrice = (float) ($_POST['regular_price'] ?? 0);
    $discountedRaw = trim((string) ($_POST['discounted_price'] ?? ''));
    $discountedPrice = $discountedRaw === '' ? null : (float) $discountedRaw;
    $stockQuantity = (int) ($_POST['stock_quantity'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($name === '' || $brand === '' || $regularPrice <= 0) {
        $error = 'Name, brand and regular price are required.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO product_master (name, brand, regular_price, discounted_price, stock_quantity, is_active, image_url, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$name, $brand, $regularPrice, $discountedPrice, $stockQuantity, $isActive, $imageUrl, $description]);
            $message = 'Product added successfully. Product ID: ' . $pdo->lastInsertId();
        } catch (Throwable $e) {
            $error = defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Failed to add product.';
        }
    }
}

if ($section === 'edit-product' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['update_product'])) {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $brand = trim((string) ($_POST['brand'] ?? ''));
    $regularPrice = (float) ($_POST['regular_price'] ?? 0);
    $discountedRaw = trim((string) ($_POST['discounted_price'] ?? ''));
    $discountedPrice = $discountedRaw === '' ? null : (float) $discountedRaw;
    $stockQuantity = max(0, (int) ($_POST['stock_quantity'] ?? 0));
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($productId <= 0 || $name === '' || $brand === '' || $regularPrice <= 0) {
        $error = 'Valid product, name, brand and regular price are required.';
    } elseif ($discountedPrice !== null && $discountedPrice < 0) {
        $error = 'Discount price cannot be negative.';
    } else {
        try {
            $updateStmt = $pdo->prepare('UPDATE product_master SET name = ?, brand = ?, regular_price = ?, discounted_price = ?, stock_quantity = ?, is_active = ?, image_url = ?, description = ? WHERE product_id = ?');
            $updateStmt->execute([$name, $brand, $regularPrice, $discountedPrice, $stockQuantity, $isActive, $imageUrl, $description, $productId]);
            $message = 'Product #' . $productId . ' updated successfully.';
        } catch (Throwable $e) {
            $error = defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Failed to update product.';
        }
    }
}

$registeredUsers = (int) ($pdo->query('SELECT COUNT(*) FROM user_master WHERE is_active = 1')->fetchColumn() ?: 0);
$totalOrders = (int) ($pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn() ?: 0);
$totalRevenue = (float) ($pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders')->fetchColumn() ?: 0);
$phonesSold = (int) ($pdo->query('SELECT COALESCE(SUM(quantity), 0) FROM order_items')->fetchColumn() ?: 0);

$filteredRevenueStmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount), 0) FROM orders' . $rangeSql);
$filteredRevenueStmt->execute();
$filteredRevenue = (float) ($filteredRevenueStmt->fetchColumn() ?: 0);

$filteredOrdersStmt = $pdo->prepare('SELECT COUNT(*) FROM orders' . $rangeSql);
$filteredOrdersStmt->execute();
$filteredOrders = (int) ($filteredOrdersStmt->fetchColumn() ?: 0);

$filteredPhonesStmt = $pdo->prepare('SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi INNER JOIN orders o ON o.order_id = oi.order_id' . str_replace('created_at', 'o.created_at', $rangeSql));
$filteredPhonesStmt->execute();
$filteredPhones = (int) ($filteredPhonesStmt->fetchColumn() ?: 0);

$chartStmt = $pdo->prepare('SELECT DATE(created_at) AS order_date, COALESCE(SUM(total_amount), 0) AS daily FROM orders' . $rangeSql . ' GROUP BY DATE(created_at) ORDER BY DATE(created_at)');
$chartStmt->execute();
$chartRows = $chartStmt->fetchAll();
$chartLabels = array_column($chartRows, 'order_date');
$chartValues = array_map('floatval', array_column($chartRows, 'daily'));

$recentOrdersStmt = $pdo->prepare('SELECT o.order_id, o.total_amount, o.order_status, o.created_at, u.full_name FROM orders o INNER JOIN user_master u ON u.user_id = o.user_id' . str_replace('created_at', 'o.created_at', $rangeSql) . ' ORDER BY o.created_at DESC LIMIT 6');
$recentOrdersStmt->execute();
$recentOrders = $recentOrdersStmt->fetchAll();

$allProducts = $pdo->query('SELECT product_id, name, brand, regular_price, discounted_price, stock_quantity, is_active, image_url, description, created_at FROM product_master ORDER BY created_at DESC')->fetchAll();
$customers = $pdo->query('SELECT u.user_id, u.full_name, u.email, u.mobile, u.is_active, u.created_at, COUNT(o.order_id) AS total_orders, COALESCE(SUM(o.total_amount), 0) AS total_spent FROM user_master u LEFT JOIN orders o ON o.user_id = u.user_id GROUP BY u.user_id, u.full_name, u.email, u.mobile, u.is_active, u.created_at ORDER BY u.created_at DESC')->fetchAll();
$ordersTableColumns = [];
try {
    $ordersColumnsStmt = $pdo->query('SHOW COLUMNS FROM orders');
    foreach ($ordersColumnsStmt->fetchAll() as $columnRow) {
        $ordersTableColumns[] = (string) $columnRow['Field'];
    }
} catch (Throwable $e) {
    $ordersTableColumns = [];
}

$hasPaymentMethod = in_array('payment_method', $ordersTableColumns, true);
$hasRecipientName = in_array('recipient_name', $ordersTableColumns, true);
$hasShippingAddress = in_array('shipping_address', $ordersTableColumns, true);

$ordersSelectParts = [
    'o.order_id',
    'o.total_amount',
    'o.order_status',
    'o.created_at',
    'u.full_name',
    'u.email',
    'COUNT(oi.order_item_id) AS items_count',
];

if ($hasPaymentMethod) {
    $ordersSelectParts[] = 'o.payment_method';
} else {
    $ordersSelectParts[] = "'N/A' AS payment_method";
}

if ($hasRecipientName) {
    $ordersSelectParts[] = 'o.recipient_name';
} else {
    $ordersSelectParts[] = "u.full_name AS recipient_name";
}

if ($hasShippingAddress) {
    $ordersSelectParts[] = 'o.shipping_address';
} else {
    $ordersSelectParts[] = "'' AS shipping_address";
}

$ordersGroupByParts = [
    'o.order_id',
    'o.total_amount',
    'o.order_status',
    'o.created_at',
    'u.full_name',
    'u.email',
];

if ($hasPaymentMethod) {
    $ordersGroupByParts[] = 'o.payment_method';
}
if ($hasRecipientName) {
    $ordersGroupByParts[] = 'o.recipient_name';
}
if ($hasShippingAddress) {
    $ordersGroupByParts[] = 'o.shipping_address';
}

$ordersQuery = 'SELECT ' . implode(', ', $ordersSelectParts) .
    ' FROM orders o INNER JOIN user_master u ON u.user_id = o.user_id LEFT JOIN order_items oi ON oi.order_id = o.order_id' .
    ' GROUP BY ' . implode(', ', $ordersGroupByParts) .
    ' ORDER BY o.created_at DESC';
$orders = $pdo->query($ordersQuery)->fetchAll();
$recentProducts = array_slice($allProducts, 0, 5);

function adminLink(string $section, string $filter): string
{
    return 'admin_dashboard.php?section=' . urlencode($section) . '&filter=' . urlencode($filter);
}

function orderStatusBadgeClass(string $status): string
{
    if ($status === 'delivered') {
        return 'bg-green-50 text-green-600';
    }
    if ($status === 'out_for_delivery') {
        return 'bg-blue-50 text-blue-600';
    }
    if ($status === 'confirmed') {
        return 'bg-indigo-50 text-indigo-600';
    }
    if ($status === 'cancelled') {
        return 'bg-red-50 text-red-600';
    }
    return 'bg-yellow-50 text-yellow-600';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | MobiWorld Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Outfit', sans-serif; background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 50%, #f8fafc 100%); }
        .sidebar-link.active { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #4338ca; box-shadow: 0 15px 35px rgba(79, 70, 229, 0.12); }
        .panel-card { background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(12px); }
    </style>
</head>
<body class="min-h-screen text-slate-900">
<div class="flex min-h-screen">
    <aside class="hidden h-screen w-72 border-r border-slate-200/80 bg-white/85 px-6 py-8 backdrop-blur-lg lg:sticky lg:top-0 lg:flex lg:flex-col">
        <div class="mb-10">
            <a href="admin_dashboard.php" class="text-4xl font-extrabold leading-tight bg-gradient-to-r from-indigo-500 to-violet-500 bg-clip-text text-transparent">MobiWorld<br>Admin</a>
        </div>
        <nav class="space-y-3">
            <a href="<?php echo htmlspecialchars(adminLink('dashboard', $filter), ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?php echo $section === 'dashboard' ? 'active' : 'text-slate-500 hover:bg-slate-50'; ?> flex items-center rounded-2xl px-5 py-4 font-bold transition"><i class="fas fa-house mr-4"></i> Dashboard</a>
            <a href="<?php echo htmlspecialchars(adminLink('products', $filter), ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?php echo $section === 'products' ? 'active' : 'text-slate-500 hover:bg-slate-50'; ?> flex items-center rounded-2xl px-5 py-4 font-bold transition"><i class="fas fa-mobile-alt mr-4"></i> Products</a>
            <a href="<?php echo htmlspecialchars(adminLink('customers', $filter), ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?php echo $section === 'customers' ? 'active' : 'text-slate-500 hover:bg-slate-50'; ?> flex items-center rounded-2xl px-5 py-4 font-bold transition"><i class="fas fa-users mr-4"></i> Customers</a>
            <a href="<?php echo htmlspecialchars(adminLink('orders', $filter), ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?php echo $section === 'orders' ? 'active' : 'text-slate-500 hover:bg-slate-50'; ?> flex items-center rounded-2xl px-5 py-4 font-bold transition"><i class="fas fa-bag-shopping mr-4"></i> Orders</a>
            <a href="<?php echo htmlspecialchars(adminLink('add-product', $filter), ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?php echo $section === 'add-product' ? 'active' : 'text-slate-500 hover:bg-slate-50'; ?> flex items-center rounded-2xl px-5 py-4 font-bold transition"><i class="fas fa-plus-circle mr-4"></i> Add Product</a>
            <a href="<?php echo htmlspecialchars(adminLink('edit-product', $filter), ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?php echo $section === 'edit-product' ? 'active' : 'text-slate-500 hover:bg-slate-50'; ?> flex items-center rounded-2xl px-5 py-4 font-bold transition"><i class="fas fa-pen-to-square mr-4"></i> Edit Product</a>
        </nav>
        <div class="mt-auto pt-8">
            <a href="logout.php" class="flex items-center rounded-2xl border border-red-100 bg-red-50/70 px-5 py-4 font-bold text-red-500 transition hover:bg-red-100"><i class="fas fa-power-off mr-4"></i> Logout</a>
        </div>
    </aside>

    <main class="flex-1 p-4 md:p-8 xl:p-10">
        <header class="mb-10 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-4xl font-extrabold tracking-tight text-slate-800">
                    <?php if ($section === 'dashboard'): ?>System <span class="text-indigo-600">Overview</span>
                    <?php elseif ($section === 'products'): ?>All <span class="text-indigo-600">Products</span>
                    <?php elseif ($section === 'customers'): ?>Customer <span class="text-indigo-600">Directory</span>
                    <?php elseif ($section === 'orders'): ?>Order <span class="text-indigo-600">Management</span>
                    <?php elseif ($section === 'add-product'): ?>Add New <span class="text-indigo-600">Product</span>
                    <?php else: ?>Edit Existing <span class="text-indigo-600">Products</span><?php endif; ?>
                </h1>
                <p class="mt-2 text-lg text-slate-500">
                    <?php if ($section === 'dashboard'): ?>Welcome back, Super Admin
                    <?php elseif ($section === 'products'): ?>Browse every product card currently in the store
                    <?php elseif ($section === 'customers'): ?>See registered customers and their order activity
                    <?php elseif ($section === 'orders'): ?>Review all orders placed on the store
                    <?php elseif ($section === 'add-product'): ?>Use the admin panel form to publish a new product instantly
                    <?php else: ?>Open any product below, update its details, and save changes directly to the database<?php endif; ?>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <form method="GET" action="admin_dashboard.php" class="panel-card flex items-center gap-3 rounded-2xl border border-slate-200/80 px-4 py-3 shadow-sm">
                    <input type="hidden" name="section" value="<?php echo htmlspecialchars($section, ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="far fa-calendar-alt text-indigo-500"></i>
                    <select name="filter" onchange="this.form.submit()" class="bg-transparent text-sm font-bold text-slate-600 focus:outline-none">
                        <option value="7days" <?php echo $filter === '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="30days" <?php echo $filter === '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="this_month" <?php echo $filter === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                    </select>
                </form>
                <div class="panel-card flex items-center rounded-full border border-slate-200/80 px-4 py-3 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 text-lg font-bold text-white shadow-lg">AD</div>
                </div>
            </div>
        </header>

        <?php if ($message !== ''): ?>
            <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="mb-10 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="panel-card rounded-[2rem] border border-slate-100 p-8 shadow-sm"><div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600"><i class="fas fa-dollar-sign text-xl"></i></div><div class="text-4xl font-extrabold text-slate-800">$<?php echo number_format($section === 'dashboard' ? $filteredRevenue : $totalRevenue, 2); ?></div><p class="mt-3 text-xs font-bold uppercase tracking-[0.25em] text-slate-400"><?php echo $section === 'dashboard' ? $rangeLabel . ' Revenue' : 'Total Revenue'; ?></p></div>
            <div class="panel-card rounded-[2rem] border border-slate-100 p-8 shadow-sm"><div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600"><i class="fas fa-mobile-alt text-xl"></i></div><div class="text-4xl font-extrabold text-slate-800"><?php echo number_format($section === 'dashboard' ? $filteredPhones : $phonesSold); ?></div><p class="mt-3 text-xs font-bold uppercase tracking-[0.25em] text-slate-400"><?php echo $section === 'dashboard' ? $rangeLabel . ' Phones Sold' : 'Phones Sold'; ?></p></div>
            <div class="panel-card rounded-[2rem] border border-slate-100 p-8 shadow-sm"><div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-purple-50 text-purple-600"><i class="fas fa-user-check text-xl"></i></div><div class="text-4xl font-extrabold text-slate-800"><?php echo number_format($registeredUsers); ?></div><p class="mt-3 text-xs font-bold uppercase tracking-[0.25em] text-slate-400">Active Users</p></div>
            <div class="panel-card rounded-[2rem] border border-slate-100 p-8 shadow-sm"><div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-green-50 text-green-600"><i class="fas fa-shopping-basket text-xl"></i></div><div class="text-4xl font-extrabold text-slate-800"><?php echo number_format($section === 'dashboard' ? $filteredOrders : $totalOrders); ?></div><p class="mt-3 text-xs font-bold uppercase tracking-[0.25em] text-slate-400"><?php echo $section === 'dashboard' ? $rangeLabel . ' Orders' : 'Total Orders'; ?></p></div>
        </section>

        <?php if ($section === 'dashboard'): ?>
            <section class="grid grid-cols-1 gap-8 xl:grid-cols-3">
                <div class="panel-card rounded-[2.25rem] border border-slate-100 p-8 shadow-sm xl:col-span-1">
                    <div class="mb-8 flex items-center justify-between"><h2 class="text-3xl font-bold text-slate-800">Sales Velocity</h2><span class="rounded-full bg-indigo-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.25em] text-indigo-600"><?php echo htmlspecialchars($rangeLabel, ENT_QUOTES, 'UTF-8'); ?></span></div>
                    <canvas id="salesChart" height="240"></canvas>
                </div>
                <div class="panel-card rounded-[2.25rem] border border-slate-100 p-8 shadow-sm xl:col-span-2">
                    <div class="mb-8 flex items-center justify-between"><h2 class="text-3xl font-bold text-slate-800">Recent Orders</h2><a href="<?php echo htmlspecialchars(adminLink('orders', $filter), ENT_QUOTES, 'UTF-8'); ?>" class="text-sm font-bold text-indigo-600 hover:underline">View All Orders</a></div>
                    <?php if (empty($recentOrders)): ?>
                        <div class="rounded-[2rem] bg-slate-50 px-6 py-16 text-center text-slate-400"><i class="fas fa-inbox mb-4 text-5xl opacity-30"></i><p class="text-lg font-semibold">No orders found for this filter.</p></div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="flex flex-col gap-4 rounded-[1.5rem] border border-slate-100 bg-slate-50/70 px-5 py-5 transition hover:border-slate-200 hover:bg-white md:flex-row md:items-center md:justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 font-bold text-indigo-600">#<?php echo (int) $order['order_id']; ?></div>
                                        <div><p class="text-lg font-bold text-slate-800"><?php echo htmlspecialchars((string) $order['full_name'], ENT_QUOTES, 'UTF-8'); ?></p><p class="text-sm text-slate-400"><?php echo date('M d, Y H:i', strtotime((string) $order['created_at'])); ?></p></div>
                                    </div>
                                    <div class="text-left md:text-right"><p class="text-2xl font-extrabold text-slate-800">$<?php echo number_format((float) $order['total_amount'], 2); ?></p><span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?php echo orderStatusBadgeClass((string) $order['order_status']); ?>"><?php echo htmlspecialchars($orderStatusLabels[(string) $order['order_status']] ?? ucwords(str_replace('_', ' ', (string) $order['order_status'])), ENT_QUOTES, 'UTF-8'); ?></span></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php elseif ($section === 'products'): ?>
            <section class="panel-card rounded-[2.25rem] border border-slate-100 p-8 shadow-sm">
                <div class="mb-8 flex items-center justify-between"><h2 class="text-3xl font-bold text-slate-800">All Products</h2><a href="<?php echo htmlspecialchars(adminLink('add-product', $filter), ENT_QUOTES, 'UTF-8'); ?>" class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-500">Add New Product</a></div>
                <?php if (empty($allProducts)): ?>
                    <p class="rounded-[1.5rem] bg-slate-50 px-6 py-12 text-center text-slate-500">No products found.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <?php foreach ($allProducts as $product): ?>
                            <?php $displayPrice = $product['discounted_price'] !== null && (float) $product['discounted_price'] > 0 ? (float) $product['discounted_price'] : (float) $product['regular_price']; ?>
                            <div class="rounded-[1.75rem] border border-slate-100 bg-slate-50/80 p-5 transition hover:border-slate-200 hover:bg-white hover:shadow-lg">
                                <div class="mb-4 flex h-48 items-center justify-center rounded-[1.5rem] bg-white p-4"><img src="<?php echo htmlspecialchars((string) ($product['image_url'] ?: 'https://via.placeholder.com/320x320?text=No+Image'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>" class="h-full w-full object-contain"></div>
                                <div class="mb-2 flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[0.25em] text-indigo-600"><?php echo htmlspecialchars((string) $product['brand'], ENT_QUOTES, 'UTF-8'); ?></p><h3 class="mt-2 text-xl font-bold text-slate-800"><?php echo htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?></h3></div><span class="rounded-full px-3 py-1 text-xs font-bold <?php echo (int) $product['is_active'] === 1 ? 'bg-green-50 text-green-600' : 'bg-slate-200 text-slate-500'; ?>"><?php echo (int) $product['is_active'] === 1 ? 'Active' : 'Hidden'; ?></span></div>
                                <p class="mb-4 min-h-[3rem] text-sm text-slate-500"><?php echo htmlspecialchars((string) ($product['description'] ?: 'No description available.'), ENT_QUOTES, 'UTF-8'); ?></p>
                                <div class="flex items-center justify-between"><div><p class="text-2xl font-extrabold text-slate-900">$<?php echo number_format($displayPrice, 2); ?></p><p class="text-sm text-slate-400">Stock: <?php echo (int) $product['stock_quantity']; ?></p></div><a href="product.php?id=<?php echo (int) $product['product_id']; ?>" class="rounded-2xl bg-white px-4 py-3 text-sm font-bold text-indigo-600 shadow-sm ring-1 ring-slate-200 transition hover:bg-indigo-50">Open Product</a></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php elseif ($section === 'customers'): ?>
            <section class="panel-card rounded-[2.25rem] border border-slate-100 p-8 shadow-sm">
                <h2 class="mb-8 text-3xl font-bold text-slate-800">Customers List</h2>
                <?php if (empty($customers)): ?>
                    <p class="rounded-[1.5rem] bg-slate-50 px-6 py-12 text-center text-slate-500">No customers found.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left">
                            <thead><tr class="border-b border-slate-100 text-xs font-bold uppercase tracking-[0.25em] text-slate-400"><th class="px-4 py-4">Customer</th><th class="px-4 py-4">Contact</th><th class="px-4 py-4">Orders</th><th class="px-4 py-4">Spent</th><th class="px-4 py-4">Status</th><th class="px-4 py-4">Joined</th></tr></thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                    <tr class="border-b border-slate-100 text-sm text-slate-600">
                                        <td class="px-4 py-5"><div class="font-bold text-slate-800"><?php echo htmlspecialchars((string) $customer['full_name'], ENT_QUOTES, 'UTF-8'); ?></div><div class="text-xs text-slate-400">#<?php echo (int) $customer['user_id']; ?></div></td>
                                        <td class="px-4 py-5"><div><?php echo htmlspecialchars((string) $customer['email'], ENT_QUOTES, 'UTF-8'); ?></div><div class="text-xs text-slate-400"><?php echo htmlspecialchars((string) ($customer['mobile'] ?: 'No mobile'), ENT_QUOTES, 'UTF-8'); ?></div></td>
                                        <td class="px-4 py-5 font-bold text-slate-800"><?php echo (int) $customer['total_orders']; ?></td>
                                        <td class="px-4 py-5 font-bold text-slate-800">$<?php echo number_format((float) $customer['total_spent'], 2); ?></td>
                                        <td class="px-4 py-5"><span class="rounded-full px-3 py-1 text-xs font-bold <?php echo (int) $customer['is_active'] === 1 ? 'bg-green-50 text-green-600' : 'bg-slate-200 text-slate-500'; ?>"><?php echo (int) $customer['is_active'] === 1 ? 'Active' : 'Inactive'; ?></span></td>
                                        <td class="px-4 py-5"><?php echo date('M d, Y', strtotime((string) $customer['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php elseif ($section === 'orders'): ?>
            <section class="panel-card rounded-[2.25rem] border border-slate-100 p-8 shadow-sm">
                <h2 class="mb-8 text-3xl font-bold text-slate-800">Orders List</h2>
                <?php if (empty($orders)): ?>
                    <p class="rounded-[1.5rem] bg-slate-50 px-6 py-12 text-center text-slate-500">No orders found.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($orders as $order): ?>
                            <div class="rounded-[1.75rem] border border-slate-100 bg-slate-50/70 p-5 transition hover:border-slate-200 hover:bg-white">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div><div class="mb-2 flex flex-wrap items-center gap-3"><span class="text-lg font-extrabold text-slate-800">Order #<?php echo (int) $order['order_id']; ?></span><span class="rounded-full px-3 py-1 text-xs font-bold <?php echo orderStatusBadgeClass((string) $order['order_status']); ?>"><?php echo htmlspecialchars($orderStatusLabels[(string) $order['order_status']] ?? ucwords(str_replace('_', ' ', (string) $order['order_status'])), ENT_QUOTES, 'UTF-8'); ?></span></div><p class="font-bold text-slate-700"><?php echo htmlspecialchars((string) $order['full_name'], ENT_QUOTES, 'UTF-8'); ?></p><p class="text-sm text-slate-400"><?php echo htmlspecialchars((string) $order['email'], ENT_QUOTES, 'UTF-8'); ?></p><p class="mt-3 text-sm text-slate-500">Recipient: <?php echo htmlspecialchars((string) ($order['recipient_name'] ?: 'Customer'), ENT_QUOTES, 'UTF-8'); ?></p><p class="text-sm text-slate-500">Payment: <?php echo htmlspecialchars((string) ($order['payment_method'] ?: 'N/A'), ENT_QUOTES, 'UTF-8'); ?></p><p class="mt-2 text-sm text-slate-500"><?php echo htmlspecialchars((string) ($order['shipping_address'] ?: 'No address provided'), ENT_QUOTES, 'UTF-8'); ?></p></div>
                                    <div class="text-left lg:text-right"><p class="text-2xl font-extrabold text-slate-900">$<?php echo number_format((float) $order['total_amount'], 2); ?></p><p class="text-sm text-slate-400"><?php echo (int) $order['items_count']; ?> item(s)</p><p class="mt-2 text-sm text-slate-400"><?php echo date('M d, Y H:i', strtotime((string) $order['created_at'])); ?></p>
                                        <form method="POST" action="<?php echo htmlspecialchars(adminLink('orders', $filter), ENT_QUOTES, 'UTF-8'); ?>" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                                            <input type="hidden" name="update_order_status" value="1">
                                            <input type="hidden" name="order_id" value="<?php echo (int) $order['order_id']; ?>">
                                            <select name="order_status" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 focus:border-indigo-500 focus:outline-none">
                                                <?php foreach ($allowedOrderStatuses as $statusOption): ?>
                                                    <option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $statusOption === (string) $order['order_status'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($orderStatusLabels[$statusOption] ?? $statusOption, ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-indigo-500">Update</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php elseif ($section === 'add-product'): ?>
            <section class="grid grid-cols-1 gap-8 xl:grid-cols-3">
                <div class="panel-card rounded-[2.25rem] border border-slate-100 p-8 shadow-sm xl:col-span-2">
                    <h2 class="mb-8 text-3xl font-bold text-slate-800">Product Form</h2>
                    <?php if ($message !== ''): ?><div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    <?php if ($error !== ''): ?><div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    <form method="POST" action="<?php echo htmlspecialchars(adminLink('add-product', $filter), ENT_QUOTES, 'UTF-8'); ?>" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div><label class="mb-2 block text-sm font-bold text-slate-700">Product Name</label><input type="text" name="name" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 focus:border-indigo-500 focus:outline-none"></div>
                        <div><label class="mb-2 block text-sm font-bold text-slate-700">Brand</label><input type="text" name="brand" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 focus:border-indigo-500 focus:outline-none" placeholder="Apple, Samsung..."></div>
                        <div><label class="mb-2 block text-sm font-bold text-slate-700">Regular Price</label><input type="number" step="0.01" name="regular_price" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 focus:border-indigo-500 focus:outline-none"></div>
                        <div><label class="mb-2 block text-sm font-bold text-slate-700">Discount Price</label><input type="number" step="0.01" name="discounted_price" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 focus:border-indigo-500 focus:outline-none"></div>
                        <div><label class="mb-2 block text-sm font-bold text-slate-700">Stock Quantity</label><input type="number" name="stock_quantity" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 focus:border-indigo-500 focus:outline-none"></div>
                        <div><label class="mb-2 block text-sm font-bold text-slate-700">Image URL</label><input type="url" name="image_url" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 focus:border-indigo-500 focus:outline-none"></div>
                        <div class="md:col-span-2"><label class="mb-2 block text-sm font-bold text-slate-700">Description</label><textarea name="description" rows="5" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 focus:border-indigo-500 focus:outline-none"></textarea></div>
                        <div class="md:col-span-2 flex items-center justify-between gap-4"><label class="flex items-center text-sm font-bold text-slate-700"><input type="checkbox" name="is_active" checked class="mr-3 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">Active Product</label><button type="submit" class="rounded-2xl bg-indigo-600 px-8 py-4 text-sm font-bold text-white shadow-lg shadow-indigo-200 transition hover:bg-indigo-500">Add Product</button></div>
                    </form>
                </div>
                <div class="panel-card rounded-[2.25rem] border border-slate-100 p-8 shadow-sm">
                    <h2 class="mb-8 text-3xl font-bold text-slate-800">Recent Products</h2>
                    <?php if (empty($recentProducts)): ?>
                        <p class="rounded-[1.5rem] bg-slate-50 px-5 py-10 text-center text-slate-500">No products yet.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recentProducts as $product): ?>
                                <div class="flex items-center gap-4 rounded-[1.5rem] border border-slate-100 bg-slate-50 p-4">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white p-2"><img src="<?php echo htmlspecialchars((string) ($product['image_url'] ?: 'https://via.placeholder.com/120x120?text=No+Image'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>" class="h-full w-full object-contain"></div>
                                    <div class="min-w-0 flex-1"><p class="truncate font-bold text-slate-800"><?php echo htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?></p><p class="text-xs uppercase tracking-[0.2em] text-indigo-600"><?php echo htmlspecialchars((string) $product['brand'], ENT_QUOTES, 'UTF-8'); ?></p><p class="text-sm text-slate-400">Stock: <?php echo (int) $product['stock_quantity']; ?></p></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php else: ?>
            <section class="panel-card rounded-[2.25rem] border border-slate-100 p-8 shadow-sm">
                <div class="mb-8 flex items-center justify-between">
                    <h2 class="text-3xl font-bold text-slate-800">Edit Products</h2>
                    <span class="rounded-full bg-indigo-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.25em] text-indigo-600"><?php echo count($allProducts); ?> Items</span>
                </div>
                <?php if (empty($allProducts)): ?>
                    <p class="rounded-[1.5rem] bg-slate-50 px-6 py-12 text-center text-slate-500">No products found.</p>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($allProducts as $product): ?>
                            <form method="POST" action="<?php echo htmlspecialchars(adminLink('edit-product', $filter), ENT_QUOTES, 'UTF-8'); ?>" class="rounded-[2rem] border border-slate-100 bg-slate-50/80 p-6 transition hover:border-slate-200 hover:bg-white hover:shadow-lg">
                                <input type="hidden" name="update_product" value="1">
                                <input type="hidden" name="product_id" value="<?php echo (int) $product['product_id']; ?>">
                                <div class="mb-6 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-24 w-24 items-center justify-center rounded-[1.5rem] bg-white p-3 shadow-sm">
                                            <img src="<?php echo htmlspecialchars((string) ($product['image_url'] ?: 'https://via.placeholder.com/160x160?text=No+Image'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>" class="h-full w-full object-contain">
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-[0.25em] text-indigo-600"><?php echo htmlspecialchars((string) $product['brand'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            <h3 class="mt-2 text-2xl font-bold text-slate-800"><?php echo htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                            <p class="mt-1 text-sm text-slate-400">Product ID: #<?php echo (int) $product['product_id']; ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="rounded-full px-3 py-1 text-xs font-bold <?php echo (int) $product['is_active'] === 1 ? 'bg-green-50 text-green-600' : 'bg-slate-200 text-slate-500'; ?>">
                                            <?php echo (int) $product['is_active'] === 1 ? 'Active' : 'Hidden'; ?>
                                        </span>
                                        <a href="product.php?id=<?php echo (int) $product['product_id']; ?>" class="rounded-2xl bg-white px-4 py-3 text-sm font-bold text-indigo-600 shadow-sm ring-1 ring-slate-200 transition hover:bg-indigo-50">Preview</a>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">Product Name</label>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 focus:border-indigo-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">Brand</label>
                                        <input type="text" name="brand" value="<?php echo htmlspecialchars((string) $product['brand'], ENT_QUOTES, 'UTF-8'); ?>" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 focus:border-indigo-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">Regular Price</label>
                                        <input type="number" step="0.01" name="regular_price" value="<?php echo htmlspecialchars((string) $product['regular_price'], ENT_QUOTES, 'UTF-8'); ?>" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 focus:border-indigo-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">Discount Price</label>
                                        <input type="number" step="0.01" name="discounted_price" value="<?php echo $product['discounted_price'] !== null ? htmlspecialchars((string) $product['discounted_price'], ENT_QUOTES, 'UTF-8') : ''; ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 focus:border-indigo-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">Stock Quantity</label>
                                        <input type="number" name="stock_quantity" value="<?php echo (int) $product['stock_quantity']; ?>" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 focus:border-indigo-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">Image URL</label>
                                        <input type="url" name="image_url" value="<?php echo htmlspecialchars((string) ($product['image_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 focus:border-indigo-500 focus:outline-none">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="mb-2 block text-sm font-bold text-slate-700">Description</label>
                                        <textarea name="description" rows="4" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 focus:border-indigo-500 focus:outline-none"><?php echo htmlspecialchars((string) ($product['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                    <div class="md:col-span-2 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <label class="flex items-center text-sm font-bold text-slate-700">
                                            <input type="checkbox" name="is_active" <?php echo (int) $product['is_active'] === 1 ? 'checked' : ''; ?> class="mr-3 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                            Active Product
                                        </label>
                                        <button type="submit" class="rounded-2xl bg-indigo-600 px-8 py-4 text-sm font-bold text-white shadow-lg shadow-indigo-200 transition hover:bg-indigo-500">Save Changes</button>
                                    </div>
                                </div>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</div>
<?php if ($section === 'dashboard'): ?>
<script>
const chartElement = document.getElementById('salesChart');
if (chartElement) {
    new Chart(chartElement.getContext('2d'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Daily Revenue',
                data: <?php echo json_encode($chartValues); ?>,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.12)',
                borderWidth: 4,
                tension: 0.35,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#4f46e5'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.12)' }, ticks: { color: '#64748b' } },
                x: { grid: { display: false }, ticks: { color: '#64748b' } }
            }
        }
    });
}
</script>
<?php endif; ?>
</body>
</html>
