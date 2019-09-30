<?php

namespace app\common\command;

use app\api\model\v3_0_0\SettingModel;
use app\api\model\v3_4_0\ThemeActivityModel;
use app\api\model\v3_5_0\ThemeArticleModel;
use app\api\model\v3_5_0\VideoModel;
use app\api\model\v3_6_0\AutoAddListModel;
use app\common\utils\date\DateHelper;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class AddViews extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('addViews')
        ->setDescription('Views add');
        // 设置参数

    }

    /**
     * 每天定时随机增加浏览量
     * @param Input $input
     * @param Output $output
     * @return bool|int|null
     */
    protected function execute(Input $input, Output $output)
    {
        try{
            $videoAddViews = model(SettingModel::class)->where('group', '=', 'video_daily_add_view')->value('value');
            $articleAddViews = model(SettingModel::class)->where('group', '=', 'article_daily_add_view')->value('value');
            $themeAddViews = model(SettingModel::class)->where('group', '=', 'theme_daily_add_view')->value('value');
            list($videoAddStart, $videoAddEnd) = explode(',', $videoAddViews);
            list($articleAddStart, $articleAddEnd) = explode(',', $articleAddViews);
            list($themeAddStart, $themeAddEnd) = explode(',', $themeAddViews);

            $videoAdd = mt_rand($videoAddStart, $videoAddEnd);//视频和随记浏览量随机
            $articleAdd = mt_rand($articleAddStart, $articleAddEnd);//文章浏览量随机
            $themeAdd = mt_rand($themeAddStart, $themeAddEnd);//个人主题浏览量随机

            $videoList = $this->videoAddList();//视频列表
            $this->videoAddHandle($videoList, $videoAdd);//视频处理
            $articleList = $this->articleAddList();//文章列表
            $this->articleAddHandle($articleList, $articleAdd);//文章处理
            $themeList = $this->themeAddList();//主题列表
            $this->themeAddHandle($themeList, $themeAdd);//主题处理

            $addAll = [
                ['add_group' => 'video_daily_add_view', 'rand_count' => $videoAdd, 'generate_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s')],
                ['add_group' => 'article_daily_add_view', 'rand_count' => $articleAdd, 'generate_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s')],
                ['add_group' => 'theme_daily_add_view', 'rand_count' => $themeAdd, 'generate_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s')],
            ];
            model(AutoAddListModel::class)->insertAll($addAll);
            $output->writeln('addViews success');

        }catch (\Exception $e){
            $output->writeln('addViews false');
            generateCustomLog('定时增加浏览量异常:'.$e->getMessage());
            return false;
        }

    }

    /**
     * 视频列表
     * @return \Generator
     * @throws
     */
    private function videoAddList()
    {
        $page = 0;
        $model =  model(VideoModel::class);
        while(true) {
            $where = [
                'audit_status' => 1,
                'status' => 1,
                'visible' => 1,
            ];
            $list = $model->field('id')->where($where)->limit($page * 10, 10)->select();//每次取10条
            if(empty($list[0])){
                break;
            }

            $page++;

            yield $list;
        }
    }

    /**
     * 视频列表添加处理
     * @param $list
     * @param $count
     * @throws \think\Exception
     */
    private function videoAddHandle($list, $count)
    {
        foreach($list as $key => $value){
            foreach($value as $v){
                Db::name('video')->where('id', $v['id'])->setInc('play_count', $count);
            }
        }
    }

    /**
     * 获取文章列表
     * @return mixed
     */
    private function articleAddList()
    {
        $model =  model(ThemeArticleModel::class);
        $where = [
            'is_delete' => 0,
            'is_show' => 1,
        ];
        $list = $model->where($where)->field('id')->order('id', 'ASC')->cursor();
        return $list;
    }


    /**
     * 文章添加浏览量处理
     * @param $list
     * @param $count
     * @throws \think\Exception
     */
    private function articleAddHandle($list, $count)
    {
        foreach($list as $value){
            Db::name('theme_article')->where('id', $value['id'])->setInc('views', $count);
        }
    }

    /**
     * 获取文章列表
     * @return mixed
     */
    private function themeAddList()
    {
        $model =  model(ThemeActivityModel::class);
        $where = [
            'delete_status' => 0,
            'theme_status' => [1, 2],
            'theme_type' => 2,
            'is_show' => 1,
        ];
        $list = $model->where($where)->field('id')->order('id', 'ASC')->cursor();
        return $list;
    }


    /**
     * 文章添加浏览量处理
     * @param $list
     * @param $count
     * @throws \think\Exception
     */
    private function themeAddHandle($list, $count)
    {
        foreach($list as $value){
            Db::name('theme_activity')->where('id', $value['id'])->setInc('view_count', $count);
        }
    }
}
