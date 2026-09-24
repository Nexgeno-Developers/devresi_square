<p class="lab-note">Zero properties: this is still the Properties workspace. The list, filters and detail pane stay. Add is a sheet, not a replacement page.</p>

<x-ui-lab.shell active="Properties" empty-account>
    <div class="pws" data-pws>
        <aside class="pws-list">
            <div class="pws-list-head">
                <span>0 homes</span>
                <button type="button" class="lab-btn lab-btn-secondary pws-add" data-lab-open="lab-add-property">Add</button>
            </div>
            <label class="pws-search">
                <span class="visually-hidden">Search properties</span>
                <input type="search" placeholder="Address or postcode" data-pws-search>
            </label>
            <div class="chips pws-chips">
                <button type="button" class="chip is-on" data-pws-filter="all">All</button>
                <button type="button" class="chip" data-pws-filter="Let">Lets</button>
                <button type="button" class="chip" data-pws-filter="Vacant">Vacant</button>
                <button type="button" class="chip" data-pws-filter="repairs">Repairs</button>
                <button type="button" class="chip" data-pws-filter="certs">Certs</button>
            </div>
            <div class="pws-rows">
                <p class="pws-empty">No properties in this account.</p>
            </div>
        </aside>
        <section class="pws-detail is-on" data-pws-detail="empty">
            <div class="pws-canvas" style="justify-content:center;">
                <div class="empty">
                    <div class="empty-icon">+</div>
                    <div style="font-weight:650;color:var(--lab-ink);">No property selected</div>
                    <p class="lab-muted" style="margin:0.35rem 0 0.85rem;">Add a property to open it in this pane.</p>
                    <button type="button" class="lab-btn lab-btn-primary" data-lab-open="lab-add-property">Add property</button>
                </div>
            </div>
        </section>
    </div>
</x-ui-lab.shell>
