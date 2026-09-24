@php
    $empty = (bool) ($empty ?? false);
@endphp

<p class="lab-note">{{ $empty
    ? 'Same overview with no records. Modules and tables stay; empty copy sits inside them. The page does not collapse into onboarding.'
    : 'Account overview, not a personal briefing. Status lives on rows. Alerts stay in the header. Compare Home empty for zero records.'
}}</p>

<x-ui-lab.shell active="Home" :empty-account="$empty">
    <div class="ov-head">
        <div>
            <p class="lab-kicker" style="margin:0 0 0.25rem;">This account</p>
            <h1>Overview</h1>
            <p class="lab-muted" style="margin:0.2rem 0 0;">September 2026 · Landlord Basic</p>
        </div>
        <div class="lab-row">
            <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-invoice">New invoice</button>
            <button type="button" class="lab-btn lab-btn-primary" data-lab-open="lab-add-property">Add property</button>
        </div>
    </div>

    <div class="ov-kpis">
        <div class="lab-card">
            <p class="lab-muted" style="margin:0 0 0.35rem;">Properties</p>
            <p style="margin:0;font-size:1.8rem;font-weight:750;">{{ $empty ? '0' : '2' }}</p>
        </div>
        <div class="lab-card">
            <p class="lab-muted" style="margin:0 0 0.35rem;">Occupied</p>
            <p style="margin:0;font-size:1.8rem;font-weight:750;">{{ $empty ? '0' : '1' }}</p>
        </div>
        <div class="lab-card">
            <p class="lab-muted" style="margin:0 0 0.35rem;">Vacant</p>
            <p style="margin:0;font-size:1.8rem;font-weight:750;">{{ $empty ? '0' : '1' }}</p>
        </div>
        <div class="lab-card">
            <p class="lab-muted" style="margin:0 0 0.35rem;">Rent due</p>
            <p style="margin:0;font-size:1.8rem;font-weight:750;">{{ $empty ? '£0.00' : '£1,100.00' }}</p>
        </div>
    </div>

    <div class="lab-grid-2" style="margin-bottom:1rem;">
        <div class="lab-card ov-mod">
            <div class="ov-mod-head">
                <p class="lab-label" style="margin:0;">Properties</p>
                <a class="lab-muted" href="{{ route('backend.ui_lab', ['screen' => $empty ? 'first-home' : 'shell']) }}">View all</a>
            </div>
            <table class="lab-table">
                <thead>
                    <tr><th>Property</th><th>Occupancy</th><th>Rent</th></tr>
                </thead>
                <tbody>
                    @if($empty)
                        <tr class="is-empty"><td colspan="3">No properties in this account.</td></tr>
                    @else
                        <tr>
                            <td>Flat 12, E14 9RU</td>
                            <td><span class="lab-pill lab-pill-ok">Let</span></td>
                            <td>£1,250.00</td>
                        </tr>
                        <tr>
                            <td>Buckingham Palace Rd</td>
                            <td><span class="lab-pill lab-pill-idle">Vacant</span></td>
                            <td>—</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="lab-card ov-mod">
            <div class="ov-mod-head">
                <p class="lab-label" style="margin:0;">Rent this month</p>
                <a class="lab-muted" href="{{ route('backend.ui_lab', ['screen' => 'finance']) }}">View all</a>
            </div>
            <table class="lab-table">
                <thead>
                    <tr><th>Invoice</th><th>Property</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @if($empty)
                        <tr class="is-empty"><td colspan="3">No invoices this month.</td></tr>
                    @else
                        <tr data-lab-open="lab-invoice-view" style="cursor:pointer;">
                            <td>September rent · Tina</td>
                            <td>Flat 12</td>
                            <td><span class="lab-pill lab-pill-warn">Unpaid</span></td>
                        </tr>
                        <tr data-lab-open="lab-invoice-view" style="cursor:pointer;">
                            <td>September rent · Sabir</td>
                            <td>Flat 12</td>
                            <td><span class="lab-pill lab-pill-ok">Paid</span></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="lab-grid-2">
        <div class="lab-card ov-mod">
            <div class="ov-mod-head">
                <p class="lab-label" style="margin:0;">Repairs</p>
                <a class="lab-muted" href="{{ route('backend.ui_lab', ['screen' => 'repairs']) }}">View all</a>
            </div>
            <table class="lab-table">
                <thead>
                    <tr><th>Issue</th><th>Property</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @if($empty)
                        <tr class="is-empty"><td colspan="3">No repairs on this account.</td></tr>
                    @else
                        <tr data-lab-open="lab-repair-case" style="cursor:pointer;">
                            <td>Kitchen tap dripping</td>
                            <td>Flat 12</td>
                            <td><span class="lab-pill lab-pill-warn">Pending</span></td>
                        </tr>
                        <tr data-lab-open="lab-repair-case" style="cursor:pointer;">
                            <td>Entrance lock stiff</td>
                            <td>Flat 12</td>
                            <td><span class="lab-pill lab-pill-warn">Under way</span></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="lab-card ov-mod">
            <div class="ov-mod-head">
                <p class="lab-label" style="margin:0;">September</p>
                <button type="button" class="lab-muted" data-lab-open="lab-today" style="border:0;background:none;font:inherit;cursor:pointer;">Open calendar</button>
            </div>
            <div class="cal-head">
                <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
            </div>
            <div class="cal" style="margin-top:0.4rem;">
                <i class="mute">31</i><i>1</i><i>2</i><i>3</i><i>4</i><i>5</i><i>6</i>
                <i>7</i><i>8</i><i>9</i><i>10</i><i>11</i><i>12</i><i>13</i>
                <i>14</i><i class="today">15</i><i>16</i><i>17</i><i>18</i><i>19</i><i>20</i>
                <i>21</i><i class="{{ $empty ? '' : 'event' }}">22</i><i>23</i><i>24</i><i>25</i><i>26</i><i>27</i>
                <i>28</i><i>29</i><i>30</i><i class="mute">1</i><i class="mute">2</i><i class="mute">3</i><i class="mute">4</i>
            </div>
            <p class="lab-muted" style="margin:0.7rem 0 0;">{{ $empty ? 'No visits booked this month.' : 'Gas Safe visit · Flat 12 · 22 Sep' }}</p>
        </div>
    </div>
</x-ui-lab.shell>
