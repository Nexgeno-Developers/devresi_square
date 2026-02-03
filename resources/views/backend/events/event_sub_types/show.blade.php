@extends('backend.layout.app')

@section('content')
    <div class="container">
        <h3>Event SubType Details</h3>

        <div class="card mt-4">
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">ID</dt>
                    <dd class="col-sm-9">{{ $eventSubType->id }}</dd>

                    <dt class="col-sm-3">Event Type</dt>
                    <dd class="col-sm-9">{{ $eventSubType->type->name }}</dd>

                    <dt class="col-sm-3">Name</dt>
                    <dd class="col-sm-9">{{ $eventSubType->name }}</dd>

                    {{-- <dt class="col-sm-3">Slug</dt>
                    <dd class="col-sm-9">{{ $eventSubType->slug ?? '-' }}</dd> --}}

                    <dt class="col-sm-3">Description</dt>
                    <dd class="col-sm-9">{{ $eventSubType->description ?? '-' }}</dd>

                    <dt class="col-sm-3">Created At</dt>
                    <dd class="col-sm-9">{{ $eventSubType->created_at->format('Y-m-d H:i') }}</dd>

                    <dt class="col-sm-3">Updated At</dt>
                    <dd class="col-sm-9">{{ $eventSubType->updated_at->format('Y-m-d H:i') }}</dd>
                </dl>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('backend.event_sub_types.edit', $eventSubType) }}" class="btn btn-warning">Edit</a>
                <a href="{{ route('backend.event_sub_types.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
@endsection