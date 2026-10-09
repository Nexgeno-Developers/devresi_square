@extends('layouts.auth')

@section('title', 'Your repair visit')
@section('shell_class', 'auth-compact')

@section('content')
    <p class="auth-mark">Repair visit</p>
    <h2>{{ $when ? $when->format('l j F, H:i') : 'Time to be arranged' }}</h2>
    <p class="auth-muted">{{ \Illuminate\Support\Str::limit($offer->repairIssue?->description, 140) }}</p>

    @if(session('success'))<div class="auth-alert auth-alert-success onb-pop">{{ session('success') }}</div>@endif
    @if(session('error') || $errors->any())<div class="auth-alert auth-alert-danger onb-pop">{{ session('error') ?: $errors->first() }}</div>@endif

    @if(! $active)
        <p class="auth-muted">This visit is no longer booked. Contact your landlord if you still need the repair.</p>
    @elseif($offer->reschedule_requested_at)
        <div class="auth-alert auth-alert-success">
            New times sent: {{ collect($offer->reschedule_slots)->map(fn ($s) => \Illuminate\Support\Carbon::parse($s)->format('D j M, H:i'))->implode(' or ') }}.
            The contractor will pick one.
        </div>
    @else
        @if($offer->tenant_confirmed_at)
            <div class="auth-alert auth-alert-success">You confirmed this time.</div>
        @elseif($when)
            <form method="POST" action="{{ request()->fullUrl() }}">
                @csrf
                <button class="auth-btn" type="submit">This time works</button>
            </form>
        @endif

        <section class="onb-extras {{ $errors->any() ? 'is-expanded' : '' }}" data-extras>
            <button type="button" class="onb-extras-toggle" data-extras-toggle aria-expanded="{{ $errors->any() ? 'true' : 'false' }}">
                <span><strong>Can’t make it?</strong> <span class="auth-muted">Offer up to 3 other times</span></span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="doc-collapse {{ $errors->any() ? 'is-open' : '' }}" data-extras-body>
                <div class="doc-body">
                    <form method="POST" action="{{ request()->fullUrl() }}" class="auth-compact-form">
                        @csrf
                        <input type="hidden" name="action" value="reschedule">
                        @for($i = 0; $i < 3; $i++)
                            <label class="auth-field">
                                <span>{{ $i === 0 ? 'A time that works' : 'Another option (optional)' }}</span>
                                <input type="datetime-local" name="slots[]" value="{{ old('slots.'.$i) }}" min="{{ now()->addHour()->format('Y-m-d\TH:i') }}" @if($i === 0) required @endif>
                            </label>
                        @endfor
                        <label class="auth-field">
                            <span>Note (optional)</span>
                            <input name="note" maxlength="500" value="{{ old('note') }}" placeholder="E.g. mornings are best">
                        </label>
                        <button class="auth-btn auth-btn-ghost" type="submit">Send new times</button>
                    </form>
                </div>
            </div>
        </section>
    @endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-extras-toggle]').forEach(button => button.addEventListener('click', () => {
    const section = button.closest('[data-extras]');
    const body = section.querySelector('[data-extras-body]');
    const open = !body.classList.contains('is-open');
    body.classList.toggle('is-open', open);
    section.classList.toggle('is-expanded', open);
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
}));
</script>
@endpush
