<?php
namespace Admin\Model;
use Think\Model;

class StockRoomModel extends Model {

    /*
      CREATE TABLE `onethink_stock_room` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `room_name` varchar(50) NOT NULL DEFAULT '' COMMENT '仓库名称',
      `room_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '仓库状态 1-正常 2-删除',
      `createtime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '数据创建时间',
      `updatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '数据最后更新时间',
      PRIMARY KEY (`id`),
      UNIQUE KEY `room_name` (`room_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='仓库信息'
     */
    
    /**
     * 创建仓库 
     * Author Raven
     * Date 2019-08-28
     * Params [params]
     * @param string $room_name [仓库名称]
     */
    public function addStockRoom($room_name='')
    {
        $roomNameExists = $this->checkRoomNameExists($room_name);

        if($roomNameExists){
            return [
                'errNo' => '1001',
                'errMsg' => '仓库名已存在'
            ];
        }

        $Dao = M('stock_room');

        $data = [];
        $data['room_name'] = $room_name;
        $data['createtime'] = date("Y-m-d H:i:s");

        $add = $Dao->add($data);

        if($add === false){
            DLOG('仓库创建失败 error: ' . $Dao->getDBError(), 'error', 'stock_room');
            return [
                'errNo' => '1003',
                'errMsg' => '仓库创建失败'
            ];
        }

        return $add;
    }

    /**
     * 修改仓库名称
     * Author Raven
     * Date 2019-08-28
     * Params [params]
     * @param  integer $id        [记录id]
     * @param  string  $room_name [仓库名称]
     */
    public function updateStockRoomById($id=0, $room_name='')
    {
        $stockRoomInfo = $this->getStockRoomInfoById($id, 'room_name');

        if(empty($stockRoomInfo)){
            return [
                'errNo' => '1001',
                'errMsg' => '仓库信息不存在'
            ];
        }

        if($stockRoomInfo['room_name'] != $room_name){
            $roomNameExists = $this->checkRoomNameExists($room_name);
            if($roomNameExists){
                return [
                    'errNo' => '1001',
                    'errMsg' => '仓库名已存在'
                ];
            }
        }

        $Dao = M('stock_room');

        $data = [];
        $data['room_name'] = $room_name;

        $where = [
            'id' => $id
        ];

        $save = $Dao
            ->where($where)
            ->save($data);

        if($save === false){
            DLOG('仓库信息保存失败 error: ' . $Dao->getDBError(), 'error', 'stock_room');
            return [
                'errNo' => '1003',
                'errMsg' => '仓库信息保存失败'
            ];
        }

        return $save;
    }

    /**
     * 获取仓库信息 
     * Author Raven
     * Date 2019-08-28
     * Params [params]
     * @param  integer $id    [仓库id]
     * @param  string  $field [查询的字段]
     */
    public function getStockRoomInfoById($id=0, $field='*')
    {
        $where = [
            'id' => $id
        ];

        $Dao = M('stock_room');

        $stockRoomInfo = $Dao
            ->field($field)
            ->where($where)
            ->find();

        return $stockRoomInfo;
    }

    /**
     * 获取仓库列表 
     * Author Raven
     * Date 2019-08-28
     */
    public function getStockRoomList()
    {
        $Dao = M('stock_room');

        $where = [
            'room_status' => 1
        ];

        $stockRoomList = $Dao
            ->field('id, room_name')
            ->where($where)
            ->select();

        return $stockRoomList;
    }
    
    /**
     * 获取仓库列表 
     * Author Raven
     * Date 2019-08-28
     */
    public function getStockRoomListInIds($room_ids=[])
    {
        $Dao = M('stock_room');

        $where = [
            'IN', $room_ids
        ];

        $stockRoomList = $Dao
            ->field('id, room_name')
            ->where($where)
            ->select();

        return $stockRoomList;
    }

    /**
     * 查询仓库信息列表 
     * Author Raven
     * Date 2019-09-02
     * Params [params]
     * @param  array  $room_id [仓库id列表]
     * @param  string $field   [查询的字段]
     */
    public function getRoomInfoByRoomIdList($room_id=[], $field='*')
    {
        $Dao = M('stock_room');

        $where = [
            'id' => [
                'IN', $room_id
            ]
        ];

        $res = $Dao
            ->where($where)
            ->getField($field);
        return $res;
    }

    /**
     * 检查仓库名称是否存在 
     * Author Raven
     * Date 2019-08-28
     * Params [params]
     * @param  string $room_name [仓库名称]
     */
    public function checkRoomNameExists($room_name='')
    {
        $Dao = M('stock_room');

        $where = [
            'room_name' => $room_name
        ];

        $count = $Dao
            ->where($where)
            ->count();

        return $count > 0 ? true : false;
    }

    public function test()
    {
        $room_name = '低值易耗品';

        // $res = $this->addStockRoom($room_name);

        $id = 2;
        // $res = $this->updateStockRoomById($id, $room_name);
        
        $res = $this->getStockRoomList();
        
        var_dump($res);
        exit();
    }
}
