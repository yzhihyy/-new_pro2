<?php

namespace app\api\controller\v3_6_0\user;

use app\api\logic\v3_6_0\user\ContactsLogic;
use app\api\Presenter;
use app\api\validate\v3_0_0\ContactsValidate;
use think\response\Json;

class Contacts extends Presenter
{
    /**
     * 通讯录好友
     *
     * @return Json
     */
    public function contactsRelation()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 获取验证器
            $contactsValidate = validate(ContactsValidate::class);
            // 参数校验
            $validateResult = $contactsValidate->scene('QueryFriendRelationship')->check($paramsArray);
            if (!$validateResult) {
                return apiError($contactsValidate->getError());
            }

            $contactList = json_decode($paramsArray['contact_list'], true);
            if (!is_array($contactList)) {
                return apiError(config('response.msg53'));
            }

            // 手机号校验
            $contactList = array_slice($contactList, 0, config('parameters.contacts_friend_relationship_query_limit'));
            foreach ($contactList as $key => $contact) {
                if (!$contactsValidate->checkRule($contact['phone'], 'mobile')) {
                    unset($contactList[$key]);
                }
            }
            if (empty($contactList)) {
                //return apiError(config('response.msg53'));
                return apiSuccess(['contact_list' => []]);
            }

            $userId = $this->request->user->id;
            $contactsLogic = new ContactsLogic();
            $responseData = $contactsLogic->queryFriendRelationship($userId, $contactList);

            return apiSuccess(['contact_list' => $responseData]);
        } catch (\Exception $e) {
            generateApiLog("通讯录好友接口异常：{$e->getMessage()}");
        }

        return apiError();
    }
}