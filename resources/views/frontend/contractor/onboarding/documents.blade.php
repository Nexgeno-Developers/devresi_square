@extends('layouts.auth')

@section('title', 'Your documents · Resisquare')
@section('shell_class', 'auth-onboard auth-onboard-wide')

@section('content')
    @php
        $status = $profile->approval_status;
        $editable = in_array($status, ['draft', 'changes_requested'], true);
        $tradeNames = collect($profile->trades ?? [])->map(fn ($t) => config('contractor_marketplace.trades.'.$t, $t))->implode(', ');
    @endphp
    <div class="auth-progress" aria-hidden="true"><span class="is-on"></span><span class="is-on"></span><span class="is-on {{ $editable ? 'is-new' : '' }}"></span></div>

    <div class="onb-head">
        <div class="onb-head-text">
            <p class="auth-mark">@if($editable) Step 3 of 3 @else {{ $profile->statusLabel() }} @endif</p>
            <h2>
                @switch($status)
                    @case('submitted') We’re checking your documents @break
                    @case('changes_requested') A few things to fix @break
                    @case('rejected') Your application wasn’t approved @break
                    @case('suspended') Your account is suspended @break
                    @default Your documents
                @endswitch
            </h2>
            <p class="auth-muted">
                @if($status === 'submitted')
                    You can still add optional items. Jobs appear once we approve you.
                @elseif($status === 'changes_requested')
                    Replace only the items marked “Not accepted”, then send again.
                @elseif($status === 'approved')
                    Upload renewals before they expire. Your approved trades keep working while we check them.
                @elseif($status === 'rejected')
                    {{ $profile->review_note }}
                @else
                    For {{ $tradeNames ?: 'your trades' }}. Snap photos with your phone or pick files.
                @endif
                <a class="auth-link" href="{{ route('contractor.profile.edit', ['step' => 'business']) }}" data-leave>Change trades or area</a>
            </p>
        </div>
        @if($editable && count($required))
            <div class="onb-meter" data-meter aria-live="polite">
                <svg viewBox="0 0 52 52" aria-hidden="true">
                    <circle class="onb-meter-track" cx="26" cy="26" r="22"/>
                    <circle class="onb-meter-fill" cx="26" cy="26" r="22" data-meter-ring/>
                </svg>
                <div>
                    <strong><span data-meter-done>0</span> of {{ count($required) }}</strong>
                    <span>ready to send</span>
                </div>
            </div>
        @endif
    </div>

    @if(session('success'))<div class="auth-alert auth-alert-success onb-pop">{{ session('success') }}</div>@endif
    @if(session('error') || $errors->any())
        <div class="auth-alert auth-alert-danger onb-pop">{{ session('error') ?: $errors->first() }}</div>
    @endif
    @if($status === 'changes_requested' && $profile->review_note)
        <div class="auth-alert auth-alert-danger">{{ $profile->review_note }}</div>
    @endif

    @if($profile->tradeApprovals->isNotEmpty() && in_array($status, ['approved', 'changes_requested', 'submitted'], true))
        <div class="auth-chips">
            @foreach($profile->tradeApprovals as $approval)
                <span class="auth-pill {{ $approval->status === 'approved' ? 'is-ok' : (in_array($approval->status, ['rejected', 'more_info'], true) ? 'is-bad' : 'is-wait') }}">
                    {{ $approval->label() }} · {{ ['approved' => 'approved', 'pending' => 'in review', 'more_info' => 'more info needed', 'rejected' => 'not approved'][$approval->status] ?? $approval->status }}
                </span>
            @endforeach
        </div>
    @endif

    @if($status === 'rejected')
        <form method="POST" action="{{ route('contractor.profile.reapply') }}">
            @csrf
            <button class="auth-btn" type="submit">Fix and apply again</button>
        </form>
    @elseif($status !== 'suspended')
        <form method="POST" action="{{ route('contractor.profile.documents') }}" enctype="multipart/form-data" data-docs-form novalidate>
            @csrf
            <div class="doc-list">
                @foreach($required as $type)
                    @include('frontend.contractor.onboarding._document', ['type' => $type, 'optional' => false, 'i' => $loop->index])
                @endforeach
            </div>

            <section class="onb-extras {{ $errors->has('utr') ? 'is-expanded' : '' }}" data-extras>
                <button type="button" class="onb-extras-toggle" data-extras-toggle aria-expanded="{{ $errors->has('utr') ? 'true' : 'false' }}">
                    <span><strong>Optional extras</strong> <span class="auth-muted">DBS badge, employers’ liability, UTR</span></span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="doc-collapse {{ $errors->has('utr') ? 'is-open' : '' }}" data-extras-body>
                    <div class="doc-body">
                        <div class="doc-list">
                            @foreach($optional as $type)
                                @include('frontend.contractor.onboarding._document', ['type' => $type, 'optional' => true, 'i' => 0])
                            @endforeach
                            <label class="auth-field doc-card doc-card-plain">
                                <span>UTR, for CIS (optional)</span>
                                <input name="utr" value="{{ old('utr', $profile->utr) }}" inputmode="numeric" maxlength="10" placeholder="10 digits">
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <div class="onb-actions">
                <p class="auth-muted" data-hint>
                    @if($editable) We check documents within {{ \App\Support\ContractorSettings::get('review_sla_working_days') }} working days and email you either way. @endif
                </p>
                <div class="onb-actions-buttons">
                    @if($editable)
                        <button class="auth-btn auth-btn-ghost" type="submit" name="submit" value="0">Save and finish later</button>
                        <button class="auth-btn" type="submit" name="submit" value="1" data-check data-busy="Sending…">Send for review</button>
                    @elseif($status === 'approved')
                        <button class="auth-btn" type="submit" name="submit" value="1" data-busy="Sending…">Save and send for review</button>
                    @else
                        <button class="auth-btn auth-btn-ghost" type="submit" name="submit" value="0" data-busy="Saving…">Save</button>
                    @endif
                </div>
            </div>
        </form>
    @endif

    <div class="onb-foot">
        @if($status === 'approved')
            <a class="auth-link" href="{{ route('contractor.jobs.index') }}" data-leave>Go to jobs</a>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="onb-logout">Log out</button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.querySelector('[data-docs-form]');
    if (!form) return;
    const MAX = 10 * 1024 * 1024;
    const cards = [...form.querySelectorAll('[data-doc]')];
    const tracked = cards.filter(card => card.hasAttribute('data-track'));
    const required = cards.filter(card => card.hasAttribute('data-required'));
    const ring = document.querySelector('[data-meter-ring]');
    const doneLabel = document.querySelector('[data-meter-done]');
    const hint = form.querySelector('[data-hint]');
    const CIRC = 2 * Math.PI * 22;

    const filled = el => el.type === 'file' ? el.files.length > 0 : el.value.trim() !== '';
    const missing = card => [
        ...[...card.querySelectorAll('[data-need]')].filter(el => !filled(el)),
        ...[...card.querySelectorAll('[data-need-group]')].filter(group => !group.querySelector('input:checked')),
    ];

    const refresh = () => {
        let done = 0;
        tracked.forEach(card => {
            const ready = missing(card).length === 0;
            const pill = card.querySelector('[data-pill]');
            card.classList.toggle('is-ready', ready);
            pill.textContent = ready ? 'Ready' : pill.dataset.pillDefault;
            if (ready) card.classList.remove('is-shake');
            if (ready && card.hasAttribute('data-required')) done++;
        });
        if (ring) {
            ring.style.strokeDasharray = CIRC;
            ring.style.strokeDashoffset = CIRC * (1 - (required.length ? done / required.length : 1));
            ring.closest('[data-meter]').classList.toggle('is-full', done === required.length);
        }
        if (doneLabel) doneLabel.textContent = done;
        if (hint && hint.classList.contains('is-warn') && done === required.length) {
            hint.textContent = 'All set. Send it over.';
            hint.classList.remove('is-warn');
        }
        return done === required.length;
    };

    const toggle = (button, body) => {
        const open = !body.classList.contains('is-open');
        body.classList.toggle('is-open', open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        button.closest('[data-extras], [data-doc]')?.classList.toggle('is-expanded', open);
        if (open) setTimeout(() => body.querySelector('input:not([type=file]), select')?.focus({ preventScroll: true }), 220);
    };
    form.querySelectorAll('[data-doc-toggle]').forEach(button =>
        button.addEventListener('click', () => toggle(button, button.closest('[data-doc]').querySelector('[data-doc-body]'))));
    form.querySelectorAll('[data-extras-toggle]').forEach(button =>
        button.addEventListener('click', () => toggle(button, button.closest('[data-extras]').querySelector('[data-extras-body]'))));

    form.querySelectorAll('[data-drop]').forEach(drop => {
        const input = drop.querySelector('input[type=file]');
        const title = drop.querySelector('[data-drop-title]');
        const sub = drop.querySelector('[data-drop-sub]');
        const thumb = drop.querySelector('[data-drop-thumb]');
        const clear = drop.querySelector('[data-drop-clear]');
        const size = bytes => bytes > 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';

        const show = () => {
            const file = input.files[0];
            drop.classList.remove('is-error');
            if (thumb.src) URL.revokeObjectURL(thumb.src);
            if (file && file.size > MAX) {
                input.value = '';
                drop.classList.add('is-error');
                title.textContent = 'That file is over 10 MB';
                sub.textContent = 'Try a photo instead of a scan, or compress the PDF.';
                thumb.hidden = true;
                clear.hidden = true;
                refresh();
                return;
            }
            drop.classList.toggle('has-file', !!file);
            title.textContent = file ? file.name : title.dataset.default;
            sub.textContent = file ? size(file.size) + ' · tap to change' : sub.dataset.default;
            clear.hidden = !file;
            if (file && file.type.startsWith('image/')) {
                thumb.src = URL.createObjectURL(file);
                thumb.hidden = false;
            } else {
                thumb.removeAttribute('src');
                thumb.hidden = true;
            }
            if (file) {
                drop.classList.remove('is-pop');
                void drop.offsetWidth;
                drop.classList.add('is-pop');
            }
            refresh();
        };

        input.addEventListener('change', show);
        clear.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); input.value = ''; show(); });
        ['dragenter', 'dragover'].forEach(type => drop.addEventListener(type, e => { e.preventDefault(); drop.classList.add('is-over'); }));
        ['dragleave', 'dragend', 'drop'].forEach(type => drop.addEventListener(type, () => drop.classList.remove('is-over')));
        drop.addEventListener('drop', e => {
            e.preventDefault();
            if (!e.dataTransfer?.files.length) return;
            const transfer = new DataTransfer();
            transfer.items.add(e.dataTransfer.files[0]);
            input.files = transfer.files;
            show();
        });
    });

    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);

    form.addEventListener('submit', e => {
        const button = e.submitter;
        if (button?.hasAttribute('data-check') && !refresh()) {
            e.preventDefault();
            const card = required.find(c => missing(c).length);
            const gap = missing(card)[0];
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            card.classList.remove('is-shake');
            void card.offsetWidth;
            card.classList.add('is-shake');
            setTimeout(() => (gap.matches('[data-need-group]') ? gap.querySelector('input') : gap).focus({ preventScroll: true }), 350);
            if (hint) {
                hint.textContent = 'Finish ' + card.querySelector('.doc-title strong').textContent + ' first, or save and finish later.';
                hint.classList.add('is-warn');
            }
            return;
        }
        if (button) {
            const mirror = document.createElement('input');
            mirror.type = 'hidden';
            mirror.name = button.name;
            mirror.value = button.value;
            form.appendChild(mirror);
            if (button.dataset.busy) button.textContent = button.dataset.busy;
            button.classList.add('is-busy');
        }
        form.querySelectorAll('button[type=submit]').forEach(b => b.disabled = true);
        document.querySelector('.auth-form')?.classList.add('is-leaving');
    });

    document.querySelectorAll('[data-leave]').forEach(link => link.addEventListener('click', () =>
        document.querySelector('.auth-form')?.classList.add('is-leaving')));
    window.addEventListener('pageshow', () => document.querySelector('.auth-form')?.classList.remove('is-leaving'));

    requestAnimationFrame(() => requestAnimationFrame(refresh));
    setTimeout(() => form.classList.add('is-settled'), 1200);
})();
</script>
@endpush
