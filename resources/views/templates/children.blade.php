{{-- A section: its text, then a card for every page below it. --}}
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

        @if ($children !== [])
            <ul class="cards">
                @foreach ($children as $child)
                    <li class="card">
                        <div class="card-body">
                            <h2><a href="{{ $child['url'] }}">{{ $child['title'] }}</a></h2>
                            @if ($child['text'] !== '')
                                <p class="card-text">{{ $child['text'] }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </article>
@endsection
