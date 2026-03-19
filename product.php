<?php
require_once __DIR__ . '/includes/config/db.php';

if (!isLoggedIn()) {
    header('Location: ' . appUrl('login.php'));
    exit;
}

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header('Location: ' . appUrl('index.php'));
    exit;
}

$stmt = $pdo->prepare(
    'SELECT product_id, name, brand, regular_price, discounted_price, stock_quantity, is_active, image_url, description, specs
     FROM product_master
     WHERE product_id = ? AND is_active = 1
     LIMIT 1'
);
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . appUrl('index.php'));
    exit;
}

$regularPrice = (float)$product['regular_price'];
$discountedPrice = $product['discounted_price'] !== null ? (float)$product['discounted_price'] : null;
$displayPrice = $discountedPrice !== null && $discountedPrice > 0 ? $discountedPrice : $regularPrice;
$stock = max(0, (int)($product['stock_quantity'] ?? 0));

$specs = [];
if (!empty($product['specs'])) {
    $decodedSpecs = json_decode((string)$product['specs'], true);
    if (is_array($decodedSpecs)) {
        $specs = $decodedSpecs;
    }
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="mb-4">
    <a href="<?php echo htmlspecialchars(appUrl('index.php'), ENT_QUOTES, 'UTF-8'); ?>" class="text-slate-500 hover:text-primary flex items-center font-semibold transition">
        <i class="fas fa-arrow-left mr-2"></i> Back to Shop
    </a>
</div>

<div class="bg-white rounded-[2.5rem] md:rounded-[3rem] shadow-2xl overflow-hidden border border-slate-100 mb-20 p-5 sm:p-8 md:p-12 lg:p-16">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 md:gap-16 items-center">
        <div class="relative group">
            <div class="absolute -inset-4 bg-gradient-to-tr from-primary/10 to-secondary/10 rounded-full blur-2xl opacity-50 group-hover:opacity-100 transition duration-1000"></div>
            <div class="relative bg-slate-50 rounded-[2rem] md:rounded-[2.5rem] p-6 sm:p-8 md:p-12 flex items-center justify-center">
                <img src="<?php echo htmlspecialchars((string)($product['image_url'] ?: 'https://via.placeholder.com/400x400?text=No+Image'), ENT_QUOTES, 'UTF-8'); ?>"
                    alt="<?php echo htmlspecialchars((string)$product['name'], ENT_QUOTES, 'UTF-8'); ?>"
                    class="w-full max-w-md object-contain transform hover:scale-105 transition duration-500">
            </div>
        </div>

        <div data-product-id="<?php echo $productId; ?>">
            <div class="flex items-center space-x-2 mb-4">
                <span class="px-3 py-1 bg-primary/10 text-primary rounded-full text-xs font-bold uppercase tracking-widest">
                    <?php echo htmlspecialchars((string)$product['brand'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <span class="text-slate-400 text-sm">SKU: MW-<?php echo str_pad((string)$productId, 5, '0', STR_PAD_LEFT); ?></span>
            </div>

            <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-slate-800 mb-6"><?php echo htmlspecialchars((string)$product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>

            <div class="flex flex-wrap items-center gap-3 md:gap-4 mb-8">
                <span class="text-4xl font-extrabold text-primary">$<?php echo number_format($displayPrice, 2); ?></span>
                <?php if ($discountedPrice !== null && $discountedPrice > 0 && $regularPrice > $discountedPrice): ?>
                    <span class="text-xl text-slate-400 line-through">$<?php echo number_format($regularPrice, 2); ?></span>
                    <span class="bg-green-100 text-green-600 px-3 py-1 rounded-lg text-sm font-bold">
                        Save $<?php echo number_format($regularPrice - $discountedPrice, 2); ?>
                    </span>
                <?php endif; ?>
            </div>

            <p class="text-slate-500 text-lg leading-relaxed mb-10">
                <?php echo htmlspecialchars((string)($product['description'] ?: 'No description available for this product yet.'), ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <?php if (!empty($specs)): ?>
                <div class="mb-10">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
                        <i class="fas fa-list-ul mr-3 text-primary"></i> Technical Specifications
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($specs as $key => $val): ?>
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <span class="block text-xs text-slate-400 uppercase font-bold tracking-wider mb-1"><?php echo htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="font-semibold text-slate-700"><?php echo htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">
                    <div class="flex items-center bg-slate-100 rounded-2xl p-1 w-fit">
                        <button type="button" onclick="decrementQty()" class="w-12 h-12 flex items-center justify-center text-slate-600 hover:bg-white hover:rounded-xl hover:shadow-sm transition">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="quantity" value="1" min="1" max="<?php echo max(1, min(10, $stock)); ?>"
                            class="w-12 bg-transparent text-center font-bold text-slate-800 focus:outline-none">
                        <button type="button" onclick="incrementQty()" class="w-12 h-12 flex items-center justify-center text-slate-600 hover:bg-white hover:rounded-xl hover:shadow-sm transition">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <span class="text-slate-400 font-medium">
                        <?php echo $stock > 0 ? 'Only ' . $stock . ' left in stock!' : 'Currently out of stock'; ?>
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <button type="button" id="product-wishlist-btn" onclick="toggleWishlist(productId, this)"
                        class="wishlist-btn flex-1 py-4 bg-white text-red-500 rounded-2xl font-bold text-lg border border-red-100 hover:bg-red-500 hover:text-white transition-all transform active:scale-95 flex items-center justify-center shadow-sm">
                        <i class="far fa-heart mr-3 font-bold"></i> Wishlist
                    </button>
                    <button type="button" onclick="addToCartFromProduct()" <?php echo $stock <= 0 ? 'disabled' : ''; ?>
                        class="flex-1 py-4 bg-slate-800 text-white rounded-2xl font-bold text-lg hover:bg-black transition-all transform active:scale-95 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                        <i class="fas fa-shopping-cart mr-3"></i> Add to Cart
                    </button>
                    <button type="button" onclick="buyNowFromProduct()" <?php echo $stock <= 0 ? 'disabled' : ''; ?>
                        class="flex-1 py-4 bg-gradient-to-r from-primary to-secondary text-white rounded-2xl font-bold text-lg hover:shadow-xl hover:shadow-indigo-200 transition-all transform active:scale-95 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                        Buy it Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const productId = <?php echo $productId; ?>;
const maxStock = <?php echo max(1, min(10, $stock)); ?>;

function getQuantity() {
    const input = document.getElementById('quantity');
    const value = parseInt(input.value, 10);
    if (Number.isNaN(value) || value < 1) {
        input.value = 1;
        return 1;
    }
    if (value > maxStock) {
        input.value = maxStock;
        return maxStock;
    }
    return value;
}

function incrementQty() {
    const input = document.getElementById('quantity');
    const current = getQuantity();
    if (current < maxStock) {
        input.value = current + 1;
    }
}

function decrementQty() {
    const input = document.getElementById('quantity');
    const current = getQuantity();
    if (current > 1) {
        input.value = current - 1;
    }
}

async function addToCartFromProduct() {
    try {
        const quantity = getQuantity();
        const productName = document.querySelector('h1')?.textContent?.trim() || 'Product';
        bumpBadgeCount('.cart-count', quantity);
        await apiCall('POST', 'cart_add.php', { product_id: productId, quantity }, { suppressSuccessToast: true });
        updateCartCount();
        showToast(`${productName} has been added to your cart.`, 'success', 'Cart Updated');
    } catch (err) {
        updateCartCount();
    }
}

async function buyNowFromProduct() {
    try {
        const quantity = getQuantity();
        bumpBadgeCount('.cart-count', quantity);
        await apiCall('POST', 'cart_add.php', { product_id: productId, quantity }, { suppressSuccessToast: true });
        showToast('Item added to cart. Redirecting to checkout...', 'success', 'Ready to Checkout');
        updateCartCount();
        window.location.href = <?php echo json_encode(appUrl('checkout.php')); ?>;
    } catch (err) {
        updateCartCount();
    }
}

async function syncProductWishlistButton() {
    const wishlistBtn = document.getElementById('product-wishlist-btn');
    if (!wishlistBtn) return;

    try {
        const data = await apiCall('GET', 'wishlist_get.php', null, { redirectOn401: false, showLoader: false });
        const isWishlisted = (data.wishlist || []).some(item => Number(item.id) === Number(productId));
        wishlistBtn.classList.toggle('liked', isWishlisted);
        const icon = wishlistBtn.querySelector('i');
        if (icon) {
            icon.className = isWishlisted ? 'fas fa-heart mr-3 font-bold' : 'far fa-heart mr-3 font-bold';
        }
    } catch (err) {
        // Silent fail
    }
}

syncProductWishlistButton();
</script>

<style>
    .wishlist-btn.liked {
        background-color: #ef4444;
        color: #fff;
        border-color: #ef4444;
    }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
