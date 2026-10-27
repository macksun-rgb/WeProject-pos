/**
 * assets/js/pos.js
 * Cart logic for the POS (cashier) page. Depends on Bootstrap's modal JS
 * and on markup/data-attributes rendered by pages/pos.php.
 */

let cart = [];
let orderNumber = '';

document.getElementById('searchProduct')?.addEventListener('keyup', function() {
    const search = this.value.toLowerCase();
    document.querySelectorAll('.product-item').forEach(item => {
        const name = item.dataset.name || '';
        item.style.display = name.includes(search) ? '' : 'none';
    });
});

function loadCart() {
    try {
        const saved = localStorage.getItem('pos_cart');
        if (saved) cart = JSON.parse(saved);
    } catch(e) { cart = []; }
    renderCart();
}

function saveCart() {
    localStorage.setItem('pos_cart', JSON.stringify(cart));
}

document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.onclick = function(e) {
        e.preventDefault();
        const id = this.dataset.id;
        const name = this.dataset.name;
        const price = parseInt(this.dataset.price);
        const stock = parseInt(this.dataset.stock);

        const exist = cart.find(i => i.id == id);
        if (exist) {
            if (exist.qty < stock) {
                exist.qty++;
                showToast(`ເພີ່ມ ${name} ອີກ 1 ລາຍການ`, 'success');
            } else {
                showToast(`ສິນຄ້າ ${name} ມີໃນສຕ໋ອກ ${stock} ເທົ່ານັ້ນ`, 'warning');
            }
        } else {
            if (stock > 0) {
                cart.push({id, name, price, qty: 1});
                showToast(`ເພີ່ມ ${name} ເຂົ້າກະຕ່າ`, 'success');
            } else {
                showToast(`ສິນຄ້າ ${name} ໝົດສຕ໋ອກ`, 'danger');
            }
        }
        renderCart();
        saveCart();
    };
});

function renderCart() {
    const container = document.getElementById('cart-items');
    let total = 0, count = 0;
    let html = '';

    if (cart.length === 0) {
        html = `<div class="text-center text-muted py-4">
            <i class="fas fa-cart-plus fa-2x mb-2 d-block" style="color: #ddd;"></i>
            <small>ຍັງບໍ່ມີສິນຄ້າ</small>
        </div>`;
    } else {
        cart.forEach(item => {
            const sum = item.price * item.qty;
            total += sum;
            count += item.qty;
            html += `
                <div class="cart-item">
                    <div class="item-info">
                        <div class="item-name">${escapeHtml(item.name)}</div>
                        <div class="item-meta">${formatNumber(item.price)} ₭ × ${item.qty} = <strong class="text-coffee">${formatNumber(sum)} ₭</strong></div>
                    </div>
                    <div class="item-actions">
                        <button class="qty-btn" onclick="updateQty('${item.id}', -1)"><i class="fas fa-minus"></i></button>
                        <span class="fw-bold" style="min-width:24px;text-align:center;">${item.qty}</span>
                        <button class="qty-btn" onclick="updateQty('${item.id}', 1)"><i class="fas fa-plus"></i></button>
                        <button class="qty-btn danger" onclick="removeItem('${item.id}')"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `;
        });
    }

    container.innerHTML = html;
    document.getElementById('total-cart').innerText = formatNumber(total) + ' ₭';
    document.getElementById('item-count').innerText = count + ' ລາຍການ';
    document.getElementById('cart-data').value = JSON.stringify(cart);
}

window.updateQty = function(id, delta) {
    const item = cart.find(i => i.id == id);
    if (item) {
        item.qty += delta;
        if (item.qty <= 0) {
            removeItem(id);
        } else {
            renderCart();
            saveCart();
        }
    }
};

window.removeItem = function(id) {
    const item = cart.find(i => i.id == id);
    if (item && confirm(`ຕ້ອງການລຶບ ${item.name} ອອກຈາກກະຕ່າ?`)) {
        cart = cart.filter(i => i.id != id);
        renderCart();
        saveCart();
        showToast(`ລຶບ ${item.name} ອອກແລ້ວ`, 'info');
    }
};

document.getElementById('clear-cart-btn')?.addEventListener('click', function() {
    if (cart.length > 0 && confirm('ຕ້ອງການລ້າງກະຕ່າທັງໝົດ?')) {
        cart = [];
        renderCart();
        saveCart();
        showToast('ລ້າງກະຕ່າສຳເລັດ', 'info');
    }
});

const paymentMethod = document.getElementById('payment-method');
const cashContainer = document.getElementById('cash-given-container');
const cashGiven = document.getElementById('cash-given');
const changeDiv = document.getElementById('change-amount');

paymentMethod?.addEventListener('change', function() {
    if (this.value === 'cash') {
        cashContainer.style.display = 'block';
        cashGiven.required = true;
        cashGiven.addEventListener('input', calcChange);
    } else {
        cashContainer.style.display = 'none';
        changeDiv.style.display = 'none';
        cashGiven.required = false;
    }
});

function calcChange() {
    const total = parseInt(document.getElementById('total-cart').innerText.replace(/[^0-9]/g, ''));
    const given = parseInt(cashGiven.value);
    if (given >= total && total > 0) {
        changeDiv.style.display = 'block';
        changeDiv.innerHTML = `<i class="fas fa-exchange-alt"></i> ເງິນທອນ: ${formatNumber(given - total)} ₭`;
    } else {
        changeDiv.style.display = 'none';
    }
}

document.getElementById('order-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    if (cart.length === 0) {
        showToast('ກະລຸນາເລືອກສິນຄ້າກ່ອນ', 'warning');
        return;
    }

    if (paymentMethod.value === 'cash') {
        const total = parseInt(document.getElementById('total-cart').innerText.replace(/[^0-9]/g, ''));
        const given = parseInt(cashGiven.value);
        if (!given || given < total) {
            showToast('ເງິນທີ່ລູກຄ້າໃຫ້ບໍ່ພຽງພໍ', 'danger');
            return;
        }
    }

    const btn = document.getElementById('save-order-btn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ກຳລັງບັນທຶກ...';
    btn.disabled = true;

    const formData = new FormData(this);
    formData.set('cart_data', JSON.stringify(cart));

    try {
        const response = await fetch('save_order.php', { method: 'POST', body: formData });

        if (!response.ok) {
            const text = await response.text();
            throw new Error('Server error ' + response.status + ': ' + text.slice(0, 300));
        }

        const raw = await response.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (parseErr) {
            throw new Error('save_order.php did not return valid JSON. Raw response: ' + raw.slice(0, 300));
        }

        if (data.success) {
            orderNumber = data.order_number;
            showReceipt(orderNumber);
            cart = [];
            renderCart();
            saveCart();
            if (cashGiven) cashGiven.value = '';
            showToast('ບັນທຶກຄຳສັ່ງຊື້ສຳເລັດ!', 'success');
        } else {
            showToast(data.message || 'ເກີດຂໍ້ຜິດພາດ', 'danger');
        }
    } catch (error) {
        showToast('ເກີດຂໍ້ຜິດພາດ: ' + error.message, 'danger');
        console.error(error);
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});

function showReceipt(orderNumber) {
    const content = document.getElementById('receipt-content');
    let total = 0;
    let itemsHtml = '';

    cart.forEach(item => {
        const sum = item.price * item.qty;
        total += sum;
        itemsHtml += `
            <tr>
                <td>${escapeHtml(item.name)}</td>
                <td class="text-center">${item.qty}</td>
                <td class="text-end">${formatNumber(item.price)}</td>
                <td class="text-end">${formatNumber(sum)}</td>
            </tr>
        `;
    });

    content.innerHTML = `
        <div class="receipt-header">
            <h5>☕ KaTunYu Coffee</h5>
            <small>${new Date().toLocaleString('lo-LA')}</small><br>
            <strong>ເລກທີ: ${orderNumber}</strong>
        </div>
        <table class="table receipt-items table-sm">
            <thead>
                <tr><th>ສິນຄ້າ</th><th class="text-center">ຈຳນວນ</th><th class="text-end">ລາຄາ</th><th class="text-end">ລວມ</th></tr>
            </thead>
            <tbody>${itemsHtml}</tbody>
            <tfoot>
                <tr class="receipt-total">
                    <td colspan="3" class="text-end">ລວມທັງໝົດ:</td>
                    <td class="text-end text-coffee">${formatNumber(total)} ₭</td>
                </tr>
            </tfoot>
        </table>
        <div class="text-center mt-3"><small>ຂອບໃຈທີ່ໃຊ້ບໍລິການ 🙏</small></div>
    `;

    new bootstrap.Modal(document.getElementById('receiptModal')).show();
}

// showToast(), formatNumber(), escapeHtml() now live in assets/js/utils.js
// (loaded globally in includes/footer.php) so other pages, like the
// order-detail modal on pages/orders.php, can use them too.

loadCart();
