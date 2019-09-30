<?php

namespace app\api;

use app\common\controller\AbstractController;
use app\api\traits\AbstractLogicTrait;
use think\facade\Hook;

class Presenter extends AbstractController
{
    use AbstractLogicTrait;

    public function initialize()
    {
        Hook::listen('before_api_controller_initialize');
        parent::initialize();
        Hook::listen('after_api_controller_initialize');
    }
}
