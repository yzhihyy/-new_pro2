<?php

namespace think;

use app\api\model\v3_6_0\SocialUserModel;
use app\api\model\v3_6_0\VideoModel;
use app\common\utils\easemob\EasemobHelper;

// 加载基础文件
require __DIR__ . '/thinkphp/base.php';

// 应用初始化
Container::get('app')->path(__DIR__ . '/application/')->initialize();

// 临时设置最大内存占用为2G
ini_set('memory_limit', '2048M');
// 设置脚本最大执行时间 为0 永不过期
set_time_limit(0);

// 获取用户列表
$videoModel = new VideoModel();
$videos = $videoModel->alias('v')
    ->leftJoin('user u', 'u.id = v.user_id')
    ->field([
        'v.id as video_id',
        'v.user_id',
        'v.generate_time'
    ])
    ->where([
        ['v.audit_status', '=', 1],
        ['v.visible', '=', 1],
        ['v.status', '=', 1],
        ['u.is_robot', '=', 1],
        ['u.account_status', '=', 1],
    ])
    ->group('v.user_id')
    ->order([
        'video_id' => 'desc'
    ])
    ->fetchSql(false)
    ->select();

// 注册环信
$easemobHelper = new EasemobHelper();
$socialUserModel = new SocialUserModel();
foreach ($videos as $video) {
    $userId = $video['user_id'];
    // 判断是否已经注册环信，如果未注册则进行注册
    $socialUser = $socialUserModel->alias('su')
        ->where([
            ['su.user_id', '=', $userId]
        ])
        ->find();
    if (empty($socialUser)) {
        $user_prefix = config('easemob.user_prefix');
        $username = $user_prefix . 'user_' . $userId;
        $password = generateEasemobPassword();
        $nickname = '';
        $easemobUser = $easemobHelper->authSingleRegister($username, $password, $nickname);
        // 保存环信用户
        if ($easemobUser) {
            $data = [
                'user_id' => $userId,
                'hx_uuid' => $easemobUser['uuid'],
                'hx_username' => $easemobUser['username'],
                'hx_nickname' => $nickname,
                'hx_password' => $password,
                'generate_time' => date('Y-m-d H:i:s')
            ];
            $socialUserModel::create($data);
        }
    }
}