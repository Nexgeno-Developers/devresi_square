<form method="POST" action="{{ route('admin.registrations.contractors.jobs.override', $offer) }}" class="d-flex gap-1 flex-wrap align-items-center">
    @csrf
    <input name="note" required maxlength="1000" class="form-control form-control-sm" style="width:200px" placeholder="Note for the audit trail">
    @foreach($actions as $action => $label)
        <button class="btn btn-sm {{ $action === 'cancel_award' ? 'btn-outline-danger' : 'btn-outline-primary' }}" name="action" value="{{ $action }}" type="submit">{{ $label }}</button>
    @endforeach
</form>
