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


class TaxiReimburseController extends \Admin\Controller\AdminController {

    public function __construct(){
        parent::__construct();

        $this->invoice_bind_type = 2; // 发票绑定记录类型 1-差旅报销单 2-交通费用报销单
    }
    /**
     * 获取交通报销单列表
     * Author Raven
     * Date 2018-07-23
     */
    public function index()
    {
        $page_num = isset($_GET['p']) ? $_GET['p'] : 1;

        $TaxiReimburseModel = D("TaxiReimburse");


        $TaxiReimburseList = $TaxiReimburseModel
            ->getTaxiReimburseList($page_num);

        $this->assign("list", $TaxiReimburseList['list']);
        $this->assign("_page", $TaxiReimburseList['page']);
        $this->display("index");
    }

    /**
     * 添加交通报销单详情
     * Author Raven
     * Date 2019-07-31
     */
    public function add_taxi_reimburse()
    {
        if($_POST){
            $staff_id = trim($_POST['staff_id']);
            $taxi_type = trim($_POST['taxi_type']);

            if(empty($staff_id)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "员工不能为空"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(empty($taxi_type)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "报销类型不能为空"
                );

                $this->ajaxReturn($res);
                exit;
            }

            $TaxiReimburseModel = D("TaxiReimburse");

            $ret = $TaxiReimburseModel->addTaxiReimburse($staff_id, $taxi_type);

            if(isset($ret['errNo'])){
                $res = $ret;
            }else{
                $res = array(
                    "errNo" => "0",
                    "errMsg" => "success"
                );
            }

            // $member_id = $_SESSION['onethink_admin']['user_auth']['uid'];
            
            // $this->create_snapshot($member_id, $ret);

            $this->ajaxReturn($res);
            exit;
        }else{
            $this->assign('TaxiType', C('TAXI_TYPE'));
            $this->display('TaxiReimburse/add_taxi_reimburse');
        }
    }

    /**
     * 编辑交通报销单详情
     * Author Raven
     * Date 2019-08-13
     */
    public function edit_taxi_reimburse()
    {
        $taxi_id = (int)$_GET['id'];
        $TaxiReimburseModel = D("TaxiReimburse");
        if($_POST){
            $staff_id = trim($_POST['staff_id']);
            $taxi_type = trim($_POST['taxi_type']);

            if(empty($staff_id)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "员工不能为空"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(empty($taxi_type)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "报销类型不能为空"
                );

                $this->ajaxReturn($res);
                exit;
            }

            $ret = $TaxiReimburseModel->updateTaxiReimburseBase($taxi_id, $staff_id, $taxi_type);

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
        }else{
            $TaxiInfo = $TaxiReimburseModel
                -> getTaxiReimburseInfoById($taxi_id);

            $staffInfo = getStaffInfoById($TaxiInfo['fk_staff_id'], 'staff_NO, staff_name');

            if(empty($staffInfo)){
                $staff_no = '';
                $staff_name = '';
            }else{
                $staff_no = $staffInfo['staff_NO'];
                $staff_name = $staffInfo['staff_name'];
            }

            $TaxiInfo['staff_no'] = $staff_no;
            $TaxiInfo['staff_name'] = $staff_name;

            $this->assign('info', $TaxiInfo);
            $this->assign('TaxiType', C('TAXI_TYPE'));
            $this->display('TaxiReimburse/edit_taxi_reimburse');
        }
    }

    /**
     * 获取报销员工信息
     * Author Raven
     * Date 2019-07-31
     */
    public function search_staff_list()
    {
        $staff_name = trim($_POST['staff_name']);

        if(empty($staff_name)){
            $res = array(
                "errNo" => "0",
                "errMsg" => "success",
                "data" => []
            );

        }else{
            $StaffModel = D("Staff");
            $field = 'id, staff_name, staff_NO';

            $data = $StaffModel->searchStaffList($staff_name, $field);
            $res = array(
                "errNo" => "0",
                "errMsg" => "success",
                "data" => $data
            ); 
        }

        $this->ajaxReturn($res);
        exit;
    }


    /**
     * 添加交通报销单详情
     * Author Raven
     * Date 2019-07-31
     */
    public function taxi_reimburse_detail()
    {
        $TaxiReimburseModel = D("TaxiReimburse");
        $taxi_id = (int)$_GET['id'];
        $TaxiInfo = $TaxiReimburseModel
                -> getTaxiReimburseInfoById($taxi_id);
        if($_POST){
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            // $total_amount = $_POST['total_amount'];

            $detail_params = $this->getTaxiReimburseDetailParams($taxi_id, $TaxiInfo['fk_staff_id']);

            if (empty($start_date) && empty($start_date) && empty($total_amount)) {
                $start_date = '';;
                $end_date ='';
                $total_amount ='';
                $detail_param = [];
            }
            $amounts = array_column($detail_params, 'detail_amount');
            if (in_array(0, $amounts)) {
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "金额不能为 0"
                );

                $this->ajaxReturn($res);
                exit;
            }
            $total_amount = array_sum($amounts);

            # TODO: 修改交通费用报销单详情
            $res_info = $TaxiReimburseModel
                -> updateTaxiReimburseInfo($taxi_id, $start_date, $end_date, $total_amount);

            if(isset($res_info['errNo'])){
                $this->ajaxReturn($res_info);
                exit;
            }else{
                $res = array(
                    "errNo" => "0",
                    "errMsg" => "success"
                );
            }

            # TODO: 创建交通费用报销单明细
            $res_detail = $TaxiReimburseModel
                -> createTaxiReimburseDetail($detail_params, $taxi_id);

            if(isset($res_detail['errNo'])){
                $res = $res_detail;
            }else{
                $res = array(
                    "errNo" => "0",
                    "errMsg" => "success"
                );
            }

            $this->ajaxReturn($res);
            exit;
        }else{
            $staffInfo = getStaffInfoById($TaxiInfo['fk_staff_id'], 'staff_NO, staff_name, staff_department');

            if(empty($staffInfo)){
                $staff_no = '';
                $staff_name = '';
                $staff_department = '';
            }else{
                $staff_no = $staffInfo['staff_NO'];
                $staff_name = $staffInfo['staff_name'];
                $staff_department = getDepartmentName($staffInfo['staff_department']);
            }

            $TaxiInfo['staff_no'] = $staff_no;
            $TaxiInfo['staff_name'] = $staff_name;
            $TaxiInfo['staff_department'] = $staff_department;
            $TaxiInfo['total_amount'] = sprintf('%.2f', $TaxiInfo['total_amount'] / 100);

            $TaxiDetailList = $TaxiReimburseModel
                -> TaxiReimburseDetailListById($taxi_id);

            $this->assign('info', $TaxiInfo);
            $this->assign('list', $TaxiDetailList);
            $this->display('TaxiReimburse/taxi_reimburse_detail');
        }
    }

    /**
     * 打印交通报销单
     * Author Raven
     * Date 2019-07-31
     */
    public function print_taxi_reimburse()
    {
        $TaxiReimburseModel = D("TaxiReimburse");
        $taxi_id = (int)$_GET['id'];

        $TaxiDetailList = $TaxiReimburseModel
            -> TaxiReimburseDetailListById($taxi_id);

        $list = $this -> print_paging_detail($TaxiDetailList);

        $this->assign('list', $list);
        $this->display('TaxiReimburse/print_taxi_reimburse');
    }

    /**
     * 处理明细打印分页数据
     * Author Raven
     * Date 2019-08-13
     */
    public function print_paging_detail($taxi_detail=[]) 
    {
        $pageSize = 35;

        $res = [];

        $page_index = -1;

        foreach ($taxi_detail as $key => $value) {
            if ($key % $pageSize == 0) {
                $page_index++;
            }
            $res[$page_index]['list'][] = $value;
        }

        foreach ($res as $key => $value) {
            $res[$key]['total_amount'] = sprintf('%.2f', array_sum(array_column($value['list'], 'detail_amount')));
            $res[$key]['total_amount_zh'] = num_to_rmb($res[$key]['total_amount']);
        }
        return $res;
    }

    /**
     * 选择发票列表 
     * Author Raven
     * Date 2019-08-06
     */
    public function select_invoice()
    {
        $TaxiReimburseModel = D("TaxiReimburse");
        $InvoiceModel = D("Invoice");

        $page_num = isset($_GET['p']) ? $_GET['p'] : 1;
        $invoice_id = isset($_GET['invoice_id']) ? $_GET['invoice_id'] : '';

        // $invoice_id = explode("|", $invoice_id);

        $seleceInvoiceList = $TaxiReimburseModel
            ->getSelectInvoiceList($invoice_id);

        $invoiceList = $TaxiReimburseModel
            ->getInvoiceList($page_num, $invoice_id);

        $categoryList = $InvoiceModel
            ->getCategoryList();

        $this->assign("category_list", $categoryList);

        $this->assign("select_list", $seleceInvoiceList);

        $this->assign("list", $invoiceList['list']);
        $this->assign("_page", $invoiceList['page']);
        $this->display('TaxiReimburse/select_invoice');
    }

    /**
     * 绑定报销单发票
     * Author Raven
     * Date 2019-08-06
     */
    public function addInvoiceBind()
    {
        $TaxiReimburseModel = D("TaxiReimburse");

        $taxi_id = isset($_POST['taxi_id']) ? $_POST['taxi_id'] : '';
        $invoice_id = isset($_POST['invoice_id']) ? $_POST['invoice_id'] : '';

        if (empty($taxi_id) || empty($invoice_id)) {
            $res = array(
                "errNo" => "1001",
                "errMsg" => "绑定发票失败"
            );

            $this->ajaxReturn($res);
            exit;
        }

        $res_invoice = $TaxiReimburseModel
            -> addInvoiceBind($invoice_id, $taxi_id, $this->invoice_bind_type);

        if(isset($res_invoice['errNo'])){
            $res = $res_invoice;
        }else{
            $res = array(
                "errNo" => "0",
                "errMsg" => "success"
            );
        }

        $this->ajaxReturn($res);
    }

    /**
     * 删除报销单发票
     * Author Raven
     * Date 2019-08-06
     */
    public function removeInvoiceBindeInfo()
    {
        $InvoiceModel = D("Invoice");

        $taxi_id = isset($_POST['taxi_id']) ? $_POST['taxi_id'] : '';
        $invoice_id = isset($_POST['invoice_id']) ? $_POST['invoice_id'] : '';

        if (empty($taxi_id) || empty($invoice_id)) {
            $res = array(
                "errNo" => "1001",
                "errMsg" => "删除发票失败"
            );

            $this->ajaxReturn($res);
            exit;
        }

        $res_invoice = $InvoiceModel
            -> removeInvoiceBindeInfo($invoice_id, $this->invoice_bind_type);

        if(isset($res_invoice['errNo'])){
            $res = $res_invoice;
        }else{
            $res = array(
                "errNo" => "0",
                "errMsg" => "success"
            );
        }

        $this->ajaxReturn($res);
    }

    /**
     * 批量打印交通费用单列表
     * Author Raven
     * Date 2019-09-05
     */
    public function batch_print_list()
    {

        $page_num = isset($_REQUEST['p']) ? $_REQUEST['p'] : 1;

        $TaxiReimburseModel = D("TaxiReimburse");
        $TaxiReimburseList = $TaxiReimburseModel
            ->getTaxiReimburseList($page_num);

        if($_POST){

            if(count($TaxiReimburseList['list']) < 1){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "没有数据了"
                );

                $this->ajaxReturn($res);
                exit;
            }

            $data = [
                'page_num' => $page_num,
                'list' => $TaxiReimburseList['list'],
            ];

            $res = array(
                "errNo" => "0",
                "errMsg" => "success",
                "data" => $data
            );

            $this->ajaxReturn($res);
        }else{
            $this->assign("list", $TaxiReimburseList['list']);
            $this->assign("page_num", $page_num);
            $this->display("batch_print_list");
        }
    }

    /**
     * 批量打印交通费用报销单 
     * Author Raven
     * Date 2019-09-05
     */
    public function batch_print()
    {
        $item_id = trim($_GET['item_id']);

        if(empty($item_id)){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "item_id is empty"
            );

            $this->ajaxReturn($res);
            exit;
        }

        $TaxiReimburseModel = D("TaxiReimburse");
        $taxi_id = (int)$_GET['id'];

        $TaxiDetailList = $TaxiReimburseModel
            ->getBatchPrintInfoByItemId($item_id);

        $list = $this->print_paging_detail($TaxiDetailList['detail_list']);

        $this->assign('list', $list);
        $this->assign('last_key', count($list));
        $this->assign('amount_info', $TaxiDetailList['amount_info']);
        $this->display('TaxiReimburse/batch_print');

    }

    /**
     * 获取差旅明细列表
     * Author Raven
     * Date 2019-08-07
     */
    public function getTaxiReimburseDetailParams($taxi_id, $staff_id)
    {
        $detail_key = [
            "id",
            "fk_staff_id",
            "fk_info_id",
            "detail_date",
            "detail_departure_location",
            "detail_arrival_location",
            "detail_cause",
            "detail_remark",
            "detail_amount",
        ];

        $detail_date = $_POST['detail_date'];
        $res = [];
        $i = sizeof($detail_date) - 1;
        foreach ($_POST['detail_date'] as $index => $taxi_date) {
            // 只处理有票据的数据
            if($taxi_date > 0){
                $tmp_params = [];

                foreach($detail_key as $dk){
                    $tmp_params[$dk] = $_POST[$dk][$index];

                    if (empty($_POST['detail_date'][$index]) || 
                        empty($_POST['detail_departure_location'][$index]) || 
                        empty($_POST['detail_arrival_location'][$index]) ||
                        empty($_POST['detail_amount'][$index]))
                    {
                        continue;
                    }

                    if ($dk == 'fk_info_id') {
                        $tmp_params[$dk] = $taxi_id;
                    }
                    if ($dk == 'fk_staff_id') {
                        $tmp_params[$dk] = $staff_id;
                    }
                    if ($dk == 'detail_amount') {
                        $tmp_params[$dk] = (string)($_POST[$dk][$index] * 100);
                    }
                }

                $tmp_params['detail_index'] = $index;
                $i--;
                $res[] = $tmp_params;
            }
        }
        return $res;
    }

}
