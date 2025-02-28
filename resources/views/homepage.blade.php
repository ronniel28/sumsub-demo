@extends('layouts.app')

@section('content')

    <div class="container">
        <h1>Welcome to the Homepage</h1>
        @auth
            <p>Hello, {{ auth()->user()->full_name }}!</p>
        @endauth
    </div>
@endsection