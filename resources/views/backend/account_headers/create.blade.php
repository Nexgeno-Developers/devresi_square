@extends('backend.layout.app')

@section('content')
<div class="container">
    <h2>Create Account Header</h2>
    <a href="{{ route('backend.account_headers.index') }}" class="btn btn-secondary mb-3">Back to List</a>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('backend.account_headers.store') }}" method="POST" id="accountHeaderForm">
        @include('backend.account_headers._form', ['buttonText' => 'Create'])
    </form>
</div>
@endsection
