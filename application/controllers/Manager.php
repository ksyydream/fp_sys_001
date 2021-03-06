<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Manager extends MY_Controller {
    /**
     * 管理员操作控制器
     * @version 2.0
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-30
     * @Copyright (C) 2018, Tianhuan Co., Ltd.
    */
    private $admin_id = 0;

	public function __construct()
    {
        parent::__construct();
        $this->load->model('manager_model');
        $admin_info = $this->session->userdata('admin_info');
        $admin = $this->manager_model->get_admin($admin_info['admin_id']);
        if(!$admin){
           $this->logout();
        }
        $this->manager_model->save_admin_log($admin_info['admin_id']);
        $this->admin_id = $admin_info['admin_id'];
        if ($admin['group_id'] != 1 && !$this->manager_model->check($this->uri->segment(1) . '/' . $this->uri->segment(2), $admin_info['admin_id'])){
            if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            {
                $res_ = $this->manager_model->fun_fail('您无操作权限!');
                $this->ajaxReturn($res_);
            }
            else {
                $this->show_message('没有权限访问本页面!');
            }
        }
        $this->assign('admin', $admin);
        $current = $this->manager_model->get_action_menu($this->uri->segment(1),$this->uri->segment(2));
        $this->assign('current', $current);
        $menu = $this->manager_model->get_menu4admin($admin_info['admin_id']);
        $menu = $this->getMenu($menu);
        $this->assign('menu', $menu);
        $this->assign('self_url',$_SERVER['PHP_SELF']);
    }

    protected function getMenu($items, $id = 'id', $pid = 'pid', $son = 'children')
    {
        $tree = array();
        $tmpMap = array();
        //修复父类设置islink=0，但是子类仍然显示的bug @感谢linshaoneng提供代码
        foreach( $items as $item ){
            if( $item['pid']==0 ){
                $father_ids[] = $item['id'];
            }
        }
        //----
        foreach ($items as $item) {
            $tmpMap[$item[$id]] = $item;
        }

        foreach ($items as $item) {
            //修复父类设置islink=0，但是子类仍然显示的bug by shaoneng @感谢linshaoneng提供代码
            if( $item['pid']<>0 && !in_array( $item['pid'], $father_ids )){
                continue;
            }
            //----
            if (isset($tmpMap[$item[$pid]])) {
                $tmpMap[$item[$pid]][$son][] = &$tmpMap[$item[$id]];
            } else {
                $tree[] = &$tmpMap[$item[$id]];
            }
        }
        return $tree;
    }

    /**
     *********************************************************************************************
     * 以下代码为看板模块
     *********************************************************************************************
     */

    /**
     * 看板
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-30
     */
    public function index()
	{
        $this->display('manager/index/index.html');
	}


    /**
     *********************************************************************************************
     * 以下代码为系统设置模块
     *********************************************************************************************
     */

    /**
     * 后台菜单列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_list(){
        $menu_all = $this->manager_model->get_menu_all();
        $data['res_list'] = $this->getMenu($menu_all);
        $this->assign('data', $data);
        $this->display('manager/menu/index.html');
    }

    /**
     * 新增后台菜单页面
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_add(){
        $menu_all = $this->manager_model->get_menu_all();
        $data['res_list'] = $this->getMenu($menu_all);
        $this->assign('data', $data);
        $this->display('manager/menu/form.html');
    }

    /**
     * 编辑后台菜单
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_edit($id){
        $data = $this->manager_model->menu_info($id);
        if(!$data){
            $this->show_message('未找到菜单信息!');
        }
        $menu_all = $this->manager_model->get_menu_all();
        $data['res_list'] = $this->getMenu($menu_all);
        $this->assign('data', $data);
        $this->display('manager/menu/form.html');
    }

    /**
     * 保存后台菜单
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_save(){
        $res = $this->manager_model->menu_save();
        if($res == 1){
            $this->show_message('保存成功!', site_url('/manager/menu_list'));
        }elseif($res == -2){
            $this->show_message('信息不全,保存失败!');
        }else{
            $this->show_message('保存失败!');
        }
    }

    /**
     * 删除后台菜单
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_del($id){
        echo $this->manager_model->menu_del($id);
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
    public function admin_list($page=1){
        $data = $this->manager_model->admin_list($page);
        $base_url = "/manager/admin_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/admin/index.html');
    }

    /**
     * 新增管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function admin_add(){
        $groups = $this->manager_model->get_group_all();
        $this->assign('data', array());
        $this->assign('groups', $groups);
        $this->display('manager/admin/form.html');
    }

    /**
     * 编辑管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function admin_edit($id){
        $data = $this->manager_model->get_admin($id);
        if(!$data){
            $this->show_message('未找到管理员信息!');
        }
        $groups = $this->manager_model->get_group_all();
        $this->assign('data', $data);
        $this->assign('groups', $groups);
        $this->display('manager/admin/form.html');
    }

    /**
     * 保存管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function admin_save(){
        $res = $this->manager_model->admin_save();
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/admin_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    /**
     * 删除管理员
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function admin_del($id){
        echo $this->manager_model->admin_del($id);
    }

    /**
     * 新增用户组
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_add(){
        $group = array();
        $group['rules']=array(1,48,49,50,55);//默认选择 5个菜单
        $menu_all = $this->manager_model->get_menu_all();
        $menu_all = $this->getMenu($menu_all);
        $this->assign('rule', $menu_all);
        $this->assign('group', $group);
        $this->display('manager/group/form.html');
    }

    /**
     * 编辑用户组
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_edit($id){
        $group =  $this->manager_model->get_group_detail($id);
        if($group == -1){
            $this->show_message('未找到用户组信息!', site_url('/manager/group_list'));
        }
        $menu_all = $this->manager_model->get_menu_all();
        $menu_all = $this->getMenu($menu_all);
        $this->assign('rule', $menu_all);
        $this->assign('group', $group);
        $this->display('manager/group/form.html');
    }

    /**
     * 保存用户组
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_save(){
        $res = $this->manager_model->group_save();
        if($res == 1){
            $this->show_message('保存成功!',site_url('/manager/group_list'));
        }else{
            $this->show_message('保存失败!');
        }
    }

    /**
     * 用户组列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_list($page=1){
        $data = $this->manager_model->group_list($page);
        $base_url = "/manager/group_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/group/index.html');
    }

    /**
     * 删除用户组
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_del($id){
        echo $this->manager_model->group_del($id);
    }

    /**
     * 个人资料页面
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function personal_info(){
        $data = $this->manager_model->get_admin($this->admin_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        $this->assign('data', $data);
        $this->display('manager/personal/profile.html');
    }

    /**
     * 保存管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function personal_save(){
        $res = $this->manager_model->personal_save($this->admin_id);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/personal_info'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    /**
     * 退出
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-30
     */
    public function logout(){
        $this->session->sess_destroy();
        redirect(base_url('/manager_login/index'));
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
    public function users_list($page = 1){
        $data = $this->manager_model->users_list($page);
        $base_url = "/manager/users_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/users/index.html');
    }

    //会员改变所属管理员
    public function users_m_change() {
        $rs = $this->manager_model->users_m_change();
        $this->ajaxReturn($rs);
    }

    /**
     * 会员详情
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-07-22
     */
    public function users_edit($user_id){
        $data = $this->manager_model->users_edit($user_id);
        if(!$data){
            $this->show_message('未找到会员信息!');
        }
        $this->assign('data', $data);
        $this->display('manager/users/form.html');
    }

    /**
     * 保存会员
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-22
     */
    public function users_save(){
        $res = $this->manager_model->users_save();
        if($res['status'] == 1){
            $this->show_message('保存成功!', site_url('/manager/users_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    /**
     * 微信管理员列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-22
     */
    public function members_list($page = 1){
        $data = $this->manager_model->members_list($page);
        $base_url = "/manager/members_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/members/index.html');
    }

    /**
     * 微信管理员新增页面
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-23
     */
    public function members_add(){
        $data['m_level_2'] = $this->manager_model->members_work_add();
        $this->assign('data', $data);
        $this->display('manager/members/members_add.html');
    }

    public function members_edit($m_id){
        $data = $this->manager_model->members_edit($m_id);
        if(!$data){
            $this->show_message('未找到微信管理员信息!');
        }
        $data['m_level_2'] = $this->manager_model->members_work_add();
        $this->assign('data', $data);
        $this->display('manager/members/members_add.html');
    }

    /**
     * 微信管理员保存页面
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-23
     */
    public function members_save(){
        $res = $this->manager_model->members_save();
        $this->ajaxReturn($res);
    }

    //组员改变所属总监
    public function members_m_change() {
        $rs = $this->manager_model->members_m_change();
        $this->ajaxReturn($rs);
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
        $data = $this->manager_model->sms_list($page, 1);
        $base_url = "/manager/sms_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/log_list/sms_list.html');
    }

    /**
     * 同盾日志列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-23
     */
    public function tongdun_log_list($page = 1){
        $data = $this->manager_model->tongdun_log_list($page, 1);
        $base_url = "/manager/tongdun_log_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/log_list/tongdun_log_list.html');
    }

    /**
     * 同盾数据列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-23
     */
    public function tongdun_info_list($page = 1){
        $data = $this->manager_model->tongdun_info_list($page, 1);
        $base_url = "/manager/tongdun_info_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/log_list/tongdun_info_list.html');
    }

    public function tongdun_info_detail($id){
        $data = $this->manager_model->tongdun_info_detail($id);
        if(!$data){
            $this->show_message('未找到同盾信息!');
        }
        $this->assign('data', $data);
        $this->display('manager/log_list/tongdun_info_detail.html');
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
    public function foreclosure_list($page = 1){
        $data = $this->manager_model->foreclosure_list($page, 1);
        $base_url = "/manager/foreclosure_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/foreclosure/work_list.html');
    }

    /**
     * 赎楼申请详情
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-22
     */
    public function foreclosure_detail($id){
        $work = $this->manager_model->foreclosure_detail($id);
        if(!$work){
            $this->show_message('未找到赎楼申请信息!');
        }
        $this->assign('work', $work['work']);
        $this->assign('property_img',$work['property_img']);
        $this->assign('credit_img',$work['credit_img']);
        $this->display('manager/foreclosure/work_detail.html');
    }

    //赎楼业务所属微管修改
    public function foreclosure_m_change() {
        $rs = $this->manager_model->foreclosure_m_change();
        $this->ajaxReturn($rs);
    }

    /**
     *********************************************************************************************
     * 以下代码为测试模块
     *********************************************************************************************
     */

    public function test_html(){
        $this->display('manager/test/test.html');
    }
}
