<?php

namespace think\view\driver;

use think\Container;

/**
 * Twig 模板引擎
 *
 * @author linyangbin
 *
 * @package think\view\driver
 */
class Twig
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    private $templatePath;

    private $options;

    public function __construct()
    {
        $this->initTwig();
        $this->addFunctions();
        $this->addFilters();
    }

    /**
     * 初始化Twig
     */
    private function initTwig()
    {
        $this->setTemplatePath();
        $this->setOptions();
        $loader = new \Twig_Loader_Filesystem($this->templatePath);
        $twig = new \Twig_Environment($loader, $this->options);
        $this->twig = $twig;
    }

    /**
     * 设置模板目录
     *
     * @return $this
     */
    private function setTemplatePath()
    {
        $appPath = Container::get('app')->getAppPath();
        $moduleName = Container::get('request')->module();
        $path = $appPath . $moduleName . DIRECTORY_SEPARATOR . 'view';
        $this->templatePath = $path;
        return $this;
    }

    /**
     * Twig配置
     *
     * @return $this
     */
    private function setOptions()
    {
        /**
         * @var \think\App $app
         */
        $app = Container::get('app');
        $runtimePath = $app->getRuntimePath();
        $isDebug = $app->isDebug();
        $options = [];
        $options['debug'] = $isDebug;
        if (!$isDebug) {
            $options['cache'] = $runtimePath . '/cache/twig';
        }
        $this->options = $options;
        return $this;
    }

    /**
     * 渲染模板
     *
     * @param $template
     * @param array $data
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function fetch($template, $data = [])
    {
        $template = $this->parseTemplate($template);
        $this->twig->display($template, $data);
    }

    /**
     * 解析模板位置
     *
     * @param $template
     * @return string
     */
    private function parseTemplate($template)
    {
        if (empty($template)) {
            $request = Container::get('request');
            $controller = $request->controller();
            $action = $request->action(true);
            $template = DIRECTORY_SEPARATOR . lcfirst($controller) . DIRECTORY_SEPARATOR . $action . '.twig';
        }
        return $template;
    }

    /**
     * Registers Functions
     */
    private function addFunctions()
    {
        // url
        $function = new \Twig_SimpleFunction('url', function ($url = '', $vars = '', $suffix = true, $domain = false) {
            return url($url, $vars, $suffix, $domain);
        });
        $this->twig->addFunction($function);
        // config
        $function = new \Twig_SimpleFunction('config', function ($name = '', $value = null) {
            return config($name, $value);
        });
        $this->twig->addFunction($function);
        // asset
        $function = new \Twig_SimpleFunction('asset', function ($path) {
            return $path;
        });
        $this->twig->addFunction($function);
        // widget
        $function = new \Twig_SimpleFunction('widget', function ($name, $data = []) {
            return widget($name, $data);
        });
        $this->twig->addFunction($function);
    }

    /**
     * Registers Filters
     */
    private function addFilters()
    {
        $filter = new \Twig_SimpleFilter('status2Text', function ($value) {
            return $value ? '是' : '否';
        });
        $this->twig->addFilter($filter);
    }
}