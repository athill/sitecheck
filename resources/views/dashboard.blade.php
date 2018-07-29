@extends('layouts.app')

@section('content')
<div class="container">

@foreach ($latest as $site)
    <h2>{{ $site->url }} Last updated: {{  $site->created_at }} </h2>
    <table border="1">
    <tr>
        <th>Status</th>
        <th>Key</th>
    </tr>
    @foreach ($site->statuses as $status)
        <tr>
            <td>{{ $status->key }}</td> 
            <td>{{ $status->up ? 'UP' : 'DOWN' }}</td>
        </tr>
    @endforeach
    </table>
@endforeach
</div>
@endsection
