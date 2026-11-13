@if(! empty($url))
    <a class="{{ $class ?? 'btn btn-sm btn-outline-primary' }}" href="{{ $url }}" data-chat-link>
        <i class="bi bi-chat-dots" aria-hidden="true"></i> {{ $label }}
    </a>
@endif
