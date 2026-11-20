{{-- Stamps every form post and AJAX call with the account this page was loaded in (EnsureFormAccountMatches). --}}
@php($stampAccountId = function_exists('current_account_id') ? current_account_id() : null)
@if($stampAccountId)
<script>
    (function () {
        var accountId = @json((string) $stampAccountId);
        var field = @json(\App\Http\Middleware\EnsureFormAccountMatches::FIELD);
        var header = @json(\App\Http\Middleware\EnsureFormAccountMatches::HEADER);

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form || (form.method || '').toLowerCase() !== 'post' || form.querySelector('input[name="' + field + '"]')) return;
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = field;
            input.value = accountId;
            form.appendChild(input);
        }, true);

        if (window.jQuery) {
            window.jQuery.ajaxPrefilter(function (options, original, xhr) {
                xhr.setRequestHeader(header, accountId);
            });
        }
        if (window.axios && window.axios.defaults) {
            window.axios.defaults.headers.common[header] = accountId;
        }
        var nativeFetch = window.fetch;
        if (nativeFetch) {
            window.fetch = function (input, init) {
                init = init || {};
                var sameOrigin = typeof input === 'string' ? (input.indexOf('http') !== 0 || input.indexOf(location.origin) === 0) : true;
                if (sameOrigin) {
                    var headers = new Headers(init.headers || (typeof input !== 'string' && input.headers) || {});
                    if (!headers.has(header)) headers.set(header, accountId);
                    init.headers = headers;
                }
                return nativeFetch.call(this, input, init);
            };
        }
    })();
</script>
@endif
