@extends('templates.default')

@section('module')
    <article data-deqode-module="link_hub">
        <h1>{{ $title }}</h1>

        @if ($links !== [])
            <nav class="link-hub-list">
                <ul>
                    @foreach ($links as $link)
                        <li>
                            <a href="{{ $link['url'] }}" rel="noopener noreferrer">{{ $link['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </article>
@endsection
