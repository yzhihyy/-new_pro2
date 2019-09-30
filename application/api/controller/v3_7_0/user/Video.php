<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/29
 * Time: 14:30
 */

namespace app\api\controller\v3_7_0\user;

use app\api\logic\v3_7_0\user\VideoLogic;
use app\api\Presenter;
use app\api\validate\v3_7_0\AnchorVideoValidate;

class Video extends Presenter
{
    public function anchorVideoDetail()
    {
        $params = $this->request->get();
        $result = $this->validate($params, AnchorVideoValidate::class.'.AnchorVideo');
        if ($result !== true) {
            return apiError($result);
        }

        $detail = VideoLogic::getAnchorVideoDetail($params);
        if(!empty($detail)){
            $detail['avatar'] = getImgWithDomain($detail['avatar']);
        }
        $info = [
            'info' => $detail
        ];
        return apiSuccess($info);
    }
}