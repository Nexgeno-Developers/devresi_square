<x-ui-lab.overlay id="add-property" title="Add property">
    <div class="steps"><b>1 Property</b><span>2 Owners</span><span>3 Tenants</span></div>
    <p class="lab-muted" style="margin:0 0 0.85rem;">Search by postcode. We fill local authority and EPC where we can.</p>
    <label class="lab-field"><span>Postcode</span><input value="E14 9RU"></label>
    <div class="lab-row">
        <span class="lab-btn lab-btn-primary">Search</span>
        <button type="button" class="lab-btn lab-btn-secondary" data-lab-close>Cancel</button>
    </div>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="certificates" title="Certificates · Flat 12" type="wide">
    <p class="lab-muted" style="margin:0 0 0.9rem;">EPC comes from the register. Gas and electrical still need a file on record.</p>
    <div class="lab-grid-3">
        <div class="lab-card">
            <div class="lab-row" style="justify-content:space-between;"><strong>EPC</strong><span class="lab-pill lab-pill-ok">On the register</span></div>
            <p style="margin:0.55rem 0 0;font-size:1.4rem;font-weight:750;">Rating B</p>
        </div>
        <div class="lab-card">
            <div class="lab-row" style="justify-content:space-between;"><strong>Gas Safe</strong><span class="lab-pill lab-pill-warn">Upload needed</span></div>
            <p class="lab-muted">Annual certificate for lets with gas.</p>
            <button type="button" class="lab-btn lab-btn-primary" data-lab-open="lab-upload">Upload certificate</button>
        </div>
        <div class="lab-card">
            <div class="lab-row" style="justify-content:space-between;"><strong>EICR</strong><span class="lab-pill lab-pill-warn">Upload needed</span></div>
            <p class="lab-muted">Electrical safety, usually every 5 years.</p>
            <button type="button" class="lab-btn lab-btn-primary" data-lab-open="lab-upload">Upload certificate</button>
        </div>
    </div>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="add-tenancy" title="Add tenancy">
    <div class="steps"><b>1 Household</b><span>2 Rent &amp; term</span><span>3 Deposit</span></div>
    <label class="lab-field"><span>Property</span>
        <select><option>Flat 12, E14 9RU</option></select>
    </label>
    <label class="lab-field"><span>Tenants</span>
        <input placeholder="Tina Tenant">
    </label>
    <div class="lab-grid-2">
        <label class="lab-field"><span>Move in</span><input value="1 Jan 2026"></label>
        <label class="lab-field"><span>Monthly rent</span><input value="£1,250.00"></label>
    </div>
    <div class="lab-row">
        <span class="lab-btn lab-btn-primary">Continue to deposit</span>
        <button type="button" class="lab-btn lab-btn-secondary" data-lab-close>Cancel</button>
    </div>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="tenancy" title="Tina Tenant · Flat 12" type="wide">
    <div class="lab-row" style="margin-bottom:0.85rem;">
        <span class="lab-pill lab-pill-ok">Active</span>
        <span class="lab-muted">Assured shorthold · moved in 1 Jan 2026 · periodic</span>
    </div>
    <div class="lab-grid-3" style="margin-bottom:0.85rem;">
        <div class="lab-card">
            <p class="lab-label">Household</p>
            <div class="people">
                <span class="person"><i>TT</i> Tina Tenant · main</span>
            </div>
            <p class="lab-muted" style="margin:0.7rem 0 0;">tenant@resisquare.test · can sign in</p>
        </div>
        <div class="lab-card">
            <p class="lab-label">Rent</p>
            <p style="margin:0;font-size:1.4rem;font-weight:700;">£1,250.00</p>
            <p class="lab-muted" style="margin:0.3rem 0 0;">Monthly · next due 9 Oct · £1,100 unpaid</p>
        </div>
        <div class="lab-card">
            <p class="lab-label">Deposit</p>
            <p style="margin:0;font-size:1.4rem;font-weight:700;">£1,442.31</p>
            <p class="lab-muted" style="margin:0.3rem 0 0;">Landlord holding · not protected</p>
        </div>
    </div>
    <div class="lab-card">
        <p class="lab-label">Deposit trail</p>
        <div class="metric"><span>Received</span><span class="lab-muted">Not recorded</span></div>
        <div class="metric"><span>Protected</span><span class="lab-pill lab-pill-warn">Empty</span></div>
        <div class="metric"><span>Prescribed information</span><span class="lab-muted">Not sent</span></div>
        <div class="metric"><span>Written terms</span><span class="lab-muted">Not sent</span></div>
    </div>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="invoice" title="Issue rent">
    <p class="lab-muted" style="margin:0 0 0.85rem;">Let is always a field. One let is pre-selected; none leaves the field empty.</p>
    <label class="lab-field"><span>Let</span>
        <select>
            <option>Tina Tenant · Flat 12, E14 9RU</option>
            <option>Sabir Sayyed · Flat 12, E14 9RU</option>
        </select>
    </label>
    <label class="lab-field"><span>Period</span>
        <input value="September 2026">
    </label>
    <div class="lab-grid-2">
        <label class="lab-field"><span>Amount</span><input value="£1,100.00"></label>
        <label class="lab-field"><span>Due</span><input value="28 Sep 2026"></label>
    </div>
    <label class="lab-field"><span>Note for the tenant</span>
        <textarea>September rent for Flat 12.</textarea>
    </label>
    <span class="lab-btn lab-btn-primary">Issue invoice</span>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="invoice-view" title="September rent">
    <div class="lab-row" style="margin-bottom:0.85rem;">
        <span class="lab-pill lab-pill-warn">Unpaid</span>
        <span class="lab-muted">Due 28 Sep 2026</span>
    </div>
    <div class="metric"><span>Tina Tenant · Flat 12</span><strong>£1,100.00</strong></div>
    <p class="lab-muted">No reminder sent yet. Mark paid if it arrived outside the portal.</p>
    <div class="lab-row">
        <span class="lab-btn lab-btn-primary">Mark as paid</span>
        <span class="lab-btn lab-btn-secondary">Send reminder</span>
    </div>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="report" title="Report a repair">
    <label class="lab-field"><span>Home</span>
        <select><option>Flat 12, E14 9RU</option></select>
    </label>
    <label class="lab-field"><span>What needs attention?</span>
        <textarea placeholder="Kitchen tap dripping after use…">Kitchen tap dripping</textarea>
    </label>
    <label class="lab-field"><span>Photos</span>
        <input value="Add photos" readonly>
    </label>
    <span class="lab-btn lab-btn-primary">Send request</span>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="repair-case" title="Kitchen tap dripping" type="wide">
    <div class="lab-row" style="margin-bottom:0.85rem;">
        <span class="lab-pill lab-pill-warn">Pending</span>
        <span class="lab-muted">Flat 12 · Tina · 14 Sep</span>
    </div>
    <div class="lab-grid-2">
        <div class="lab-card">
            <p class="lab-label">What happened</p>
            <p style="margin:0 0 0.5rem;">Hot tap in the kitchen drips after use. Started this week. No photos yet.</p>
            <p class="lab-muted" style="margin:0;">Priority · Normal</p>
        </div>
        <div class="lab-card">
            <p class="lab-label">Timeline</p>
            <div class="timeline">
                <div class="timeline-item"><div><strong>Reported</strong><div class="lab-muted">Tina · 14 Sep</div></div></div>
                <div class="timeline-item"><div><strong>Waiting for a contractor</strong><div class="lab-muted">Not booked</div></div></div>
            </div>
        </div>
    </div>
    <div class="lab-row" style="margin-top:1rem;">
        <span class="lab-btn lab-btn-primary">Mark as under way</span>
        <button type="button" class="lab-btn lab-btn-secondary" data-lab-close>Close</button>
    </div>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="link-property" title="Link this repair">
    <p class="lab-muted" style="margin:0 0 0.85rem;">Assign a home so the tenant can see it.</p>
    <label class="lab-field"><span>Property</span>
        <select><option>Flat 12, E14 9RU</option><option>Buckingham Palace Rd</option></select>
    </label>
    <span class="lab-btn lab-btn-primary">Save</span>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="add-person" title="Add tenant">
    <p class="lab-muted" style="margin:0 0 0.85rem;">One sheet: person, let and portal invite. Not Contacts, then Tenancies, then Portal Access.</p>
    <label class="lab-field"><span>Home</span>
        <select><option>Flat 12, E14 9RU</option></select>
    </label>
    <div class="lab-grid-2">
        <label class="lab-field"><span>Name</span><input placeholder="Tina Tenant"></label>
        <label class="lab-field"><span>Email</span><input placeholder="tina@email.com"></label>
    </div>
    <div class="lab-grid-2">
        <label class="lab-field"><span>Move in</span><input value="1 Jan 2026"></label>
        <label class="lab-field"><span>Monthly rent</span><input value="£1,250.00"></label>
    </div>
    <label class="lab-field"><span>Invite to the portal</span>
        <select><option>Send invite email</option><option>Save without invite</option></select>
    </label>
    <div class="lab-row">
        <span class="lab-btn lab-btn-primary">Save tenant</span>
        <button type="button" class="lab-btn lab-btn-secondary" data-lab-close>Cancel</button>
    </div>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="invite" title="Portal access">
    <p class="lab-muted" style="margin:0 0 0.85rem;">They only see their home, rent, documents and repairs.</p>
    <label class="lab-field"><span>Tenancy</span><select><option>Tina · Flat 12</option></select></label>
    <label class="lab-field"><span>Name</span><input value="Tina Tenant"></label>
    <label class="lab-field"><span>Email</span><input value="tenant@resisquare.test"></label>
    <div class="metric"><span>Status</span><span class="lab-pill lab-pill-ok">Can sign in</span></div>
    <div class="lab-row">
        <span class="lab-btn lab-btn-primary">Send invite</span>
        <span class="lab-btn lab-btn-danger">Revoke</span>
    </div>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="owners" title="Owner groups" type="wide">
    <p class="lab-muted" style="margin:0 0 0.85rem;">People on <code>owner_group_users</code>. Purchase date and status live on the group. Shares are not stored.</p>
    <div class="lab-grid-2">
        <div class="lab-card">
            <div class="lab-row" style="justify-content:space-between;margin-bottom:0.45rem;">
                <strong>Flat 12, E14 9RU</strong>
                <span class="lab-pill lab-pill-ok">Active</span>
            </div>
            <p class="lab-muted" style="margin:0 0 0.55rem;">Purchased 12 Mar 2021</p>
            <div class="people">
                <span class="person"><i>LL</i> Lara Landlord · Main</span>
            </div>
            <button type="button" class="lab-btn lab-btn-secondary" style="margin-top:0.75rem;" data-lab-open="lab-owners-edit">Edit</button>
        </div>
        <div class="lab-card">
            <div class="lab-row" style="justify-content:space-between;margin-bottom:0.45rem;">
                <strong>1 Staging Landlord Street</strong>
                <span class="lab-pill lab-pill-ok">Active</span>
            </div>
            <p class="lab-muted" style="margin:0 0 0.55rem;">Purchased 4 Jun 2019</p>
            <div class="people">
                <span class="person"><i>LL</i> Lara Landlord · Main</span>
            </div>
            <button type="button" class="lab-btn lab-btn-secondary" style="margin-top:0.75rem;" data-lab-open="lab-owners-edit">Edit</button>
        </div>
    </div>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="owners-edit" title="Edit owner group">
    <label class="lab-field"><span>Property</span><input value="Flat 12, E14 9RU" readonly></label>
    <label class="lab-field"><span>Main owner</span>
        <select><option>Lara Landlord</option></select>
    </label>
    <label class="lab-field"><span>Purchased</span><input value="12 Mar 2021"></label>
    <label class="lab-field"><span>Status</span>
        <select><option>Active</option><option>Inactive</option><option>Archived</option></select>
    </label>
    <span class="lab-btn lab-btn-primary">Save group</span>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="upload" title="Upload a file">
    <label class="lab-field"><span>Name</span><input value="Gas Safe certificate"></label>
    <label class="lab-field"><span>File</span><input value="Choose PDF or photo" readonly></label>
    <label class="lab-field"><span>Share with tenant</span>
        <select><option>Yes</option><option>Keep private</option></select>
    </label>
    <span class="lab-btn lab-btn-primary">Upload</span>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="share" title="Share with tenant">
    <p style="margin:0 0 0.85rem;">Share <strong>How to rent guide</strong> on Flat 12?</p>
    <p class="lab-muted">Tina will see it on Documents.</p>
    <div class="lab-row">
        <span class="lab-btn lab-btn-primary">Share</span>
        <button type="button" class="lab-btn lab-btn-secondary" data-lab-close>Keep private</button>
    </div>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="photo" title="Change photo">
    <p class="lab-muted" style="margin:0 0 0.85rem;">A square photo works best. Initials show until you add one.</p>
    <label class="lab-field"><span>Photo</span><input value="Choose image" readonly></label>
    <span class="lab-btn lab-btn-primary">Save photo</span>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="password" title="Change password">
    <label class="lab-field"><span>Current password</span><input type="password" value="••••••••"></label>
    <label class="lab-field"><span>New password</span><input type="password"></label>
    <label class="lab-field"><span>Confirm</span><input type="password"></label>
    <span class="lab-btn lab-btn-primary">Update password</span>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="event" title="Add event">
    <label class="lab-field"><span>Title</span><input value="Gas Safe visit"></label>
    <label class="lab-field"><span>Home</span>
        <select><option>Flat 12, E14 9RU</option></select>
    </label>
    <div class="lab-grid-2">
        <label class="lab-field"><span>Date</span><input value="22 Sep 2026"></label>
        <label class="lab-field"><span>Time</span><input value="10:00"></label>
    </div>
    <span class="lab-btn lab-btn-primary">Save event</span>
</x-ui-lab.overlay>
