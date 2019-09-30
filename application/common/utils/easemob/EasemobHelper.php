<?php

namespace app\common\utils\easemob;

use Exception;

class EasemobHelper
{
    /**
     * @var string
     */
    private $appKey = '';

    /**
     * @var string
     */
    private $orgName = '';

    /**
     * @var string
     */
    private $appName = '';

    /**
     * @var string
     */
    private $clientId = '';

    /**
     * @var string
     */
    private $clientSecret = '';

    /**
     * @var string
     */
    private $logPath;

    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var string
     */
    private $url = 'https://a1.easemob.com/';

    /**
     * EasemobHelper constructor.
     */
    public function __construct()
    {
        $config = config('easemob.');
        $this->appKey = $config['app_key'];
        list($orgName, $appName) = explode('#', $config['app_key']);
        $this->orgName = $orgName;
        $this->appName = $appName;
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->logPath = $config['log_path'];
        $this->cachePath = $config['cache_path'];
    }

    /**
     * 获取环信token.
     *
     * @return mixed
     */
    public function getToken()
    {
        try {
            // 接口请求地址
            $url = $this->url . $this->orgName . '/' . $this->appName . '/token';
            // 获取缓存
            $tokenCacheKey = 'easemob_token';
            $tokenValue = cache($tokenCacheKey);
            if (empty($tokenValue)) {
                $query = [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ];
                $header = [
                    'Content-Type: application/json',
                ];
                $response = $this->curlRequest($url, json_encode($query), $header, 'post');
                $result = json_decode($response, true);
                if (!empty($result['error'])) {
                    throw new Exception($response);
                }
                if (empty($result['access_token'])) {
                    throw new Exception('接口请求错误,未获取到token');
                }
                // 记录请求日志
                $this->queryLog('环信[获取token]接口返回信息: ' . $response);
                // 缓存token
                $token = $result['access_token'];
                $time = $result['expires_in'];
                cache($tokenCacheKey, $token, $time);
            } else {
                $token = $tokenValue;
            }
            return $token;
        } catch (Exception $e) {
            $logContent = '环信[获取token]接口错误: ' . $e->getMessage();
            $this->errorLog($logContent);
            return false;
        }
    }

    /**
     * 单用户注册(开放).
     *
     * @param string $username
     * @param string $password
     * @param string $nickname
     *
     * @return mixed
     */
    public function openSingleRegister($username, $password, $nickname = '')
    {
        //接口请求地址
        $url = $this->url . $this->orgName . '/' . $this->appName . '/users';
        try {
            $query = [
                'username' => $username,
                'password' => $password,
                'nickname' => $nickname,
            ];
            $header = [
                'Content-Type: application/json',
            ];
            $response = $this->curlRequest($url, json_encode($query), $header, 'post');
            $result = json_decode($response, true);
            if (!empty($result['error'])) {
                throw new Exception($response);
            }
            if (empty($result) || empty($result['entities'])) {
                throw new Exception('接口请求错误,未成功注册用户');
            }
            // 记录请求日志
            $this->queryLog('环信[单用户开放注册]接口返回信息: ' . $response);
            return $result['entities'][0];
        } catch (Exception $e) {
            $logContent = '环信[单用户开放注册]接口错误: ' . $e->getMessage();
            $this->errorLog($logContent);
            return false;
        }
    }

    /**
     * 单用户注册(授权).
     *
     * @param string $username
     * @param string $password
     * @param string $nickname
     *
     * @return mixed
     */
    public function authSingleRegister($username, $password, $nickname = '')
    {
        $url = $this->url . $this->orgName . '/' . $this->appName . '/users'; // 接口请求地址
        try {
            $query = [
                'username' => $username,
                'password' => $password,
                'nickname' => $nickname,
            ];
            $header = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->getToken(),
            ];
            $response = $this->curlRequest($url, json_encode($query), $header, 'post');
            $result = json_decode($response, true);
            if (!empty($result['error'])) {
                throw new Exception($response);
            }
            if (empty($result) || empty($result['entities'])) {
                throw new Exception('接口请求错误,未成功注册用户');
            }
            // 记录请求日志
            $this->queryLog('环信[单用户授权注册]接口返回信息: ' . $response);
            return $result['entities'][0];
        } catch (Exception $e) {
            $logContent = '环信[单用户授权注册]接口错误: ' . $e->getMessage();
            $this->errorLog($logContent);
            return false;
        }
    }

    /**
     * 获取单个用户.
     *
     * @param string $username
     *
     * @return mixed
     */
    public function getSingleRegister($username)
    {
        $url = $this->url . $this->orgName . '/' . $this->appName . '/users/' . $username; // 接口请求地址
        try {
            $query = [];
            $header = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->getToken(),
            ];
            $response = $this->curlRequest($url, json_encode($query), $header, 'post');
            $result = json_decode($response, true);
            if (!empty($result['error'])) {
                throw new Exception($response);
            }
            if (empty($result) || empty($result['entities'])) {
                throw new Exception('接口请求错误,未获取到用户');
            }
            // 记录请求日志
            $this->queryLog('环信[获取当个用户]接口返回信息: ' . $response);
            return $result['entities'][0];
        } catch (Exception $e) {
            $logContent = '环信[获取当个用户]接口错误: ' . $e->getMessage();
            $this->errorLog($logContent);
            return false;
        }
    }

    /**
     * 删除单个用户.
     *
     * @param string $username
     *
     * @return mixed
     */
    public function deleteSingleUser($username)
    {
        $url = $this->url . $this->orgName . '/' . $this->appName . '/users/' . $username; // 接口请求地址
        try {
            $query = [];
            $header = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->getToken(),
            ];
            $response = $this->curlRequest($url, json_encode($query), $header, 'delete');
            $result = json_decode($response, true);
            if (!empty($result['error'])) {
                throw new Exception($response);
            }
            if (empty($result) || empty($result['entities'])) {
                throw new Exception('接口请求错误');
            }
            // 记录请求日志
            $this->queryLog('环信[删除单个用户]接口返回信息: ' . $response);
            return $result['entities'][0];
        } catch (Exception $e) {
            $logContent = '环信[删除单个用户]接口错误: ' . $e->getMessage();
            $this->errorLog($logContent);
            return false;
        }
    }

    /**
     * 重置IM用户密码
     *
     * @param string $username
     * @param string $newPassword
     *
     * @return mixed
     */
    public function resetPassword($username, $newPassword)
    {
        $url = $this->url . $this->orgName . '/' . $this->appName . '/users/' . $username . '/password'; // 接口请求地址
        try {
            $query = [
                'newpassword' => $newPassword,
            ];
            $header = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->getToken(),
            ];
            $response = $this->curlRequest($url, json_encode($query), $header, 'put');
            $result = json_decode($response, true);
            if (!empty($result['error'])) {
                throw new Exception($response);
            }
            // 记录请求日志
            $this->queryLog('环信[重置IM用户密码]接口返回信息: ' . $response);
            return $result;
        } catch (Exception $e) {
            $logContent = '环信[重置 IM 用户密码]接口错误: ' . $e->getMessage();
            $this->errorLog($logContent);
            return false;
        }
    }

    /**
     * 设置推送消息显示昵称.
     *
     * @param $username
     * @param $nickname
     *
     * @return mixed
     */
    public function editNickname($username, $nickname)
    {
        $url = $this->url . $this->orgName . '/' . $this->appName . '/users/' . $username;
        try {
            $query = [
                'nickname' => $nickname,
            ];
            $header = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->getToken(),
            ];
            $response = $this->curlRequest($url, json_encode($query), $header, 'put');
            $result = json_decode($response, true);
            if (!empty($result['error'])) {
                throw new Exception($response);
            }
            if (empty($result) || empty($result['entities'])) {
                throw new Exception('接口请求错误,未成功设置推送消息显示昵称');
            }
            // 记录请求日志
            $this->queryLog('环信[设置推送消息显示昵称]接口返回信息: ' . $response);
            return $result['entities'][0];
        } catch (Exception $e) {
            $logContent = '环信[设置推送消息显示昵称]接口错误: ' . $e->getMessage();
            $this->errorLog($logContent);
            return false;
        }
    }

    /**
     * 添加好友.
     *
     * @param $ownerUsername
     * @param $friendUsername
     *
     * @return mixed
     */
    public function addFriend($ownerUsername, $friendUsername)
    {
        $url = $this->url . $this->orgName . '/' . $this->appName . '/users/' . $ownerUsername . '/contacts/users/' . $friendUsername;
        try {
            $query = [];
            $header = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->getToken(),
            ];
            $response = $this->curlRequest($url, json_encode($query), $header, 'post');
            $result = json_decode($response, true);
            if (!empty($result['error'])) {
                throw new Exception($response);
            }
            if (empty($result) || empty($result['entities'])) {
                throw new Exception('接口请求错误,未成功添加好友');
            }
            // 记录请求日志
            $this->queryLog('环信[添加好友]接口返回信息: ' . $response);
            return $result['entities'][0];
        } catch (Exception $e) {
            $logContent = '环信[添加好友]接口错误: ' . $e->getMessage();
            $this->errorLog($logContent);
            return false;
        }
    }

    /**
     * 删除好友.
     *
     * @param $ownerUsername
     * @param $friendUsername
     *
     * @return mixed
     */
    public function deleteFriend($ownerUsername, $friendUsername)
    {
        $url = $this->url . $this->orgName . '/' . $this->appName . '/users/' . $ownerUsername . '/contacts/users/' . $friendUsername;
        try {
            $query = [];
            $header = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->getToken(),
            ];
            $response = $this->curlRequest($url, json_encode($query), $header, 'delete');
            $result = json_decode($response, true);
            if (!empty($result['error'])) {
                throw new Exception($result['error']);
            }
            if (empty($result) || empty($result['entities'])) {
                throw new Exception('接口请求错误,未成功删除好友');
            }
            // 记录请求日志
            $this->queryLog('环信[删除好友]接口返回信息: ' . $response);
            return $result['entities'][0];
        } catch (Exception $e) {
            $logContent = '环信[删除好友]接口错误: ' . $e->getMessage();
            $this->errorLog($logContent);
            return false;
        }
    }

    /**
     * 好友列表.
     *
     * @param $ownerUsername
     *
     * @return mixed
     */
    public function friendList($ownerUsername)
    {
        $url = $this->url . $this->orgName . '/' . $this->appName . '/users/' . $ownerUsername . '/contacts/users';
        try {
            $query = [];
            $header = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->getToken(),
            ];
            $response = $this->curlRequest($url, json_encode($query), $header, 'get');
            $result = json_decode($response, true);
            if (!empty($result['error'])) {
                throw new Exception($response);
            }
            // 记录请求日志
            $this->queryLog('环信[好友列表]接口返回信息: ' . $response);
            return $result['data'];
        } catch (Exception $e) {
            $logContent = '环信[好友列表]接口错误: ' . $e->getMessage();
            $this->errorLog($logContent);
            return false;
        }
    }

    /**
     * 发送文本消息.
     *
     * @param $toUsers array 接收人数组形式,如['aUser', 'bUser']
     * @param $message string 消息
     * @param $ownerUsername string 发送人
     * @param null $ext mixed 扩展属性 字符串或数组
     *
     * @return mixed ['aUser'=> 'success', 'bUser'=> 'success'] 发送成功对象
     */
    public function sendTxtMessage($toUsers, $message, $ownerUsername = 'admin', $ext = null)
    {
        $url = $this->url . $this->orgName . '/' . $this->appName . '/messages';
        try {
            $query = [
                'target_type' => 'users', //发送给用户
                'target' => $toUsers, // 发给谁 ['aUser', 'bUser']
                'msg' => [
                    'type' => 'txt', // 消息类型 文本消息
                    'msg' => $message, // 消息内容
                ],
                'from' => $ownerUsername, //发送人(谁发的)
            ];
            if (!empty($ext)) {
                $query['ext'] = $ext; // 扩展属性 可传字符串或数组
            }
            $header = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->getToken(),
            ];
            $response = $this->curlRequest($url, json_encode($query), $header, 'post');
            $result = json_decode($response, true);
            if (!empty($result['error'])) {
                throw new Exception($response);
            }
            // 记录请求日志
            $this->queryLog('环信[发送文本消息]接口返回信息: ' . $response);
            return $result['data'];
        } catch (Exception $e) {
            $logContent = '环信[发送文本消息]接口错误: ' . $e->getMessage();
            $this->errorLog($logContent);
            return false;
        }
    }

    /**
     * 记录环信请求日志.
     *
     * @param $logContent
     */
    protected function queryLog($logContent)
    {
        generateCustomLog($logContent, $this->logPath, 'info');
    }

    /**
     * 记录环信错误日志.
     *
     * @param $logContent
     */
    protected function errorLog($logContent)
    {
        generateCustomLog($logContent, $this->logPath, 'error');
    }

    /**
     * 环信curl请求
     *
     * @param $url $请求地址
     * @param $data $请求数据
     * @param $header $请求头
     * @param $method $请求类型
     *
     * @return mixed
     */
    private function curlRequest($url, $data, $header, $method)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); // 定义请求地址
        if ('put' == $method) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        } elseif ('post' == $method) {
            curl_setopt($ch, CURLOPT_POST, 1);
        } elseif ('get' == $method) {
            curl_setopt($ch, CURLOPT_POST, 0);
        } elseif ('delete' == $method) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        curl_setopt($ch, CURLOPT_HEADER, 0); // 定义是否显示状态头 1：显示 ； 0：不显示
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); // 定义header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 定义是否直接输出返回流
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // 定义提交的数据
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $res = curl_exec($ch);
        curl_close($ch); // 关闭
        return $res;
    }
}
