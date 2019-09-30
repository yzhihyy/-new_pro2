<?php

namespace app\api\logic\v3_3_0\user;

use app\api\logic\BaseLogic;

class VideoLogic extends BaseLogic
{
    /**
     * 处理视频数据
     *
     * @param array $videos
     *
     * @return array
     */
    public function transformVideoData(array $videos)
    {
        $transform = function ($video) {
            return [
                'video_id' => $video['videoId'],
                'type' => $video['type'],
                'video_title' => $video['videoTitle'],
                'cover_url' => $video['coverUrl'],
                'video_url' => $video['videoUrl'],
                'video_width' => $video['videoWidth'],
                'video_height' => $video['videoHeight'],
                'play_count' => $video['playCount'],
                'nickname' => $video['nickname'],
                'avatar' => getImgWithDomain($video['avatar'])
            ];
        };

        foreach ($videos as $key => $value) {
            if (is_array($value)) {
                $videos[$key] = $transform($value);
            } else {
                $videos = $transform($videos);
                break;
            }
        }

        return $videos;
    }
}