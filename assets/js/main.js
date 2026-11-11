/**
 * NomReNom - Main Client JavaScript & Interactive Cart Engine
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Enable Bootstrap tooltips if any
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 2. Auto-dismiss standard alerts after 7 seconds
    setTimeout(function () {
        var alerts = document.querySelectorAll('.flash-container .alert:not(.alert-permanent)');
        alerts.forEach(function (alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 7000);

    // 3. Navbar scroll background enhancement
    var navbar = document.querySelector('.navbar-nomrenom');
    if (navbar) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 30) {
                navbar.classList.add('shadow-sm');
                navbar.style.backgroundColor = 'rgba(253, 251, 248, 0.98)';
            } else {
                navbar.classList.remove('shadow-sm');
                navbar.style.backgroundColor = 'rgba(253, 251, 248, 0.95)';
            }
        });
    }

    // 4. Base URL Resolution Helper
    const baseMeta = document.querySelector('meta[name="base-url"]');
    const baseUrl = baseMeta ? baseMeta.getAttribute('content').replace(/\/+$/, '') : '/nomrenom';

    // 5. Global Cart Badge Updater
    function updateCartBadge(count) {
        const badge = document.getElementById('nav-cart-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
        const mobileBadge = document.querySelector('.mobile-bottom-nav .badge');
        if (mobileBadge) {
            mobileBadge.textContent = count;
            mobileBadge.style.display = count > 0 ? 'inline-block' : 'none';
        }
    }

    // 6. Floating Cart Toast Notification
    function showCartToast(title, message) {
        let container = document.getElementById('cart-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'cart-toast-container';
            container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'cart-toast shadow-lg rounded-4 p-3 d-flex align-items-center gap-3 bg-white border border-success';
        toast.style.cssText = 'pointer-events:auto;min-width:320px;max-width:420px;box-shadow:0 12px 32px rgba(15,76,58,0.18);transition:all 0.3s cubic-bezier(0.16,1,0.3,1);transform:translateY(20px);opacity:0;';
        toast.innerHTML = `
            <div class="p-2 rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;">
                <i class="bi bi-cart-check-fill fs-5"></i>
            </div>
            <div class="flex-grow-1" style="min-width:0;">
                <strong class="d-block text-dark small fw-bold mb-0">${title}</strong>
                <span class="text-muted small d-block text-truncate">${message}</span>
            </div>
            <a href="${baseUrl}/cart.php" class="btn btn-sm btn-nrn-primary rounded-pill px-3 py-1 text-nowrap fw-bold">
                View Cart
            </a>
        `;

        container.appendChild(toast);

        // Animate In
        requestAnimationFrame(() => {
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
        });

        // Auto dismiss after 4.5 seconds
        setTimeout(() => {
            toast.style.transform = 'translateY(20px)';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 4500);
    }

    // 7. Quick Add to Cart Handler (Catalog & Home Grid: .btn-quick-add)
    window.nomrenomAddToCart = function (e, btn) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (!btn || btn.disabled) return;

        const productId = btn.getAttribute('data-id');
        const productName = btn.getAttribute('data-name') || 'Snack Item';
        const originalHtml = btn.getAttribute('data-orig-html') || btn.innerHTML;
        btn.setAttribute('data-orig-html', originalHtml);

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Adding...';

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('qty', 1);

        fetch(baseUrl + '/api/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateCartBadge(data.cart_count);

                btn.classList.remove('btn-outline-success');
                btn.classList.add('btn-success', 'text-white');
                btn.innerHTML = '<i class="bi bi-check2"></i> Added!';

                showCartToast('Added to Cart! 🛒', productName);

                setTimeout(() => {
                    btn.classList.remove('btn-success', 'text-white');
                    btn.classList.add('btn-outline-success');
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }, 1600);
            } else {
                alert(data.message || 'Could not add item to cart.');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error('Cart add error:', err);
            alert('Network error adding to cart. Please try again.');
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    };

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-quick-add');
        if (!btn) return;
        window.nomrenomAddToCart(e, btn);
    });

    // 8. Product Details Add to Cart Handler (#btnDetailAddToCart)
    const detailAddBtn = document.getElementById('btnDetailAddToCart');
    if (detailAddBtn) {
        detailAddBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name') || 'Snack Item';
            const qtyInput = document.getElementById('reservationQty');
            const qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;
            const originalHtml = this.innerHTML;

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Adding to Cart...';

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('qty', qty);

            fetch(baseUrl + '/api/cart.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updateCartBadge(data.cart_count);

                    this.classList.remove('btn-outline-success');
                    this.classList.add('btn-success', 'text-white');
                    this.innerHTML = '<i class="bi bi-check2-circle me-2"></i> Added to Cart!';

                    showCartToast(`Added ${qty}x to Cart! 🛒`, productName);

                    setTimeout(() => {
                        this.classList.remove('btn-success', 'text-white');
                        this.classList.add('btn-outline-success');
                        this.innerHTML = originalHtml;
                        this.disabled = false;
                    }, 1800);
                } else {
                    alert(data.message || 'Could not add item to cart.');
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }
            })
            .catch(err => {
                console.error('Detail add to cart error:', err);
                alert('Network error adding to cart. Please try again.');
                this.innerHTML = originalHtml;
                this.disabled = false;
            });
        });
    }
});

/**
 * Format raw number to VND string in browser
 * @param {number} amount 
 * @returns {string}
 */
function formatVndJs(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' ₫';
}
