@extends('backend.layout.app')

@section('content')
    @include('backend.partials.assets.select2')
<div class="container py-4">
    <h2>Create {{ $title }}</h2>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route($routeName . '.store') }}" method="POST">
        @include('backend.accounting.sale.invoices._form')
    </form>
</div>
@endsection
