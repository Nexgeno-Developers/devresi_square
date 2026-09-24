<p class="lab-note">Issue rent always includes a let field. One let is pre-selected. Invoice detail stays an overlay.</p>

<x-ui-lab.shell active="Finance">
    <div class="lab-hero">
        <div>
            <p class="lab-kicker">Finance</p>
            <h1>Rent invoices</h1>
            <p>£1,100 outstanding this month.</p>
        </div>
        <button type="button" class="lab-btn lab-btn-light" data-lab-open="lab-invoice">New invoice</button>
    </div>
    <div class="lab-grid-3" style="margin-bottom:1rem;">
        <div class="lab-card"><p class="lab-muted" style="margin:0 0 0.35rem;">Outstanding</p><p style="margin:0;font-size:1.5rem;font-weight:750;">£1,100.00</p></div>
        <div class="lab-card"><p class="lab-muted" style="margin:0 0 0.35rem;">Collected</p><p style="margin:0;font-size:1.5rem;font-weight:750;">£2,150.00</p></div>
        <div class="lab-card"><p class="lab-muted" style="margin:0 0 0.35rem;">Overdue</p><p style="margin:0;font-size:1.5rem;font-weight:750;">0</p></div>
    </div>
    <div class="lab-card">
        <table class="lab-table">
            <thead><tr><th>Invoice</th><th>Who</th><th>Due</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
                <tr data-lab-open="lab-invoice-view" style="cursor:pointer;">
                    <td>September rent</td>
                    <td>Tina Tenant · Flat 12</td>
                    <td>28 Sep 2026</td>
                    <td>£1,100.00</td>
                    <td><span class="lab-pill lab-pill-warn">Unpaid</span></td>
                </tr>
                <tr data-lab-open="lab-invoice-view" style="cursor:pointer;">
                    <td>September rent</td>
                    <td>Sabir Sayyed · Flat 12</td>
                    <td>28 Sep 2026</td>
                    <td>£950.00</td>
                    <td><span class="lab-pill lab-pill-ok">Paid</span></td>
                </tr>
                <tr data-lab-open="lab-invoice-view" style="cursor:pointer;">
                    <td>Holding deposit</td>
                    <td>Tina Tenant · Flat 12</td>
                    <td>14 Sep 2026</td>
                    <td>£1,200.00</td>
                    <td><span class="lab-pill lab-pill-ok">Paid</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</x-ui-lab.shell>
