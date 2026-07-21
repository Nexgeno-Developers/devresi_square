@extends('backend.layout.app')

@section('content')
@if(isset($saasMetrics))
    @include('backend.dashboard.super-admin')
@else
    @include('backend.dashboard.account')
@endif
@endsection
