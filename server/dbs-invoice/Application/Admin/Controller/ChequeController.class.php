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


class ChequeController extends \Admin\Controller\AdminController {

    /**
     * 发票申请单列表 
     * Author Raven
     * Date 2019-08-02
     */
    public function index()
    {
        $page_num = isset($_GET['p']) ? $_GET['p'] : 1;

        $ChequeModel = D("Cheque");

        $chequeList = $ChequeModel
            ->getChequeList($page_num);

        $projectList = $this->getFullProjectName();

        $this->assign('project_list', $projectList);
        $this->assign("list", $chequeList['list']);
        $this->assign("_page", $chequeList['page']);
        $this->display("index");
    }

    /**
     * 添加发票申请单 
     * Author Raven
     * Date 2019-08-02
     */
    public function add_cheque()
    {
        $ChequeModel = D("Cheque");
        if($_POST){
            $project_id = trim($_POST['project_id']);
            $department = trim($_POST['department']);
            $cheque_NO = trim($_POST['cheque_NO']);
            $cheque_apply_date = trim($_POST['cheque_apply_date']);
            $cheque_num = trim($_POST['cheque_num']);
            $cheque_verify_date = trim($_POST['cheque_verify_date']);

            $cheque_company_name = trim($_POST['cheque_company_name']);
            $cheque_bank_name = trim($_POST['cheque_bank_name']);
            $cheque_contact_name = trim($_POST['cheque_contact_name']);
            $cheque_contact_phone_num = trim($_POST['cheque_contact_phone_num']);

            $cheque_desc = trim($_POST['cheque_desc']);
            $cheque_amount = trim($_POST['cheque_amount']);
            $cheque_remark = trim($_POST['cheque_remark']);

            if(false == empty($cheque_apply_date) && false == strtotime($cheque_apply_date)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请输入正确的申领日期"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(false == empty($cheque_num) && mb_strlen($cheque_num) > 20){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => '请输入长度不大于20位的发票号'
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(empty($department) || false == is_numeric($department)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请选择申领部门"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(false == empty($cheque_verify_date) && false == strtotime($cheque_verify_date)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请输入正确的签发日期"
                );

                $this->ajaxReturn($res);
                exit;
            }

            // if(empty($cheque_company_name) || mb_strlen($cheque_company_name) > 50){
            //     $res = array(
            //         "errNo" => "1001",
            //         "errMsg" => "请输入长度不大于50个字的接受单位"
            //     );

            //     $this->ajaxReturn($res);
            //     exit;
            // }

            // if(empty($cheque_bank_name) || mb_strlen($cheque_bank_name) > 50){
            //     $res = array(
            //         "errNo" => "1001",
            //         "errMsg" => "请输入长度不大于50个字的银行名称"
            //     );

            //     $this->ajaxReturn($res);
            //     exit;
            // }

            // if(empty($cheque_contact_name) || mb_strlen($cheque_contact_name) > 20){
            //     $res = array(
            //         "errNo" => "1001",
            //         "errMsg" => "请输入长度不大于20个字的联系人"
            //     );

            //     $this->ajaxReturn($res);
            //     exit;
            // }


            // if(empty($cheque_contact_phone_num) || mb_strlen($cheque_contact_phone_num) > 50){
            //     $res = array(
            //         "errNo" => "1001",
            //         "errMsg" => "请输入长度不大于50个字的电话"
            //     );

            //     $this->ajaxReturn($res);
            //     exit;
            // }

            // if (!preg_match("/^\d{11}$/", $cheque_contact_phone_num)) {
            //     $res = [
            //         'status' => FALSE,
            //         'errMsg' => '请输入有效的电话'
            //     ];
            //     $this->ajaxReturn($res);
            //     exit;
            // }

            if(empty($project_id) || false == is_numeric($project_id)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请选择项目名称"
                );

                $this->ajaxReturn($res);
                exit;
            }

            

            if(empty($cheque_NO) || mb_strlen($cheque_NO) > 12){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "参数 cheque_NO 错误"
                );

                $this->ajaxReturn($res);
                exit;
            }

            // if(empty($cheque_desc) || mb_strlen($cheque_desc) > 200){
            //     $res = array(
            //         "errNo" => "1001",
            //         "errMsg" => "请输入长度不大于200个字的支票用途"
            //     );

            //     $this->ajaxReturn($res);
            //     exit;
            // }

            if(empty($cheque_amount) || false == is_numeric($cheque_amount)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请输入正确的计划金额"
                );

                $this->ajaxReturn($res);
                exit;
            }
            
            // if(empty($cheque_remark) || mb_strlen($cheque_remark) > 250){
            //     $res = array(
            //         "errNo" => "1001",
            //         "errMsg" => "请输入长度不大于250个字的备注"
            //     );

            //     $this->ajaxReturn($res);
            //     exit;
            // }

            $ret = $ChequeModel->createChequeInfo(
                $project_id, $cheque_NO, $cheque_apply_date, $cheque_num, $cheque_bank_name,
                $department, $cheque_verify_date, $cheque_company_name, 
                $cheque_contact_name, $cheque_contact_phone_num, $cheque_desc,
                $cheque_amount, $cheque_remark
            );

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
        }else{
            $projectList = $this->getFullProjectName();
            $chequeNO = $ChequeModel->createChequeNO();

            $departmentList = C('STAFF_DEPARTMENT');

            $this->assign('department_list', $departmentList);
            $this->assign('cheque_NO', $chequeNO);
            $this->assign('project_list', $projectList);
            $this->display("Cheque/add_cheque");
        }
    }

    public function edit_cheque()
    {
        $cheque_id = intval($_GET['id']);

        if(empty($cheque_id)){
            echo "Cheque NotFound!";
            exit;
        }

        $ChequeModel = D("Cheque");

        if($_POST){
            $project_id = trim($_POST['project_id']);
            $department = trim($_POST['department']);
            $cheque_apply_date = trim($_POST['cheque_apply_date']);
            $cheque_num = trim($_POST['cheque_num']);
            $cheque_verify_date = trim($_POST['cheque_verify_date']);

            $cheque_company_name = trim($_POST['cheque_company_name']);
            $cheque_bank_name = trim($_POST['cheque_bank_name']);
            $cheque_contact_name = trim($_POST['cheque_contact_name']);
            $cheque_contact_phone_num = trim($_POST['cheque_contact_phone_num']);

            $cheque_desc = trim($_POST['cheque_desc']);
            $cheque_amount = trim($_POST['cheque_amount']);
            $cheque_remark = trim($_POST['cheque_remark']);

            if(false == empty($cheque_apply_date) && false == strtotime($cheque_apply_date)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请输入正确的申领日期"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(false == empty($cheque_num) && mb_strlen($cheque_num) > 20){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => '请输入长度不大于20位的发票号'
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(empty($department) || false == is_numeric($department)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请选择申领部门"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(false == empty($cheque_verify_date) && false == strtotime($cheque_verify_date)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请输入正确的签发日期"
                );

                $this->ajaxReturn($res);
                exit;
            }

            // if(empty($cheque_company_name) || mb_strlen($cheque_company_name) > 50){
            //     $res = array(
            //         "errNo" => "1001",
            //         "errMsg" => "请输入长度不大于50个字的接受单位"
            //     );

            //     $this->ajaxReturn($res);
            //     exit;
            // }

            // if(empty($cheque_bank_name) || mb_strlen($cheque_bank_name) > 50){
            //     $res = array(
            //         "errNo" => "1001",
            //         "errMsg" => "请输入长度不大于50个字的银行名称"
            //     );

            //     $this->ajaxReturn($res);
            //     exit;
            // }

            // if(empty($cheque_contact_name) || mb_strlen($cheque_contact_name) > 20){
            //     $res = array(
            //         "errNo" => "1001",
            //         "errMsg" => "请输入长度不大于20个字的联系人"
            //     );

            //     $this->ajaxReturn($res);
            //     exit;
            // }


            // if(empty($cheque_contact_phone_num) || mb_strlen($cheque_contact_phone_num) > 50){
            //     $res = array(
            //         "errNo" => "1001",
            //         "errMsg" => "请输入长度不大于50个字的电话"
            //     );

            //     $this->ajaxReturn($res);
            //     exit;
            // }

            // if (!preg_match("/^\d{11}$/", $cheque_contact_phone_num)) {
            //     $res = [
            //         'status' => FALSE,
            //         'errMsg' => '请输入有效的电话'
            //     ];
            //     $this->ajaxReturn($res);
            //     exit;
            // }

            if(empty($project_id) || false == is_numeric($project_id)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请选择项目名称"
                );

                $this->ajaxReturn($res);
                exit;
            }

            // if(empty($cheque_desc) || mb_strlen($cheque_desc) > 200){
            //     $res = array(
            //         "errNo" => "1001",
            //         "errMsg" => "请输入长度不大于200个字的支票用途"
            //     );

            //     $this->ajaxReturn($res);
            //     exit;
            // }

            if(empty($cheque_amount) || false == is_numeric($cheque_amount)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请输入正确的计划金额"
                );

                $this->ajaxReturn($res);
                exit;
            }
            
            // if(empty($cheque_remark) || mb_strlen($cheque_remark) > 250){
            //     $res = array(
            //         "errNo" => "1001",
            //         "errMsg" => "请输入长度不大于250个字的备注"
            //     );

            //     $this->ajaxReturn($res);
            //     exit;
            // }


            $ret = $ChequeModel->updateChequeInfo(
                $cheque_id, $project_id,  $cheque_apply_date, $cheque_num, $cheque_bank_name,
                $department, $cheque_verify_date, $cheque_company_name, 
                $cheque_contact_name, $cheque_contact_phone_num, $cheque_desc,
                $cheque_amount, $cheque_remark
            );

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
        }else{
            $projectList = $this->getFullProjectName();
            $departmentList = C('STAFF_DEPARTMENT');

            $field = 'id, fk_project_id, cheque_NO, cheque_apply_date, cheque_num, cheque_apply_department, cheque_verify_date, cheque_company_name, cheque_bank_name, cheque_contact_name, cheque_contact_phone_num, cheque_desc, cheque_amount, cheque_remark';
            $chequeInfo = $ChequeModel->getChequeInfoById($cheque_id, $field);

            if(empty($chequeInfo)){
                echo "Cheque NotFound!";
                exit;
            }

            $chequeInfo['project_code'] = getProjectCode($chequeInfo['fk_project_id']);
            $chequeInfo['cheque_amount'] = $chequeInfo['cheque_amount'] / 100;
            $chequeInfo['cheque_amount_zh'] = num_to_rmb($chequeInfo['cheque_amount']);
            
            $this->assign('info', $chequeInfo);
            $this->assign('department_list', $departmentList);
            $this->assign('project_list', $projectList);
            $this->display("Cheque/edit_cheque");
        }
    }

    public function print_cheque()
    {
        $cheque_id = intval($_GET['id']);

        if(empty($cheque_id)){
            echo "Cheque NotFound!";
            exit;
        }

        $ChequeModel = D("Cheque");

        $field = 'fk_project_id, cheque_apply_date, cheque_NO, cheque_num, cheque_apply_department, cheque_verify_date, cheque_company_name, cheque_bank_name, cheque_contact_name, cheque_contact_phone_num, cheque_desc, cheque_amount, cheque_remark';
        $chequeInfo = $ChequeModel->getChequeInfoById($cheque_id, $field);

        if(empty($chequeInfo)){
            echo "Cheque NotFound!";
            exit;
        }

        $chequeInfo['project_code'] = getProjectCode($chequeInfo['fk_project_id']);
        $chequeInfo['project_name'] = getProjectName($chequeInfo['fk_project_id']);
        $chequeInfo['cheque_amount'] = $chequeInfo['cheque_amount'] / 100;
        $chequeInfo['cheque_amount_zh'] = num_to_rmb($chequeInfo['cheque_amount']);
        $chequeInfo['cheque_amount'] = number_format($chequeInfo['cheque_amount'], 2);
        $chequeInfo['department_name'] = getDepartmentName($chequeInfo['cheque_apply_department']);
        // $chequeInfo['cheque_apply_date'] = date("Y 年 m 月 d 日", strtotime($chequeInfo['cheque_apply_date']));
        // $chequeInfo['cheque_verify_date'] = date("Y 年 m 月 d 日", strtotime($chequeInfo['cheque_verify_date']));

        $chequeInfo['cheque_apply_date'] = strtotime($chequeInfo['cheque_apply_date']) > 0 ? date("Y 年 m 月 d 日", strtotime($chequeInfo['cheque_apply_date'])) : '';
        $chequeInfo['cheque_verify_date'] = strtotime($chequeInfo['cheque_verify_date']) > 0 ? date("Y 年 m 月 d 日", strtotime($chequeInfo['cheque_verify_date'])) : '';




        $print_cnt = $ChequeModel->incrPrintCnt($cheque_id);

        $chequeInfo['print_cnt'] = $print_cnt;
        // echo "<pre>";
        // var_dump($chequeInfo);
        // echo "</pre>";

        $this->assign('info', $chequeInfo);
        $this->display('Cheque/print_cheque');

    }

    /**
     * 查询历史公司列表 
     * Author Raven
     * Date 2019-08-02
     */
    public function search_history_company()
    {
        $company_name = trim($_POST['company_name']);

        if(empty($company_name)){
            $res = array(
                "errNo" => "0",
                "errMsg" => "success",
                "data" => []
            );
        }else{
            $ChequeModel = D("Cheque");
            
            $data = $ChequeModel->searchHistoryCompanyList($company_name);

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
     * 获取项目列表 
     * Author Raven
     * Date 2019-08-02
     */
    public function getFullProjectName()
    {
        $PorjectModel = D('Project');

        return $PorjectModel->getFullProjectName();
    }

    
}
