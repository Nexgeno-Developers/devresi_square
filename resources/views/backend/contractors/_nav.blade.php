<div class="lw-hero d-flex justify-content-between align-items-center gap-3 flex-wrap">
    <div>
        <h4>Contractors</h4>
        <p>Approving a contractor never creates a landlord account or subscription.</p>
    </div>
</div>
<ul class="nav nav-pills mb-3 flex-nowrap overflow-auto">
    @foreach([
        'index' => 'Review queue',
        'directory' => 'All contractors',
        'jobs' => 'Stuck and disputed jobs',
        'coverage' => 'Coverage gaps',
        'settings' => 'Settings',
    ] as $route => $label)
        <li class="nav-item">
            <a class="nav-link text-nowrap {{ request()->routeIs('admin.registrations.contractors.'.$route) ? 'active' : '' }}" href="{{ route('admin.registrations.contractors.'.$route) }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>
@foreach(['success', 'error'] as $flash)
    @if(session($flash))
        <div class="alert alert-{{ $flash === 'success' ? 'success' : 'danger' }}">{{ session($flash) }}</div>
    @endif
@endforeach
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
