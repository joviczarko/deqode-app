@extends('layouts.wrapper')

@section('title', $title ?? config('app.name'))

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <style>
        :root {
            --deqode-accent: #0f766e;
            --pico-primary: var(--deqode-accent);
        }
    </style>
@endpush

@section('body')
    <main class="container" data-deqode-template="default">
        @yield('module')
    </main>
@endsection
