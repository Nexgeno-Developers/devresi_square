@extends('backend.layout.app')

@section('content')
{{-- Calendar --}}
<div class="container m-4">
    <h2>Upcoming Events</h2>
    @include('backend.partials.calendar')
</div>
    
@endsection

@section('page.scripts')

@endsection
