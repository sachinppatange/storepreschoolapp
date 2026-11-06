<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $result = print_add($_POST, $_FILES['file'] ?? []);
    flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Print added to your cart.' : $result['error']);
    redirect($result['ok'] ? url('cart.php') : url('print.php'));
}

$pageTitle = 'Print on demand';
$metaDesc = 'Print school documents and photos, packed at the nearest dark store.';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap" style="max-width:760px">
    <div class="page-head">
        <h1>Print on demand</h1>
        <p class="muted">Upload a file. The nearest dark store prints it and delivers it to the school.</p>
    </div>
    <form method="post" enctype="multipart/form-data" class="panel" id="printForm">
        <?= csrf_field() ?>
        <div class="print-choice">
            <label><input type="radio" name="kind" value="document" checked> Document</label>
            <label><input type="radio" name="kind" value="photo"> Photo</label>
        </div>
        <label class="field">File<input type="file" name="file" accept="application/pdf,image/jpeg,image/png,image/webp" required></label>
        <p class="small muted">PDF, JPG, PNG or WEBP. Up to 8 MB. One price, no binding options.</p>
        <div class="two">
            <label class="field" id="pageField">Pages in the file<input type="number" name="pages" min="1" max="100" value="1" required></label>
            <label class="field">Copies<input type="number" name="copies" min="1" max="50" value="1" required></label>
        </div>
        <label class="check" id="colorField"><input type="checkbox" name="color" value="1"> Colour printing</label>
        <p class="print-total" id="printTotal">₹2</p>
        <p class="small muted" id="printRate">Black and white documents are ₹2 a page. Colour is ₹5 a page. A photo is ₹15 a copy.</p>
        <button class="btn btn-green" type="submit">Add print to cart</button>
    </form>
</div>
<script>
(function () {
    const form = document.getElementById('printForm');
    const total = document.getElementById('printTotal');
    const pageField = document.getElementById('pageField');
    const colorField = document.getElementById('colorField');
    function money(amount) {
        return '₹' + amount.toLocaleString('en-IN');
    }
    function refresh() {
        const photo = form.kind.value === 'photo';
        pageField.hidden = photo;
        colorField.hidden = photo;
        const pages = photo ? 1 : Math.min(100, Math.max(1, parseInt(form.pages.value, 10) || 1));
        const copies = Math.min(50, Math.max(1, parseInt(form.copies.value, 10) || 1));
        const color = !photo && form.color.checked;
        const rate = photo ? 15 : (color ? 5 : 2);
        total.textContent = money(rate * pages * copies);
    }
    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    refresh();
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
