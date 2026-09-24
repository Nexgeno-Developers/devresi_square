<p class="lab-note">Tenant home on a phone: full width, pay first, bottom tabs. Home, pay and calendar stay as sheets.</p>

<x-ui-lab.phone tab="Home">
    <p class="lab-kicker" style="color:var(--lab-muted);">Tenant portal</p>
    <h2 style="margin:0 0 0.35rem;letter-spacing:-0.03em;">Welcome, Tina</h2>
    <p class="lab-muted" style="margin:0 0 1rem;">Your home, rent and repairs in one place.</p>
    <div class="lab-card" style="margin-bottom:0.75rem;background:var(--lab-ink);color:#fff;">
        <p class="lab-muted" style="color:#99f6e4;margin:0 0 0.25rem;">Due 28 Sep</p>
        <p style="margin:0 0 0.7rem;font-size:1.55rem;font-weight:750;">£1,100.00</p>
        <button type="button" class="lab-btn" data-lab-open="lab-pay" style="background:#fff;color:var(--lab-ink);width:100%;">Pay September rent</button>
    </div>
    <button type="button" class="lab-card" data-lab-open="lab-home" style="margin-bottom:0.75rem;display:block;text-decoration:none;width:100%;text-align:left;font:inherit;cursor:pointer;">
        <span class="lab-pill lab-pill-ok">Let</span>
        <h3 style="margin:0.4rem 0 0.15rem;">Flat 12, E14 9RU</h3>
        <p class="lab-muted" style="margin:0;">1 Baltimore Wharf</p>
    </button>
    <button type="button" class="lab-card" data-lab-open="lab-tenant-repair" style="margin-bottom:0.75rem;display:block;text-decoration:none;width:100%;text-align:left;font:inherit;cursor:pointer;">
        <p class="lab-label">Open repairs</p>
        <p style="margin:0;font-weight:650;">Kitchen tap dripping</p>
        <p class="lab-muted" style="margin:0.2rem 0 0;">Pending · 14 Sep</p>
    </button>
    <button type="button" class="lab-card" data-lab-open="lab-calendar" style="display:block;width:100%;text-align:left;font:inherit;cursor:pointer;">
        <p class="lab-label">Upcoming</p>
        <p style="margin:0;font-weight:650;">Gas Safe visit · 22 Sep</p>
    </button>
</x-ui-lab.phone>
