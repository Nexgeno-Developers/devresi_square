@php
    $review = $contractorReview;
    $profile = $review['profile'];
    $tradeNames = \App\Support\ContractorCompliance::trades();
    $canDecide = $profile && in_array($profile->approval_status, ['submitted', 'changes_requested', 'draft', 'rejected'], true);
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between gap-2">
        <h5 class="mb-0 h6">Documents to review</h5>
        @if($profile)
            <span class="badge bg-{{ $profile->approval_status === 'approved' ? 'success' : (in_array($profile->approval_status, ['rejected', 'suspended', 'removed'], true) ? 'danger' : 'warning') }}">{{ $profile->statusLabel() }}</span>
        @endif
    </div>
    <div class="card-body">
        @unless($profile)
            <p class="text-muted mb-0">This contractor has not submitted any documents yet.</p>
        @else
            <p class="small text-muted mb-3">
                {{ $profile->trading_type === 'limited_company' ? 'Limited company' : 'Sole trader' }}
                @if($profile->postcode) · {{ $profile->postcode }} @endif
                @if($profile->coverage_radius_miles) · {{ $profile->coverage_radius_miles }} miles @endif
                @if($profile->trades)
                    · {{ collect($profile->trades)->map(fn ($trade) => $tradeNames[$trade] ?? $trade)->implode(', ') }}
                @endif
            </p>

            <div class="contractor-doc-grid">
                @foreach($review['types'] as $type)
                    @php
                        $spec = \App\Support\ContractorCompliance::documentSpec($type);
                        $doc = $profile->documents->first(fn ($row) => $row->type === $type);
                        $required = in_array($type, $review['required'], true);
                        $fileUrl = $doc?->path ? route('admin.registrations.contractors.document', [$profile, $doc]) : null;
                        $kind = match (true) {
                            $fileUrl && str_starts_with((string) $doc->mime, 'image/') => 'image',
                            $fileUrl && $doc->mime === 'application/pdf' => 'pdf',
                            (bool) $fileUrl => 'file',
                            default => null,
                        };
                    @endphp
                    <div class="contractor-doc-cell">
                        @if($kind)
                            <button type="button"
                                class="contractor-doc w-100 text-start border rounded p-3 bg-white"
                                data-doc-open
                                data-doc-title="{{ $spec['label'] ?? $type }}"
                                data-doc-url="{{ $fileUrl }}"
                                data-doc-kind="{{ $kind }}">
                        @else
                            <div class="border rounded p-3 h-100">
                        @endif
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <strong>{{ $spec['label'] ?? $type }}</strong>
                                    @unless($required)<span class="small text-muted">(optional)</span>@endunless
                                </div>
                                @if($kind)
                                    <span class="small text-primary">Open</span>
                                @endif
                            </div>
                            @if($kind === 'image')
                                <img src="{{ $fileUrl }}" alt="" class="w-100 rounded border bg-light mb-2" style="height: 180px; object-fit: contain;">
                            @elseif($kind)
                                <div class="d-flex align-items-center justify-content-center bg-light rounded border mb-2 text-muted" style="height: 180px;">
                                    <span>{{ $kind === 'pdf' ? 'PDF' : 'File' }} · click to open</span>
                                </div>
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-light rounded text-muted small" style="height: 140px;">
                                    {{ $required ? 'Not uploaded' : 'Not provided' }}
                                </div>
                            @endif
                            @if($doc)
                                <div class="small text-muted">
                                    {{ $doc->original_name ?: 'No file' }}
                                    @if($doc->submitted_at) · {{ $doc->submitted_at->format('j M Y') }} @endif
                                    @if($doc->reference) · {{ $doc->reference }} @endif
                                    @if($doc->cover_amount !== null) · £{{ number_format((float) $doc->cover_amount, 0) }} cover @endif
                                    @if($doc->expires_at) · expires {{ $doc->expires_at->format('j M Y') }} @endif
                                    @if($doc->isExpired()) · <span class="text-danger">expired</span> @endif
                                </div>
                            @endif
                        @if($kind)
                            </button>
                        @else
                            </div>
                        @endif
                    </div>
                @endforeach

                @if($canDecide)
                    <div class="contractor-decide {{ count($review['types']) % 2 === 0 ? 'is-wide' : '' }}">
                        <strong>Decision</strong>
                        <p class="small text-muted mb-3">One decision for every document above.</p>
                        <form method="POST" action="{{ route('admin.registrations.contractors.pack', $profile) }}" class="mb-2">
                            @csrf
                            <button class="btn btn-success w-100" name="decision" value="approve" type="submit">Approve</button>
                        </form>
                        <button class="btn btn-outline-danger w-100" type="button" id="contractor-reject-open">Reject</button>
                        <form method="POST" action="{{ route('admin.registrations.contractors.pack', $profile) }}" id="contractor-reject-form" hidden class="mt-3">
                            @csrf
                            <label class="form-label small mb-1" for="contractor-reject-note">Why is this rejected?</label>
                            <textarea id="contractor-reject-note" name="review_note" class="form-control mb-2" rows="4" required placeholder="The contractor will see this note"></textarea>
                            <button class="btn btn-danger w-100" name="decision" value="reject" type="submit">Reject application</button>
                        </form>
                    </div>
                @endif
            </div>
        @endunless
    </div>
</div>

<div id="contractor-doc-modal" hidden>
    <div class="contractor-doc-modal-backdrop" data-doc-close></div>
    <div class="contractor-doc-modal-panel" role="dialog" aria-modal="true" aria-labelledby="contractor-doc-title">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
            <h5 class="mb-0" id="contractor-doc-title"></h5>
            <button type="button" class="btn-close" data-doc-close aria-label="Close"></button>
        </div>
        <div id="contractor-doc-body"></div>
    </div>
</div>

<style>
    .contractor-doc-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        align-items: stretch;
    }
    .contractor-doc-cell,
    .contractor-doc { height: 100%; }
    .contractor-decide {
        height: 100%;
        border: 1px solid #e6e9ef;
        border-radius: 0.375rem;
        padding: 1rem;
        background: #f8f9fb;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .contractor-decide.is-wide { grid-column: 1 / -1; }
    @media (max-width: 767px) {
        .contractor-doc-grid { grid-template-columns: 1fr; }
        .contractor-decide.is-wide { grid-column: auto; }
    }
    .contractor-doc { cursor: pointer; }
    .contractor-doc:hover { border-color: #0f173a !important; }
    #contractor-doc-modal { position: fixed; inset: 0; z-index: 1080; }
    .contractor-doc-modal-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.72); }
    .contractor-doc-modal-panel {
        position: relative;
        z-index: 1;
        width: min(960px, calc(100% - 2rem));
        max-height: calc(100vh - 2rem);
        margin: 1rem auto;
        background: #fff;
        border-radius: 12px;
        padding: 1rem;
        overflow: auto;
    }
    #contractor-doc-body img,
    #contractor-doc-body iframe { width: 100%; height: min(78vh, 820px); object-fit: contain; background: #f4f6f8; border: 0; border-radius: 8px; }
</style>

<script>
    (function () {
        const modal = document.getElementById('contractor-doc-modal');
        const title = document.getElementById('contractor-doc-title');
        const body = document.getElementById('contractor-doc-body');
        if (!modal || !title || !body) return;

        const close = () => {
            modal.hidden = true;
            body.replaceChildren();
        };
        document.querySelectorAll('[data-doc-open]').forEach((button) => {
            button.addEventListener('click', () => {
                title.textContent = button.dataset.docTitle || 'Document';
                body.replaceChildren();
                const url = button.dataset.docUrl;
                if (button.dataset.docKind === 'image') {
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = title.textContent;
                    body.appendChild(img);
                } else if (button.dataset.docKind === 'pdf') {
                    const frame = document.createElement('iframe');
                    frame.src = url;
                    frame.title = title.textContent;
                    body.appendChild(frame);
                } else if (url) {
                    const link = document.createElement('a');
                    link.href = url;
                    link.target = '_blank';
                    link.rel = 'noopener';
                    link.textContent = 'Open file';
                    body.appendChild(link);
                }
                modal.hidden = false;
            });
        });
        modal.querySelectorAll('[data-doc-close]').forEach((el) => el.addEventListener('click', close));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) close();
        });

        const rejectOpen = document.getElementById('contractor-reject-open');
        const rejectForm = document.getElementById('contractor-reject-form');
        rejectOpen?.addEventListener('click', () => {
            rejectForm.hidden = false;
            rejectOpen.hidden = true;
            document.getElementById('contractor-reject-note')?.focus();
        });
    })();
</script>
