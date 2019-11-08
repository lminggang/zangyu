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


class  StockAssetController extends \Admin\Controller\AdminController {
    
    public function index()
    {
        $page_num = isset($_GET['p']) ? $_GET['p'] : 1;

        $StockAssetModel = D('StockAsset');

        $stockAssetList = $StockAssetModel
            ->getStockAssetList($page_num);

        var_dump($stockAssetList);exit();

        // $staffList = getFullStaffList();


        // $this->assign('staff_list', $staffList);
        $this->assign("list", $stockAssetList['list']);
        $this->assign("_page", $stockAssetList['page']);
        $this->display("index");
    }
}
