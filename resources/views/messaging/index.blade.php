@extends('backend.layout.app')

@section('content')
@include('messaging.partials.styles')
<div class="msg-shell" data-messaging-area="{{ $area }}">
    @include('messaging.partials.header')
    <div class="msg-layout {{ $items->isEmpty() ? 'is-empty' : '' }}">
        <aside class="msg-list" aria-label="{{ $archived ? 'Archived conversations' : 'Conversations' }}">
            @include('messaging.partials.list', ['items' => $items, 'activeKey' => null, 'archived' => $archived])
        </aside>
        <section class="msg-pane">
            <div class="msg-empty">
                <div class="msg-empty-icon"><i class="bi bi-chat-square-text" aria-hidden="true"></i></div>
                <h3>{{ $archived ? 'Archived chats' : 'Your conversations' }}</h3>
                <p>{{ $archived ? 'Pick a chat to read its history. Archived chats are read-only.' : 'Pick a chat on the left to read and reply. Everyone in a chat sees every message.' }}</p>
            </div>
        </section>
    </div>
</div>
@include('messaging.partials.list-script')
@endsection
