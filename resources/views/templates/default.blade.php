{{-- A page: its heading and its text. --}}
@extends('atrium-theme::layout', [
    'metaTitle' => $page->getSeoTitle() ?: $title,
    'metaDescription' => $page->getSeoDescription(),
    'metaKeywords' => $page->getSeoKeywords(),
    'ogImage' => $page->getOgImage(),
])

@section('content')
    <article class="page page-{{ $page->template }}">
        <h1>{{ $title }}</h1>

        @if ($content)
            <div class="content">{!! $content !!}</div>
        @endif
    </article>
@endsection
