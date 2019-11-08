<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * 系统配文件
 * 所有系统级别的配置
 */
return array(
    /* 模块相关配置 */
    'AUTOLOAD_NAMESPACE' => array('Addons' => ONETHINK_ADDON_PATH), //扩展模块列表
    'DEFAULT_MODULE'     => 'Admin',
    'MODULE_DENY_LIST'   => array('Common', 'User'),

    /* 系统数据加密设置 */
    'DATA_AUTH_KEY' => 'Whm|4!^JeE</KbwVG=&"tN(MAdgR_~un;?Y1@fZ>', //默认数据加密KEY

    /* 调试配置 */
    'SHOW_PAGE_TRACE' => false,

    /* 用户相关设置 */
    'USER_MAX_CACHE'     => 1000, //最大缓存用户数
    'USER_ADMINISTRATOR' => 1, //管理员用户ID

    /* URL配置 */
    'URL_CASE_INSENSITIVE' => true, //默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'            => 3, //URL模式
    'VAR_URL_PARAMS'       => '', // PATHINFO URL参数变量
    'URL_PATHINFO_DEPR'    => '/', //PATHINFO URL分割符

    /* 全局过滤配置 */
    'DEFAULT_FILTER' => '', //全局过滤函数

    /* 数据库配置 */
    'DB_TYPE'   => 'mysqli', // 数据库类型
    'DB_HOST'   => '127.0.0.1', // 服务器地址
    'DB_NAME'   => 'invoice', // 数据库名
    'DB_USER'   => 'invoice', // 用户名
    'DB_PWD'    => 'vRWBfJRxDVd1XAlM',  // 密码
    'DB_PORT'   => '3306', // 端口
    'DB_PREFIX' => 'onethink_', // 数据库表前缀

    /* 文档模型配置 (文档模型核心配置，请勿更改) */
    'DLOG_LEVEL' => array('error','run','fatal'),
    'DLOG_DIR' => './log/',
    'DOCUMENT_MODEL_TYPE' => array(2 => '主题', 1 => '目录', 3 => '段落'),
    "redis_conf" => array(
        'host' => '127.0.0.1', 
        'port' => 6379, 
        'pswd' => '7ejBNP6MwHzgZ1GC', 
        'timeout' => 1
    ),
    'SNAPSHOT_PATH' => './snapshot/', // 快照保存路径

    // 部门
    'STAFF_DEPARTMENT' => [
        '1' => '研发部',
        '2' => '运营部',
        '3' => '其他'
    ],

    // 性别
    'STAFF_GENDER' => [
        '1' => '男',
        '2' => '女'
    ],

    // 学历
    'STAFF_EDUCATION' => [
        '1' => '小学',
        '2' => '初中',
        '3' => '高中',
        '4' => '高职',
        '5' => '大专',
        '6' => '本科',
        '7' => '硕士研究生',
        '8' => '博士',
        '9' => '其他'
    ],
    
    // 户口性质
    'STAFF_ACCOUNT_TYPE' => [
        '1' => '城镇',
        '2' => '农村'
    ],

    // 用工形式
    'STAFF_TYPE' => [
        '1' => '固定期限',
        '2' => '实习生',
        '3' => '劳务派遣'
    ],

    // 员工状态
    'STAFF_STATUS' => [
        '1' => '在职',
        '2' => '离职',
        '3' => '病假',
        '4' => '事假'
    ],
    'TRAVEL' => [
       'DEFAULT_ROW_CNT' => 5, //默认差旅费明细条数 
    ], 
    'TAXI_TYPE' => [
       '1' => '出租车', // 出租车
       '2' => '滴滴', // 滴滴
    ], 
    'STOCK' => [
        'DEFAULT_STAFF' => 1, // 入库默认员工
        'INVOICE_TYPE' => [
            '1' => '增值税专用票',
            '2' => '增值税普通发票',
        ],
        'DEPRECIATION_TIME' => [ // 折旧年限
            '1' => '1年',
            '2' => '2年',
            '3' => '3年',
            '4' => '4年',
            '6' => '5年',
        ],
    ], 
);

