<?php


namespace App\Includes;


use App\Constants\Post;

class Book
{
    public static function hold(\WP_Post $book) {
        if (!static::user_can_take($book)) {
            return new \WP_Error(403, 'Вы не можете взять эту книгу');
        }

        if (!static::is_available($book)) {
            return new \WP_Error(403, 'Книга зарезервирована другим читателем');
        }

        $holding_id = static::set_in_hold($book, wp_get_current_user());

        return $holding_id;
    }

    public static function un_hold(\WP_Post $book) {
        $not_in_hold_status = static::set_not_in_hold($book);

        return $not_in_hold_status ? true : new \WP_Error(403, 'Ошибка: книга не была зарезервирована');
    }

    public static function user_can_take(\WP_Post $book) {
        $user = wp_get_current_user();

        return !!$user->ID;
    }

    public static function is_available(\WP_Post $book) {
        if (get_post_meta($book->ID, Post\Book\Meta::CURRENT_HOLDING, true)) {
            return false;
        }

        return $book->post_status === \App\Constants\Post\Book::AVAILABLE_POST_STATUS;
    }

    /**
     * @param \WP_Post $book
     * @param \WP_User $user
     * @return int|\WP_Error
     */
    public static function set_in_hold(\WP_Post $book, \WP_User $user) {
        $holding_id = wp_insert_post([
            'post_title'   => $book->post_title,
            'post_type'    => Post\Types::HOLDING,
            'post_author'  => $user->ID,
            'post_content' => $book->ID
        ]);

        if ($holding_id instanceof \WP_Error) {
            return $holding_id;
        }

        update_post_meta($holding_id, Post\Holding\Meta::BOOK, $book->ID);
        update_post_meta($book->ID, Post\Book\Meta::CURRENT_HOLDING, $holding_id);
        add_post_meta($book->ID, Post\Book\Meta::HOLDING, $holding_id);

        return $holding_id;
    }

    public static function set_not_in_hold(\WP_Post $book) {
        return delete_post_meta($book->ID, Post\Book\Meta::CURRENT_HOLDING);
    }
}
