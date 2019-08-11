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
        $this->db->select('m.rel_name,count(us.user_id) users_count_');
        $this->db->from('members m');
        $this->db->join('members m1', 'm1.parent_id = m.m_id or m1.m_id = m.m_id','left');
        $this->db->join('users us', 'us.invite = m1.m_id', 'left');
        $this->db->where('m.level', 2);
        $this->db->where('us.status', 1);
        $this->db->group_by('m.m_id');
        $res['list'] = $this->db->get()->result_array();
        return $res;
    }

}