<?php

namespace App\Utils;

use App\Consts;
use App\Utils;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Format\Video\X264;
use FFMpeg\Format\Video\WebM;
use Storage;

class VideoProcessor
{

    private static $temporaryFiles = [];

    public static function clipVideo($filePath, $destinationFolder, $options = [])
    {
        $ffmpeg = FFMpeg::create();

        $video = $ffmpeg->open($filePath);

        $start = empty($options['start']) ? 0 : $options['start'];
        $duration = empty($options['duration']) ? static::getDuration($video, $filePath) : $options['duration'];

        $video->filters()->clip(TimeCode::fromSeconds($start), TimeCode::fromSeconds($duration));

        $temporaryFile = static::newTemporaryFile() . '.webm';
        $video->save(new WebM(), $temporaryFile);

        $filename = $destinationFolder . DIRECTORY_SEPARATOR . Utils::currentMilliseconds() . '.webm';

        Storage::disk(env('FILESYSTEM_DRIVER'))->put($filename, file_get_contents($temporaryFile));

        static::cleanupTemporaryFiles();

        return Storage::disk(env('FILESYSTEM_DRIVER'))->url($filename);
    }

    private static function getDuration($video, $filePath)
    {
        // FFMpeg\FFProbe\DataMapping/StreamCollection
        $infos = $video->getFFProbe()->streams($filePath);

        // empty
        if (! $infos->count()) {
            return null;
        }

        // FFMpeg\FFProbe\DataMapping/Stream
        $firstStream = $infos->first();

        return $firstStream->get('duration');
    }

    private static function getRootPath()
    {
        return Storage::disk(env('FILESYSTEM_DRIVER'))->url($filename);
    }

    public static function newTemporaryFile()
    {
        return self::$temporaryFiles[] = tempnam(sys_get_temp_dir(), 'gamelancer-ffmpeg');
    }

    public static function cleanupTemporaryFiles()
    {
        foreach (self::$temporaryFiles as $path) {
            @unlink($path);
        }
    }
}
