{{-- dashboard.blade.php (Final Layout) --}}
{{-- This file now extends the admin layout and includes dynamic content --}}

@extends('layouts.admin') {{-- Changed to extend the new admin layout --}}

@section('admin_content') {{-- Content will be injected into admin_content section --}}
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
