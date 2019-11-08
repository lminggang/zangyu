<?php
namespace Admin\Model;
use Think\Model;

class StockinModel extends SnapshotModel {

    public function __construct(){
        parent::__construct();

        $this->snapshot_type = 4; //快照类型 4-差旅费用报销单
        $this->snapshot_folder = 'stockin'; //快照保存路径

        $this->invoice_bind_type = 3; // 发票绑定记录类型 3-入库记录
    }

    /**
     * 通过日期获取入库单编号
     * Author Raven
     * Date 2019-09-01
     * Params [params]
     * @param  string $stockin_date [入库日期]
     */
    public function getStockinNumByDate($stockin_date='')
    {
        $Dao = M('stockin_info');

        $where = [
            'stockin_date' => $stockin_date
        ];

        $stockin_cnt = $Dao
            ->where($where)
            ->count();

        $stockin_num = $stockin_cnt + 1;

        $stockin_date = date("Ymd", strtotime($stockin_date));

        $res = $stockin_date. str_pad($stockin_num, 3, 0, STR_PAD_LEFT);
        
        return $res;
    }

    /**
     * 获取仓库列表 
     * Author Raven
     * Date 2019-09-01
     */
    public function getStockRoomList()
    {
        $StockRoomModel = D('StockRoom');

        return $StockRoomModel->getStockRoomList();
    }

    /**
     * 获取存放地点列表 
     * Author Raven
     * Date 2019-09-01
     */
    public function getStockAddressList()
    {
        $StockAddressModel = D('StockAddress');

        return $StockAddressModel->getStockAddressList();
    }

    /**
     * 检查质检单编号是否存在 
     * Author Raven
     * Date 2019-09-01
     * Params [params]
     * @param  string $QC_Bill_Number [质检单编号]
     */
    public function checkQCBillNumberExists($QC_Bill_Number='')
    {
        $where = [
            'QC_Bill_Number' => $QC_Bill_Number
        ];

        $Dao = M('stockin_info');

        $cnt = $Dao
            ->where($where)
            ->find();

        return $cnt > 0;
    }

    /**
     * 检查采购单编号是否存在 
     * Author Raven
     * Date 2019-09-01
     * Params [params]
     * @param  string $purchase_order_num [采购单编号]
     */
    public function checkPurchaseOrderNumExists($purchase_order_num='')
    {
        $where = [
            'purchase_order_num' => $purchase_order_num
        ];

        $Dao = M('stockin_info');

        $cnt = $Dao
            ->where($where)
            ->find();

        return $cnt > 0;
    }

    /**
     * 检查合同编号是否存在 
     * Author Raven
     * Date 2019-09-01
     * Params [params]
     * @param  string $contract_num [合同编号]
     */
    public function checkContractNumExists($contract_num = '')
    {
        $where = [
            'contract_num' => $contract_num
        ];

        $Dao = M('stockin_info');

        $cnt = $Dao
            ->where($where)
            ->find();

        return $cnt > 0;
    }

    /**
     * 添加商品入库明细 
     * Author Raven
     * Date 2019-09-01
     * Params [params]
     * @param integer $stockin_id   [入库id]
     * @param integer $detail_index   [入库记录下标]
     * @param array   $product_item [商品列表]
     */
    public function addStockinDetail($stockin_id=0, $detail_index=0, $product_item = [])
    {
        $data = [];

        $createtime = date('Y-m-d H:i:s');

        foreach ($product_item as $key => $value) {
            $tmp_data = [];

            $tmp_data['detail_index'] = $detail_index;
            $tmp_data['fk_stockin_id'] = $stockin_id;
            $tmp_data['fk_product_id'] = $value['product_id'];
            $tmp_data['fk_room_id'] = $value['room_id'];
            $tmp_data['fk_address_id'] = $value['address_id'];
            $tmp_data['fk_invoice_id'] = $value['invoice_id'];
            $tmp_data['detail_quantity'] = $value['item_quantity'];
            $tmp_data['quantity_left'] = $value['item_quantity'];
            $tmp_data['detail_sale'] = $value['item_sale'];
            $tmp_data['detail_total_amount'] = $value['item_total_amount'];
            $tmp_data['createtime'] = $createtime;

            $data[] = $tmp_data;
        }

        $Dao = M('stockin_detail');

        $add = $Dao
            ->addAll($data);

        if($add === false){
            DLOG('入库明细创建失败 error: ' . $Dao->getDBError(), 'error', 'stockin');
            return [
                'errNo' => '1003',
                'errMsg' => '入库明细创建失败'
            ];
        }

        return true;
    }

    /**
     * 更新库存信息 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param integer $stockin_id   [入库id]
     * @param array   $product_item [商品列表]
     */
    public function updateStockInfo($stockin_id=0, $product_item=[])
    {
        $stock_statement = [];

        foreach ($product_item as $key => $value) {
            $stockInfo = $this->getStockInfo(
                $value['product_id'], $value['room_id'], $value['address_id']
            );

            if(isset($stockInfo['errNo'])){
                return $stockInfo;
            }

            $stock_quantity = $stockInfo['stock_quantity'] + $value['item_quantity'];
            $updateStockQuantity = $this->updateStockQuantity($stockInfo['id'], $stock_quantity);

            if(isset($updateStockQuantity['errNo'])){
                return $updateStockQuantity;
            }

            $updateProductStock = $this->updateProductStockQuantity($value['product_id'], $value['item_quantity']);

            if(isset($updateProductStock['errNo'])){
                return $updateProductStock;
            }

            $statement_data = [];
            $statement_data['fk_stockin_id'] = $stockin_id;
            $statement_data['fk_room_id'] = $value['room_id'];
            $statement_data['fk_product_id'] = $value['product_id'];
            $statement_data['fk_address_id'] = $value['address_id'];
            $statement_data['stock_quantity'] = $value['item_quantity'];
            $statement_data['after_stock_quantity'] = $stock_quantity;
            $statement_data['createtime'] = date("Y-m-d H:i:s");
            
            $stock_statement[] = $statement_data;
        }

        $Dao = M('stock_statement');

        $add = $Dao
            ->addAll($stock_statement);

        if($add === false){
            DLOG('库存流水创建失败 error: ' . $Dao->getDBError(), 'error', 'stock_statement');
            return [
                'errNo' => '1003',
                'errMsg' => '库存流水创建失败'
            ];
        }

        return true;
    }

    /**
     * 更新库存数量 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param  integer $stock_id       [库存记录id]
     * @param  integer $stock_quantity [库存数]
     */
    public function updateStockQuantity($stock_id=0, $stock_quantity=0)
    {
        $Dao = M('stock_info');

        $where = [
            'id' => $stock_id
        ];

        $save = $Dao
            ->where($where)
            ->setField('stock_quantity', $stock_quantity);

        if($save === false){
            DLOG('库存信息更新失败 error: ' . $Dao->getDBError(), 'error', 'stock_info');
            return [
                'errNo' => '1003',
                'errMsg' => '库存信息更新失败'
            ];
        }

        return true;
    }

    /**
     * 更新商品库存 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param  integer $product_id     [商品id]
     * @param  integer $stock_quantity [库存数量]
     */
    public function updateProductStockQuantity($product_id=0, $stock_quantity=0)
    {
        $Dao = M('stock_product');

        $where = [
            'id' => $product_id
        ];

        $save = $Dao
            ->where($where)
            ->setInc('stock_quantity', $stock_quantity);

        if($save === false){
            DLOG('商品库存更新失败 error: ' . $Dao->getDBError(), 'error', 'stock_product');
            return [
                'errNo' => '1003',
                'errMsg' => '商品库存更新失败'
            ];
        }

        return true;
    }

    /**
     * 获取库存信息 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param  integer $product_id [商品id]
     * @param  integer $room_id    [仓库id]
     * @param  integer $address_id [存放地点id]
     */
    public function getStockInfo($product_id=0, $room_id=0, $address_id=0)
    {
        $where = [
            'fk_product_id' => $product_id,
            'fk_room_id' => $room_id,
            'fk_address_id' => $address_id
        ];

        $Dao = M('stock_info');

        $stockInfo = $Dao
            ->field('id, stock_quantity')
            ->where($where)
            ->find();

        if(empty($stockInfo)){
            $data = [];
            $data['fk_room_id'] = $room_id;
            $data['fk_product_id'] = $product_id;
            $data['fk_address_id'] = $address_id;
            $data['stock_quantity'] = 0;
            $data['createtime'] = date("Y-m-d H:i:s");

            $add = $Dao
                ->add($data);

            if($add === false){
                DLOG('库存信息创建失败 error: ' . $Dao->getDBError(), 'error', 'stock_info');
                return [
                    'errNo' => '1003',
                    'errMsg' => '库存信息创建失败'
                ];
            }

            $stockInfo = [
                'id' => $add,
                'stock_quantity' => 0
            ];
        }

        return $stockInfo;
    }

    /**
     * 添加发票绑定信息 
     * Author Raven
     * Date 2019-09-03
     * Params [params]
     * @param integer $stockin_id [入库记录id]
     * @param integer $invoice_id [发票id]
     */
    public function addInvoiceBind($stockin_id=0, $invoice_id=0)
    {
        $Dao = M('invoice_bind');

        $data = [
            'fk_invoice_id' => $invoice_id,
            'bind_type' => $this->invoice_bind_type,
            'bind_other_id' => $stockin_id,
            'createtime' => date('Y-m-d H:i:s')
        ];

        $add = $Dao
            ->add($data);

        if($add === false){
            DLOG('发票绑定信息添加失败 error: ' . $Dao->getDBError(), 'error', 'stock_info');
            return [
                'errNo' => '1003',
                'errMsg' => '发票绑定信息添加失败'
            ];
        }

        return true;
    }

    /**
     * 删除发票绑定信息 
     * Author Raven
     * Date 2019-09-03
     * Params [params]
     * @param integer $stockin_id [入库记录id]
     */
    public function removeInvoiceBind($stockin_id=0)
    {
        $Dao = M('invoice_bind');

        $data = [
            'fk_invoice_id' => $invoice_id,
            'bind_type' => $this->invoice_bind_type,
            'bind_other_id' => $stockin_id,
            'createtime' => date('Y-m-d H:i:s')
        ];

        $where = [
            'bind_type' => $this->invoice_bind_type,
            'bind_other_id' => $stockin_id
        ];

        $remove = $Dao
            ->where($where)
            ->delete();

        if($remove === false){
            DLOG('发票绑定信息删除失败 error: ' . $Dao->getDBError(), 'error', 'stock_info');
            return [
                'errNo' => '1003',
                'errMsg' => '发票绑定信息删除失败'
            ];
        }

        return true;
    }

    /**
     * 添加商品入库信息 
     * Author Raven
     * Date 2019-09-01
     * Params [params]
     * @param string  $stockin_date       [入库时间]
     * @param integer $staff_id           [入库人id]
     * @param integer $transactor_id      [经办人id]
     * @param integer $invoice_type       [发票类型]
     * @param string  $QC_Bill_Number     [质检单编号]
     * @param string  $purchase_order_num [采购单编号]
     * @param string  $contract_num       [合同编号]
     * @param array   $product_item       [入库商品列表]
     */
    public function addStockinInfo($stockin_date='', $staff_id=0, $transactor_id=0, $invoice_type=0, $QC_Bill_Number='', $purchase_order_num='', $contract_num='', $product_item=[])
    {
        if(false === empty($QC_Bill_Number)){
            $QCBillNumberExists = $this->checkQCBillNumberExists($QC_Bill_Number);

            if($QCBillNumberExists){
                return [
                    "errNo" => "1001",
                    "errMsg" => "质检单编号已存在"
                ];
            }
        }

        if(false === empty($purchase_order_num)){
            $purchaseOrderNumExists = $this->checkPurchaseOrderNumExists($purchase_order_num);

            if($purchaseOrderNumExists){
                return [
                    "errNo" => "1001",
                    "errMsg" => "采购单编号已存在"
                ];
            }
        }

        if(false === empty($contract_num)){
            $contractNumExists = $this->checkContractNumExists($contract_num);

            if($contractNumExists){
                return [
                    "errNo" => "1001",
                    "errMsg" => "合同编号已存在"
                ];
            }
        }

        $Dao = M('stockin_info');

        $Dao->startTrans();

        $data = [];
        $data['fk_staff_id'] = $staff_id;
        $data['fk_transactor_id'] = $transactor_id;
        $data['invoice_type'] = $invoice_type;
        $data['stockin_date'] = $stockin_date;
        $data['stockin_num'] = $this->getStockinNumByDate($stockin_date);
        $data['QC_Bill_Number'] = $QC_Bill_Number;
        $data['purchase_order_num'] = $purchase_order_num;
        $data['contract_num'] = $contract_num;
        $data['total_amount'] = array_sum(array_column($product_item, 'item_total_amount'));
        $data['createtime'] = date("Y-m-d H:i:s");

        $stockin_id = $Dao->add($data);

        if($add === false){
            $Dao->rollback();
            DLOG('入库单详情创建失败 error: ' . $Dao->getDBError(), 'error', 'stockin');
            return [
                'errNo' => '1003',
                'errMsg' => '入库单详情创建失败'
            ];
        }

        $detail_index = 0;

        $addStockinDetail = $this->addStockinDetail($stockin_id, $detail_index, $product_item);

        if(isset($addStockinDetail['errNo'])){
            $Dao->rollback();
            return $addStockinDetail;
        }
        
        $addStockInfo = $this->updateStockInfo($stockin_id, $product_item);

        if(isset($addStockInfo['errNo'])){
            $Dao->rollback();
            return $addStockInfo;
        }


        $addAssetInfo = $this->addAssetInfoByStockinDetail($stockin_id, $product_item);

        if(isset($addAssetInfo['errNo'])){
            $Dao->rollback();
            return $addAssetInfo;
        }

        $Dao->commit();

        return true;
    }

    /**
     * 创建固定资产记录
     * Author Raven
     * Date 2019-09-06
     * Params [params]
     * @param integer $stockin_id   [入库记录id]
     * @param array   $product_item [入库商品列表]
     */
    public function addAssetInfoByStockinDetail($stockin_id=0, $product_item=[])
    {
        $assetProduct = [];

        $categoryProdcutCnt = [];
        $assetCodeIndex = [];

        foreach ($product_item as $key => $value) {
            $value['product_index'] = $key;
            if($value['category_id'] > 0){
                $assetProduct[] = $value;
                $categoryProdcutCnt[$value['category_id']] += $value['item_quantity'];
                $assetCodeIndex[$value['category_id']] = 0;
            }
        }

        if(empty($assetProduct)){
            return true;
        }

        $detail_index = 0;

        $stockinDetail = $this->getStockDetailByStockinId($stockin_id, $detail_index, 'id');

        $assetCode = $this->createAssetCodeByCategoryId($categoryProdcutCnt);

        $assetData = [];

        foreach ($assetProduct as $key => $value) {

            for($i=0; $i<$value['item_quantity']; $i++){
                $category_id = $value['category_id'];
                $tmp_data = [];
                $tmp_data['fk_product_id'] = $value['product_id'];
                $tmp_data['fk_detail_id'] = $stockinDetail[$value['product_index']]['id'];
                $tmp_data['fk_room_id'] = $value['room_id'];
                $tmp_data['fk_category_id'] = $category_id;

                $tmpAssetCode = $assetCode[$category_id][$assetCodeIndex[$category_id]];
                $tmp_data['asset_code'] = $tmpAssetCode;
                $tmp_data['address_amount'] = $value['item_sale'];
                $tmp_data['asset_buy_date'] = $value['buy_date'];
                $tmp_data['asset_depreciation_time'] = $value['depreciation_time'];

                $assetCodeIndex[$category_id]++;
                $assetData[] = $tmp_data;
            }
        }

        $StockAssetModel = D('StockAsset');
        $addAsset = $StockAssetModel
            ->addStockAsset($assetData);

        if(empty($addAsset)){
            return [
                'errNo' => '1003',
                'errMsg' => '固定资产记录添加失败'
            ];
        }

        return true;
    }

    /**
     * 通过资产类别id列表生成资产编号
     * Author Raven
     * Date 2019-09-06
     * Params [params]
     * @param  array  $category_id [资产类别id]
     */
    public function createAssetCodeByCategoryId($category_id=[])
    {
        $Dao = M('stock_asset');
        $where = [
            'fk_category_id' => ['IN', array_keys($category_id)]
        ];

        $assetProductCnt = $Dao
            ->where($where)
            ->group('fk_category_id')
            ->getField('fk_category_id, count(*) as product_cnt');


        $categoryPrefix = $this->getCategoryCodePrefixByCagegoryId(array_keys($category_id));

        $res = [];
        foreach ($category_id as $key => $value) {
            $dbProductCnt = isset($assetProductCnt[$key]) ? $assetProductCnt[$key] : 0;
            if(false === isset($categoryPrefix[$key])){
                return [
                    'errNo' => '1003',
                    'errMsg' => '资产类别信息错误'
                ];
            }

            $category_prefix = $categoryPrefix[$key];
            $product_cnt = $value;
            $start_num = $dbProductCnt + 1;

            $res[$key] = $this->batchCreateAssetCode($category_prefix, $start_num, $product_cnt);
            
        }
        
        return $res;
    }

    /**
     * 批量生成固定资产编号
     * Author Raven
     * Date 2019-09-06
     * Params [params]
     * @param  string  $prefix_str [前缀字符串]
     * @param  integer $start_num  [开始的数值]
     * @param  integer $code_cnt   [生成的数量]
     */
    public function batchCreateAssetCode($prefix_str='', $start_num=0, $code_cnt=0)
    {
        $res = [];
        $for_end = $start_num + $code_cnt;

        for($i = $start_num; $i < $for_end; $i++){
            $res[] = $prefix_str . '_' . str_pad($i, 4, 0, STR_PAD_LEFT);
        }

        return $res;
    }

    /**
     * 通过资产类别id获取资产编号前缀
     * Author Raven
     * Date 2019-09-06
     * Params [params]
     * @param  array  $category_id [资产类别id列表]
     */
    public function getCategoryCodePrefixByCagegoryId($category_id=[])
    {
        $Dao = M('stock_category');

        $where = [
            'id' => ['IN', $category_id]
        ];

        $res = $Dao
            ->where($where)
            ->getField('id, category_code');
        return $res;
    }

    /**
     * 通过id获取入库记录信息 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param  integer $stockin_id [入库记录id]
     * @param  string  $field      [查询字段]
     */
    public function getStockinInfoById($stockin_id=0, $field='*')
    {
        $Dao = M('stockin_info');

        $where = [
            'id' => $stockin_id
        ];

        $res = $Dao
            ->field($field)
            ->where($where)
            ->find();
        return $res;
    }

    /**
     * 更新入库信息 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param  integer $stockin_id         [入库记录id]
     * @param  integer $staff_id           [入库人id]
     * @param  integer $transactor_id      [经办人id]
     * @param  integer $invoice_type       [发票类型]
     * @param  string  $QC_Bill_Number     [质检单编号]
     * @param  string  $purchase_order_num [采购单编号]
     * @param  string  $contract_num       [合同编号]
     * @param  array   $product_item       [入库商品列表]
     */
    public function updateStockinInfo($stockin_id=0, $staff_id=0, $transactor_id=0, $invoice_type=0, $QC_Bill_Number='', $purchase_order_num='', $contract_num='', $product_item=[])
    {
        $stockinInfo = $this->getStockinInfoById($stockin_id, 'QC_Bill_Number, purchase_order_num, contract_num');

        if(empty($stockinInfo)){
            return [
                'errNo' => '1001',
                'errMsg' => '入库信息不存在'
            ];
        }

        if(false === empty($QC_Bill_Number) && $stockinInfo['QC_Bill_Number'] != $QC_Bill_Number){
            $QCBillNumberExists = $this->checkQCBillNumberExists($QC_Bill_Number);

            if($QCBillNumberExists){
                return [
                    "errNo" => "1001",
                    "errMsg" => "质检单编号已存在"
                ];
            }
        }

        if(false === empty($purchase_order_num) && $stockinInfo['purchase_order_num'] != $purchase_order_num){
            $purchaseOrderNumExists = $this->checkPurchaseOrderNumExists($purchase_order_num);

            if($purchaseOrderNumExists){
                return [
                    "errNo" => "1001",
                    "errMsg" => "采购单编号已存在"
                ];
            }
        }

        if(false === empty($contract_num) && $stockinInfo['contract_num'] != $contract_num){
            $contractNumExists = $this->checkContractNumExists($contract_num);

            if($contractNumExists){
                return [
                    "errNo" => "1001",
                    "errMsg" => "合同编号已存在"
                ];
            }
        }

        $Dao = M('stockin_info');

        $Dao->startTrans();
        $data = [];
        $data['fk_staff_id'] = $staff_id;
        $data['fk_transactor_id'] = $transactor_id;
        $data['invoice_type'] = $invoice_type;
        $data['QC_Bill_Number'] = $QC_Bill_Number;
        $data['purchase_order_num'] = $purchase_order_num;
        $data['contract_num'] = $contract_num;

        $where = [
            'id' => $stockin_id
        ];

        $save = $Dao
            ->where($where)
            ->save($data);

        if($save === false){
            $Dao->rollback();
            DLOG('入库信息保存失败 error: ' . $Dao->getDBError(), 'error', 'stockin');
            return [
                'errNo' => '1003',
                'errMsg' => '入库信息保存失败'
            ];
        }

        $max_detail_index = $this->getMaxDetailIndexByStockinId($stockin_id);
        $detail_index = $max_detail_index + 1;

        $addStockinDetail = $this->addStockinDetail($stockin_id, $detail_index, $product_item);

        if(isset($addStockinDetail['errNo'])){
            $Dao->rollback();
            return $addStockinDetail;
        }


        $Dao->commit();
        return true;
    }

    /**
     * 通过入库id获取最大的明细下标 
     * Author Raven
     * Date 2019-09-06
     * Params [params]
     * @param  integer $stockin_id [入库单id]
     */
    public function getMaxDetailIndexByStockinId($stockin_id=0)
    {
        $Dao = M('stockin_detail');

        $where = [
            'fk_stockin_id' => $stockin_id
        ];

        $res = $Dao
            ->where($where)
            ->max('detail_index');

        return $res;
    }

    /**
     * 设置员工信息到入库记录 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param array $stockin_list [入库记录]
     */
    public function setStaffInfoByStockinList($stockin_list=[])
    {
        $StaffModel = D('Staff');

        $staffId = array_unique(
            array_merge(
                array_column($stockin_list, 'fk_staff_id'),
                array_column($stockin_list, 'fk_transactor_id')
            )
        );

        $staffInfo = $StaffModel->getStaffInfoByIdList($staffId, 'id, staff_name, staff_NO');

        foreach ($stockin_list as $key => $value) {
            $staff_name = '-';
            $transactor_name = '-';
            if(isset($staffInfo[$value['fk_staff_id']])){
                $staff_name = $staffInfo[$value['fk_staff_id']]['staff_name'];
            }

            if(isset($staffInfo[$value['fk_transactor_id']])){
                $transactor_name = $staffInfo[$value['fk_transactor_id']]['staff_name'];
            }

            $stockin_list[$key]['staff_name'] = $staff_name;
            $stockin_list[$key]['transactor_name'] = $transactor_name;


        }

        return $stockin_list;
    }

    /**
     * 设置发票信息到入库记录列表
     * Author Raven
     * Date 2019-09-04
     * Params [params]
     * @param array $stockin_list [description]
     */
    public function setInvoiceInfoByStockinList($stockin_list=[])
    {
        $invoiceId = array_unique(array_column($stockin_list, 'fk_invoice_id'));

        $InvoiceModel = D('Invoice');
        $invoiceAmount = $InvoiceModel->getInvoiceAmountByIdList($invoiceId);

        foreach ($stockin_list as $key => $value) {
            $stockin_list[$key]['invoice_amount'] = isset($invoiceAmount[$value['fk_invoice_id']]) ? $invoiceAmount[$value['fk_invoice_id']] : '0';
        }
        return $stockin_list;
    }

    /**
     * 获取入库记录列表 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param  integer $page_num [当前页码]
     */
    public function getStockinList($page_num=1)
    {
        $where = [];
        if(false === empty($_GET['staff_id'])){
            $where['fk_staff_id'] = intval($_GET['staff_id']);
        }

        if(false === empty($_GET['invoice_type'])){
            $where['invoice_type'] = intval($_GET['invoice_type']);
        }

        if(false === empty($_GET['transactor_id'])){
            $where['fk_transactor_id'] = intval($_GET['transactor_id']);
        }

        if(false === empty($_GET['stockin_num'])){
            $where['stockin_num'] = [
                'LIKE', '%' . trim($_GET['stockin_num']) . '%'
            ];
        }

        if (false === empty($_GET['stockin_date_start']) && false === empty($_GET['stockin_date_end'])) {
            $where['_string'] = sprintf("stockin_date >= '%s' and stockin_date <= '%s'", trim($_GET['stockin_date_start']), trim($_GET['stockin_date_end']));
        }

        $Dao = M('stockin_info');

        $list = $Dao
            ->field("id, invoice_type, fk_staff_id, fk_transactor_id, stockin_date, stockin_num, total_amount, createtime")
            ->where($where)
            ->order('id desc')
            ->page($page_num.', 25')
            ->select();

        $list = $this->setStaffInfoByStockinList($list);

        $count = $Dao
            ->where($where)
            ->count();// 查询满足要求的总记录数
        $Page = new \COM\Page($count, 25);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme',"<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出


        foreach ($list as $key => $value) {
            $list[$key]['total_amount'] = number_format($value['total_amount'] / 100, 2);
        }

        $res = array(
            "list" => $list,
            "page" => $show,
        );
        return $res;
    }

    /**
     * 通过入库记录id获取入库明细 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param  integer $stockin_id   [入库记录id]
     * @param  integer $detail_index [入库记录下标]
     * @param  string  $field        [查询的字段]
     */
    public function getStockDetailByStockinId($stockin_id=0, $detail_index=0, $field='*')
    {
        $Dao = M('stockin_detail');

        $where = [
            'fk_stockin_id' => $stockin_id,
            'detail_index' => $detail_index
        ];

        $stockinDetail = $Dao
            ->field($field)
            ->where($where)
            ->select();

        return $stockinDetail;
    }

    /**
     * 设置入库仓库信息到入库明细 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param array $stockin_detail [入库明细]
     */
    public function setStockRoomNameByStockinDetail($stockin_detail = [])
    {
        $roomId = array_unique(array_column($stockin_detail, 'fk_room_id'));

        $StockRoomModel = D('StockRoom');

        $stockRoomInfo = $StockRoomModel->getRoomInfoByRoomIdList($roomId, 'id, room_name');

        foreach ($stockin_detail as $key => $value) {
            $room_name = '-';

            if(isset($stockRoomInfo[$value['fk_room_id']])){
                $room_name = $stockRoomInfo[$value['fk_room_id']];
            }

            $stockin_detail[$key]['room_name'] = $room_name;
        }

        return $stockin_detail;
    }

    /**
     * 设置存放地点信息 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param array $stockin_detail [入库明细]
     */
    public function setStockAddressNameByStockinDetail($stockin_detail = [])
    {
        $addressId = array_unique(array_column($stockin_detail, 'fk_address_id'));

        $StockAddressModel = D('StockAddress');

        $stockAddressInfo = $StockAddressModel->getAddressInfoByAddressIdList($addressId, 'id, address_name');

        foreach ($stockin_detail as $key => $value) {
            $address_name = '-';

            if(isset($stockAddressInfo[$value['fk_address_id']])){
                $address_name = $stockAddressInfo[$value['fk_address_id']];
            }

            $stockin_detail[$key]['address_name'] = $address_name;

        }

        return $stockin_detail;
    }

    /**
     * 设置商品信息到入库明细 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param array $stockin_detail [入库明细]
     */
    public function setProdcutInfoToStockinDetail($stockin_detail = [])
    {
        $productId = array_unique(array_column($stockin_detail, 'fk_product_id'));

        $StcokProductModel = D('StockProduct');

        $productInfo = $StcokProductModel->getProductInfoByProductIdList($productId, 'id, fk_project_id, product_NO, product_name, part_number, product_unit');
        
        foreach ($stockin_detail as $key => $value) {
            $fk_project_id = 0;
            $product_NO = '-';
            $product_name = '-';
            $part_number = '-';
            $product_unit = '-';

            if(isset($productInfo[$value['fk_product_id']])){
                $fk_project_id = $productInfo[$value['fk_product_id']]['fk_project_id'];
                $product_NO = $productInfo[$value['fk_product_id']]['product_NO'];
                $product_name = $productInfo[$value['fk_product_id']]['product_name'];
                $part_number = $productInfo[$value['fk_product_id']]['part_number'];
                $product_unit = $productInfo[$value['fk_product_id']]['product_unit'];
            }

            $stockin_detail[$key]['fk_project_id'] = $fk_project_id;
            $stockin_detail[$key]['product_NO'] = $product_NO;
            $stockin_detail[$key]['product_name'] = $product_name;
            $stockin_detail[$key]['part_number'] = $part_number;
            $stockin_detail[$key]['product_unit'] = $product_unit;

        }

        return $stockin_detail;
    }

    /**
     * 设置项目信息 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param array $stockin_detail [入库明细]
     */
    public function setProjectInfo($stockin_detail=[])
    {
        $projectId = array_unique(array_column($stockin_detail, 'fk_project_id'));

        $ProjectModel = D('Project');
        $projectInfo = $ProjectModel
            ->getProjectNameByIdList($projectId);

        foreach ($stockin_detail as $key => $value) {
            $project_name = '-';
            $project_code = '-';
            if(isset($projectInfo[$value['fk_project_id']])){
                $project_name = $projectInfo[$value['fk_project_id']]['project_name'];
                $project_code = $projectInfo[$value['fk_project_id']]['project_code'];
            }

            $stockin_detail[$key]['project_name'] = $project_name;
            $stockin_detail[$key]['project_code'] = $project_code;

            unset($stockin_detail[$key]['fk_project_id']);
        }

        return $stockin_detail;
    }

    /**
     * 格式化入库明细金额信息 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param  array  $stockin_detail [入库明细]
     */
    public function formatStockDetailAmount($stockin_detail=[])
    {
        foreach ($stockin_detail as $key => $value) {
            $stockin_detail[$key]['detail_sale'] = number_format($value['detail_sale'] / 100, 2);
            $stockin_detail[$key]['detail_total_amount'] = number_format($value['detail_total_amount'] / 100, 2);
            $stockin_detail[$key]['invoice_amount'] = number_format($value['invoice_amount'] / 100, 2);
        }

        return $stockin_detail;
    }


    /**
     * 通过发票id获取发票金额
     * Author Raven
     * Date 2019-09-03
     * Params [params]
     * @param  [integer] $invoice_id [发票id]
     */
    public function getInvoiceAmountByInvoiceId($invoice_id=0)
    {
        if(empty($invoice_id)){
            return '';
        }

        $InvoiceModel = D("Invoice");

        $invoiceInfo = $InvoiceModel->getInvoiceInfoById($invoice_id, 'invoice_amount');

        if(empty($invoiceInfo)){
            return '';
        }

        $invoice_amount = strval($invoiceInfo['invoice_amount'] * 100);

        return number_format($invoice_amount / 100, 2);
    }

    /**
     * 获取编辑入库详情id 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param  integer $stockin_id [入库id]
     */
    public function getStockinEditInfo($stockin_id = 0)
    {
        $Dao = M('stockin_info');

        $where = [
            'id' => $stockin_id
        ];

        $stockinInfo = $Dao
            ->field('id, fk_staff_id, fk_transactor_id, invoice_type, stockin_date, stockin_num, QC_Bill_Number, purchase_order_num, contract_num, total_amount')
            ->where($where)
            ->find();

        if(empty($stockinInfo)){
            return [];
        }

        $stockinInfo = $this->setStaffInfoByStockinList([$stockinInfo]);
        $stockinInfo = $stockinInfo[0];


        $stockinInfo['zh_total_amount'] = num_to_rmb($stockinInfo['total_amount'] / 100);
        $stockinInfo['total_amount'] = number_format($stockinInfo['total_amount'] / 100, 2);

        $detail_index = $this->getMaxDetailIndexByStockinId($stockin_id);


        $stockinDetail = $this->getStockDetailByStockinId($stockin_id, $detail_index, 'fk_product_id, fk_room_id, fk_address_id, fk_invoice_id, detail_quantity, detail_sale, detail_total_amount');
        $stockinDetail = $this->setStockRoomNameByStockinDetail($stockinDetail);
        $stockinDetail = $this->setStockAddressNameByStockinDetail($stockinDetail);
        $stockinDetail = $this->setProdcutInfoToStockinDetail($stockinDetail);
        $stockinDetail = $this->setProjectInfo($stockinDetail);
        $stockinDetail = $this->setInvoiceInfoByStockinList($stockinDetail);
        $stockinDetail = $this->formatStockDetailAmount($stockinDetail);

        
        $res = [
            'stockin_info' => $stockinInfo,
            'stockin_detail' => $stockinDetail,
        ];

        return $res;
    }

    /**
     * 格式化发票列表 
     * Author Raven
     * Date 2019-09-03
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
     * 获取已选择发票列表 
     * Author Raven
     * Date 2019-09-03
     * Params [params]
     * @param  string  $invoice_id [发票记录id]
     */
    public function getSelectInvoiceList($invoice_id='')
    {
        $where = array();
        if(false == empty($invoice_id)){
            $where['id'] = $invoice_id;
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
     * Date 2019-09-03
     * Params [params]
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
                $bind_status = "BIND";
            }

            $invoice_list[$key]['bind_status'] = $bind_status;
        }

        return $invoice_list;

    }

    /**
     * 获取发票列表 
     * Author Raven
     * Date 2019-08-06
     * Params [params]
     * @param  integer $page_num   [当前页码]
     * @param  array   $invoice_id [已选发票记录列表]
     */
    public function getInvoiceList($page_num = 0, $invoice_id=[], $detail_id=0)
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
}
