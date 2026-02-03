@extends('backend.layout.app')

@section('content')
<h1>Edit Branch</h1>
<form action="{{ route('admin.branches.update', $branch) }}" method="POST">
    @csrf
    @method('PUT')
    @include('backend.branches.form', ['branch' => $branch, 'buttonText' => 'Update Branch'])
</form>
@endsection
