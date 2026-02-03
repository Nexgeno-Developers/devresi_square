<!-- resources/views/backend/properties/edit.blade.php -->
@extends('backend.layout.app')

@section('content')
<div class="container">
    <!-- Include the step add form -->
    @include('backend.properties.form_components.form', ['property' => $property])  
</div>
@endsection