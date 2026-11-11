@extends('backend.layout.app')

@section('content')
@include('messaging.partials.styles')
<div class="msg-shell" data-messaging-area="{{ $area }}">
    @include('messaging.partials.header')
    <div class="msg-layout">
        <aside class="msg-list" aria-label="{{ $archived ? 'Archived conversations' : 'Conversations' }}">
            @include('messaging.partials.list', ['items' => $items, 'activeKey' => null, 'archived' => $archived])
        </aside>
        <section class="msg-pane">
            <div class="msg-empty">
                <i class="bi bi-chat-square-text fs-1 d-block mb-2" aria-hidden="true"></i>
                Pick a conversation to read it.
            </div>
        </section>
    </div>
</div>
@endsection
