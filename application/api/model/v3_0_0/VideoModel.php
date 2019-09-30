<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class VideoModel extends AbstractModel
{
    protected $name = 'video';

    /**
     * @return $this
     */
    public function getQuery()
    {
        return $this->alias('v')
            ->field([
                'v.id',
                'v.id AS videoId',
                'v.category_id AS categoryId',
                'v.user_id AS userId',
                'v.shop_id AS shopId',
                'v.province_id AS provinceId',
                'v.city_id AS cityId',
                'v.area_id AS areaId',
                'v.title',
                'v.content',
                'v.cover_url AS coverUrl',
                'v.video_url AS videoUrl',
                'v.like_count AS likeCount',
                'v.comment_count AS commentCount',
                'v.share_count AS shareCount',
                'v.is_recommend AS isRecommend',
                'v.status',
                'v.generate_time AS generateTime',
            ]);
    }

    /**
     * 获取首页视频列表
     *
     * @param array $where
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getHomeVideoList(array $where = [])
    {
        $query = $this->alias('v')
            ->field([
                'v.id',
                'v.id AS videoId',
                'v.user_id AS videoUserId',
                'v.shop_id AS videoShopId',
                'v.title',
                'v.cover_url AS coverUrl',
                'v.video_url AS videoUrl',
                'v.video_width AS videoWidth',
                'v.video_height AS videoHeight',
                'v.like_count AS likeCount',
                'v.comment_count AS commentCount',
                'v.share_count AS shareCount',
                'v.generate_time AS generateTime',
                'u.nickname',
                'u.avatar',
                'u.thumb_avatar AS thumbAvatar',
                's.id AS shopId',
                's.shop_name AS shopName',
                's.shop_image AS shopImage',
                's.shop_thumb_image AS shopThumbImage',
            ])
            ->join('user u', 'u.id = v.user_id AND u.account_status = 1')
            ->leftJoin('shop s', 's.id = v.shop_id')
            ->where('v.type', 1)
            ->where('v.status', 1)
            ->where('v.visible', 1)
            // 店铺被禁用或下线后搜索不显示店铺视频
            ->where('IF(s.id, s.account_status = 1 AND s.online_status = 1, 1)');

        // 指定城市
        if (isset($where['cityId'])) {
            $query->where('v.city_id', $where['cityId']);
        }

        $type = $where['type'] ?? 1;
        switch ($type) {
            // 获取系统推荐视频
            case 1:
                $query->where('v.is_recommend', 1);
                break;
            // 获取用户喜好的标签视频(不包含系统推荐视频)
            case 2:
                if (empty($where['tagIds'])) {
                    return [];
                }

                $query->join('video_tag_relation vtr', "vtr.video_id = v.id AND vtr.tag_id IN ({$where['tagIds']})")
                    ->where('v.is_recommend', 0);
                break;
            // 获取普通视频(不包含系统推荐视频/不包含指定视频)
            case 3:
                if (!empty($where['videoIds']) && is_array($where['videoIds'])) {
                    $query->whereNotIn('v.id', $where['videoIds']);
                }

                $query->where('v.is_recommend', 0);
                break;
            // 获取指定标签下的视频
            case 4:
                if (empty($where['tagIds'])) {
                    return [];
                }

                $query->join('video_tag_relation vtr', "vtr.video_id = v.id AND vtr.tag_id IN ({$where['tagIds']})");
                break;
            default:
                return [];
        }

        // 随机数据
        //$maxSql = $this->field(['MAX(id)'])->buildSql();
        //$minSql = $this->field(['MIN(id)'])->buildSql();
        //$randSql = "(SELECT (ROUND(RAND() * ({$maxSql} - {$minSql}) + {$minSql}) - {$where['limit']}) AS id)";
        //$query->join([$randSql => 'rv'], 'v.id >= rv.id');

        if (!empty($where['userId'])) {
            // 包含用户观看过的视频,默认不包含
            $containHistoryFlag = $where['contain_history_flag'] ?? false;
            if (!$containHistoryFlag) {
                $query->leftJoin('user_video_history uvh', "uvh.user_id = {$where['userId']} AND uvh.video_id = v.id")
                    ->where('uvh.video_id', 'null');
            }

            // 关注|点赞
            $query->field([
                'IF(fr.id, 1, 0) AS isFollow',
                'IF(va.id, 1, 0) AS isLike',
            ])
                ->leftJoin('follow_relation fr', "fr.from_user_id = {$where['userId']} AND fr.rel_type = 1 AND IF(v.shop_id > 0, v.shop_id = fr.to_shop_id, v.user_id = fr.to_user_id)")
                ->leftJoin('video_action va', "va.action_type = 1 AND va.video_id = v.id AND va.user_id = {$where['userId']} AND va.status = 1");

            // 拉黑过滤
            $query->leftJoin('user_black_list ubl', "ubl.user_id = {$where['userId']} AND IF(v.shop_id > 0, ubl.to_shop_id = v.shop_id, ubl.to_user_id = v.user_id)")
                ->where('IF(ubl.id, ubl.status = 2, 1)');
        }

        $result = $query->group('v.id')
            ->orderRand()
            ->limit($where['limit'])
            ->select()
            ->toArray();

        return $result;
    }

    /**
     * 获取用户喜好的商家
     *
     * @param array $where
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserLikeShop(array $where = [])
    {
        $unionSql = [];
        // 用户点赞和转发过的视频
        $unionSql[] = $this->name(model(VideoActionModel::class)->getName())
            ->alias('va')
            ->field(['va.video_id AS videoId'])
            ->where('va.user_id', $where['userId'])
            ->whereIn('va.action_type', [1, 2])
            ->buildSql();
        // 用户评论过的视频
        $unionSql[] = $this->name(model(VideoCommentModel::class)->getName())
            ->alias('vc')
            ->field(['vc.video_id'])
            ->where('vc.from_user_id', $where['userId'])
            ->buildSql();
        // 用户完整观看的视频
        $unionSql[] = $this->name(model(UserVideoHistoryModel::class)->getName())
            ->alias('uvh')
            ->field(['uvh.video_id'])
            ->where('uvh.user_id', $where['userId'])
            ->where('uvh.play_finished', 1)
            ->buildSql();
        $unionSql = '(' . implode(' UNION ALL ', $unionSql) . ')';

        return $this->alias('v')
            ->distinct(true)
            ->field([
                'v.shop_id AS shopId'
            ])
            ->join([
                $unionSql => 'uls'
            ], 'uls.videoId = v.id')
            ->where('v.status', 1)
            ->select()
            ->toArray();
    }

    /**
     * 搜索视频
     *
     * @param $where
     *
     * @return array
     * @throws \Exception
     */
    public function searchVideo($where = [])
    {
        // 主查询
        $query = $this->alias('v')
            ->join('user u', 'v.user_id = u.id and u.account_status = 1')
            ->leftJoin('shop s', 'v.shop_id = s.id')
            ->leftJoin('shop_category sc', 's.shop_category_id = sc.id')
            ->field([
                'v.id',
                'v.id as videoId',// 视频ID
                'v.user_id as videoUserId',// 视频用户ID
                'v.title as videoTitle', // 视频标题
                'v.content as videoContent', // 视频简介
                'v.video_url as videoUrl', // 视频播放地址
                'v.cover_url as coverUrl', // 封面图
                'v.video_width AS videoWidth', // 视频宽度
                'v.video_height AS videoHeight', // 视频高度
                'v.like_count as likeCount', // 点赞数
                'v.comment_count as commentCount', // 评论数
                'v.location as videoLocation', // 发布视频的地理位置
                'v.share_count as shareCount', // 转发数
                'v.generate_time as generateTime', // 视频发布时间
                's.id as shopId', // 店铺ID
                's.shop_name as shopName', // 店铺名称
                's.shop_image as shopImage', // 商家头像
                's.shop_thumb_image as shopThumbImage', // 商家头像
                's.shop_address as shopAddress', // 商家地址
                'v.video_width as videoWidth', // 视频宽
                'v.video_height as videoHeight', // 视频高
                'v.is_top as isTop',
                'v.type',
                'u.id as videoUserId',
                'if(v.shop_id, s.shop_image, u.thumb_avatar) as avatar',
                'if(v.shop_id, s.shop_name, u.nickname) as nickname',
                'if(v.shop_id, 2, 1) as videoType', // 视频归属类型，1用户，2商家
                's.setting',
                's.phone',
                'v.relation_shop_id AS relationShopId',
                'v.audit_status as auditStatus', // 审核状态
            ])
            ->where('v.status', 1)
            ->where('v.visible', 1)
            ->where('v.audit_status', 1)
            // 店铺被禁用或下线后搜索不显示店铺视频
            ->where('if(s.id, s.account_status = 1 AND s.online_status = 1, 1)');
        // 是否搜索
        if (isset($where['keyword'])) {
            $query->where('v.title|v.content', 'like', '%' . $where['keyword'] . '%');
        }

        //判断当前请求版本号，如果小于3.3.0版本增加视频类型为1判断
        $requestVersion = request()->header('version');
        //$currentVersion = config('parameters.current_version');
        $currentVersion = '3.3.0';
        $versionCheck = version_compare($requestVersion, $currentVersion, 'ge');
        if (!$versionCheck) {
            $query->where('v.type', 1);
        }
        // 增加审核状态为1判断
        $query->where('v.audit_status', 1);

        // 是否点赞视频&&是否关注过
        if (isset($where['userId']) && $where['userId']) {
            // 查询是否点赞过视频
            $query->leftJoin('video_action va', "va.action_type = 1 AND va.user_id = {$where['userId']} AND va.video_id = v.id AND va.status = 1")
                ->field(['IF(va.id, 1, 0) as videoIsLike']);
            // 只查询关注的视频
            if (isset($where['followedFlag']) && $where['followedFlag']) {
                $followJoinType = 'join';
            } else { // 是否关注过
                $followJoinType = 'leftJoin';
            }
            $query->field(['IF(fr.id, 1, 0) as isFollow'])
                ->{$followJoinType}('follow_relation fr', "fr.from_user_id = '{$where['userId']}' AND fr.rel_type = 1 AND 
                if(v.shop_id, fr.to_shop_id = v.shop_id, fr.to_user_id = v.user_id)");
        }

        if (isset($where['followedOrderFlag']) && $where['followedOrderFlag']) {
            $query->order('v.generate_time', 'desc');
        } else {
            $query->order([
                'v.is_recommend' => 'desc',
                'v.recommend_time' => 'desc',
                'v.like_count' => 'desc',
                'v.comment_count' => 'desc',
                'v.share_count' => 'desc',
                'v.generate_time' => 'desc'
            ]);
        }
        $query->limit($where['page'] * $where['limit'], $where['limit']);

        return $query->select();
    }

    /**
     * 获取点赞数最多的评论.
     *
     * @param $where
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function getVideoCommentLikeMostInfo($where)
    {
        $videoIdArr = $where['videoIdArr'];
        $subQuery = $this->name(model(VideoCommentModel::class)->getName())
            ->alias('vc')
            ->distinct(true)
            ->field([
                'vc.video_id as videoId', // 视频ID
                'vc.id as commentId', // 评论ID
                'vc.like_count as commentLikeCount', // 评论点赞数量
                'vc.content as commentContent', // 评论内容
                'vc.generate_time as commentGenerateTime', // 评论日期
                'u.id as commentUserId', // 评论人用户ID
                's.id as commentShopId', // 评论人店铺ID
                'CASE WHEN s.id > 0 THEN s.shop_name ELSE u.nickname END as commentNickname', // 评论人昵称
                'CASE WHEN s.id > 0 THEN s.shop_thumb_image ELSE u.thumb_avatar END as commentAvatar', // 评论人头像
            ])
            ->join('user u', 'vc.from_user_id = u.id')
            ->leftJoin('shop s', 'vc.from_shop_id = s.id')
            ->where([
                ['vc.status', '=', 1,],
                ['vc.top_comment_id', '=', 0],
                ['vc.video_id', 'in', $videoIdArr]
            ])
            ->order([
                'vc.like_count' => 'desc',
                'vc.generate_time' => 'desc'
            ]);
        // 该评论是否已经点赞
        if (isset($where['userId']) && $where['userId']) {
            $subQuery->leftJoin('video_action va', "va.action_type = 1 AND va.user_id = {$where['userId']} AND va.comment_id = vc.id AND va.status = 1")
                ->field('IF(va.id, 1, 0) as commentIsLike');
        } else {
            $subQuery->field('0 as commentIsLike');
        }

        $subQuery = $subQuery->buildSql();
        // 获取点赞数最多的评论
        $query = $this->table($subQuery)
            ->alias('subQuery')
            ->group('subQuery.videoId');

        return $query->select();
    }

    /**
     * 获取店铺视频列表
     *
     * @param $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopVideoList($where)
    {
        $query = $this->alias('v')
            ->join('shop s', 'v.shop_id = s.id')
            ->field([
                'v.id as videoId', // 视频ID
                'v.user_id as videoUserId', // 视频用户ID
                'v.title as videoTitle', // 视频标题
                'v.cover_url as coverUrl', // 封面
                'v.video_url as videoUrl', // 视频地址
                'v.like_count as likeCount', // 点赞数量
                'v.comment_count as commentCount', // 评论数量
                'v.share_count as shareCount', // 转发数量
                's.id as shopId', // 店铺ID
                's.shop_name as shopName', // 店铺ID
                's.shop_image as shopImage', // 店铺图片
                's.shop_thumb_image as shopThumbImage', // 店铺缩略图
                's.setting', // 关联配置
                's.qq', // qq
                's.wechat', // 微信
                's.shop_address as shopAddress', // 店铺地址
                'v.video_width as videoWidth', // 视频宽度
                'v.video_height as videoHeight', // 视频高度
                'v.is_top as isTop', // 是否置顶
            ])
            ->where([
                ['v.type', '=', 1],
                ['v.status', '=', 1],
                ['v.visible', '=', 1],
                ['v.shop_id', '=', $where['shopId']],
                ['s.account_status', '=', 1],
                ['s.online_status', '=', 1],
            ])
            ->order([
                'v.is_top' => 'desc',
                'v.id' => 'desc',
            ])
            ->limit($where['page'] * $where['limit'], $where['limit']);
        // 获取是否点赞过，是否关注过
        if (isset($where['userId']) && $where['userId']) {
            $query->leftJoin('follow_relation fr', "fr.rel_type = 1 and fr.from_user_id = {$where['userId']} and fr.to_shop_id = s.id")
                ->leftJoin('video_action va', "va.action_type = 1 and va.video_id = v.id and va.user_id = {$where['userId']} AND va.status = 1")
                ->field([
                    'IF(fr.id, 1, 0) as isFollow', // 是否关注
                    'IF(va.id, 1, 0) as isLike', // 是否点赞
                ]);
        }
        $query->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select();
    }

    /**
     * 获取视频详情
     *
     * @param array $where
     * @return VideoModel|array|null
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getVideoDetail(array $where = [])
    {
        $query = $this->alias('v')
            ->field([
                'v.id',
                'v.id AS videoId',
                'v.user_id AS videoUserId',
                'v.shop_id AS videoShopId',
                'v.title',
                'v.type',
                'v.cover_url AS coverUrl',
                'v.video_url AS videoUrl',
                'v.video_width AS videoWidth',
                'v.video_height AS videoHeight',
                'v.like_count AS likeCount',
                'v.comment_count AS commentCount',
                'v.share_count AS shareCount',
                'v.generate_time AS generateTime',
                'u.nickname',
                'u.avatar',
                'u.thumb_avatar AS thumbAvatar',
                'u.gender',
                'u.age',
                'u.location',
                's.id AS shopId',
                's.shop_name AS shopName',
                's.shop_image AS shopImage',
                's.shop_thumb_image AS shopThumbImage',
                'rs.id AS relatedShopId',
                'rs.phone AS relatedShopPhone',
                'rs.shop_name AS relatedShopName',
                'rs.shop_image AS relatedShopImage',
                'rs.shop_thumb_image AS relatedShopThumbImage',
                'rs.shop_address AS relatedShopAddress',
                'rs.longitude AS relatedShopLongitude',
                'rs.latitude AS relatedShopLatitude',
                'rs.qq AS relatedShopQq',
                'rs.wechat AS relatedShopWechat',
                'rs.setting AS relatedShopSetting',
                'rs.pay_setting_type AS related_pay_setting_type',
                'v.audit_status AS auditStatus',
            ])
            ->join('user u', 'u.id = v.user_id AND u.account_status = 1')
            ->leftJoin('shop s', 's.id = v.shop_id AND s.online_status = 1')
            ->leftJoin('shop rs', 'rs.id = v.relation_shop_id AND rs.online_status = 1')
            ->where('v.id', $where['videoId'])
            ->where('v.status', 1)
            ->where('v.visible', 1);

        if (isset($where['userId']) && $where['userId']) {
            $query->field([
                'IF(fr.id, 1, 0) AS isFollow',
                'IF(va.id, 1, 0) AS isLike',
            ])
                ->leftJoin('follow_relation fr', "fr.from_user_id = {$where['userId']} AND fr.rel_type = 1 AND IF(v.shop_id > 0, v.shop_id = fr.to_shop_id, v.user_id = fr.to_user_id)")
                ->leftJoin('video_action va', "va.action_type = 1 AND va.video_id = v.id AND va.user_id = {$where['userId']} AND va.status = 1");
        }

        return $query->find();
    }

    /**
     * 获取我的视频列表.
     *
     * @param array $where
     *
     * @return array
     * @throws \Exception
     */
    public function getMyVideoList($where)
    {
        $query = $this->alias('v')
            ->join('user u', 'v.user_id = u.id')
            ->leftJoin('shop s', 's.user_id = u.id and s.account_status = 1 and s.online_status = 1')
            ->leftJoin('follow_relation fr', "fr.rel_type = 1 and fr.from_user_id = '{$where['userId']}' and 
            (IF(v.shop_id, fr.to_shop_id = s.id, fr.to_user_id = u.id))")
            ->leftJoin('video_action va', "va.action_type = 1 and va.video_id = v.id and va.status = 1 and va.user_id = '{$where['userId']}'")
            ->field([
                'v.id as videoId', // 视频ID
                'v.title as videoTitle', // 视频标题
                'v.user_id as videoUserId', // 发视频用户ID
                'v.cover_url as coverUrl', // 封面
                'v.video_url as videoUrl', // 视频地址
                'v.video_width AS videoWidth', // 视频宽度
                'v.video_height AS videoHeight', // 视频高度
                'v.like_count as likeCount', // 点赞数量
                'v.comment_count as commentCount', // 评论数量
                'v.share_count as shareCount', // 转发数量
                's.id as shopId', // 店铺ID
                's.shop_image as shopImage', // 店铺图片
                's.shop_thumb_image as shopThumbImage', // 店铺缩略图
                'IF(fr.id, 1, 0) as isFollow', // 是否关注
                'IF(va.id, 1, 0) as isLike', // 是否点赞
                'v.is_top as isTop', // 是否置顶
                'if(v.shop_id, s.shop_image, u.thumb_avatar) as avatar',
                'if(v.shop_id, s.shop_name, u.nickname) as nickname',
                'if(v.shop_id, 2, 1) as videoType'
            ])
            ->where([
                'v.user_id' => $where['userId'],
                'v.shop_id' => 0,
                'v.status' => 1,
                //'v.visible' => 1,
                'v.type' => 1,
            ])
            ->order([
                'v.is_top' => 'desc',
                'v.id' => 'desc'
            ])
            ->group('v.id');
        // 统计总数量
        $_query = clone $query;
        $totalCount = $_query->select()->count();
        // 分页
        $videoList = $query->limit($where['page'] * $where['limit'], $where['limit'])->select();
        return [
            'totalCount' => $totalCount,
            'videoList' => $videoList
        ];
    }

    /**
     * 获取用户的视频列表.
     *
     * @param array $where
     *
     * @return array
     * @throws \Exception
     */
    public function getUserVideoList($where)
    {
        // 获取视频列表
        $query = $this->alias('v')
            ->field([
                'v.id as videoId', // 视频ID
                'v.title as videoTitle', // 视频标题
                'v.user_id as videoUserId', // 发视频用户ID
                'v.cover_url as coverUrl', // 封面
                'v.video_url as videoUrl', // 视频地址
                'v.video_width AS videoWidth', // 视频宽度
                'v.video_height AS videoHeight', // 视频高度
                'v.like_count as likeCount', // 点赞数量
                'v.comment_count as commentCount', // 评论数量
                'v.share_count as shareCount', // 转发数量
                's.id as shopId', // 店铺ID
                's.shop_image as shopImage', // 店铺图片
                's.shop_thumb_image as shopThumbImage', // 店铺缩略图
                'v.is_top as isTop', // 是否置顶
                'if(v.shop_id, s.shop_image, u.thumb_avatar) as avatar',
                'if(v.shop_id, s.shop_name, u.nickname) as nickname',
                'if(v.shop_id, 2, 1) as videoType',
                'v.location', // 发布视频的地理位置
                'v.generate_time as generateTime',
            ])
            ->join('user u', '(v.user_id = u.id and u.account_status = 1)')
            ->leftJoin('shop s', '(s.user_id = u.id and s.account_status = 1 and s.online_status = 1)');
        // 当用户是登录状态下判断是否点赞过视频
        if ($where['loginUserId']) {
            $joinCondition = "va.action_type = 1 and va.video_id = v.id and va.status = 1 and va.user_id = '{$where['loginUserId']}'";
            $query->field('IF(va.id, 1, 0) as isLike')
                ->leftJoin('video_action va', $joinCondition);
        } else {
            $query->field('0 as isLike');
        }
        // 当用户是登录状态下判断是否关注
        if (isset($where['loginUserId']) && $where['loginUserId']) {
            $joinCondition = "(fr.rel_type = 1 and fr.from_user_id = '{$where['loginUserId']}' and IF(v.shop_id, fr.to_shop_id = s.id, fr.to_user_id = u.id))";
            $query->leftJoin('follow_relation fr', $joinCondition)
                ->field('IF(fr.id, 1, 0) as isFollow'); // 是否关注
        } else {
            $query->field('0 as isFollow');
        }
        // 条件筛选
        $query->where([
            'v.user_id' => $where['userId'],
            'v.shop_id' => 0,
            'v.status' => 1,
            //'v.visible' => 1,
            'v.type' => 1,
        ])
            ->order([
                'v.is_top' => 'desc',
                'v.id' => 'desc'
            ])
            ->group('v.id');
        // 统计总数量
        $_query = clone $query;
        $totalCount = $_query->select()->count();
        // 分页
        $videoList = $query->limit($where['page'] * $where['limit'], $where['limit'])->select();
        return [
            'totalCount' => $totalCount,
            'videoList' => $videoList
        ];
    }

    /**
     * 获取推荐视频
     *
     * @param array $where
     *
     * @return array
     */
    public function getRecommendVideo($where)
    {
        $query = $this->alias('v')
            ->where([
                'v.is_recommend' => 1,
                'v.status' => 1,
                'v.visible' => 1
            ])
            ->order('v.id', 'desc')
            ->limit($where['limit']);
        return $query->select()->toArray();
    }

    /**
     * 获取视频的点赞数最多的一条评论信息.
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \Exception
     */
    public function getVideoCommentInfo($where)
    {
        // 评论按点赞数量排序
        $subQuery = $this->name('video_comment')
            ->alias('vc')
            ->distinct(true)
            ->field([
                'vc.video_id as videoId', // 视频ID
                'vc.id as commentId', // 评论ID
                'vc.like_count as commentLikeCount', // 评论点赞数量
                'vc.content as commentContent', // 评论内容
                'vc.generate_time as commentGenerateTime', // 评论日期
                'u.id as commentUserId', // 评论人用户ID
                's.id as commentShopId', // 评论人店铺ID
                'IF(s.id > 0, s.shop_name, u.nickname) as commentNickname', // 评论人昵称
                'IF(s.id > 0, s.shop_thumb_image, u.thumb_avatar) as commentAvatar', // 评论人头像
            ])
            ->join('user u', 'vc.from_user_id = u.id')
            ->leftJoin('shop s', 'vc.from_shop_id = s.id')
            ->where([
                ['vc.video_id', 'in', $where['videoIdArr']],
                ['vc.status', '=', 1,],
                ['vc.top_comment_id', '=', 0]
            ])
            ->order([
                'vc.like_count' => 'desc',
                'vc.generate_time' => 'desc'
            ]);
        // 该评论是否已经点赞
        if (isset($where['loginUserId']) && $where['loginUserId']) {
            $joinCondition = "va.action_type = 1 AND va.user_id = '{$where['loginUserId']}' AND va.comment_id = vc.id AND va.status = 1";
            $subQuery->leftJoin('video_action va', $joinCondition)
                ->field('IF(va.id, 1, 0) as commentIsLike');
        } else {
            $subQuery->field('0 as commentIsLike');
        }
        $table = $subQuery->buildSql();
        // 获取点赞数最多的评论
        $query = $this->table($table)
            ->alias('subQuery')
            ->field([
                'subQuery.videoId',
                'subQuery.commentId',
                'subQuery.commentContent',
                'subQuery.commentGenerateTime',
                'subQuery.commentLikeCount',
                'subQuery.commentShopId',
                'subQuery.commentUserId',
                'subQuery.commentNickname',
                'subQuery.commentAvatar',
                'subQuery.commentIsLike',
            ])
            ->group('subQuery.videoId');
        return $query->select();
    }
}
