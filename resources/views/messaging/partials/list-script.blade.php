@once
<script>
(function () {
    // Filter the chat list as you type.
    var input = document.querySelector('[data-msg-search]');
    if (!input) { return; }
    var items = Array.prototype.slice.call(document.querySelectorAll('[data-msg-item]'));
    var none = document.querySelector('[data-msg-search-none]');
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        var shown = 0;
        items.forEach(function (el) {
            var hit = !q || el.textContent.toLowerCase().indexOf(q) !== -1;
            el.style.display = hit ? '' : 'none';
            if (hit) { shown++; }
        });
        if (none) { none.style.display = shown ? 'none' : 'block'; }
    });
})();
</script>
@endonce
