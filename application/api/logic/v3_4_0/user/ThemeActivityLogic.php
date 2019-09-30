<?php

namespace app\api\logic\v3_4_0\user;

use app\api\logic\BaseLogic;
use app\api\model\v3_0_0\FollowRelationModel;
use app\common\utils\date\DateHelper;
use app\api\model\v3_4_0\{ThemeArticleActionModel,
    ThemeArticleCommentModel,
    ThemeArticleModel,
    ThemeActivityShopModel,
    ThemeActivityModel,
    ThemeVoteRecordModel};

class ThemeActivityLogic extends BaseLogic
{
    /**
     * 主题活动列表
     * @param $params
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
                'theme_type' => $value['theme_type'],
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

                    $v['show_send_sms'] = 0;
                    $v['show_phone'] = 0;
                    $v['show_enter_shop'] = 0;
                    $v['show_address'] = 0;
                    $v['show_wechat'] = 0;
                    $v['show_qq'] = 0;

                    // 平台主题
                    if ($value['theme_type'] == 1) {
                        $v['vote_share_url'] = sprintf(config('h5.platform_theme_article'), $v['theme_id'], $v['article_id']);
                        $setting = json_decode($v['setting'], true);
                        if(!empty($setting)){
                            $v['show_send_sms'] = $setting['show_send_sms'] ?? 0;
                            $v['show_phone'] = $setting['show_phone'] ?? 0;
                            $v['show_enter_shop'] = $setting['show_enter_shop'] ?? 0;
                            $v['show_address'] = $setting['show_address'] ?? 0;
                            $v['show_wechat'] = $setting['show_wechat'] ?? 0;
                            $v['show_qq'] = $setting['show_qq'] ?? 0;
                            $v['show_payment'] = $setting['show_payment'] ?? 0;
                        }

                    } elseif ($value['theme_type'] == 2) { // 个人主题
                        $v['vote_share_url'] = sprintf(config('h5.personal_theme_article'), $v['theme_id'], $v['article_id']);
                    }

                    if ($value['theme_status'] == 3) {
                        $isVote = 2;//已结束
                    }
                    $v['is_vote'] = $isVote;
                    unset($v['priority']);
                    unset($v['theme_id']);
                    unset($v['setting']);
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
        $params['theme_type'] = $theme->theme_type;
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
     * 主题文章评论列表
     *
     * @param int $articleId 文章ID
     * @param int $page 页码
     *
     * @return array
     */
    public function articleCommentList($articleId, $page)
    {
        // 评论列表
        $comments = [];
        $userId = $this->getUserId();
        $commentModel = model(ThemeArticleCommentModel::class);
        // 总评论数量
        $commentCount = $commentModel->where(['article_id' => $articleId, 'status' => 1])->count(1);
        // 获取一级评论列表
        $topComments = $commentModel->getTopLevelCommentList([
            'page' => $page,
            'userId' => $userId,
            'articleId' => $articleId,
            'limit' => config('parameters.page_size_level_2')
        ]);

        foreach ($topComments as $item) {
            $comments[] = [
                'comment_id' => $item['commentId'],
                'like_count' => $item['likeCount'],
                'content' => $item['content'],
                'user_id' => $item['userId'],
                'nickname' => $item['nickname'],
                'avatar' => getImgWithDomain($item['avatar']),
                'is_like' => $item['isLike'],
                'generate_time' => date('m月d日 H:i', strtotime($item['generateTime']))
            ];
        }

        return [
            'comment_count' => $commentCount,
            'comment_list' => $comments
        ];
    }

    /**
     * 主题文章评论点赞
     *
     * @param User $user 用户信息
     * @param int $commentId 评论ID
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function articleCommentLike($user, $commentId)
    {
        $nowTime = date('Y-m-d H:i:s');
        $comment = ThemeArticleCommentModel::where(['id' => $commentId, 'status' => 1])->find();
        if (empty($comment)) {
            return $this->logicResponse(config('response.msg5'));
        }

        // 开启事务
        ThemeArticleCommentModel::startTrans();

        $like = ThemeArticleActionModel::where(['action_type' => 1, 'comment_id' => $comment->id, 'user_id' => $user->id])->find();
        // 有点赞过
        if (!empty($like)) {
            // 已经点赞，则取消点赞
            if ($like->status == 1) {
                $like->status = 2;
                // 减少点赞量
                if ($comment->like_count > 0) {
                    $comment->like_count--;
                    $comment->save();
                }
            }
            // 已取消点赞，则重新点赞
            elseif ($like->status == 2) {
                $like->status = 1;
                $like->generate_time = $nowTime;
                // 增加点赞量
                $comment->like_count++;
                $comment->save();
            }
            $like->save();
        }
        // 没点赞过
        else {
            // 添加点赞
            $like = ThemeArticleActionModel::create([
                'action_type' => 1,
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'status' => 1,
                'generate_time' => $nowTime
            ]);
            // 增加点赞量
            $comment->like_count++;
            $comment->save();
        }

        // 提交事务
        ThemeArticleCommentModel::commit();

        return $this->logicResponse([], [
            'like_count' => $comment->like_count,
            'is_like' => $like->status == 1 ? 1 : 0
        ]);
    }

    /**
     * 主题文章评论
     *
     * @param User $user 用户信息
     * @param array $paramsArray 参数数组
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function articleComment($user, $paramsArray)
    {
        $nowTime = date('Y-m-d H:i:s');
        $article = ThemeArticleModel::where(['id' => $paramsArray['article_id'], 'is_delete' => 0])->find();
        // 文章不存在
        if (!$article) {
            return $this->logicResponse(config('response.msg5'));
        }

        // 评论数据
        $commentData = [
            'article_id' => $article->id,
            'content' => $paramsArray['content'],
            'from_user_id' => $user->id,
            'from_shop_id' => 0,
            'to_user_id' => $article->user_id,
            'to_shop_id' => 0,
            'top_comment_id' => 0,
            'parent_comment_id' => 0,
            'like_count' => 0,
            'status' => 1,
            'generate_time' => $nowTime
        ];

        // 开启事务
        ThemeArticleModel::startTrans();
        $article->comment_count++;
        $article->save();
        // 保存评论
        $comment = ThemeArticleCommentModel::create($commentData);

        // 提交事务
        ThemeArticleModel::commit();

        $response = [
            'from_user_id' => $user->id,
            'from_user_avatar' => getImgWithDomain($user->thumbAvatar),
            'from_user_nickname' => $user->nickname,
            'comment_id' => $comment->id,
            'content' => $comment->content,
            'generate_time' => date('m月d日 H:i')
        ];

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

        $themeArticle = ThemeArticleModel::where(['id' => $paramsArray['article_id'], 'shop_id' => $paramsArray['shop_id'], 'is_delete' => 0])->find();
        // 文章不存在或者被删除
        if (!$themeArticle) {
            return $this->logicResponse(config('response.msg97'));
        }

        // 平台主题
        if ($theme->theme_type == 1) {
            // 在单店铺的投票次数
            $shopVotedCount = $this->shopVotedCount($theme->id, $theme->vote_type, $user->id, $themeArticle->shop_id);
            // 一家店铺永久/当天只能投票一次
            if ($this->isOverSingleLimit($shopVotedCount)) {
                return $this->logicResponse(explode('|', sprintf(config('response.msg87'), '店铺')));
            }
        }
        // 个人主题
        elseif ($theme->theme_type == 2) {
            $articleVotedCount = $this->articleVotedCount($theme->id, $theme->vote_type, $user->id, $themeArticle->id);
            // 一篇文章永久/当天只能投票一次
            if ($this->isOverSingleLimit($articleVotedCount)) {
                return $this->logicResponse(explode('|', sprintf(config('response.msg87'), '文章')));
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
            'article_id' => $themeArticle->id,
            'user_id' => $user->id,
            'shop_id' => $themeArticle->shop_id,
            'receive_status' => 0,
            'generate_time' => date('Y-m-d H:i:s')
        ]);
        // 增加店铺投票数
        ThemeActivityShopModel::where(['theme_id' => $theme->id, 'shop_id' => $themeArticle->shop_id, 'article_id' => $themeArticle->id])->setInc('vote_count');

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
     * 在单店铺或单文章投票次数是否超过上限
     *
     * @param array $votedCount
     *
     * @return bool
     */
    public function isOverSingleLimit($votedCount)
    {
        // 一家店铺或一篇文章 永久/当天 只能投票一次
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
}
