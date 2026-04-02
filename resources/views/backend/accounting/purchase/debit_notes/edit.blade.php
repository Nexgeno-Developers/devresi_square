@extends('backend.layout.app')

@section('content')
<div class="container py-4">
    <h2>Edit {{ $title }}</h2>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route($routeName . '.update', $item->id) }}" method="POST">
        @include('backend.accounting.purchase.debit_notes._form')
    </form>
</div>
@endsection
