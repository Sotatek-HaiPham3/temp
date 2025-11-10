<?php

namespace App;

use Carbon\Carbon;
use App\Consts;
use Auth;
use DB;
use App;
use App\Utils\BigNumber;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class Utils
{

    public static function previous24hInMillis() {
        return Carbon::now()->subDay()->timestamp * 1000;
    }

    public static function previousDayInMillis($day) {
        return Carbon::now()->subDay($day)->timestamp * 1000;
    }

    public static function currentMilliseconds() {
        return round(microtime(true) * 1000);
    }

    public static function toMilliseconds($hours) {
        return floatval($hours) * 60 * 60 * 1000;
    }

    public static function millisecondsToCarbon($timestamp) {
        return Carbon::createFromTimestampUTC(floor($timestamp/1000));
    }

    public static function dateTimeToMilliseconds($stringDate) {
        $date = !empty($stringDate) ? Carbon::parse($stringDate) : Carbon::now();
        return $date->timestamp * 1000 + $date->micro;
    }

    public static function generateRandomString($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $pieces = [];
        $max = strlen($keyspace) - 1;
        for ($i = 0; $i < $length; ++$i) {
            $pieces []= $keyspace[random_int(0, $max)];
        }
        return implode('', $pieces);
    }

    public static function trimFloatNumber($val) {
        return strpos($val,'.')!==false ? rtrim(rtrim($val,'0'),'.') : $val;
    }

    public static function saveFileToStorage ($file, $pathFolder, $prefixName = null) {
        $filename = Utils::currentMilliseconds() . '.' . $file->extension();

        $fullFilename = $pathFolder . DIRECTORY_SEPARATOR .$filename;

        if (!empty($prefixName)) {
            $fullFilename = $pathFolder . DIRECTORY_SEPARATOR .$prefixName . '_' . $filename;
        }

        $fullFilename = str_replace(Consts::CHAR_SPACE, Consts::CHAR_HYPHEN, $fullFilename);

        Storage::disk(env('FILESYSTEM_DRIVER'))->put($fullFilename, file_get_contents($file));
        return Storage::disk(env('FILESYSTEM_DRIVER'))->url($fullFilename);
    }

    public static function saveFileFromDropBox($file, $pathFolder, $fileName, $fileExtension)
    {
        $fileFullname = $pathFolder . DIRECTORY_SEPARATOR . $fileName . '.' . $fileExtension;

        Storage::disk('public_images')->put($fileFullname, file_get_contents($file));
        return basename(public_path('images')) . DIRECTORY_SEPARATOR . $fileFullname;
    }

    public static function getSchemeAndHttpHost()
    {
        return env('APP_URL', 'http://localhost');
    }

    public static function getSchemeAndHttpHostForAssets()
    {
        $awsUrl = env('AWS_URL');
        return empty($awsUrl) ? env('APP_URL') : $awsUrl;
    }

    public static function getContentCsvFile($fileUpload, $header = [])
    {
        $content = [];

        $path = $fileUpload->getRealPath();
        $file = fopen($path, 'r');

        $columns = static::removeSpecialChars(fgetcsv($file));

        if (!empty($header) && join(Consts::CHAR_COMMA, $columns) !== join(Consts::CHAR_COMMA, $header)) {
            throw new \Exception('The header file upload is invalid.');
        }

        while (!feof($file)) {
            $value = fgetcsv($file);
            if (!empty($value) && $value !== array(null)) {
                $value = static::removeSpecialChars($value);
                $content[] = array_combine($columns, $value);
            }
        }
        fclose($file);

        return $content;
    }

    private static function removeSpecialChars(array $data)
    {
        return collect($data)->map(function ($item) {
            $item = preg_replace(Consts::REGEX_REMOVE_SPECIAL_CHAR, Consts::STRING_EMPTY, $item);
            return trim($item);
        })
        ->toArray();
    }

    public static function isProduction()
    {
        return app()->environment('production');
    }

    public static function isLocal()
    {
        return app()->environment('local');
    }

    public static function getRefererReturnUrlForDeposit($input)
    {
        $separator = strpos($input['referer_url'], '?') ? '&' : '?';
        if (array_key_exists('game_bounty_id', $input)) {
            $ortherQueryString = "&game_bounty_id={$input['game_bounty_id']}";
        } else {
            $ortherQueryString = "&transaction_id={$input['transaction_id']}";
        }
        $refererUrl = sprintf('%s%s%s%s%s', $input['referer_url'], $separator, 'action=return', $ortherQueryString, array_get($input, 'hash_url', ''));
        return $refererUrl;
    }

    public static function escapeLike(string $value, string $char = '\\'): string
    {
        return str_replace(
            [$char, '%', '_'],
            [$char.$char, $char.'%', $char.'_'],
            $value
        );
    }

    public static function standardNumber($values = [])
    {
        return collect($values)->map(function ($item) {
            return intval($item);
        })->toArray();
    }
  
    public static function concealEmail($value)
    {
        $splitEmail  = explode('@', $value);
        $saparateTail = explode('.', $splitEmail[1], 2);
        return substr($splitEmail[0], 0, 2).'***@***.'.$saparateTail[1];
    }

    public static function maskData($data, $ignoreAttributes)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = static::maskData($value, $ignoreAttributes);
                continue;
            }

            if (! in_array($key, $ignoreAttributes)) {
                continue;
            }

            $data[$key] = sprintf('%s**********', substr($value, 0, 2));
        }

        return $data;
    }

    public static function convertArrayToPagination($data, $limit)
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $itemCollection = collect($data);
        $currentPageItems = $itemCollection->slice(($currentPage * $limit) - $limit, $limit)->all();

        return new LengthAwarePaginator($currentPageItems, count($itemCollection), $limit);
    }

    public static function trimChar($string, $char = DIRECTORY_SEPARATOR)
    {
        $data = ltrim($string, $char);
        return rtrim($data, $char);
    }

    public static function formatPropsValue($data)
    {
        return Utils::trimFloatNumber(BigNumber::round($data, BigNumber::ROUND_MODE_FLOOR, 2));
    }

    public static function unsetFields ($data, ...$fields)
    {
        foreach($fields as $field) {
            unset($data->$field);
        }
        return $data;
    }
}
