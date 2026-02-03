@extends('backend.layout.app')

@section('content')
    @include('backend.partials.assets.select2')
    <div class="container">
        <h1>Edit Transaction</h1>

        <form action="{{ route('backend.transactions.update', $transaction) }}" method="POST">
            @method('PUT')
            @include('backend.transactions._form')

            <button type="submit" class="btn btn-primary">Update Transaction</button>
            <a href="{{ route('backend.transactions.index') }}" class="btn btn-secondary">Cancel</a>
        </form>

    </div>
@endsection