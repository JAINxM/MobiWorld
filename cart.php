<?php
include_once __DIR__ . '/includes/config/db.php';

if (!isLoggedIn()) {
    redirect('login.php');
}
?>


<?php include __DIR__ . '/includes/header.php'; ?>

<div class="mb-12">
    <h1 class="text-4xl font-extrabold text-slate-800">Shopping <span class="text-primary">Cart</span></h1>
<p class="text-slate-500 mt-2">Loading cart...</p>
</div>
<div id="cart-container" class="min-h-[400px]">
    Loading your cart...
</div>

<script>
async function loadCart() {
  try {
    const data = await apiCall('GET', 'cart_get.php');
    const container = document.getElementById('cart-container');
    if (data.cart.length === 0) {
      container.innerHTML = `
        <div class="bg-white rounded-[3rem] p-20 text-center shadow-xl border border-slate-100">
          <div class="w-32 h-32 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-8 text-slate-300">
            <i class="fas fa-shopping-basket text-5xl"></i>
          </div>
          <h2 class="text-2xl font-bold text-slate-700 mb-4">Your cart is empty</h2>
          <p class="text-slate-500 mb-10">Looks like you haven't added anything to your cart yet.</p>
          <a href="index.php" class="px-10 py-4 bg-primary text-white rounded-2xl font-bold hover:bg-secondary transition-all shadow-lg shadow-indigo-100">
            Explore Smartphones
          </a>
        </div>
      `;
      return;
    }
    container.innerHTML = `
      <div class="lg:col-span-2 space-y-6">
        ${data.cart.map(item => `
          <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col md:flex-row items-center gap-8 relative group">
            <div class="w-32 h-32 bg-slate-50 rounded-2xl p-4 flex-shrink-0">
              <img src="${item.image}" alt="${item.name}" class="w-full h-full object-contain">
            </div>
            <div class="flex-grow text-center md:text-left">
              <span class="text-primary text-xs font-bold uppercase tracking-widest">${item.brand}</span>
              <h3 class="text-xl font-bold text-slate-800 mb-1">${item.name}</h3>
              <p class="text-slate-400 text-sm mb-4">$${parseFloat(item.price).toLocaleString()} each</p>
            </div>
            <div class="flex items-center bg-slate-100 rounded-xl p-1">
              <button onclick="updateCartItem(${item.product_id}, ${item.quantity - 1})" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:text-primary transition">
                <i class="fas fa-minus text-xs"></i>
              </button>
              <span class="w-10 text-center font-bold text-slate-800">${item.quantity}</span>
              <button onclick="updateCartItem(${item.product_id}, ${item.quantity + 1})" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:text-primary transition">
                <i class="fas fa-plus text-xs"></i>
              </button>
            </div>
            <div class="text-xl font-bold text-slate-900 w-24 text-right">
              $${item.subtotal.toLocaleString()}
            </div>
            <button onclick="removeCartItem(${item.product_id})" class="absolute top-4 right-4 md:static text-slate-300 hover:text-red-500 transition text-xl">
              <i class="fas fa-times-circle"></i>
            </button>
          </div>
        `).join('')}
        <div class="mt-8 flex justify-between">
          <a href="index.php" class="text-primary font-bold hover:underline flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Continue Shopping
          </a>
          <button onclick="loadCart()" class="text-slate-600 font-bold hover:text-primary flex items-center">
            <i class="fas fa-sync-alt mr-2"></i> Update Cart
          </button>
        </div>
      </div>
      <div class="lg:col-span-1">
        <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-slate-100 sticky top-32">
          <h3 class="text-2xl font-bold text-slate-800 mb-8">Order Summary</h3>
          <div class="space-y-4 mb-8">
            <div class="flex justify-between text-slate-500">
              <span>Subtotal</span>
              <span class="font-bold text-slate-800">$${data.total.toLocaleString()}</span>
            </div>
            <div class="flex justify-between text-slate-500">
              <span>Shipping</span>
              <span class="font-bold text-green-500 uppercase text-xs">Free</span>
            </div>
            <div class="flex justify-between text-slate-500">
              <span>Tax (Estimated)</span>
              <span class="font-bold text-slate-800">$0.00</span>
            </div>
          </div>
          <div class="border-t border-slate-100 pt-6 mb-10 flex justify-between items-center">
            <span class="text-xl font-bold text-slate-800">Total Amount</span>
            <span class="text-3xl font-extrabold text-primary">$${data.total.toLocaleString()}</span>
          </div>
          <a href="checkout.php" class="block w-full text-center py-5 bg-gradient-to-r from-primary to-secondary text-white rounded-2xl font-bold text-lg hover:shadow-xl hover:shadow-indigo-200 transition-all transform active:scale-95 shadow-lg shadow-indigo-100 group">
            Proceed to Checkout
            <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition"></i>
          </a>
          <div class="mt-8 flex items-center justify-center space-x-4 grayscale opacity-40">
            <i class="fab fa-cc-visa text-3xl"></i>
            <i class="fab fa-cc-mastercard text-3xl"></i>
            <i class="fab fa-cc-paypal text-3xl"></i>
            <i class="fab fa-cc-apple-pay text-3xl"></i>
          </div>
        </div>
      </div>
    `;
  } catch (err) {
    document.getElementById('cart-container').innerHTML = '<p class="text-center py-20 text-slate-500">Failed to load cart. <button onclick="loadCart()" class="text-primary underline">Retry</button></p>';
  }
}

async function updateCartItem(productId, quantity) {
  await apiCall('POST', 'cart_update.php', {product_id: productId, quantity});
  loadCart();
}

async function removeCartItem(productId) {
  if (confirm('Remove this item?')) {
    await apiCall('POST', 'cart_remove.php', {product_id: productId});
    loadCart();
  }
}

loadCart();
</script>

<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
