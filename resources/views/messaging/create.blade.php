@extends('backend.layout.app')

@section('content')
@include('messaging.partials.styles')
@php $r = fn ($name, $params = []) => \App\Support\Messaging\MessagingArea::route($area, $name, $params); @endphp
<div class="msg-shell" data-messaging-area="{{ $area }}">
    @include('messaging.partials.header')

    <div class="msg-card" style="max-width:640px">
        <h2 class="h5">New message</h2>
        <p class="msg-muted">
            People you already work with are one click away: use the Message buttons on tenancies, jobs and appointments.
            For anyone else, send a request. Once they accept, you can chat.
        </p>
        <form method="POST" action="{{ $r('requests.store') }}">
            @csrf
            @if($target)
                <input type="hidden" name="contractor" value="{{ $contractorId }}">
                <p class="mb-2">To: <strong>{{ $target->displayName() }}</strong> <span class="msg-muted">· {{ $target->roleLabel() }}</span></p>
            @else
                <label class="form-label" for="req-email">Their email address</label>
                <input class="form-control mb-1" type="email" id="req-email" name="email" value="{{ old('email') }}" maxlength="191" required autocomplete="off">
                <div class="msg-muted mb-3">We don’t show whether this address has an account. If it does, they’ll get your request.</div>
            @endif
            <label class="form-label" for="req-intro">Short note (optional)</label>
            <textarea class="form-control mb-1" id="req-intro" name="intro" rows="3" maxlength="{{ config('messaging.intro_max') }}" placeholder="Say who you are and why you’re getting in touch">{{ old('intro') }}</textarea>
            <div class="msg-muted mb-3">Phone numbers, emails and links are hidden until you have both replied.</div>
            <button class="btn lw-btn-primary" type="submit">Send request</button>
            <a class="btn btn-link" href="{{ $r('index') }}">Cancel</a>
        </form>
    </div>
</div>
@endsection
