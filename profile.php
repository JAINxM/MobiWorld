<?php
require_once __DIR__ . '/includes/config/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = getCurrentUserId();

$userStmt = $pdo->prepare('SELECT user_id, full_name, email FROM user_master WHERE user_id = ? LIMIT 1');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if (!$user) {
    header('Location: login.php');
    exit;
}

$ordersStmt = $pdo->prepare(
    'SELECT order_id, total_amount, order_status, created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC'
);
$ordersStmt->execute([$userId]);
$orders = $ordersStmt->fetchAll();

$orderStatusLabels = [
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'out_for_delivery' => 'Out for Delivery',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];

function profileOrderStatusClass(string $status): string
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

$orderIds = [];
$totalSpending = 0.0;
$totalOrders = count($orders);
$orderItemsByOrder = [];

foreach ($orders as $order) {
    $orderIds[] = (int)$order['order_id'];
    $totalSpending += (float)$order['total_amount'];
}

if (!empty($orderIds)) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itemsStmt = $pdo->prepare(
        "SELECT
            oi.order_id,
            oi.product_id,
            oi.quantity,
            oi.price_at_time,
            pm.name,
            pm.brand,
            pm.image_url
         FROM order_items oi
         INNER JOIN product_master pm ON pm.product_id = oi.product_id
         WHERE oi.order_id IN ($placeholders)
         ORDER BY oi.order_item_id DESC"
    );
    $itemsStmt->execute($orderIds);

    foreach ($itemsStmt->fetchAll() as $item) {
        $orderId = (int)$item['order_id'];
        if (!isset($orderItemsByOrder[$orderId])) {
            $orderItemsByOrder[$orderId] = [];
        }
        $orderItemsByOrder[$orderId][] = $item;
    }
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-12">
    <div class="lg:col-span-1 lg:sticky lg:top-28 h-fit space-y-8">
        <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-slate-100 text-center">
            <div class="w-24 h-24 bg-gradient-to-tr from-primary to-secondary rounded-full flex items-center justify-center mx-auto mb-6 text-white text-3xl font-bold shadow-lg">
                <?php echo htmlspecialchars(strtoupper(substr((string)$user['full_name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <h2 class="text-2xl font-bold text-slate-800"><?php echo htmlspecialchars((string)$user['full_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="text-slate-500 mb-8"><?php echo htmlspecialchars((string)$user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="pt-8 border-t border-slate-100">
                <a href="logout.php" class="text-red-500 font-bold hover:underline">Sign Out Account</a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-primary">
                    <i class="fas fa-box-open"></i>
                </div>
                <div>
                    <span class="block text-2xl font-extrabold text-slate-800"><?php echo $totalOrders; ?></span>
                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Total Orders</span>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="w-12 h-12 bg-green-50 rounded-2xl flex items-center justify-center text-green-500">
                    <i class="fas fa-wallet"></i>
                </div>
                <div>
                    <span class="block text-2xl font-extrabold text-slate-800">$<?php echo number_format($totalSpending, 2); ?></span>
                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Total Spending</span>
                </div>
            </div>
        </div>
    </div>

    <div class="lg:col-span-3">
        <div class="bg-white p-8 md:p-12 rounded-[2.5rem] shadow-xl border border-slate-100 min-h-[600px]">
            <h3 class="text-2xl font-bold text-slate-800 mb-10 flex items-center">
                <i class="fas fa-history text-primary mr-4"></i> Purchase History
            </h3>

            <?php if (empty($orders)): ?>
                <div class="text-center py-20">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                        <i class="fas fa-shopping-bag text-3xl"></i>
                    </div>
                    <p class="text-slate-500 text-lg">No purchased products yet.</p>
                    <a href="index.php" class="text-primary font-bold mt-4 inline-block hover:underline">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="space-y-8">
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $orderId = (int)$order['order_id'];
                        $items = $orderItemsByOrder[$orderId] ?? [];
                        ?>
                        <div class="border border-slate-100 rounded-[2rem] p-6 md:p-8 hover:border-slate-200 hover:shadow-lg transition-all">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                                <div>
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold uppercase tracking-widest <?php echo profileOrderStatusClass((string)$order['order_status']); ?>">
                                        <?php echo htmlspecialchars($orderStatusLabels[(string)$order['order_status']] ?? ucwords(str_replace('_', ' ', (string)$order['order_status'])), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <h4 class="mt-3 text-xl font-bold text-slate-800">Order #<?php echo $orderId; ?></h4>
                                    <p class="text-sm text-slate-400">
                                        <?php echo date('M d, Y h:i A', strtotime((string)$order['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="text-left md:text-right">
                                    <span class="block text-2xl font-extrabold text-slate-900">$<?php echo number_format((float)$order['total_amount'], 2); ?></span>
                                    <span class="text-sm text-slate-400"><?php echo count($items); ?> Product<?php echo count($items) === 1 ? '' : 's'; ?></span>
                                </div>
                            </div>

                            <?php if (empty($items)): ?>
                                <p class="text-slate-500">No product details found for this order.</p>
                            <?php else: ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                                    <?php foreach ($items as $item): ?>
                                        <a href="product.php?id=<?php echo (int)$item['product_id']; ?>"
                                            class="group block rounded-[1.5rem] border border-slate-100 bg-slate-50 p-4 hover:border-primary hover:bg-white hover:shadow-lg transition-all">
                                            <div class="mb-4 flex h-40 items-center justify-center rounded-[1.25rem] bg-white p-4">
                                                <img src="<?php echo htmlspecialchars((string)($item['image_url'] ?: 'https://via.placeholder.com/300x300?text=No+Image'), ENT_QUOTES, 'UTF-8'); ?>"
                                                    alt="<?php echo htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-105">
                                            </div>
                                            <div class="space-y-1">
                                                <p class="text-xs font-bold uppercase tracking-widest text-primary"><?php echo htmlspecialchars((string)$item['brand'], ENT_QUOTES, 'UTF-8'); ?></p>
                                                <h5 class="font-bold text-slate-800 line-clamp-2 min-h-[3rem]"><?php echo htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                                                <p class="text-sm text-slate-500">Qty: <?php echo (int)$item['quantity']; ?></p>
                                                <p class="text-lg font-extrabold text-slate-900">$<?php echo number_format((float)$item['price_at_time'], 2); ?></p>
                                                <span class="inline-flex items-center text-sm font-bold text-primary">
                                                    Open Product
                                                    <i class="fas fa-arrow-right ml-2 text-xs transition-transform duration-300 group-hover:translate-x-1"></i>
                                                </span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
