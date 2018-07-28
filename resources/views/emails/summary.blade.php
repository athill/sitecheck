<!DOCTYPE html>
<html>
<head>
	<title>Summary!!</title>
</head>
<body>

<h3>Summary for {{ $checks['start'] }} to {{ $checks['end'] }}</h3>

@foreach ($checks['messages'] as $url => $data)
	<h4>Notices for {{ $url }}</h4>
	@if (count($data))
		<ul>
			@foreach ($data as $message)
				<ul>{{ $message }}</ul>
			@endforeach
		</ul>
	@endif
@endforeach

</body>
</html>
