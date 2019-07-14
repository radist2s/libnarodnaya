<?php


namespace App\Includes;

use App\Constants\Auth\Providers;
use Hybridauth;

class User
{
    const META_KEY_PROVIDER = 'auth_provider';
    const META_KEY_IDENTIFIER = 'identifier';
    const META_KEY_PROVIDER_PROFILE = 'provider_profile';
    const AUTH_REDIRECT_PARAM = 'on_auth_redirect';
    const HOLDER_ROLE = 'contributor';

    /**
     * @var Hybridauth\Hybridauth
     */
    public static $hybridauth;

    /**
     * @param string $provider
     * @param bool $session_only
     * @param string $redirect_url
     * @return Hybridauth\User\Profile|null
     * @throws Hybridauth\Exception\InvalidArgumentException
     * @throws Hybridauth\Exception\UnexpectedValueException
     */
    public static function auth($provider = Providers::VK, $session_only = false, string $redirect_url = '') {
        $hybridauth = static::hybrid_auth_instance($redirect_url);

        if ($session_only and !$hybridauth->isConnectedWith($provider)) {
            return null;
        }

        //Attempt to authenticate users with a provider by name
        $adapter = $hybridauth->authenticate($provider);

        /*$adapter_expires_at = $adapter->getAccessToken()['expires_at'] ?? null;

        if ($adapter_expires_at and $adapter_expires_at <= time()) {
            $adapter->disconnect();

            if ($session_only) {
                return null;
            }

            $adapter = $hybridauth->authenticate($provider);
        }*/

        return $adapter->getUserProfile();
    }

    /**
     * @param string $redirect_url
     * @return Hybridauth\Hybridauth
     * @throws Hybridauth\Exception\InvalidArgumentException
     */
    public static function hybrid_auth_instance(string $redirect_url = '') {
        if (!static::$hybridauth) {
            static::$hybridauth = new Hybridauth\Hybridauth(static::get_config($redirect_url));
        }

        return static::$hybridauth;
    }

    public static function get_config(string $redirect_url = '') {
        if (!$auth_callback_url = static::get_auth_callback_url($redirect_url)) {
            return [];
        }

        return [
            'callback'  => $auth_callback_url,
            'providers' => [
                Providers::VK => [
                    'enabled' => true,
                    'keys'    => ['id' => AUTH_PROVIDER_VK_ID, 'secret' => AUTH_PROVIDER_VK_SECRET],
                ]
            ]
        ];
    }

    /**
     * @param string $redirect_url
     * @return string|null
     */
    public static function get_auth_callback_url($redirect_url = '') {
        $callback_post = get_field(\App\Constants\Options::HYBRID_AUTH_CALLBACK_POST, 'options');

        if ($callback_post and $callback_url = get_permalink($callback_post)) {
            $concat_symbol = (mb_strpos($callback_url, '?') === false) ? '?' : '&';

            $callback_url .= $concat_symbol . http_build_query([
                static::AUTH_REDIRECT_PARAM => $redirect_url
            ]);
        }

        return $callback_url ?? null;
    }

    /**
     * @param Hybridauth\User\Profile $profile
     * @param string $provider
     * @return \WP_User|null
     */
    public static function get_by_profile(Hybridauth\User\Profile $profile, string $provider) {
        if (!$profile) {
            return null;
        }

        $meta_query = [];

        foreach (static::user_registration_meta($profile, $provider) as $meta_key => $meta_value) {
            $meta_query[] = [
                'key'     => $meta_key,
                'value'   => $meta_value,
                'compare' => '==',
            ];
        }

        $users = get_users([
            'meta_query'  => $meta_query,
            'number'      => 1,
            'count_total' => false
        ]);

        return $users ? reset($users) : null;
    }

    public static function user_registration_meta(Hybridauth\User\Profile $profile, string $provider) {
        return [
            static::META_KEY_PROVIDER   => $provider,
            static::META_KEY_IDENTIFIER => $profile->identifier,
        ];
    }

    /**
     * @param Hybridauth\User\Profile $profile
     * @param string $provider
     * @return int|\WP_Error
     */
    public static function register(Hybridauth\User\Profile $profile, string $provider) {
        add_filter('registration_errors', $ignore_registration_empty_email_callback = function (\WP_Error $errors) {
            unset($errors->errors['empty_email']);

            return $errors;
        });

        $user_id = wp_insert_user([
            'user_email' => $profile->email ?: '',
            'user_login' => static::create_username($profile, $provider),
            'user_pass'  => wp_generate_password(12),
            'first_name' => wp_kses($profile->firstName, []),
            'last_name' => wp_kses($profile->lastName, []),
            'role' => static::HOLDER_ROLE,
        ]);

        remove_filter('registration_errors', $ignore_registration_empty_email_callback);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        foreach (static::user_registration_meta($profile, $provider) as $meta_key => $meta_value) {
            update_user_meta($user_id, $meta_key, $meta_value);
        }

        update_user_meta($user_id, \App\Includes\User::META_KEY_PROVIDER_PROFILE, json_encode($profile));

        $user = get_user_by('id', $user_id);

        return $user ?: new \WP_Error(403, "User with the ID $user_id is not exists");
    }

    public static function create_username(Hybridauth\User\Profile $profile, string $provider) {
        $provider_name = mb_strtolower($provider);

        return "{$provider_name}_{$profile->identifier}";
    }

    public static function is_privileged() {
        if (!current_user_can('edit_posts')) {
            return false;
        }

        return !in_array(\App\Includes\User::HOLDER_ROLE, wp_get_current_user()->roles);
    }
}
