<article @php post_class('book-card') @endphp>
    <div class="container">
        <header>
            <h1 class="book-title book-title--align-center">{!! get_the_title() !!}</h1>
        </header>

        <div class="container">
            <div class="row">
                @if(has_post_thumbnail())
                    <div class="col col--6">
                        {!! get_the_post_thumbnail(get_post(), 'medium', ['class' => 'book-thumbnail']) !!}
                    </div>
                @endif

                    <div class="col col--6">
                        @php the_content() @endphp

                        @include('partials.book-controls')
                    </div>
            </div>
        </div>
    </div>
</article>
