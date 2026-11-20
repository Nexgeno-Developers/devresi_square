{{-- One-time token checked by PreventDuplicateSubmission; the script disables the button after the first submit. --}}
<input type="hidden" name="{{ \App\Http\Middleware\PreventDuplicateSubmission::FIELD }}" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
@once
    <script>
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form.querySelector('input[name="{{ \App\Http\Middleware\PreventDuplicateSubmission::FIELD }}"]')) return;
            if (form.dataset.submitted === '1') { event.preventDefault(); return; }
            setTimeout(function () {
                if (event.defaultPrevented) return;
                form.dataset.submitted = '1';
                form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (b) { b.disabled = true; });
            }, 0);
        }, true);
    </script>
@endonce
