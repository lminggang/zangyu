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


class  StockinController extends \Admin\Controller\AdminController {
    
    public function index()
    {
        $page_num = isset($_GET['p']) ? $_GET['p'] : 1;

        $StockinModel = D('Stockin');

        $stockinList = $StockinModel
            ->getStockinList($page_num);

        $staffList = getFullStaffList();


        $this->assign('staff_list', $staffList);
        $this->assign("list", $stockinList['list']);
        $this->assign("_page", $stockinList['page']);
        $this->display("index");
    }

    /**
     * 添加入库记录
     * Author Raven
     * Date 2019-08-30
     */
    public function add_stockin()
    {
        $StockinModel = D('Stockin');

        if($_POST){
            $stockin_date = trim($_POST['stockin_date']);
            $staff_id = trim($_POST['staff_id']);
            $transactor_id = trim($_POST['transactor_id']);

            $invoice_id = intval($_POST['invoice_id']);

            $QC_Bill_Number = trim($_POST['QC_Bill_Number']);
            $purchase_order_num = trim($_POST['purchase_order_num']);
            $contract_num = trim($_POST['contract_num']);



            if(empty($stockin_date) || false === strtotime($stockin_date)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请选择入库时间"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(empty($staff_id)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请选择入库人"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(empty($staff_id)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请选择经办人"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(false === empty($QC_Bill_Number) && mb_strlen($QC_Bill_Number) > 50){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "质检单编号不能超过 50 个字符"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(false === empty($purchase_order_num) && mb_strlen($purchase_order_num) > 50){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "采购单编号不能超过 50 个字符"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(false === empty($contract_num) && mb_strlen($contract_num) > 50){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "合同编号不能超过 50 个字符"
                );
                $this->ajaxReturn($res);
                exit;
            }

            $product_item = $this->formatProductItem();

            if(isset($product_item['errNo'])){
                $this->ajaxReturn($product_item);
                exit;
            }

            if(empty($product_item)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "没有找到入库物品信息"
                );
                $this->ajaxReturn($res);
                exit;
            }

            $ret = $StockinModel
                ->addStockinInfo(
                    $stockin_date, $staff_id, $transactor_id, $invoice_id,
                    $QC_Bill_Number, $purchase_order_num, $contract_num,
                    $product_item
                );

            if(isset($ret['errNo'])){
                $res = $ret;
            }elseif($ret === false){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "添加入库记录失败"
                );
            }else{
                $res = array(
                    "errNo" => "0",
                    "errMsg" => "success"
                );
            }

            $this->ajaxReturn($res);
            exit;
        }else{

            $staffList = getFullStaffList();
            $today_date = date("Y-m-d");

            $stockin_num = $StockinModel
                ->getStockinNumByDate($today_date);


            $this->assign('staff_list', $staffList);
            $this->assign('today_date', $today_date);
            $this->assign('stockin_num', $stockin_num);
            $this->display('add_stockin');
        }
    }

    public function formatProductItem()
    {
        $res = [];

        foreach($_POST['product_id'] as $key => $val){
            $product_id = intval($_POST['product_id'][$key]);
            $address_id = intval($_POST['address_id'][$key]);
            $room_id = intval($_POST['room_id'][$key]);
            $item_sale = floatval($_POST['item_sale'][$key]);
            $item_quantity = intval($_POST['item_quantity'][$key]);

            if(empty($product_id)){
                return [
                    "errNo" => "1001",
                    "errMsg" => "请输入物料id"
                ];
            }

            if(empty($room_id)){
                return [
                    "errNo" => "1001",
                    "errMsg" => "请选择仓库类别"
                ];
            }

            if(empty($address_id)){
                return [
                    "errNo" => "1001",
                    "errMsg" => "请选择存放地点"
                ];
            }

            if(empty($item_sale)){
                return [
                    "errNo" => "1001",
                    "errMsg" => "请输入物品单价"
                ];
            }

            if($item_sale < 0){
                return [
                    "errNo" => "1001",
                    "errMsg" => "物品单价需要大于 0 "
                ];
            }


            if(empty($item_quantity)){
                return [
                    "errNo" => "1001",
                    "errMsg" => "请输入物品数量"
                ];
            }

            if($item_quantity < 0){
                return [
                    "errNo" => "1001",
                    "errMsg" => "物品数量需要大于 0 "
                ];
            }

            $res[] = [
                'product_id' => $product_id,
                'address_id' => $address_id,
                'room_id' => $room_id,
                'item_sale' => (string)($item_sale * 100),
                'item_quantity' => $item_quantity,
                'item_total_amount' => (string)($item_sale * 100 * $item_quantity)
            ];
        }

        return $res;
    }

    /**
     * 获取入库单编号 
     * Author Raven
     * Date 2019-09-01
     */
    public function get_stockin_num()
    {
        $stockin_date = trim($_POST['stockin_date']);

        $StockinModel = D('Stockin');

        $ret = $StockinModel
            ->getStockinNumByDate($stockin_date);

        if(isset($ret['errNo'])){
            $res = $ret;
        }elseif($ret === false){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "获取入库单编号失败"
            );
        }else{
            $res = array(
                "errNo" => "0",
                "errMsg" => "success",
                'data' => [
                    'stockin_num' => $ret
                ]
            );
        }

        $this->ajaxReturn($res);
        exit;
    }

    /**
     * 选择入库物品 
     * Author Raven
     * Date 2019-09-01
     */
    public function get_stock_product()
    {
        $page_num = isset($_GET['p']) ? $_GET['p'] : 1;

        $StockProductModel = D('StockProduct');
        $StockinModel = D('Stockin');

        $productList = $StockProductModel
            ->getProductList($page_num);

        $projectList = $StockProductModel->getProjectList();
        $roomList = $StockinModel->getStockRoomList();
        $addressList = $StockinModel->getStockAddressList();

        $this->assign('project_list', $projectList);
        $this->assign('room_list', json_encode($roomList));
        $this->assign('address_list', json_encode($addressList));
        $this->assign("list", $productList['list']);
        $this->assign("_page", $productList['page']);
        $this->display('stock_product');
    }

    /**
     * 编辑入库信息 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     */
    public function edit_stockin()
    {
        $StockinModel = D('Stockin');

        $stockin_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if($_POST){
            $staff_id = trim($_POST['staff_id']);
            $transactor_id = trim($_POST['transactor_id']);
            $invoice_id = intval($_POST['invoice_id']);

            $QC_Bill_Number = trim($_POST['QC_Bill_Number']);
            $purchase_order_num = trim($_POST['purchase_order_num']);
            $contract_num = trim($_POST['contract_num']);

            if(empty($staff_id)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请选择入库人"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(empty($staff_id)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请选择经办人"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(false === empty($QC_Bill_Number) && mb_strlen($QC_Bill_Number) > 50){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "质检单编号不能超过 50 个字符"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(false === empty($purchase_order_num) && mb_strlen($purchase_order_num) > 50){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "采购单编号不能超过 50 个字符"
                );
                $this->ajaxReturn($res);
                exit;
            }

            if(false === empty($contract_num) && mb_strlen($contract_num) > 50){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "合同编号不能超过 50 个字符"
                );
                $this->ajaxReturn($res);
                exit;
            }

            $ret = $StockinModel
                ->updateStockinInfo(
                    $stockin_id, $staff_id, $transactor_id, $invoice_id,
                    $QC_Bill_Number, $purchase_order_num, $contract_num
                );

            if(isset($ret['errNo'])){
                $res = $ret;
            }elseif($ret === false){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "编辑入库记录失败"
                );
            }else{
                $res = array(
                    "errNo" => "0",
                    "errMsg" => "success"
                );
            }

            $this->ajaxReturn($res);
            exit;
        }else{
            $stockEditInfo = $StockinModel
                ->getStockinEditInfo($stockin_id);

            if(empty($stockEditInfo)){
                echo "Stockin Info Not Found";
                exit();
            }

            $staffList = getFullStaffList();

            $this->assign('staff_list', $staffList);
            $this->assign('stockin_detail', $stockEditInfo['stockin_detail']);
            $this->assign('stockin_info', $stockEditInfo['stockin_info']);

            $this->display('edit_stockin');

        }
    }

    /**
     * 选择发票 
     * Author Raven
     * Date 2019-09-03
     */
    public function select_invoice()
    {
        $StockinModel = D("Stockin");
        $InvoiceModel = D("Invoice");


        $page_num = isset($_GET['p']) ? $_GET['p'] : 1;
        $invoice_id = isset($_GET['invoice_id']) ? $_GET['invoice_id'] : '';

        $seleceInvoiceList = $StockinModel
            ->getSelectInvoiceList($invoice_id);

        $invoiceList = $StockinModel
            ->getInvoiceList($page_num, $invoice_id);

        $categoryList = $InvoiceModel
            ->getCategoryList();

        $this->assign("category_list", $categoryList);

        $this->assign("select_list", $seleceInvoiceList);

        $this->assign("list", $invoiceList['list']);
        $this->assign("_page", $invoiceList['page']);
        $this->display('Stockin/select_invoice');
    }
}
