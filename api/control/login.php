<?php
/**
 * 前台登录 退出操作
 *
 *
 *
 *
 * by www.shopjl.com 运营版
 */

use Shopnc\Tpl;

defined('InShopNC') or exit('Access Invalid!');
Base::autoload('vendor/autoload');
class loginControl extends apiHomeControl
{

    public function __construct()
    {
        parent::__construct();
    }

    private function isQQLogin()
    {
        return !empty($_GET[type]);
    }

    /**
     * 登录
     */
    public function indexOp()
    {
        if (!$this->isQQLogin()) {
            if (empty($_POST['username']) || empty($_POST['password']) || !in_array($_POST['client'], $this->client_type_array)) {
                output_error('登录失败');
            }
        }
        $model_member = Model('member');
        $array = array();
        if ($this->isQQLogin()) {
            $array['member_qqopenid'] = $_SESSION['openid'];
        } else {
            $array['member_name'] = $_POST['username'];
            $array['member_passwd'] = md5($_POST['password']);
        }
        $member_info = $model_member->getMemberInfo($array);
        if (!empty($member_info)) {
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $_POST['client']);

            $imToken = $this->getImToken($member_info['member_id'],$member_info['member_name']);

            if ($token) {
                if ($this->isQQLogin()) {
                    setNc2Cookie('username', $member_info['member_name']);
                    setNc2Cookie('key', $token);
                    header("location:" . WAP_SITE_URL . '/tmpl/member/member.html?act=member');
                } else {
                    //  融云Token
                    output_data(array(
                            'username' => $member_info['member_name'],
                            'uid' => $member_info['member_id'],
                            'key' => $token,
                            'imToken' => $imToken)
                    );
                }
            } else {
                output_error('登录失败');
            }
        } else {
            output_error('用户名密码错误');
        }
    }


    private function getImToken($uid,$uname)
    {
        $p = new ServerAPI('0vnjpoadnw2uz','hg0BUlbxV8a1');
        $r = $p->getToken($uid,$uname,getMemberAvatarForID($uid));
        //  处理返回的json数据
        $obj = json_decode($r);
        $imToken = $obj->token;
        //  TODO 将解析出的Token存入数据库
        return $imToken;
    }

    /**
     * 登录生成token
     */
    private function _get_token($member_id, $member_name, $client)
    {
        $model_mb_user_token = Model('mb_user_token');

        //重新登录后以前的令牌失效
        //暂时停用
        //$condition = array();
        //$condition['member_id'] = $member_id;
        //$condition['client_type'] = $_POST['client'];
        //$model_mb_user_token->delMbUserToken($condition); ww w.sho pjl.co m出 品

        //生成新的token
        $mb_user_token_info = array();
        $token = md5($member_name . strval(TIMESTAMP) . strval(rand(0, 999999)));
        $mb_user_token_info['member_id'] = $member_id;
        $mb_user_token_info['member_name'] = $member_name;
        $mb_user_token_info['token'] = $token;
        $mb_user_token_info['login_time'] = TIMESTAMP;
        $mb_user_token_info['client_type'] = $_POST['client'] == null ? 'Android' : $_POST['client'];

        $result = $model_mb_user_token->addMbUserToken($mb_user_token_info);

        if ($result) {
            return $token;
        } else {
            return null;
        }

    }

    /**
     * 注册
     */
    public function registerOp()
    {
        $model_member = Model('member');

        $register_info = array();
        $register_info['username'] = $_POST['username'];
        $register_info['password'] = $_POST['password'];
        $register_info['password_confirm'] = $_POST['password_confirm'];
        $register_info['email'] = $_POST['email'];
        $member_info = $model_member->register($register_info);
        if (!isset($member_info['error'])) {
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $_POST['client']);
            if ($token) {
                output_data(array(
                        'username' => $member_info['member_name'],
                        'uid' => $member_info['member_id'],
                        'key' => $token)
                );
            } else {
                output_error('注册失败');
            }
        } else {
            output_error($member_info['error']);
        }

    }

    /**
     * 手机号注册
     */
    public function phone_registerOp()
    {
        $model_member = Model('member');

        $register_info = array();
        $register_info['username'] = $_POST['username'];
        $register_info['password'] = $_POST['password'];
        $register_info['password_confirm'] = $_POST['password_confirm'];
        $register_info['mobile'] = $_POST['phone'];
        $member_info = $model_member->mobile_register($register_info);
        if (!isset($member_info['error'])) {
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $_POST['client']);
            if ($token) {
                output_data(array(
                        'username' => $member_info['member_name'],
                        'uid' => $member_info['member_id'],
                        'key' => $token)
                );
            } else {
                output_error('注册失败');
            }
        } else {
            output_error($member_info['error']);
        }
    }

    public function phone_statusOp()
    {
        $member_model = Model('member');
        $check_member_mobile = $member_model->getMemberInfo(array('member_mobile' => $_GET['mobile']));
        if (is_array($check_member_mobile) and count($check_member_mobile) > 0)
        {
            echo '1';
        }
        else
        {
            echo '0';
        }
    }
}
