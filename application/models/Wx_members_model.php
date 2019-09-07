<?php
/**
 * Created by PhpStorm.
 * User: bin.shen
 * Date: 5/9/16
 * Time: 13:40
 */

class Wx_members_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    public function get_member_info($m_id){
        return $this->db->select()->from('members')->where('m_id', $m_id)->get()->row_array();
    }

    //总监组 门店/直客人数
    public function zj_users($member_info_){
        $this->db->select('m.m_id, m.rel_name,count(us.user_id) users_count_');
        $this->db->from('members m');
        $this->db->join('members m1', 'm1.parent_id = m.m_id or m1.m_id = m.m_id','left');
        $this->db->join('users us', 'us.invite = m1.m_id', 'left');
        $this->db->where('m.level', 2);
        $this->db->where('us.status', 1);
        if($member_info_ && $member_info_['level'] == 2){
            $this->db->where('m.m_id', $member_info_['m_id']);
        }
        if($member_info_ && $member_info_['level'] == 3){
            $this->db->where('m.m_id', $member_info_['parent_id']);
            $this->db->where('m1.m_id', $member_info_['m_id']);
        }
        $this->db->group_by('m.m_id');
        $res['list'] = $this->db->get()->result_array();
        foreach($res['list'] as $k => $v){
            $this->db->select('m.m_id, m.rel_name,count(us.user_id) users_count_');
            $this->db->from('members m');
            $this->db->join('users us', 'us.invite = m.m_id', 'left');
            //$this->db->where('m.level', 2);
            $this->db->where('us.status', 1);
            $this->db->where(" (m.parent_id = {$v['m_id']} or m.m_id = {$v['m_id']})");
            if($member_info_ && $member_info_['level'] == 3){
                $this->db->where('m.m_id', $member_info_['m_id']);
            }
            $this->db->order_by('m.level asc');
            $this->db->group_by('m.m_id');
            $res['list'][$k]['m_list'] = $this->db->get()->result_array();
            //die(var_dump($this->db->last_query()));
        }
        //die(var_dump($res));
        return $res;
    }

    public function zj_users_list_load($member_info_){
        $page = $this->input->post('page') ? $this->input->post('page') : 1;
        $limit_ = 5;
        $m_id_ = $this->input->post('m_id') ? $this->input->post('m_id') : 0;
        $parent_id_ = $this->input->post('parent_id') ? $this->input->post('parent_id') : 0;
        $keyword_ = $this->input->post('keyword') ? $this->input->post('keyword') : '';
        $this->db->select('us.*, m1.rel_name m_rel_name_, m1.mobile m_mobile_,r1.name r1_name,r2.name r2_name,r3.name r3_name,r4.name r4_name');
        $this->db->from('members m');
        $this->db->join('members m1', 'm1.parent_id = m.m_id or m1.m_id = m.m_id','inner');
        $this->db->join('users us', 'm1.m_id = us.invite', 'inner');
        $this->db->join('region r1', 'us.province = r1.id', 'left');
        $this->db->join('region r2', 'us.city = r2.id', 'left');
        $this->db->join('region r3', 'us.district = r3.id', 'left');
        $this->db->join('region r4', 'us.twon = r4.id', 'left');
        $this->db->where('m.level', 2);
        $this->db->where('us.status', 1);
        if($member_info_['level'] == 1){
            $this->db->where('m.m_id', $parent_id_);
            if($m_id_){
                $this->db->where('m1.m_id', $m_id_);
            }
        }
        if($member_info_['level'] == 2){
            $this->db->where('m.m_id', $member_info_['m_id']);
            if($m_id_){
                $this->db->where('m1.m_id', $m_id_);
            }
        }
        if($member_info_['level'] == 3){
            $this->db->where('m1.m_id', $member_info_['m_id']);
        }
        if($keyword_){
            $this->db->group_start();
            $this->db->like('us.rel_name', $keyword_);
            $this->db->or_like('us.mobile', $keyword_);
            $this->db->or_like('m1.rel_name', $keyword_);
            $this->db->or_like('m1.mobile', $keyword_);
            $this->db->group_end();
        }
        $this->db->limit($limit_, ($page - 1) * $limit_ );
        $res = $this->db->order_by('us.reg_time', 'desc')->get()->result_array();
        //die(var_dump($this->db->last_query()));
        $data['list'] = $res;
        $data['is_finish'] = -1;
        if(count($res) < $limit_)
            $data['is_finish'] = 1;
        return $data;
    }

    public function user_info_edit($user_id){
        $data = $this->input->post();
        $update_ = array();
        if(!$data['rel_name']){
            return $this->fun_fail('请填写姓名!');
        }
        switch($data['type_id']){
            case 1:
                //门店注册需要保存门店信息
                if(!$data['shop_name']){
                    return $this->fun_fail('请填写门店名称!');
                }

                $area_value = $data['area_value'];
                if(!$area_value){
                    return $this->fun_fail('请选择区域!');
                }
                $area_arr = explode(',', $area_value);
                if(!$area_arr[0] || !isset($area_arr[1]) || !isset($area_arr[2])){
                    return $this->fun_fail('必须选择区域!');
                }
                //区域保存
                $update_['shop_name'] = $data['shop_name'];
                $update_['province'] = $area_arr[0];
                $update_['city'] = isset($area_arr[1]) ? $area_arr[1] : 0;
                $update_['district'] = isset($area_arr[2]) ? $area_arr[2] : 0;
                $update_['twon'] = isset($area_arr[3]) ? $area_arr[3] : 0;
                $update_['address'] = $data['address'];
                if(!$update_['address']){
                    return $this->fun_fail('必须选择区域!');
                }
                break;
            case 2:
                break;
            default:
                return $this->fun_fail('请选择注册类型!');
        }
        $update_['type_id'] = $data['type_id'];
        $update_['rel_name'] = $data['rel_name'];
        $this->db->where('user_id', $user_id)->update('users', $update_);
        return $this->fun_success('操作成功');
    }

    public function check_user4m($member_info_, $user_id){
        if(!in_array($member_info_['level'], array(1,2,3))){
            return false;
        }
        $this->db->select('us.*, m1.rel_name m_rel_name_, m1.mobile m_mobile_');
        $this->db->from('members m');
        $this->db->join('members m1', 'm1.parent_id = m.m_id or m1.m_id = m.m_id','inner');
        $this->db->join('users us', 'm1.m_id = us.invite', 'inner');
        $this->db->where('m.level', 2);
        $this->db->where('us.status', 1);
        $this->db->where('us.user_id', $user_id);
        if($member_info_['level'] == 1){
            //$this->db->where('m.m_id', $m_id_);
        }
        if($member_info_['level'] == 2){
            $this->db->where('m.m_id', $member_info_['m_id']);
        }
        if($member_info_['level'] == 3){
            $this->db->where('m1.m_id', $member_info_['m_id']);
        }
        $res = $this->db->order_by('us.reg_time', 'desc')->get()->row_array();
        if($res){
            return true;
        }else{
            return false;
        }
    }

    //获取总监及其组员信息
    public function get_m_select_list($m_id = 0){
        $this->db->from('members m');
        $this->db->where(" (m.parent_id = {$m_id} or m.m_id = {$m_id})");
        $this->db->order_by('m.level asc');
        return $this->db->get()->result_array();
    }

    //总监组 组员签单数量
    public function zj_fs($member_info_){
        $this->db->select('m.m_id, m.rel_name,count(f.foreclosure_id) f_count_');
        $this->db->from('members m');
        $this->db->join('members m1', 'm1.parent_id = m.m_id or m1.m_id = m.m_id','left');
        $this->db->join('users us', 'us.invite = m1.m_id', 'left');
        $this->db->join('foreclosure f', 'us.user_id = f.user_id and f.status in (2,3,4,-2,-3)', 'left');
        $this->db->where('m.level', 2);
        $this->db->where('us.status', 1);
        //$this->db->where_in('f.status', array(2,3,4,-2,-3));
        if($member_info_ && $member_info_['level'] == 2){
            $this->db->where('m.m_id', $member_info_['m_id']);
        }
        if($member_info_ && $member_info_['level'] == 3){
            $this->db->where('m.m_id', $member_info_['parent_id']);
            $this->db->where('m1.m_id', $member_info_['m_id']);
        }
        $this->db->group_by('m.m_id');
        $res['list'] = $this->db->get()->result_array();
        foreach($res['list'] as $k => $v){
            $this->db->select('m.m_id, m.rel_name,count(f.foreclosure_id) f_count_');
            $this->db->from('members m');
            $this->db->join('users us', 'us.invite = m.m_id', 'left');
            $this->db->join('foreclosure f', 'us.user_id = f.user_id  and f.status in (2,3,4,-2,-3)', 'left');
           // $this->db->where_in('f.status', array(2,3,4,-2,-3));
            $this->db->where('us.status', 1);
            $this->db->where(" (m.parent_id = {$v['m_id']} or m.m_id = {$v['m_id']})");
            if($member_info_ && $member_info_['level'] == 3){
                $this->db->where('m.m_id', $member_info_['m_id']);
            }
            $this->db->order_by('m.level asc');
            $this->db->group_by('m.m_id');
            $res['list'][$k]['m_list'] = $this->db->get()->result_array();
            //die(var_dump($this->db->last_query()));
        }
        //die(var_dump($res));
        return $res;
    }

}