function reviewStars(rating) {
  const safeRating = Math.max(0, Math.min(5, Number(rating) || 0));
  return `${'&#9733;'.repeat(safeRating)}${'&#9734;'.repeat(5 - safeRating)}`;
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function refreshReviewCta(orderItemId, rating) {
  const card = document.querySelector(`[data-order-item-id="${orderItemId}"]`);
  if (!card) return;

  const button = card.querySelector('button[onclick*="showReviewModal"]');
  if (!button) return;

  const badge = document.createElement('span');
  badge.className = 'inline-flex items-center px-3 py-1 bg-green-50 text-xs font-bold text-green-700 rounded-full whitespace-nowrap';
  badge.innerHTML = `<i class="fas fa-check-circle mr-1"></i> Reviewed ${'&#9733;'.repeat(Math.max(1, Math.min(5, rating)))}`;
  button.replaceWith(badge);
}

async function loadProductReviews(productId, containerId) {
  const container = document.getElementById(containerId);
  if (!container || !productId) return;

  try {
    const data = await apiCall('GET', `product_reviews.php?id=${productId}`, null, {
      showLoader: false,
      redirectOn401: false
    });

    if (!data.total_reviews) {
      container.innerHTML = `
        <div class="rounded-[1.5rem] bg-white px-6 py-10 text-center shadow-sm">
          <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
            <i class="fas fa-star text-xl text-slate-400"></i>
          </div>
          <p class="text-slate-500 italic">No reviews yet. Delivered customers will be able to rate this product first.</p>
        </div>
      `;
      return;
    }

    const reviewsHtml = data.reviews.map(review => `
      <article class="rounded-[1.5rem] border border-slate-100 bg-white p-5 shadow-sm">
        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div class="text-lg text-yellow-400">${reviewStars(review.rating)}</div>
            <p class="font-bold text-slate-800">${escapeHtml(review.reviewer_name)}</p>
          </div>
          <span class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">${escapeHtml(review.created_at)}</span>
        </div>
        ${review.review_text ? `<p class="text-sm leading-7 text-slate-600">${escapeHtml(review.review_text)}</p>` : '<p class="text-sm italic text-slate-400">Rated without written review.</p>'}
      </article>
    `).join('');

    container.innerHTML = `
      <div class="mb-6 rounded-[1.5rem] bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm font-bold uppercase tracking-[0.25em] text-slate-400">Average Rating</p>
            <div class="mt-2 flex items-center gap-3">
              <span class="text-3xl font-extrabold text-slate-800">${escapeHtml(data.avg_rating)}</span>
              <span class="text-2xl text-yellow-400">${reviewStars(Math.round(data.avg_rating))}</span>
            </div>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-500">
            ${escapeHtml(data.total_reviews)} verified review${Number(data.total_reviews) === 1 ? '' : 's'}
          </div>
        </div>
      </div>
      <div class="space-y-4">${reviewsHtml}</div>
    `;
  } catch (err) {
    container.innerHTML = `
      <div class="rounded-[1.5rem] border border-red-100 bg-red-50 px-6 py-8 text-center text-sm font-semibold text-red-600">
        Reviews could not be loaded right now.
      </div>
    `;
  }
}

function initStarRating(container, onRatingSelect) {
  const stars = Array.from(container.querySelectorAll('.star'));
  let selectedRating = 0;

  const paintStars = (activeCount) => {
    stars.forEach((star, index) => {
      star.classList.toggle('text-yellow-400', index < activeCount);
      star.classList.toggle('text-slate-300', index >= activeCount);
    });
  };

  paintStars(0);

  stars.forEach((star, index) => {
    star.style.cursor = 'pointer';
    star.addEventListener('mouseenter', () => paintStars(index + 1));
    star.addEventListener('click', () => {
      selectedRating = index + 1;
      paintStars(selectedRating);
      onRatingSelect(selectedRating);
    });
  });

  container.addEventListener('mouseleave', () => paintStars(selectedRating));
}

async function submitReview(orderItemId, productName, productId) {
  const rating = parseInt(document.getElementById('review-rating-value')?.textContent || '0', 10);
  const reviewText = document.getElementById('review-text')?.value.trim() || '';

  if (rating < 1 || rating > 5) {
    showToast('Rating dena compulsory hai. Please 1 se 5 stars select karo.', 'error', 'Rating Required');
    return;
  }

  try {
    await apiCall('POST', 'reviews_submit.php', {
      order_item_id: orderItemId,
      rating,
      review_text: reviewText || null
    });

    document.getElementById('review-modal')?.remove();
    refreshReviewCta(orderItemId, rating);

    if (productId) {
      loadProductReviews(productId, 'product-reviews-container');
    }

    showToast(`Thanks for rating ${productName} with ${rating} star${rating === 1 ? '' : 's'}.`, 'success', 'Review Submitted');
  } catch (err) {
    // Toast already handled by apiCall
  }
}

function showReviewModal(orderItemId, productName, productId) {
  const safeProductName = String(productName || 'this product');
  const modal = document.createElement('div');
  modal.id = 'review-modal';
  modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm';
  modal.innerHTML = `
    <div class="w-full max-w-lg overflow-hidden rounded-[2rem] bg-white shadow-2xl">
      <div class="border-b border-slate-100 px-6 py-5 sm:px-8">
        <p class="text-xs font-bold uppercase tracking-[0.25em] text-primary">Verified Purchase</p>
        <h3 class="mt-2 text-2xl font-extrabold text-slate-800">Rate & Review</h3>
        <p class="mt-2 text-sm text-slate-500">${escapeHtml(safeProductName)}</p>
      </div>
      <div class="px-6 py-6 sm:px-8">
        <label class="mb-3 block text-sm font-bold text-slate-700">Your rating <span class="text-red-500">*</span></label>
        <div id="star-rating-container" class="mb-6 flex items-center gap-4">
          <div class="flex text-4xl leading-none">
            <button type="button" class="star px-1 text-slate-300 transition hover:text-yellow-400" aria-label="1 star">&#9733;</button>
            <button type="button" class="star px-1 text-slate-300 transition hover:text-yellow-400" aria-label="2 stars">&#9733;</button>
            <button type="button" class="star px-1 text-slate-300 transition hover:text-yellow-400" aria-label="3 stars">&#9733;</button>
            <button type="button" class="star px-1 text-slate-300 transition hover:text-yellow-400" aria-label="4 stars">&#9733;</button>
            <button type="button" class="star px-1 text-slate-300 transition hover:text-yellow-400" aria-label="5 stars">&#9733;</button>
          </div>
          <span id="review-rating-value" class="min-w-[2rem] text-lg font-extrabold text-slate-400">0</span>
        </div>

        <label for="review-text" class="mb-3 block text-sm font-bold text-slate-700">Write review <span class="text-slate-400 font-semibold">(optional)</span></label>
        <textarea id="review-text" rows="5" placeholder="Aapko product kaisa laga? Delivery, performance, camera, battery, sab share kar sakte ho."
          class="w-full rounded-[1.5rem] border border-slate-200 px-4 py-4 text-sm text-slate-700 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"></textarea>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
          <button type="button" class="rounded-2xl px-5 py-3 text-sm font-bold text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" data-close-review-modal>
            Cancel
          </button>
          <button type="button" class="rounded-2xl bg-gradient-to-r from-yellow-400 to-yellow-500 px-6 py-3 text-sm font-bold text-slate-900 shadow-lg shadow-yellow-100 transition hover:shadow-xl"
            data-submit-review>
            Submit Review
          </button>
        </div>
      </div>
    </div>
  `;

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      modal.remove();
    }
  });

  modal.querySelector('[data-close-review-modal]')?.addEventListener('click', () => modal.remove());
  modal.querySelector('[data-submit-review]')?.addEventListener('click', () => submitReview(orderItemId, safeProductName, productId));

  document.body.appendChild(modal);

  initStarRating(modal.querySelector('#star-rating-container'), (rating) => {
    const ratingElement = modal.querySelector('#review-rating-value');
    if (ratingElement) {
      ratingElement.textContent = String(rating);
      ratingElement.classList.remove('text-slate-400');
      ratingElement.classList.add('text-slate-800');
    }
  });
}

async function deleteAdminReview(reviewId) {
  if (!window.confirm('Delete this review permanently?')) return;

  try {
    await apiCall('POST', 'admin_review_delete.php', { review_id: reviewId });
    window.location.reload();
  } catch (err) {
    // Toast already handled by apiCall
  }
}

function initReviewPage() {
  const productContainer = document.querySelector('[data-product-id]');
  const reviewContainer = document.getElementById('product-reviews-container');

  if (productContainer && reviewContainer) {
    loadProductReviews(productContainer.dataset.productId, 'product-reviews-container');
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initReviewPage);
} else {
  initReviewPage();
}
