<?php
include_once __DIR__ . '/includes/config/db.php';

// Check if logged in (optional for homepage, comment if guest view wanted)
if (!isLoggedIn()) {
    // redirect('login.php'); // Disabled for guest browsing
}
?>


<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Hero Section -->
<section class="mb-16 text-center py-10">
    <h1 class="text-5xl md:text-6xl font-extrabold text-slate-800 mb-6 tracking-tight">
        Future is in your <span class="bg-clip-text text-transparent bg-gradient-to-r from-primary to-secondary">Hand</span>.
    </h1>
    <p class="text-xl text-slate-500 max-w-2xl mx-auto mb-10 leading-relaxed">
        Discover the latest smartphone innovations. From high-end flagships to budget-friendly powerhouses.
    </p>
    <div class="flex flex-wrap justify-center gap-4">
        <a href="#products" class="px-8 py-4 bg-primary text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-secondary transition-all transform hover:-translate-y-1">
            Shop Collections
        </a>
        <a href="#" class="px-8 py-4 bg-white text-slate-700 border border-slate-200 rounded-2xl font-bold hover:bg-slate-50 transition-all transform hover:-translate-y-1">
            View Offers
        </a>
    </div>
</section>

<!-- Mobile Brands Filter (Visual only) -->
<div id="brand-filters" class="flex overflow-x-auto pb-8 mb-10 gap-4 no-scrollbar">
    <?php
    $brands = ['All', 'Apple', 'Samsung', 'Google', 'OnePlus', 'Xiaomi', 'Sony', 'Asus'];
    foreach ($brands as $brand):
    ?>
        <button
            type="button"
            data-brand="<?php echo htmlspecialchars($brand, ENT_QUOTES, 'UTF-8'); ?>"
            onclick="applyBrandFilter('<?php echo htmlspecialchars($brand, ENT_QUOTES, 'UTF-8'); ?>', this)"
            class="brand-filter px-6 py-2 rounded-full whitespace-nowrap font-semibold border transition <?php echo $brand === 'All' ? 'border-primary bg-primary text-white shadow-lg shadow-indigo-200' : 'border-slate-200 bg-white text-slate-600 hover:border-primary hover:text-primary'; ?>"
        >
            <?php echo $brand; ?>
        </button>
    <?php endforeach; ?>
</div>

<!-- Product Grid -->
<div id="products-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
    Loading products...
</div>

<script>
let allProducts = [];
let activeBrand = 'All';

function renderProducts(products) {
  const grid = document.getElementById('products-grid');

  if (!products.length) {
    grid.innerHTML = `
      <div class="col-span-full rounded-[2rem] border border-dashed border-slate-300 bg-white/80 px-8 py-16 text-center shadow-sm">
        <p class="text-lg font-bold text-slate-700">No smartphones found for ${activeBrand}</p>
        <p class="mt-2 text-sm text-slate-500">Try another brand filter to explore more devices.</p>
      </div>
    `;
    return;
  }

  grid.innerHTML = products.map(product => `
    <div class="group bg-white rounded-[2rem] overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-500 border border-slate-100 flex flex-col h-full transform hover:-translate-y-2" data-product-id="${product.id}" data-brand="${product.brand}">
      <div class="relative overflow-hidden h-64 bg-slate-50">
        <img src="${product.image}" alt="${product.name}" class="w-full h-full object-contain p-6 transform group-hover:scale-110 transition-transform duration-500">
        <div class="absolute top-4 right-4 translate-x-12 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all duration-300">
          <button class="wishlist-btn w-10 h-10 bg-white rounded-full flex items-center justify-center text-red-500 shadow-lg hover:bg-red-500 hover:text-white transition liked:bg-red-500 liked:text-white" onclick="toggleWishlist(${product.id}, this)">
            <i class="far fa-heart font-bold"></i>
          </button>
        </div>
        <div class="absolute bottom-4 left-4">
          <span class="bg-white/80 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold text-slate-700 border border-white/40 uppercase tracking-widest">
            New Arrival
          </span>
        </div>
      </div>
      <div class="p-8 flex flex-col flex-grow">
        <div class="flex justify-between items-start mb-2">
          <span class="text-primary font-bold text-xs uppercase tracking-widest">${product.brand}</span>
          <div class="flex text-yellow-400 text-xs">
            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
          </div>
        </div>
        <h3 class="text-xl font-bold text-slate-800 mb-2 truncate">${product.name}</h3>
        <p class="text-slate-500 text-sm line-clamp-2 mb-6">${product.description}</p>
        <div class="mt-auto flex items-center justify-between">
          <span class="text-2xl font-extrabold text-slate-900">$${parseFloat(product.price).toLocaleString()}</span>
          <a href="#" onclick="loadProductDetail(${product.id}); return false;" class="text-primary font-bold text-sm hover:underline flex items-center">
            Details <i class="fas fa-arrow-right ml-2 text-xs"></i>
          </a>
        </div>
        <div class="grid grid-cols-2 gap-3 mt-8">
          <button onclick="addToCart(${product.id})" class="flex items-center justify-center py-3 bg-slate-100 text-slate-700 rounded-xl font-bold text-sm hover:bg-primary hover:text-white transition-colors">
            <i class="fas fa-shopping-cart mr-2"></i> Cart
          </button>
          <button onclick="buyNow(${product.id})" class="flex items-center justify-center py-3 bg-gradient-to-r from-primary to-secondary text-white rounded-xl font-bold text-sm hover:shadow-lg hover:shadow-indigo-200 transition-all">
            Buy Now
          </button>
        </div>
      </div>
    </div>
  `).join('');
}

function normalizeBrandName(value) {
  return String(value || '').trim().toLowerCase();
}

function setActiveBrandButton(activeButton = null, brand = 'All') {
  document.querySelectorAll('.brand-filter').forEach(button => {
    const isActive = activeButton ? button === activeButton : button.dataset.brand === brand;
    button.classList.toggle('border-primary', isActive);
    button.classList.toggle('bg-primary', isActive);
    button.classList.toggle('text-white', isActive);
    button.classList.toggle('shadow-lg', isActive);
    button.classList.toggle('shadow-indigo-200', isActive);
    button.classList.toggle('border-slate-200', !isActive);
    button.classList.toggle('bg-white', !isActive);
    button.classList.toggle('text-slate-600', !isActive);
  });
}

function applyBrandFilter(brand, clickedButton = null) {
  activeBrand = brand;
  const normalizedBrand = normalizeBrandName(brand);
  const filteredProducts = normalizedBrand === 'all'
    ? allProducts
    : allProducts.filter(product => normalizeBrandName(product.brand).includes(normalizedBrand));

  renderProducts(filteredProducts);
  setActiveBrandButton(clickedButton, brand);
}

async function loadProducts() {
  try {
    const data = await apiCall('GET', 'products.php');
    allProducts = data.products || [];
    applyBrandFilter(activeBrand);
    showToast('Products loaded!');
  } catch (err) {
    document.getElementById('products-grid').innerHTML = '<p class="col-span-full text-center text-slate-500 py-20">Failed to load products. <button onclick="loadProducts()" class="text-primary underline">Retry</button></p>';
  }
}

async function addToCart(productId) {
  try {
    const productName = document.querySelector(`[data-product-id="${productId}"] h3`)?.textContent?.trim() || 'Product';
    bumpBadgeCount('.cart-count', 1);
    await apiCall('POST', 'cart_add.php', {product_id: productId, quantity: 1}, { suppressSuccessToast: true });
    updateCartCount();
    showToast(`${productName} has been added to your cart.`, 'success', 'Cart Updated');
  } catch (err) {
    updateCartCount();
    // Already handled in apiCall
  }
}

async function buyNow(productId) {
  try {
    await apiCall('POST', 'cart_add.php', {product_id: productId, quantity: 1});
    window.location.href = 'cart.php';
  } catch (err) {}
}

function loadProductDetail(id) {
  window.location.href = 'product.php?id=' + id;
}

// Load on page
loadProducts();
</script>

<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    
    .wishlist-btn.liked {
      background-color: #ef4444;
      color: white;
    }
    .wishlist-btn.liked i {
      color: white !important;
    }
    .wishlist-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>

