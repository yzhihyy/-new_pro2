<?php

namespace app\api\validate\v3_0_0;

use think\Validate;

class ThemeValidate extends Validate
{
    // 错误信息
    protected $message = [
        'theme_id.require' => '参数缺失',
        'theme_id.number' => '主题ID错误',
        'shop_id.require' => '参数缺失',
        'shop_id.number' => '店铺ID错误',
        'article_id.require' => '参数缺失',
        'article_id.number' => '文章ID错误',
        'record_id.require' => '参数缺失',
        'record_id.number' => '记录ID错误',
    ];

    /**
     * 报名参加主题
     *
     * @return ThemeValidate
     */
    public function sceneSignUp()
    {
        return $this->append([
            'theme_id' => 'require|number',
        ]);
    }

    /**
     * 参与主题的商家列表
     *
     * @return $this
     */
    public function sceneShopList()
    {
        return $this->append([
            'theme_id' => 'require|number',
        ]);
    }

    /**
     * 主题活动详情
     *
     * @return $this
     */
    public function sceneActivityDetail()
    {
        return $this->append([
            'theme_id' => 'require|number'
        ]);
    }

    /**
     * 主题文章详情
     *
     * @return $this
     */
    public function sceneArticleDetail()
    {
        return $this->append([
            'article_id' => 'require|number',
            'theme_id' => 'number',
            'latitude' => 'float|between:-180,180',
            'longitude' => 'float|between:-180,180',
        ]);
    }

    /**
     * 投票
     *
     * @return $this
     */
    public function sceneVote()
    {
        return $this->append([
            'theme_id' => 'require|number',
            'shop_id' => 'require|number'
        ]);
    }

    /**
     * 领取投票分享红包
     *
     * @return $this
     */
    public function sceneReceiveBonus()
    {
        return $this->append([
            'record_id' => 'require|number',
        ]);
    }

}
