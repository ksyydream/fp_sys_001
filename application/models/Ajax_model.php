<?php
/**
 * Created by PhpStorm.
 * User: bin.shen
 * Date: 6/2/16
 * Time: 21:22
 */

class Ajax_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    public function show_members_list4users($status= null){
        $this->db->select()->from('members');
        $this->db->where_in('level', array(2, 3));
        if($status)
            $this->db->where(array('status' => $status));
        $data = $this->db->get()->result_array();
        return $data;
    }

    public function get_users_info($user_id){
        $this->db->select('us.*, m.rel_name m_rel_name_,m.mobile m_mobile_');
        $this->db->from('users us');
        $this->db->join('members m', 'm.m_id = us.invite', 'left');
        $this->db->where(array('user_id' => $user_id));
        $user_info = $this->db->get()->row_array();
        return $user_info;
    }
}