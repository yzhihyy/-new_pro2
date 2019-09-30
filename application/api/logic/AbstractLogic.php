<?php

namespace app\api\logic;


use app\common\traits\RequestTrait;

class AbstractLogic
{
    use RequestTrait;
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
