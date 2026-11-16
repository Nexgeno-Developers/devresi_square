@if(! empty($url))
    {{-- Inline sizing: this link sits on pages that do not load the messaging styles, and long labels must wrap on phones. --}}
    <a class="{{ $class ?? 'btn btn-sm btn-outline-primary' }}" href="{{ $url }}" data-chat-link style="display:inline-flex;align-items:center;gap:6px;max-width:100%;height:auto;min-height:0;padding-top:6px;padding-bottom:6px;line-height:1.3;white-space:normal;text-align:left">
        <i class="bi bi-chat-dots" aria-hidden="true" style="flex:none"></i> <span>{{ $label }}</span>
    </a>
@endif
