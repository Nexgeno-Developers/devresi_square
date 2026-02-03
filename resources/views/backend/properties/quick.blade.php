<!-- resources/views/backend/properties/quick.blade.php -->
@extends('backend.layout.app')

@section('content')

    @include('backend.properties.quick_form_components.form',['countries' => $countries])

@endsection
