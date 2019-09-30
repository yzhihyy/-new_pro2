<?php

namespace app\api\middleware;

class CheckVersion
{

    /**
     * 版本号验证，低于配置版本就报错
     * @param \think\Request $request
     * @param \Closure $next
     * @return mixed|\think\response\Json
     */
    public function handle($request, \Closure $next)
    {
        $version = $request->header('version');
        $platform = $request->header('platform', '');
        $lowVersion = config('parameters.low_version');
        $compareVersion = version_compare($version, $lowVersion['version'], 'le');

        if(!$lowVersion['enable_flag'] || !$compareVersion || (!empty($platform) && !in_array($platform, $lowVersion['platform']))){
            return $next($request);
        }

        $msg = config('response.msg106');
        return apiError($msg);



    }
}
