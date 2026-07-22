@extends('templates.default')

@section('module')
    <article data-deqode-module="form">
        <h1>{{ $title }}</h1>

        @if ($submitted)
            <p role="status" data-deqode-lead-success="1">Thanks — your response was submitted.</p>
        @endif

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ $action }}" data-deqode-lead-form="1">
            @csrf

            @foreach ($fields as $field)
                <label for="field-{{ $field['key'] }}">
                    {{ $field['label'] }}
                    @if ($field['required'])
                        <span aria-hidden="true">*</span>
                    @endif
                </label>

                @if ($field['type'] === 'textarea')
                    <textarea
                        id="field-{{ $field['key'] }}"
                        name="{{ $field['key'] }}"
                        @if ($field['required']) required @endif
                    >{{ old($field['key']) }}</textarea>
                @else
                    <input
                        id="field-{{ $field['key'] }}"
                        type="{{ $field['type'] === 'email' ? 'email' : 'text' }}"
                        name="{{ $field['key'] }}"
                        value="{{ old($field['key']) }}"
                        @if ($field['required']) required @endif
                    >
                @endif
            @endforeach

            <button type="submit">Submit</button>
        </form>
    </article>
@endsection
