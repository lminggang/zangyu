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


class  StockProductController extends \Admin\Controller\AdminController {

    /*
        StockProduct/index
        StockProduct/add_product
        StockProduct/get_product_NO
        StockProduct/edit_product
     */
    

    /**
     * 物品目录
     * Author Raven
     * Date 2019-08-29
     */
    public function index()
    {
        $page_num = isset($_GET['p']) ? $_GET['p'] : 1;

        $StockProductModel = D('StockProduct');

        $productList = $StockProductModel
            ->getProductList($page_num);

        $projectList = $StockProductModel->getProjectList();

        $this->assign('project_list', $projectList);
        $this->assign("list", $productList['list']);
        $this->assign("_page", $productList['page']);
        $this->display("index");
    }

    /**
     * 添加物品
     * Author Raven
     * Date 2019-08-29
     */
    public function add_product()
    {
        $StockProductModel = D('StockProduct');

        if($_POST){
            $project_id = intval($_POST['project_id']);
            $product_name = trim($_POST['product_name']);
            $part_number = trim($_POST['part_number']);
            $product_unit = trim($_POST['product_unit']);

            if(empty($project_id)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请选择项目"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(empty($product_name)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请输入物品名称"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(mb_strlen($product_name) > 50){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "物品名称长度不能超过 50 个字符"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(empty($part_number)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请输入物品型号"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(mb_strlen($part_number) > 50){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "物品型号长度不能超过 50 个字符"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(empty($product_unit)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请输入物品单位"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(mb_strlen($product_unit) > 5){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "物品单位长度不能超过 5 个字符"
                );

                $this->ajaxReturn($res);
                exit;
            }

            $ret = $StockProductModel
                ->addProductInfo($project_id, $product_name, $part_number, $product_unit);

            if(isset($ret['errNo'])){
                $res = $ret;
            }elseif($ret === false){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "添加物品信息失败"
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
            $projectList = $StockProductModel->getProjectList();
            
            $this->assign('project_list', $projectList);
            $this->display('add_product');
        }
    }

    /**
     * 编辑物品
     * Author Raven
     * Date 2019-08-30
     */
    public function edit_product()
    {
        $StockProductModel = D('StockProduct');

        $product_id = intval($_GET['id']);
        if($_POST){
            $product_name = trim($_POST['product_name']);
            $part_number = trim($_POST['part_number']);
            $product_unit = trim($_POST['product_unit']);

            if(empty($product_id)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "缺少必要的参数"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(empty($product_name)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请输入物品名称"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(mb_strlen($product_name) > 50){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "物品名称长度不能超过 50 个字符"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(empty($part_number)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请输入物品型号"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(mb_strlen($part_number) > 50){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "物品型号长度不能超过 50 个字符"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(empty($product_unit)){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "请输入物品单位"
                );

                $this->ajaxReturn($res);
                exit;
            }

            if(mb_strlen($product_unit) > 5){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "物品单位长度不能超过 5 个字符"
                );

                $this->ajaxReturn($res);
                exit;
            }

            $ret = $StockProductModel
                ->updateProductInfo($product_id, $product_name, $part_number, $product_unit);

            if(isset($ret['errNo'])){
                $res = $ret;
            }elseif($ret === false){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => "物品信息修改失败"
                );
            }else{
                $res = array(
                    "errNo" => "0",
                    "errMsg" => "success"
                );
            }

            $this->ajaxReturn($res);
        }else{
            $field = 'id, fk_project_id, product_NO, product_name, part_number, product_unit';

            $productInfo = $StockProductModel
                ->getProductInfoById($product_id, $field);

            if(empty($productInfo)){
                echo "Product Not Found!";
                exit();
            }
            $productInfo['project_name'] = getProjectName($productInfo['fk_project_id']);

            $this->assign('info', $productInfo);
            $this->display('edit_product');
        }
    }

    /**
     * 获取物品编号 
     * Author Raven
     * Date 2019-08-30
     */
    public function get_product_NO()
    {
        $project_id = intval($_POST['project_id']);

        if(empty($project_id)){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "缺少必要的参数"
            );

            $this->ajaxReturn($res);
            exit;
        }

        $StockProductModel = D('StockProduct');

        $ret = $StockProductModel
            ->getProductNoByProjectId($project_id);

        if(isset($ret['errNo'])){
            $res = $ret;
        }elseif($ret === false){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "获取物品编号失败"
            );
        }else{
            $res = array(
                "errNo" => "0",
                "errMsg" => "success",
                'data' => [
                    'product_NO' => $ret
                ]
            );
        }

        $this->ajaxReturn($res);
        exit;
    }
}
