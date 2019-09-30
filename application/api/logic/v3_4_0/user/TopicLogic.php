<?php

namespace app\api\logic\v3_4_0\user;

use app\api\logic\BaseLogic;
use app\api\logic\v3_3_0\user\VideoLogic;
use app\api\model\v3_0_0\TagModel;
use app\api\model\v3_3_0\{VideoModel};
use app\api\model\v3_4_0\TopicModel;

class TopicLogic extends BaseLogic
{
    /**
     * 获取首页话题列表
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getHomeTopicList($params)
    {
        /** @var TopicModel $topicModel */
        $topicModel = model(TopicModel::class);

        // 获取话题
        $condition = array_merge($this->getReviewDisplayRule(), [
            'limit' => 12,
            'page' => $params['page'],
            'cityId' => $params['cityId'],
        ]);
        $getTopicList = $topicModel->getTopicList($condition);

        // 获取第一个视频ID
        $videoIdFun = function ($videoIds) {
            $index = strpos($videoIds, ',');
            return $index === false ? $videoIds : substr($videoIds, 0, $index);
        };
        // 话题列表
        $topicList = [];
        // 临时数组
        foreach ($getTopicList as $item) {
            $topicList[] = [
                'topic_id' => $item['topicId'],
                'video_id' => $videoIdFun($item['videoIds']),
                'cover_url' => $item['coverUrl'],
                'video_width' => $item['topicWidth'],
                'video_height' => $item['topicHeight'],
            ];
        }

        if (!empty($topicList)) {
            // 获取视频列表
            /** @var VideoModel $videoModel */
            $videoModel = model(VideoModel::class);
            $videoList = $videoModel->getHomeVideoList([
                'type' => 1,
                'videoIds' => array_column($topicList, 'video_id')
            ]);
            $videoList = array_column($videoList->toArray(), null, 'videoId');
            // 数据处理
            $videoLogic = new VideoLogic();
            foreach ($topicList as &$topic) {
                $video = $videoList[$topic['video_id']] ?? [];
                if (!empty($video)) {
                    $topicCoverUrl = $topic['cover_url'];
                    $topicWidth = $topic['video_width'];
                    $topicHeight = $topic['video_height'];
                    unset($topic['video_id']);
                    $topic = array_merge($topic, $videoLogic->transformVideoData($video));
                    if(!empty($topicCoverUrl) && !empty($topicWidth) && !empty($topicHeight)){//有话题封面用话题封面
                        $topic['cover_url'] = getImgWithDomain($topicCoverUrl);
                        $topic['video_width'] = $topicWidth;
                        $topic['video_height'] = $topicHeight;
                    }
                }
            }
        }
        return $topicList;
    }

    /**
     * 获取显示规则
     *
     * @return array
     */
    public function getReviewDisplayRule()
    {
        $result = [];

        // TODO APP 审核时的视频显示规则：统一显示指定标签下的视频
        $reviewDisplayRule = config('parameters.home_video_display_rule_for_review');
        if ($reviewDisplayRule['enable_flag']) {
            $platform = $this->request->header('platform');
            $version = $this->request->header('version');
            if (in_array($platform, $reviewDisplayRule['platform']) && version_compare($version, $reviewDisplayRule['version']) == 0) {
                // 获取指定标签的ID
                /** @var TagModel $tagModel */
                $tagModel = model(TagModel::class);
                $tagIdArray = $tagModel->where('tag_type', 1)->whereIn('tag_name', $reviewDisplayRule['tagNames'])->column('id');
                $result['tagIds'] = implode(',', array_values($tagIdArray));
            }
        }

        return $result;
    }
}
