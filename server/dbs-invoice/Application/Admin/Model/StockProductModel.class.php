<?php
namespace Admin\Model;
use Think\Model;

class StockProductModel extends Model {

    /*
      CREATE TABLE `onethink_stock_product` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `fk_project_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联的项目id',
      `product_NO` varchar(50) NOT NULL DEFAULT '' COMMENT '物料编号（商品编号）',
      `product_name` varchar(50) NOT NULL DEFAULT '' COMMENT '物料名称（商品名称）',
      `part_number` varchar(50) NOT NULL DEFAULT '' COMMENT '规格型号',
      `product_unit` char(5) NOT NULL DEFAULT '' COMMENT '物品单位 例：单、个、只、台',
      `stock_quantity` int(11) NOT NULL DEFAULT '0' COMMENT '当前物品总库存数',
      `product_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '商品状态 1-正常 2-删除',
      `createtime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
      `updatetime` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '数据最后更新时间',
      PRIMARY KEY (`id`),
      UNIQUE KEY `product_NO` (`product_NO`),
      KEY `fk_project_id` (`fk_project_id`),
      KEY `product_name` (`product_name`)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='库存物品'
     */
    
    /**
     * 添加商品信息 
     * Author Raven
     * Date 2019-08-29
     * Params [params]
     * @param integer $project_id   [项目id]
     * @param string  $product_name [商品名称]
     * @param string  $part_number  [商品型号]
     * @param string  $product_unit [商品单位]
     */
    public function addProductInfo($project_id=0, $product_name='', $part_number='', $product_unit='')
    {
        $product_NO = $this->getProductNoByProjectId($project_id);

        if(isset($product_NO['errNo'])){
            return $product_NO;
        }

        $Dao = M('stock_product');

        $data = [];
        $data['fk_project_id'] = $project_id;
        $data['product_NO'] = $product_NO;
        $data['product_name'] = $product_name;
        $data['part_number'] = $part_number;
        $data['product_unit'] = $product_unit;
        $data['createtime'] = date('Y-m-d H:i:s');

        $add = $Dao->add($data);

        if($add === false){
            DLOG('商品创建失败 error: ' . $Dao->getDBError(), 'error', 'stock_product');
            return [
                'errNo' => '1003',
                'errMsg' => '商品创建失败'
            ];
        }

        return $add;
    }


    /**
     * 获取项目列表
     * Author Raven
     * Date 2019-08-29
     */
    public function getProjectList()
    {
        $Dao = M("project");

        $field = "id, project_name, product_NO_prefix";

        $projectList = $Dao
            ->field($field)
            ->select();
        $res = [];

        foreach ($projectList as $key => $value) {
            if(false == empty($value['product_NO_prefix'])){
                $res[] = $value;
            }
        }

        return $res;
    }

    /**
     * 通过项目id获取商品编号 
     * Author Raven
     * Date 2019-08-29
     * Params [params]
     * @param  integer $project_id [项目id]
     */
    public function getProductNoByProjectId($project_id=0)
    {
        $product_NO_prefix = $this->getProductPrefixByProjectId($project_id);

        if(empty($product_NO_prefix)){
            return [
                'errNo' => '1001',
                'errMsg' => '编号前缀获取失败'
            ];
        }

        $product_cnt = $this->getProductCntByProjectId($project_id);
        $product_cnt += 1;

        $productNo = str_pad($product_cnt, 3, '0', STR_PAD_LEFT);

        $res = sprintf("%s_%s", $product_NO_prefix, $productNo);
        return $res;
    }

    /**
     * 通过项目名称获取商品编号前缀 
     * Author Raven
     * Date 2019-08-29
     * Params [params]
     * @param  integer $project_id [项目id]
     */
    public function getProductPrefixByProjectId($project_id=0)
    {
        $ProjectModel = D("Project");

        $projectInfo = $ProjectModel
            ->getProjectInfoById($project_id, 'product_NO_prefix');

        if(empty($projectInfo) or empty($projectInfo['product_NO_prefix'])){
            return false;
        }

        return $projectInfo['product_NO_prefix'];
    }

    /**
     * 通过项目名称获取商品数量 
     * Author Raven
     * Date 2019-08-29
     * Params [params]
     * @param  integer $project_id [项目id]
     */
    public function getProductCntByProjectId($project_id=0)
    {
        $Dao = M("stock_product");

        $where = [
            'fk_project_id' => $project_id
        ];

        $cnt = $Dao
            ->where($where)
            ->count();

        return $cnt;
    }

    /**
     * 获取商品信息
     * Author Raven
     * Date 2019-08-29
     * Params [params]
     * @param  integer $product_id [商品id]
     * @param  string  $field      [查询的字段]
     */
    public function getProductInfoById($product_id=0, $field='*')
    {
        $where = [
            'id' => $product_id
        ];

        $Dao = M('stock_product');

        $productInfo = $Dao
            ->field($field)
            ->where($where)
            ->find();

        if(empty($productInfo)){
            return false;
        }

        return $productInfo;
    }

    /**
     * 更新商品信息 
     * Author Raven
     * Date 2019-08-29
     * Params [params]
     * @param  integer $product_id   [商品id]
     * @param  string  $product_name [商品名称]
     * @param  string  $part_number  [商品型号]
     * @param  string  $product_unit [商品单位]
     */
    public function updateProductInfo($product_id=0, $product_name='', $part_number='', $product_unit='')
    {
        $data = [];
        $data['product_name'] = $product_name;
        $data['part_number'] = $part_number;
        $data['product_unit'] = $product_unit;

        $where = [
            'id' => $product_id
        ];

        $Dao = M('stock_product');

        $save = $Dao
            ->where($where)
            ->save($data);

        if($save === false){
            DLOG('商品信息保存失败 error: ' . $Dao->getDBError(), 'error', 'stock_product');
            return [
                'errNo' => '1003',
                'errMsg' => '商品信息保存失败'
            ];
        }

        return true;
    }

    /**
     * 获取商品列表
     * Author Raven
     * Date 2019-08-29
     * Params [params]
     * @param  integer $page_num [当前页码]
     */
    public function getProductList($page_num=0)
    {
        $where = [];

        if(false == empty($_GET['project_id'])){
            $where['fk_project_id'] = intval($_GET['project_id']);
        }

        if(false == empty($_GET['product_name'])){
            $where['product_name'] = [
                'LIKE', "%" . trim($_GET['product_name']) . "%"
            ];
        }

        if(false == empty($_GET['part_number'])){
            $where['part_number'] = [
                'LIKE', "%" . trim($_GET['part_number']) . "%"
            ];
        }

        if(false == empty($_GET['product_NO'])){
            $where['product_NO'] = [
                'LIKE', "%" . trim($_GET['product_NO']) . "%"
            ];
        }

        $Dao = M('stock_product');

        $list = $Dao
            ->field("id, fk_category_id, fk_project_id, product_NO, product_name, part_number, product_unit, stock_quantity, used_quantity, createtime")
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

        $list = $this->setProjectInfo($list);
        $list = $this->setCategoryInfo($list);
        $res = array(
            "list" => $list,
            "page" => $show,
        );
        return $res;
    }

    /**
     * 根据产品名称获取产品列表
     * Author liminggang
     * Params [params]
     * @param string $product_name [产品名称]
     */
    public function getProductListByName($product_name='')
    {
        if(false == empty($_GET['product_name'])){
            $where['product_name'] = [
                'LIKE', "%" . trim($product_name) . "%"
            ];
        }

        $Dao = M('stock_product');

        $ids = $Dao
            ->field("id")
            ->where($where)
            ->select();
        return $ids;
    }

    /**
     * 根据产品名称获取产品列表
     * Author liminggang
     * Params [params]
     * @param string $product_name [产品名称]
     */
    public function getProductListInIds($product_ids=[])
    {
        if(false == empty($_GET['product_name'])){
            $where['id'] = [
                'IN',  $product_ids
            ];
        }

        $Dao = M('stock_product');

        $ids = $Dao
            ->field("id, fk_category_id, fk_project_id, product_NO, product_name, part_number, product_unit, stock_quantity, used_quantity, createtime")
            ->where($where)
            ->select();
        return $ids;
    }

    /**
     * 设置项目信息 
     * Author Raven
     * Date 2019-08-29
     * Params [params]
     * @param array $product_list [发票申请列表]
     */
    public function setProjectInfo($product_list=[])
    {
        $projectId = array_unique(array_column($product_list, 'fk_project_id'));

        $ProjectModel = D('Project');
        $projectInfo = $ProjectModel
            ->getProjectNameByIdList($projectId);

        foreach ($product_list as $key => $value) {
            $project_name = '-';
            $project_code = '-';
            if(isset($projectInfo[$value['fk_project_id']])){
                $project_name = $projectInfo[$value['fk_project_id']]['project_name'];
                $project_code = $projectInfo[$value['fk_project_id']]['project_code'];
            }

            $product_list[$key]['project_name'] = $project_name;
            $product_list[$key]['project_code'] = $project_code;

            unset($product_list[$key]['fk_project_id']);
        }

        return $product_list;
    }

    /**
     * 设置资产类别信息 
     * Author Raven
     * Date 2019-09-06
     * Params [params]
     * @param array $product_list [发票申请列表]
     */
    public function setCategoryInfo($product_list=[])
    {
        $categoryId = array_unique(array_column($product_list, 'fk_category_id'));

        $StockCategoryModel = D('StockCategory');
        $categoryInfo = $StockCategoryModel
            ->getCategoryNameByIdList($categoryId);

        foreach ($product_list as $key => $value) {
            $category_name = '-';
            if(isset($categoryInfo[$value['fk_category_id']])){
                $category_name = $categoryInfo[$value['fk_category_id']];
            }

            $product_list[$key]['category_name'] = $category_name;
        }

        return $product_list;
    }

    /**
     * 查询商品信息列表 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param  array  $product_id [商品id列表]
     * @param  string $field      [查询的字段]
     */
    public function getProductInfoByProductIdList($product_id=[], $field='*')
    {
        $Dao = M('stock_product');

        $where = [
            'id' => [
                'IN', $product_id
            ]
        ];

        $res = $Dao
            ->where($where)
            ->getField($field);
        return $res;
    }

    public function test()
    {
        // $res = $this->getProjectList();

        $project_id = 8;
        // $res = $this->getProductNoByProjectId($project_id);
        // 
        $project_id = 8;
        $product_name = '苹果手机 iPhone Xs Max 256G';
        $part_number = 'MT752CH/A';
        $product_unit = '台';

        // $res = $this->addProductInfo($project_id, $product_name, $part_number, $product_unit);

        $product_id = 1;
        $field = 'id, fk_project_id, product_NO, product_name, part_number, product_unit, stock_quantity';

        // $res = $this->getProductInfoById($product_id, $field);


        // $res = $this->updateProductInfo($product_id, $product_name, $part_number, $product_unit);
        
        $page_num = 1;
        // $_GET['project_id'] = 8;
        // $_GET['product_name'] = '笔记本';
        // $_GET['part_number'] = '';
        // $_GET['product_NO'] = 'YF_KC_002';
        $res = $this->getProductList($page_num);
        var_dump($res);
        exit();
    }
}
