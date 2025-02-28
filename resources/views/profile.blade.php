@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Profile</h1>
        <p>Name: {{ Auth::user()->full_name }}</p>
        <p>Email: {{ Auth::user()->email }}</p>
        <a href="{{ route('verify.index') }}" class="btn btn-primary">Verify ID</a>
    </div>
@endsection