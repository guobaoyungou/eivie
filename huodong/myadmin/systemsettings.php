<?php

require_once('Page.php');

class SystemSettings extends Page{
	function show(){
		$this->_load->model('Weixin_model');
		$weixin_config=$this->_load->weixin_model->getConfig();
		$this->_load->model("System_Config_model");
		$menucolor=$this->_load->system_config_model->get('menucolor');
		$showcountsign=$this->_load->system_config_model->get('showcountsign');
		$show_company_name=$this->_load->system_config_model->get('show_company_name');
		$show_activity_name=$this->_load->system_config_model->get('show_activity_name');
		$show_copyright=$this->_load->system_config_model->get('show_copyright');
		$this->assign('menucolor',$menucolor['configvalue']);
		$this->assign('showcountsign',$showcountsign['configvalue']);
		$this->assign('show_company_name',empty($show_company_name['configvalue'])?'1':$show_company_name['configvalue']);
		$this->assign('show_activity_name',empty($show_activity_name['configvalue'])?'1':$show_activity_name['configvalue']);
		$this->assign('show_copyright',empty($show_copyright['configvalue'])?'1':$show_copyright['configvalue']);
		$this->assign('weixin_config',$weixin_config);
		$this->display('templates/systemsettings.html');
	}
}
$page=new SystemSettings();
$page->setTitle('系统设置');
$page->show();
