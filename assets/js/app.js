// MobiWorld API Client & UI Helpers
const APP_BASE_URL = (window.APP_BASE_URL || '').replace(/\/$/, '');
const API_BASE = (APP_BASE_URL ? APP_BASE_URL + '/' : '/') + 'api/';

function normalizeApiEndpoint(endpoint) {
  const cleaned = String(endpoint || '').trim().replace(/^\.?\//, '');
  return cleaned.replace(/^api\//, '');
}

async function apiCall(method, endpoint, data = null, options = {}) {
  const { redirectOn401 = true, showLoader = true, suppressSuccessToast = false } = options;
  if (showLoader) {
    showLoading(true);
  }
  try {
    const normalizedEndpoint = normalizeApiEndpoint(endpoint);
    const res = await fetch(API_BASE + normalizedEndpoint, {
      method: method.toUpperCase(),
      headers: {
        'Content-Type': 'application/json',
      },
      body: data ? JSON.stringify(data) : null,
      credentials: 'same-origin'
    });
    const rawText = await res.text();
    const result = rawText ? JSON.parse(rawText) : {};
    if (showLoader) {
      showLoading(false);
    }
    if (res.status === 401) {
      if (redirectOn401) {
        showToast(result.error || 'Please login first', 'error');
        setTimeout(() => {
          window.location.href = (APP_BASE_URL ? APP_BASE_URL + '/' : '/') + 'login.php';
        }, 800);
      }
      throw new Error(result.error || 'Unauthorized');
    }
    if (res.ok && result.success) {
      if (result.message && !suppressSuccessToast) {
        showToast(result.message, 'success', 'Success');
      }
      return result;
    } else {
      showToast(result.error || 'Error occurred', 'error', 'Request Failed');
      throw new Error(result.error);
    }
  } catch (err) {
    if (showLoader) {
      showLoading(false);
    }
    if (!(err && err.message === 'Unauthorized' && !redirectOn401)) {
      showToast('Network error: ' + err.message, 'error', 'Connection Problem');
    }
    throw err;
  }
}

function getToastContainer() {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'fixed top-4 right-4 z-[9999] flex w-[min(92vw,24rem)] flex-col gap-3';
    document.body.appendChild(container);
  }
  return container;
}

function showToast(message, type = 'success', title = '') {
  const container = getToastContainer();
  const toast = document.createElement('div');
  const config = type === 'success'
    ? {
        shell: 'border-emerald-200 bg-white/95 text-slate-900',
        chip: 'bg-emerald-500 text-white',
        bar: 'bg-emerald-500',
        icon: 'fa-check'
      }
    : {
        shell: 'border-rose-200 bg-white/95 text-slate-900',
        chip: 'bg-rose-500 text-white',
        bar: 'bg-rose-500',
        icon: 'fa-exclamation'
      };

  toast.className = `overflow-hidden rounded-2xl border shadow-2xl backdrop-blur transition-all duration-300 translate-x-6 opacity-0 ${config.shell}`;
  toast.innerHTML = `
    <div class="relative p-4">
      <div class="flex items-start gap-3">
        <div class="mt-0.5 flex h-10 w-10 items-center justify-center rounded-2xl shadow-sm ${config.chip}">
          <i class="fas ${config.icon} text-sm"></i>
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-extrabold tracking-wide text-slate-800">${title || (type === 'success' ? 'Success' : 'Error')}</p>
          <p class="mt-1 text-sm leading-6 text-slate-600">${message}</p>
        </div>
        <button type="button" class="toast-close rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
          <i class="fas fa-xmark"></i>
        </button>
      </div>
      <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-100">
        <div class="toast-progress h-full rounded-full ${config.bar}"></div>
      </div>
    </div>
  `;

  container.appendChild(toast);

  requestAnimationFrame(() => {
    toast.classList.remove('translate-x-6', 'opacity-0');
  });

  const removeToast = () => {
    toast.classList.add('translate-x-6', 'opacity-0');
    setTimeout(() => toast.remove(), 250);
  };

  toast.querySelector('.toast-close')?.addEventListener('click', removeToast);

  const progress = toast.querySelector('.toast-progress');
  if (progress) {
    progress.animate(
      [
        { transform: 'translateX(0%)' },
        { transform: 'translateX(-100%)' }
      ],
      {
        duration: 3200,
        easing: 'linear',
        fill: 'forwards'
      }
    );
  }

  setTimeout(removeToast, 3200);
}

function setBadgeCount(selector, count) {
  const badges = document.querySelectorAll(selector);
  badges.forEach(badge => {
    badge.textContent = count;
    badge.classList.toggle('hidden', count === 0);
    if (count === 0) {
      badge.classList.remove('inline-flex', 'flex');
    } else {
      badge.classList.add('inline-flex');
    }
  });
}

function getBadgeCount(selector) {
  const badge = document.querySelector(selector);
  if (!badge) return 0;
  const count = parseInt(badge.textContent || '0', 10);
  return Number.isNaN(count) ? 0 : count;
}

function bumpBadgeCount(selector, delta) {
  const nextCount = Math.max(0, getBadgeCount(selector) + delta);
  setBadgeCount(selector, nextCount);
  return nextCount;
}

function getProductName(productId) {
  const card = document.querySelector(`[data-product-id="${productId}"]`);
  const cardTitle = card?.querySelector('h3');
  if (cardTitle?.textContent?.trim()) {
    return cardTitle.textContent.trim();
  }

  const pageTitle = document.querySelector('h1');
  if (pageTitle?.textContent?.trim()) {
    return pageTitle.textContent.trim();
  }

  return 'Product';
}

function showLoading(show = true) {
  let loader = document.getElementById('loader');
  if (show) {
    if (!loader) {
      loader = document.createElement('div');
      loader.id = 'loader';
      loader.className = 'fixed inset-0 bg-slate-900/20 backdrop-blur-sm flex items-center justify-center z-[9998]';
      loader.innerHTML = `
        <div class="w-16 h-16 border-4 border-primary/30 border-t-primary rounded-full animate-spin"></div>
      `;
      document.body.appendChild(loader);
    }
    loader.classList.add('flex');
    loader.classList.remove('hidden');
  } else if (loader) {
    loader.classList.add('hidden');
  }
}

function updateCartCount() {
  apiCall('GET', 'cart_get.php', null, { redirectOn401: false, showLoader: false })
    .then(data => {
      let count = 0;
      if (data.cart) data.cart.forEach(item => count += item.quantity);
      setBadgeCount('.cart-count', count);
    }).catch(() => {}); // Silent
}

// Wishlist functions
async function toggleWishlist(productId) {
  if (!productId) return;

  const trigger = arguments[1] || null;
  const btn = trigger || document.querySelector(`[data-product-id="${productId}"] .wishlist-btn`);
  if (!btn) return;
  const wasLiked = btn.classList.contains('liked');
  const icon = btn.querySelector('i');
  const productName = getProductName(productId);

  try {
    btn.disabled = true;
    const result = await apiCall('POST', 'wishlist_toggle.php', { product_id: productId }, { suppressSuccessToast: true });

    const nowLiked = result.in_wishlist;
    btn.classList.toggle('liked', nowLiked);
    if (icon) {
      icon.className = nowLiked ? 'fas fa-heart font-bold' : 'far fa-heart font-bold';
    }

    if (nowLiked !== wasLiked) {
      bumpBadgeCount('.wishlist-count', nowLiked ? 1 : -1);
    }
    showToast(
      nowLiked ? `${productName} has been added to your wishlist.` : `${productName} has been removed from your wishlist.`,
      'success',
      nowLiked ? 'Wishlist Updated' : 'Removed from Wishlist'
    );
    updateWishlistCount();

  } catch (err) {
    // Toast handled by apiCall
  } finally {
    btn.disabled = false;
  }
}

async function updateWishlistCount() {
  try {
    const data = await apiCall('GET', 'wishlist_get.php', null, { redirectOn401: false, showLoader: false });
    setBadgeCount('.wishlist-count', data.count || 0);
  } catch (err) {
    // Silent fail
  }
}

// Load review functions
if (typeof loadProductReviews !== 'undefined') {
  const productContainer = document.querySelector('[data-product-id]');
  if (productContainer) {
    const productId = productContainer.dataset.productId;
    loadProductReviews(productId, 'product-reviews-container');
  }
}

// Init
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    updateCartCount();
    updateWishlistCount();
  });
} else {
  updateCartCount();
  updateWishlistCount();
}
