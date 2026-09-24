<p class="lab-note">This is the language every landlord and tenant page should use: navy ink, teal accent, wash background, black primary buttons, quiet status pills. Lists stay as pages. Add, invite, pay and inspect open as overlays.</p>

<div class="lab-grid-3" style="margin-bottom: 1rem;">
    <div class="swatch" style="background:#0b1220;">Ink #0b1220</div>
    <div class="swatch" style="background:#0f766e;">Teal #0f766e</div>
    <div class="swatch" style="background:#f4f6f8;color:#0b1220;border:1px solid #e6e9ef;">Wash #f4f6f8</div>
</div>

<div class="lab-card" style="margin-bottom: 1rem;">
    <p class="lab-label">Buttons</p>
    <div class="lab-row">
        <span class="lab-btn lab-btn-primary">Add property</span>
        <span class="lab-btn lab-btn-secondary">View ledger</span>
        <span class="lab-btn lab-btn-danger">Delete</span>
    </div>
</div>

<div class="lab-card" style="margin-bottom: 1rem;">
    <p class="lab-label">Status</p>
    <div class="lab-row">
        <span class="lab-pill lab-pill-ok">Let</span>
        <span class="lab-pill lab-pill-ok">Paid</span>
        <span class="lab-pill lab-pill-warn">Pending</span>
        <span class="lab-pill lab-pill-bad">Overdue</span>
        <span class="lab-pill lab-pill-idle">Closed</span>
    </div>
</div>

<div class="lab-grid-2">
    <div class="lab-card">
        <p class="lab-label">Copy on screen</p>
        <p style="margin:0 0 0.5rem;"><strong>Flat 12, E14 9RU</strong><br><span class="lab-muted">not Flat 12, 1 Baltimore Wharf, London, Tower Hamlets, E14 9RU, United Kingdom</span></p>
        <p style="margin:0 0 0.5rem;"><strong>September rent · £1,100.00</strong><br><span class="lab-muted">not RENT-0003 or 1,100.00</span></p>
        <p style="margin:0;"><strong>1 Jan 2026</strong><br><span class="lab-muted">not 2026-01-01 00:00:00</span></p>
    </div>
    <div class="lab-card empty">
        <div class="empty-icon">✓</div>
        <div style="font-weight:650;color:var(--lab-ink);">No visits this month</div>
        <p class="lab-muted" style="margin:0.35rem 0 0;">When you book an inspection it will show here.</p>
    </div>
</div>

<div class="lab-card" style="margin-top:1rem;">
    <p class="lab-label">Overlays</p>
    <p class="lab-muted">Sub-actions stay on the list. A modal for forms and cases. A sheet only on the tenant phone.</p>
    <div class="lab-row">
        <button type="button" class="lab-btn lab-btn-primary" data-lab-open="lab-demo-dialog">Form</button>
        <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-demo-wide">Case</button>
        <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-demo-sheet">Sheet</button>
    </div>
</div>

<x-ui-lab.overlay id="demo-dialog" title="Add something">
    <p class="lab-muted" style="margin:0 0 0.85rem;">Short forms stay here. Cancel or Escape closes.</p>
    <label class="lab-field"><span>Name</span><input value="Flat 12"></label>
    <span class="lab-btn lab-btn-primary">Save</span>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="demo-wide" title="Kitchen tap dripping" type="wide">
    <p class="lab-muted" style="margin:0 0 0.85rem;">Inspect a case in a modal. The list stays underneath.</p>
    <span class="lab-pill lab-pill-warn">Pending</span>
    <p style="margin:0.75rem 0 0;">Hot tap drips after use. Reported by Tina on 14 Sep.</p>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="demo-sheet" title="Pay September rent" type="sheet">
    <p class="lab-muted" style="margin:0 0 0.85rem;">Tenant actions rise from the bottom of the phone.</p>
    <span class="lab-btn lab-btn-primary" style="width:100%;">Pay £1,100.00</span>
</x-ui-lab.overlay>
