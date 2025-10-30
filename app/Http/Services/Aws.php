<?php

namespace App\Http\Services;

use Aws\Firehose\FirehoseClient;
use App\Jobs\PutFirehoseJob;
use App\Consts;
use App\Utils;
use Auth;

class Aws
{

    public function __construct()
    {
        //
    }

    private function addDataDefault($data, $user = null)
    {
        $server = request()->server;
        $trace = debug_backtrace()[2];
        $trace['args'] = json_encode($trace['args']);

        $httpXForwardedFor = $server->get('HTTP_X_FORWARDED_FOR') ?? '';
        $clientIPs = explode(Consts::CHAR_COMMA, $httpXForwardedFor);
        $clientAddress = is_array($clientIPs) ? $clientIPs[0] : $clientIPs;

        $default_data = [
            'timestamp' => date(\DateTime::ISO8601),
            'content_type' => $server->get('CONTENT_TYPE') ?? '',
            'scheme' => $server->get('REQUEST_SCHEME') ?? '',
            'query_string' => $server->get('QUERY_STRING') ?? '',
            'request_time' => $server->get('REQUEST_TIME') ?? '',
            'request_method' => $server->get('REQUEST_METHOD') ?? '',
            'request_uri' => $server->get('REQUEST_URI') ?? '',
            'client_port' => $server->get('REMOTE_PORT') ?? '',
            'remote_addr' => $server->get('REMOTE_ADDR') ?? '',
            'server_name' => $server->get('SERVER_NAME') ?? '',
            'server_addr' => $server->get('SERVER_ADDR') ?? '',
            'server_port' => $server->get('SERVER_PORT') ?? '',
            'client_address' => $clientAddress ?? '',
            'http_user_agent' => $server->get('HTTP_USER_AGENT') ?? '',
            'http_origin' => $server->get('HTTP_ORIGIN') ?? '',
            'http_referer' => $server->get('HTTP_REFERER') ?? '',
            'http_sec_fetch_dest' => $server->get('HTTP_SEC_FETCH_DEST') ?? '',
            'http_sec_fetch_mode' => $server->get('HTTP_SEC_FETCH_MODE') ?? '',
            'http_sec_fetch_site' => $server->get('HTTP_SEC_FETCH_SITE') ?? '',
            'http_host' => $server->get('HTTP_HOST') ?? '',
            'http_x_forwarded_port' => $server->get('HTTP_X_FORWARDED_PORT') ?? '',
            'http_x_forwarded_proto' => $server->get('HTTP_X_FORWARDED_PROTO') ?? '',
            'http_x_forwarded_for' => $httpXForwardedFor,
            'script_name' => $server->get('SCRIPT_NAME') ?? '',
            'php_self' => $server->get('PHP_SELF') ?? '',
            'argv' => json_encode($server->get('argv')) ?? '',
            'argc' => $server->get('argc') ?? '',
            'trace' => $trace,
        ];

        $user = $user ?? Auth::user();
        if (!empty($user)) {
            $default_data['user_id'] = $user->id;
            $default_data['user_email'] = $user->email;
        }

        $put_data = array_merge($data, $default_data);

        return $put_data;
    }

    private function getParamsCredential()
    {
        $credential_params = [
            'region' => config('aws.aws_region'),
            'version' => 'latest',
        ];
        if (config('aws.aws_access_key') && config('aws.aws_secret_access_key')) {
            $credential_params += [
                'credentials' => [
                    'key' => config('aws.aws_access_key'),
                    'secret' => config('aws.aws_secret_access_key')
                ]
            ];
        }
        return $credential_params;
    }

    /**
     * PUT data into a stream
     *
     * @param type $arguments
     */
    public function putFirehose($data = [])
    {
        $firehose = new FirehoseClient($this->getParamsCredential());
        try {
            $result = $firehose->PutRecord([
                'DeliveryStreamName' => config('aws.app.firehose_stream_name'),
                'Record' => [
                    'Data' => json_encode($data)
                ],
            ]);
            return $result;
        } catch (\AwsException $e) {
            // output error message if fails
            logger()->error('=========AwsError=========: ', [$e]);
        }
    }

    /**
     * List firehose data streams
     *
     * @param type $arguments
     */
    public function firehoseList($arguments = [])
    {
        $firehose = new FirehoseClient($this->getParamsCredential());
        try {
            $result = $firehose->listDeliveryStreams([
                'DeliveryStreamType' => 'DirectPut',
            ]);
            return $result;
        } catch (\Aws\Exception\AwsException $e) {
            // output error message if fails
            logger()->error('=========AwsError=========: ', [$e]);
        }
    }

    /**
     * Describe a firehose data stream
     *
     * @param type $name
     */
    public function firehoseDescribe($name = '')
    {
        $firehose = new FirehoseClient($this->getParamsCredential());
        try {
            $result = $firehose->describeDeliveryStream([
                'DeliveryStreamName' => $name,
            ]);
            return $result;
        } catch (AwsException $e) {
            // output error message if fails
            logger()->error('=========AwsError=========: ', [$e]);
        }
    }

    public function performFirehosePut($data, $user = null)
    {
        if (Utils::isProduction()) {
            PutFirehoseJob::dispatch($this->addDataDefault($data, $user))->onQueue(Consts::QUEUE_PUT_FIREHOSE);
        }
    }
}
