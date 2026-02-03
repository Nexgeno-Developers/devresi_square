@extends('backend.layout.app')

@section('content')
<div class="container">
    <h2>Edit Estate Charge</h2>

    <form action="{{ route('backend.estate_charges.update', $estateCharge) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Reference No</label>
            <input type="text" name="ref_no" class="form-control" value="{{ $estateCharge->ref_no }}" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" required>{{ $estateCharge->description }}</textarea>
        </div>

        <div class="form-group">
            <label>Amount</label>
            <input type="number" name="amount" class="form-control" value="{{ $estateCharge->amount }}" required>
        </div>

        <div class="form-group">
            <label>Due Date</label>
            <input type="date" name="due_date" class="form-control" value="{{ $estateCharge->due_date }}" required>
        </div>

        <div class="form-group">
            <label>Status</label>
            <input type="text" name="status" class="form-control" value="{{ $estateCharge->status }}" required>
        </div>

        <button type="submit" class="btn btn_secondary">Update</button>
    </form>
</div>
@endsection
