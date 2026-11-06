(function () {
    const store = window.STORE || { base: '/', csrf: '', cartCount: 0, cartTotal: '' };

    function toast(message) {
        const el = document.getElementById('toast');
        if (!el || !message) return;
        el.textContent = message;
        el.classList.add('show');
        clearTimeout(toast._t);
        toast._t = setTimeout(function () { el.classList.remove('show'); }, 1800);
    }

    function moneyCount(count) {
        const label = count + ' item' + (count === 1 ? '' : 's');
        document.querySelectorAll('.js-cart-count').forEach(function (el) {
            el.textContent = String(count);
            el.hidden = count <= 0;
        });
        document.querySelectorAll('.js-cart-count-label').forEach(function (el) {
            el.textContent = label;
        });
    }

    function setDock(count, totalLabel) {
        moneyCount(count);
        document.querySelectorAll('.js-cart-total').forEach(function (el) {
            el.textContent = totalLabel;
        });
        const dock = document.getElementById('cartDock');
        if (dock) dock.hidden = count <= 0;
        document.body.classList.toggle('has-dock', count > 0 && !!dock);
    }

    function stepper(id, qty) {
        return '<form method="post" action="' + store.base + 'cart.php" class="qty-stepper js-cart-form">' +
            '<input type="hidden" name="csrf" value="' + store.csrf + '">' +
            '<input type="hidden" name="action" value="update">' +
            '<input type="hidden" name="product_id" value="' + id + '">' +
            '<button type="submit" name="qty" value="' + (qty - 1) + '" aria-label="Decrease quantity">−</button>' +
            '<span>' + qty + '</span>' +
            '<button type="submit" name="qty" value="' + (qty + 1) + '" aria-label="Increase quantity">+</button>' +
            '</form>';
    }

    function addButton(id) {
        return '<form method="post" action="' + store.base + 'cart.php" class="js-cart-form">' +
            '<input type="hidden" name="csrf" value="' + store.csrf + '">' +
            '<input type="hidden" name="action" value="add">' +
            '<input type="hidden" name="product_id" value="' + id + '">' +
            '<input type="hidden" name="qty" value="1">' +
            '<button class="btn-add" type="submit">ADD</button></form>';
    }

    function paintCards(productId, qty) {
        document.querySelectorAll('[data-product-id="' + productId + '"]').forEach(function (card) {
            const stock = parseInt(card.dataset.stock || '0', 10);
            const slot = card.querySelector('.pcard-action');
            if (!slot) return;
            if (stock <= 0) {
                slot.innerHTML = '<button class="btn-add" type="button" disabled>Sold out</button>';
            } else if (qty > 0) {
                slot.innerHTML = stepper(productId, qty);
            } else {
                slot.innerHTML = addButton(productId);
            }
        });
    }

    document.addEventListener('submit', function (event) {
        const cartForm = event.target.closest('.js-cart-form');
        if (cartForm) {
            event.preventDefault();
            const data = new FormData(cartForm);
            if (event.submitter && event.submitter.name) {
                data.set(event.submitter.name, event.submitter.value);
            }
            fetch(store.base + 'api/cart.php', {
                method: 'POST',
                body: data,
                headers: { 'X-CSRF-Token': store.csrf, 'Accept': 'application/json' }
            }).then(function (res) { return res.json(); }).then(function (json) {
                if (!json.ok) {
                    toast(json.error || 'Could not update the cart');
                    return;
                }
                if (json.product_id) paintCards(json.product_id, json.line_qty || 0);
                setDock(json.count || 0, json.total_label || '');
                toast(json.message || 'Cart updated');
            }).catch(function () { cartForm.submit(); });
            return;
        }

        const wishForm = event.target.closest('.js-wish-form');
        if (wishForm) {
            event.preventDefault();
            const data = new FormData(wishForm);
            fetch(store.base + 'api/wishlist.php', {
                method: 'POST',
                body: data,
                headers: { 'X-CSRF-Token': store.csrf, 'Accept': 'application/json' }
            }).then(function (res) { return res.json(); }).then(function (json) {
                if (json.login) {
                    window.location = json.login;
                    return;
                }
                if (!json.ok) {
                    toast(json.error || 'Could not update the wishlist');
                    return;
                }
                wishForm.querySelectorAll('.wish').forEach(function (btn) {
                    btn.classList.toggle('on', !!json.on);
                });
                toast(json.message || 'Saved');
            }).catch(function () { wishForm.submit(); });
        }
    });

    const search = document.querySelector('.search input');
    const box = document.querySelector('.suggest');
    if (search && box) {
        let timer;
        search.addEventListener('input', function () {
            clearTimeout(timer);
            const q = search.value.trim();
            if (q.length < 2) {
                box.hidden = true;
                return;
            }
            const esc = function (value) {
                return String(value).replace(/[&<>"']/g, function (ch) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
                });
            };
            timer = setTimeout(function () {
                fetch(store.base + 'api/search.php?q=' + encodeURIComponent(q))
                    .then(function (res) { return res.json(); })
                    .then(function (json) {
                        const items = json.items || [];
                        const searchUrl = store.base + 'search.php?q=' + encodeURIComponent(q);
                        if (!items.length) {
                            box.innerHTML = '<a href="' + searchUrl + '">Search “' + esc(q) + '”</a>';
                        } else {
                            box.innerHTML = items.map(function (item) {
                                return '<a href="' + esc(item.url) + '"><span>' + esc(item.emoji) + ' ' + esc(item.name) + '</span><strong>' + esc(item.price) + '</strong></a>';
                            }).join('') + '<a class="all" href="' + searchUrl + '">See all results</a>';
                        }
                        box.hidden = false;
                    }).catch(function () { box.hidden = true; });
            }, 180);
        });
        document.addEventListener('click', function (event) {
            if (!event.target.closest('.search')) box.hidden = true;
        });
    }

    document.querySelectorAll('[data-fill-coupon]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.querySelector('[name="coupon"]');
            if (input) input.value = btn.getAttribute('data-fill-coupon');
        });
    });

    const bulkForm = document.querySelector('[data-bulk]');
    if (bulkForm) {
        const out = bulkForm.querySelector('[data-bulk-total]');
        const refresh = function () {
            let total = 0;
            let count = 0;
            bulkForm.querySelectorAll('[data-price]').forEach(function (input) {
                const qty = Math.max(0, parseInt(input.value || '0', 10) || 0);
                total += qty * parseFloat(input.dataset.price || '0');
                count += qty;
            });
            if (out) {
                out.textContent = count + ' items · about ₹' + Math.round(total).toLocaleString('en-IN');
            }
        };
        bulkForm.addEventListener('input', refresh);
        refresh();
    }
})();
