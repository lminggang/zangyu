<?php
namespace Admin\Model;
use Think\Model;

class TaxiReimburseModel extends SnapshotModel {

    public function __construct(){
        parent::__construct();

        $this->snapshot_type = 1; //快照类型 1-项目管理
        $this->snapshot_folder = 'taxi_reimburse'; //快照保存路径

        $this->invoice_bind_type = 2; // 发票绑定记录类型 1-差旅报销单 2-交通费用报销单
    }

    /**
     * 添加交通费用报销单
     * Author Raven
     * Date 2019-07-31
     * Params [params]
     * @param string $staff_id    [员工ID]
     * @param string $taxi_type   [打车类型]
     */
    public function addTaxiReimburse($staff_id='', $taxi_type = '')
    {
        $data = array();
        $data['fk_staff_id'] = $staff_id;
        $data['taxi_type'] = $taxi_type;
        $data['info_status'] = 1;
        $data['createtime'] = date('Y-m-d H:i:s');

        $Dao = M('taxi_reimburse');

        $add = $Dao->add($data);

        if($add === false){
            DLOG('添加交通报销单失败 error: ' . $Dao->getDBError(), 'error', 'taxi_reimbures');
            return [
                'errNo' => '1003',
                'errMsg' => '添加交通报销单失败'
            ];
        }

        return $add;
    }

    /**
     * 更新交通费用报销单 
     * Author Raven
     * Date 2019-07-31
     * Params [params]
     * @param  integer $taxi_id           [交通费用ID]
     * @param string $staff_id            [员工ID]
     * @param string $taxi_type           [打车类型]
     */
    public function updateTaxiReimburseBase($taxi_id, $staff_id, $taxi_type)
    {
        $TaxiInfo = $this -> getTaxiReimburseInfoById($taxi_id);

        if(isset($TaxiInfo['errNo'])){
            return $TaxiInfo;
        }

        $data = array();
        $data['fk_staff_id'] = $staff_id;
        $data['taxi_type'] = $taxi_type;

        $Dao = M('taxi_reimburse');

        $where = [
            'id' => $taxi_id
        ];
        $save = $Dao
            ->where($where)
            ->save($data);

        if($save === false){
            DLOG('交通费用报销单保存失败 error: ' . $Dao->getDBError(), 'error', 'taxi_reimbures');
            return [
                'errNo' => '1003',
                'errMsg' => '交通费用报销单编辑失败'
            ];
        }

        return $save;
    }

    /**
     * 更新交通费用报销单 
     * Author Raven
     * Date 2019-07-31
     * Params [params]
     * @param  integer $taxi_id           [交通费用ID]
     * @param  string  $travel_start_date [行程开始时间]
     * @param  string  $travel_end_date   [行程结束时间]
     * @param  string  $total_amount      [合计金额]
     */
    public function updateTaxiReimburseInfo($taxi_id, $travel_start_date, $travel_end_date, $total_amount)
    {
        $TaxiInfo = $this -> getTaxiReimburseInfoById($taxi_id);

        if(isset($TaxiInfo['errNo'])){
            return $TaxiInfo;
        }

        $data = array();
        $data['travel_start_date'] = $travel_start_date;
        $data['travel_end_date'] = $travel_end_date;
        $data['total_amount'] = (string)$total_amount;

        $Dao = M('taxi_reimburse');

        $where = [
            'id' => $taxi_id
        ];
        $save = $Dao
            ->where($where)
            ->save($data);

        if($save === false){
            DLOG('交通费用报销单保存失败 error: ' . $Dao->getDBError(), 'error', 'taxi_reimbures');
            return [
                'errNo' => '1003',
                'errMsg' => '交通费用报销单编辑失败'
            ];
        }

        return $save;
    }

    /**
     * 获取交通费用报销单列表 
     * Author Raven
     * Date 2019-07-31
     * Params [params]
     * @param  integer $page_num [当前页码]
     */
    public function getTaxiReimburseList($page_num=0)
    {
        $where = array();

        if(isset($_REQUEST['staff_name']) && $_REQUEST['staff_name'] != '' || isset($_REQUEST['staff_no']) && $_REQUEST['staff_no'] != ''){
            $staffIds = $this->getStaffIdListByNameAndNo($_REQUEST['staff_no'], $_REQUEST['staff_name']);
            if (!empty($staffIds)) {
                $where['fk_staff_id'] = [
                    'IN', $staffIds
                ];
            }
        }

        if(empty($_REQUEST['start_time']) == false){
            $where['travel_start_date'] = array(
                "egt", $_REQUEST['start_time']
            );
        }

        if(empty($_REQUEST['end_date']) == false){
            $where['travel_end_date'] = array(
                "elt", $_REQUEST['end_date']
            );
        }

        $Dao = M('taxi_reimburse');
        $list = $Dao
            ->field("id, fk_staff_id, taxi_type, travel_start_date, travel_end_date, total_amount")
            ->where($where)
            ->order('id desc')
            ->page($page_num.', 25')
            ->select();


        $count = $Dao
            ->where($where)
            ->count();// 查询满足要求的总记录数
        $Page = new \COM\Page($count, 25);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme',"<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出

        $list = $this->setStaffInfo($list);

        $res = array(
            "list" => $this->setInvoiceId($list),
            "page" => $show,
        );
        return $res;
    }

    /**
     * 设置发票绑定信息 
     * Author Raven
     * Date 2019-08-09
     * Params [params]
     * @param integer $detail_id    [当前明细id]
     * @param array   $taxi_list    [交通费用报销单列表]
     */
    public function setInvoiceId($taxi_list = [])
    {
        $taxiIds = array_column($taxi_list, 'id');

        $Dao = M('invoice_bind');
        $where = [
            'bind_other_id' => [
                'IN', $taxiIds
            ],
            'bind_type' => $this->invoice_bind_type
        ];

        $invoiceBind = $Dao
            ->where($where)
            ->getField('bind_other_id, fk_invoice_id');


        foreach ($taxi_list as $key => $value) {
            $taxi_id = $value['id'];
            $taxi_list[$key]['taxi_type_zh'] = C('TAXI_TYPE')[$taxi_list[$key]['taxi_type']];

            if(isset($invoiceBind[$taxi_id])){
                $taxi_list[$key]['invoice_id'] = $invoiceBind[$taxi_id];
            }else {
                $taxi_list[$key]['invoice_id'] = 0;
            }
            
        }

        return $taxi_list;
    }

    /**
     * 获取项目信息 
     * Author Raven
     * Date 2019-07-31
     * Params [params]
     * @param  integer $taxi_id    [交通费用id]
     * @param  string  $field      [查询字段]
     */
    public function getTaxiReimburseInfoById($taxi_id=0, $field='*')
    {
        $Dao = M('taxi_reimburse');

        $where = [
            'id' => $taxi_id
        ];
        $taxiInfo = $Dao
            ->field($field)
            ->where($where)
            ->find();

        if(empty($taxiInfo)){
            return [
                'errNo' => '1004',
                'errMsg' => '交通费用信息不存在'
            ];
        }

        return $taxiInfo;
    }

    /**
     * 设置员工信息 
     * Author Raven
     * Date 2019-08-05
     * Params [params]
     * @param array $taxi_reimburse_list [交通费用列表]
     */
    public function setStaffInfo($taxi_reimburse_list=[])
    {
        $StaffModel = D('Staff');

        $staffId = array_unique(array_column($taxi_reimburse_list, 'fk_staff_id'));

        $staffInfo = $StaffModel->getStaffInfoByIdList($staffId, 'id,staff_NO,staff_name');

        foreach ($taxi_reimburse_list as $key => $value) {
            $staff_NO = '-';
            $staff_name = '-';
            if(isset($staffInfo[$value['fk_staff_id']])){
                $staff_NO = $staffInfo[$value['fk_staff_id']]['staff_NO'];
                $staff_name = $staffInfo[$value['fk_staff_id']]['staff_name'];
            }

            $taxi_reimburse_list[$key]['staff_NO'] = $staff_NO;
            $taxi_reimburse_list[$key]['staff_name'] = $staff_name;
            $taxi_reimburse_list[$key]['total_amount'] = (string)sprintf('%.2f', $value['total_amount'] / 100);

            unset($taxi_reimburse_list[$key]['fk_staff_id']);
        }

        return $taxi_reimburse_list;
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
     * 根据员工编号,名称 获取员工id列表
     * @return [type] [description]
     */
    public function getStaffIdListByNameAndNo($staff_no, $staff_name)
    {
        $StaffModel = D('Staff');

        $ids = $StaffModel
            -> getStaffIdListByNameAndNo($staff_no, $staff_name);

        return $ids;
    }

    /**
     * 交通费用报销单->创建明细单
     * @param  array  $data_params [description]
     * @return [type]              [description]
     */
    public function createTaxiReimburseDetail($data_params=[], $info_id) {
        $Dao = M('taxi_reimburse_detail');

        $where = [
            "fk_info_id" => $info_id
        ];

        $detail_list = $Dao
            ->field("*")
            ->where($where)
            ->select();

        $old_ids = array_column($detail_list, 'id');
        $new_ids = array_column($data_params, 'id');
        $intersection_ids = array_diff($old_ids,$new_ids);


        $delete_detail = $Dao
            ->where(array('id' => array('in', $intersection_ids)))
            ->delete();

        if($empty_school === false)
        {
            DLOG('编辑交通明细失败 error: ' . $Dao->getDBError(), 'error', 'taxi_reimbures_detail');
            return [
                'errNo' => '1003',
                'errMsg' => '编辑交通明细失败-删除'
            ];
            $Dao->rollback();
        }

        $add_list = [];

        foreach ($data_params as $key => $value) {
            if ($value['id'] != 0) {
                $Dao -> save($value);
            } else {
                $add_list[] = $value;
            } 
        }

        if (!empty($add_list)) {
            $add = $Dao->addAll($add_list);
        }


        if($add === false){
            DLOG('添加交通报销单明细失败 error: ' . $Dao->getDBError(), 'error', 'taxi_reimbures_detail');
            return [
                'errNo' => '1003',
                'errMsg' => '添加交通报销单明细失败'
            ];
        }

        return $add;
    }

    /**
     * 交通费用报销单->创建明细单
     * @param  array  $data_params [description]
     * @return [type]              [description]
     */
    public function TaxiReimburseDetailListById($info_id, $field='*') {        

        $where = [
            'fk_info_id' => $info_id
        ];

        $Dao = M('taxi_reimburse_detail');
        $list = $Dao
            ->field($field)
            ->where($where)
            ->order('detail_index asc')
            ->select();

        foreach ($list as $key => $value) {
            $list[$key]['detail_amount'] = sprintf('%.2f', $value['detail_amount'] / 100);
        }

        return $list;
    }

    /**
     * 添加交通费用报销单发票绑定
     * Author liminggang
     * Date 2019-08-16
     * Params [params]
     * @param integer $invoice_id [绑定发票ID]
     * @param integer $taxi_id    [交通费用报销单ID]
     */
    public function addInvoiceBind($invoice_id, $taxi_id)
    {
        # 删除历史绑定发票记录
        $Dao = M('invoice_bind');
        $where = [
            'bind_other_id' => [
                'IN', $taxi_id
            ],
            'bind_type' => $this->invoice_bind_type
        ];

        $invoiceBind = $Dao
            ->where($where)
            ->delete();

        # 添加新的绑定发票记录
        $InvoiceModel = D("Invoice");
        $res_invoice_add = $InvoiceModel
        -> addInvoiceBind($invoice_id, $taxi_id, $this->invoice_bind_type);

        return $res_invoice_add;
    }

    /**
     * 获取发票列表 
     * Author Raven
     * Date 2019-08-06
     * Params [params]
     * @param  integer $page_num   [当前页码]
     * @param  array   $invoice_id [已选发票记录列表]
     */
    public function getInvoiceList($page_num = 0, $invoice_id=[])
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

        if(false == empty($invoice_id)){
            $where['id'] = [
                [
                    'NOT IN', $invoice_id
                ]
            ];
        }

        $Dao = M('invoice_info'); // 实例化User对象
        // 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
        $list = $Dao
            ->field("id,fk_category_id,product_name,release_date,invoice_code,invoice_num,invoice_amount,createtime")
            ->where($where)
            ->order('id desc')
            ->page($page_num.', 10')
            ->select();

        $list = $this->formatInvoiceList($list);
        $list = $this->setInvoiceBindInfo($list);

        $count = $Dao
            ->where($where)
            ->count();// 查询满足要求的总记录数
        $Page = new \COM\Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme',"<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出


        $res = array(
            "list" => $list,
            "page" => $show,
        );
        return $res;
    }

    /*
     * 获取已选择发票列表 
     * Author Raven
     * Date 2019-08-09
     * Params [params]
     * @param  array  $invoice_id [发票记录id]
     */        
    public function getSelectInvoiceList($invoice_id=[])
    {
        $where = array();
        if(false == empty($invoice_id)){
            $where['id'] = [
                [
                    'IN', $invoice_id
                ]
            ];
        }else{
            return [];
        }

        $Dao = M('invoice_info'); // 实例化User对象
        $list = $Dao
            ->field("id,fk_category_id,product_name,release_date,invoice_code,invoice_num,invoice_amount,createtime")
            ->where($where)
            ->order('id desc')
            ->select();

        $list = $this->formatInvoiceList($list);

        return $list;
    }

    /**
     * 设置发票绑定信息 
     * Author Raven
     * Date 2019-08-09
     * Params [params]
     * @param integer $detail_id    [当前明细id]
     * @param array   $invoice_list [发票记录列表]
     */
    public function setInvoiceBindInfo($invoice_list = [])
    {
        $invoiceId = array_column($invoice_list, 'id');

        $Dao = M('invoice_bind');
        $where = [
            'fk_invoice_id' => [
                'IN', $invoiceId
            ],
            'bind_type' => $this->invoice_bind_type
        ];

        $invoiceBind = $Dao
            ->where($where)
            ->getField('fk_invoice_id, bind_other_id');


        foreach ($invoice_list as $key => $value) {
            $bind_status = 'NO_BIND';
            $invoice_id = $value['id'];

            if(isset($invoiceBind[$invoice_id])){
                $bind_detail_id = $invoiceBind[$invoice_id];

                $bind_status = $bind_detail_id == $detail_id ? "ONSELF" : "OTHER";
            }

            $invoice_list[$key]['bind_status'] = $bind_status;
        }

        return $invoice_list;
    }

    /**
     * 格式化发票列表 
     * Author Raven
     * Date 2019-08-06
     * Params [params]
     * @param  array  $invoice_list [发票列表]
     */
    public function formatInvoiceList($invoice_list = array())
    {
        foreach ($invoice_list as $key => $value) {
            $invoice_list[$key]['invoice_show_amount'] = number_format(floatval($value['invoice_amount'] / 100), 2);
        }

        return $invoice_list;
    }

    /**
     * 获取批量打印信息 
     * Author Raven
     * Date 2019-09-05
     * Params [params]
     * @param  array  $item_id [交通记录id]
     */
    public function getBatchPrintInfoByItemId($item_id=[])
    {
        $Dao = M('taxi_reimburse');

        $where = [
            'id' => ['IN', $item_id],
            'taxi_type' => ['IN', [1,2]]
        ];

        $reimburseInfo =  $Dao
            ->field('id, taxi_type')
            ->where($where)
            ->order('taxi_type desc, id asc')
            ->getField('id, taxi_type');

        $reimburseId = array_keys($reimburseInfo);
        $reimburseDetail = $this->getReimburseDetailByIdList($reimburseId);

        $total_amount = array_sum(array_column($reimburseDetail, 'detail_amount')) / 100;
        $total_amount_zh = num_to_rmb($total_amount);

        foreach ($reimburseDetail as $key => $value) {
            $reimburseDetail[$key]['index'] = $key + 1;
            $reimburseDetail[$key]['detail_amount'] = sprintf('%.2f', $value['detail_amount'] / 100);

        }
        
        $res = [
            'detail_list' => $reimburseDetail,
            'amount_info' => [
                'total_amount' => $total_amount,
                'total_amount_zh' => $total_amount_zh
            ]
        ];

        return $res;
    }


    /**
     * 批量获取交通费用单明细 
     * Author Raven
     * Date 2019-09-05
     * Params [params]
     * @param  array  $reimburse_id [交通费用单id]
     */
    public function getReimburseDetailByIdList($reimburse_id=[])
    {
        $Dao = M('taxi_reimburse_detail');

        $where = [
            'fk_info_id' => ['IN', $reimburse_id]
        ];

        $detailList = $Dao
            ->where($where)
            ->select();

        $sortList = [];

        foreach ($detailList as $key => $value) {
            $sortList[$value['fk_info_id']][] = $value;
        }

        $res = [];

        foreach ($reimburse_id as $key => $value) {
            $res = array_merge($res, $sortList[$value]);
        }

        return $res;
    }

}
