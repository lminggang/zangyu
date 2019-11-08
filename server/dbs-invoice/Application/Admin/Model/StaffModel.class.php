<?php
namespace Admin\Model;
use Think\Model;

class StaffModel extends SnapshotModel {

    public function __construct(){
        parent::__construct();

        $this->snapshot_type = 2; //快照类型 2-员工管理
        $this->snapshot_folder = 'staff'; //快照保存路径
        $this->INTEERN = '2';
    }

    /**
     * 添加员工
     * Author Raven
     * Date 2019-08-01
     * Params [params]
     * @param  array $staff_base      [员工基本信息]
     * @param  array $staff_contact   [员工联系方式]
     * @param  array $staff_position  [员工职位信息]
     * @param  array $staff_card      [员工银行卡信息]
     * @param  array $staff_school    [员工学历信息]
     */
    public function addStaff($staff_base=[], $staff_contact=[], $staff_position=[], $staff_card=[], $staff_school=[])
    {

        if (!empty($this->get_staff_no($staff_base["staff_NO"]))){
            return [
                'errNo' => '1003',
                'errMsg' => '当前员工编号已存在'
            ];
        }

        if (!empty($this->get_staff_idcard($staff_base["staff_idcard_num"]))){
            return [
                'errNo' => '1003',
                'errMsg' => '当前员工身份证号已存在'
            ];
        }


        $stff_data = array_merge($staff_base, $staff_contact, $staff_position, $staff_card);
        $stff_data['createtime'] =  date("Y-m-d H:i:s");
        $Dao = M('staff_info');
        $Dao->startTrans();
        
        $add = $Dao->add($stff_data);

        if($add === false){
            DLOG('添加员工失败 error: ' . $Dao->getDBError(), 'error', 'staff');
            return [
                'errNo' => '1003',
                'errMsg' => '添加员工失败'
            ];
            $Dao->rollback();
        }
        
        $staff_school_data = $this->createStaffSchool($add, $staff_school);
        $SchoolDao = M('staff_school');
        if (!empty($staff_school_data)) {
            $school_add = $SchoolDao->addAll($staff_school_data);

            if($school_add === false){
                DLOG('添加员工学历信息失败 error: ' . $Dao->getDBError(), 'error', 'staff');
                return [
                    'errNo' => '1003',
                    'errMsg' => '添加员工学历信息失败'
                ];
                $Dao->rollback();
            }
        }

        $Dao->commit();

        return $add;
    }

    /**
     * 修改员工
     * Author Raven
     * Date 2019-08-05
     * Params [params]
     * @param  int $staff_id          [员工ID]
     * @param  array $staff_base      [员工基本信息]
     * @param  array $staff_contact   [员工联系方式]
     * @param  array $staff_position  [员工职位信息]
     * @param  array $staff_card      [员工银行卡信息]
     * @param  array $staff_school    [员工学历信息]
     */
    public function editStaff($staff_id=0, $staff_base=[], $staff_contact=[], $staff_position=[], $staff_card=[], $staff_school=[])
    {

        $Dao = M('staff_info');
        $staff_info = $this -> getStaffInfoById($staff_id);

        $find_staff_no = $this->get_staff_no($staff_base["staff_NO"]);
        if (!empty($find_staff_no)){
            if ($find_staff_no != $staff_info["staff_NO"])
            {
                return [
                    'errNo' => '1003',
                    'errMsg' => '当前员工编号已存在'
                ];
            }
        }

        $find_staff_idcard_num = $this->get_staff_idcard($staff_base["staff_idcard_num"]);
        if (!empty($find_staff_idcard_num)){
            if ($find_staff_idcard_num != $staff_info['staff_idcard_num'])
            {
                return [
                    'errNo' => '1003',
                    'errMsg' => '当前员工身份证号已存在'
                ];
            }
        }


        $staff_data = array_merge($staff_base, $staff_contact, $staff_position, $staff_card);
        $Dao->startTrans();

        $where = [
            'id' => $staff_id
        ];
        $save = $Dao
            ->where($where)
            ->save($staff_data);

        if($save === false){
            DLOG('修改员工信息失败 error: ' . $Dao->getDBError(), 'error', 'staff');
            return [
                'errNo' => '1003',
                'errMsg' => '修改员工信息失败'
            ];
            $Dao->rollback();
        }

        $school_where = [
            'fk_staff_id' => $staff_id
        ];

        $SchoolDao = M('staff_school');
        $empty_school = $SchoolDao
            ->where($school_where)
            ->delete();
        
        if($empty_school === false)
        {
            DLOG('修改员工信息失败 error: ' . $Dao->getDBError(), 'error', 'staff');
            return [
                'errNo' => '1003',
                'errMsg' => '修改员工信息失败-删除'
            ];
            $Dao->rollback();
        }

        $staff_school_data = $this->createStaffSchool($staff_id, $staff_school);

        if (!empty($staff_school_data))
        {
            $school_add = $SchoolDao->addAll($staff_school_data);

            if($school_add === false){
                DLOG('修改员工信息失败 error: ' . $Dao->getDBError(), 'error', 'staff');
                return [
                    'errNo' => '1003',
                    'errMsg' => '修改员学历工信息失败'
                ];
                $Dao->rollback();
            }
        }
        $Dao->commit();

        return $save;
    }

    /**
     * 获取员工列表 
     * Author Raven
     * Date 2019-07-31
     * Params [params]
     * @param  integer $page_num [当前页码]
     */
    public function getStaffList($page_num=0)
    {
        $where = array();

        if(isset($_GET['staff_name']) && $_GET['staff_name'] != ''){
            $where['staff_name'] = [
                'LIKE', $_GET['staff_name'] . "%"
            ];
        }

        if(isset($_GET['staff_NO']) && $_GET['staff_NO'] != ''){
            $where['staff_NO'] = $_GET['staff_NO'];
        }

        if(isset($_GET['staff_department']) && $_GET['staff_department'] != ''){
            $where['staff_department'] = $_GET['staff_department'];
        }

        $Dao = M('staff_info');
        $list = $Dao
            ->field("id, staff_NO, staff_name, staff_department, staff_birthday, staff_hiredate, staff_education")
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

        $res = array(
            "list" => $list,
            "page" => $show,
        );
        return $res;
    }

    /**
     * 获取员工列表 
     * Author Raven
     * Date 2019-07-31
     * Params [params]
     * @param  integer $page_num [当前页码]
     */
    public function getStaffListInIds($staff_ids=[])
    {
        $where['id'] = [
            'IN', $staff_ids
        ];

        $Dao = M('staff_info');
        $list = $Dao
            ->field("id, staff_NO, staff_name, staff_department, staff_birthday, staff_hiredate, staff_education")
            ->where($where)
            ->select();

        return $list;
    }

    /**
     * 获取员工详情
     * Author Raven
     * Date 2019-07-31
     * Params [params]
     * @param integer $staff_id    [员工ID]
     * @param string  $field       [获取数据列明]
     */
    public function getStaffInfoById($staff_id=0, $field='*')
    {
        $Dao = M('staff_info');

        $where = [
            'id' => $staff_id
        ];

        $StaffInfo = $Dao
            ->field($field)
            ->where($where)
            ->find();

        if(empty($StaffInfo)){
            return [
                'errNo' => '1004',
                'errMsg' => '用户信息不存在'
            ];
        }

        return $StaffInfo;
    }
    
    /**
     * 获取员工学历列表
     * Author Raven
     * Date 2019-07-31
     * Params [params]
     * @param integer $staff_id    [员工ID]
     * @param string  $field       [获取数据列明]
     */
    public function getStaffSchoolByStaffId($staff_id=0, $field='*')
    {
        $SchoolDao = M('staff_school');

        $where_school = [
            'fk_staff_id' => $staff_id
        ];

        $StaffSchoolList = $SchoolDao
            ->field($field)
            ->where($where_school)
            ->order('school_index asc')
            ->select();
        
        return $StaffSchoolList;
    }

    /**
     * 查找员工列表 
     * Author Raven
     * Date 2019-08-06
     * Params [params]
     * @param  string $staff_name [员工姓名]
     * @param string  $field      [获取数据列明]
     */
    public function searchStaffList($staff_name='', $field)
    {
        $Dao = M('staff_info');

        $where = [
            'staff_name' => [
                'LIKE', $staff_name . "%"
            ]
        ];

        $res = $Dao
            ->field($field)
            ->where($where)
            ->limit('0, 5')
            ->select();

        if(empty($res)){
            return [];
        }

        return $res;
    }



    /**
     * 创建员工基本信息 
     * Author Raven
     * Date 2019-08-01
     * Params [params]
     * @param  string  $staff_NO         [员工编号]
     * @param  string  $staff_name       [员工姓名]
     * @param  string  $staff_idcard_num [员工身份证号]
     * @param  string  $staff_birthday   [员工生日]
     * @param  integer $staff_gender     [员工性别 1-男 2-女]
     */
    public function createStaffBaseInfo($staff_NO='', $staff_name='', $staff_idcard_num='', $staff_birthday='', $staff_gender=0, $staff_education=9, $staff_status=0, $staff_working_age=0, $staff_annual_leave_cnt=0)
    {
        $data = [];
        $data['staff_NO'] = $staff_NO;
        $data['staff_name'] = $staff_name;
        $data['staff_idcard_num'] = $staff_idcard_num;
        $data['staff_birthday'] = $staff_birthday;
        $data['staff_gender'] = $staff_gender;
        $data['staff_education'] = $staff_education;
        $data['staff_status'] = $staff_status;
        $data['staff_working_age'] = $staff_working_age;
        $data['staff_annual_leave_cnt'] = $staff_annual_leave_cnt;

        return $data;
    }

    /**
     * 创建员工联系方式 
     * Author Raven
     * Date 2019-08-01
     * Params [params]
     * @param  integer $province_id        [户籍所在省份id]
     * @param  integer $city_id            [户籍所在城市id]
     * @param  integer $district_id        [户籍所在区县id]
     * @param  string  $staff_address      [现居住地址]
     * @param  string  $staff_phone_num    [联系方式]
     * @param  integer $staff_account_type [户口性质 1-城镇 2-农村]
     */
    public function createStaffContactInfo($province_id=0, $city_id=0, $district_id=0, $staff_address='', $staff_phone_num='', $staff_account_type=0)
    {
        $data = [];
        $data['fk_province_id'] = $province_id;
        $data['fk_city_id'] = $city_id;
        $data['fk_district_id'] = $district_id;
        $data['staff_address'] = $staff_address;
        $data['staff_phone_num'] = $staff_phone_num;
        $data['staff_account_type'] = $staff_account_type;

        return $data;
    }

    /**
     * 员工职位信息 
     * Author Raven
     * Date 2019-08-01
     * Params [params]
     * @param  integer $staff_department      [员工部门 1-研发部 2-运营部 3-其他]
     * @param  string  $staff_rank            [员工职位]
     * @param  string  $staff_hiredate        [入职时间]
     * @param  integer $staff_type            [用工形式 1-固定期限 2-实习生 3-劳务派遣]
     * @param  string  $staff_official_date   [员工转正日期]
     * @param  string  $staff_finish_date     [合同终止日期]
     * @param  string  $staff_NDA_finish_date [员工保密协议终止时间 NDA (Non-Disclosure Agreement]
     * @param  string  $staff_term_date       [员工离职日期]
     */
    public function createStaffWorkInfo($staff_department = 0, $staff_rank = '', $staff_hiredate = '', $staff_type = 0, $staff_official_date = '', $staff_finish_date = '', $staff_NDA_finish_date = '', $staff_term_date = '')
    {
        $data = [];
        $data['staff_department'] = $staff_department;
        $data['staff_rank'] = $staff_rank;
        $data['staff_hiredate'] = $staff_hiredate;
        $data['staff_type'] = $staff_type;
        $data['staff_official_date'] = $staff_official_date;
        $data['staff_finish_date'] = $staff_finish_date;
        $data['staff_NDA_finish_date'] = $staff_NDA_finish_date;
        $data['staff_term_date'] = $staff_term_date;

        return $data;
    }

    /**
     * 员工学历信息 
     * Author Raven
     * Date 2019-08-01
     * Params [params]
     * @param  array $school_list           [学校列表]
     */
    public function createStaffSchool($staff_id = 0, $school_list = [])
    {
        $data = [];
        foreach ($school_list as $key => $value)
        {
            if (empty($value['school_name']) || empty($value['school_major']))
            {
                continue;
            }
            $school = [];
            $school['fk_staff_id'] = $staff_id;
            $school['school_index'] = $value['school_index'];
            $school['school_name'] = $value['school_name'];
            $school['school_major'] = $value['school_major'];
            $school['createtime'] =  date("Y-m-d H:i:s");
            $data[] = $school;
        }

        return $data;
    }

    /**
     * 银行卡信息 
     * Author Raven
     * Date 2019-08-01
     * Params [params]
     * @param  string $staff_wage_card_num       [员工工资卡卡号]
     * @param  string $staff_wage_card_bank      [员工工资卡银行]
     * @param  string $staff_wage_card_reg_bank  [员工工资卡开卡行]
     * @param  string $staff_bouns_card_num      [员工报销卡卡号]
     * @param  string $staff_bouns_card_bank     [员工报销卡银行]
     * @param  string $staff_bouns_card_reg_bank [员工报销卡开卡行]
     */
    public function createStaffBankCard($staff_wage_card_num = 0, $staff_wage_card_bank = '', $staff_wage_card_reg_bank='', $staff_bouns_card_num='', $staff_bouns_card_bank='', $staff_bouns_card_reg_bank='')
    {
        $data = [];
        $data['staff_wage_card_num'] = $staff_wage_card_num;
        $data['staff_wage_card_bank'] = $staff_wage_card_bank;
        $data['staff_wage_card_reg_bank'] = $staff_wage_card_reg_bank;
        $data['staff_bouns_card_num'] = $staff_bouns_card_num;
        $data['staff_bouns_card_bank'] = $staff_bouns_card_bank;
        $data['staff_bouns_card_reg_bank'] = $staff_bouns_card_reg_bank;

        return $data;
    }

    /**
     * 通过员工姓名获取id列表
     * Author Raven
     * Date 2019-08-05
     * Params [params]
     * @param  string $staff_name [员工姓名]
     */
    public function getStaffIdListByName($staff_name=''){
        $Dao = M('staff_info');

        $where = [
            'staff_name' => [
                'LIKE', trim($staff_name) . "%"
            ]
        ];

        $userId = $Dao
            ->field('id')
            ->where($where)
            ->select();

        if(empty($userId)){
            return [-99];
        }

        $res = array_column($userId, 'id');
        return $res;
    }

    /**
     * 通过员工编号获取id列表
     * Author Raven
     * Date 2019-08-05
     * Params [params]
     * @param  string $staff_no [员工编号]
     */
    public function getStaffIdListByNo($staff_no=''){
        $Dao = M('staff_info');

        $where = [
            'staff_NO' => [
                'LIKE', trim($staff_no) . "%"
            ]
        ];

        $userId = $Dao
            ->field('id')
            ->where($where)
            ->select();

        if(empty($userId)){
            return [-99];
        }

        $res = array_column($userId, 'id');
        return $res;
    }

    /**
     * 通过员工编号获取id列表
     * Author Raven
     * Date 2019-08-05
     * Params [params]
     * @param  string $staff_no [员工编号]
     */
    public function getStaffIdListByNameAndNo($staff_no='', $staff_name=''){
        $Dao = M('staff_info');

        $where = [
            'staff_name' => [
                'LIKE', trim($staff_name) . "%"
            ],
            'staff_NO' => [
                'LIKE', trim($staff_no) . "%"
            ]
        ];

        $userId = $Dao
            ->field('id')
            ->where($where)
            ->select();

        if(empty($userId)){
            return [-99];
        }

        $res = array_column($userId, 'id');
        return $res;
    }

    /**
     * 查询员工信息 
     * Author Raven
     * Date 2019-08-05
     * Params [params]
     * @param  array  $staff_id [员工id列表]
     * @param  string $field    [查询的字段]
     */
    public function getStaffInfoByIdList($staff_id=[], $field='*')
    {
        $Dao = M('staff_info');

        $where = [
            'id' => [
                'IN', $staff_id
            ]
        ];

        $res = $Dao
            ->where($where)
            ->getField($field);
        return $res;
    }

    /**
     * 检查员工基本信息 
     * Author Raven
     * Date 2019-08-02
     * Params [params]
     * @param  array  $staff_base         [员工基本信息]
     */
    public function checkStaffBase($staff_base=[])
    {
        $res = [
            'status' => TRUE,
            'errMsg' => ''
        ];

        if (empty($staff_base["staff_NO"]))
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入员工编号'
            ];
            return $res;
        }

        if (empty($staff_base["staff_name"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入员工姓名'
            ];
            return $res;
        }

        if (empty($staff_base["staff_idcard_num"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入员工身份证号'
            ];
            return $res;
        }

         if (!preg_match("/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/", $staff_base["staff_idcard_num"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入有效的身份证号'
            ];
            return $res;
        }

        if (empty($staff_base["staff_birthday"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入员工出生日期'
            ];
            return $res;
        }

        if (empty($staff_base["staff_gender"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请选择员工性别'
            ];
            return $res;
        }

        if (empty($staff_base["staff_education"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请选择员工最高学历'
            ];
            return $res;
        }

        if (empty($staff_base["staff_status"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请选择员工状态'
            ];
            return $res;
        }

        if (empty($staff_base["staff_working_age"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入员工工龄'
            ];
            return $res;
        }

        if (empty($staff_base["staff_annual_leave_cnt"]))
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入员工年假'
            ];
            return $res;
        }

        return $res;
    }

    /**
     * 检查员工联系方式 
     * Author Raven
     * Date 2019-08-02
     * Params [params]
     * @param  array  $staff_contact         [员工联系方式]
     */
    public function checkStaffContact($staff_contact=[])
    {
        $res = [
            'status' => TRUE,
            'errMsg' => ''
        ];

        if (empty($staff_contact["fk_province_id"]))
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请选择省'
            ];
            return $res;
        }

        if (empty($staff_contact["fk_city_id"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请选择市'
            ];
            return $res;
        }

        if (empty($staff_contact["fk_district_id"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请选择地区'
            ];
            return $res;
        }

        if (empty($staff_contact["staff_address"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入现居住地址'
            ];
            return $res;
        }

        if (empty($staff_contact["staff_phone_num"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入联系方式'
            ];
            return $res;
        }

        if (!preg_match("/^\d{11}$/", $staff_contact["staff_phone_num"])) {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入有效的联系方式'
            ];
            return $res;
        }

        if (empty($staff_contact["staff_account_type"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请选择户口性质'
            ];
            return $res;
        }

        if (empty($staff_contact["staff_EC_name"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入紧急联系人姓名'
            ];
            return $res;
        }

        if (empty($staff_contact["staff_EC_relations"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入紧急联系人关系'
            ];
            return $res;
        }

        if (empty($staff_contact["staff_EC_phone"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入紧急联系人电话'
            ];
            return $res;
        }

        if (!preg_match("/^\d{11}$/", $staff_contact["staff_EC_phone"])) {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入有效的紧急联系人电话'
            ];
            return $res;
        }

        return $res;
    }

    /**
     * 检查员工职位信息
     * Author Raven
     * Date 2019-08-02
     * Params [params]
     * @param  array  $staff_position         [员工职位信息]
     */
    public function checkStaffPosition($staff_position=[])
    {
        $res = [
            'status' => TRUE,
            'errMsg' => ''
        ];

        if (empty($staff_position["staff_department"]))
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请选择部门'
            ];
            return $res;
        }

        if (empty($staff_position["staff_rank"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入职位'
            ];
            return $res;
        }

        if (empty($staff_position["staff_hiredate"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请选择入职时间'
            ];
            return $res;
        }

        if (empty($staff_position["staff_type"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请选择用工形式'
            ];
            return $res;
        }

        if (empty($staff_position["staff_official_date"]) and $staff_position["staff_type"] != $this->INTEERN) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请选择转正日期'
            ];
            return $res;
        }

        if (empty($staff_position["staff_finish_date"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请选择合同终止日期'
            ];
            return $res;
        }

        if (empty($staff_position["staff_NDA_finish_date"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请选择保密协议终止日期'
            ];
            return $res;
        }

        // if (empty($staff_position["staff_term_date"])) 
        // {
        //     $res = [
        //         'status' => FALSE,
        //         'errMsg' => '请选择离职日期'
        //     ];
        //     return $res;
        // }

        return $res;
    }

    /**
     * 检查员工银行卡信息
     * Author Raven
     * Date 2019-08-02
     * Params [params]
     * @param  array  $staff_card         [员工银行卡信息]
     */
    public function checkStaffCard($staff_card=[])
    {
        $res = [
            'status' => TRUE,
            'errMsg' => ''
        ];

        if (empty($staff_card["staff_wage_card_num"]))
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入工资卡号'
            ];
            return $res;
        }

        if (!preg_match("/^\d{16,20}$/", $staff_card["staff_wage_card_num"]))
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入有效的工资卡号'
            ];
            return $res;
        }

        if (empty($staff_card["staff_wage_card_bank"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入工资卡归属行'
            ];
            return $res;
        }

        if (empty($staff_card["staff_wage_card_reg_bank"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入工资卡开卡行'
            ];
            return $res;
        }

        if (empty($staff_card["staff_bouns_card_num"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入报销卡号'
            ];
            return $res;
        }

         if (!preg_match("/^\d{16,20}$/", $staff_card["staff_bouns_card_num"]))
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入有效的报销卡号'
            ];
            return $res;
        }

        if (empty($staff_card["staff_bouns_card_bank"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入报销卡归属行'
            ];
            return $res;
        }

        if (empty($staff_card["staff_bouns_card_reg_bank"])) 
        {
            $res = [
                'status' => FALSE,
                'errMsg' => '请输入报销卡开卡行'
            ];
            return $res;
        }

        return $res;
    }

    /**
     * 检查员工是否存在 
     * Author Raven
     * Date 2019-08-05
     * Params [params]
     * @param  integer $staff_id [员工id]
     */
    public function checkStaffExists($staff_id=0)
    {
        $Dao = M('staff_info');
        $where = [
            'id' => $staff_id
        ];

        $cnt = $Dao
            ->where($where)
            ->count();

        return $cnt > 0 ? true : false;
    }

    /**
     * 根据员工编号获取员工编号
     * Author Raven
     * Date 2019-07-31
     * Params [params]
     * @param  string  $project_code [项目编号]
     */
    public function get_staff_no($staff_no='')
    {
        $Dao = M('staff_info');

        $where = [
            "staff_NO" => $staff_no
        ];
        $staff_no = $Dao
            ->field('staff_NO')
            ->where($where)
            ->find();

        return $staff_no['staff_NO'];
    }

    /**
     * 检查员工编号是否存在 
     * Author Raven
     * Date 2019-07-31
     * Params [params]
     * @param  string  $project_code [项目编号]
     */
    public function get_staff_idcard($staff_idcard_num='')
    {
        $Dao = M('staff_info');

        $where = [
            "staff_idcard_num" => $staff_idcard_num
        ];
        $staff_list = $Dao
            ->field('staff_idcard_num')
            ->where($where)
            ->find();
        return $staff_list['staff_idcard_num'];
    }

    public function test()
    {
        //员工基本信息
        $staff_NO = 'DBS-001';
        $staff_name = '杨帆';
        $staff_idcard_num = '110108198812052219';
        $staff_birthday = '1988-12-05';
        $staff_gender = 1;

        $staffBaseInfo = $this->createStaffBaseInfo($staff_NO, $staff_name, $staff_idcard_num, $staff_birthday, $staff_gender);


        //员工联系方式
        $province_id = 1;
        $city_id = 1;
        $district_id = 1;
        $staff_address = '北京市西城区手帕口南街甲1号朗琴园2号楼1303';
        $staff_phone_num = '18612050059';
        $staff_account_type = 1;

        $staffContactInfo = $this->createStaffContactInfo($province_id, $city_id, $district_id, $staff_address, $staff_phone_num, $staff_account_type);

        //员工工作信息
        $staff_department = 2;
        $staff_rank = '运营副总';
        $staff_hiredate = '';
        $staff_type = 1;
        $staff_official_date = '';
        $staff_finish_date = '';
        $staff_NDA_finish_date = '';
        $staff_term_date = '';

        $staffWorkInfo = $this->createStaffWorkInfo($staff_department, $staff_rank, $staff_hiredate, $staff_type, $staff_official_date, $staff_finish_date, $staff_NDA_finish_date, $staff_term_date);

        
        var_dump($staffWorkInfo);
        exit();
    }
}
