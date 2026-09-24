@php
    $confirmation = $tenancyConfirmation ?? null;
    $openCorrection = $confirmation && $errors->any() && (string) old('tenancy_id') === (string) ($confirmation['tenancy']->id ?? '');
@endphp
@if($confirmation)
    @if($confirmation['mode'] === 'waiting')
        <div class="tp-confirm-banner" role="status">
            <div>
                <strong>Waiting for your landlord</strong>
                <p>You asked us to check the details for {{ $confirmation['tenancy']->property?->full_address ?: 'this home' }}. You can keep using the portal while they review it.</p>
                @if($errors->any())
                    <p>{{ $errors->first() }}</p>
                @endif
            </div>
        </div>
    @else
        <div class="tp-confirm-overlay" id="tp-confirm-overlay" role="dialog" aria-modal="true" aria-labelledby="tp-confirm-title">
            <div class="tp-confirm-dialog">
                <p class="tp-kicker">Confirm your home</p>
                <h2 id="tp-confirm-title">Check these tenancy details</h2>
                <p class="tp-muted">Your landlord entered these for {{ $confirmation['tenancy']->property?->full_address ?: 'this property' }}. Confirm them if they look right, or tell us what needs changing.</p>

                @if($confirmation['review_note'])
                    <div class="tp-banner">Your landlord replied: {{ $confirmation['review_note'] }}</div>
                @endif

                @if($errors->any())
                    <div class="tp-banner">{{ $errors->first() }}</div>
                @endif

                <dl class="tp-dl tp-confirm-dl">
                    @foreach($confirmation['details'] as $row)
                        <dt>{{ $row['label'] }}</dt>
                        <dd>{{ $row['display'] }}</dd>
                    @endforeach
                </dl>

                <form method="POST" action="{{ route('tenant.tenancy.confirm') }}" class="tp-confirm-actions" id="tp-confirm-form">
                    @csrf
                    <input type="hidden" name="tenancy_id" value="{{ $confirmation['tenancy']->id }}">
                    <button type="submit" class="tp-btn">Looks correct</button>
                    <button type="button" class="tp-btn tp-btn-ghost" id="tp-confirm-wrong">Something is wrong</button>
                </form>

                <form method="POST" action="{{ route('tenant.tenancy.correction') }}" class="tp-confirm-correct{{ $openCorrection ? ' is-open' : '' }}" id="tp-correct-form">
                    @csrf
                    <input type="hidden" name="tenancy_id" value="{{ $confirmation['tenancy']->id }}">
                    <p class="tp-metric-label">What needs correcting?</p>
                    <div class="tp-confirm-fields">
                        @foreach($confirmation['details'] as $row)
                            @php $checked = $openCorrection && in_array($row['key'], old('fields', []), true); @endphp
                            <label class="tp-confirm-field">
                                <span class="tp-confirm-field-head">
                                    <input type="checkbox" name="fields[]" value="{{ $row['key'] }}" class="tp-confirm-check" @checked($checked)>
                                    {{ $row['label'] }}
                                </span>
                                <span class="tp-muted">Currently: {{ $row['display'] }}</span>
                                @if($row['type'] === 'frequency')
                                    <select name="suggested[{{ $row['key'] }}]" class="form-control">
                                        <option value="">Keep current</option>
                                        <option value="Monthly" @selected(old('suggested.'.$row['key']) === 'Monthly')>Monthly</option>
                                        <option value="Weekly" @selected(old('suggested.'.$row['key']) === 'Weekly')>Weekly</option>
                                    </select>
                                @elseif($row['type'] === 'date')
                                    <input type="date" name="suggested[{{ $row['key'] }}]" class="form-control" value="{{ old('suggested.'.$row['key']) }}">
                                @elseif($row['type'] === 'money')
                                    <input type="number" min="0" step="0.01" name="suggested[{{ $row['key'] }}]" class="form-control" placeholder="Suggested amount" value="{{ old('suggested.'.$row['key']) }}">
                                @elseif($row['type'] === 'integer')
                                    <input type="number" min="0" max="60" name="suggested[{{ $row['key'] }}]" class="form-control" placeholder="Suggested months" value="{{ old('suggested.'.$row['key']) }}">
                                @else
                                    <input type="text" name="suggested[{{ $row['key'] }}]" class="form-control" placeholder="What should this be?" value="{{ old('suggested.'.$row['key']) }}">
                                @endif
                            </label>
                        @endforeach
                    </div>
                    <label class="d-block mt-3">
                        <span class="tp-metric-label">Tell your landlord what is wrong</span>
                        <textarea name="message" class="form-control" rows="3" required maxlength="2000" placeholder="e.g. Rent should be £1,100 and move-in is 1 October.">{{ old('message') }}</textarea>
                    </label>
                    <div class="tp-confirm-actions mt-3">
                        <button type="submit" class="tp-btn">Send correction request</button>
                        <button type="button" class="tp-btn tp-btn-ghost" id="tp-correct-cancel">Back</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            (function () {
                var overlay = document.getElementById('tp-confirm-overlay');
                var confirmForm = document.getElementById('tp-confirm-form');
                var correctForm = document.getElementById('tp-correct-form');
                var openBtn = document.getElementById('tp-confirm-wrong');
                var cancelBtn = document.getElementById('tp-correct-cancel');
                if (!overlay || !correctForm) return;
                function showCorrect(open) {
                    correctForm.classList.toggle('is-open', open);
                    if (confirmForm) confirmForm.style.display = open ? 'none' : 'flex';
                }
                if (openBtn) openBtn.addEventListener('click', function () { showCorrect(true); });
                if (cancelBtn) cancelBtn.addEventListener('click', function () { showCorrect(false); });
            })();
        </script>
    @endif
@endif
