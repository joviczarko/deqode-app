@extends('templates.default')

@section('module')
    <article data-deqode-module="content">
        <h1>{{ $title }}</h1>

        @if (filled($body))
            <div class="content-body">
                {!! $body !!}
            </div>
        @endif
    </article>
@endsection
