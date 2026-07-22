@extends('templates.default')

@section('module')
    <article data-deqode-module="file_download">
        <h1>{{ $title }}</h1>

        <p>{{ $downloadName }}</p>

        <p>
            <a href="{{ $downloadUrl }}" data-deqode-download="1">Download</a>
        </p>
    </article>
@endsection
