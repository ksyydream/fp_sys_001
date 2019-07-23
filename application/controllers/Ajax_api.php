<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ajax_api extends CI_Controller {

    /**
     * Ajax控制器
     * @version 1.0
     * @author yaobin <bin.yao@thmarket.cn>
     * @date 2017-12-20n
     * @Copyright (C) 2017, Tianhuan Co., Ltd.
     */
	public function __construct()
    {
        parent::__construct();
        ini_set('date.timezone','Asia/Shanghai');
        $this->load->library('image_lib');
        $this->load->helper('directory');
        $this->load->model('manager_model');
        $this->load->model('ajax_model');
    }


    /**
     * 上传头像
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-3-31
     */
    public function upload_head(){
        $admin_info = $this->session->userdata('admin_info');
        if(!$admin_info){
            echo -1;//如果没有登陆 不可上传,以免有人恶意上传图片占用服务器资源
        }
        $dir = FCPATH . '/upload_files/head';
        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }
        $config['upload_path'] = './upload_files/head/';
        $config['allowed_types'] = 'gif|jpg|png|jpeg';
        $config['encrypt_name'] = true;
        $config['max_size'] = '3200';
        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('userfile')){
            echo 1;
        }else{
            $pic_arr = $this->upload->data();
            echo $pic_arr['file_name'];
        }
    }

    public function wx_notify(){
        die();
        $this->load->config('wxpay_config');
        $wx_config = array();
        $wx_config['appid']=$this->config->item('appid');
        $wx_config['mch_id']=$this->config->item('mch_id');
        $wx_config['apikey']=$this->config->item('apikey');
        $wx_config['appsecret']=$this->config->item('appsecret');
        $wx_config['sslcertPath']=$this->config->item('sslcertPath');
        $wx_config['sslkeyPath']=$this->config->item('sslkeyPath');
        $this->load->library('wxpay/Wechatpay',$wx_config);
        $data_array = $this->wechatpay->get_back_data();
        if($data_array['result_code']=='SUCCESS' && $data_array['return_code']=='SUCCESS'){
            if($this->ajax_api_model->wx_change_order($data_array['out_trade_no']) != 1){
                return 'FAIL';
            }else{
                return 'SUCCESS';
            }
        }
    }

    public function create_work_order(){
        die();
        exit();
        $rs = $this->ajax_api_model->create_work_order();
        echo json_encode($rs);
    }

    public function create_info($flag){
        exit();
        die();
        //set_time_limit(0);
        $this->manager_model->create_info($flag);
    }

}
