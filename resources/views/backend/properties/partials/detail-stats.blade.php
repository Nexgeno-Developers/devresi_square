@php
    $activeTenancies = \App\Models\Tenancy::where('property_id', $property->id)->where('status','Active')->count();
    $openRepairs = \App\Models\RepairIssue::where('property_id', $property->id)
        ->whereIn('status', ['Pending','Reported','Under Process'])->count();
    $complianceCount = $property->complianceRecords()->count();
    $expiringCompliance = $property->complianceRecords()
        ->where('expiry_date','<=', now()->addMonths(2))
        ->where('expiry_date','>=', now())->count();
    $activeTenancy = \App\Models\Tenancy::where('property_id', $property->id)
        ->where('status', 'Active')
        ->orderByDesc('move_in')
        ->first();
    $nextRentDue = '-';
    if ($activeTenancy?->move_in) {
        $freq = strtolower((string) $activeTenancy->frequency);
        $cursor = $activeTenancy->move_in->copy()->startOfDay();
        $today = now()->startOfDay();
        $guard = 0;
        while ($cursor->lt($today) && $guard < 240) {
            $guard++;
            if (str_contains($freq, 'week')) {
                $cursor->addWeek();
            } elseif (str_contains($freq, 'quarter')) {
                $cursor->addMonths(3);
            } elseif (str_contains($freq, 'year') || str_contains($freq, 'annual')) {
                $cursor->addYear();
            } else {
                $cursor->addMonth();
            }
        }
        $nextRentDue = $cursor->format('d M');
    }
    $daysListed = 0;
    if ($property->created_at) {
        $daysListed = max(0, (int) $property->created_at->copy()->startOfDay()->diffInDays(now()->startOfDay()));
    }
@endphp
<div class="pcc-stats-bar" id="pccDetailStats">
    <a href="{{ route('admin.tenancies.index', ['propertyId'=>$property->id]) }}" class="pcc-stat-pill">
        <div class="pcc-stat-icon"><i class="bi bi-house-door"></i></div>
        <div class="pcc-stat-content">
            <div class="pcc-stat-value">{{ $activeTenancies }}</div>
            <div class="pcc-stat-label">Tenancies</div>
        </div>
    </a>
    <div class="pcc-stat-pill">
        <div class="pcc-stat-icon"><i class="bi bi-currency-pound"></i></div>
        <div class="pcc-stat-content">
            <div class="pcc-stat-value">£{{ number_format($property->letting_price ?? 0, 0) }}</div>
            <div class="pcc-stat-label">Rent/mo</div>
        </div>
    </div>
    <a href="{{ route('admin.properties.index', ['property_id'=>$property->id,'tabname'=>'Compliance']) }}" class="pcc-stat-pill">
        <div class="pcc-stat-icon"><i class="bi bi-shield-check"></i></div>
        <div class="pcc-stat-content">
            <div class="pcc-stat-value {{ $expiringCompliance > 0 ? 'text-warning' : 'text-success' }}">
                {{ $complianceCount }}
            </div>
            <div class="pcc-stat-label">Compliance {{ $expiringCompliance > 0 ? '('.$expiringCompliance.' expiring)' : '' }}</div>
        </div>
    </a>
    <a href="{{ route('admin.property_repairs.index', ['property_id'=>$property->id]) }}" class="pcc-stat-pill">
        <div class="pcc-stat-icon"><i class="bi bi-wrench"></i></div>
        <div class="pcc-stat-content">
            <div class="pcc-stat-value">{{ $openRepairs }}</div>
            <div class="pcc-stat-label">Open Repairs</div>
        </div>
    </a>
    <div class="pcc-stat-pill">
        <div class="pcc-stat-icon"><i class="bi bi-calendar"></i></div>
        <div class="pcc-stat-content">
            <div class="pcc-stat-value">{{ $daysListed }}</div>
            <div class="pcc-stat-label">Days Listed</div>
        </div>
    </div>
    <div class="pcc-stat-pill">
        <div class="pcc-stat-icon"><i class="bi bi-clock"></i></div>
        <div class="pcc-stat-content">
            <div class="pcc-stat-value">{{ $nextRentDue }}</div>
            <div class="pcc-stat-label">Next Rent Due</div>
        </div>
    </div>
</div>
