<?php

namespace app\api\logic\v3_0_0\user;

use app\api\logic\BaseLogic;
use app\api\model\v3_0_0\{FollowRelationModel,
    ShopModel,
    ThemeActivityModel,
    ThemeActivityShopModel,
    ThemeArticleModel,
    ThemeVoteRecordModel,
    UserModel,
    VideoModel};
use app\common\utils\date\DateHelper;
use app\api\logic\shop\SettingLogic;

class ThemeActivityLogic extends BaseLogic
{
    /**
     * 报名参加主题
     *
     * @param User $user 用户信息
     * @param array $paramsArray 参数数组
     *
     * @return array
     * @throws \Exception
     */
    public function signUp($user, $paramsArray)
    {
        $themeModel = model(ThemeActivityModel::class);
        $theme = $themeModel->getTheme($paramsArray['theme_id']);
        // 主题不存在
        if (!$theme) {
            return $this->logicResponse(config('response.msg83'));
        }

        // 主题已结束
        if ($theme->theme_status == 3) {
            return $this->logicResponse(config('response.msg85'));
        }

        $themeShop = ThemeActivityShopModel::where(['theme_id' => $theme->id, 'user_id' => $user->id, 'delete_status' => 0])->find();
        // 已经报名
        if ($themeShop && in_array($themeShop->status, [1, 2])) {
            return $this->logicResponse(config('response.msg92'));
        }

        // 用户是否有上线的主店铺
        $shop = ShopModel::where(['shop_type' => 1, 'user_id' => $user->id, 'online_status' => 1])->find();
        // 有报名
        if ($themeShop) {
            // 被拒绝，更新为待审批
            if ($themeShop->status == 3) {
                $themeShop->status = 1;
                $themeShop->save();
            }
        } else { // 没报名过
            ThemeActivityShopModel::create([
                'user_id' => $user->id,
                'shop_id' => $shop ? $shop->id : 0,
                'theme_id' => $theme->id,
                'status' => 1, // 待审批
                'generate_time' => date('Y-m-d H:i:s')
            ]);
        }

        return $this->logicResponse();
    }

    /**
     * 主题活动详情
     *
     * @param array $paramsArray 参数数组
     * @param int $page 页码
     *
     * @return array
     * @throws \Exception
     */
    public function activityDetail($paramsArray, $page)
    {
        $userId = $this->getUserId();
        $themeModel = model(ThemeActivityModel::class);
        $theme = $themeModel->getTheme($paramsArray['theme_id'], ['theme_status' => [2, 3]]);
        // 主题不存在
        if (!$theme) {
            return $this->logicResponse(config('response.msg83'));
        }

        $themeShopModel = model(ThemeActivityShopModel::class);
        // 是否已报名
        $isJoin = 0;
        // 是否有设置红包
        $hasBonus = $theme->bonus_status;
        // 该主题红包已领完或主题已结束
        if (decimalSub($theme->total_bonus, $theme->received_bonus) <= 0 || $theme->theme_status == 3) {
            $hasBonus = 0;
        }

        $themeShop = $themeShopModel->activityDetailShop([
            'themeId' => $theme->id,
            'page' => $page,
            'limit' => config('parameters.page_size_level_2')
        ]);

        // 投票记录
        $votedCount = [];
        // 红包领取记录
        $bonusRecord = [];
        // 用户有登录
        if ($userId) {
            // 判断是否报名过该活动
            $userThemeShop = $themeShopModel->where(['theme_id' => $theme->id, 'user_id' => $userId, 'status' => [1, 2], 'delete_status' => 0])->find();
            $userThemeShop && $isJoin = 1;

            // 判断是否已领红包
            $receivedBonusCount = $this->receivedBonusCount($userId, $theme->id);
            $receivedBonusCount > 0 && $hasBonus = 2;

            $voteRecordModel = model(ThemeVoteRecordModel::class);
            // 平台主题
            if ($theme->theme_type == 1) {
                // 判断是否已投票
                $themeShopIds = array_column($themeShop, 'shopId');
                if ($themeShopIds) {
                    $votedCount = $this->shopVotedCount($theme->id, $theme->vote_type, $userId, $themeShopIds);
                    // 之前有投票，但是未领取红包，查询领取记录
                    $bonusRecord = $voteRecordModel->getBonusRecord(['themeId' => $theme->id, 'userId' => $userId, 'shopId' => $themeShopIds]);
                }
            }
            // 个人主题
            elseif ($theme->theme_type == 2) {
                $themeArticleIds = array_column($themeShop, 'articleId');
                $votedCount = $this->articleVotedCount($theme->id, $theme->vote_type, $userId, $themeArticleIds);
                // 之前有投票，但是未领取红包，查询领取记录
                $bonusRecord = $voteRecordModel->getBonusRecord(['themeId' => $theme->id, 'userId' => $userId, 'articleId' => $themeArticleIds]);
            }
        }

        // 参加主题的店铺列表
        $shopList = [];
        foreach ($themeShop as $item) {
            $temp = [
                'shop_id' => $item['shopId'],
                'shop_thumb_image' => getImgWithDomain($item['shopThumbImage']),
                'shop_name' => $item['shopName'],
                'pay_setting_type' => $item['paySettingType'],
                'vote_count' => $item['voteCount'],
                'article_id' => $item['articleId'],
                'article_title' => $item['articleTitle'],
                'article_desc' => $item['articleTitle'],
                'article_cover' => getImgWithDomain($item['articleCover']),
                'vote_share_url' => config('h5.platform_theme_article'), // 主题文章 h5 url
                'record_id' => 0,
                'is_vote' => 0 // 是否已投票
            ];

            $voteField = 'shopId';
            // 个人主题
            if ($theme->theme_type == 2) {
                $voteField = 'articleId';
                $temp['vote_share_url'] = config('h5.personal_theme_article');
            }
            $temp['vote_share_url'] = sprintf($temp['vote_share_url'], $theme->id, $item['articleId']);

            foreach ($votedCount as $key => $value) {
                if ($item[$voteField] == $value[$voteField]) {
                    // 已投过票
                    if ($value['votedCount'] > 0) {
                        $temp['is_vote'] = 1;
                    }
                    unset($votedCount[$key]);
                }
            }

            foreach ($bonusRecord as $k => $v) {
                if ($item[$voteField] == $v[$voteField]) {
                    $temp['record_id'] = $v['recordId'];
                    unset($bonusRecord[$k]);
                }
            }

            $shopList[] = $temp;
        }

        // 总投票数
        $totalVoteCount = 0;
        // 开启投票
        if ($theme->vote_status == 1) {
            $totalVoteCount = $themeShopModel->where(['theme_id' => $paramsArray['theme_id'], 'status' => 2])->sum('vote_count');
        }
        // 倒计时
        $countdown = strtotime($theme->end_time) - strtotime('now');
        $response = [
            'theme_type' => $theme->theme_type,
            'theme_status' => $theme->theme_status,
            'theme_name' => $theme->theme_title,
            'theme_desc' => $theme->theme_desc,
            'theme_cover' => getImgWithDomain($theme->theme_cover),
            'theme_thumb_cover' => getImgWithDomain($theme->theme_thumb_cover),
            'theme_share_url' => sprintf(config('h5.platform_theme'), $theme->id),
            'view_count' => $theme->view_count,
            'countdown' => $countdown > 0 ? $countdown : 0,
            'total_vote_count' => $totalVoteCount,
            'vote_status' => $theme->vote_status,
            'booking_status' => $theme->booking_status,
            'is_join' => $isJoin, // 是否已报名
            'has_bonus' => $hasBonus, // 是否有红包，0领完或没有或主题已结束，1有红包，2达到上限
            'shop_id' => 0,
            'shop_name' => '',
            'shop_image' => '',
            'shop_thumb_image' => '',
            'shop_phone' => '',
            'longitude' => 0,
            'latitude' => 0,
            'pay_setting_type' => 0,
            'qq' => '',
            'wechat' => '',
            'show_send_sms' => 0,
            'show_phone' => 0,
            'show_enter_shop' => 0,
            'show_address' => 0,
            'show_wechat' => 0,
            'show_qq' => 0,
            'show_payment' => 0
        ];
        // 个人主题，返回店铺信息
        if ($theme->theme_type == 2) {
            $ownerShop = ShopModel::find($theme->shop_id);
            if ($ownerShop) {
                // 店铺设置逻辑
                $settingLogic = new SettingLogic();
                // 店铺设置转换
                $ownerShopSetting = $settingLogic->settingTransform($ownerShop['setting']);
                $response['theme_share_url'] = sprintf(config('h5.personal_theme'), $theme->id);
                $response['shop_id'] = $ownerShop->id;
                $response['shop_name'] = $ownerShop->shop_name;
                $response['shop_image'] = getImgWithDomain($ownerShop->shop_image);
                $response['shop_thumb_image'] = getImgWithDomain($ownerShop->shop_thumb_image);
                $response['shop_phone'] = $ownerShop->phone;
                $response['longitude'] = $ownerShop->longitude;
                $response['latitude'] = $ownerShop->latitude;
                $response['pay_setting_type'] = $ownerShop->pay_setting_type;
                $response['qq'] = $ownerShop->qq;
                $response['wechat'] = $ownerShop->wechat;
                $response['show_send_sms'] = $ownerShopSetting['show_send_sms'];
                $response['show_phone'] = $ownerShopSetting['show_phone'];
                $response['show_enter_shop'] = $ownerShopSetting['show_enter_shop'];
                $response['show_address'] = $ownerShopSetting['show_address'];
                $response['show_wechat'] = $ownerShopSetting['show_wechat'];
                $response['show_qq'] = $ownerShopSetting['show_qq'];
                $response['show_payment'] = $ownerShopSetting['show_payment'];
            }
        }

        // 参与主题的店铺列表
        $response['shop_list'] = $shopList;

        // 增加主题浏览量
        $theme->view_count++;
        $theme->save();

        return $this->logicResponse([], $response);
    }

    /**
     * 主题文章详情
     *
     * @param array $paramsArray 参数数组
     *
     * @return array
     * @throws \Exception
     */
    public function articleDetail($paramsArray)
    {
        $userId = $this->getUserId();
        $activityModel = model(ThemeArticleModel::class);
        $detail = $activityModel->getArticleDetail([
            'articleId' => $paramsArray['article_id'],
            'latitude' => $paramsArray['latitude'] ?? 0,
            'longitude' => $paramsArray['longitude'] ?? 0
        ]);
        if (!$detail) {
            return $this->logicResponse(config('response.msg5'));
        }

        $paramsArray['theme_id'] = $paramsArray['theme_id'] ?? 0;
        if ($paramsArray['theme_id']) {
            $theme = model(ThemeActivityModel::class)->getTheme($paramsArray['theme_id'], ['delete_status' => 0]);
        } else {
            $theme = model(ThemeActivityShopModel::class)->getThemeByArticle(['articleId' => $detail['articleId'], 'allThemeStatus' => true]);
        }
        $themeArray = [
            'theme_id' => 0, // 默认文章不参加主题，主题ID设置为0
            'theme_type' => 1, // 默认平台主题
            'theme_title' => '',
            'theme_status' => 2, // 主题状态，默认进行中
            'vote_status' => 0, // 默认文章不参加主题，关闭投票
            'vote_type' => 1, // 默认投票类型为永久
        ];
        // 主题存在
        if ($theme) {
            $themeArray = array_combine(array_keys($themeArray), [
                $theme->id,
                $theme->theme_type,
                $theme->theme_title,
                $theme->theme_status,
                $theme->vote_status,
                $theme->vote_type
            ]);
        }

        // 主题文章 h5 url
        $voteShareUrl = config('h5.platform_theme_article');
        // 个人主题
        if ($themeArray['theme_type'] == 2) {
            $voteShareUrl = config('h5.personal_theme_article');
        }

        // 店铺设置逻辑
        $settingLogic = new SettingLogic();
        // 店铺设置转换
        $detail['shopSetting'] = $shopSetting = $settingLogic->settingTransform($detail['shopSetting']);
        $response = [
            'article_id' => $detail['articleId'],
            'article_title' => $detail['articleTitle'],
            'article_desc' => $detail['articleTitle'],
            'article_cover' => getImgWithDomain($detail['articleCover']),
            'article_thumb_cover' => getImgWithDomain($detail['articleThumbCover']),
            'article_content' => $detail['articleContent'],
            'video_position' => $detail['videoPosition'],
            'video_id' => $detail['videoId'],
            'video_url' => '',
            'cover_url' => '',
            'shop_id' => $detail['shopId'],
            'shop_name' => $detail['shopName'],
            'announcement' => $detail['shopAnnouncement'],
            'shop_image' => getImgWithDomain($detail['shopImage']),
            'shop_thumb_image' => getImgWithDomain($detail['shopThumbImage']),
            'shop_address' => $detail['shopAddress'],
            'shop_address_poi' => $detail['shopAddressPoi'],
            'shop_phone' => $detail['shopPhone'],
            'operation_time' => $detail['operationTime'],
            'views' => $detail['articleViews'] + 1, // 返回增加此次浏览后的浏览量
            'distance' => (int) $detail['distance'],
            'longitude' => $detail['longitude'],
            'latitude' => $detail['latitude'],
            'shop_category_name' => $detail['shopCategoryName'],
            'pay_setting_type' => $detail['paySettingType'],
            'related_pay_setting_type' => (string) $detail['paySettingType'],
            'qq' => $detail['shopQq'],
            'wechat' => $detail['shopWechat'],
            'show_send_sms' => $detail['shopSetting']['show_send_sms'],
            'show_phone' => $detail['shopSetting']['show_phone'],
            'show_enter_shop' => $detail['shopSetting']['show_enter_shop'],
            'show_address' => $detail['shopSetting']['show_address'],
            'show_wechat' => $detail['shopSetting']['show_wechat'],
            'show_qq' => $detail['shopSetting']['show_qq'],
            'show_payment' => $detail['shopSetting']['show_payment'],
            'theme_id' => $themeArray['theme_id'],
            'theme_type' => $themeArray['theme_type'],
            'theme_title' => $themeArray['theme_title'],
            'theme_status' => $themeArray['theme_status'],
            'vote_status' => $themeArray['vote_status'],
            'vote_share_url' => sprintf($voteShareUrl, $themeArray['theme_id'], $detail['articleId']),
            'is_vote' => 0
        ];

        //个人主题时关闭该按钮
        if($themeArray['theme_type'] == 2){
            $response['show_send_sms'] = 0;
            $response['show_phone'] = 0;
            $response['show_enter_shop'] = 0;
            $response['show_address'] = 0;
            $response['show_wechat'] = 0;
            $response['show_qq'] = 0;
            $response['show_payment'] = 0;
        }

        // 文章内容视频位置
        if ($detail['videoPosition'] != 0) {
            $video = VideoModel::find($detail['videoId']);
            if ($video) {
                $response['video_url'] = $video->video_url;
                $response['cover_url'] = getImgWithDomain($video->cover_url);
            }
        }

        // 用户有登录且该文章有参加主题且开启投票，判断是否已投票
        if ($userId && $themeArray['vote_status'] == 1) {
            // 平台主题
            if ($themeArray['theme_type'] == 1) {
                $votedCount = $this->shopVotedCount($themeArray['theme_id'], $themeArray['vote_type'], $userId, $detail['shopId']);
            }
            // 个人主题
            elseif ($themeArray['theme_type'] == 2) {
                $votedCount = $this->articleVotedCount($themeArray['theme_id'], $themeArray['vote_type'], $userId, $detail['articleId']);
            }
            // 已投票
            if (isset($votedCount) && $this->isVotedToShop($votedCount)) {
                $response['is_vote'] = 1;
            }
        }

        // 增加文章浏览量
        ThemeArticleModel::where('id', $detail['articleId'])->setInc('views');

        return $this->logicResponse([], $response);
    }

    /**
     * 投票
     *
     * @param User $user 用户信息
     * @param array $paramsArray 参数数组
     *
     * @return array
     * @throws \Exception
     */
    public function vote($user, $paramsArray)
    {
        $themeModel = model(ThemeActivityModel::class);
        $theme = $themeModel->getTheme($paramsArray['theme_id']);
        // 主题不存在
        if (!$theme) {
            return $this->logicResponse(config('response.msg83'));
        }

        // 主题招募中
        if ($theme->theme_status == 1) {
            return $this->logicResponse(config('response.msg89'));
        } elseif ($theme->theme_status == 3) { // 主题已结束
            return $this->logicResponse(config('response.msg85'));
        }

        // 未开启投票
        if ($theme->vote_status == 0) {
            return $this->logicResponse(config('response.msg103'));
        }

        $themeShop = ThemeActivityShopModel::where([
            'theme_id' => $theme->id,
            'shop_id' => $paramsArray['shop_id'],
            'status' => 2,
            'delete_status' => 0
        ])->find();
        // 主题店铺不存在
        if (!$themeShop) {
            return $this->logicResponse(config('response.msg86'));
        }

        // 平台主题
        if ($theme->theme_type == 1) {
            // 在单店铺的投票次数
            $shopVotedCount = $this->shopVotedCount($theme->id, $theme->vote_type, $user->id, $themeShop->shop_id);
            // 一家店铺永久/当天只能投票一次
            if ($this->isVotedToShop($shopVotedCount)) {
                return $this->logicResponse(explode('|', sprintf(config('response.msg87'), '店铺')));
            }
        }

        // 总的已投票次数
        $totalVotedCount = $this->totalVotedCount($theme->id, $theme->vote_type, $user->id);
        // 投票次数达到上限
        if ($this->isOverVoteLimit($theme->vote_limit, $totalVotedCount)) {
            // 永久投票
            if ($theme->vote_type == 1) {
                return $this->logicResponse(explode('|', config('response.msg101')));
            } elseif ($theme->vote_type == 2) { // 每日投票
                return $this->logicResponse(explode('|', config('response.msg102')));
            }
        }

        // 开启事务
        ThemeVoteRecordModel::startTrans();

        // 创建投票
        $record = ThemeVoteRecordModel::create([
            'theme_id' => $theme->id,
            'theme_type' => $theme->theme_type,
            'article_id' => 0,
            'user_id' => $user->id,
            'shop_id' => $themeShop->shop_id,
            'receive_status' => 0,
            'generate_time' => date('Y-m-d H:i:s')
        ]);
        // 增加店铺投票数
        ThemeActivityShopModel::where(['theme_id' => $theme->id, 'shop_id' => $themeShop->shop_id])->setInc('vote_count');

        // 领取的红包金额
        $bonus = '0.00';
        // 是否有设置红包
        $hasBonus = $theme->bonus_status;
        if ($hasBonus == 1) {
            // 剩余可领的红包金额
            $subBonus = decimalSub($theme->total_bonus, $theme->received_bonus);
            // 该主题红包已领完
            if ($subBonus <= 0) {
                $hasBonus = 0;
            } else {
                $receivedBonusCount = $this->receivedBonusCount($user->id, $theme->id);
                $hasBonus = $receivedBonusCount == 0 ? 1 : 2;
            }

            // h5直接领取红包
            if ($this->request->header('platform') == 3) {
                if ($hasBonus == 1) {
                    $bonus = $this->saveBonus($subBonus, $theme, $record, $user);
                    // 领完状态
                    $hasBonus = 2;
                }
            }
        }

        // 提交事务
        ThemeVoteRecordModel::commit();

        return $this->logicResponse([], [
            'record_id' => (int) $record->id,
            'bonus_status' => $theme->bonus_status,
            'bonus' => $bonus,
            'has_bonus' => $hasBonus, // 是否有红包，0领完或没有，1有红包，2达到上限
        ]);
    }

    /**
     * 领取投票分享红包
     *
     * @param array $paramsArray 参数数组
     *
     * @return array
     * @throws \Exception
     */
    public function receiveBonus($paramsArray)
    {
        $response = ['bonus' => '0.00'];
        $record = ThemeVoteRecordModel::find($paramsArray['record_id']);
        // 记录不存在 || 已领红包
        if (!$record || $record->receive_status == 1) {
            return $this->logicResponse([], $response);
        }

        // 一个主题只能领取一个红包
        $receivedBonusCount = $this->receivedBonusCount($record->user_id, $record->theme_id);
        if ($receivedBonusCount > 0) {
            return $this->logicResponse([], $response);
        }

        $theme = model(ThemeActivityModel::class)->getTheme($record->theme_id);
        // 主题不存在 || 招募中和已结束，不发放红包
        if (!$theme || in_array($theme->theme_status, [1, 3])) {
            return $this->logicResponse([], $response);
        }

        // 剩余可领的红包金额
        $subBonus = decimalSub($theme->total_bonus, $theme->received_bonus);
        // 红包已领完
        if ($subBonus <= 0) {
            return $this->logicResponse([], $response);
        }

        // 投票用户
        $user = UserModel::find($record->user_id);
        // 用户不存在
        if (!$user) {
            return $this->logicResponse([], $response);
        }

        // 开启事务
        ThemeVoteRecordModel::startTrans();

        $bonus = $this->saveBonus($subBonus, $theme, $record, $user);

        // 提交事务
        ThemeVoteRecordModel::commit();

        $response['bonus'] = $bonus;

        return $this->logicResponse([], $response);
    }

    /**
     * 保存投票分享红包
     *
     * @param string $subBonus 剩余可领的红包金额
     * @param ThemeActivityModel $theme 主题
     * @param ThemeVoteRecordModel $record 投票记录
     * @param UserModel $user 用户
     *
     * @return string
     */
    public function saveBonus($subBonus, $theme, $record, $user)
    {
        // 设置的最低红包比剩余可领的红包大
        if (decimalSub($theme->vote_bonus_min, $subBonus) >= 0) {
            $bonus = $subBonus;
        } else {
            // 可领红包区间
            $interval = [$subBonus, $theme->vote_bonus_min, $theme->vote_bonus_max];
            sort($interval);
            // 计算红包金额
            $bonus = decimalSub(mt_rand($interval[0] * 100, $interval[1] * 100) / 100, 0);
        }

        // 更新领取记录
        $record->bonus = $bonus;
        $record->receive_status = 1;
        $record->save();

        // 更新主题已领红包金额
        $theme->received_bonus = decimalAdd($theme->received_bonus, $bonus);
        $theme->save();

        // 更新用户余额
        $beforeAmount = $user->balance;
        $user->balance = decimalAdd($beforeAmount, $bonus);
        $user->save();

        // 记录明细
        $this->saveUserTransactionsRecord($user->id, $bonus, [
            'shop_id' => $record->shop_id,
            'type' => 5, // 投票红包收入
            'order_id' => $record->id,
            'before_amount' => $beforeAmount,
            'after_amount' => $user->balance,
            'status' => 3
        ]);

        return $bonus;
    }

    /**
     * 主题活动列表
     *
     * @param $params
     *
     * @return mixed
     * @throws
     */
    public function themeActivityList($params)
    {
        $themeModel = model(ThemeActivityModel::class);
        $themeShopModel = model(ThemeActivityShopModel::class);
        $list = $themeModel->list($params);//活动主题列表
        $userId = $this->getUserId();

        foreach ($list as $key => &$value) {
            $value['theme_cover'] = getImgWithDomain($value['theme_cover']);
            $value['theme_thumb_cover'] = getImgWithDomain($value['theme_thumb_cover']);
            //参与的用户信息
            $themeParams = [
                'user_id'   => $userId,
                'theme_id'  => $value['theme_id'],
                'page'      => 0,
                'limit'     => 10,
                'vote_type' => $value['vote_type'],
                'today'     => DateHelper::getNowDateTime()->format('Y-m-d'),
            ];

            $activityInfo = $themeShopModel->themeShopList($themeParams);

            if (!empty($activityInfo)) {
                foreach ($activityInfo as $k => &$v) {
                    $v['article_cover'] = getImgWithDomain($v['article_cover']);
                    $v['article_thumb_cover'] = getImgWithDomain($v['article_thumb_cover']);
                    $v['vote_share_url'] = ''; // 主题文章 h5 url
                    $v['shop_image'] = getImgWithDomain($v['shop_image']);
                    $v['shop_thumb_image'] = getImgWithDomain($v['shop_thumb_image']);
                    $isVote = $v['priority'];

                    // 平台主题
                    if ($value['theme_type'] == 1) {
                        $v['vote_share_url'] = sprintf(config('h5.platform_theme_article'), $v['theme_id'], $v['article_id']);
                    } elseif ($value['theme_type'] == 2) { // 个人主题
                        $v['vote_share_url'] = sprintf(config('h5.personal_theme_article'), $v['theme_id'], $v['article_id']);
                    }

                    if ($value['theme_status'] == 3) {
                        $isVote = 2;//已结束
                    }
                    $v['is_vote'] = $isVote;
                    unset($v['theme_id']);
                }
            }
            $value['activity_info'] = $activityInfo;
        }
        return $list;
    }

    /**
     * 参与主题投票活动的商家
     *
     * @param $params
     *
     * @return mixed
     * @throws
     */
    public function themeActivityShopList($params)
    {
        $userId = $this->getUserId();
        $theme = model(ThemeActivityModel::class)->field(['theme_type', 'vote_type'])->find($params['theme_id']);
        $params['vote_type'] = $theme->vote_type ?? null;
        $params['user_id'] = $userId;
        $params['today']   = DateHelper::getNowDateTime()->format('Y-m-d');
        $model = model(ThemeActivityShopModel::class);
        $themeShop = $model->themeShopList($params);
        foreach ($themeShop as $key => &$value) {
            $value['article_cover'] = getImgWithDomain($value['article_cover']);
            $value['article_thumb_cover'] = getImgWithDomain($value['article_thumb_cover']);
            $value['vote_share_url'] = ''; // 主题文章 h5 url
            $value['shop_image'] = getImgWithDomain($value['shop_image']);
            $value['shop_thumb_image'] = getImgWithDomain($value['shop_thumb_image']);
            $value['article_count'] = $this->articleCount(['shop_id' => $value['shop_id']]);//主题作品数
            $value['follow_count'] = $this->followCount(['shop_id' => $value['shop_id']]);//获取粉丝总数
            $value['is_vote'] = $value['priority'];
            if (isset($theme->theme_type)) {
                // 平台主题
                if ($theme->theme_type == 1) {
                    $value['vote_share_url'] = sprintf(config('h5.platform_theme_article'), $value['theme_id'], $value['article_id']);
                } elseif ($theme->theme_type == 2) { // 个人主题
                    $value['vote_share_url'] = sprintf(config('h5.personal_theme_article'), $value['theme_id'], $value['article_id']);
                }
            }

            unset($value['theme_id']);
        }
        return $themeShop;
    }

    /**
     * 获取商家作品数
     *
     * @param $params
     *
     * @return float|string
     */
    private function articleCount($params)
    {
        return model(ThemeArticleModel::class)
            ->where(['shop_id' => $params['shop_id'], 'is_delete' => 0])
            ->count();
    }

    /**
     * 获取商家粉丝总数
     *
     * @param $params
     *
     * @return float|string
     */
    private function followCount($params)
    {
        return model(FollowRelationModel::class)
            ->where(['to_shop_id' => $params['shop_id'], 'rel_type' => 1])
            ->count();
    }

    /**
     * 获取主题店铺已投票数量
     *
     * @param int $themeId 主题ID
     * @param int $voteType 投票类型
     * @param int $userId 用户ID
     * @param int|array $shopId 店铺ID
     *
     * @return array
     */
    public function shopVotedCount($themeId, $voteType, $userId, $shopId)
    {
        $voteRecordModel = model(ThemeVoteRecordModel::class);
        $where = ['themeId' => $themeId, 'userId' => $userId, 'shopId' => $shopId];
        // 每天投票
        if ($voteType == 2) {
            $where['today'] = true;
        }
        return $voteRecordModel->getVotedCount($where);
    }

    /**
     * 获取主题文章已投票数量
     *
     * @param int $themeId 主题ID
     * @param int $voteType 投票类型
     * @param int $userId 用户ID
     * @param int|array $articleId 主题文章ID
     *
     * @return array
     */
    public function articleVotedCount($themeId, $voteType, $userId, $articleId)
    {
        $voteRecordModel = model(ThemeVoteRecordModel::class);
        $where = ['themeId' => $themeId, 'userId' => $userId, 'articleId' => $articleId];
        // 每天投票
        if ($voteType == 2) {
            $where['today'] = true;
        }
        return $voteRecordModel->getVotedCount($where);
    }

    /**
     * 获取主题总共已投票数量
     *
     * @param int $themeId 主题ID
     * @param int $voteType 投票类型
     * @param int $userId 用户ID
     *
     * @return int
     */
    public function totalVotedCount($themeId, $voteType, $userId)
    {
        $voteRecordModel = model(ThemeVoteRecordModel::class);
        $where = ['themeId' => $themeId, 'userId' => $userId];
        // 每天投票
        if ($voteType == 2) {
            $where['today'] = true;
        }
        $count = $voteRecordModel->getVotedCount($where);

        return current($count)['votedCount'];
    }

    /**
     * 在单店铺投票次数是否超过上限
     *
     * @param array $votedCount
     *
     * @return bool
     */
    public function isVotedToShop($votedCount)
    {
        // 一家店铺永久/当天只能投票一次
        return $votedCount && reset($votedCount)['votedCount'] > 0;
    }

    /**
     * 总投票次数是否达到上限
     *
     * @param int $voteLimit 投票次数限制
     * @param int $votedCount 已投票次数
     *
     * @return bool
     */
    public function isOverVoteLimit($voteLimit, $votedCount)
    {
        // 投票次数达到上限
        return $voteLimit <= $votedCount;
    }

    /**
     * 在主题下已领取的红包数量
     *
     * @param int $userId 用户ID
     * @param int $themeId 主题ID
     *
     * @return float|string
     */
    public function receivedBonusCount($userId, $themeId)
    {
        $voteRecordModel = model(ThemeVoteRecordModel::class);

        return $voteRecordModel->getReceivedBonusCount(compact('userId', 'themeId'));
    }
}
