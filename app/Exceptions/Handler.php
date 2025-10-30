<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Session\TokenMismatchException;
use App\Exceptions\Reports\BaseException;
use Illuminate\Support\Str;
use App\Mails\ExceptionEmail;
use Mail;
use App\Utils;
use App\Http\Services\SystemNotification;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        logger()->error($exception);

        if ($this->isAdminLoginRoute()) {
            return parent::render($request, $exception);
        }

        if ($exception instanceof ValidationException) {
            return self::handleResponseFormRequestException($exception);
        }

        if ($exception instanceof TokenMismatchException) {
            if ($request->is('login') && $request->isMethod('post')) {
                return $this->handleLoginTokenMismatchException($exception);
            }
        }

        $internalError = $exception instanceof BaseException;
        if ($internalError) {
            return self::handleResponseException($exception);
        }

        if ($this->shouldReport($exception) && !$internalError && Utils::isProduction()) {
            $content = "{$exception->getMessage()} at {$exception->getFile()}:{$exception->getLine()} {$exception->getTraceAsString()}";
            SystemNotification::sendExceptionEmail($content);
        }

        return parent::render($request, $exception);
    }

    private function handleResponseFormRequestException(ValidationException $exception)
    {
        return response()->json([
            'status' => false,
            'message' => $exception->getMessage(),
            'errors' => self::formatValidationErrors($exception->validator),
        ], 422);
    }

    protected function handleLoginTokenMismatchException($exception) {
        logger()->warning('Login EXCEPTION TokenMismatchException');

        return redirect()->guest('login')->withErrors([
            'session_timeout' => $exception->getMessage()
        ]);
    }

    private function handleResponseException(BaseException $exception)
    {
        return response()->json([
            'status' => false,
            'error' => [
                'key' => $exception->key(),
                'content' => $exception->getMessage(),
            ],
        ], 400);
    }

    private function formatValidationErrors($validator)
    {
        $msgErrors = $validator->errors()->getMessages();

        if (!$validator->failed()) {
            return $msgErrors;
        }

        $result = [];
        foreach($validator->failed() as $input => $rules){
            $i = 0;
            $errorsDetail = [];

            foreach($rules as $rule => $ruleInfo) {
                $key = sprintf('validation.%s', Str::snake($rule));
                $errorsDetail[$key] = $msgErrors[$input][$i];
                $i++;
            }

            $result[$input] = $errorsDetail;
        }
        return $result;
    }

    private function isAdminLoginRoute()
    {
        return request()->is('admin/login');
    }

}
