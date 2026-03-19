<?php
require_once __DIR__ . '/includes/config/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = getCurrentUserId();
$user = null;

if ($userId !== null) {
    $stmt = $pdo->prepare('SELECT full_name, email FROM user_master WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
}

if (!$user) {
    header('Location: login.php');
    exit;
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="mb-12">
    <h1 class="text-4xl font-extrabold text-slate-800">Checkout</h1>
    <p class="text-slate-500 mt-2">Review your order and shipping details</p>
</div>

<div id="checkout-message" class="hidden mb-8 rounded-2xl border px-6 py-5"></div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
    <div class="lg:col-span-2 space-y-8">
        <div class="bg-white p-8 md:p-12 rounded-[2.5rem] shadow-xl border border-slate-100">
            <h3 class="text-2xl font-bold text-slate-800 mb-8 flex items-center">
                <i class="fas fa-truck text-primary mr-4"></i> Shipping Information
            </h3>
            <form id="checkout-form" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="recipient_name" class="block text-sm font-semibold text-slate-700 mb-2">Recipient Name</label>
                    <input type="text" id="recipient_name" name="recipient_name"
                        value="<?php echo htmlspecialchars((string)$user['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required
                        class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                    <input type="email" value="<?php echo htmlspecialchars((string)$user['email'], ENT_QUOTES, 'UTF-8'); ?>" disabled
                        class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-slate-400 cursor-not-allowed">
                </div>
                <div class="md:col-span-2">
                    <label for="shipping_address" class="block text-sm font-semibold text-slate-700 mb-2">Shipping Address</label>
                    <textarea id="shipping_address" name="shipping_address" required placeholder="Enter your full delivery address"
                        class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition h-32"></textarea>
                </div>
            </form>
        </div>

        <div class="bg-white p-8 md:p-12 rounded-[2.5rem] shadow-xl border border-slate-100">
            <h3 class="text-2xl font-bold text-slate-800 mb-8 flex items-center">
                <i class="fas fa-credit-card text-primary mr-4"></i> Payment Method
            </h3>
            <div class="space-y-4">
                <label class="flex items-center p-6 border-2 border-primary bg-indigo-50 rounded-2xl cursor-pointer">
                    <input type="radio" name="payment_method" value="cod" checked form="checkout-form" class="w-5 h-5 text-primary">
                    <div class="ml-4">
                        <span class="block font-bold text-slate-800 text-lg">Cash on Delivery (COD)</span>
                        <span class="text-sm text-slate-500">Fast and secure delivery</span>
                    </div>
                    <i class="fas fa-money-bill-wave ml-auto text-2xl text-primary"></i>
                </label>
                <label class="flex items-center p-6 border-2 border-slate-100 rounded-2xl cursor-not-allowed opacity-50">
                    <input type="radio" name="payment_method" disabled class="w-5 h-5">
                    <div class="ml-4">
                        <span class="block font-bold text-slate-800 text-lg">Credit / Debit Card</span>
                        <span class="text-sm text-slate-500 underline">Currently unavailable</span>
                    </div>
                    <i class="fas fa-lock ml-auto text-xl text-slate-400"></i>
                </label>
            </div>
        </div>
    </div>

    <div class="lg:col-span-1">
        <div class="bg-slate-900 p-8 rounded-[2.5rem] shadow-2xl text-white sticky top-32 overflow-hidden">
            <div class="absolute -top-20 -right-20 w-40 h-40 bg-primary rounded-full blur-[80px] opacity-20"></div>

            <h3 class="text-2xl font-bold mb-8 relative">Order Review</h3>

            <div id="checkout-items" class="space-y-6 mb-10 max-h-80 overflow-y-auto pr-2 custom-scrollbar relative">
                <p class="text-white/60">Loading your order...</p>
            </div>

            <div class="border-t border-white/10 pt-6 space-y-4 mb-8 relative">
                <div class="flex justify-between text-white/60">
                    <span>Subtotal</span>
                    <span id="subtotal-amount">$0.00</span>
                </div>
                <div class="flex justify-between text-white/60">
                    <span>Shipping</span>
                    <span class="text-green-400">FREE</span>
                </div>
                <div class="flex justify-between text-xl font-bold pt-2 border-t border-white/10 mt-4">
                    <span>Total</span>
                    <span id="total-amount" class="text-primary">$0.00</span>
                </div>
            </div>

            <button type="submit" form="checkout-form" id="place-order-btn"
                class="w-full py-5 bg-primary text-white rounded-2xl font-bold text-lg hover:bg-secondary transition-all transform active:scale-95 shadow-xl shadow-primary/20 relative">
                Confirm & Place Order
            </button>

            <p class="text-center text-white/40 text-xs mt-6">
                <i class="fas fa-shield-alt mr-1"></i> Secure 256-bit SSL encrypted checkout
            </p>
        </div>
    </div>
</div>

<script>
function formatCurrency(value) {
    return '$' + Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function showCheckoutMessage(message, type) {
    const box = document.getElementById('checkout-message');
    box.className = 'mb-8 rounded-2xl border px-6 py-5';
    box.classList.add('block');
    box.classList.remove('hidden');

    if (type === 'success') {
        box.classList.add('bg-green-50', 'border-green-200', 'text-green-700');
    } else {
        box.classList.add('bg-red-50', 'border-red-200', 'text-red-700');
    }

    box.textContent = message;
}

async function loadCheckoutCart() {
    try {
        const data = await apiCall('GET', 'cart_get.php');
        const itemsContainer = document.getElementById('checkout-items');
        const subtotal = Number(data.total || 0);

        if (!data.cart || data.cart.length === 0) {
            itemsContainer.innerHTML = `
                <div class="text-center py-10">
                    <p class="text-white/60 mb-6">Your cart is empty.</p>
                    <a href="index.php" class="inline-block px-6 py-3 rounded-xl bg-white text-slate-900 font-bold">Continue Shopping</a>
                </div>
            `;
            document.getElementById('place-order-btn').disabled = true;
            document.getElementById('place-order-btn').classList.add('opacity-50', 'cursor-not-allowed');
            document.getElementById('subtotal-amount').textContent = formatCurrency(0);
            document.getElementById('total-amount').textContent = formatCurrency(0);
            return;
        }

        itemsContainer.innerHTML = data.cart.map(item => `
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white/10 rounded-xl p-2 flex-shrink-0">
                    <img src="${item.image}" alt="${item.name}" class="w-full h-full object-contain">
                </div>
                <div class="flex-grow min-w-0">
                    <h4 class="font-bold text-sm truncate">${item.name}</h4>
                    <p class="text-white/50 text-xs">Qty: ${item.quantity} x ${formatCurrency(item.price)}</p>
                </div>
                <div class="font-bold text-sm">
                    ${formatCurrency(item.subtotal)}
                </div>
            </div>
        `).join('');

        document.getElementById('subtotal-amount').textContent = formatCurrency(subtotal);
        document.getElementById('total-amount').textContent = formatCurrency(subtotal);
    } catch (err) {
        document.getElementById('checkout-items').innerHTML = '<p class="text-white/60">Failed to load your cart. Please refresh and try again.</p>';
    }
}

document.getElementById('checkout-form').addEventListener('submit', async function (event) {
    event.preventDefault();

    const button = document.getElementById('place-order-btn');
    const formData = new FormData(event.currentTarget);
    const payload = {
        recipient_name: formData.get('recipient_name'),
        shipping_address: formData.get('shipping_address'),
        payment_method: formData.get('payment_method')
    };

    button.disabled = true;
    button.textContent = 'Placing Order...';

    try {
        const data = await apiCall('POST', 'order_place.php', payload);
        updateCartCount();
        showCheckoutMessage('Order placed successfully. Your order number is #' + data.order.order_id + '.', 'success');
// event.currentTarget.reset(); // FIXED null reset error
        document.getElementById('recipient_name').value = <?php echo json_encode((string)$user['full_name']); ?>;
        document.getElementById('checkout-items').innerHTML = `
            <div class="text-center py-10">
                <p class="text-white mb-2 text-lg font-bold">Order confirmed</p>
                <p class="text-white/60 mb-6">We have received your order and will start processing it shortly.</p>
                <a href="index.php" class="inline-block px-6 py-3 rounded-xl bg-white text-slate-900 font-bold">Shop More</a>
            </div>
        `;
        document.getElementById('subtotal-amount').textContent = formatCurrency(0);
        document.getElementById('total-amount').textContent = formatCurrency(0);
    } catch (err) {
        showCheckoutMessage(err.message || 'Order placement failed.', 'error');
    } finally {
        button.disabled = false;
        button.textContent = 'Confirm & Place Order';
        loadCheckoutCart();
    }
});

loadCheckoutCart();
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
