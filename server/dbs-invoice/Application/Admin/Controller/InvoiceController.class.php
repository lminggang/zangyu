<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: huajie <banhuajie@163.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use COM\Page;

use PHPZxing\PHPZxingDecoder;

/**
 * 后台内容控制器
 * @author huajie <banhuajie@163.com>
 */

class InvoiceController extends \Admin\Controller\AdminController {

    /**
     * 发票列表 
     * Author Raven
     * Date 2018-07-23
     */
    public function index()
    {
        //dump($_GET);exit;
        $page_num = isset($_GET['p']) ? $_GET['p'] : 1;

        $InvoiceModel = D("Invoice");

        $invoiceList = $InvoiceModel
            ->getInvoiceList($page_num);

        $categoryList = $InvoiceModel
            ->getCategoryList();

        $this->assign("category_list", $categoryList);

        $this->assign("list", $invoiceList['list']);
        $this->assign("_page", $invoiceList['page']);
        $this->display("index");
    }

    /**
     * 发票分类列表 
     * Author Raven
     * Date 2018-07-20
     */
    public function category()
    {
        $InvoiceModel = D("Invoice");

        $categoryList = $InvoiceModel
            ->getCategoryList();

        $this->assign("list", $categoryList);

        $this->display("category");
    }

    /**
     * 添加发票分类
     * Author Raven
     * Date 2018-07-20
     */
    public function addCategory()
    {
        $category_name = trim($_POST['category_name']);

        if(empty($category_name)){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "请输入分类名称"
            );

            $this->ajaxReturn($res);
            exit;
        }

        if(mb_strlen($category_name) > 20){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "分类名称不能超过 20 个字符"
            );

            $this->ajaxReturn($res);
            exit;
        }

        $InvoiceModel = D("Invoice");

        $ret = $InvoiceModel->addCategory($category_name);

        if(isset($ret['errNo'])){
            $res = $ret;
        }else{
            $res = array(
                "errNo" => "0",
                "errMsg" => "success"
            );
        }

        $this->ajaxReturn($res);
        exit;
    }

    /**
     * 删除发票分类 
     * Author Raven
     * Date 2018-07-20
     */
    public function removeCategory()
    {
        $category_id = intval($_GET['id']);

        if(empty($category_id)){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "分类id错误"
            );

            $this->ajaxReturn($res);
            exit;
        }

        $InvoiceModel = D("Invoice");

        $ret = $InvoiceModel->removeCategory($category_id);

        if(isset($ret['errNo'])){
            $res = $ret;
        }else{
            $res = array(
                "errNo" => "0",
                "errMsg" => "success"
            );
        }

        $this->ajaxReturn($res);
        exit;
    }

    /**
     * 修改发票分类 
     * Author Raven
     * Date 2018-11-30
     */
    public function updateCategory()
    {
        $id = intval($_GET['id']);
        $category_name = trim($_GET['category_name']);

        if(empty($id)){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "参数错误"
            );

            $this->ajaxReturn($res);
            exit;
        }

        if(empty($category_name)){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "请输入分类名称"
            );

            $this->ajaxReturn($res);
            exit;
        }

        if(mb_strlen($category_name) > 20){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "分类名称不能超过 20 个字符"
            );

            $this->ajaxReturn($res);
            exit;
        }

        $InvoiceModel = D("Invoice");
        $update = $InvoiceModel
            ->updateCategoryName($id, $category_name);

        if($update){
            $res = array(
                "errNo" => "0",
                "errMsg" => "success"
            );
        }else{
            $res = [
                'errNo' => '0003',
                'errMsg' => '系统错误'
            ];
        }

        $this->ajaxReturn($res);

    }

    /**
     * 添加发票信息 
     * Author Raven
     * Date 2018-07-23
     */
    public function addInvoiceInfo()
    {
        $InvoiceModel = D("Invoice");

        if($_POST){
            $invoice_code = trim($_POST['invoice_code']);
            $invoice_num = trim($_POST['invoice_num']);
            $release_date = trim($_POST['release_date']);
            $verify_code = trim($_POST['verify_code']);
            $invoice_type = trim($_POST['invoice_type_code']);
            $no_tax_amount = floatval($_POST['no_tax_amount']);
            $no_tax_amount *= 100;
            $product_name = trim($_POST['product_name']);
            $invoice_amount = floatval($_POST['invoice_amount']);
            $invoice_amount *= 100;
            $invoice_author = trim($_POST['invoice_author']);
            $category_id = intval($_POST['category_id']);
            $reimburse_id = (int)$_POST['reimburse_id'];
            $invoice_info = '';

            if (!empty($invoice_type)) {
                $invoice_info = '01,' . $invoice_type . ',' . $invoice_code . ',' . $invoice_num . ',' . $no_tax_amount . ',' . $verify_code;
            }

            if(empty($invoice_code) || mb_strlen($invoice_code) > 15){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "发票代码格式错误"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(empty($invoice_num) || mb_strlen($invoice_num) > 10){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "发票号码格式错误"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(empty($release_date) || strtotime($release_date) === false){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "开票日期格式错误"
                );
                $this->ajaxReturn($res);
                exit;
            }

            /*if(empty($verify_code) || mb_strlen($verify_code) > 32){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "发票校验码格式错误"
                );
                $this->ajaxReturn($res);
                exit;
            }*/

            if(empty($product_name) || mb_strlen($product_name) > 200){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "商品名称格式错误"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if($invoice_amount <= 0){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "发票金额错误"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(empty($category_id)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请选择发票分类"
                );
                $this->ajaxReturn($res);
                exit;
            }

            $ret = $InvoiceModel
                ->addInvoiceInfo(
                    $invoice_code, $invoice_num, $release_date, $verify_code, $no_tax_amount,
                    $invoice_info, $product_name, $invoice_amount, $invoice_author, $category_id,
                    $reimburse_id
                );

            if(isset($ret['errNo'])){
                $res = $ret;
            }elseif($ret === false){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "发票信息添加失败"
                );
            }else{
                $res = array(
                    "errNo" => "0",
                    "errMsg" => "success"
                );
            }

            $this->ajaxReturn($res);

        }else{
            $categoryList = $InvoiceModel
                ->getCategoryList();

            $this->assign("category_list", $categoryList);
            $this->display("add_invoice");
        }
    }

    /**
     * 编辑发票信息 
     * Author Raven
     * Date 2018-07-24
     */
    public function editInvoiceInfo()
    {
        $invoice_id = intval($_GET['id']);

        if(empty($invoice_id)){
            echo "Invoice NotFound!";
            exit;
        }

        $InvoiceModel = D("Invoice");

        if($_POST){
            $invoice_code = trim($_POST['invoice_code']);
            $invoice_num = trim($_POST['invoice_num']);
            $release_date = trim($_POST['release_date']);
            $verify_code = trim($_POST['verify_code']);
            $invoice_type = trim($_POST['invoice_type_code']);
            $no_tax_amount = floatval($_POST['no_tax_amount']);
            $no_tax_amount *= 100;
            $product_name = trim($_POST['product_name']);
            $invoice_amount = floatval($_POST['invoice_amount']);
            $invoice_amount *= 100;
            $invoice_author = trim($_POST['invoice_author']);
            $category_id = intval($_POST['category_id']);
            $reimburse_id = (int)$_POST['reimburse_id'];
            $invoice_info = '';

            if (!empty($invoice_type)) {
                $invoice_info = '01,' . $invoice_type . ',' . $invoice_code . ',' . $invoice_num . ',' . $no_tax_amount . ',' . $verify_code;
            }

            if(empty($invoice_code) || mb_strlen($invoice_code) > 15){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "发票代码格式错误"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(empty($invoice_num) || mb_strlen($invoice_num) > 10){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "发票号码格式错误"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(empty($release_date) || strtotime($release_date) === false){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "开票日期格式错误"
                );
                $this->ajaxReturn($res);
                exit;
            }

            /*if(empty($verify_code) || mb_strlen($verify_code) > 32){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "发票校验码格式错误"
                );
                $this->ajaxReturn($res);
                exit;
            }*/

            if(empty($product_name) || mb_strlen($product_name) > 200){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "商品名称格式错误"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if($invoice_amount <= 0){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "发票金额错误"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(empty($category_id)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请选择发票分类"
                );
                $this->ajaxReturn($res);
                exit;
            }

            $ret = $InvoiceModel
                ->updateInvoiceInfoById(
                    $invoice_id, $invoice_code, $invoice_num, $release_date, $verify_code, $no_tax_amount, $invoice_info,
                    $product_name, $invoice_amount, $invoice_author, $category_id, $reimburse_id
                );

            if(isset($ret['errNo'])){
                $res = $ret;
            }elseif($ret === false){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "发票信息修改失败"
                );
            }else{
                $res = array(
                    "errNo" => "0",
                    "errMsg" => "success"
                );
            }

            $this->ajaxReturn($res);

        }else{
            $invoiceInfo = $InvoiceModel
                ->getInvoiceInfoById($invoice_id);

            if(empty($invoiceInfo)){
                echo "Invoice NotFound!";
                exit;
            }

            $invoiceInfo = $InvoiceModel
                ->setReimburseId($invoice_id, $invoiceInfo);
            $categoryList = $InvoiceModel
                ->getCategoryList();
            
            $invoiceInfo['no_tax_amount'] = number_format($invoiceInfo['no_tax_amount'] / 100, 2);

            $this->assign("info", $invoiceInfo);
            $this->assign("category_list", $categoryList);
            $this->display("edit_invoice");

        }
    }

    /**
     * 发票统计饼状图 
     * Author Raven
     * Date 2018-07-24
     */
    public function invoiceStatPie()
    {
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date("Y-01-01");
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date("Y-m-d");

        $InvoiceModel = D("Invoice");

        $statInfo = $InvoiceModel
            ->getInvoicePieStatInfo($start_date, $end_date);

        $this->assign("start_date", $start_date);
        $this->assign("end_date", $end_date);
        $this->assign("stat", $statInfo);
        $this->display("invoice_stat_pie");
    }

    /**
     * 采购明细 
     * Author Raven
     * Date 2018-07-25
     */
    public function monthly()
    {
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date("Y-m-01");
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date("Y-m-d");

        $InvoiceModel = D("Invoice");

        $monthlyInfo = $InvoiceModel
            ->getInvoiceMonthlyInfo($start_date, $end_date);

        $this->assign("start_date", $start_date);
        $this->assign("end_date", $end_date);
        $this->assign("title", $monthlyInfo['title']);
        $this->assign("total_amount", $monthlyInfo['total_amount']);
        $this->assign("monthly", $monthlyInfo['monthly']);

        $this->display("invoice_monthly");
    }

    public function checkReview()
    {
        $invoice_id = intval($_GET['id']);

        $InvoiceReviewModel = D("InvoiceReview");

        $reviewInfo = $InvoiceReviewModel
            ->getReviewInfo($invoice_id);
        
        

        if(empty($reviewInfo)){
            echo "Review Info Error!";
            exit;
        }

        $reviewInfo['invoice']['total_amount_zh'] = num_to_rmb(str_replace(',', '', $reviewInfo['invoice']['sumamount']));

        $this->assign("invoice", $reviewInfo['invoice']);
        $this->assign("goods", $reviewInfo['goods']);

        $this->display("invoice_review");
    }

    /**
     * 临时获取发票信息
     * Author Raven
     * Date 2019-08-13
     */
    public function temp_invoice_review()
    {
        $invoice_code = trim($_POST['invoice_code']);
        $invoice_num = trim($_POST['invoice_num']);
        $release_date = trim($_POST['release_date']);
        $verify_code = trim($_POST['verify_code']);
        $no_tax_amount = trim($_POST['no_tax_amount']);

        if(empty($invoice_code) || mb_strlen($invoice_code) > 15){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "发票代码格式错误"
            );
            $this->ajaxReturn($res);
            exit;
        }

        if(empty($invoice_num) || mb_strlen($invoice_num) > 10){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "发票号码格式错误"
            );
            $this->ajaxReturn($res);
            exit;
        }

        if(empty($release_date) || strtotime($release_date) === false){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "开票日期格式错误"
            );
            $this->ajaxReturn($res);
            exit;
        }

        $InvoiceReviewModel = D("InvoiceReview");

        $ret = $InvoiceReviewModel
            ->tempInvoiceReview($invoice_code, $invoice_num, $release_date, $verify_code, $no_tax_amount);

        if(isset($ret['errNo'])){
            $res = $ret;
        }else{
            $res = array(
                "errNo" => "0",
                "errMsg" => "success",
                "data" => $ret
            );
        }

        $this->ajaxReturn($res);

    }

    /**
     * 显示临时发票信息 
     * Author Raven
     * Date 2019-08-13
     */
    public function show_temp_invoice_review()
    {
        $UUID = isset($_GET['uuid']) ? $_GET['uuid'] : '';

        $InvoiceReviewModel = D("InvoiceReview");

        $reviewInfo = $InvoiceReviewModel
            ->getTempReviewInfo($UUID);

        if(empty($reviewInfo)){
            echo "Review Info Error!";
            exit;
        }

        $reviewInfo['total_amount_zh'] = num_to_rmb(str_replace(',', '', $reviewInfo['sumamount']));

        $this->assign("invoice", $reviewInfo);
        $this->assign("goods", $reviewInfo['goodsData']);
        $this->display("temp_invoice_review");
    }

    /**
     * 获取发票绑定列表
     * Author Raven
     * Date 2019-08-14
     */
    public function show_bind_list()
    {
        $invoice_id = isset($_GET['id']) ? $_GET['id'] : $_GET['id'];

        $InvoiceModel = D('Invoice');

        $bind_list = $InvoiceModel->getInvoiceBindList($invoice_id);

        $this->assign("list", $bind_list);
        $this->display("show_bind_list");
    }

    /**
     * 显示发票绑定详情 
     * Author Raven
     * Date 2019-08-14
     */
    public function show_bind_info()
    {
        $bind_id = isset($_GET['id']) ? $_GET['id'] : $_GET['id'];

        $InvoiceModel = D('Invoice');
        $InvoiceModel->showBindInfo($bind_id);
    }

    public function qrcode()
    {
        $token = trim($_GET['token']);

        $url = U("Home/Index/scanInvoice", array("token" => $token));
        $text = "http://" . $_SERVER['SERVER_NAME'] . $url;

        Vendor('phpqrcode.phpqrcode');

        $level=3;
        $size=30;

        Vendor('phpqrcode.phpqrcode');
        $errorCorrectionLevel = intval($level) ;//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        //生成二维码图片
        $object = new \QRcode();
        $object->png($text, false, $errorCorrectionLevel, $matrixPointSize, 2);
    }

    /**
     * 手机扫码页面 
     * Author Raven
     * Date 2018-07-31
     */
    public function mobileScanPage()
    {
        $InvoiceModel = D("Invoice");

        $sacnInfo = $InvoiceModel
            ->mobileScanInfo();

        $this->assign("token", $sacnInfo['token']);

        $this->display("mobile_scan");
    }

    /**
     * 微信扫码页面 
     * Author Raven
     * Date 2018-09-20
     */
    public function wechatScanPage()
    {
        $InvoiceModel = D("Invoice");

        $this->display("wechat_scan");
    }

    /**
     * 删除发票信息 
     * Author Raven
     * Date 2018-11-30
     */
    public function removeInvoiceInfo()
    {
        $InvoiceModel = D("Invoice");

        $id = intval($_GET['id']);

        if(empty($id)){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "参数错误"
            );
            $this->ajaxReturn($res);
            exit;
        }

        $InvoiceModel->removeInvoiceInfo($id);
        $res = array(
            "errNo" => "0",
            "errMsg" => "success"
        );

        $this->ajaxReturn($res);

    }

    /**
     * 更新发票金额 
     * Author Raven
     * Date 2018-11-30
     */
    public function reloadInvoiceAmount()
    {
        $InvoiceModel = D("Invoice");

        $id = intval($_GET['id']);

        if(empty($id)){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "参数错误"
            );
            $this->ajaxReturn($res);
            exit;
        }

        $amount = $InvoiceModel->reloadInvoiceAmount($id);

        if($amount > 0){
            $res = array(
                "errNo" => "0",
                "errMsg" => "success",
                'data' => [
                    'amount' => number_format($amount / 100, 2)
                ]
            );
        }else{
            $res = array(
                "errNo" => "1001",
                "errMsg" => "发票信息错误"
            );
        }
        $this->ajaxReturn($res);
    }

    /**
     * 选择交通费用单
     * Author Raven
     * Date 2019-08-13
     */
    public function select_taxi_reimburse()
    {
        $page_num = isset($_GET['p']) ? $_GET['p'] : 1;
        $reimburse_id = isset($_GET['reimburse_id']) ? $_GET['reimburse_id'] : 0; 

        $InvoiceModel = D('Invoice');

        $taxiReimubrseInfo = $InvoiceModel
            ->getTaxiReimburseInfo($reimburse_id);

        $taxiReimubrseList = $InvoiceModel
            ->selectTaxiReimburse($page_num, $reimburse_id);

        $this->assign("info", $taxiReimubrseInfo);
        $this->assign("list", $taxiReimubrseList['list']);
        $this->assign("_page", $taxiReimubrseList['page']);
        $this->display('Invoice/select_taxi_reimburse');

    }


    /**
     * 添加发票信息 
     * Author Raven
     * Date 2018-07-23
     */
    public function search_invoice()
    {
        $InvoiceModel = D("Invoice");
        
        if($_POST){
            $invoice_code = trim($_POST['invoice_code']);
            $invoice_num = trim($_POST['invoice_num']);

            if(empty($invoice_code) || empty($invoice_num)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "发票信息有误"
                );
                $this->ajaxReturn($res);
                exit;
            }

            $invoiceInfo = $InvoiceModel
                ->getInvoiceInfoByCodeANDNumberAndDate($invoice_code, $invoice_num);

            if(empty($invoiceInfo)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "发票信息不存在"
                );
                $this->ajaxReturn($res);
                exit;
            }

            $InvoiceReviewModel = D("InvoiceReview");

            $reviewInfo = $InvoiceReviewModel
                ->getReviewInfo($invoiceInfo['id']);

            if(empty($reviewInfo)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "发票信息不存在"
                );
                $this->ajaxReturn($res);
                exit;
            }
            $reviewInfo['invoice']['total_amount_zh'] = num_to_rmb(str_replace(',', '', $reviewInfo['invoice']['sumamount']));
            $reviewInfo['goods_count'] = count($reviewInfo['goods']);
            $reviewInfo['goods'] = array_slice($reviewInfo['goods'], 0, 5);

            $res = array(
                "errNo" => "0",
                "errMsg" => "success",
                'data' => [
                    "reviewInfo" => $reviewInfo,

                ]
            );
            $this->ajaxReturn($res);

        }else{
            $categoryList = $InvoiceModel
                ->getCategoryList();

            $this->assign("category_list", $categoryList);
            $this->display("search_invoice");
        }
    }

}
