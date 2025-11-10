<?php

use App\Consts;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Utils\BigNumber;

if (!function_exists('reset_password_url')) {
    function reset_password_url($token = '', $email = '', $username = '')
    {
        return url(config('app.web_url') . Consts::AUTH_ROUTE_RESET_PASSWORD . $token . '&email=' . urlencode($email) . '&username=' . urlencode($username));
    }
}

if (!function_exists('verify_change_email_url')) {
    function verify_change_email_url($code = '')
    {
        return url(config('app.web_url') . Consts::AUTH_ROUTE_VERIFY_CHANGE_EMAIL . $code);
    }
}

if (!function_exists('verify_change_username_url')) {
    function verify_change_username_url($code = '')
    {
        return url(config('app.web_url') . Consts::AUTH_ROUTE_VERIFY_CHANGE_USERNAME . $code);
    }
}

if (!function_exists('verify_change_phone_url')) {
    function verify_change_phone_url($code = '')
    {
        return url(config('app.web_url') . Consts::AUTH_ROUTE_VERIFY_CHANGE_PHONE . $code);
    }
}

if (!function_exists('confirm_email_url')) {
    function confirm_email_url($code = '') {
        return url(config('app.url').Consts::AUTH_ROUTE_CONFIRM_EMAIL.$code);
    }
}

if (!function_exists('verify_account_checking_url')) {
    function verify_account_checking_url($email='', $code = '', $vip = 0) {
        $path = sprintf(Consts::AUTH_ROUTE_VERIFY_ACCOUNT_CHECKING, urlencode($email), $code, $vip);
        return url(config('app.web_url') . $path);
    }
}

if (!function_exists('go_profile')) {
    function go_profile($username='') {
        return url(config('app.web_url') . '/@'.$username);
    }
}

if (!function_exists('go_game_profile')) {
    function go_game_profile($username, $game) {
        return url(config('app.web_url') . '/@'.$username.'/'.$game);
    }
}

if (!function_exists('bounty_detail_link')) {
    function bounty_detail_link($username = '', $slug = '') {
        return url(config('app.web_url') . '/@' . $username . '/bounties/' . $slug);
    }
}

if (!function_exists('chat_link')) {
    function chat_link($username = '') {
        return url(config('app.web_url') . '/m/' . $username);
    }
}

if (!function_exists('grant_device_url')) {
    function grant_device_url($code = '') {
        return url(config('app.url').Consts::AUTH_ROUTE_GRANT_DEVICE.$code);
    }
}

if (!function_exists('base64url_encode')) {
    function base64url_encode($data = '')
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('base64url_decode')) {
    function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}

if (!function_exists('array_get')) {
    function array_get($array, $key, $default = null) {
        return Arr::get($array, $key, $default);
    }
}

if (!function_exists('str_random')) {
    function str_random($length = 16) {
        return Str::random($length);
    }
}

if (! function_exists('ends_with')) {
    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function ends_with($haystack, $needles)
    {
        return Str::endsWith($haystack, $needles);
    }
}

if (!function_exists('gamelancer_hash')) {
    function gamelancer_hash($key = '')
    {
        $plain = $key . config('app.key', '');
        return sha1($plain);
    }
}

/*
    Example:
    url_query('products', ['manufacturer' => 'Samsung']);
    Returns 'http://localhost/products?manufacturer=Samsung'

    url_query('products', ['manufacturer' => 'Samsung'], [$product->id]);
    Returns 'http://localhost/products/1?manufacturer=Samsung'
*/
if (!function_exists('url_query')) {
    function url_query($to, array $params = [], array $additional = []) {
        return Str::finish(url($to, $additional), '?') . Arr::query($params);
    }
}

if (!function_exists('to_number')) {
    function to_number($number) {
        return BigNumber::new($number)->toString();
    }
}

if (!function_exists('domain_name')) {
    function domain_name($url) {
        $domain = str_ireplace('www.', '', parse_url($url, PHP_URL_HOST));
        $position = strpos($domain, '.');
        return substr($domain, 0, $position);
    }
}

if (!function_exists('array_diff_with_serialize')) {
    function array_diff_with_serialize($arr1, $arr2) {
        $arrayDiff = array_diff(array_map('serialize',$arr1), array_map('serialize',$arr2));
        return array_map('unserialize',$arrayDiff);
    }
}

if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
}

if (!function_exists('getSourceUrl')) {
    function getSourceUrl($url) {
        $url = parse_url($url, PHP_URL_HOST) ?? '';
        return $url;
    }
}

if (!function_exists('ksort_recursive')) {
    function ksort_recursive(array &$a) {
      ksort($a, SORT_NATURAL | SORT_FLAG_CASE);

      foreach ($a as $k => $v) {
        if (is_array($v)) {
          ksort_recursive($a[$k]);
        }
      }
    }
}

if (!function_exists('getOriginalClientIp')) {
    function getOriginalClientIp()
    {
        $request = request();

        $originalClientIp = $request->header('x-forwarded-for');

        if (empty($originalClientIp)) {
            $ip = $request->ip();
        } else {
            $ip = $originalClientIp;
        }

        $clientIPs = explode(Consts::CHAR_COMMA, $ip);
        return is_array($clientIPs) ? $clientIPs[0] : $clientIPs;
    }
}

if (!function_exists('str_contains')) {
    function str_contains($string, $text = '') {
        return Str::contains($string, $text);
    }
}

if (!function_exists('array_shuffle')) {
    function array_shuffle($data) {
        $data = collect($data)->values()->toArray();
        $currentIndex = count($data);

        while (0 < $currentIndex) {
            $currentIndex   = $currentIndex - 1;
            $randomIndex    = intval(floor(rand(0, $currentIndex)));

            $temporaryValue         = $data[$currentIndex];
            $data[$currentIndex]    = $data[$randomIndex];
            $data[$randomIndex]     = $temporaryValue;
        }

      return $data;
    }
}

if (!function_exists('cloneDeep')) {
    function cloneDeep($data) {
        $data = json_encode($data);
        return json_decode($data, true);
    }
}
