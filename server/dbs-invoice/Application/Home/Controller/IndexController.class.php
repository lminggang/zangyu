<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;

use PHPZxing\PHPZxingDecoder;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class IndexController extends HomeController {

	//系统首页
    public function index(){
    	if(IS_CLI){
            $data = M('Content')->field("id,content")->select();
            foreach ($data as $value) {
                $value['content'] = ubb($value['content']);
                M('Content')->save($value);
            }

        } else {
            $category = D('Category')->getTree();
            $lists    = D('Document')->lists(null);

            $this->assign('category',$category);//栏目
            $this->assign('lists',$lists);//列表
            $this->assign('page',D('Document')->page);//分页

            $this->display();
        }
    }

    public function upload(){
    	if(IS_POST){
            //又拍云
            // $config = array(
            //     'host'     => 'http://v0.api.upyun.com', //又拍云服务器
            //     'username' => 'zuojiazi', //又拍云用户
            //     'password' => 'thinkphp2013', //又拍云密码
            //     'bucket'   => 'thinkphp-static', //空间名称
            // );
            // $upload = new \COM\Upload(array('rootPath' => 'image/'), 'Upyun', $config);
            //百度云存储
            $config = array(
                'AccessKey'  =>'3321f2709bffb9b7af32982b1bb3179f',
                'SecretKey'  =>'67485cd6f033ffaa0c4872c9936f8207',
                'bucket'     =>'test-upload',
                'size'      =>'104857600'
            );
    		$upload = new \COM\Upload(array('rootPath' => './Uploads/bcs'), 'Bcs', $config);
    		$info   = $upload->upload($_FILES);
    	} else {
    		$this->display();
    	}
    }

    public function upyun(){
        $policydoc = array(
            "bucket"             => "thinkphp-static", /// 空间名
            "expiration"         => NOW_TIME + 600, /// 该次授权过期时间
            "save-key"            => "/{year}/{mon}/{random}{.suffix}",
            "allow-file-type"      => "jpg,jpeg,gif,png", /// 仅允许上传图片
            "content-length-range" => "0,102400", /// 文件在 100K 以下
        );

        $policy = base64_encode(json_encode($policydoc));
        $sign = md5($policy.'&'.'56YE3Ne//xc+JQLEAlhQvLjLALM=');

        $this->assign('policy', $policy);
        $this->assign('sign', $sign);
        $this->display();
    }

    public function test(){
        $table = new \OT\DataDictionary;
        echo "<pre>".PHP_EOL;
        $out = $table->generateAll();
        echo "</pre>";
        // print_r($out);
    }

    /**
     * 扫描二发票信息
     * Author Raven
     * Date 2018-07-31
     */
    public function scanInvoice()
    {
        $token = trim($_GET['token']);

        $this->assign("token", $token);
        $this->display("scan_invoice");
    }

    public function scanQRcode()
    {
        $token = trim($_GET['token']);

        if(isset($_FILES['file']['tmp_name']) == false){
            $res = array(
                "errNo" => '1001',
                "errMsg" => "图片识别错误"
            );
            $this->ajaxReturn($res);
        }

        DLOG(json_encode($_FILES), "run", "debug");
        $path = $_FILES['file']['tmp_name'];

        $config = array(
            'try_harder' => true, // 当不知道二维码的位置是设置为true
            'multiple_bar_codes' => false, // 当要识别多张二维码是设置为true
        );

        // $decoder = new PHPZxingDecoder($config);
        $decoder = new \PHPZxing\PHPZxingDecoder($config);

        $data = $decoder->decode($path);
        // 
        //$qrcode = $this->getQRCodeText($path);

        $res = array();

        if($data->isFound()) {

            $invoice_text = $data->getImageValue();

            $this->saveInvoiceQRCodeToRedis($token, $invoice_text);

            $res = array(
                "errNo" => "0",
                "errMsg" => "success",
                "data" => array(
                    "text" => $invoice_text
                )
            );    
        }else{
            $res = array(
                "errNo" => '1001',
                "errMsg" => "图片识别错误"
            );
        }

        $this->ajaxReturn($res);
    }

    public function getScanInvoiceList()
    {
        $token = trim($_GET['token']);

        $token = empty($token) ? trim($_POST['token']) : $token;

        if(empty($token)){
            $res = [
                'errNo' => '1001',
                'errMsg' => '参数错误'
            ];
            $this->_response($res);
        }

        $key = sprintf("SK_INVOICE_SCAN_INVOICE_LIST_%s", $token);

        $redis = D("Redis");

        $ttl = $redis->ttl($key);


        $invoice = array();

        if($ttl < -1){
            $invoice = array();
        }else{
            $invoice = $redis->ZREVRANGE($key, 0, -1, 'WITHSCORES');
        }

        foreach ($invoice as $key => $value) {
            $tmp = explode("|", $key);
            $invoice[$key] = [
                'title' => '[' . $value . '] ' . $tmp[0],
                'value' => $tmp[1]
            ];
        }



        $res = array(
            "errNo" => "0",
            "errMsg" => "success",
            "data" => array_values($invoice)
        );

        $this->_response($res);

    }


    public function cleartScanInvoiceList()
    {
        $token = trim($_GET['token']);

        $token = empty($token) ? trim($_POST['token']) : $token;

        if(empty($token)){
            $res = [
                'errNo' => '1001',
                'errMsg' => '参数错误'
            ];
            $this->_response($res);
        }

        $key = sprintf("SK_INVOICE_SCAN_INVOICE_LIST_%s", $token);

        $redis = D("Redis");

        $ttl = $redis->del($key);



        $res = array(
            "errNo" => "0",
            "errMsg" => "success"        
        );

        $this->_response($res);

    }

    public function saveInvoiceQRCodeToRedis($token = '', $invoice_text = '')
    {
        $save_text = getScanInvoiceStr($invoice_text);


        $redis = D("Redis");

        $key = sprintf("SK_INVOICE_SCAN_INVOICE_LIST_%s", $token);
        
        $ttl = $redis->ttl($key);

        $redis->SADD($key, $save_text);

        if($ttl < 0){
            $redis->expire($key, 7200);
        }
    }

    public function pushInvoiceInfo()
    {
        $token = trim($_POST['token']);
        $invoice_text = trim($_POST['invoice_text']);
        $invoice_index = trim($_POST['invoice_index']);

        if(empty($token) || empty($invoice_text) || empty($invoice_index)){
            $res = [
                'errNo' => '1001',
                'errMsg' => '缺少必要的参数'
            ];

            $this->_response($res);
        }
        $save_text = getScanInvoiceStr($invoice_text);

        $redis = D("Redis");

        $key = sprintf("SK_INVOICE_SCAN_INVOICE_LIST_%s", $token);
        
        $ttl = $redis->ttl($key);

        $redis->ZADD($key, $invoice_index, $save_text);

        if($ttl < 0){
            $redis->expire($key, 7200);
        }

        $res = [
            'errNo' => "0",
            'errMsg' => 'success',
            'data' => []
        ];

        $this->_response($res);
    }

    public function removeInvoice()
    {
        $token = $_GET['token'] ? trim($_GET['token']) : trim($_POST['token']);

        $invoice_text = trim($_POST['invoice']);


        $member = getScanInvoiceStr($invoice_text);

        $redis = D("Redis");

        $key = sprintf("SK_INVOICE_SCAN_INVOICE_LIST_%s", $token);

        $redis->ZREM($key, $member);
    }

}
