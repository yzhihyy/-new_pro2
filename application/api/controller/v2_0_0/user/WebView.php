<?php

namespace app\api\controller\v2_0_0\user;

use app\api\Presenter;

class WebView extends Presenter
{
    /**
     * 用户协议
     *
     * @return \think\response\View
     */
    public function userProtocol()
    {
        return view('/v2_0_0/webView/userProtocol.twig');
    }

    /**
     * 商家协议
     *
     * @return \think\response\View
     */
    public function merchantProtocol()
    {
        return view('/v2_0_0/webView/merchantProtocol.twig');
    }

    /**
     * 关于我们
     *
     * @return \think\response\View
     */
    public function aboutUs()
    {
        $version = $this->request->header('version', '3.0.0');
        $this->assign('version', $version);
        return view('/v2_0_0/webView/aboutUs.twig');
    }

    /**
     * 免单说明
     *
     * @return \think\response\View
     */
    public function freeExplain()
    {
        return view('/v2_0_0/webView/freeExplain.twig');
    }

    /**
     * 广告详情
     *
     * @return \think\response\View
     */
    public function bannerDetail()
    {
        // 图片路径
        $imgPath = input('imgPath');
        $data = [
            'imgPath' => $imgPath,
            'appName' => config('app_name'),
            'resourcesDomain' => config('resources_domain')
        ];
        return view('/v2_0_0/webView/bannerDetail.twig', $data);
    }
}
