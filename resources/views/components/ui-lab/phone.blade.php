@props(['tab' => 'Home'])

@php
    $tabs = [
        'Home' => 'tenant',
        'Rent' => 'tenant-rent',
        'Repairs' => 'tenant-repairs',
        'Docs' => 'tenant-docs',
        'Me' => 'tenant-me',
    ];
@endphp

<div class="phone-wrap">
    <div class="phone">
        <div class="phone-top">
            <span>Resisquare</span>
            <button type="button" class="lab-muted" data-lab-open="lab-tenant-alerts" style="border:0;background:none;font:inherit;cursor:pointer;">Alerts</button>
        </div>
        <div class="phone-body">{{ $slot }}</div>
        <div class="phone-tabs">
            @foreach($tabs as $label => $key)
                <a href="{{ route('backend.ui_lab', ['screen' => $key]) }}" class="{{ $tab === $label ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
</div>

<x-ui-lab.overlay id="tenant-alerts" title="Alerts" type="sheet">
    <p class="lab-muted" style="margin:0 0 0.7rem;">Rent due and repair updates.</p>
    <div class="metric"><span>September rent due 28 Sep</span><span class="lab-pill lab-pill-warn">Unpaid</span></div>
    <div class="metric"><span>Kitchen tap · pending</span><span class="lab-muted">14 Sep</span></div>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="pay" title="Pay September rent" type="sheet">
    <p class="lab-muted" style="margin:0 0 0.85rem;">Pay by card. A small fee may apply.</p>
    <div class="metric"><span>Tina Tenant · Flat 12</span><strong>£1,100.00</strong></div>
    <label class="lab-field"><span>Card</span><input value="···· 4242" readonly></label>
    <span class="lab-btn lab-btn-primary" style="width:100%;">Pay £1,100.00</span>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="home" title="Flat 12, E14 9RU" type="sheet">
    <img src="https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=800&q=70" alt="" style="width:100%;height:120px;object-fit:cover;border-radius:12px;margin-bottom:0.85rem;">
    <span class="lab-pill lab-pill-ok">Active</span>
    <p class="lab-muted" style="margin:0.45rem 0 0.85rem;">1 Baltimore Wharf · moved in 1 Jan 2026</p>
    <div class="metric"><span>Rent</span><strong>£1,250 / month</strong></div>
    <div class="metric"><span>Deposit</span><strong>£1,442.31</strong></div>
    <div class="metric"><span>Landlord</span><strong>Lara Landlord</strong></div>
    <span class="lab-btn lab-btn-secondary" style="width:100%;margin-top:0.65rem;">Download agreement</span>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="calendar" title="September 2026" type="sheet">
    <div class="cal-head">
        <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
    </div>
    <div class="cal" style="margin-top:0.4rem;margin-bottom:0.85rem;">
        <i class="mute">31</i><i>1</i><i>2</i><i>3</i><i>4</i><i>5</i><i>6</i>
        <i>7</i><i>8</i><i>9</i><i>10</i><i>11</i><i>12</i><i>13</i>
        <i>14</i><i class="today">15</i><i>16</i><i>17</i><i>18</i><i>19</i><i>20</i>
        <i>21</i><i class="event">22</i><i>23</i><i>24</i><i>25</i><i>26</i><i>27</i>
        <i>28</i><i>29</i><i>30</i><i class="mute">1</i><i class="mute">2</i><i class="mute">3</i><i class="mute">4</i>
    </div>
    <strong>Gas Safe visit</strong>
    <p class="lab-muted" style="margin:0.3rem 0 0;">22 Sep · contractor at the property</p>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="tenant-repair" title="Kitchen tap dripping" type="sheet">
    <span class="lab-pill lab-pill-warn">Pending</span>
    <p style="margin:0.55rem 0 0.35rem;">Hot tap drips after use. Reported 14 Sep.</p>
    <p class="lab-muted">Your landlord has not booked a contractor yet.</p>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="tenant-report" title="Report a repair" type="sheet">
    <label class="lab-field"><span>What needs attention?</span>
        <textarea placeholder="Leaking tap in the kitchen…"></textarea>
    </label>
    <label class="lab-field"><span>Photos</span>
        <input value="Add photos" readonly>
    </label>
    <span class="lab-btn lab-btn-primary" style="width:100%;">Send request</span>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="tenant-doc" title="Tenancy agreement" type="sheet">
    <p class="lab-muted" style="margin:0 0 0.85rem;">Shared 14 Sep 2026.</p>
    <span class="lab-btn lab-btn-primary" style="width:100%;">Download PDF</span>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="tenant-photo" title="Change photo" type="sheet">
    <p class="lab-muted" style="margin:0 0 0.85rem;">Initials show until you add a photo.</p>
    <label class="lab-field"><span>Photo</span><input value="Choose image" readonly></label>
    <span class="lab-btn lab-btn-primary" style="width:100%;">Save photo</span>
</x-ui-lab.overlay>
