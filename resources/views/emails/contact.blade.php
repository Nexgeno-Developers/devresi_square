
<p>
    <strong>Name:</strong> {{ $name }}<br>
    @if ($email != null || $email != '')
    <strong>Email:</strong> {{ $email }}
    <br>
    @endif
    @if ($phone != null || $phone != '')
    <strong>Phone:</strong> {{ $phone }}
    @endif 
</p>
<p>{!! $content !!}</p>
<p><strong>Page URL:</strong> <a href="{{ env('APP_URL') }}">Go to the website</a></p>

