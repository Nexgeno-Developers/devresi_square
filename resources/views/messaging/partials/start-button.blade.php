{{--
    "Message …" button for people who already work together. The start
    route re-checks the relationship, so a stale button only leads to a 404.
    Params: $area, $context, $id, $user (optional), $label, $class (optional)
--}}
@if(config('messaging.enabled', true) && ! session()->has('impersonator_user_id'))
    <a class="{{ $class ?? 'btn btn-sm lw-btn-ghost' }}"
       href="{{ \App\Support\Messaging\MessagingArea::route($area, 'start', array_filter(['context' => $context, 'id' => $id, 'user' => $user ?? null])) }}">
        <i class="bi bi-chat-dots" aria-hidden="true"></i> <span>{{ $label ?? 'Message' }}</span>
    </a>
@endif
