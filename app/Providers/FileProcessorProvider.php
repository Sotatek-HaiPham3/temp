<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Utils\FileProcessor;

class FileProcessorProvider extends ServiceProvider {

    public function boot()
    {
      //
    }

    public function register()
    {
     $this->app->bind('fileprocessor',function() {

        $awsKey         = env('AWS_KEY_IMG');
        $awsSecretKey   = env('AWS_SECRET_KEY_IMG');
        $awsBucket      = env('AWS_BUCKET_IMG');
        $awsRegion      = env('AWS_REGION_IMG');
        $fullPath       = true;
        $cdnDomain      = env('AWS_CDN_DOMAIN_IMG');

        return new FileProcessor($awsKey, $awsSecretKey, $awsBucket, $awsRegion, $fullPath, $cdnDomain);
      });
   }
}
