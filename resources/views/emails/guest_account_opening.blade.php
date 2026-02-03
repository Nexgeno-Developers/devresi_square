<p>{{ 'Hi! An account has been created on'.' '.env('APP_NAME') }}</p>
<p>Your Email is: {{ $email }}</p>
<p>Your Password is: {{ $password }}</p>
<a class="btn btn-primary btn-md" href="{{ env('APP_URL') }}">Go to the website</a>