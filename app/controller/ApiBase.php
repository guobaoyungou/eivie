<?php
/**
 * 点大商城（www.diandashop.com） - 微信公众号小程序商城系统!
 * Copyright © 2020 山东点大网络科技有限公司 保留所有权利
 * =========================================================
 * 版本：V2
 * 授权主体：shop.guobaoyungou.cn
 * 授权域名：guobaoyungou.cn
 * 授权码：TZJcxBSGGdtDBIxFerKVJo
 * ----------------------------------------------
 * 您只能在商业授权范围内使用，不可二次转售、分发、分享、传播
 * 任何企业和个人不得对代码以任何目的任何形式的再发布
 * =========================================================
 */

// +----------------------------------------------------------------------
// | 公共接口
// +----------------------------------------------------------------------
namespace app\controller;
use app\BaseController;
use think\facade\Db;
class ApiBase extends BaseController
{
	public $aid;
	public $mid;
	public $member;
	public $indexurl;
	public $sysset;
    public $admin;
    public function initialize(){

		$request = request();
		if(!in_array($request->controller(),['ApiAdminProduct','ApiAdminRestaurantProduct'])){
			\think\facade\Request::filter(['strip_tags','htmlspecialchars']);
		}
		
		//die(json_encode(['status'=>0,'msg'=>'test']));
        $aid = input('param.aid/d');
		if(!$aid) die(jsonEncode(['status'=>0,'msg'=>'参数错误']));
		$admin = Db::name('admin')->where('id',$aid)->find();
		if(!$admin) die(jsonEncode(['status'=>0,'msg'=>'参数错误']));
		if($admin['status'] == 0 ) die(jsonEncode(['status'=>0,'msg'=>'账号未启用']));//控制台-用户列表 编辑
		if($admin['endtime'] < time()) die(jsonEncode(['status'=>0,'msg'=>'账号过期']));
        $this->admin = $admin;

		$platform = input('param.platform');
		if($platform && !in_array($platform,['mp','wx','alipay','baidu','toutiao','qq','h5','app'])) die(jsonEncode(['status'=>0,'msg'=>'参数错误']));
		if($platform){
			define('platform',$platform);
		}else{
			if(!is_weixin()){
				define('platform','h5');
			}else{
				define('platform','mp');
			}
		}
		if(input('param.isdouyin') == 1){
			$douyinset = Db::name('douyin_sysset')->where('aid',$aid)->find();
			if($douyinset['status'] == 1){
				define('isdouyin',1);
			}else{
				define('isdouyin',0);
			}
		}else{
			define('isdouyin',0);
		}
		$this->aid = $aid;
		define('aid',$aid);
    }
}