<?php
/**
 * Created by PhpStorm.
 * User: bin.shen
 * Date: 5/2/16
 * Time: 09:56
 */

 if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once "Wx_controller.php";
class Wx_members extends Wx_controller {
    private $m_id;
    private $m_info = [];
    public function __construct()
    {
        parent::__construct();
        $this->load->model('wx_index_model');
        $this->load->model('wx_members_model');
        $this->load->model('foreclosure_model');
        $this->load->model('wx_users_model');
        if($this->session->userdata('wx_class') != 'members' || !$this->session->userdata('wx_m_id') ){
            redirect('wx_index/logout');
        }
        $this->m_id = $this->session->userdata('wx_m_id');
        $this->m_info = $this->wx_members_model->get_member_info($this->m_id);
        if(!$this->m_info){
            redirect('wx_index/logout');
        }
        if($this->m_info['status'] != 1){
            redirect('wx_index/logout');
        }
        $this->assign('m_info', $this->m_info);
    }


    public function index() {
        redirect('wx_members/person_info');
        //$this->display('members/index.html');
    }

    public function person_info(){
        $this->display('members/person_info.html');
    }

    public function create_RQimg(){
        $this->load->library('wxjssdk_th',array('appid' => $this->config->item('appid'), 'appsecret' => $this->config->item('appsecret')));
        $access_token = $this->wxjssdk_th->wxgetAccessToken();
        $img_url = $this->get_or_create_ticket($access_token, 'QR_STR_SCENE', $this->m_info['invite_code']);
        $this->buildWxData();
        $this->assign('img_url',$img_url);
        $this->display('members/wx_qr_code.html');
    }

    //赎楼列表
    public function foreclosure_list($status_type = 0, $m_id = 0){
        $this->assign('status_type', $status_type);
        $show_m_info_ = $this->wx_members_model->get_member_info($m_id);
        if($show_m_info_){
            $this->assign('m_id', $m_id);
            $this->assign('show_m_info_', $show_m_info_);
        }else{
            $this->assign('m_id', 0);
            $this->assign('show_m_info_', '我的');
        }

        $this->display('members/foreclosure/list1.html');
    }

    public function foreclosure_list_load(){
        $res = $this->foreclosure_model->get_list4members();
        $this->assign('list', $res['list']);
        $this->assign('is_finish', $res['is_finish']);
        $this->display('members/foreclosure/list_data_load.html');
    }

    //检查 管理员 是否可以修改赎楼业务
    //只有总监可以修改和审核 , 也就是只有level是2的用户,并且是关联的总监才有权限
    private function check_foreclosure_edit($f_id = 0){
        //$fc_deadline_ = $this->config->item('fc_deadline'); //缓存数据使用限期,这里是秒为单位的
        $f_info = $this->foreclosure_model->get_foreclosure($f_id);
        $m_info = $this->m_info;
        if(IS_POST){
            if(!$f_info){
                $res = $this->foreclosure_model->fun_fail('工作单不存在!');
                $this->ajaxReturn($res);
            }
            switch($f_info['status']){
                case 2:
                    if($m_info['level'] != 2){
                        $res = $this->foreclosure_model->fun_fail('待审核时,只有总监级才有修改和审核权限!');
                        $this->ajaxReturn($res);
                    }
                    //获取直接对接 业务员的信息
                    $manger_info = $this->wx_members_model->get_member_info($f_info['m_id']);
                    if($f_info['m_id'] != $m_info['m_id'] && $manger_info['parent_id'] != $m_info['m_id']){
                        $res = $this->foreclosure_model->fun_fail('你没有权限修改,或审核此工作单!');
                        $this->ajaxReturn($res);
                    }
                    break;
                case 3:
                    if($m_info['level'] != 1){
                        $res = $this->foreclosure_model->fun_fail('审核通过时,只有总经理才有修改和审核权限!');
                        $this->ajaxReturn($res);
                    }
                    break;
                default:
                    $res = $this->foreclosure_model->fun_fail('工作单已不可修改!');
                    $this->ajaxReturn($res);
            }
        }else{
            if(!$f_info){
                redirect('wx_members/foreclosure_list'); //不是自己的工作单,就直接回到首页
            }
            switch($f_info['status']){
                case 2:
                    if($m_info['level'] != 2){
                        redirect('wx_members/foreclosure_list'); //不是自己的工作单,就直接回到首页
                    }
                    //获取直接对接 业务员的信息
                    $manger_info = $this->wx_members_model->get_member_info($f_info['m_id']);
                    if($f_info['m_id'] != $m_info['m_id'] && $manger_info['parent_id'] != $m_info['m_id']){
                        redirect('wx_members/foreclosure_list'); //不是自己的工作单,就直接回到首页
                    }
                    break;
                case 3:
                    if($m_info['level'] != 1){
                        redirect('wx_members/foreclosure_list'); //不是自己的工作单,就直接回到首页
                    }
                    break;
                default:
                    redirect('wx_members/foreclosure_detail1/' . $f_id); ////如果工作单不是待审核,就到详情页面
            }
        }
        return true;
    }

    /**
     * 申请赎楼 借款人信息
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-10
     */
    public function foreclosure_show($f_id = 0){
        $this->check_foreclosure_edit($f_id); //检查权限
        $f_info = $this->foreclosure_model->get_foreclosure($f_id);
        $this->assign('f_info', $f_info);
        $this->assign('now_time', $f_info['now_time']);
        $this->display('members/foreclosure/step_show.html');
    }

    /**
     * 申请赎楼 第二步,填写主贷人和类型
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-10
     */
    public function foreclosure_s2($f_id = 0){

        if(IS_POST){
            $f_id = $this->input->post('fc_id');
            $this->check_foreclosure_edit($f_id); //检查权限
            $res = $this->foreclosure_model->edit_foreclosure4s2();
            $this->ajaxReturn($res);
        }
        $this->check_foreclosure_edit($f_id); //检查权限
        $f_info = $this->foreclosure_model->get_foreclosure($f_id);
        $this->assign('f_info', $f_info);
        $this->display('members/foreclosure/step2.html');
    }

    /**
     * 申请赎楼 贷款信息
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-12
     */
    public function foreclosure_s3($f_id = 0){

        if(IS_POST){
            $f_id = $this->input->post('fc_id');
            $this->check_foreclosure_edit($f_id); //检查权限
            $res = $this->foreclosure_model->edit_foreclosure4s3();
            $this->ajaxReturn($res);
        }
        $this->check_foreclosure_edit($f_id); //检查权限
        $f_info = $this->foreclosure_model->get_foreclosure($f_id);
        if($f_info['bank_loan_type'] == 1){
            //redirect('wx_users/foreclosure_s4/' . $f_id); //如果是一次性付款 不需要填写此页面
        }
        $this->assign('f_info', $f_info);
        $this->display('members/foreclosure/step3.html');
    }

    /**
     * 申请赎楼 上传身份证
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-10
     */
    public function foreclosure_s4($f_id = 0){
        if(IS_POST){
            $f_id = $this->input->post('fc_id');
            $this->check_foreclosure_edit($f_id); //检查权限
            $res = $this->foreclosure_model->edit_foreclosure4s4();
            $this->ajaxReturn($res);
        }
        $this->check_foreclosure_edit($f_id); //检查权限
        $f_info = $this->foreclosure_model->get_foreclosure($f_id);
        $this->buildWxData();
        $this->assign('f_info', $f_info);
        $this->display('members/foreclosure/step4.html');
    }

    /**
     * 申请赎楼 上传房产证
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-10
     */
    public function foreclosure_s5($f_id = 0){
        if(IS_POST){
            $f_id = $this->input->post('fc_id');
            $this->check_foreclosure_edit($f_id); //检查权限
            $res = $this->foreclosure_model->edit_foreclosure4s5();
            $this->ajaxReturn($res);
        }
        $this->check_foreclosure_edit($f_id); //检查权限
        $f_info = $this->foreclosure_model->get_foreclosure($f_id);
        $property_img_list = $this->foreclosure_model->get_property_img($f_id);
        $this->buildWxData();
        $this->assign('f_info', $f_info);
        $this->assign('property_img_list', $property_img_list);
        $this->display('members/foreclosure/step5.html');
    }

    /**
     * 申请赎楼 上传征信报告
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-10
     */
    public function foreclosure_s6($f_id = 0){
        if(IS_POST){
            $f_id = $this->input->post('fc_id');
            $this->check_foreclosure_edit($f_id); //检查权限
            $res = $this->foreclosure_model->edit_foreclosure4s6();
            $this->ajaxReturn($res);
        }
        $this->check_foreclosure_edit($f_id); //检查权限
        $f_info = $this->foreclosure_model->get_foreclosure($f_id);
        $credit_img_list = $this->foreclosure_model->get_credit_img($f_id);
        $this->buildWxData();
        $this->assign('f_info', $f_info);
        $this->assign('credit_img_list', $credit_img_list);
        $this->display('members/foreclosure/step6.html');
    }

    //赎楼详情页面 公共验证
    private function foreclosure_detail_common($f_id = 0){
        $f_info = $this->foreclosure_model->get_foreclosure($f_id);
        if(!$f_info){
            redirect('wx_users/index'); //不是自己的工作单,就直接回到首页
        }
        $user_info = $this->wx_users_model->get_user_info($f_info['user_id']);
        $this->assign('f_info', $f_info);
        $this->assign('user_info', $user_info);
        //获取直接对接 业务员的信息
        $m_info = $this->m_info;
        $manger_info = $this->wx_members_model->get_member_info($f_info['m_id']);
        //如果是 财务 ,可以看所有
        if($m_info['level'] == 4 || $m_info['level'] == 1){

        }else{
            if($f_info['m_id'] != $m_info['m_id'] && $manger_info['parent_id'] != $m_info['m_id']){
                redirect('wx_members/foreclosure_list'); //不是自己的工作单,就直接回到首页
            }
        }
        //如果是审核页面detail6,则需要额外的验证
        if($this->uri->segment(2) == 'foreclosure_detail6'){
            if($m_info['level'] != 2){
                redirect('wx_members/foreclosure_list'); //不是总监,就直接回到首页
            }
            if($f_info['status'] != 2){
                redirect('wx_members/foreclosure_list'); //赎楼不是待审核,直接回到首页
            }
        }
        return true;
    }
    //赎楼详情页 1
    public function foreclosure_detail1($f_id = 0){
        $this->foreclosure_detail_common($f_id);
        $this->display('members/foreclosure/detail1.html');
    }

    //赎楼详情页 身份证
    public function foreclosure_detail3($f_id = 0){
        $this->foreclosure_detail_common($f_id);
        $this->display('members/foreclosure/detail3.html');
    }

    //赎楼详情页 房产证
    public function foreclosure_detail4($f_id = 0){
        $this->foreclosure_detail_common($f_id);
        $property_img_list = $this->foreclosure_model->get_property_img($f_id);
        $this->assign('property_img_list', $property_img_list);
        $this->display('members/foreclosure/detail4.html');
    }

    //赎楼详情页 房产证
    public function foreclosure_detail5($f_id = 0){
        $this->foreclosure_detail_common($f_id);
        $credit_img_list = $this->foreclosure_model->get_credit_img($f_id);
        $this->assign('credit_img_list', $credit_img_list);
        $this->display('members/foreclosure/detail5.html');
    }

    //赎楼审核页面
    public function foreclosure_detail6($f_id = 0){
        $this->foreclosure_detail_common($f_id);
        $file_list = $this->foreclosure_model->get_file_list();
        $this->assign('file_list', $file_list);
        $this->display('members/foreclosure/detail6.html');
    }

    //赎楼详情页 材料列表
    public function foreclosure_detail7($f_id = 0){
        $this->foreclosure_detail_common($f_id);
        $file_list = $this->foreclosure_model->get_file_listbyFid($f_id);
        $this->assign('file_list', $file_list);
        $this->display('members/foreclosure/detail7.html');
    }

    //赎楼审核
    public function foreclosure_audit(){
        $res = $this->foreclosure_model->foreclosure_audit($this->m_info);
        $this->ajaxReturn($res);
    }

    //赎楼 设置绿色通道
    public function foreclosure_special(){
        $res = $this->foreclosure_model->foreclosure_special($this->m_info);
        $this->ajaxReturn($res);
    }

    public function foreclosure_finish(){
        $res = $this->foreclosure_model->foreclosure_finish($this->m_info);
        $this->ajaxReturn($res);
    }

    public function foreclosure_err_finish(){
        $res = $this->foreclosure_model->foreclosure_err_finish($this->m_info);
        $this->ajaxReturn($res);
    }

    //总监组 门店/直客人数
    public function zj_users(){
        //只有总经理 和 总监可以查看
        if(!in_array($this->m_info['level'], array(1, 2, 3, 4))){
            redirect('wx_index/index');
        }
        $res = $this->wx_members_model->zj_users($this->m_info);
        $this->assign('list', $res['list']);
        $this->display('members/users/customer-manger.html');
    }

    //门店/直客 列表
    public function zj_users_list($parent_id = 0, $m_id = 0){
        $show_m_info_ = array();
        $select_list = array();
        $m_id = $this->input->post('m_id') ? $this->input->post('m_id') : $m_id;
        $parent_id = $this->input->post('parent_id') ? $this->input->post('parent_id') : $parent_id;
        if($this->m_info['level'] == 1 || $this->m_info['level'] == 4){
            $show_m_info_ = $this->wx_members_model->get_member_info($parent_id);
            $select_list = $this->wx_members_model->get_m_select_list($parent_id);
        }
        if($this->m_info['level'] == 2 || $this->m_info['level'] == 3){
            $show_m_info_ = $this->wx_members_model->get_member_info($this->m_id);
            $select_list = $this->wx_members_model->get_m_select_list($this->m_id);
        }
        if(!$show_m_info_){
            redirect('wx_index/index');
        }
        $this->assign('show_m_info_', $show_m_info_);
        $this->assign('select_list', $select_list);
        $this->assign('parent_id', $parent_id);
        $this->assign('m_id', $m_id);
        $this->assign('keyword', $this->input->post('keyword'));
        $this->display('members/users/customer-search.html');
    }

    public function zj_users_list_load(){
        $res = $this->wx_members_model->zj_users_list_load($this->m_info);
        $this->assign('m_info', $this->m_info);
        $this->assign('list', $res['list']);
        $this->assign('is_finish', $res['is_finish']);
        $this->display('members/users/zj_users_list_load.html');
    }

    public function user_info_edit($user_id){
        if(IS_POST){
            $check_ = $this->wx_members_model->check_user4m($this->m_info,$user_id);
            if(!$check_){
                $res = $this->wx_users_model->fun_fail('无权限操作此用户!');
                $this->ajaxReturn($res);
            }
            $res = $this->wx_members_model->user_info_edit($user_id);
            $this->ajaxReturn($res);
        }else{
            $user_ = $this->wx_users_model->get_user_info($user_id);
            if(!$user_ || $user_['status'] != 1){
                redirect('wx_index/index');
            }
            $check_ = $this->wx_members_model->check_user4m($this->m_info, $user_id);
            if(!$check_){
                redirect('wx_index/index');
            }
            $this->assign('user_info', $user_);
            $index_arr = $this->wx_index_model->new_region($user_['district'], $user_['twon']);
            $this->assign('index_1', $index_arr['index_arr']['index_1']);
            $this->assign('index_2', $index_arr['index_arr']['index_2']);
            $this->assign('index_3', $index_arr['index_arr']['index_3']);
            $this->assign('index_4', $index_arr['index_arr']['index_4']);
            $user_info_ = $this->wx_users_model->get_user_info4region($user_id);
            $this->assign('user_region', $user_info_);
            $this->display('members/users/user_info_edit.html');
        }
    }

    //总监组 组员签单数量
    public function zj_fs(){
        //只有总经理 和 总监可以查看
        if(!in_array($this->m_info['level'], array(1, 2, 4))){
            redirect('wx_index/index');
        }
        $res = $this->wx_members_model->zj_fs($this->m_info);
        $this->assign('list', $res['list']);
        $this->display('members/users/customer-manger-3.html');
    }

    //门店/直客 列表
    public function zj_shop_list(){
        //只有总经理可以查看
        if(!in_array($this->m_info['level'], array(1, 4))){
            redirect('wx_index/index');
        }
        $this->assign('keyword', $this->input->post('keyword'));
        $this->display('members/users/customer-manger-2.html');
    }

    public function zj_shop_list_load(){
        $res = $this->wx_members_model->zj_shop_list_load($this->m_info);
        $this->assign('list', $res['list']);
        $this->assign('is_finish', $res['is_finish']);
        $this->display('members/users/zj_shop_list_load.html');
    }
}