<!DOCTYPE html>
<html>
<head>
	<title>Notify!!</title>
</head>
<body>

<h3>Notifications</h3>

@foreach ($sites as $url => $data)
	@if (count($data['messages']))
		<h4>{{ $url }}</h4>
		<ul>
			@foreach ($data['messages'] as $message)
				<ul>{{ $message }}</ul>
			@endforeach
		</ul>
	@endif
@endforeach

</body>
</html>