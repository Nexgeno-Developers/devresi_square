@extends('backend.layout.app')

@section('content')
    @include('backend.partials.assets.select2')
    <div class="container">
        <h1>Add Transaction</h1>

        <form action="{{ route('backend.transactions.store') }}" method="POST">
            @include('backend.transactions._form')

            <div class="d-flex justify-content-end gap-3 mt-3">
                <button type="submit" class="btn btn-success">Save Transaction</button>
                <a href="{{ route('backend.transactions.index') }}" class="btn btn-secondary btn">Cancel</a>
            </div>
        </form>

    </div>
@endsection