<?php
namespace Admin\Model;
use Think\Model;

class StockAssetModel extends SnapshotModel {

    public function __construct(){
        parent::__construct();

        $this->snapshot_type = 4; //快照类型 4-差旅费用报销单
        $this->snapshot_folder = 'stockin'; //快照保存路径

        $this->invoice_bind_type = 3; // 发票绑定记录类型 3-入库记录
        $this->assert_code = "GDZC";
    }

    /**
     * 添加固定资产明细
     * Author liminggang
     * Date 2019-09-06
     * Params [params]
     * @param array $asset_array    [资产管理对象]
     *      fk_product_id           [关联商品ID]
     *      fk_detail_id            [关联入库明细ID]
     *      fk_room_id              [关联仓库ID]
     *      fk_category_id          [关联物品类别ID]
     *      asset_code              [固定资产编号]
     *      asset_amount            [资产原值(单位: 分)]
     *      asset_depreciation_time [折旧时间(单位: 月份)]
     *      asset_buy_date          [购买日期]
     */
    public function addStockAsset($asset_array)
    {
        $Dao = M('stock_asset');
        $add = $Dao
            ->addAll($asset_array);
        if($add === false){
            DLOG('添加固定资产明细失败 error: ' . $Dao->getDBError(), 'error', 'stock_asset');
            return false;
        }
        return true;
    }

    /**
     * 查询仓库信息列表 
     * Author liminggang
     * Date 2019-09-06
     * Params [params]
     * @param  array  $room_id [仓库id列表]
     * @param  string $field   [查询的字段]
     */
    public function getStockAssetList($page_num=0)
    {
        $StockProductModel = D('StockProduct');
        $where = array();

        // TODO 固定资产时间
        if(empty($_GET['date_of_entry_start']) == false){
            $where['create_time'] = array(
                "egt", $_GET['date_of_entry_start']
            );
        }

        if(empty($_GET['date_of_entry_end']) == false){
            $where['create_time'] = array(
                "elt", $_GET['date_of_entry_end']
            );
        }
        
        if(empty($_GET['product_name']) == false){
            $productIds = $StockProductModel->getProductListByName($_GET['product_name']);
            if (!empty($productIds)) {
                $where['fk_product_id'] = [
                    'IN', $productIds
                ];
            }
        }
        
        // TODO 类别名称 模糊查询
        if(isset($_GET['category_name']) == false){
            // $productIds = $StockProductModel->getProductListByName($_GET['product_name']);
            $categoryIds = [];
            if (!empty($categoryIds)) {
                $where['fk_category_id'] = [
                    'IN', $categoryIds
                ];
            }
        }

        // TODO 部门查询
        if(isset($_GET['category_name']) == false){
            if (!empty($categoryIds)) {
                $where['asset_use_department'] = $_GET['asset_use_department'];
            }
        }

        // TODO 员工姓名模糊查询
        if(isset($_GET['staff_name']) && $_GET['staff_name'] != ''){
            $staffIds = $this->getStaffIdListByName($_GET['staff_name']);
            if (!empty($staffIds)) {
                $where['asset_use_staff'] = [
                    'IN', $staffIds
                ];
            }
        }

        $where['asset_status'] = 1;

        $Dao = M('stock_asset');
        $list = $Dao
            ->field("id,fk_product_id,fk_detaill_id,fk_room_id,fk_category_id,asset_use_department,asset_user_staff,asset_code,asset_num,asset_amount,asset_depreciation_time,createtime")
            ->where($where)
            ->order('id desc')
            ->page($page_num.', 25')
            ->select();
        
        // TODO 数据处理调用 StockAssetListHandle 方法
        $list = $this -> StockAssetListHandle($list);

        $count = $Dao
            ->where($where)
            ->count();// 查询满足要求的总记录数
        $Page = new \COM\Page($count, 25);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme',"<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出

        $list = $this->setProjectInfo($list);
        $list = $this->setCategoryInfo($list);
        $res = array(
            "list" => $list,
            "page" => $show,
        );
        return $res;
    }

    public function StockAssetListHandle($asset_list) 
    {
        $StockProductModel = D('StockProduct');
        $StockinModel = D('Stockin');
        $StockRoomModel = D('StockRoom');
        $StockCategoryModel = D('StockCategory');
        $StaffModel = D('Staff');
        // TODO 在上面所有Model中新增 get_list_by_ids 的方法获取内容并拼接参数 最后返回

        // 商品ID列表
        $product_ids = array_column($asset_list, 'fk_product_id');
        // 仓库ID列表
        $room_ids = array_column($asset_list, 'fk_room_id');
        // 物品类别ID列表
        $category_ids = array_column($asset_list, 'fk_category_id');
        // 入库单明细ID列表
        $detail_ids = array_column($asset_list, 'fk_detail_id');
        // 员工ID列表
        $staff_ids = array_column($asset_list, 'asset_use');

        $product_obj_list = $StockProductModel -> getProductListInIds($product_ids);
        $room_obj_list = $StockRoomModel -> getStockRoomListInIds($room_ids);

        $staff_obj_list = $StaffModel -> getStaffListInIds($staff_ids);

        foreach($asset_array as $key => $value) 
        {
            $asset_array[$key]['product_name'] = $product_obj_list[$key]['product_name'];
            $asset_array[$key]['product_NO'] = $product_obj_list[$key]['product_NO'];
            $asset_array[$key]['asset_use_department_name'] = STAFF_DEPARTMENT($value['asset_use_department']);
            $asset_array[$key]['asset_use_staff_name'] = $staff_obj_list[$key]['staff_name'];
            $asset_array[$key]['cfdd'] = $room_obj_list[$key]['room_name'] . '存放位置（目前还没处理）';
            $asset_array[$key]['ysynx'] = (int)((strtotime(date('Y-m-d H:i:s')) - strtotime($value['asset_depreciation_time'])) / (60 * 60 * 24));
            $asset_array[$key]['jczy'] = '净残值 -占位';
            $asset_array[$key]['dqzj_m'] = $value['asset_amount'] * 0.95 / $value['asset_depreciation_time'];
            $asset_array[$key]['ljzk'] = $asset_array[$key]['dqzj_m'] * $asset_array[$key]['ysynx'];
        }
        return $asset_array;
    }
}
