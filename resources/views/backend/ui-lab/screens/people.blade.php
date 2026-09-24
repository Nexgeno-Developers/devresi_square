<p class="lab-note">People is the directory. Add tenant is one sheet: person, let and invite. Owners stay an overlay on the card.</p>

<x-ui-lab.shell active="People">
    <div class="lab-hero">
        <div>
            <p class="lab-kicker">People</p>
            <h1>Tenants, owners and others</h1>
            <p>Invite to the portal from the person’s card.</p>
        </div>
        <button type="button" class="lab-btn lab-btn-light" data-lab-open="lab-add-person">Add tenant</button>
    </div>
    <div class="chips">
        <span class="chip is-on">All</span>
        <span class="chip">Tenants</span>
        <span class="chip">Owners</span>
        <span class="chip">Others</span>
    </div>
    <div class="lab-grid-2">
        <article class="lab-card">
            <div class="lab-row" style="margin-bottom:0.5rem;">
                <span class="shell-avatar">TT</span>
                <div>
                    <strong>Tina Tenant</strong>
                    <div class="lab-muted">tenant@resisquare.test</div>
                </div>
                <span class="lab-pill lab-pill-ok" style="margin-left:auto;">Tenant</span>
            </div>
            <p class="lab-muted" style="margin:0 0 0.7rem;">Flat 12, E14 9RU</p>
            <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-invite">Portal access</button>
        </article>
        <article class="lab-card">
            <div class="lab-row" style="margin-bottom:0.5rem;">
                <span class="shell-avatar">SS</span>
                <div>
                    <strong>Sabir Sayyed</strong>
                    <div class="lab-muted">sabir.nexgeno@gmail.com</div>
                </div>
                <span class="lab-pill lab-pill-ok" style="margin-left:auto;">Tenant</span>
            </div>
            <p class="lab-muted" style="margin:0 0 0.7rem;">Flat 12, E14 9RU</p>
            <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-invite">Portal access</button>
        </article>
        <article class="lab-card">
            <div class="lab-row" style="margin-bottom:0.5rem;">
                <span class="shell-avatar">CC</span>
                <div>
                    <strong>Chris Contact</strong>
                    <div class="lab-muted">landlord.contact@resisquare.test</div>
                </div>
                <span class="lab-pill lab-pill-idle" style="margin-left:auto;">Owner</span>
            </div>
            <p class="lab-muted" style="margin:0 0 0.7rem;">Buckingham Palace Rd</p>
            <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-owners">Owners</button>
        </article>
    </div>
</x-ui-lab.shell>
