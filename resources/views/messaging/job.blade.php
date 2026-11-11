@extends('backend.layout.app')

@section('content')
@include('messaging.partials.styles')
@php
    $r = fn ($name, $params = []) => \App\Support\Messaging\MessagingArea::route($area, $name, $params);
    $activeKey = ($kind === 'repair' ? 'rj-' : 'cj-').$offer->id;
@endphp
<div class="msg-shell" data-messaging-area="{{ $area }}">
    @include('messaging.partials.header')
    <div class="msg-layout has-thread">
        <aside class="msg-list" aria-label="Conversations">
            @include('messaging.partials.list', ['items' => $sidebarItems, 'activeKey' => $activeKey])
        </aside>
        <section class="msg-pane" aria-label="Job chat with {{ $title }}">
            <div class="msg-thread-head">
                <div>
                    <a class="msg-back btn btn-sm btn-link px-0" href="{{ $r('index') }}"><i class="bi bi-chevron-left" aria-hidden="true"></i> <span>All messages</span></a>
                    <h2>{{ $title }}</h2>
                    <div class="msg-muted">{{ $label }}</div>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="{{ $jobUrl }}">Open the job</a>
            </div>
            <div class="msg-note">This chat belongs to the job. Contact details stay hidden until the job is awarded.</div>
            <div class="msg-thread" id="msg-thread">
                @forelse($messages as $message)
                    @php $mine = $message->side === $mySide; @endphp
                    <div class="msg-bubble {{ $mine ? 'is-mine' : '' }}">
                        @if(! $mine && $message->user)<div class="msg-muted small fw-semibold">{{ $message->user->name }}</div>@endif
                        <div>{!! nl2br(e($message->body)) !!}</div>
                        <div class="msg-meta msg-muted">
                            <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('j M, H:i') }}</time>
                            @if($mine && $message->read_at)<span>Seen</span>@endif
                        </div>
                    </div>
                @empty
                    <div class="msg-empty">No messages yet.</div>
                @endforelse
                <span id="latest"></span>
            </div>
            @if($canMessage)
                <form class="msg-composer" method="POST" action="{{ $sendUrl }}">
                    @csrf
                    <label class="visually-hidden" for="job-body">Message</label>
                    <textarea id="job-body" name="body" maxlength="2000" required placeholder="Write a message about this job">{{ old('body') }}</textarea>
                    <div class="msg-composer-row">
                        <span class="msg-muted">Files for jobs go on the job page.</span>
                        <button type="submit" class="btn btn-sm lw-btn-primary">Send</button>
                    </div>
                </form>
            @else
                <div class="msg-readonly">This job is closed, so messages are switched off.</div>
            @endif
        </section>
    </div>
</div>
<script>
(function () { var t = document.getElementById('msg-thread'); if (t) { t.scrollTop = t.scrollHeight; } })();
</script>
@endsection
