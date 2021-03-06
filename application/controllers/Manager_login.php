<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Manager_login extends MY_Controller {

    /**
     * 管理员 操作控制器
     * @version 1.0
     * @author yaobin <bin.yao@thmarket.cn>
     * @date 2017-12-22
     * @Copyright (C) 2017, Tianhuan Co., Ltd.
    */
	public function __construct()
    {
        parent::__construct();
        $this->load->model('manager_model');
        $this->load->model('ajax_model');
        $ignore_methods = array(
            'index', 'check_login', 'get_cap', ''
        );
        //判断用户是否登录
        if(!$this->session->userdata('admin_info') && !in_array($this->uri->segment(2), $ignore_methods)){
            echo -1;//如果没有登陆
            die();
        }
    }

    /**
     * 登陆页面
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-29
     */
    public function index($flag = null)
	{
        $this->assign('flag',$flag);
        $this->display('manager/login/index.html');
	}

    /**
     * 账号登陆
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-29
     */
    public function check_login(){
        $rs = $this->manager_model->check_login();
        if($rs > 0){
            redirect(base_url('/manager/index'));
            exit();
        }else{
            redirect(base_url('/manager_login/index/'.$rs));
        }
    }

    /**
     * 验证码获取函数
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-29
     */
    public function get_cap(){
        $vals = array(
            //'word'      => 'Random word',
            'img_path'  => './upload/captcha/',
            'img_url'   => '/upload/captcha/',
            'img_width' => '120',
            'img_height'    => 30,
            'expiration'    => 7200,
            'word_length'   => 4,
            'font_size' => 18,
            'img_id'    => 'Imageid',
            'pool'      => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',

            // White background and border, black text and red grid
            'colors'    => array(
                'background' => array(255, 255, 255),
                'border' => array(255, 255, 255),
                'text' => array(0, 0, 0),
                'grid' => array(255, 40, 40)
            )
        );

        $rs = create_captcha($vals);
        $this->session->set_flashdata('cap', $rs['word']);
    }


    public function save_pics4con($time){
        $this->load->library('image_lib');

        if (is_readable('./././upload/consignment') == false) {
            mkdir('./././upload/consignment');
        }
        if (is_readable('./././upload/consignment/'.$time) == false) {
            mkdir('./././upload/consignment/'.$time);
        }
        $path = './././upload/consignment/'.$time;

        //设置缩小图片属性
        $config_small['image_library'] = 'gd2';
        $config_small['create_thumb'] = TRUE;
        $config_small['quality'] = 80;
        $config_small['maintain_ratio'] = TRUE; //保持图片比例
        $config_small['new_image'] = $path;
        $config_small['width'] = 300;
        $config_small['height'] = 190;

        //设置原图限制
        $config['upload_path'] = $path;
        $config['allowed_types'] = 'gif|jpg|png|jpeg';
        $config['max_size'] = '10000';
        $config['encrypt_name'] = true;
        $this->load->library('upload', $config);

        if($this->upload->do_upload()){
            $data = $this->upload->data();//返回上传文件的所有相关信息的数组
            $config_small['source_image'] = $data['full_path']; //文件路径带文件名
            $this->image_lib->initialize($config_small);
            $this->image_lib->resize();

            echo 1;
        }else{
            echo -1;
        }
        exit;
    }

    //ajax获取图片信息
    public function get_pics4con($time){
        $this->load->helper('directory');
        $path = './././upload/consignment/'.$time;
        $map = directory_map($path);
        $data = array();
        //整理图片名字，取缩略图片
        foreach($map as $v){
            if(substr(substr($v,0,strrpos($v,'.')),-5) == 'thumb'){
                $data['img'][] = $v;
            }
        }
        $data['time'] = $time;
        echo json_encode($data);
    }

    //微信会员-门店/直客列表-选择所属管理员时的弹窗
    public function show_members_list4users($user_id, $status = null){
        $data = $this->ajax_model->show_members_list4users($status);
        $user_info = $this->ajax_model->get_users_info($user_id);
        $this->assign('data', $data);
        $this->assign('user_info', $user_info);
        $this->display('manager/users/show_members_list4users.html');
    }

    //微信会员-管理员列表-选择所属新总监时的弹窗
    public function show_level2_list4members($m_id){
        $data = $this->ajax_model->show_level2_list4members();
        $m_info = $this->ajax_model->get_members_info($m_id);
        if($m_info['level'] != 3){
            echo '此微信管理员账号不可划分总监!';
            exit();
        }
        $this->assign('data', $data);
        $this->assign('m_info', $m_info);
        $this->display('manager/members/show_level2_list4members.html');
    }

    //金融业务-赎楼列表-选择所属管理员时的弹窗
    public function show_level23_list4f($f_id){
        $data = $this->ajax_model->show_members_list4users(1);
        $this->load->model('foreclosure_model');
        $f_info = $this->foreclosure_model->get_foreclosure($f_id);
        if(!$f_info){
            echo '赎楼工作单不存在!';
            exit();
        }
        $m_info = $this->ajax_model->get_members_info($f_info['m_id']);
        $this->assign('data', $data);
        $this->assign('f_info', $f_info);
        $this->assign('m_info', $m_info);
        $this->display('manager/foreclosure/show_level23_list4f.html');
    }
}
