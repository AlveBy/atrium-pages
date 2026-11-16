{{--
    A landing page: the text across the whole width, with no heading and
    no breadcrumbs, for pages that bring their own.
--}}
@extends('atrium-theme::layout', [
    'metaTitle' => $page->getSeoTitle() ?: $title,
    'metaDescription' => $page->getSeoDescription(),
    'metaKeywords' => $page->getSeoKeywords(),
    'ogImage' => $page->getOgImage(),
    'breadcrumbs' => [],
])

@section('content')
    <article class="page page-{{ $page->template }}">
        @if ($content)
            {!! $content !!}
        @endif
    </article>
@endsection
