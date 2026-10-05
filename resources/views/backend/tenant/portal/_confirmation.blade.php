@php
    $confirmation = $tenancyConfirmation ?? null;
    $openCorrection = $confirmation && $errors->any() && (string) old('tenancy_id') === (string) ($confirmation['tenancy']->id ?? '');
    $address = $confirmation
        ? (($confirmation['tenancy']->property?->full_address ?? null) ?: 'this home')
        : 'this home';
@endphp
@if($confirmation)
    @if($confirmation['mode'] === 'waiting')
        <div class="tp-confirm-banner" role="status">
            <strong>Waiting for your landlord</strong>
            <p>You asked us to check the details for {{ $address }}. You can keep using the portal while they review it.</p>
            @if($errors->any())
                <p>{{ $errors->first() }}</p>
            @endif
        </div>
    @else
        <div class="tp-confirm-overlay" id="tp-confirm-overlay" role="dialog" aria-modal="true" aria-labelledby="tp-confirm-title">
            <div class="tp-confirm-dialog{{ $openCorrection ? ' is-correcting' : '' }}" id="tp-confirm-dialog">
                <div class="tp-confirm-review">
                    <header class="tp-confirm-head">
                        <p class="tp-kicker">Confirm your home</p>
                        <h2 id="tp-confirm-title">Check these tenancy details</h2>
                        <p class="tp-muted">Your landlord entered these for {{ $address }}. Confirm them if they look right, or tell us what needs changing.</p>
                    </header>

                    <div class="tp-confirm-body">
                        @if($confirmation['review_note'])
                            <div class="tp-banner">Your landlord replied: {{ $confirmation['review_note'] }}</div>
                        @endif

                        @if($errors->any() && ! $openCorrection)
                            <div class="tp-banner">{{ $errors->first() }}</div>
                        @endif

                        <ul class="tp-confirm-facts">
                            @foreach($confirmation['details'] as $row)
                                <li>
                                    <span>{{ $row['label'] }}</span>
                                    <strong>{{ $row['display'] }}</strong>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('tenant.tenancy.confirm') }}" class="tp-confirm-foot" id="tp-confirm-form">
                        @csrf
                        <input type="hidden" name="tenancy_id" value="{{ $confirmation['tenancy']->id }}">
                        <button type="submit" class="tp-btn">Looks correct</button>
                        <button type="button" class="tp-btn tp-btn-ghost" id="tp-confirm-wrong">Something is wrong</button>
                    </form>
                </div>

                <form method="POST" action="{{ route('tenant.tenancy.correction') }}" class="tp-confirm-correct" id="tp-correct-form">
                    @csrf
                    <input type="hidden" name="tenancy_id" value="{{ $confirmation['tenancy']->id }}">
                    <header class="tp-confirm-head">
                        <p class="tp-kicker">{{ $address }}</p>
                        <h2>What needs correcting?</h2>
                        <p class="tp-muted">Tick the details that are wrong. Add the right value only for the ones you tick.</p>
                    </header>
                    <div class="tp-confirm-body">
                        @if($errors->any() && $openCorrection)
                            <div class="tp-banner">{{ $errors->first() }}</div>
                        @endif
                        <div class="tp-confirm-fields">
                            @foreach($confirmation['details'] as $row)
                                @continue(! $row['editable'])
                                @php $checked = $openCorrection && in_array($row['key'], old('fields', []), true); @endphp
                                <div class="tp-confirm-field{{ $checked ? ' is-picked' : '' }}">
                                    <label class="tp-confirm-field-head">
                                        <input type="checkbox" name="fields[]" value="{{ $row['key'] }}" class="tp-confirm-check" @checked($checked)>
                                        {{ $row['label'] }}
                                    </label>
                                    <p class="tp-muted mb-0 mt-1">Currently {{ $row['display'] }}</p>
                                    <div class="tp-confirm-suggest">
                                        @if($row['type'] === 'frequency')
                                            <select name="suggested[{{ $row['key'] }}]" class="tp-confirm-input">
                                                <option value="">Keep current</option>
                                                <option value="Monthly" @selected(old('suggested.'.$row['key']) === 'Monthly')>Monthly</option>
                                                <option value="Weekly" @selected(old('suggested.'.$row['key']) === 'Weekly')>Weekly</option>
                                            </select>
                                        @elseif($row['type'] === 'date')
                                            <input type="date" name="suggested[{{ $row['key'] }}]" class="tp-confirm-input" value="{{ old('suggested.'.$row['key']) }}">
                                        @elseif($row['type'] === 'money')
                                            <input type="number" min="0" step="0.01" name="suggested[{{ $row['key'] }}]" class="tp-confirm-input" placeholder="Suggested amount" value="{{ old('suggested.'.$row['key']) }}">
                                        @elseif($row['type'] === 'integer')
                                            <input type="number" min="0" max="60" name="suggested[{{ $row['key'] }}]" class="tp-confirm-input" placeholder="Suggested months" value="{{ old('suggested.'.$row['key']) }}">
                                        @else
                                            <input type="text" name="suggested[{{ $row['key'] }}]" class="tp-confirm-input" placeholder="What should this be?" value="{{ old('suggested.'.$row['key']) }}">
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <label class="tp-confirm-note">
                            <span class="tp-metric-label">Tell your landlord what is wrong</span>
                            <textarea name="message" rows="3" required maxlength="2000" placeholder="e.g. Rent should be £1,100 and move-in is 1 October.">{{ old('message') }}</textarea>
                        </label>
                    </div>
                    <div class="tp-confirm-foot">
                        <button type="submit" class="tp-btn">Send correction request</button>
                        <button type="button" class="tp-btn tp-btn-ghost" id="tp-correct-cancel">Back</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            (function () {
                var dialog = document.getElementById('tp-confirm-dialog');
                var correctForm = document.getElementById('tp-correct-form');
                var openBtn = document.getElementById('tp-confirm-wrong');
                var cancelBtn = document.getElementById('tp-correct-cancel');
                if (!dialog || !correctForm) return;
                document.body.classList.add('tp-confirm-lock');
                function showCorrect(open) {
                    dialog.classList.toggle('is-correcting', open);
                }
                if (openBtn) openBtn.addEventListener('click', function () { showCorrect(true); });
                if (cancelBtn) cancelBtn.addEventListener('click', function () { showCorrect(false); });
                correctForm.querySelectorAll('.tp-confirm-check').forEach(function (box) {
                    box.addEventListener('change', function () {
                        var field = box.closest('.tp-confirm-field');
                        if (field) field.classList.toggle('is-picked', box.checked);
                    });
                });
            })();
        </script>
    @endif
@endif
