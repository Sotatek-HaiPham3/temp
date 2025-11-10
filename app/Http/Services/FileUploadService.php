<?php

namespace App\Http\Services;

use App\Models\FileUpload;
use App\Consts;
use App\Utils;
use Exception;
use App\Exceptions\Reports\FileUploadException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Utils\VideoProcessor;
use Illuminate\Support\Str;
use FileProcessor;
use ContentApp;
use App\Jobs\VideoUpdatingJob;
use App\Jobs\CollectTaskingJob;
use App\Http\Services\MasterdataService;
use App\Models\Tasking;

class FileUploadService {

    public function generatePreSignedS3Form($input = [])
    {
        $userId = Auth::id() ?: 0;
        $now = time();
        $destinationFolder = "uploads/{$userId}/{$now}/";

        $diskContent = 's3-content';
        $adapter = Storage::disk($diskContent)->getDriver()->getAdapter();

        $client = $adapter->getClient();
        $bucket = $adapter->getBucket();

        $expiry = '+30 minutes';

        $formInputs = ['acl' => 'private'];
        $options = [
            ['acl' => 'private'],
            ['bucket' => $bucket],
            ['starts-with', '$key', $destinationFolder],
        ];

        $postObject = new \Aws\S3\PostObjectV4(
            $client,
            $bucket,
            $formInputs,
            $options,
            $expiry
        );

        $attributes = $postObject->getFormAttributes();
        $inputs = $postObject->getFormInputs();

        $inputs['key'] = sprintf('%s/%s', Utils::trimChar($destinationFolder), $inputs['key']);

        $fileInfo = [
            'prefix' => $destinationFolder,
            'link' => null,
            'filename' => $input['filename'],
            'mimetype' => $input['mime_type'],
        ];

        return [
            'attributes' => $attributes,
            'inputs' => $inputs,
            'info' => $fileInfo
        ];
    }

    public function verifyUploadS3($params)
    {
        $data = [
            'title' => array_get($params, 'title'),
            'description' => array_get($params, 'description'),
            'games' => [array_get($params, 'game_id')],
            'tags' => array_get($params, 'tags'),
            'mimetype' => array_get($params, 'mimetype'),
            'prefix' => array_get($params, 'prefix'),
            'filename' => array_get($params, 'filename'),
        ];

        $this->saveVideoTags(array_get($params, 'tags', []));

        $info = ContentApp::triggerTranscodingVideo($data);

        $videoId = $info['data']['content_id'];

        if (!Utils::isProduction()) {
            VideoUpdatingJob::addVideoTracking($videoId);
        }

        $info['data']['video_id'] = $videoId;
        unset($info['data']['content_id']);

        CollectTaskingJob::dispatch(Auth::id(), Tasking::UPLOAD_VIDEO_INTRO);

        return $info;
    }

    private function saveVideoTags($tags = [])
    {
        if (empty($tags)) {
            return;
        }

        $shouldClearCache = false;
        foreach ($tags as $value) {
            $value = Utils::escapeLike($value);
            $tag = DB::table('video_tags')->where(function ($query) use ($value) {
                    $key = Str::slug($value, '_');
                    return $query->where('key', $key)
                        ->orWhere('content', $value);
                })
                ->first();

            if ($tag) {
                continue;
            }

            DB::table('video_tags')->insert([
                'key'           => Str::slug($value, '_'),
                'content'       => $value,
                'created_at'    => now(),
                'updated_at'    => now()
            ]);

            $shouldClearCache = true;
        }

        if ($shouldClearCache) {
            MasterdataService::clearCacheOneTable('video_tags');
        }
    }

    public function uploadFile($fileUpload, $input, $destinationFolder)
    {
        if (self::isImageFile($fileUpload)) {
            return self::storeImageFile($fileUpload, $destinationFolder);
        }

        return $this->storeFile($fileUpload, $input, $destinationFolder);
    }

    private function storeImageFile($fileUpload, $destinationFolder)
    {
        $filePath = null;

        $destinationFolder = "{$destinationFolder}/images";

        if (Utils::isLocal()) {
            $filePath = Utils::saveFileToStorage($fileUpload, $destinationFolder);
        } else {
            $filePath = FileProcessor::upload_image($fileUpload, $destinationFolder);
        }

        if (!$filePath) {
            throw new FileUploadException();
        }

        $fileInfo = FileUpload::firstOrCreate([
            'file_path' => $filePath,
            'user_id' => Auth::id(),
            'is_used' => Consts::TRUE
        ]);

        return $fileInfo;
    }

    private function storeFile($fileUpload, $input, $destinationFolder)
    {
        $newPathFile = null;

        $destinationFolder = "{$destinationFolder}/files";

        if (Utils::isLocal()) {
            $filePath = Utils::saveFileToStorage($fileUpload, $destinationFolder);

            // $filePath = self::standardFilePath($filePath);

            logger()->info('===Standard File Path: ', [$filePath]);

            $newPathFile = $filePath;//VideoProcessor::clipVideo($filePath, $destinationFolder, $input);
        } else {
            $newPathFile = FileProcessor::upload_file($fileUpload, $destinationFolder);
        }

        if (!$newPathFile) {
            throw new FileUploadException();
        }

        $fileInfo = FileUpload::create([
            'file_path' => $newPathFile,
            'user_id' => Auth::id(),
            'is_used' => Consts::TRUE
        ]);

        $fileInfo->is_video = Consts::TRUE;

        return $fileInfo;
    }

    private function isImageFile($fileUpload)
    {
        $validator = Validator::make(['file' => $fileUpload], ['file' => 'mimes:jpg,jpeg,png,gif']);

        return ! $validator->fails();
    }

    private function standardFilePath($filePath)
    {
        if (self::isActiveUrl($filePath)) {
            return $filePath;
        }

        // filePath maybe localhost or internal IP: 192.168.x.x
        $keyword = 'storage';
        $info = parse_url($filePath);
        if (strpos($info['path'], $keyword) !== false) {
            $rootPath = rtrim(Storage::disk(env('FILESYSTEM_DRIVER'))->getAdapter()->getPathPrefix(), DIRECTORY_SEPARATOR);
            $newPath = trim(str_replace($keyword, '', $info['path']), DIRECTORY_SEPARATOR);

            return "$rootPath/$newPath";
        }

        return $filePath;
    }

    private function isActiveUrl($url)
    {
        $validator = Validator::make(['url' => $url], ['file' => 'active_url']);

        $isUrlLocalhost = Str::contains($url, 'localhost');

        return ! $validator->fails() && !$isUrlLocalhost;
    }


    public function removeFileUpload($fileId)
    {
        $fileUpload = FileUpload::find($fileId);

        $fileUpload->is_used = Consts::FALSE;
        $fileUpload->save();

        return $fileUpload;
    }
}
