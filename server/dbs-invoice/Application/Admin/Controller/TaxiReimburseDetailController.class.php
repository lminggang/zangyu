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


class TaxiReimburseDetailController extends \Admin\Controller\AdminController {

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
    public function add_taxi_reimburse_detail()
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
     * 获取差旅明细列表
     * Author Raven
     * Date 2019-08-07
     */
    public function getTaxiReimburseDetailParams()
    {
        $detail_key = [
            "detail_date",
            "start_address",
            "finish_address",
            "detail_desc",
            "ticket_cnt",
            "local_taxi_expense",
            "remote_taxi_expense",
            "air_expense",
            "meals_expense",
            "hotel_expense",
            "total_expense",
        ];

        $res = [];
        $i = 0;
        foreach ($_POST['ticket_cnt'] as $index => $ticket_cnt) {
            // 只处理有票据的数据
            if($ticket_cnt > 0){
                $tmp_params = [];

                foreach($detail_key as $dk){
                    $tmp_params[$dk] = $_POST[$dk][$index];
                }

                $tmp_params['index'] = $i;
                $i++;
                $res[] = $tmp_params;
            }
        }

        $decode_key = [
            "local_taxi_expense",
            "remote_taxi_expense",
            "air_expense",
            "meals_expense",
            "hotel_expense"
        ];

        foreach ($res as $key => $value) {
            foreach ($decode_key as $dk) {
                $res[$key][$dk] = json_decode($value[$dk], true);
            }
        }
        return $res;
    }

}
