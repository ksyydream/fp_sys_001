<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Manager_model extends MY_Model
{

    /**
     * 管理员操作Model
     * @version 1.0
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-29
     * @Copyright (C) 2017, Tianhuan Co., Ltd.
     */

    public function __construct() {
        parent::__construct();
    }

    public function check_login() {
        if (strtolower($this->input->post('verify')) != strtolower($this->session->flashdata('cap')))
            return -1;
        $data = array(
            'user' => trim($this->input->post('user')),
            'password' => password(trim($this->input->post('password'))),
        );
        $row = $this->db->select()->from('admin')->where($data)->get()->row_array();
        if ($row) {
            $data['admin_info'] = $row;
            $this->session->set_userdata($data);
            return 1;
        } else {
            return -2;
        }
    }

    /**
     * 获取用户所能显示的菜单
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-30
     */
    public function get_menu4admin($admin_id = 0) {
        $admin_info = $this->db->select()->from('auth_group g')
            ->join('auth_group_access a', 'g.id=a.group_id', 'left')
            ->where('a.admin_id', $admin_id)->get()->row_array();
        if (!$admin_info) {
            return array();
        }
        $menu_access_arr = explode(",", $admin_info['rules']);
        $this->db->select('id,title,pid,name,icon');
        $this->db->from('auth_rule');
        $this->db->where('islink', 1);
        $this->db->where('status', 1);
        if ($admin_info['group_id'] != 1) {
            $this->db->where_in('id', $menu_access_arr);
        }
        $menu = $this->db->order_by('o asc')->get()->result_array();
        return $menu;
    }

    public function get_action_menu($controller = null, $action = null) {
        $action_new = str_replace('edit', 'list', $action);
        $action_new = str_replace('add', 'list', $action_new);
        $action_new = str_replace('detail', 'list', $action_new);
        $this->db->select('s.id,s.title,s.name,s.tips,s.pid,p.pid as ppid,p.title as ptitle');
        $this->db->from('auth_rule s');
        $this->db->join('auth_rule p', 'p.id = s.pid', 'left');
        $this->db->where('s.name', $controller . '/' . $action_new);
        $row = $this->db->get()->row_array();
        if (!$row) {
            $this->db->select('s.id,s.title,s.name,s.tips,s.pid,p.pid as ppid,p.title as ptitle');
            $this->db->from('auth_rule s');
            $this->db->join('auth_rule p', 'p.id = s.pid', 'left');
            $this->db->where('s.name', $controller . '/' . $action);
            $row = $this->db->get()->row_array();
        }
        return $row;
    }

    public function get_admin($admin_id) {
        $admin_info = $this->db->select('a.*,b.group_id,c.title')->from('admin a')
            ->join('auth_group_access b', 'a.admin_id = b.admin_id', 'left')
            ->join('auth_group c', 'c.id = b.group_id', 'left')
            ->where('a.admin_id', $admin_id)->get()->row_array();
        return $admin_info;
    }

    /**
     *********************************************************************************************
     * 以下代码为系统设置模块
     *********************************************************************************************
     */

    /**
     * 查找所有可添加的菜单
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function get_menu_all() {
        $this->db->select('id,title,pid,name,icon,islink,o');
        $this->db->from('auth_rule');
        $this->db->where('status', 1);
        $menu = $this->db->order_by('o asc')->get()->result_array();
        return $menu;
    }

    /**
     * 获取后台菜单详情
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_info($id) {
        $menu_info = $this->db->select()->from('auth_rule')->where('id', $id)->get()->row_array();
        return $menu_info;
    }

    /**
     * 保存管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_save() {
        $data = array(
            'pid' => trim($this->input->post('pid')) ? trim($this->input->post('pid')) : 0,
            'title' => trim($this->input->post('title')) ? trim($this->input->post('title')) : null,
            'name' => trim($this->input->post('name')) ? trim($this->input->post('name')) : '',
            'icon' => trim($this->input->post('icon')) ? trim($this->input->post('icon')) : '',
            'islink' => trim($this->input->post('islink')) ? trim($this->input->post('islink')) : 0,
            'o' => trim($this->input->post('o')) ? trim($this->input->post('o')) : 0,
            'tips' => trim($this->input->post('tips')) ? trim($this->input->post('tips')) : '',
            'cdate' => date('Y-m-d H:i:s', time()),
            'mdate' => date('Y-m-d H:i:s', time())
        );
        if (!$data['title'])
            return -2;//信息不全
        if ($id = $this->input->post('id')) {
            unset($data['cdate']);
            $this->db->where('id', $id)->update('auth_rule', $data);
        } else {
            $this->db->insert('auth_rule', $data);
        }
        return 1;
    }

    /**
     * 删除管理员
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_del($id) {
        if (!$id)
            return -1;
        $rs = $this->db->where('id', $id)->delete('auth_rule');
        if ($rs)
            return 1;
        return -1;
    }

    /**
     *********************************************************************************************
     * 以下代码为个人中心模块
     *********************************************************************************************
     */

    /**
     * 管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */

    public function admin_list($page = 1) {
        $data['limit'] = $this->limit;//每页显示多少调数据
        $data['keyword'] = trim($this->input->get('keyword')) ? trim($this->input->get('keyword')) : null;
        $data['field'] = trim($this->input->get('field')) ? trim($this->input->get('field')) : 1;// 1是用户名,2是电话,3是QQ,4是邮箱
        $data['order'] = trim($this->input->get('order')) ? trim($this->input->get('order')) : 1;// 1是desc,2是asc
        $this->db->select('count(1) num');
        $this->db->from('admin a');
        $this->db->join('auth_group_access b', 'a.admin_id = b.admin_id', 'left');
        $this->db->join('auth_group c', 'c.id = b.group_id', 'left');
        if ($data['keyword']) {
            switch ($data['field']) {
                case '1':
                    $this->db->like('a.user', $data['keyword']);
                    break;
                case '2':
                    $this->db->like('a.phone', $data['keyword']);
                    break;
                case '3':
                    $this->db->like('a.qq', $data['keyword']);
                    break;
                case '4':
                    $this->db->like('a.email', $data['keyword']);
                    break;
                default:
                    $this->db->like('a.user', $data['keyword']);
                    break;
            }
        }
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;
        //list
        $this->db->select('a.*,b.group_id,c.title');
        $this->db->from('admin a');
        $this->db->join('auth_group_access b', 'a.admin_id = b.admin_id', 'left');
        $this->db->join('auth_group c', 'c.id = b.group_id', 'left');
        if ($data['keyword']) {
            switch ($data['field']) {
                case '1':
                    $this->db->like('a.user', $data['keyword']);
                    break;
                case '2':
                    $this->db->like('a.phone', $data['keyword']);
                    break;
                case '3':
                    $this->db->like('a.qq', $data['keyword']);
                    break;
                case '4':
                    $this->db->like('a.email', $data['keyword']);
                    break;
                default:
                    $this->db->like('a.user', $data['keyword']);
                    break;
            }
        }
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        if ($data['order'] == 1) {
            $this->db->order_by('a.t', 'desc');
        } else {
            $this->db->order_by('a.t', 'asc');
        }
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    /**
     * 查找所有可添加的用户组
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function get_group_all() {
        $this->db->select('id,title');
        $this->db->from('auth_group');
        $this->db->where('status', 1);
        $menu = $this->db->order_by('id asc')->get()->result_array();
        return $menu;
    }

    /**
     * 保存管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function admin_save() {
        $data = array(
            'user' => trim($this->input->post('user')) ? trim($this->input->post('user')) : null,
            'sex' => $this->input->post('sex') ? $this->input->post('sex') : 0,
            'head' => $this->input->post('head') ? $this->input->post('head') : null,
            'phone' => trim($this->input->post('phone')) ? trim($this->input->post('phone')) : null,
            'qq' => trim($this->input->post('qq')) ? trim($this->input->post('qq')) : null,
            'email' => trim($this->input->post('email')) ? trim($this->input->post('email')) : null,
            'birthday' => trim($this->input->post('birthday')) ? trim($this->input->post('birthday')) : null,
            't' => time()
        );
        if (!$data['user'] || !$data['head'] || !$data['phone'] || !$data['qq'] || !$data['email'] || !$data['birthday'])
            return $this->fun_fail('信息不全!');
        if (!file_exists(dirname(SELF) . '/upload_files/head/' . $data['head'])) {
            return $this->fun_fail('信息不全,头像异常!');
        }
        if (!$group_id = $this->input->post('group_id')) {
            return $this->fun_fail('需要选择用户组!');
        }
        if (trim($this->input->post('password'))) {
            if (strlen(trim($this->input->post('password'))) < 6) {
                return $this->fun_fail('密码长度不可小于6位!');
            }
            if (is_numeric(trim($this->input->post('password')))) {
                return $this->fun_fail('密码不可是纯数字!');
            }
            $data['password'] = password(trim($this->input->post('password')));
        }
        if ($admin_id = $this->input->post('admin_id')) {
            unset($data['t']);
            $check_ = $this->db->select()->from('admin')
                ->where('user', $data['user'])
                ->where('admin_id <>', $admin_id)
                ->get()->row_array();
            if ($check_) {
                return $this->fun_fail('新建或修改的用户名已存在!');
            }
            $this->db->where('admin_id', $admin_id)->update('admin', $data);
        } else {
            if (!trim($this->input->post('password'))) {
                return $this->fun_fail('新建用户需要设置密码!');
            }
            $check_ = $this->db->select()->from('admin')->where('user', $data['user'])->get()->row_array();
            if ($check_) {
                return $this->fun_fail('新建或修改的用户名已存在!');
            }
            $this->db->insert('admin', $data);
            $admin_id = $this->db->insert_id();
        }
        $this->db->where('admin_id', $admin_id)->delete('auth_group_access');
        $this->db->insert('auth_group_access', array('admin_id' => $admin_id, 'group_id' => $group_id));
        return $this->fun_success('保存成功');
    }

    /**
     * 删除管理员
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function admin_del($id) {
        if (!$id)
            return -1;
        $admin_info = $this->get_admin($id);
        if (!$admin_info)
            return -1;
        if ($admin_info['group_id'] == 1)
            return -2;
        $rs = $this->db->where('admin_id', $id)->delete('admin');
        if ($rs)
            return 1;
        return -1;
    }

    /**
     * 获取用户组信息
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function get_group_detail($id = 0) {
        $group_detail = $this->db->select()->from('auth_group')->where('id', $id)->get()->row_array();
        if (!$group_detail) {
            return -1;
        }
        $group_detail['rules'] = explode(',', $group_detail['rules']);
        return $group_detail;
    }

    /**
     * 保存用户组
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_save() {
        $data = array(
            'title' => trim($this->input->post('title')) ? trim($this->input->post('title')) : null,
            'status' => $this->input->post('status') ? $this->input->post('status') : -1,
        );
        if ($data['title'] == "") {
            return -1;
        }
        $rules = $this->input->post('rules') ? $this->input->post('rules') : 0;
        if (is_array($rules)) {
            foreach ($rules as $k => $v) {
                $rules[$k] = intval($v);
            }
            $rules = implode(',', $rules);
        }
        $data['rules'] = $rules;
        if ($group_id = $this->input->post('id')) {
            $this->db->where('id', $group_id)->update('auth_group', $data);
        } else {
            $this->db->insert('auth_group', $data);
        }
        return 1;
    }

    /**
     * 用户组列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_list($page = 1) {
        $data['limit'] = $this->limit;//每页显示多少调数据
        $this->db->select('count(1) num');
        $this->db->from('auth_group a');
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;

        //list
        $this->db->select('a.*');
        $this->db->from("auth_group a");
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('id', 'asc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    /**
     * 删除用户组
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_del($id) {
        if (!$id)
            return -1;
        if ($id == 1)
            return -2;
        $rs = $this->db->where('id', $id)->delete('auth_group');
        if ($rs)
            return 1;
        return -1;
    }

    /**
     * 保存管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function personal_save($admin_id) {
        $data = array(
            'user' => trim($this->input->post('user')) ? trim($this->input->post('user')) : null,
            'sex' => $this->input->post('sex') ? $this->input->post('sex') : 0,
            'head' => $this->input->post('head') ? $this->input->post('head') : null,
            'phone' => trim($this->input->post('phone')) ? trim($this->input->post('phone')) : null,
            'qq' => trim($this->input->post('qq')) ? trim($this->input->post('qq')) : null,
            'email' => trim($this->input->post('email')) ? trim($this->input->post('email')) : null,
            'birthday' => trim($this->input->post('birthday')) ? trim($this->input->post('birthday')) : null,
        );
        if (!$data['user'] || !$data['head'] || !$data['phone'] || !$data['qq'] || !$data['email'] || !$data['birthday'])
            return $this->fun_fail('信息不全!');
        if (!file_exists(dirname(SELF) . '/upload_files/head/' . $data['head'])) {
            return $this->fun_fail('信息不全!');
        }
        if (trim($this->input->post('password'))) {
            if (strlen(trim($this->input->post('password'))) < 6) {
                return $this->fun_fail('密码长度不可小于6位!');
            }
            if (is_numeric(trim($this->input->post('password')))) {
                return $this->fun_fail('密码不可是纯数字!');
            }
            $data['password'] = password(trim($this->input->post('password')));
        }
        $this->db->where('admin_id', $admin_id)->update('admin', $data);
        return $this->fun_success('保存成功!');
    }

    /**
     *********************************************************************************************
     * 以下代码为微信端账号模块
     *********************************************************************************************
     */

    /**
     * 会员列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function users_list($page = 1) {
        $data['limit'] = $this->limit;//每页显示多少调数据
        $data['keyword'] = trim($this->input->get('keyword')) ? trim($this->input->get('keyword')) : null;
        $data['type_id'] = trim($this->input->get('type_id')) ? trim($this->input->get('type_id')) : null;
        $data['status'] = trim($this->input->get('status')) ? trim($this->input->get('status')) : null;
        $data['s_date'] = trim($this->input->get('s_date')) ? trim($this->input->get('s_date')) : '';
        $data['e_date'] = trim($this->input->get('e_date')) ? trim($this->input->get('e_date')) : '';
        $data['ml23_id'] = trim($this->input->get('ml23_id')) ? trim($this->input->get('ml23_id')) : '';
        $this->db->select('count(1) num');
        $this->db->from('users us');
        if ($data['keyword']) {
            $this->db->group_start();
            $this->db->like('us.rel_name', $data['keyword']);
            $this->db->or_like('us.mobile', $data['keyword']);
            $this->db->group_end();
        }
        if ($data['s_date']) {
            $this->db->where('us.reg_time >=', strtotime($data['s_date'] . " 00:00:00"));
        }
        if ($data['e_date']) {
            $this->db->where('us.reg_time <=', strtotime($data['e_date'] . " 23:59:59"));
        }
        if ($data['type_id']) {
            $this->db->where('us.type_id', $data['type_id']);
        }
        if ($data['status']) {
            $this->db->where('us.status', $data['status']);
        }
        if ($data['ml23_id']) {
            $this->db->where('us.invite', $data['ml23_id']);
        }
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;
        //list
        $this->db->select('us.*,r1.name r1_name,r2.name r2_name,r3.name r3_name,r4.name r4_name, m.rel_name m_rel_name_,m.mobile m_mobile_');
        $this->db->from('users us');
        $this->db->join('region r1', 'us.province = r1.id', 'left');
        $this->db->join('region r2', 'us.city = r2.id', 'left');
        $this->db->join('region r3', 'us.district = r3.id', 'left');
        $this->db->join('region r4', 'us.twon = r4.id', 'left');
        $this->db->join('members m', 'm.m_id = us.invite', 'left');
        if ($data['keyword']) {
            $this->db->group_start();
            $this->db->like('us.rel_name', $data['keyword']);
            $this->db->or_like('us.mobile', $data['keyword']);
            $this->db->group_end();
        }
        if ($data['s_date']) {
            $this->db->where('us.reg_time >=', strtotime($data['s_date'] . " 00:00:00"));
        }
        if ($data['e_date']) {
            $this->db->where('us.reg_time <=', strtotime($data['e_date'] . " 23:59:59"));
        }
        if ($data['type_id']) {
            $this->db->where('us.type_id', $data['type_id']);
        }
        if ($data['status']) {
            $this->db->where('us.status', $data['status']);
        }
        if ($data['ml23_id']) {
            $this->db->where('us.invite', $data['ml23_id']);
        }
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('us.reg_time', 'desc');
        $data['res_list'] = $this->db->get()->result_array();
        $data['m_level_2_3'] = $this->db->select('')->from('members')->where_in('level', array(2, 3))->get()->result_array();
        return $data;
    }

    //会员改变所属管理员
    public function users_m_change() {
        $m_id = $this->input->post('sel_member_id');
        $user_id = $this->input->post('user_id');
        if(!$m_id){
            return $this->fun_fail('请选择新管理员!');
        }
        $check_ = $this->db->select()->from('members')->where(array('m_id' => $m_id, 'status' => 1))->where_in('level', array(2, 3))->get()->row();
        if(!$check_){
            return $this->fun_fail('所选新管理员,不规范!');
        }
        if(!$user_id){
            return $this->fun_fail('请选择会员!');
        }
        $this->db->where(array('user_id' => $user_id))->update('users', array('invite' => $m_id));
        return $this->fun_success('操作成功!');
    }

    /**
     * 会员详情
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-07-22
     */
    public function users_edit($user_id){
        $this->db->select('us.*,r1.name r1_name,r2.name r2_name,r3.name r3_name,r4.name r4_name, m.rel_name m_rel_name_,m.mobile m_mobile_');
        $this->db->from('users us');
        $this->db->join('region r1', 'us.province = r1.id', 'left');
        $this->db->join('region r2', 'us.city = r2.id', 'left');
        $this->db->join('region r3', 'us.district = r3.id', 'left');
        $this->db->join('region r4', 'us.twon = r4.id', 'left');
        $this->db->join('members m', 'm.m_id = us.invite', 'left');
        $user_info = $this->db->where('user_id', $user_id)->get()->row_array();
        if(!$user_info)
            return $user_info;
        $this->db->select()->from('members');
        $this->db->group_start();
        $this->db->where_in('level', array(2,3));
        $this->db->where('status', 1);
        $this->db->group_end();
        $this->db->or_group_start();
        $this->db->where('m_id', $user_info['invite']);
        $this->db->group_end();
        $user_info['sel_member_list'] = $this->db->get()->result_array();
        return $user_info;
    }

    /**
     * 保存会员
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-22
     */
    public function users_save(){
        $user_id = $this->input->post('user_id');
        $update = array(
            'status' => $this->input->post('status') ? $this->input->post('status') : -1,
            'remark' => $this->input->post('remark'),
            'invite' => $this->input->post('sel_member_id')
        );
        if($update['status'] != 1)
            $update['openid'] = '';
        if(!$user_id){
            return $this->fun_fail('操作失败');
        }
        if(!in_array($update['status'], array(1, -1))){
            return $this->fun_fail('请选择状态');
        }
        if(!$update['invite']){
            return $this->fun_fail('请选择所属管理员');
        }
        $check_ = $this->db->select()->from('members')->where(array('m_id' => $update['invite'], 'status' => 1))->where_in('level', array(2, 3))->get()->row();
        if(!$check_){
            return $this->fun_fail('所选新管理员,不规范!');
        }
        $this->db->where('user_id', $user_id)->update('users', $update);
        return $this->fun_success('操作成功');
    }

    /**
     * 会员列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function members_list($page = 1) {
        $data['limit'] = $this->limit;//每页显示多少调数据
        $data['keyword'] = trim($this->input->get('keyword')) ? trim($this->input->get('keyword')) : null;
        $data['level'] = trim($this->input->get('level')) ? trim($this->input->get('level')) : null;
        $data['status'] = trim($this->input->get('status')) ? trim($this->input->get('status')) : null;
        $data['s_date'] = trim($this->input->get('s_date')) ? trim($this->input->get('s_date')) : '';
        $data['e_date'] = trim($this->input->get('e_date')) ? trim($this->input->get('e_date')) : '';
        $data['ml2_id'] = trim($this->input->get('ml2_id')) ? trim($this->input->get('ml2_id')) : '';
        $this->db->select('count(1) num');
        $this->db->from('members m');
        if ($data['keyword']) {
            $this->db->group_start();
            $this->db->like('m.rel_name', $data['keyword']);
            $this->db->or_like('m.mobile', $data['keyword']);
            $this->db->group_end();
        }
        if ($data['s_date']) {
            $this->db->where('m.add_time >=', strtotime($data['s_date'] . " 00:00:00"));
        }
        if ($data['e_date']) {
            $this->db->where('m.add_time <=', strtotime($data['e_date'] . " 23:59:59"));
        }
        if ($data['level']) {
            $this->db->where('m.level', $data['level']);
        }
        if ($data['ml2_id']) {
            $this->db->where('m.parent_id', $data['ml2_id']);
        }
        if ($data['status']) {
            $this->db->where('m.status', $data['status']);
        }

        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;
        //list
        $this->db->select('m.*, m1.rel_name m1_rel_name_,m1.mobile m1_mobile_');
        $this->db->from('members m');
        $this->db->join('members m1', 'm.parent_id = m1.m_id', 'left');
        if ($data['keyword']) {
            $this->db->group_start();
            $this->db->like('m.rel_name', $data['keyword']);
            $this->db->or_like('m.mobile', $data['keyword']);
            $this->db->group_end();
        }
        if ($data['s_date']) {
            $this->db->where('m.add_time >=', strtotime($data['s_date'] . " 00:00:00"));
        }
        if ($data['e_date']) {
            $this->db->where('m.add_time <=', strtotime($data['e_date'] . " 23:59:59"));
        }
        if ($data['level']) {
            $this->db->where('m.level', $data['level']);
        }
        if ($data['status']) {
            $this->db->where('m.status', $data['status']);
        }
        if ($data['ml2_id']) {
            $this->db->where('m.parent_id', $data['ml2_id']);
        }
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('m.level', 'asc')->order_by('m.add_time', 'desc');
        $data['res_list'] = $this->db->get()->result_array();
        $data['m_level_2'] = $this->db->select('')->from('members')->where(array('level' => 2))->get()->result_array();
        return $data;
    }

    public function members_work_add(){
        $data = $this->db->select('')->from('members')->where(array('level' => 2, 'status' => 1))->get()->result_array();
        return $data;
    }

    public function members_edit($m_id){
        $data = $this->db->select()->from('members')->where('m_id', $m_id)->get()->row_array();
        return $data;
    }

    //组员改变所属总监
    public function members_m_change() {
        $parent_id = $this->input->post('sel_member_id');
        $m_id = $this->input->post('m_id');
        if(!$parent_id){
            return $this->fun_fail('请选择新总监!');
        }
        $check_ = $this->db->select()->from('members')->where(array('m_id' => $parent_id, 'status' => 1))->where_in('level', array(2))->get()->row();
        if(!$check_){
            return $this->fun_fail('所选新总监,不规范!');
        }
        if(!$m_id){
            return $this->fun_fail('请选择组员!');
        }
        $m_info_ = $this->db->select()->from('members')->where('m_id', $m_id)->get()->row_array();
        if($m_info_['level'] != 3)
            return $this->fun_fail('所选择组员,职务发生改变!');
        $this->db->where(array('m_id' => $m_id))->update('members', array('parent_id' => $parent_id));
        return $this->fun_success('操作成功!');
    }

    /**
     * 微信管理员保存页面
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-23
     */
    public function members_save(){
        $data = array(
            'parent_id' => -1, //默认-1
            'level' => $this->input->post('level'),
            'mobile' => $this->input->post('mobile'),
            'status' => $this->input->post('status') ? $this->input->post('status') : -1,
            'rel_name' => $this->input->post('rel_name'),
            'remark' => $this->input->post('remark'),
            'pic' => $this->input->post('head') ? $this->input->post('head') : null,
        );
        if($data['status'] != 1)
            $data['openid'] = '';
        if(!$data['rel_name'])
            return $this->fun_fail('请填写姓名');
        if(!$data['mobile'])
            return $this->fun_fail('请填写手机号');
        if(!$data['pic'])
            return $this->fun_fail('请设置头像');
        if(!check_mobile($data['mobile']))
            return $this->fun_fail('手机号不规范');
        if(!$data['level'])
            return $this->fun_fail('请选择职务');
        if($data['level'] == 3){
            $data['parent_id'] = $this->input->post('sel_member_id');
            if(!$data['parent_id']){
                return $this->fun_fail('请选择所属总监');
            }
            $check_ = $this->db->select()->from('members')->where(array('m_id' => $data['parent_id'], 'status' => 1, 'level' => 2))->get()->row();
            if(!$check_){
                return $this->fun_fail('所选总监,已不可选择!');
            }
        }

        $m_id = $this->input->post('m_id');
        if($m_id){
            $m_info_ = $this->db->select()->from('members')->where(array('m_id' => $m_id))->get()->row_array();
            if(!$m_info_)
                return $this->fun_fail('所操作的微信管理员不存在');
            //修改
            //1,检查手机号是否被占用
            $check_mobile_ = $this->db->select()->from('members')->where(array('mobile' => $data['mobile'], 'm_id <>' => $m_id))->get()->row();
            if($check_mobile_)
                return $this->fun_fail('手机号码已被占用');

            //2,如果是总监, 变更职务或者停用时,必须将名下组员(启用状态)都划分
            if($m_info_['level'] == 2 && ($data['level'] != 2 || $data['status'] != 1)){
                $m_2_list_ = $this->db->select()->from('members')->where(array('parent_id' => $m_id, 'status' => 1))->get()->result_array();
                if($m_2_list_)
                    return $this->fun_fail('此账号下还有组员,请将组员划分完成后,再修改职务和状态!');
                $u_list_ = $this->db->select()->from('users')->where(array('invite' => $m_id, 'status' => 1))->get()->result_array();
                if($u_list_)
                    return $this->fun_fail('此账号下还有直属会员,请将会员划分完成后,再修改职务和状态!');
            }
            $this->db->where('m_id', $m_id)->update('members', $data);
        }else{
            //新增
            //1,检查手机号是否被占用
            $check_mobile_ = $this->db->select()->from('members')->where('mobile', $data['mobile'])->get()->row();
            if($check_mobile_)
                return $this->fun_fail('手机号码已被占用');
            $data['add_time'] = time();
            $title_ = 'KS';
            $data['invite_code'] = $title_ . sprintf('%04s', $this->get_sys_num_auto($title_));
            $this->db->insert('members', $data);

        }
        return $this->fun_success('操作成功');
    }

    /**
     *********************************************************************************************
     * 以下代码为系统记录模块
     *********************************************************************************************
     */

    /**
     * 短信日志列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-23
     */
    public function sms_list($page = 1){
        $data['limit'] = $this->limit;//每页显示多少调数据
        $data['mobile'] = trim($this->input->get('mobile')) ? trim($this->input->get('mobile')) : null;
        $data['s_date'] = trim($this->input->get('s_date')) ? trim($this->input->get('s_date')) : '';
        $data['e_date'] = trim($this->input->get('e_date')) ? trim($this->input->get('e_date')) : '';
        $where_ = array('sl.id >' => 0);
        if ($data['s_date']) {
            $where_['sl.add_time >='] = strtotime($data['s_date'] . " 00:00:00");
        }
        if ($data['e_date']) {
            $where_['sl.add_time <='] = strtotime($data['e_date'] . " 00:00:00");
        }
        if ($data['mobile']) {
            $where_['sl.mobile like'] = '%' . $data['mobile'] . '%';
        }
        $this->db->select('count(1) num');
        $this->db->from('sms_log sl');
        $this->db->where($where_);
        $rs_total = $this->db->get()->row();
        //die(var_dump($this->db->last_query()));
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;
        //list
        $this->db->select('sl.*');
        $this->db->from('sms_log sl');
        $this->db->where($where_);
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('sl.add_time', 'desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    /**
     * 同盾日志列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-23
     */
    public function tongdun_log_list($page = 1){
        $data['limit'] = $this->limit;//每页显示多少调数据
        $data['keyword'] = trim($this->input->get('keyword')) ? trim($this->input->get('keyword')) : null;
        $data['keyword2'] = trim($this->input->get('keyword2')) ? trim($this->input->get('keyword2')) : null;
        $data['s_date'] = trim($this->input->get('s_date')) ? trim($this->input->get('s_date')) : '';
        $data['e_date'] = trim($this->input->get('e_date')) ? trim($this->input->get('e_date')) : '';

        $this->db->select('count(1) num');
        $this->db->from('tongdun_log tl');
        $this->db->join('users us', 'tl.user_id = us.user_id', 'left');
        if ($data['keyword']) {
            $this->db->group_start();
            $this->db->like('tl.account_name', $data['keyword']);
            $this->db->or_like('tl.id_number', $data['keyword']);
            $this->db->group_end();
        }
        if ($data['keyword2']) {
            $this->db->group_start();
            $this->db->like('us.rel_name', $data['keyword2']);
            $this->db->or_like('us.mobile', $data['keyword2']);
            $this->db->group_end();
        }
        if ($data['s_date']) {
            $this->db->where('tl.add_time >=', strtotime($data['s_date'] . " 00:00:00"));
        }
        if ($data['e_date']) {
            $this->db->where('tl.add_time <=', strtotime($data['e_date'] . " 23:59:59"));
        }
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;
        //list
        $this->db->select('tl.*, us.rel_name us_rel_name_, us.mobile us_mobile_');
        $this->db->from('tongdun_log tl');
        $this->db->join('users us', 'tl.user_id = us.user_id', 'left');
        if ($data['keyword']) {
            $this->db->group_start();
            $this->db->like('tl.account_name', $data['keyword']);
            $this->db->or_like('tl.id_number', $data['keyword']);
            $this->db->group_end();
        }
        if ($data['keyword2']) {
            $this->db->group_start();
            $this->db->like('us.rel_name', $data['keyword2']);
            $this->db->or_like('us.mobile', $data['keyword2']);
            $this->db->group_end();
        }
        if ($data['s_date']) {
            $this->db->where('tl.add_time >=', strtotime($data['s_date'] . " 00:00:00"));
        }
        if ($data['e_date']) {
            $this->db->where('tl.add_time <=', strtotime($data['e_date'] . " 23:59:59"));
        }
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('tl.add_time', 'desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    /**
     * 同盾数据列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-25
     */
    public function tongdun_info_list($page = 1){
        $data['limit'] = $this->limit;//每页显示多少调数据
        $data['keyword'] = trim($this->input->get('keyword')) ? trim($this->input->get('keyword')) : null;
        $data['keyword2'] = trim($this->input->get('keyword2')) ? trim($this->input->get('keyword2')) : null;
        $data['s_date'] = trim($this->input->get('s_date')) ? trim($this->input->get('s_date')) : '';
        $data['e_date'] = trim($this->input->get('e_date')) ? trim($this->input->get('e_date')) : '';

        $this->db->select('count(1) num');
        $this->db->from('tongdun_info ti');
        $this->db->join('users us', 'ti.user_id = us.user_id', 'left');
        if ($data['keyword']) {
            $this->db->group_start();
            $this->db->like('ti.account_name', $data['keyword']);
            $this->db->or_like('ti.id_number', $data['keyword']);
            $this->db->group_end();
        }
        if ($data['keyword2']) {
            $this->db->group_start();
            $this->db->like('us.rel_name', $data['keyword2']);
            $this->db->or_like('us.mobile', $data['keyword2']);
            $this->db->group_end();
        }
        if ($data['s_date']) {
            $this->db->where('ti.add_time >=', strtotime($data['s_date'] . " 00:00:00"));
        }
        if ($data['e_date']) {
            $this->db->where('ti.add_time <=', strtotime($data['e_date'] . " 23:59:59"));
        }
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;
        //list
        $this->db->select('ti.*, us.rel_name us_rel_name_, us.mobile us_mobile_');
        $this->db->from('tongdun_info ti');
        $this->db->join('users us', 'ti.user_id = us.user_id', 'left');
        if ($data['keyword']) {
            $this->db->group_start();
            $this->db->like('ti.account_name', $data['keyword']);
            $this->db->or_like('ti.id_number', $data['keyword']);
            $this->db->group_end();
        }
        if ($data['keyword2']) {
            $this->db->group_start();
            $this->db->like('us.rel_name', $data['keyword2']);
            $this->db->or_like('us.mobile', $data['keyword2']);
            $this->db->group_end();
        }
        if ($data['s_date']) {
            $this->db->where('ti.add_time >=', strtotime($data['s_date'] . " 00:00:00"));
        }
        if ($data['e_date']) {
            $this->db->where('ti.add_time <=', strtotime($data['e_date'] . " 23:59:59"));
        }
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('ti.add_time', 'desc');
        $data['res_list'] = $this->db->get()->result_array();
        $td_deadline_ = $this->config->item('td_deadline'); //缓存数据使用限期,这里是秒为单位的
        foreach($data['res_list'] as $k_ => $item){
            if($item['add_time'] + $td_deadline_ < time()){
                $data['res_list'][$k_]['gq_flag'] = 1;   //过期标记位
            }else{
                $data['res_list'][$k_]['gq_flag'] = -1;  //过期标记位
            }
        }
        return $data;
    }

    //同盾数据详情
    public function tongdun_info_detail($id){
        $this->db->select('ti.*, us.rel_name us_rel_name_, us.mobile us_mobile_');
        $this->db->from('tongdun_info ti');
        $this->db->join('users us', 'ti.user_id = us.user_id', 'left');
        $this->db->where('id', $id);
        $data = $this->db->get()->row_array();
        return $data;
    }

    /**
     *********************************************************************************************
     * 以下代码为金融业务模块
     *********************************************************************************************
     */

    /**
     * 赎楼申请列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-22
     */
    public function foreclosure_list($page = 1) {
        $data['limit'] = $this->limit;//每页显示多少调数据
        $data['work_no'] = trim($this->input->get('work_no')) ? trim($this->input->get('work_no')) : null;
        $data['status'] = trim($this->input->get('status')) ? trim($this->input->get('status')) : null;
        $data['ml2_id'] = trim($this->input->get('ml2_id')) ? trim($this->input->get('ml2_id')) : null;
        $data['s_s_date'] = trim($this->input->get('s_s_date')) ? trim($this->input->get('s_s_date')) : date('Y-m-d', strtotime("-1 month"));
        $data['s_e_date'] = trim($this->input->get('s_e_date')) ? trim($this->input->get('s_e_date')) : date('Y-m-d');
        $m_list_arr_ = array();
        if($data['ml2_id']){
            $m_list_ = $this->db->select('m_id')->from('members')->where('m_id', $data['ml2_id'])->get()->result_array();
            foreach($m_list_ as $v){
                $m_list_arr_[] = $v['m_id'];
            }
        }
        $this->db->select('count(1) num');
        $this->db->from('foreclosure f');
        $this->db->join('members m', 'm.m_id = f.m_id', 'left');
        $this->db->join('users us', 'us.user_id = f.user_id', 'left');
        $this->db->where_in('f.status', array(2, 3, 4)); //后台只显示待审核,审核通过,终审通过
        if ($data['work_no']) {
            $this->db->like('f.work_no', $data['work_no']);
        }
        if ($data['status']) {
            $this->db->where('f.status', $data['status']);
        }
        if ($data['s_s_date']) {
            $this->db->where('f.submit_time >=', strtotime($data['s_s_date'] . " 00:00:00"));
        }
        if ($data['s_e_date']) {
            $this->db->where('f.submit_time <=', strtotime($data['s_e_date'] . " 23:59:59"));
        }
        if($m_list_arr_){
            $this->db->where_in('f.m_id', $m_list_arr_);
        }

        $rs_total = $this->db->get()->row();
        //die(var_dump($this->db->last_query()));
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;

        //list
        $this->db->select('f.*, us.rel_name us_rel_name_, us.mobile us_mobile_, m.rel_name m_rel_name_, m.mobile m_mobile_');
        $this->db->from('foreclosure f');
        $this->db->join('members m', 'm.m_id = f.m_id', 'left');
        $this->db->join('users us', 'us.user_id = f.user_id', 'left');
        $this->db->where_in('f.status', array(2, 3, 4)); //后台只显示待审核,审核通过,终审通过
        if ($data['work_no']) {
            $this->db->like('f.work_no', $data['work_no']);
        }
        if ($data['status']) {
            $this->db->where('f.status', $data['status']);
        }
        if ($data['s_s_date']) {
            $this->db->where('f.submit_time >=', strtotime($data['s_s_date'] . " 00:00:00"));
        }
        if ($data['s_e_date']) {
            $this->db->where('f.submit_time <=', strtotime($data['s_e_date'] . " 23:59:59"));
        }
        if($m_list_arr_){
            $this->db->where_in('f.m_id', $m_list_arr_);
        }
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('f.submit_time', 'desc');
        $data['res_list'] = $this->db->get()->result_array();
        $data['m_level_2'] = $this->db->select('')->from('members')->where(array('level' => 2))->get()->result_array();
        $data['m_level_2_3'] = $this->db->select('')->from('members')->where_in('level', array(2, 3))->get()->result_array();
        return $data;
    }

    /**
     * 赎楼申请详情
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-22
     */
    public function foreclosure_detail($id){
        $this->db->select('f.*, us.rel_name us_rel_name_, us.mobile us_mobile_, m.rel_name m_rel_name_, m.mobile m_mobile_');
        $this->db->from('foreclosure f');
        $this->db->join('members m', 'm.m_id = f.m_id', 'left');
        $this->db->join('users us', 'us.user_id = f.user_id', 'left');
        $this->db->where_in('f.status', array(2, 3, 4)); //后台只显示待审核,审核通过,终审通过
        $info_ = $this->db->where('foreclosure_id', $id)->get()->row();
        if(!$info_)
            return array();
        $res['work'] = $info_;
        $res['property_img'] = $this->db->from('foreclosure_property_img')->where('fc_id', $id)->order_by('sort_id','asc')->get()->result();
        //die(var_dump($res['property_img']));
        $res['credit_img'] = $this->db->from('foreclosure_credit_img')->where('fc_id', $id)->order_by('sort_id','asc')->get()->result();
        return $res;
    }

    //赎楼业务所属微管修改
    public function foreclosure_m_change() {
        $m_id = $this->input->post('sel_member_id');
        $f_id = $this->input->post('f_id');
        if(!$m_id){
            return $this->fun_fail('请选择新微管!');
        }
        $check_ = $this->db->select()->from('members')->where(array('m_id' => $m_id, 'status' => 1))->where_in('level', array(2, 3))->get()->row();
        if(!$check_){
            return $this->fun_fail('所选新微管,不规范!');
        }
        if(!$f_id){
            return $this->fun_fail('请选择赎楼业务!');
        }
        $this->db->where(array('foreclosure_id' => $f_id))->update('foreclosure', array('m_id' => $m_id));
        return $this->fun_success('操作成功!');
    }
}
