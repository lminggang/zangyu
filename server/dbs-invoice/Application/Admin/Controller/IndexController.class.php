<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use User\Api\UserApi as UserApi;

/**
 * 后台首页控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class IndexController extends AdminController {

    static protected $allow = array( 'verify');

    /**
     * 后台首页
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index(){
        if(UID){
			$this->display();
        } else {
            $this->redirect('Public/login');
        }
    }

    public function login()
    {
        if($_POST){

            $MemberModel = D('Member');

            $token = $_POST['nc_token'];
            $csessionid = $_POST['nc_csessionid'];
            $sig = $_POST['nc_sig'];
            $MemberModel->checkAliASF($token, $csessionid, $sig);
        }else{
            $this->display('login');
        }
    }

    public function test_jx()
    {
        $StaffModel = D('StockProduct');

        echo "<pre>";
        $StaffModel->test();
        echo "</pre>";
    }
}
