<!-- resources/views/backend/properties/create.blade.php -->
@extends('backend.layout.app')

@section('content')
<div class="container">
    @if (request()->has('stepform'))
        <!-- Include the step add form -->
        @include('backend.properties.form_components.form')
    @else
         <!-- Include the quick add form -->
         @include('backend.properties.quick_form')
    @endif
</div>
@endsection
