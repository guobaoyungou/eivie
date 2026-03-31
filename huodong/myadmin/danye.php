<?php

require_once('Page.php');

class Danye extends Page {

    function show() {
        $danye = new M('danye');
        $danyedata = $danye->select();

        $this->assign('danye', $danyedata);

        $this->display('templates/danye.html');
    }

}

$page = new Danye();
$page->setTitle('单页管理');
$page->show();
