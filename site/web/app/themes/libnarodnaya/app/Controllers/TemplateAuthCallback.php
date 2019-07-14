<?php

namespace App\Controllers;

use App\Constants\Auth\Providers;
use Sober\Controller\Controller;

class TemplateAuthCallback extends Controller
{
    public static function auth(string $provider, string $redirect_url = '') {
        try {
            return \App\Includes\User::auth(Providers::VK, false, $redirect_url);
        }
        catch (\Exception $exception) {
            var_dump($exception->getMessage());

            exit;
        }
    }

    public static function maybe_register($auto_login = false, $redirect = false) {
        $provider = \App\Constants\Auth\Providers::VK;

        $redirect_url = $redirect ? ($_REQUEST[\App\Includes\User::AUTH_REDIRECT_PARAM] ?? '') : '';

        $profile = static::auth($provider, $redirect_url);

        $user = \App\Includes\User::get_by_profile($profile, $provider);

        if (!$user) {
            $user = \App\Includes\User::register($profile, $provider);

            if ($user instanceof \WP_Error) {
                var_dump($user->get_error_messages());

                exit;
            }
        }

        if ($auto_login and $user) {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
        }

        if ($user and $redirect and $redirect_url) {
            if (wp_safe_redirect($redirect_url)) {
                exit;
            }
        }

        return $user;
    }
}
