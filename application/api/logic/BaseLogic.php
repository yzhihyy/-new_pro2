<?php

namespace app\api\logic;

use app\api\Presenter;

class BaseLogic extends Presenter
{
    /**
     * logic response.
     *
     * @param array $msg
     * @param array $data
     *
     * @return array
     */
    public function logicResponse($msg = [], $data = [])
    {
        return compact('msg', 'data');
    }
}
