<?php
include_once __DIR__ . '/includes/config/db.php';

// Check if logged in (optional for homepage, comment if guest view wanted)
if (!isLoggedIn()) {
    // redirect('login.php'); // Disabled for guest browsing
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Hero Section -->
<section class="mb-12 md:mb-16 text-center py-8 md:py-10">
    <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold text-slate-800 mb-6 tracking-tight">
        Future is in your <span class="bg-clip-text text-transparent bg-gradient-to-r from-primary to-secondary">Hand</span>.
    </h1>
    <p class="text-lg md:text-xl text-slate-500 max-w-2xl mx-auto mb-8 md:mb-10 leading-relaxed">
        Discover the latest smartphone innovations. From high-end flagships to budget-friendly powerhouses.
    </p>
    <div class="flex flex-col sm:flex-row flex-wrap justify-center gap-4">
        <button type="button" onclick="applyHeroFilter('all')" class="hero-filter-btn hero-filter-btn-active w-full sm:w-auto min-w-[12rem] justify-center rounded-2xl px-8 py-4 font-bold transition-all transform hover:-translate-y-1 inline-flex items-center">
            Shop Collections
        </button>
        <button type="button" onclick="applyHeroFilter('offers')" class="hero-filter-btn hero-filter-btn-active w-full sm:w-auto min-w-[12rem] justify-center rounded-2xl px-8 py-4 font-bold transition-all transform hover:-translate-y-1 inline-flex items-center">
            View Offers
        </button>
    </div>
    

</section>

<!-- Mobile Brands Filter (Visual only) -->

<div id="brand-filters" class="flex overflow-x-auto pb-8 mb-10 gap-4 no-scrollbar">
    <?php
    $brands = ['All', 'Apple', 'Samsung', 'Google', 'OnePlus', 'Xiaomi', 'Vivo', 'Oppo', 'Realme', 'iQOO', 'Asus'];
    foreach ($brands as $brand):
    ?>
        <button
            type="button"
            data-brand="<?php echo htmlspecialchars($brand, ENT_QUOTES, 'UTF-8'); ?>"
            onclick="applyBrandFilter('<?php echo htmlspecialchars($brand, ENT_QUOTES, 'UTF-8'); ?>', this)"
            class="brand-filter px-6 py-2 rounded-full whitespace-nowrap font-semibold border transition <?php echo $brand === 'All' ? 'border-primary bg-primary text-white shadow-lg shadow-indigo-200' : 'border-slate-200 bg-white text-slate-600 hover:border-primary hover:text-black'; ?>"
        >
            <?php echo $brand; ?>
        </button>
    <?php endforeach; ?>
</div>

<!-- Product Grid -->
<div id="products-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 md:gap-8">
    Loading products...
</div>

<script>
let allProducts = [];
let activeBrand = 'All';
let activeHeroFilter = 'all';

function renderProducts(products) {
  const grid = document.getElementById('products-grid');

  if (!products.length) {
    grid.innerHTML = `
      <div class="col-span-full rounded-[2rem] border border-dashed border-slate-300 bg-white/80 px-8 py-16 text-center shadow-sm">
        <p class="text-lg font-bold text-slate-700">No smartphones found for this selection</p>
        <p class="mt-2 text-sm text-slate-500">Try another brand filter to explore more devices.</p>
      </div>
    `;
    return;
  }

  grid.innerHTML = products.map(product => `
    <div class="group bg-white rounded-[2rem] overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-500 border border-slate-100 flex flex-col h-full transform hover:-translate-y-2" data-product-id="${product.id}" data-brand="${product.brand}">
      <div class="relative overflow-hidden h-56 md:h-64 bg-slate-50">
        <img src="${product.image}" alt="${product.name}" class="w-full h-full object-contain p-6 transform group-hover:scale-110 transition-transform duration-500">
        <div class="absolute top-4 right-4 translate-x-12 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all duration-300">
          <button class="wishlist-btn w-10 h-10 bg-white rounded-full flex items-center justify-center text-red-500 shadow-lg hover:bg-red-500 hover:text-white transition liked:bg-red-500 liked:text-white" onclick="toggleWishlist(${product.id}, this)">
            <i class="far fa-heart font-bold"></i>
          </button>
        </div>
        <div class="absolute bottom-4 left-4">
          ${product.has_discount
            ? `<span class="bg-emerald-500/90 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold text-white border border-white/40 uppercase tracking-widest shadow-lg">Offer Live</span>`
            : `<span class="bg-white/80 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold text-slate-700 border border-white/40 uppercase tracking-widest">New Arrival</span>`
          }
        </div>
      </div>
      <div class="p-8 flex flex-col flex-grow">

        <div class="flex justify-between items-start mb-2">
          <span class="text-primary font-bold text-xs uppercase tracking-widest">${product.brand}</span>
        <div class="flex text-yellow-400 text-xs rating-stars" data-product-id="${product.id}">
            <i class="far fa-star" data-rating-star="1"></i>
            <i class="far fa-star" data-rating-star="2"></i>
            <i class="far fa-star" data-rating-star="3"></i>
            <i class="far fa-star" data-rating-star="4"></i>
            <i class="far fa-star" data-rating-star="5"></i>
            <span class="ml-1 text-slate-400 text-xs font-medium">New</span>
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

function getFilteredProducts() {
  const normalizedBrand = normalizeBrandName(activeBrand);
  return allProducts.filter(product => {
    const matchesBrand = normalizedBrand === 'all'
      ? true
      : normalizeBrandName(product.brand).includes(normalizedBrand);
    const matchesOffer = activeHeroFilter === 'offers'
      ? Boolean(product.has_discount)
      : true;
    return matchesBrand && matchesOffer;
  });
}

function setActiveHeroButton(mode = 'all') {
  document.querySelectorAll('.hero-filter-btn').forEach(button => {
    const isOffersButton = button.textContent.trim().toLowerCase() === 'view offers';
    const isActive = mode === 'offers' ? isOffersButton : !isOffersButton;
    button.classList.toggle('hero-filter-btn-active', isActive);
    button.classList.toggle('hero-filter-btn-inactive', !isActive);
    button.classList.toggle('offers-active', isActive && isOffersButton);
  });
}

function applyBrandFilter(brand, clickedButton = null) {
  activeBrand = brand;
  renderProducts(getFilteredProducts());
  setActiveBrandButton(clickedButton, brand);
}

function applyHeroFilter(mode = 'all') {
  activeHeroFilter = mode;
  renderProducts(getFilteredProducts());
  setActiveHeroButton(mode);
}

async function loadProductRatings(productId) {
  try {
    const data = await apiCall('GET', 'product_reviews.php', {id: productId}, {showLoader: false});
    const stars = document.querySelector(`[data-product-id="${productId}"] .rating-stars`);
    if (stars && data.avg_rating) {
      const rating = parseFloat(data.avg_rating);
      stars.querySelectorAll('[data-rating-star]').forEach((star, i) => {
        if (i + 1 <= rating) star.className = 'fas fa-star';
        else star.className = 'far fa-star';
      });
      stars.querySelector('span').textContent = rating.toFixed(1) + ` (${data.total_reviews})`;
    }
  } catch(e) {}
}

async function loadProducts() {
  console.log('Loading products...');
  try {
    const data = await apiCall('GET', 'products.php');
    console.log('API response:', data);
    allProducts = data.products || data || [];
    console.log('Parsed products:', allProducts.length);
    renderProducts(getFilteredProducts());
    
    // Load ratings
    setTimeout(() => allProducts.forEach(p => loadProductRatings(p.id)), 100);
    
    applyBrandFilter(activeBrand);
    setActiveHeroButton(activeHeroFilter);
    showToast(`Products loaded! (${allProducts.length})`);
  } catch (err) {
    console.error('Load error:', err);
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

// Rating stars loader
async function loadProductRatings(productId) {
  try {
    const data = await apiCall('GET', 'product_reviews.php?id=' + productId, null, {showLoader: false});
    const starsDiv = document.querySelector(`[data-product-id="${productId}"] .rating-stars`);
    if (starsDiv && data.success && data.avg_rating > 0) {
      const rating = parseFloat(data.avg_rating);
      starsDiv.querySelectorAll('[data-rating-star]').forEach((star, i) => {
        star.className = (i + 1 <= rating) ? 'fas fa-star' : 'far fa-star';
      });
      const ratingText = starsDiv.querySelector('span:last-child');
      if (ratingText) ratingText.textContent = rating.toFixed(1) + ` (${data.total_reviews || 0})`;
    }
  } catch (e) {
    console.log('Rating load failed for', productId);
  }
}


// Search + Filter Logic
let searchQuery = '';
let sortBy = 'relevance';
let minPrice = 0;
let maxPrice = Infinity;
let minRating = 0;

function searchProducts() {
  const query = normalizeSearch(searchQuery);
  return allProducts.filter(product => {
    // Search match
    const matchesSearch = !query || 
      normalizeSearch(product.name).includes(query) ||
      normalizeSearch(product.brand).includes(query) ||
      normalizeSearch(product.description || '').includes(query);
    
    // Price match
    const price = parseFloat(product.price);
    const matchesPrice = price >= minPrice && price <= maxPrice;
    
    // Rating match
    const matchesRating = minRating === 0 || product.avg_rating >= minRating;
    
    return matchesSearch && matchesPrice && matchesRating;
  });
}

function normalizeSearch(str) {
  return String(str || '').toLowerCase().trim();
}

function updateResultsCount(count) {
  const countEl = document.getElementById('results-count');
  if (!countEl) return; // No element - safe
  countEl.classList.remove('hidden');
  countEl.textContent = `${count.toLocaleString()} phone${count === 1 ? '' : 's'} found`;
}

function getSortedProducts(products) {
  return products.slice().sort((a, b) => {
    switch (sortBy) {
      case 'price-asc': return parseFloat(a.price) - parseFloat(b.price);
      case 'price-desc': return parseFloat(b.price) - parseFloat(a.price);
      case 'name': return a.name.localeCompare(b.name);
      case 'date': return new Date(b.created_at) - new Date(a.created_at);
      case 'rating': return (b.avg_rating || 0) - (a.avg_rating || 0);
      default: return 0;
    }
  });
}

function getFilteredProducts() {
  const normalizedBrand = normalizeBrandName(activeBrand);
  let filtered = searchProducts();
  
  // Brand filter
  if (normalizedBrand !== 'all') {
    filtered = filtered.filter(product => 
      normalizeBrandName(product.brand).includes(normalizedBrand)
    );
  }
  
  // Hero offer filter
  if (activeHeroFilter === 'offers') {
    filtered = filtered.filter(p => p.has_discount);
  }
  
  const sorted = getSortedProducts(filtered);
  updateResultsCount(sorted.length);
  return sorted;
}

// Event listeners

document.addEventListener('DOMContentLoaded', () => {
  const navbarSearch = document.getElementById('navbar-search');
  const navbarFilter = document.getElementById('navbar-filter');

  const sortSelect = document.getElementById('sort-select');
  const clearBtn = document.getElementById('clear-search');
  const searchToggle = document.getElementById('search-toggle');
  const closeSearch = document.getElementById('close-search');
  const globalSearch = document.getElementById('global-search');
  const searchOverlay = document.getElementById('search-overlay');
  
  // Hero search
  if (navbarSearch) {
    navbarSearch.addEventListener('input', (e) => {
      searchQuery = e.target.value;
      renderProducts(getFilteredProducts());
    });
  }
  
  if (navbarFilter) {
    navbarFilter.addEventListener('change', (e) => {
      const value = e.target.value;
      const map = {
        'Price Low-High': 'price-asc',
        'Price High-Low': 'price-desc',
        'Highest Rated': 'rating',
        'Newest First': 'date',
        'All Products': 'relevance'
      };
      sortBy = map[value] || 'relevance';
      renderProducts(getFilteredProducts());
    });
  }


  
  if (sortSelect) {
    sortSelect.addEventListener('change', (e) => {
      const map = {
        'Price Low-High': 'price-asc',
        'Price High-Low': 'price-desc',
        'Newest First': 'date',
        'Highest Rated': 'rating',
        'Sort: Relevance': 'relevance'
      };
      sortBy = map[e.target.value] || 'relevance';
      renderProducts(getFilteredProducts());
    });
  }
  
  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      searchQuery = '';
      sortBy = 'relevance';
      if (searchInput) searchInput.value = '';
      if (sortSelect) sortSelect.value = 'Sort: Relevance';
      renderProducts(getFilteredProducts());
    });
  }
  
  // Navbar search
  if (searchToggle) {
    searchToggle.addEventListener('click', () => {
      if (searchOverlay) searchOverlay.classList.remove('hidden');
    });
  }
  
  if (closeSearch) {
    closeSearch.addEventListener('click', () => {
      if (searchOverlay) searchOverlay.classList.add('hidden');
    });
  }
  
  if (globalSearch) {
    globalSearch.addEventListener('input', (e) => {
      searchQuery = e.target.value;
      if (searchInput) searchInput.value = searchQuery;
      renderProducts(getFilteredProducts());
      // Sync to hero input
    });
  }
  
  // Close on overlay click
  if (searchOverlay) {
    searchOverlay.addEventListener('click', (e) => {
      if (e.target === searchOverlay) {
        searchOverlay.classList.add('hidden');
      }
    });
  }
  
  // ESC key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !searchOverlay.classList.contains('hidden')) {
      searchOverlay.classList.add('hidden');
    }
  });
});

// Fix ratings after render
function loadRatingsForProducts(products) {
  products.forEach(product => {
    setTimeout(() => loadProductRatings(product.id), Math.random() * 200 + 100);
  });
}

const originalRenderProducts = renderProducts;
renderProducts = function(products) {
  originalRenderProducts(products);
  setTimeout(() => loadRatingsForProducts(products), 200);
};

// Load on page
loadProducts();
setTimeout(() => {
  document.querySelectorAll('[data-product-id]').forEach(el => {
    const id = el.dataset.productId;
    if (id) loadProductRatings(id);
  });
}, 500);

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
    .hero-filter-btn {
      min-height: 56px;
      border: 1px solid transparent;
      box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
    }
    .hero-filter-btn-active {
      background: #6366f1;
      color: #fff;
      box-shadow: 0 18px 35px rgba(99, 102, 241, 0.24);
    }
    .hero-filter-btn-active:hover {
      background: #4f46e5;
      color: #000;
    }
    .hero-filter-btn-inactive {
      background: #fff;
      color: #334155;
      border-color: #e2e8f0;
    }
    .hero-filter-btn-inactive:hover {
      background: #f8fafc;
      color: #0f172a;
    }
    .brand-filter:hover {
      color: #000 !important;
    }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>

