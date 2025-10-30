<?php

namespace App\Utils;

use App\Utils;
use Exception;

/**
 * class FileProcessor
 * Requires AWS SDK API installed via composer
 */
class FileProcessor {

    public $aws_access_key;

    public $aws_secret_access_key;

    public $aws_bucket;

    /**
     * width
     *
     * $w int
     */
    public $w;

    /**
     * height
     *
     * $h int
     */
    public $h;

    /**
     * Allow animated images
     * (False will only process first frame of animated images)
     *
     * @var string
     */
    public $animated = false;

    /**
     * image src
     *
     * $src string
     */
    public $src;

    /**
     * bool for is_file or not
     *
     * @var type
     */
    public $is_file;

    /**
     *
     * @var string
     */
    public $hash;

    /**
     *
     * @var bool
     */
    public $return_fullpath;

    /**
     *
     * @var string
     */
    public $mime_type;

    /**
     * The function path on AWS for the Lambda image processor.
     * @var string
     */
    public $lambda_function_path;

    /**
     * The AWS region where our Lambda processor lives.
     * @var string
     */
    public $aws_region;

    /**
     * Create an object to process images with Lambda
     * @requires AWS API SDK i.e.... "new Aws\Lambda\LambdaClient"..
     *
     * @param string $aws_access_key
     * AWS access key
     *
     * @param string $aws_secret_access_key
     * AWS secret key
     *
     * @param string $aws_bucket
     * The bucket to use for our processor
     *
     * @param bool $return_fullpath
     * if true, returns full path with http, cdn/bucket, path
     * if false, returns only path without domain
     *
     * @param string $cdn_domain
     * Path to final CDN for delivery if exists.  Default: false
     * Example: content-assets.gamelancer.com
     *
     */
    function __construct($aws_access_key, $aws_secret_access_key, $aws_bucket, $aws_region, $return_fullpath=false, $cdn_domain=false) {

        if(!$aws_access_key || !$aws_secret_access_key) {
            die('You must pass in your AWS secret and access keys.');
        }

        $this->aws_access_key   = $aws_access_key;

        $this->aws_secret_access_key = $aws_secret_access_key;
        $this->lambda_function_path = env('AWS_LAMBDA_FUNCTION_PATH');

        $this->setBucket($aws_bucket);
        $this->setRegion($aws_region);
        $this->setReturnFullpath($return_fullpath);
        $this->setDeliveryPath($cdn_domain);

    }

    function setDeliveryPath($cdn=false) {
        if(!$cdn) {
            $this->delivery_path = 'https://' . $this->aws_bucket . ".s3-{$this->aws_region}.amazonaws.com/";
        } else {
            $this->delivery_path = 'https://' . $cdn . '/';
        }
    }

    function setReturnFullpath($return_fullpath) {
        if($return_fullpath) {
            $this->return_fullpath = true;
        } else {
            $this->return_fullpath = false;
        }
    }

    /**
     * Change the destination bucket
     *
     * @param string $new_bucket
     */
    public function setBucket($new_bucket) {
        $this->aws_bucket = $new_bucket;
    }

    /**
     * Get the currently set destination bucket
     *
     * @param string $new_bucket
     */
    public function getBucket() {
        if(!$this->aws_bucket) {
            return false;
        } else {
            return $this->aws_bucket;
        }
    }

    /**
     * Change the destination region
     *
     * @param string $region
     */
    public function setRegion($region) {
        $this->aws_region = $region;
    }

    /**
     * Get the currently set destination region
     *
     * @param string $new_bucket
     */
    public function getRegion() {
        if(!$this->aws_region) {
            return false;
        } else {
            return $this->aws_region;
        }
    }

    /**
     * Execute the lambda function to process and reduce images from an existing
     * source path on S3 ($s3path) to a destination path on S3 in the
     * same bucket ($s3dest).
     *
     * @param string $s3path
     * the path ('/original/foo/bar.jpg') of an existing source image.
     *
     * @param string $s3dest
     * the desired path ('/processed/foo/bar.jpg') of processed output.
     *
     * @param int $width
     * Value in pixels of desired output file's maximum width. If set to FALSE,
     * $height will be used to calculate scaled width.
     *
     * @param int $height
     * Value in pixels of desired output file's maximum height. If set to FALSE,
     * $width will be used to calculate a scaled height.
     *
     * @param boolean $thumb
     * When TRUE, $width and $height will be exact values for output,
     * and image is scaled + cropped to fit. Both $width and $height must
     * contain values.
     * When FALSE: Image will be scaled to fit within boundaries of maximum
     * dimensions given in $width and/or $height.
     *
     * @param string $acl
     * Access control list of processed output. Canned ACLs are accepted.
     * Most common: 'private', 'public-read'
     *
     * Examples:
     * If input image is 25x50, $width is 100, $height is 100, and $thumb is
     * set to TRUE, output image will be 100x100 (image will be centered
     * and cropped on the top and bottom).
     * If input image is 25x50, $width is 100, $height is 100, and $thumb is
     * set to FALSE, output image will be 50x100.
     * If input image is 25x50, $width is 100, $height is FALSE, and $thumb is
     * set to FALSE, output image will be 100x200.
     * If input image is 25x50, $width is FALSE, $height is FALSE, and $thumb is
     * set to FALSE, output image will be 25x50.
     *
     * @return boolean
     * @throws Exception
     */
    public function create($s3path, $s3dest, $width = false, $height = false, $thumb = false, $acl = 'public-read') {
        if(!$this->getBucket()) {
            throw new Exception('There is no destination bucket set!');
        }
        if(!strlen($this->lambda_function_path)) {
            throw new Exception('There is no lambda function path set!');
        }
        if($thumb && (!$width || !$height)) {
            throw new Exception('You must provide both width and height parameters when using the $thumb option.');
        }

        $lambda = new \Aws\Lambda\LambdaClient([
            'region' => $this->aws_region,
            'version' => 'latest',
            'credentials' => [
                'key' => $this->aws_access_key,
                'secret' => $this->aws_secret_access_key
            ]
        ]);

        $req_object = (object) [
            'bucket' => $this->aws_bucket,
            'access' => $this->aws_access_key,
            'secret' => $this->aws_secret_access_key,
            'region' => $this->aws_region,
            'src_key' => urldecode($s3path),
            'dest_key' => urldecode($s3dest),
            'animated' => $this->animated,
            'acl' => $acl,
        ];

        if ($height) {
            $req_object->height = $height;
            $req_object->resize = true;
        }
        if ($width) {
            $req_object->width = $width;
            $req_object->resize = true;
        }
        if ($thumb) {
            $req_object->thumb = true;
        }

        $response = $lambda->invoke([
            'FunctionName' => $this->lambda_function_path,
            'Payload' => json_encode($req_object)
        ]);

        if (trim($response['Payload']->getContents(), '"') === "Success" || $response['StatusCode'] == 200) {
            $result = true;
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Return the mime type of an uploaded file
     *
     * @return string mime type
     */
    public function realmime($filepath) {
        // New finfo object
        $file_info = new \finfo(FILEINFO_MIME);

        // grab the mimetype
        $mime_type = strtolower($file_info->buffer(file_get_contents($filepath)));

        // If we have encoding info, ditch it
        $real_mime_type = strpos($mime_type, ';') ? substr($mime_type, 0, strpos($mime_type, ';')) : $mime_type;

        return $real_mime_type;
    }

    /**
     * Upload an image to an S3 bucket
     *
     * @param string $upload_path
     * Desired destination path of new upload (i.e. '/foo/bar.jpg')
     *
     * @param string $upload
     * Existing object to upload (i.e. $_FILES['foo']['tmp_name'])
     *
     * @param string $mime
     * Mimetype for header accuracy
     *
     * @param string $uploadtype
     * 'SourceFile' when passing a File Path
     * 'Body' when passing entire image data loaded into a variable
     *
     * @param string $acl
     * Access control list of processed output. Canned ACLs are accepted.
     * Most common: 'private', 'public-read'
     *
     * @return boolean
     */
    function upload($upload_path, $upload, $mime = 'image/jpeg', $uploadtype = 'SourceFile', $acl = 'public-read') {
        if ((!empty($upload)) && ($upload != ('' || false))) {

            $s3 = new \Aws\S3\S3Client([
                'region' => $this->aws_region,
                'version' => 'latest',
                'credentials' => [
                    'key' => $this->aws_access_key,
                    'secret' => $this->aws_secret_access_key
                ]
            ]);

            $object = [
                'Bucket' => $this->aws_bucket,
                'Key' => $upload_path,
                'ContentType' => $mime,
                $uploadtype => $upload,
                'ACL' => $acl,
                'Expires' => gmdate("D, d M Y H:i:s T", strtotime("+1 year")),
                'Content-Language' => 'en-US',
                'Cache-Control' => 'public, max-age=31536000',
            ];
            if ($acl === 'private') {
                // Encrypt all private objects.
                $object['ServerSideEncryption'] = 'AES256';
            }

            $s3->putObject($object);
            return $upload_path;

        }
        return false;
    }

    /**
     * upload_image - move an image to S3 if it doesn't
     * already exist on S3, return the path. Retain original dimensions
     * by default.
     *
     * @param type $src
     * @param type $path
     * @param boolean $animated
     * @param integer $setWidth
     * @param integer $setHeight
     *  additional path before hash
     *
     * @return mixed: $string url if success, false if failure.
     */
    public function upload_image($src, $path=false, $animated = false, $setWidth = 500, $setHeight = 500) {
        $this->animated = $animated;

        $this->src = str_replace('`', '', $src);

        if($this->validate_url($this->src)){
            $this->is_file = false;
        } else {
            $this->is_file = true;
        }

        $ext = $this->getExtensionFile($this->src);

        $this->timestamp        =  time();
        $this->hash             = '';
        $filename         = 'o-' . $this->timestamp;
        $reduce_filename  = 'i-' . $this->timestamp;

        if($path){
            $this->hash .= $path;
        } else {
            if($this->is_file) {
                $this->hash     = md5($this->src . $this->timestamp);
            } else {
                $this->hash     = md5($this->src);
            }
        }

        $save_path    =  $this->hash."/$filename.$ext";
        $reduce_path  =  $this->hash."/$reduce_filename.$ext";

        $reduced_url      = $this->delivery_path . $reduce_path;

        list($width, $height, $type) = @getimagesize($this->src);

        // Get the 'realmime' type from our file and do some
        // basic security /checks for image data
        if(!$width || !$height || !$type) {
            throw new Exception("Unsupported file type");
        }

        $this->mime_type = $this->realmime($this->src);

        /**
         * Uploading the original. No reduction happens here.
         */
        if(!$this->is_file) {
            $upload = $this->upload($save_path, @file_get_contents($this->src), $this->mime_type, 'Body', 'public-read');
        } else {
            $upload = $this->upload($save_path, $this->src, $this->mime_type, 'SourceFile', 'public-read');
        }

        /**
         * Reducing file size, optimizing
         */
        $reduced = $this->create($save_path, $reduce_path, $setWidth, $setHeight, false, 'public-read');

        if($reduced) {
            if($this->return_fullpath) {
                return $reduced_url . '.webp';
            } else {
                return $reduce_path. '.webp';
            }
        }

        if(!$reduced) {
            return false;
        }

        if(!$this->return_fullpath) {
            return $reduce_path;
        }

        $awsUrl = env('AWS_URL');
        if (!empty($awsUrl)) {
            return rtrim($awsUrl, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $reduce_path;
        }

        return $reduced_url;
    }

    /**
     * upload_image - move an image to S3 if it doesn't
     * already exist on S3, return the path. Retain original dimensions
     * by default.
     *
     * @param type $src
     *  the source file being uploaded
     *
     * @param type $path
     *  (optional) - s3 folder path to upload image, i.e.
     * /users/1/profile (filename is autogenerated)
     *
     * @return mixed: $string url if success, false if failure.
     */
    public function upload_file($src, $path=false) {
        $this->src = str_replace('`', '', $src);

        if($this->validate_url($this->src)){
            $this->is_file = false;
        } else {
            $this->is_file = true;
        }

        $this->ext          = pathinfo($this->src, PATHINFO_EXTENSION);
        $this->mime_type    = $this->realmime($this->src);

        if(!$this->ext) {
            $this->ext    = $this->mime2ext($this->mime_type);
        }

        $this->timestamp        =  time();
        $this->hash             = '';
        $this->filename         = 'o-' . $this->timestamp;

        if($path){
            $this->hash .= $path;
        } else {
            if($this->is_file) {
                $this->hash     = md5($this->src . $this->timestamp);
            } else {
                $this->hash     = md5($this->src);
            }
        }

        $this->save_path    =  $this->hash."/$this->filename.$this->ext";
        $this->save_url        = $this->delivery_path . $this->save_path;

        /**
         * Uploading the original. No reduction happens here.
         */
        if(!$this->is_file) {
            $upload = $this->upload($this->save_path, @file_get_contents($this->src), $this->mime_type, 'Body', 'public-read');
        } else {
            $upload = $this->upload($this->save_path, $this->src, $this->mime_type, 'SourceFile', 'public-read');
        }

        if($upload) {
            if($this->return_fullpath) {
                return $this->save_url;
            } else {
                return $this->save_path;
            }
        }
        return false;
    }

    /**
     * extra_thumb - Create thumbnail of a recently stored image
     * (from the same session / instance)
     *
     * @param type $w
     * @param type $h
     * @param string $append_filename
     *
     * @return mixed: $string url if success, false if failure.
     */
    public function extra_thumb($imageUrl, $w=false, $h=false, $append_filename=false) {
        if (! $this->validate_url($imageUrl)) {
            throw new Exception('Image url is invalid.');
        }

        $this->w = (int)$w ?? false;
        $this->h = (int)$h ?? false;

        if(!$this->w && !$this->h) {
            throw new Exception("No dimensions passed for thumbnails");
        }

        $min = 10;
        $max = 1500;

        if($this->w && $this->w < $max) {
            $this->w = max([$min, $this->w]);
        } elseif($this->w) {
            $this->w = min([$max, $this->w]);
        }

        if($this->h && $this->h < $max) {
            $this->h = max([$min, $this->h]);
        } elseif($this->h) {
            $this->h = min([$max, $this->h]);
        }

        $ext = $this->getExtensionFile($this->src);

        $reduce_path = ltrim(parse_url($imageUrl, PHP_URL_PATH), DIRECTORY_SEPARATOR);
        $thumb_path = str_replace(".{$ext}", '', $reduce_path);

        // Build file name
        if(strlen($append_filename)) {
            $thumb_path .= $append_filename;
        } else {
            $thumb_path .= "-{$this->w}x{$this->h}";
        }
        // Append extension
        $thumb_path .= ".{$ext}";

        $thumb_url = $this->delivery_path . $thumb_path;

        /**
         * Reducing file size, optimizing
         */
        $thumbnailed = $this->create($reduce_path, $thumb_path, $this->w, $this->h, true, 'public-read');
        // $thumbnailed = $this->create('test/i-1593502826.png', 'test/i-1593502826-250x250_xxxxx.png', $this->w, $this->h, true, 'public-read');

        if($thumbnailed) {
            if($reduce_path) {
                return $thumb_url . '.webp';
            } else {
                return $thumb_path . '.webp';
            }
        }

        if(!$thumbnailed) {
            return false;
        }

        if(!$this->return_fullpath) {
            return $thumb_path;
        }

        $awsUrl = env('AWS_URL');
        if (!empty($awsUrl)) {
            return rtrim($awsUrl, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $thumb_path;
        }

        return $thumb_url;
    }

    function getExtensionFile($url) {
        $ext = pathinfo($url, PATHINFO_EXTENSION);

        if(!$ext) {
            $ext = 'jpg';
        }
        return $ext;
    }

    /**
     * Validate if a URL is valid
     *
     * @param type $url
     * @return type
     */
    function validate_url($url) {
        $path = parse_url($url, PHP_URL_PATH);
        $encoded_path = array_map('urlencode', explode('/', $path));
        $url = str_replace($path, implode('/', $encoded_path), $url);

        return filter_var($url, FILTER_VALIDATE_URL) ? true : false;
    }

    function mime2ext($mime) {
        $mime_map = [
            'video/3gpp2'                                                               => '3g2',
            'video/3gp'                                                                 => '3gp',
            'video/3gpp'                                                                => '3gp',
            'application/x-compressed'                                                  => '7zip',
            'audio/x-acc'                                                               => 'aac',
            'audio/ac3'                                                                 => 'ac3',
            'application/postscript'                                                    => 'ai',
            'audio/x-aiff'                                                              => 'aif',
            'audio/aiff'                                                                => 'aif',
            'audio/x-au'                                                                => 'au',
            'video/x-msvideo'                                                           => 'avi',
            'video/msvideo'                                                             => 'avi',
            'video/avi'                                                                 => 'avi',
            'application/x-troff-msvideo'                                               => 'avi',
            'application/macbinary'                                                     => 'bin',
            'application/mac-binary'                                                    => 'bin',
            'application/x-binary'                                                      => 'bin',
            'application/x-macbinary'                                                   => 'bin',
            'image/bmp'                                                                 => 'bmp',
            'image/x-bmp'                                                               => 'bmp',
            'image/x-bitmap'                                                            => 'bmp',
            'image/x-xbitmap'                                                           => 'bmp',
            'image/x-win-bitmap'                                                        => 'bmp',
            'image/x-windows-bmp'                                                       => 'bmp',
            'image/ms-bmp'                                                              => 'bmp',
            'image/x-ms-bmp'                                                            => 'bmp',
            'application/bmp'                                                           => 'bmp',
            'application/x-bmp'                                                         => 'bmp',
            'application/x-win-bitmap'                                                  => 'bmp',
            'application/cdr'                                                           => 'cdr',
            'application/coreldraw'                                                     => 'cdr',
            'application/x-cdr'                                                         => 'cdr',
            'application/x-coreldraw'                                                   => 'cdr',
            'image/cdr'                                                                 => 'cdr',
            'image/x-cdr'                                                               => 'cdr',
            'zz-application/zz-winassoc-cdr'                                            => 'cdr',
            'application/mac-compactpro'                                                => 'cpt',
            'application/pkix-crl'                                                      => 'crl',
            'application/pkcs-crl'                                                      => 'crl',
            'application/x-x509-ca-cert'                                                => 'crt',
            'application/pkix-cert'                                                     => 'crt',
            'text/css'                                                                  => 'css',
            'text/x-comma-separated-values'                                             => 'csv',
            'text/comma-separated-values'                                               => 'csv',
            'application/vnd.msexcel'                                                   => 'csv',
            'application/x-director'                                                    => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/x-dvi'                                                         => 'dvi',
            'message/rfc822'                                                            => 'eml',
            'application/x-msdownload'                                                  => 'exe',
            'video/x-f4v'                                                               => 'f4v',
            'audio/x-flac'                                                              => 'flac',
            'video/x-flv'                                                               => 'flv',
            'image/gif'                                                                 => 'gif',
            'application/gpg-keys'                                                      => 'gpg',
            'application/x-gtar'                                                        => 'gtar',
            'application/x-gzip'                                                        => 'gzip',
            'application/mac-binhex40'                                                  => 'hqx',
            'application/mac-binhex'                                                    => 'hqx',
            'application/x-binhex40'                                                    => 'hqx',
            'application/x-mac-binhex40'                                                => 'hqx',
            'text/html'                                                                 => 'html',
            'image/x-icon'                                                              => 'ico',
            'image/x-ico'                                                               => 'ico',
            'image/vnd.microsoft.icon'                                                  => 'ico',
            'text/calendar'                                                             => 'ics',
            'application/java-archive'                                                  => 'jar',
            'application/x-java-application'                                            => 'jar',
            'application/x-jar'                                                         => 'jar',
            'image/jp2'                                                                 => 'jp2',
            'video/mj2'                                                                 => 'jp2',
            'image/jpx'                                                                 => 'jp2',
            'image/jpm'                                                                 => 'jp2',
            'image/jpeg'                                                                => 'jpeg',
            'image/pjpeg'                                                               => 'jpeg',
            'application/x-javascript'                                                  => 'js',
            'application/json'                                                          => 'json',
            'text/json'                                                                 => 'json',
            'application/vnd.google-earth.kml+xml'                                      => 'kml',
            'application/vnd.google-earth.kmz'                                          => 'kmz',
            'text/x-log'                                                                => 'log',
            'audio/x-m4a'                                                               => 'm4a',
            'application/vnd.mpegurl'                                                   => 'm4u',
            'audio/midi'                                                                => 'mid',
            'application/vnd.mif'                                                       => 'mif',
            'video/quicktime'                                                           => 'mov',
            'video/x-sgi-movie'                                                         => 'movie',
            'audio/mpeg'                                                                => 'mp3',
            'audio/mpg'                                                                 => 'mp3',
            'audio/mpeg3'                                                               => 'mp3',
            'audio/mp3'                                                                 => 'mp3',
            'video/mp4'                                                                 => 'mp4',
            'video/mpeg'                                                                => 'mpeg',
            'application/oda'                                                           => 'oda',
            'audio/ogg'                                                                 => 'ogg',
            'video/ogg'                                                                 => 'ogg',
            'application/ogg'                                                           => 'ogg',
            'application/x-pkcs10'                                                      => 'p10',
            'application/pkcs10'                                                        => 'p10',
            'application/x-pkcs12'                                                      => 'p12',
            'application/x-pkcs7-signature'                                             => 'p7a',
            'application/pkcs7-mime'                                                    => 'p7c',
            'application/x-pkcs7-mime'                                                  => 'p7c',
            'application/x-pkcs7-certreqresp'                                           => 'p7r',
            'application/pkcs7-signature'                                               => 'p7s',
            'application/pdf'                                                           => 'pdf',
            'application/octet-stream'                                                  => 'pdf',
            'application/x-x509-user-cert'                                              => 'pem',
            'application/x-pem-file'                                                    => 'pem',
            'application/pgp'                                                           => 'pgp',
            'application/x-httpd-php'                                                   => 'php',
            'application/php'                                                           => 'php',
            'application/x-php'                                                         => 'php',
            'text/php'                                                                  => 'php',
            'text/x-php'                                                                => 'php',
            'application/x-httpd-php-source'                                            => 'php',
            'image/png'                                                                 => 'png',
            'image/x-png'                                                               => 'png',
            'application/powerpoint'                                                    => 'ppt',
            'application/vnd.ms-powerpoint'                                             => 'ppt',
            'application/vnd.ms-office'                                                 => 'ppt',
            'application/msword'                                                        => 'doc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop'                                                   => 'psd',
            'image/vnd.adobe.photoshop'                                                 => 'psd',
            'audio/x-realaudio'                                                         => 'ra',
            'audio/x-pn-realaudio'                                                      => 'ram',
            'application/x-rar'                                                         => 'rar',
            'application/rar'                                                           => 'rar',
            'application/x-rar-compressed'                                              => 'rar',
            'audio/x-pn-realaudio-plugin'                                               => 'rpm',
            'application/x-pkcs7'                                                       => 'rsa',
            'text/rtf'                                                                  => 'rtf',
            'text/richtext'                                                             => 'rtx',
            'video/vnd.rn-realvideo'                                                    => 'rv',
            'application/x-stuffit'                                                     => 'sit',
            'application/smil'                                                          => 'smil',
            'text/srt'                                                                  => 'srt',
            'image/svg+xml'                                                             => 'svg',
            'application/x-shockwave-flash'                                             => 'swf',
            'application/x-tar'                                                         => 'tar',
            'application/x-gzip-compressed'                                             => 'tgz',
            'image/tiff'                                                                => 'tiff',
            'text/plain'                                                                => 'txt',
            'text/x-vcard'                                                              => 'vcf',
            'application/videolan'                                                      => 'vlc',
            'text/vtt'                                                                  => 'vtt',
            'audio/x-wav'                                                               => 'wav',
            'audio/wave'                                                                => 'wav',
            'audio/wav'                                                                 => 'wav',
            'application/wbxml'                                                         => 'wbxml',
            'video/webm'                                                                => 'webm',
            'audio/x-ms-wma'                                                            => 'wma',
            'application/wmlc'                                                          => 'wmlc',
            'video/x-ms-wmv'                                                            => 'wmv',
            'video/x-ms-asf'                                                            => 'wmv',
            'application/xhtml+xml'                                                     => 'xhtml',
            'application/excel'                                                         => 'xl',
            'application/msexcel'                                                       => 'xls',
            'application/x-msexcel'                                                     => 'xls',
            'application/x-ms-excel'                                                    => 'xls',
            'application/x-excel'                                                       => 'xls',
            'application/x-dos_ms_excel'                                                => 'xls',
            'application/xls'                                                           => 'xls',
            'application/x-xls'                                                         => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.ms-excel'                                                  => 'xlsx',
            'application/xml'                                                           => 'xml',
            'text/xml'                                                                  => 'xml',
            'text/xsl'                                                                  => 'xsl',
            'application/xspf+xml'                                                      => 'xspf',
            'application/x-compress'                                                    => 'z',
            'application/x-zip'                                                         => 'zip',
            'application/zip'                                                           => 'zip',
            'application/x-zip-compressed'                                              => 'zip',
            'application/s-compressed'                                                  => 'zip',
            'multipart/x-zip'                                                           => 'zip',
            'text/x-scriptzsh'                                                          => 'zsh',
        ];

        return isset($mime_map[$mime]) === true ? $mime_map[$mime] : false;
    }
}
