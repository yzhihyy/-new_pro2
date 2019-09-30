<?php

namespace app\common\command;

use think\console\{
    Command, Input, Output
};
use app\api\model\v3_0_0\{
    ThemeActivityModel, ThemeActivityShopModel
};
use app\common\utils\jPush\JpushHelper;

class ThemeActivityPush extends Command
{
    /**
     * 配置指令
     */
    protected function configure()
    {
        $this->setName('themeActivityPush')
            ->setDescription('Theme Activity Push');
    }

    /**
     * 执行指令
     *
     * @param Input  $input
     * @param Output $output
     *
     * @throws \Exception
     */
    protected function execute(Input $input, Output $output)
    {
        /** @var ThemeActivityModel $themeActivityModel */
        $themeActivityModel = model(ThemeActivityModel::class);
        // 获取进行中的主题活动
        $themeActivityList = $themeActivityModel->list([
            'theme_status' => [2]
        ]);

        if (!empty($themeActivityList)) {
            // 主题活动标题数组
            $themeTitleArr = [];
            foreach ($themeActivityList as $themeActivity) {
                $themeTitleArr[$themeActivity['theme_id']] = $themeActivity['theme_title'];
            }

            // 获取参与主题活动的店铺列表
            /** @var ThemeActivityShopModel $themeActivityShopModel */
            $themeActivityShopModel = model(ThemeActivityShopModel::class);
            $themeActivityShopList = $themeActivityShopModel->getThemeActivityShopList([
                'themeId' => array_keys($themeTitleArr)
            ]);

            if (!empty($themeActivityShopList)) {
                $count = 1;
                foreach ($themeActivityShopList as $index => $shop) {
                    if ($index !== 0 && $shop['themeId'] !== $themeActivityShopList[$index - 1]['themeId']) {
                        $count = 1;
                    }

                    if (!empty($shop['registratioinId'])) {
                        $ranking = num2Ch((string)$count);
                        $themeTitle = $themeTitleArr[$shop['themeId']];
                        $content = sprintf(config('response.msg100'), $themeTitle, $shop['voteCount'], $ranking);
                        JpushHelper::push([$shop['registratioinId']], [
                            'title' => $content,
                            'message' => $content,
                            'extras' => [
                                'push_type' => 'themeActivityType',
                                'theme_id' => $shop['themeId'],
                                'theme_type' => $shop['themeType']
                            ]
                        ]);
                    }

                    $count++;
                }
            }
        }
    }
}
