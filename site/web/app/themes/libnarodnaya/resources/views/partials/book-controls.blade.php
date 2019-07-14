@php
    /** @var \WP_post $book */
    $book = $book ?? get_post();

    if (!\App\Includes\Book::is_available($book) and !\App\Includes\User::is_privileged()) {
        return;
    }
@endphp

<form class="book-controls" action="{!! get_permalink() !!}" method="POST">
    @if(\App\Includes\Book::is_available($book))
        @if(is_user_logged_in())
            <button type="submit"
                    name="{!! \App\Constants\Post\Book\Payload::HOLD_ARG !!}"
                    value="{!! get_the_ID() !!}"
                    class="btn btn--success">Взять</button>
        @else
            <a class="btn btn--primary" href="{!! \App\Includes\User::get_auth_callback_url(get_permalink()) !!}">Войти через ВКонтакте</a>
        @endif
    @endif

    @if(\App\Includes\User::is_privileged())
        <button type="submit"
                name="{!! \App\Constants\Post\Book\Payload::UN_HOLD_ARG !!}"
                value="{!! get_the_ID() !!}"
                class="btn btn-outline--secondary">Вернуть</button>
    @endif
</form>
