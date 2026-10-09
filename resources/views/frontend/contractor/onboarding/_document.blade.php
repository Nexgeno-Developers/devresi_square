@php
    $spec = config('contractor_marketplace.documents.'.$type, []);
    $doc = $profile->document($type);
    $renewal = $store->renewalFor($profile, $type);
    $expired = $doc?->isExpired() ?? false;
    $locked = $doc && $doc->status === 'approved' && ! $expired;
    [$pill, $state] = match (true) {
        $renewal !== null => ['Renewal in review', 'wait'],
        ! $doc => [$optional ? 'Optional' : 'Needed', 'todo'],
        $doc->status === 'rejected' => ['Not accepted', 'bad'],
        $expired => ['Expired', 'bad'],
        $doc->status === 'approved' => [$doc->expires_at ? 'Approved until '.$doc->expires_at->format('j M Y') : 'Approved', 'ok'],
        default => ['In review', 'wait'],
    };
    $current = $renewal ?: $doc;
    $hasFile = $current && filled($current->path) && $current->status !== 'rejected' && ! $expired;
    $needsFile = (bool) ($spec['file'] ?? true);
    $checks = in_array($state, ['todo', 'bad'], true);
    $counts = $checks && ! $optional && $editable;
    $need = fn (bool $condition) => $checks && $condition ? 'data-need' : '';
    $field = fn (string $name) => 'docs['.$type.']['.$name.']';
    $old = fn (string $name, $fallback = null) => old('docs.'.$type.'.'.$name, $fallback);
    $expiry = $old('expires_at', $expired ? null : optional($current?->expires_at)->format('Y-m-d'));
@endphp
<article class="doc-card is-{{ $state }} {{ $locked && ! $renewal ? 'is-locked' : '' }}"
    data-doc @if($checks) data-track @endif @if($counts) data-required @endif style="--i: {{ $i ?? 0 }}">
    <header class="doc-head">
        <span class="doc-icon" aria-hidden="true">
            <svg class="doc-icon-check" viewBox="0 0 24 24"><path d="M5 12.5l4.2 4.2L19 7"/></svg>
            <span class="doc-icon-mark">{{ $state === 'bad' ? '!' : '' }}</span>
        </span>
        <div class="doc-title">
            <strong>{{ $spec['label'] ?? $type }}</strong>
            @if(! empty($spec['hint']))<span>{{ $spec['hint'] }}</span>@endif
        </div>
        <span class="auth-pill doc-pill" data-pill data-pill-default="{{ $pill }}">{{ $pill }}</span>
    </header>

    @if($doc?->status === 'rejected' && $doc->rejected_reason)
        <div class="auth-alert auth-alert-danger doc-reason">{{ $doc->rejected_reason }}</div>
    @endif

    @if($locked && ! $renewal)
        <button type="button" class="doc-toggle" data-doc-toggle aria-expanded="false">
            <span>Upload a renewal</span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
        </button>
    @endif

    <div class="doc-collapse {{ $locked && ! $renewal ? '' : 'is-open' }}" data-doc-body>
        <div class="doc-body">
            @if(! empty($spec['issuers']) || ! empty($spec['reference']) || ! empty($spec['expires']))
                <div class="doc-grid">
                    @if(! empty($spec['issuers']))
                        <label class="auth-field">
                            <span>Scheme</span>
                            <select name="{{ $field('issuer') }}" {!! $need(true) !!}>
                                <option value="">Choose</option>
                                @foreach($spec['issuers'] as $issuer)
                                    <option value="{{ $issuer }}" @selected($old('issuer', $current?->issuer) === $issuer)>{{ $issuer }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    @if(! empty($spec['reference']))
                        <label class="auth-field">
                            <span>{{ $spec['reference'] }}</span>
                            <input name="{{ $field('reference') }}" value="{{ $old('reference', $current?->reference) }}" autocapitalize="characters" autocomplete="off" {!! $need(true) !!}>
                        </label>
                    @endif
                    @if(! empty($spec['expires']))
                        <label class="auth-field">
                            <span>{{ $type === 'public_liability' ? 'Cover ends' : 'Expiry date (if shown)' }}</span>
                            <input type="date" name="{{ $field('expires_at') }}" value="{{ $expiry }}" min="{{ now()->addDay()->format('Y-m-d') }}" {!! $need($type === 'public_liability') !!}>
                        </label>
                    @endif
                </div>
            @endif

            @if(! empty($spec['cover']))
                <div>
                    <span class="auth-label">Cover level</span>
                    <div class="auth-choices doc-cover" @if($checks && ! $optional && $type === 'public_liability') data-need-group @endif>
                        @foreach(config('contractor_marketplace.cover_options') as $cover)
                            <label class="auth-choice">
                                <input type="radio" name="{{ $field('cover_amount') }}" value="{{ $cover }}" @checked((float) $old('cover_amount', $current?->cover_amount) === (float) $cover)>
                                <span>£{{ $cover / 1000000 }}m</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <label class="doc-drop {{ $hasFile ? 'has-saved' : '' }}" data-drop>
                <input type="file" name="{{ $field('file') }}" accept="image/*,application/pdf" {!! $need($needsFile && ! $hasFile) !!}>
                <span class="doc-drop-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 16V4m0 0l-4.5 4.5M12 4l4.5 4.5M4 15v3a2 2 0 002 2h12a2 2 0 002-2v-3"/></svg>
                    <img alt="" data-drop-thumb hidden>
                </span>
                <span class="doc-drop-text">
                    <strong data-drop-title data-default="{{ $hasFile ? 'Replace file' : ($needsFile ? 'Add a photo or PDF' : 'Add a photo of the card') }}">
                        {{ $hasFile ? 'Replace file' : ($needsFile ? 'Add a photo or PDF' : 'Add a photo of the card') }}
                    </strong>
                    <small data-drop-sub data-default="{{ $hasFile ? 'Saved: '.$current->original_name : 'Tap to take a photo, or drop a file here. Up to 10 MB.' }}">
                        {{ $hasFile ? 'Saved: '.$current->original_name : 'Tap to take a photo, or drop a file here. Up to 10 MB.' }}
                    </small>
                </span>
                <button type="button" class="doc-drop-clear" data-drop-clear hidden aria-label="Remove file">&times;</button>
            </label>
        </div>
    </div>
</article>
