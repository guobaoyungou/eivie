<?php

namespace app\controller;

use app\BaseController;
use think\facade\View;

class ApiConfigTest extends BaseController
{
    public function index()
    {
        return 'ApiConfig Test - OK';
    }
    
    public function test2()
    {
        View::assign('test', 'Hello World');
        return View::fetch();
    }
}
