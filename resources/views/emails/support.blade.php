<h1>Ticket</h1>
<p>{{ $content }}</p>
<p><b>Sender: </b>{{ $sender }}</p>
<p>
	<b>Details:</b>
	<br>
	@php echo $details; @endphp
</p>
<a class="btn btn-primary btn-md" href="{{ $link }}">See ticket</a>
