<?php

namespace app\common\exception;

use Exception;
use think\Container;
use think\Response;
use think\exception\Handle as ThinkExceptionHandle;
use think\exception\HttpException;

class Handle extends ThinkExceptionHandle
{
    public function render(Exception $e)
    {
        if (Container::get('app')->isDebug()) {
            return parent::render($e);
        } else {
            $content = Container::get('app')->config('error_message');
            $response = Response::create($content, 'html');
            if ($e instanceof HttpException) {
                $statusCode = $e->getStatusCode();
                $response->header($e->getHeaders());
            }
            if (!isset($statusCode)) {
                $statusCode = 500;
            }
            $response->code($statusCode);
            return $response;
        }
    }
}