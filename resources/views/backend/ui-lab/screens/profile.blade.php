<p class="lab-note">Settings is one page: you, plan, alerts. Photo and password stay overlays. Billing is also the header plan chip.</p>

<x-ui-lab.shell active="Settings">
    <div class="lab-hero">
        <div>
            <p class="lab-kicker">Settings</p>
            <h1>Lara Landlord</h1>
            <p>Your name, plan and what we email you.</p>
        </div>
    </div>
    <div class="lab-grid-2">
        <div class="lab-card">
            <p class="lab-label">Profile</p>
            <div class="lab-row" style="margin-bottom:1rem;">
                <span class="shell-avatar" style="width:52px;height:52px;font-size:1rem;">LL</span>
                <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-photo">Change photo</button>
            </div>
            <label class="lab-field"><span>First name</span><input value="Lara"></label>
            <label class="lab-field"><span>Last name</span><input value="Landlord"></label>
            <label class="lab-field"><span>Email</span><input value="you@example.com"></label>
            <label class="lab-field"><span>Phone</span><input placeholder="Add a phone"></label>
            <span class="lab-btn lab-btn-primary">Save</span>
        </div>
        <div>
            <div class="lab-card" style="margin-bottom:1rem;">
                <p class="lab-label">Plan &amp; billing</p>
                <div class="metric"><span>Landlord Basic</span><span class="lab-pill lab-pill-warn">Trialing</span></div>
                <div class="metric"><span>Homes</span><strong>2 / 5</strong></div>
                <div class="usage"><i></i></div>
                <p class="lab-muted">Trial ends 17 Sep · £29 / month after that.</p>
                <div class="lab-row">
                    <button type="button" class="lab-btn lab-btn-primary" data-lab-open="lab-billing">Add payment method</button>
                    <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-limit">Hit the limit</button>
                </div>
            </div>
            <div class="lab-card" style="margin-bottom:1rem;">
                <p class="lab-label">Password</p>
                <p class="lab-muted">Last changed · not recorded in this mock.</p>
                <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-password">Change password</button>
            </div>
            <div class="lab-card">
                <p class="lab-label">Email me about</p>
                <div class="metric"><span>Rent due or unpaid</span><span class="lab-pill lab-pill-ok">On</span></div>
                <div class="metric"><span>New repair</span><span class="lab-pill lab-pill-ok">On</span></div>
                <div class="metric"><span>Certificate expiring</span><span class="lab-pill lab-pill-ok">On</span></div>
                <p class="lab-muted" style="margin:0.7rem 0 0;">Not offers, contractor quotes or statements.</p>
            </div>
        </div>
    </div>
</x-ui-lab.shell>
