<p class="lab-note">Today the repair list is an overlapping agency split-pane. Proposed: an inbox of cards. Open a case in a modal.</p>

<x-ui-lab.shell active="Repairs">
    <div class="lab-hero">
        <div>
            <p class="lab-kicker">Repairs</p>
            <h1>Repairs</h1>
            <p>Issues on this account.</p>
        </div>
        <button type="button" class="lab-btn lab-btn-light" data-lab-open="lab-report">Report a repair</button>
    </div>
    <div class="chips">
        <span class="chip is-on">Open</span>
        <span class="chip">Pending</span>
        <span class="chip">Under way</span>
        <span class="chip">Closed</span>
    </div>
    <div class="lab-card" style="margin-bottom: 0.85rem;">
        <div class="lab-row" style="justify-content:space-between;align-items:flex-start;">
            <div class="lab-row" style="align-items:flex-start;">
                <img class="lab-thumb" src="https://images.unsplash.com/photo-1552321554-5fefe8c9ef14?auto=format&fit=crop&w=200&q=70" alt="">
                <div>
                    <div class="lab-row" style="margin-bottom:0.35rem;">
                        <span class="lab-pill lab-pill-warn">Pending</span>
                        <span class="lab-muted">14 Sep</span>
                    </div>
                    <h3 style="margin:0 0 0.2rem;">Kitchen tap dripping</h3>
                    <p class="lab-muted" style="margin:0;">Flat 12, E14 9RU · reported by Tina</p>
                </div>
            </div>
            <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-repair-case">Open</button>
        </div>
    </div>
    <div class="lab-card" style="margin-bottom: 0.85rem;">
        <div class="lab-row" style="justify-content:space-between;">
            <div>
                <div class="lab-row" style="margin-bottom:0.35rem;">
                    <span class="lab-pill lab-pill-warn">Under way</span>
                    <span class="lab-muted">14 Sep</span>
                </div>
                <h3 style="margin:0 0 0.2rem;">Bathroom extractor noisy</h3>
                <p class="lab-muted" style="margin:0;">Flat 12, E14 9RU</p>
            </div>
            <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-repair-case">Open</button>
        </div>
    </div>
    <div class="lab-card">
        <div class="lab-row" style="justify-content:space-between;">
            <div>
                <div class="lab-row" style="margin-bottom:0.35rem;">
                    <span class="lab-pill lab-pill-idle">No property</span>
                    <span class="lab-muted">Needs linking</span>
                </div>
                <h3 style="margin:0 0 0.2rem;">General repair</h3>
                <p class="lab-muted" style="margin:0;">Assign this to a home so the tenant can see it.</p>
            </div>
            <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-link-property">Link property</button>
        </div>
    </div>
</x-ui-lab.shell>
