@extends('backend.layout.app')

@section('content')
<div class="container">
    <h2>Add New Estate Charge</h2>

    <form action="{{ route('backend.estate_charges.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label>Reference No</label>
            <input type="text" name="ref_no" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" required></textarea>
        </div>

        <div class="form-group">
            <label>Amount</label>
            <input type="number" name="amount" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Due Date</label>
            <input type="date" name="due_date" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Status</label>
            <input type="text" name="status" class="form-control" required>
        </div>

        <button type="submit" class="btn btn_secondary">Save</button>
    </form>
</div>
@endsection
