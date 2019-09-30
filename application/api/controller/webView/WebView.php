<?php

namespace app\api\controller\webView;

use app\api\Presenter;

class WebView extends Presenter
{
    /**
     * 用户协议
     */
    public function userProtocol()
    {
        return $this->fetch('/webView/webView/userProtocol.twig');
    }

    /**
     * 商家协议
     */
    public function merchantProtocol()
    {
        return $this->fetch('/webView/webView/merchantProtocol.twig');
    }

    /**
     * 关于我们
     *
     * @return mixed
     */
    public function aboutUs()
    {
        $version = input('version/s', '1.0.0');
        $this->assign('version', $version);
        return $this->fetch('/webView/webView/aboutUs.twig');
    }

    /**
     * 免单说明
     *
     * @return mixed
     */
    public function freeExplain()
    {
        return redirect('/h5/rule.html');
        //return $this->fetch('/webView/webView/freeExplain.twig');
    }

    /**
     * 广告详情
     *
     * @return mixed
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
        return view('/webView/webView/bannerDetail.twig', $data);
    }
}
