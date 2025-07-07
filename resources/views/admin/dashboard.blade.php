{{-- dashboard.blade.php (Final) --}}
{{-- This file now extends the base layout and includes dynamic content --}}

@extends('app') {{-- Changed from layouts.app to app as your main layout is app.blade.php --}}

@section('content')
    {{-- Include the dashboard content partial --}}
    @include('partials._dashboard')

    {{-- Include other section content partials, initially hidden --}}
    @include('partials._products')
    @include('partials._orders')
    @include('partials._customers')
    @include('partials._reports')
    @include('partials._marketing')
    @include('partials._discounts')
    @include('partials._content_management')
    @include('partials._comments')
    @include('partials._support')
    @include('partials._shipping')
    @include('partials._payments')
    @include('partials._analytics')
    @include('partials._settings')
    @include('partials._user_management')
    @include('partials._backup')
@endsection
