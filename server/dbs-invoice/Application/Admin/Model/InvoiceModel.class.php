<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <code-tech.diandian.com>
// +----------------------------------------------------------------------

namespace Admin\Model;
use Think\Model;

class InvoiceModel extends Model {

    /**
     * 获取发票分类 
     * Author Raven
     * Date 2018-07-20
     */
    public function getCategoryList()
    {
        $Dao = M("invoice_category");

        $res = $Dao
            ->field("id,category_name")
            ->order("category_name asc")
            ->select();

        return $res;
    }

    /**
     * 添加发票分类 
     * Author Raven
     * Date 2018-07-20
     * Params [params]
     * @param string $category_name [分类名称]
     */
    public function addCategory($category_name = '')
    {
        $hasCategoryName = $this->hasCategoryName($category_name);

        if($hasCategoryName){
            $res = array(
                "errNo" => "1001",
                "errMsg" => "分类名称已存在",
            );
            return $res;
        }

        $Dao = M("invoice_category");

        $data = array();
        $data['category_name'] = $category_name;
        $data['createtime'] = date("Y-m-d H:i:s");


        $add = $Dao->add($data);

        return $add;
    }


    /**
     * 检测发票分类名称是否存在 
     * Author Raven
     * Date 2018-07-20
     * Params [params]
     * @param string $category_name [分类名称]
     */
    public function hasCategoryName($category_name = '')
    {
        $Dao = M("invoice_category");

        $where = array(
            "category_name" => $category_name
        );

        $cnt = $Dao
            ->where($where)
            ->count();

        return $cnt > 0 ? true : false;
    }

    /**
     * 删除发票分类 
     * Author Raven
     * Date 2018-07-20
     * Params [params]
     * @param  integer $category_id [分类id]
     */
    public function removeCategory($category_id = 0)
    {
        $Dao = M("invoice_category");

        $where = array(
            "id" => $category_id
        );

        $res = $Dao
            ->where($where)
            ->delete();

        return $res > 0 ? true : false;
    }

    /**
     * 更新分类名称 
     * Author Raven
     * Date 2018-11-30
     * Params [params]
     * @param  integer $category_id   [分类id]
     * @param  string  $category_name [分类名称]
     */
    public function updateCategoryName($category_id = 0, $category_name = '')
    {
        $where = array(
            "id" => $category_id
        );

        $data = [];
        $data['category_name'] = $category_name;

        $Dao = M("invoice_category");
        $res = $Dao
            ->where($where)
            ->save($data);

        return $res === false ? false : true;
    }

    /**
     * 添加发票信息 
     * Author Raven
     * Date 2018-07-23
     * Params [params]
     * @param string  $invoice_code   [发票编号]
     * @param string  $invoice_num    [发票号码]
     * @param string  $release_date   [开票日期]
     * @param string  $verify_code    [校验码]
     * @param string  $product_name   [商品名称]
     * @param integer $invoice_amount [发票金额 单位：分]
     * @param string  $invoice_author [开票人]
     * @param integer $category_id    [发票分类id]
     * @param integer $reimburse_id   [交通报销单记录id]
     */
    public function addInvoiceInfo($invoice_code = '', $invoice_num = '', $release_date = '', $verify_code = '', $no_tax_amount = '', $invoice_info, $product_name = '', $invoice_amount = 0, $invoice_author = '', $category_id = 0, $reimburse_id=0)
    {

        if($this->hasInvoiceNum($invoice_code, $invoice_num)){
            $res = array(
                "errNo" => "1001",
                "errMsg" => sprintf("发票代码: %s <br> 发票号码: %s <br><span style='color:#d9534f'>发票信息重复</span>", $invoice_code, $invoice_num),
            );

            return $res;
        }

        $bind_type = 2; // 2-打车报销单
        if(false == empty($reimburse_id)){
            
            $reimburseBind = $this->checkReimburseBind($reimburse_id, $bind_type);

            if($reimburseBind){
                $res = array(
                    "errNo" => "1001",
                    "errMsg" => '此报销单已绑定发票',
                );
                return $res;       
            }
        }

        $data = array();
        $data['fk_category_id'] = $category_id;
        $data['invoice_code'] = $invoice_code;
        $data['invoice_num'] = $invoice_num;
        $data['release_date'] = $release_date;
        $data['verify_code'] = $verify_code;
        $data['invoice_info'] = $invoice_info;
        $data['no_tax_amount'] = $no_tax_amount;
        $data['product_name'] = $product_name;
        $data['invoice_amount'] = $invoice_amount;
        $data['invoice_author'] = $invoice_author;
        $data['createtime'] = date("Y-m-d H:i:s");


        $Dao = M("invoice_info");

        $Dao->startTrans();

        $res = $Dao
            ->add($data);

        if(false === $res){
            $Dao->rollback();
            DLOG('发票记录创建失败 error: ' . $Dao->getDBError(), 'error', 'invoice');
            return [
                'errNo' => '1003',
                'errMsg' => '发票记录创建失败'
            ];
        }

        if(false == empty($reimburse_id)){
            $addBind = $this->addInvoiceBind($res, $reimburse_id, $bind_type);

            if(isset($addBind['errNo'])){
                $Dao->rollback();
                return $addBind;
            }
        }

        $addReviewData = $this->addReviewData($res, $invoice_code, $invoice_num, $release_date, $verify_code);

        if(empty($addReviewData)){
            $Dao->rollback();
            DLOG('添加发票核验信息失败 error: ' . $Dao->getDBError(), 'error', 'invoice');
            return [
                'errNo' => '1003',
                'errMsg' => '核验信息添加失败'
            ];
        }
        $Dao->commit();
        return intval($res);
    }

    /**
     * 添加发票核验信息 
     * Author Raven
     * Date 2019-08-14
     * Params [params]
     * @param  integer $invoice_id   [发票记录id]
     * @param  string  $invoice_code [发票代码]
     * @param  string  $invoice_num  [发票号码]
     * @param  string  $release_date [开票日期]
     * @param  string  $verify_code  [发票校验码]
     */
    public function addReviewData($invoice_id = 0, $invoice_code = '', $invoice_num = '', $release_date = '', $verify_code = '')
    {
        $release_date = date("Ymd", strtotime($release_date));

        $data = array();
        $data['checkCode'] = substr($verify_code, -6, 6);
        $data['fpdm'] = $invoice_code;
        $data['fphm'] = $invoice_num;
        $data['kprq'] = $release_date;

        $invoice_UUID = md5($data['checkCode'] . $data['fpdm'] . $data['fphm'] . $data['kprq']);
        $invoice_UUID = strtoupper(substr($invoice_UUID, 8, 16));

        $redisKey = "TMEM_INVOICE_" . $invoice_UUID;

        $redis = D("Redis");

        if($redis->TTL($redisKey) < 1){
            return true;
        }

        $reviewData = json_decode($redis->get($redisKey), true);

        $InvoiceReviewModel = D('InvoiceReview');

        $addInfo = $InvoiceReviewModel->addReviewInfo($invoice_id, $reviewData);

        if(empty($addInfo)){
            return false;
        }

        $addGoods = $InvoiceReviewModel->addReviewGoods($invoice_id, $reviewData['goodsData']);

        if(empty($addGoods)){
            return false;
        }


        $redis->delete($redisKey);

        return true;
    }
    /**
     * 创建发票绑定状态
     * Author Raven
     * Date 2019-08-13
     * Params [params]
     * @param integer $invoice_id   [发票记录id]
     * @param integer $reimburse_id [报销单id]
     * @param integer $bind_type    [绑定类型 1-差旅报销单 2-打车报销单]
     */
    public function addInvoiceBind($invoice_id = 0, $reimburse_id=0, $bind_type = 0)
    {
        $Dao = M('invoice_bind');

        $data = [];
        $data['fk_invoice_id'] = $invoice_id;
        $data['bind_type'] = $bind_type;
        $data['bind_other_id'] = $reimburse_id;
        $data['createtime'] = date("Y-m-d H:i:s");

        $res = $Dao
            ->add($data);

        if(false === $res){
            DLOG('绑定记录创建失败 error: ' . $Dao->getDBError(), 'error', 'invoice');
            return [
                'errNo' => '1003',
                'errMsg' => '绑定记录创建失败'
            ];
        }

        return $res;
    }

    /**
     * 检查报销单绑定状态 
     * Author Raven
     * Date 2019-08-13
     * Params [params]
     * @param  integer $reimburse_id [报销单id]
     * @param  integer $bind_type    [绑定类型 1-差旅报销单 2-打车报销单]
     */
    public function checkReimburseBind($reimburse_id = 0, $bind_type = 0)
    {
        $Dao = M('invoice_bind');

        $where = [
            'bind_type' => $bind_type,
            'bind_other_id' => $reimburse_id
        ];

        $cnt = $Dao
            ->where($where)
            ->count();

        return $cnt > 0 ? true : false;
    }

    /**
     * 编辑发票信息 
     * Author Raven
     * Date 2018-07-23
     * Params [params]
     * @param string  $invoice_code   [发票编号]
     * @param string  $invoice_num    [发票号码]
     * @param string  $release_date   [开票日期]
     * @param string  $verify_code    [校验码]
     * @param string  $product_name   [商品名称]
     * @param integer $invoice_amount [发票金额 单位：分]
     * @param string  $invoice_author [开票人]
     * @param integer $category_id    [发票分类id]
     * @param integer $reimburse_id   [交通费用记录id]
     */
    public function updateInvoiceInfoById($invoice_id = 0, $invoice_code = '', $invoice_num = '', $release_date = '', $verify_code = '', $no_tax_amount = '', $invoice_info = '', $product_name = '', $invoice_amount = 0, $invoice_author = '', $category_id = 0, $reimburse_id =0)
    {
        $where = array(
            "id" => $invoice_id
        );

        $Dao = M("invoice_info");

        $invoice_old_code = $Dao
            ->field('invoice_code')
            ->where($where)
            ->find();

        $data = array();
        $data['fk_category_id'] = $category_id;
        $data['invoice_code'] = $invoice_code;
        $data['invoice_num'] = $invoice_num;
        $data['release_date'] = $release_date;
        $data['verify_code'] = $verify_code;
        $data['no_tax_amount'] = $no_tax_amount;
        $data['invoice_info'] = $invoice_info;
        $data['product_name'] = $product_name;
        $data['invoice_amount'] = strval($invoice_amount);
        $data['invoice_author'] = $invoice_author;
        $data['createtime'] = date("Y-m-d H:i:s");



        $Dao->startTrans();
        $bind_type = 2;

        $removeBindInfo = $this->removeInvoiceBindeInfo($invoice_id, $bind_type);

        if(isset($removeBindInfo['errNo'])){
            $Dao->rollback();
            return $removeBindInfo;
        }

        if(false == empty($reimburse_id)){
            $addBind = $this->addInvoiceBind($invoice_id, $reimburse_id, $bind_type);

            if(isset($addBind['errNo'])){
                $Dao->rollback();
                return $addBind;
            }
        }

        if ($invoice_old_code != $invoice_code) {
            $this->removeInvoiceReviewInfo($invoice_id);
        }

        $res = $Dao
            ->where($where)
            ->save($data);

        $Dao->commit();
        return intval($res);
    }

    /**
     * 删除发票绑定记录 
     * Author Raven
     * Date 2019-08-13
     * Params [params]
     * @param  integer $invoice_id [发票记录id]
     * @param  integer $bind_type  [绑定类型 1-差旅报销单 2-交通报销单]
     */
    public function removeInvoiceBindeInfo($invoice_id = 0, $bind_type = 0)
    {
        $Dao = M('invoice_bind');

        $where = [
            'fk_invoice_id' => $invoice_id,
            'bind_type' => $bind_type
        ];

        $remove = $Dao
            ->where($where)
            ->delete();

        if(false === $remove){
            DLOG('删除发票绑定记录失败 sql: ' . $Dao->getDBError(), 'error', 'invoice');
            return [
                'errNo' => '1003',
                'errMsg' => '删除发票绑定记录失败'
            ];
        }

        return true;
    }

    /**
     * 删除发票核验记录 
     * Author Raven
     * Date 2019-08-13
     * Params [params]
     * @param  integer $invoice_id [发票记录id]
     * @param  integer $bind_type  [绑定类型 1-差旅报销单 2-交通报销单]
     */
    public function removeInvoiceReviewInfo($invoice_id = 0)
    {
        $Dao = M('invoice_review_info');

        $where = [
            'fk_invoice_id' => $invoice_id
        ];
        
        $data = array();
        $data['fk_invoice_id'] = -$invoice_id;

        $remove = $Dao
            ->where($where)
            ->save($data);


        if(false === $remove){
            DLOG('删除发票核验记录失败 sql: ' . $Dao->getDBError(), 'error', 'invoice');
            return [
                'errNo' => '1003',
                'errMsg' => '删除发票核验记录失败'
            ];
        }

        return true;
    }

    /**
     * 检测发票编号是否存在 
     * Author Raven
     * Date 2018-07-20
     * Params [params]
     * @param string $invoice_code [发票编号]
     */
    public function hasInvoiceCode($invoice_code = '')
    {
        $Dao = M("invoice_info");

        $where = array(
            "invoice_code" => $invoice_code
        );

        $cnt = $Dao
            ->where($where)
            ->count();

        return $cnt > 0 ? true : false;
    }

    /**
     * 检测发票号码是否存在 
     * Author Raven
     * Date 2018-07-20
     * Params [params]
     * @param string $invoice_code [发票代码]
     * @param string $invoice_num  [发票号码]
     */
    public function hasInvoiceNum($invoice_code = '',$invoice_num = '')
    {
        $Dao = M("invoice_info");

        $where = array(
            "invoice_code" => $invoice_code,
            "invoice_num" => $invoice_num
        );

        $cnt = $Dao
            ->where($where)
            ->count();

        return $cnt > 0 ? true : false;
    }

    /**
     * 获取发票列表 
     * Author Raven
     * Date 2018-07-23
     * Params [params]
     * @param  integer $page_num [当前页码]
     */
    public function getInvoiceList($page_num = 0)
    {
        $where = array();

        if(isset($_GET['category_id']) && $_GET['category_id'] != ''){
            $where['fk_category_id'] = $_GET['category_id'];
        }

        if(empty($_GET['search_type']) == false && empty($_GET['title']) == false){
            $searchType =  array(
                "1" => "invoice_code",
                "2" => "invoice_num",
                "3" => "invoice_author",
            );

            $where[$searchType[$_GET['search_type']]] = trim($_GET['title']);
        }

        if(empty($_GET['time-start']) == false && empty($_GET['time-end']) == false){
            $where['release_date'] = array(
                "BETWEEN", array($_GET['time-start'], $_GET['time-end'])
            );
        }

        $Dao = M('invoice_info'); // 实例化User对象
        // 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
        $list = $Dao
            ->field("id,fk_category_id,product_name,release_date,invoice_code,invoice_num,invoice_amount,invoice_author,createtime")
            ->where($where)
            ->order('id desc')
            ->page($page_num.', 25')
            ->select();

        $list = $this->setReviewStatus($list);
        $list = $this->formatInvoiceList($list);
        $list = $this->setInvoiceCategory($list);

        $count = $Dao
            ->where($where)
            ->count();// 查询满足要求的总记录数
        $Page = new \COM\Page($count, 25);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme',"<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出

        $res = array(
            "list" => $list,
            "page" => $show,
        );
        return $res;
    }

    /**
     * 获取发票绑定列表
     * Author Raven
     * Date 2019-08-14
     * Params [params]
     * @param  integer $invoice_id [发票记录id]
     */
    public function getInvoiceBindList($invoice_id = 0)
    {
        $Dao = M('invoice_bind');

        $where = [
            'fk_invoice_id' => $invoice_id
        ];

        $bind_list = $Dao
            ->field('id, bind_type, createtime')
            ->where($where)
            ->select();

        $bindTypeDesc = [
            '1' => '差旅报销单',
            '2' => '交通费用单'
        ];
        foreach ($bind_list as $key => $value) {
            $bind_list[$key]['index'] = $key + 1;
            $bind_list[$key]['bind_type'] = isset($bindTypeDesc[$value['bind_type']]) ? $bindTypeDesc[$value['bind_type']] : "-";
        }

        return $bind_list;
    }

    /**
     * 跳转绑定详情页 
     * Author Raven
     * Date 2019-08-14
     * Params [params]
     * @param  integer $bind_id [绑定记录id]
     */
    public function showBindInfo($bind_id = 0)
    {   
        $Dao = M('invoice_bind');

        $where = [
            'id' => $bind_id
        ];
        $bindInfo = $Dao
            ->field('bind_type, bind_other_id')
            ->where($where)
            ->find();

        if(empty($bindInfo)){
            echo "Invoice Bind Not Found";
            exit();
        }

        $bind_type = $bindInfo['bind_type'];
        $other_id = $bindInfo['bind_other_id'];

        $location = '';
        if($bind_type == 1){
            //差旅费用报销单
            $travel_id = getTravelIdByDetailId($other_id);

            if(empty($travel_id)){
                echo "TravelInfo Not Found";
                exit();       
            }

            $location = U("Travel/travel_detail", 'id=' . $travel_id);

        }elseif($bind_type == 2){
            // 交通费用报销单
            $location = U("TaxiReimburse/taxi_reimburse_detail", 'id=' . $other_id);
        }else{
            echo "Invoice Bind Type (" . $bind_type . ") Found";
            exit();
        }
        
        header("Location: " . $location);
        exit();
    }

    /**
     * 设置发票核验状态 
     * Author Raven
     * Date 2019-08-14
     * Params [params]
     * @param array $invoice_list [发票列表]
     */
    public function setReviewStatus($invoice_list=[])
    {
        $invoiceId = array_column($invoice_list, 'id');
        $Dao = M('invoice_review_info');

        $where = [
            'fk_invoice_id' => [
                'IN', $invoiceId
            ]
        ];

        $reviewInfo = $Dao
            ->where($where)
            ->getField('fk_invoice_id, sumamount');

        foreach ($invoice_list as $key => $value) {
            $review_status = 'NOT_REVIEW';
            $real_amount = 0;
            if(isset($reviewInfo[$value['id']])){
                $review_status = "REVIEW";
                $real_amount = $reviewInfo[$value['id']] == $value['invoice_amount'] ? 1 : 0;
            }

            $invoice_list[$key]['review_status'] = $review_status;
            $invoice_list[$key]['real_amount'] = $real_amount;
        }

        return $invoice_list;
    }

    /**
     * 格式化发票列表 
     * Author Raven
     * Date 2018-07-23
     * Params [params]
     * @param  array  $invoice_list [发票列表]
     */
    public function formatInvoiceList($invoice_list = array())
    {
        foreach ($invoice_list as $key => $value) {
            $invoice_list[$key]['invoice_amount'] = number_format(floatval($value['invoice_amount'] / 100), 2);
        }

        return $invoice_list;
    }

    /**
     * 设置发票分类信息 
     * Author Raven
     * Date 2018-07-23
     * Params [params]
     * @param array $invoice_list [发票列表]
     */
    public function setInvoiceCategory($invoice_list = array())
    {
        $categoryList = $this->getCategoryList();

        $categoryName = array();

        foreach ($categoryList as $key => $value) {
            $categoryName[$value['id']] = $value['category_name'];
        }

        foreach ($invoice_list as $key => $value) {
            $category_name = "未知";

            if($value['fk_category_id'] == 0){
                $category_name = "默认";
            }elseif(isset($categoryName[$value['fk_category_id']])){
                $category_name = $categoryName[$value['fk_category_id']];
            }

            $invoice_list[$key]['category_name'] = $category_name;

            unset($invoice_list[$key]['fk_category_id']);
        }

        return $invoice_list;
    }


    /**
     * 获取发票金额
     * Author Raven
     * Date 2019-08-09
     * Params [params]
     * @param  array  $invoice_id [发票记录id列表]
     */
    public function getInvoiceAmountByIdList($invoice_id=[])
    {
        $where = [
            'id' => [
                'IN', $invoice_id
            ]
        ];

        $Dao = M('invoice_info');

        $res = $Dao
            ->where($where)
            ->getField('id, invoice_amount');
        return $res;
    }

    /**
     * 获取发票详情信息 
     * Author Raven
     * Date 2018-07-24
     * Params [params]
     * @param  integer $invoice_id [发票记录id]
     */
    public function getInvoiceInfoById($invoice_id = 0, $field='fk_category_id,product_name,release_date,invoice_code,invoice_num,verify_code,invoice_info,no_tax_amount,invoice_amount,invoice_author')
    {
        $Dao = M("invoice_info");

        $where = array(
            "id" => $invoice_id
        );

        $res = $Dao
            ->field($field)
            ->where($where)
            ->find();

        if(isset($res['invoice_amount'])){
            $res['invoice_amount'] = number_format(floatval($res['invoice_amount'] / 100), 2, '.', '');
        }

        return $res;
    }

    /**
     * 获取发票详情信息 
     * Author Raven
     * Date 2018-07-24
     * Params [params]
     * @param  integer $invoice_id [发票记录id]
     */
    public function getInvoiceInfoByCodeANDNumberAndDate($invoice_code = 0, $invoice_num = 0)
    {
        $Dao = M("invoice_info");

        $where = array(
            "invoice_code" => $invoice_code,
            "invoice_num" => $invoice_num,
        );

        $res = $Dao
            ->field("id,fk_category_id,product_name,release_date,invoice_code,invoice_num,verify_code,invoice_amount,invoice_author")
            ->where($where)
            ->find();

        if(isset($res['invoice_amount'])){
            $res['invoice_amount'] = number_format(floatval($res['invoice_amount'] / 100), 2, '.', '');
        }

        return $res;
    }

    /**
     * 设置打车报销单信息
     * Author Raven
     * Date 2019-08-13
     * Params [params]
     * @param array $invoice_id [发票记录id]
     * @param array $invoice_info [发票信息]
     */
    public function setReimburseId($invoice_id = 0, $invoice_info = [])
    {
        $bind_type = 2;
        $where = [
            'fk_invoice_id' => $invoice_id,
            'bind_type' => $bind_type
        ];

        $Dao = M('invoice_bind');

        $reimburse_id = $Dao
            ->where($where)
            ->limit(1)
            ->getField('bind_other_id');

        $invoice_info['reimburse_id'] = empty($reimburse_id) ? "" : $reimburse_id;

        return $invoice_info;
    }

    /**
     * 获取发票统计饼状图 
     * Author Raven
     * Date 2018-07-24
     * Params [params]
     * @param  string $start_date [开始时间]
     * @param  string $end_date   [结束时间]
     */
    public function getInvoicePieStatInfo($start_date = '', $end_date = '')
    {
        
        $statInfo = $this->getInvoiceAmountStatByDate($start_date, $end_date);
        $statInfo = $this->setInvoiceCategory($statInfo);

        $legendText = $this->getStatPiLegend($statInfo);
        $statInfo = $this->formatStatInvoiceAmount($statInfo);

        $statInfo = $this->formatStatInvoicePieInfo($statInfo);

        $res = array();

        $res['title'] = "发票金额汇总图";
        $res['subtext'] = sprintf("日期：%s 至 %s", $start_date, $end_date);
        $res['total_amount'] = $this->getTotalAmountBySataInfo($statInfo);

        $res['legend']['orient'] = 'vertical';
        $res['legend']['left'] = 'left';
        $res['legend']['data'] = $legendText;

        $res['series']['data'] = $statInfo;

        return $res;
    }

    /**
     * 通过日期获取发票统计信息 
     * Author Raven
     * Date 2018-07-24
     * Params [params]
     * @param  string $start_date [开始时间]
     * @param  string $end_date   [结束时间]
     */
    public function getInvoiceAmountStatByDate($start_date = '', $end_date = '')
    {
        $Dao = M("invoice_info");

        $where = array(
            "release_date" => array(
                "between",
                array($start_date, $end_date)
            )
        );

        $statInfo = $Dao
            ->field("fk_category_id,sum(invoice_amount) sum_amount")
            ->where($where)
            ->order("sum_amount desc")
            ->group("fk_category_id")
            ->select();

        return $statInfo;
    }

    /**
     * 获取饼状图说明敏感
     * Author Raven
     * Date 2018-07-24
     * Params [params]
     * @param  array  $stat_info [统计信息]
     */
    public function getStatPiLegend($stat_info = array())
    {
        $res = array();

        foreach ($stat_info as $key => $value) {
            $res[] = $value['category_name'];
        }

        return $res;
    }

    /**
     * 更新发票金额信息 
     * Author Raven
     * Date 2018-07-24
     * Params [params]
     * @param array $invoice_info [发票信息]
     */
    public function formatStatInvoiceAmount($invoice_info = array())
    {
        foreach ($invoice_info as $key => $value) {
            $invoice_info[$key]['sum_amount'] = number_format(floatval($value['sum_amount'] / 100), 2, '.', '');
        }

        return $invoice_info;
    }

    /**
     * 格式化统计图数据 
     * Author Raven
     * Date 2018-07-24
     * Params [params]
     * @param  array  $stat_info [统计信息]
     */
    public function formatStatInvoicePieInfo($stat_info = array())
    {
        $res = array();

        foreach ($stat_info as $key => $value) {
            $res[] = array(
                "name" => $value['category_name'],
                "value" => $value['sum_amount'],
                "amount" => number_format($value['sum_amount'], 2),
            );
        }

        return $res;
    }

    /**
     * 获取统计合计金额 
     * Author Raven
     * Date 2018-07-24
     * Params [params]
     * @param  array  $stat_info [统计信息]
     */
    public function getTotalAmountBySataInfo($stat_info = array())
    {
        $amount = 0;

        foreach ($stat_info as $key => $value) {
            $amount += $value['value'];
        }

        return number_format($amount, 2);
    }

    /**
     * 获取采购明细 
     * Author Raven
     * Date 2018-07-30
     * Params [params]
     * @param  string $start_date [开始时间]
     * @param  string $end_date   [结束时间]
     */
    public function getInvoiceMonthlyInfo($start_date = '', $end_date = '')
    {
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date);

        $titleDate = sprintf("%s - %s", date("m.d", $start_time), date("m.d", $end_time));

        $monthlyInfo = $this->getInvoiceMonthlyInfoByDate($start_date, $end_date);

        $monthlyInfo = $this->groupMonthlyInfo($monthlyInfo);
        $monthlyInfo['monthly_info'] = $this->setInvoiceCategory($monthlyInfo['monthly_info']);

        $res = array(
            "title" => $titleDate . " 深蓝声科采购明细",
            "monthly" => $monthlyInfo['monthly_info'],
            "total_amount" => $monthlyInfo['total_amount']
        );
        return $res;
    }

    /**
     * 通过日期发票月报数据 
     * Author Raven
     * Date 2018-07-25
     * Params [params]
     * @param  string $start_date [开始日期]
     * @param  string $end_date   [结束日期]
     */
    public function getInvoiceMonthlyInfoByDate($start_date = '', $end_date = '')
    {   
        $where = array(
            "_string" => sprintf("release_date >= '%s' and release_date <= '%s'", $start_date, $end_date),
        );

        $Dao = M("invoice_info");

        $res = $Dao
            ->field("fk_category_id, release_date, product_name, invoice_amount")
            ->where($where)
            ->order("fk_category_id asc, release_date asc, id asc")
            ->select();

        return $res;
    }

    /**
     * 月报信息分组 
     * Author Raven
     * Date 2018-07-25
     * Params [params]
     * @param  array  $monthly_info [月报信息]
     */
    public function groupMonthlyInfo($monthly_info = array())
    {
        $monthly_res = array();

        $total_amount = 0;


        foreach ($monthly_info as $key => $value) {
            $cat_id = $value['fk_category_id'];

            if(isset($monthly_res[$cat_id]) == false){
                $monthly_res[$cat_id]['fk_category_id'] = $cat_id;
                $monthly_res[$cat_id]['total_amount'] = 0;
                $monthly_res[$cat_id]['product_item'] = array();
            }

            $total_amount += $value['invoice_amount'];

            unset($value['fk_category_id']);

            $value['product_no'] = count($monthly_res[$cat_id]['product_item']) + 1;

            $monthly_res[$cat_id]['total_amount'] += $value['invoice_amount'];
            $monthly_res[$cat_id]['product_item'][] = $value;
        }

        foreach ($monthly_res as $key => $value) {
            $monthly_res[$key]['total_amount'] = number_format($value['total_amount'] / 100, 2, '.', '');

            foreach ($value['product_item'] as $product_key => $product_value) {
                $monthly_res[$key]['product_item'][$product_key]['invoice_amount'] = number_format($product_value['invoice_amount'] / 100, 2, '.', '');
            }
        }

        $total_amount = number_format($total_amount / 100, 2, '.', '');

        $monthly_res = array_values($monthly_res);

        $res = array(
            "total_amount" => $total_amount,
            "monthly_info" => $monthly_res,
        );

        return $res;
    }

    /**
     * 获取手机扫码信息 
     * Author Raven
     * Date 2018-07-31
     */
    public function mobileScanInfo()
    {
        if(isset($_SESSION['scan_token'])){
            $token = $_SESSION['scan_token'];
        }else{
            $token = strtoupper(md5(time() . rand(1111,9999)));
            $_SESSION['scan_token'] = $token;
        }


        $res = array(
            "token" => $token,
        );

        return $res;
    }

    /**
     * 删除发票信息 
     * Author Raven
     * Date 2018-11-30
     * Params [params]
     * @param  integer $invoice_id [发票id]
     */
    public function removeInvoiceInfo($invoice_id = 0)
    {
        $where = [
            'id' => $invoice_id
        ];

        $Dao = M("invoice_info");

        $Dao->where($where)
            ->delete();
    }

    /**
     * 计算发票合计金额 
     * Author Raven
     * Date 2019-08-07
     * Params [params]
     * @param  array  $invoice_id [发票id列表]
     */
    public function sumInvoiceAmountByIdList($invoice_id = [])
    {
        if(empty($invoice_id)){
            return 0;
        }
        
        $Dao = M('invoice_info');

        $where = [
            'id' => [
                'IN', $invoice_id
            ]
        ];
        $res = $Dao
            ->where($where)
            ->sum('invoice_amount');

        return $res;
    }
    /**
     * 更新发票金额 
     * Author Raven
     * Date 2018-11-30
     * Params [params]
     * @param  integer $invoice_id [发票id]
     */
    public function reloadInvoiceAmount($invoice_id = 0)
    {
        $infoDao = M("invoice_review_info");

        $where = array(
            "fk_invoice_id" => $invoice_id
        );

        $reviewInfo = $infoDao
            ->field("sumamount")
            ->where($where)
            ->find();

        if(empty($reviewInfo)){
            return 0;
        }


        $invoice_amount = $reviewInfo['sumamount'];
        $where = array(
            "id" => $invoice_id
        );

        $data = array();
        $data['invoice_amount'] = strval($invoice_amount);

        $Dao = M("invoice_info");

        $res = $Dao
            ->where($where)
            ->save($data);

        return $invoice_amount;
    }

    /**
     * 获取交通费用单详情
     * Author Raven
     * Date 2019-08-13
     * Params [params]
     * @param  integer $reimburse_id [交通费用单详情]
     */
    public function getTaxiReimburseInfo($reimburse_id = 0)
    {
        if(empty($reimburse_id)){
            return [];
        }

        $TaxiReimburseModel = D('TaxiReimburse');
        $field = 'id, fk_staff_id, taxi_type, travel_start_date, travel_end_date, total_amount';
        $res = $TaxiReimburseModel->getTaxiReimburseInfoById($reimburse_id, $field);

        if(isset($res['errNo'])){
            return [];
        }

        $res['total_amount'] = number_format($res['total_amount'] / 100, 2, '.', '');

        $res = $this->setStaffInfo([$res]);
        return $res[0];
    }

    /**
     * 根据员工姓名获取员工id列表
     * @return [type] [description]
     */
    public function getStaffIdListByName($staff_name)
    {
        $StaffModel = D('Staff');

        $ids = $StaffModel
            -> getStaffIdListByName($staff_name);


        return $ids;
    }


    /**
     * 选择交通费用单
     * Author Raven
     * Date 2019-08-13
     * Params [params]
     * @param  integer $page_num     [当前页码]
     * @param  integer $reimburse_id [交通费用单id]
     */
    public function selectTaxiReimburse($page_num = 0, $reimburse_id = 0)
    {
        $where = [];

        if(false == empty($reimburse_id)){
            $where = [
                'id' => ['NEQ', $reimburse_id]
            ];
        }

        if(isset($_GET['staff_name']) && $_GET['staff_name'] != ''){
            $staffIds = $this->getStaffIdListByName($_GET['staff_name']);

            $where['fk_staff_id'] = [
                'IN', $staffIds
            ];
        }

        if(empty($_GET['start_time']) == false){
            $where['travel_start_date'] = array(
                "egt", $_GET['start_time']
            );
        }

        if(empty($_GET['end_date']) == false){
            $where['travel_end_date'] = array(
                "elt", $_GET['end_date']
            );
        }


        $Dao = M('taxi_reimburse');

        $list = $Dao
            ->field('id, fk_staff_id, taxi_type, travel_start_date, travel_end_date, total_amount')
            ->where($where)
            ->order('id desc')
            ->page($page_num.', 10')
            ->select();

        $count = $Dao
            ->where($where)
            ->count();// 查询满足要求的总记录数
        $Page = new \COM\Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme',"<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出

        $list = $this->setStaffInfo($list);


        foreach ($list as $key => $value) {
            $list[$key]['total_amount'] = number_format($value['total_amount'] / 100, 2, '.', '');
        }

        $bind_type = 2;

        $list = $this->setInvoiceBindeInfo($list, $bind_type);

        $res = array(
            "list" => $list,
            "page" => $show,
        );
        return $res;
    }

    /**
     * 设置发票绑定信息 
     * Author Raven
     * Date 2019-08-13
     * Params [params]
     * @param array   $list      [记录id]
     * @param integer $bind_type [绑定类型 1-差旅报销单 2-打车报销单]
     */
    public function setInvoiceBindeInfo($list=[], $bind_type=0)
    {
        $otherId = array_column($list, 'id');

        $where = [
            'bind_other_id' => [
                'IN', $otherId
            ],
            'bind_type' => $bind_type
        ];

        $Dao = M('invoice_bind');

        $invoiceBind = $Dao
            ->where($where)
            ->getField('bind_other_id,fk_invoice_id');

        foreach ($list as $key => $value) {
            $bind_status = 'NO_BIND';

            if(isset($invoiceBind[$value['id']])){
                $bind_status = 'OTHER';
            }

            $list[$key]['bind_status'] = $bind_status;
        }

        return $list;
    }

    /**
     * 设置员工信息 
     * Author Raven
     * Date 2019-08-13
     * Params [params]
     * @param array $list [数据列表]
     */
    public function setStaffInfo($list = [])
    {
        $staffId = array_unique(array_column($list, 'fk_staff_id'));

        $field = 'id,staff_name,staff_NO,staff_department';

        $StaffModel = D('Staff');
        $staffInfo = $StaffModel->getStaffInfoByIdList($staffId, $field);
        $departmentInfo = C('STAFF_DEPARTMENT');

        foreach ($list as $key => $value) {
            $staff_name = '-';
            $staff_no = '-';
            $staff_department = '-';


            if(isset($staffInfo[$value['fk_staff_id']])){
                $staff_name = $staffInfo[$value['fk_staff_id']]['staff_name'];
                $staff_NO = $staffInfo[$value['fk_staff_id']]['staff_NO'];
                $staff_department = $staffInfo[$value['fk_staff_id']]['staff_department'];
            }

            if(isset($departmentInfo[$staff_department])){
                $staff_department = $departmentInfo[$staff_department];
            }

            $list[$key]['staff_name'] = $staff_name;
            $list[$key]['staff_NO'] = $staff_NO;
            $list[$key]['staff_department'] = $staff_department;

        }

        return $list;
    }
}
