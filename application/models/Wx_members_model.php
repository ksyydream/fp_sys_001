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
    public function zj_users(){
        $this->db->select('m.m_id, m.rel_name,count(us.user_id) users_count_');
        $this->db->from('members m');
        $this->db->join('members m1', 'm1.parent_id = m.m_id or m1.m_id = m.m_id','left');
        $this->db->join('users us', 'us.invite = m1.m_id', 'left');
        $this->db->where('m.level', 2);
        $this->db->where('us.status', 1);
        $this->db->group_by('m.m_id');
        $res['list'] = $this->db->get()->result_array();
        return $res;
    }

    public function zj_users_list_load($member_info_){
        $page = $this->input->post('page') ? $this->input->post('page') : 1;
        $limit_ = 5;
        $m_id_ = $this->input->post('m_id') ? $this->input->post('m_id') : 0;
        $keyword_ = $this->input->post('keyword') ? $this->input->post('keyword') : '';
        $this->db->select('us.*, m1.rel_name m_rel_name_, m1.mobile m_mobile_,r1.name r1_name,r2.name r2_name,r3.name r3_name,r4.name r4_name');
        $this->db->from('members m');
        $this->db->join('members m1', 'm1.parent_id = m.m_id or m1.m_id = m.m_id','inner');
        $this->db->join('users us', 'm.m_id = us.invite', 'inner');
        $this->db->join('region r1', 'us.province = r1.id', 'left');
        $this->db->join('region r2', 'us.city = r2.id', 'left');
        $this->db->join('region r3', 'us.district = r3.id', 'left');
        $this->db->join('region r4', 'us.twon = r4.id', 'left');
        $this->db->where('m.level', 3);
        if($member_info_['level'] == 1){
            $this->db->where('m.m_id', $m_id_);
        }
        if($member_info_['level'] == 2){
            $this->db->where('m.m_id', $member_info_['m_id']);
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
        $data['list'] = $res;
        $data['is_finish'] = -1;
        if(count($res) < $limit_)
            $data['is_finish'] = 1;
        return $data;
    }

}