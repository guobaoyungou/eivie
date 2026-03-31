<?php

require_once('Page.php');

class Hdxc extends Page {

    function show() {
        $hdxc = new M('hdxc');
        $hdxc_config = $hdxc->find('1');
        $hdxc_config['image'] = $hdxc_config['img'];
        $this->assign('hdxc_config',$hdxc_config);
        $this->display('templates/hdxc.html');
    }

}

$page = new Hdxc();
$page->setTitle('活动行程');
$page->show();
