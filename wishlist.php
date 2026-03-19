<?php
include_once __DIR__ . '/includes/config/db.php';

// Check if logged in (optional)
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="mb-12 md:mb-16">
    <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-slate-800 mb-4 md:mb-6 tracking-tight">
        <i class="fas fa-heart text-pink-500 mr-4"></i>My Wishlist
    </h1>
    <p class="text-lg md:text-xl text-slate-500 max-w-2xl leading-relaxed">
        Products you've loved. Ready to make them yours?
    </p>
</div>

<!-- Product Grid -->
<div id="wishlist-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 md:gap-8">
    Loading wishlist...
</div>

<script>
async function loadWishlistProducts() {
  try {
    const data = await apiCall('GET', 'wishlist_get.php');
    const grid = document.getElementById('wishlist-grid');
    
    if (data.count === 0) {
      grid.innerHTML = `
        <div class="col-span-full text-center py-14 md:py-20">
          <i class="fas fa-heart-broken text-6xl text-slate-300 mb-6"></i>
          <h3 class="text-2xl font-bold text-slate-700 mb-2">Your wishlist is empty</h3>
          <p class="text-slate-500 mb-8">Like some products on the home page to see them here.</p>
          <a href="index.php" class="px-8 py-4 bg-primary text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-secondary transition-all transform hover:-translate-y-1">
            Start Shopping
          </a>
        </div>
      `;
      return;
    }

    grid.innerHTML = data.wishlist.map(product => `
      <div class="group bg-white rounded-[2rem] overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-500 border border-slate-100 flex flex-col h-full transform hover:-translate-y-2" data-product-id="${product.id}">
        <div class="relative overflow-hidden h-56 md:h-64 bg-slate-50">
          <img src="${product.image}" alt="${product.name}" class="w-full h-full object-contain p-6 transform group-hover:scale-110 transition-transform duration-500">
          <div class="absolute top-4 right-4 translate-x-12 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all duration-300">
            <button class="wishlist-btn w-10 h-10 bg-white rounded-full flex items-center justify-center text-red-500 shadow-lg hover:bg-red-500 hover:text-white transition liked:bg-red-500 liked:text-white" onclick="toggleWishlist(${product.id}, this)">
              <i class="fas fa-heart font-bold"></i>
            </button>
          </div>
          <div class="absolute bottom-4 left-4">
            <span class="bg-white/80 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold text-pink-600 border border-white/40 uppercase tracking-widest shadow-lg">
              Wishlisted
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
              <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
            </button>
            <button onclick="buyNow(${product.id})" class="flex items-center justify-center py-3 bg-gradient-to-r from-primary to-secondary text-white rounded-xl font-bold text-sm hover:shadow-lg hover:shadow-indigo-200 transition-all">
              Buy Now
            </button>
          </div>
        </div>
      </div>
    `).join('');
    showToast(`Loaded ${data.count} wishlisted items!`);
  } catch (err) {
    document.getElementById('wishlist-grid').innerHTML = '<p class="col-span-full text-center text-slate-500 py-20">Failed to load wishlist. <button onclick="loadWishlistProducts()" class="text-primary underline">Retry</button></p>';
  }
}

// Load on page
loadWishlistProducts();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

