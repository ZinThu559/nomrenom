/**
 * NomReNom - Reservation & Checkout Dynamics
 */

document.addEventListener('DOMContentLoaded', function () {
    const qtyInput = document.getElementById('reservationQty');
    const priceUnitInput = document.getElementById('priceUnit');
    const displaySubtotal = document.getElementById('displaySubtotal');
    const displayTotal = document.getElementById('displayTotal');
    const maxQty = parseInt(qtyInput ? qtyInput.getAttribute('max') : 1, 10);
    const btnMinus = document.getElementById('btnQtyMinus');
    const btnPlus = document.getElementById('btnQtyPlus');

    function updateTotal() {
        if (!qtyInput || !priceUnitInput) return;
        let qty = parseInt(qtyInput.value, 10);
        if (isNaN(qty) || qty < 1) qty = 1;
        if (qty > maxQty) qty = maxQty;
        qtyInput.value = qty;

        const unitPrice = parseFloat(priceUnitInput.value) || 0;
        const total = qty * unitPrice;

        if (displaySubtotal) displaySubtotal.textContent = formatVndJs(total);
        if (displayTotal) displayTotal.textContent = formatVndJs(total);
    }

    if (btnMinus && qtyInput) {
        btnMinus.addEventListener('click', function () {
            let val = parseInt(qtyInput.value, 10) || 1;
            if (val > 1) {
                qtyInput.value = val - 1;
                updateTotal();
            }
        });
    }

    if (btnPlus && qtyInput) {
        btnPlus.addEventListener('click', function () {
            let val = parseInt(qtyInput.value, 10) || 1;
            if (val < maxQty) {
                qtyInput.value = val + 1;
                updateTotal();
            } else {
                alert('Sorry! The maximum available quantity for this food box is ' + maxQty + ' item(s).');
            }
        });
    }

    if (qtyInput) {
        qtyInput.addEventListener('change', updateTotal);
        qtyInput.addEventListener('keyup', updateTotal);
    }

    // Payment method selector visual highlight
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    paymentRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.payment-option-card').forEach(function (card) {
                card.classList.remove('border-success', 'bg-light');
            });
            const parentCard = radio.closest('.payment-option-card');
            if (parentCard) {
                parentCard.classList.add('border-success', 'bg-light');
            }
        });
    });
});
