<?php

namespace App;

/**
 * Add <body> classes
 */
add_filter('body_class', function (array $classes) {
    /** Add page slug if it doesn't exist */
    if (is_single() || is_page() && !is_front_page()) {
        if (!in_array(basename(get_permalink()), $classes)) {
            $classes[] = basename(get_permalink());
        }
    }

    /** Add class if sidebar is active */
    if (display_sidebar()) {
        $classes[] = 'sidebar-primary';
    }

    /** Clean up class names for custom templates */
    $classes = array_map(function ($class) {
        return preg_replace(['/-blade(-php)?$/', '/^page-template-views/'], '', $class);
    }, $classes);

    return array_filter($classes);
});

/**
 * Add "… Continued" to the excerpt
 */
add_filter('excerpt_more', function () {
    return ' &hellip; <a href="' . get_permalink() . '">' . __('Continued', 'sage') . '</a>';
});

/**
 * Template Hierarchy should search for .blade.php files
 */
collect([
    'index', '404', 'archive', 'author', 'category', 'tag', 'taxonomy', 'date', 'home',
    'frontpage', 'page', 'paged', 'search', 'single', 'singular', 'attachment', 'embed'
])->map(function ($type) {
    add_filter("{$type}_template_hierarchy", __NAMESPACE__.'\\filter_templates');
});

/**
 * Render page using Blade
 */
add_filter('template_include', function ($template) {
    collect(['get_header', 'wp_head'])->each(function ($tag) {
        ob_start();
        do_action($tag);
        $output = ob_get_clean();
        remove_all_actions($tag);
        add_action($tag, function () use ($output) {
            echo $output;
        });
    });
    $data = collect(get_body_class())->reduce(function ($data, $class) use ($template) {
        return apply_filters("sage/template/{$class}/data", $data, $template);
    }, []);
    if ($template) {
        echo template($template, $data);
        return get_stylesheet_directory().'/index.php';
    }
    return $template;
}, PHP_INT_MAX);

/**
 * Render comments.blade.php
 */
add_filter('comments_template', function ($comments_template) {
    $comments_template = str_replace(
        [get_stylesheet_directory(), get_template_directory()],
        '',
        $comments_template
    );

    $data = collect(get_body_class())->reduce(function ($data, $class) use ($comments_template) {
        return apply_filters("sage/template/{$class}/data", $data, $comments_template);
    }, []);

    $theme_template = locate_template(["views/{$comments_template}", $comments_template]);

    if ($theme_template) {
        echo template($theme_template, $data);
        return get_stylesheet_directory().'/index.php';
    }

    return $comments_template;
}, 100);

add_action('after_setup_theme', function () {
    if (!\App\Includes\User::is_privileged()) {
        show_admin_bar(false);

        if (is_admin()) {
            wp_redirect(home_url());
        }
    }
});

/**
 * Set Hold Payload prepare
 */
add_action('template_redirect', function () {
    if (!$book = get_post() or $book->post_type !== \App\Constants\Post\Types::BOOK) {
        return;
    }

    $holding_book_id = (int)($_REQUEST[\App\Constants\Post\Book\Payload::HOLD_ARG] ?? 0);

    if ($holding_book_id !== $book->ID) {
        return;
    }

    $holding_id = \App\Includes\Book::hold($book);

    if ($holding_id instanceof \WP_Error) {
        die(wpautop(implode(PHP_EOL, $holding_id->get_error_messages())));
    }

    wp_safe_redirect(get_permalink());

    exit;
});

/**
 * Set Not in Hold Payload prepare
 */
add_action('template_redirect', function () {
    if (!$book = get_post() or $book->post_type !== \App\Constants\Post\Types::BOOK) {
        return;
    }

    $holding_book_id = (int)($_REQUEST[\App\Constants\Post\Book\Payload::UN_HOLD_ARG] ?? 0);

    if ($holding_book_id !== $book->ID) {
        return;
    }

    $un_hold_status = \App\Includes\Book::un_hold($book);

    if ($un_hold_status instanceof \WP_Error) {
        die(wpautop(implode(PHP_EOL, $un_hold_status->get_error_messages())));
    }

    wp_safe_redirect(get_permalink());

    exit;
});
