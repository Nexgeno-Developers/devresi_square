@extends('backend.layout.app')

@section('content')
<h1>Create Branch</h1>
<form action="{{ route('admin.branches.store') }}" method="POST">
    @csrf
    @include('backend.branches.form', ['buttonText' => 'Create Branch'])
</form>
@endsection
