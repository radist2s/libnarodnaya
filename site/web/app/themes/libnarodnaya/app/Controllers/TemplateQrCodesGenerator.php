<?php

namespace App\Controllers;

use App\Constants\Post\Types;
use Sober\Controller\Controller;

class TemplateQrCodesGenerator extends Controller
{
    const QR_CODES_PER_PAGE_LIMIT_MAX = 50;

    /**
     * @param int $posts_per_page
     * @param int $paged
     * @return array
     * @throws \Exception
     */
    public static function generate_book_qr_codes($posts_per_page = -1, $paged = 1) {
        $querier = new \WP_Query();

        $post_per_page_filtered = static::QR_CODES_PER_PAGE_LIMIT_MAX > 0 ?
            min(static::QR_CODES_PER_PAGE_LIMIT_MAX, (int)$posts_per_page) :
            (int)$posts_per_page;

        $books = $querier->query([
            'post_type'      => Types::BOOK,
            'posts_per_page' => $post_per_page_filtered,
            'paged' => (int)$paged
        ]);

        $qr_codes = [];

        foreach ($books as $book) {
            $book_url = static::get_book_url($book);

            $qr_codes[$book_url] = [
                'title' => get_the_title($book),
                'image' => \App\generate_qr_code($book_url),
                'formatted_url' => preg_replace('~^(?:https?:)?/+~iu', '', static::get_book_url($book, true))
            ];
        }

        return $qr_codes;
    }

    /**
     * @param \WP_Post $post
     * @param bool $wrapped_id
     * @return string
     * @throws \Exception
     */
    public static function get_book_url(\WP_Post $post, $wrapped_id = false) {
        $qr_code_base_url = defined('QR_CODE_BASE_URL') ? QR_CODE_BASE_URL : '';

        if (!$qr_code_base_url) {
            throw new \Exception('`QR_CODE_BASE_URL` const must be manually defined');
        }

        $base_url = trailingslashit($qr_code_base_url);

        if ($wrapped_id) {
            return "$base_url<strong>$post->ID</strong>";
        }
        else {
            return $base_url . $post->ID;
        }
    }
}
