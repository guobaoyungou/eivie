<?php
require_once('Page.php');
class Intergrate extends Page{
	function show(){

		$this->_load->model('Weixin_model');
		$weixin_config=$this->_load->weixin_model->getConfig();
		$this->assign('weixin_config',$weixin_config);
		$this->assign('domain',$_SERVER['HTTP_HOST']);
		$this->display('templates/intergrate.html');
	}
}
$page=new Intergrate();
$page->setTitle('对接设置');
$page->show();
