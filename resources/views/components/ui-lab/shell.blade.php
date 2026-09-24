@props(['active' => 'Home', 'emptyAccount' => false])

@php
    $alerts = $emptyAccount ? 0 : 3;
    $repairs = $emptyAccount ? 0 : 3;
    $homes = $emptyAccount ? 0 : 2;
    $items = [
        'Home' => ['key' => 'dashboard'],
        'Properties' => ['key' => 'shell'],
        'Finance' => ['key' => 'finance'],
        'Repairs' => ['key' => 'repairs', 'count' => $repairs],
        'People' => ['key' => 'people'],
        'Settings' => ['key' => 'profile'],
    ];
@endphp

<div class="shell">
    <header class="shell-top">
        <div class="shell-brand"><span class="shell-mark"></span> Resisquare</div>
        <div class="shell-top-tools">
            <button type="button" class="shell-search" data-lab-open="lab-search">Address, person or invoice</button>
            <button type="button" class="lab-btn lab-btn-ghost" data-lab-open="lab-today">Today</button>
            <button type="button" class="lab-btn lab-btn-ghost shell-alerts" data-lab-open="lab-alerts">
                Alerts
                @if($alerts)
                    <span class="shell-alert-count" aria-label="{{ $alerts }} unread alerts">{{ $alerts }}</span>
                @endif
            </button>
            <button type="button" class="lab-btn lab-btn-ghost" data-lab-open="lab-billing">Landlord Basic · {{ $homes }} of 5</button>
            <a class="shell-avatar-link" href="{{ route('backend.ui_lab', ['screen' => 'profile']) }}" title="Settings">
                <span class="shell-avatar">LL</span>
            </a>
        </div>
    </header>
    <nav class="shell-nav">
        <div class="shell-nav-items">
            @foreach($items as $label => $meta)
                <a href="{{ route('backend.ui_lab', ['screen' => $meta['key']]) }}" class="{{ $active === $label ? 'is-active' : '' }}">
                    <span>{{ $label }}</span>
                    @if(!empty($meta['count']))
                        <b class="shell-nav-count">{{ $meta['count'] }}</b>
                    @endif
                </a>
            @endforeach
        </div>
        <div class="shell-nav-foot">
            <button type="button" class="shell-collapse" data-shell-collapse><span>Collapse menu</span></button>
        </div>
    </nav>
    <main class="shell-main">{{ $slot }}</main>
</div>

<x-ui-lab.overlay id="search" title="Search" type="wide">
    <p class="lab-muted" style="margin:0 0 0.7rem;">One search. Homes, people and invoices — not the menu.</p>
    <label class="lab-field"><span>Find</span><input value="" placeholder="Flat 12, Tina, September rent" data-lab-search></label>
    <button type="button" class="metric" data-lab-open="lab-invoice-view" data-lab-search-row data-search="september rent tina invoice 1100">
        <span>September rent · Tina · Flat 12</span><span class="lab-pill lab-pill-bad">£1,100 unpaid</span>
    </button>
    <a class="metric" href="{{ route('backend.ui_lab', ['screen' => 'shell']) }}" data-lab-search-row data-search="flat 12 e14 baltimore tina home" style="text-decoration:none;color:inherit;">
        <span>Flat 12, E14 9RU</span><span class="lab-muted">Let · 1 bed</span>
    </a>
    <button type="button" class="metric" data-lab-open="lab-invite" data-lab-search-row data-search="tina tenant person">
        <span>Tina Tenant</span><span class="lab-muted">Tenant · can sign in</span>
    </button>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="today" title="Today">
    <p class="lab-muted" style="margin:0 0 0.7rem;">Visits live here, not as a sidebar item.</p>
    <div class="metric"><span>Gas Safe visit · Flat 12</span><strong>22 Sep</strong></div>
    <div class="metric"><span>Inventory · Buckingham Palace Rd</span><span class="lab-muted">Not booked</span></div>
    <div class="cal-head" style="margin-top:0.85rem;">
        <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
    </div>
    <div class="cal" style="margin-top:0.4rem;margin-bottom:0.85rem;">
        <i class="mute">31</i><i>1</i><i>2</i><i>3</i><i>4</i><i>5</i><i>6</i>
        <i>7</i><i>8</i><i>9</i><i>10</i><i>11</i><i>12</i><i>13</i>
        <i>14</i><i class="today">15</i><i>16</i><i>17</i><i>18</i><i>19</i><i>20</i>
        <i>21</i><i class="event">22</i><i>23</i><i>24</i><i>25</i><i>26</i><i>27</i>
        <i>28</i><i>29</i><i>30</i><i class="mute">1</i><i class="mute">2</i><i class="mute">3</i><i class="mute">4</i>
    </div>
    <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-event">Add visit</button>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="alerts" title="Alerts">
    <p class="lab-muted" style="margin:0 0 0.7rem;">Rent, repair and certificate notices for this account.</p>
    @if($alerts)
        <div class="metric"><span>Kitchen tap reported on Flat 12</span><span class="lab-muted">14 Sep</span></div>
        <div class="metric"><span>September rent unpaid · Tina</span><span class="lab-muted">Today</span></div>
        <div class="metric"><span>Gas Safe certificate missing</span><span class="lab-muted">Overdue</span></div>
    @else
        <p class="lab-muted" style="margin:0;">No unread alerts.</p>
    @endif
</x-ui-lab.overlay>

<x-ui-lab.overlay id="billing" title="Plan & billing">
    <p class="lab-label">Landlord Basic</p>
    <p style="margin:0 0 0.85rem;">Trial ends 17 Sep 2026 · £29 / month after that</p>
    <div class="metric"><span>Properties</span><strong>{{ $homes }} / 5</strong></div>
    <div class="usage"><i></i></div>
    <div class="metric"><span>Status</span><span class="lab-pill lab-pill-warn">Trialing</span></div>
    <p class="lab-muted">Add-ons for branch and staff stay off this plan.</p>
    <span class="lab-btn lab-btn-primary">Add payment method</span>
    <button type="button" class="lab-btn lab-btn-secondary" style="margin-top:0.55rem;" data-lab-open="lab-limit">What if I hit 5 homes?</button>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="limit" title="Plan limit">
    <p style="margin:0 0 0.55rem;font-weight:650;">5 of 5 homes used</p>
    <p class="lab-muted" style="margin:0 0 0.85rem;">Adding another home opens this sheet — not a 403. Extra Property is £5 / month.</p>
    <div class="metric"><span>Extra Property</span><strong>£5 / mo</strong></div>
    <span class="lab-btn lab-btn-primary" style="width:100%;">Add Extra Property</span>
</x-ui-lab.overlay>

@include('backend.ui-lab.overlays.landlord')
