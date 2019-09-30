<?php

namespace app\api\controller\v2_0_0\user;

use app\api\Presenter;
use app\common\utils\string\StringHelper;

class Service extends Presenter
{
    /**
     * APP版本更新
     *
     * @return \think\response\Json
     */
    public function appVersionUpdate()
    {
        // 平台类型 1：iOS；2：android
        $platform = request()->header('platform');
        $platform = in_array($platform, [1, 2]) ? $platform : 0;
        // 当前版本号
        $curVersion = request()->header('version');
        // APP类型，默认用户端
        $appType = input('get.app_type', 1);
        // 默认没有新版本
        $newVersion = ['', 0, '', ''];
        switch ($appType) {
            case 2:
                $cfgAppVersion = config('parameters.user_app_version');
                break;
            default:
                $cfgAppVersion = config('parameters.user_app_version');
                break;
        }
        try {
            $version = db('app_version')->where([
                'platform' => $platform,
                'app_type' => $appType
            ])->order('id', 'desc')->limit(1)->find();

            // 数据库存在对应的版本信息
            if (!empty($version)) {
                if ($curVersion < $version['new_version']) {
                    $newVersion = [
                        $version['update_url'],
                        "{$version['update_type']}",
                        $version['update_info'],
                        $version['new_version']
                    ];
                }
            } else {
                // 判断如果当前版本小于最新版本，则提醒更新
                if ($curVersion < $cfgAppVersion['new_version']) {
                    // 数据库为空时从配置文件取得更新信息
                    $newVersion = [
                        $platform == 1 ? $cfgAppVersion['update_url']['ios'] : $cfgAppVersion['update_url']['android'],
                        "{$cfgAppVersion['update_type']}",
                        $cfgAppVersion['update_info'],
                        $cfgAppVersion['new_version']
                    ];

                }
            }
        } catch (\Exception $e) {
            generateApiLog('APP版本更新异常信息：' . $e->getMessage());
        }

        $newVersion = array_combine(['url', 'type', 'info', 'new_version'], $newVersion);
        // null转化为空字符串
        $newVersion = StringHelper::nullValueToEmptyValue($newVersion);

        return apiSuccess($newVersion);
    }
}
