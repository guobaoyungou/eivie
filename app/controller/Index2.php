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
// | 首页
// +----------------------------------------------------------------------
namespace app\controller;
use app\BaseController;
use think\facade\View;
use think\facade\Db;

class Index2 extends BaseController
{
	public $webinfo;
	public function initialize(){
		if(MN == 'notify'){

		}else{
			$this->webinfo = Db::name('sysset')->where(['name'=>'webinfo'])->value('value');
			$this->webinfo = json_decode($this->webinfo,true);
			if($this->webinfo['showweb']!=1 && request()->action() != 'downloadapp'){
				header('Location:'.(string)url('Backstage/index'));die;
			}
			View::assign('webinfo',$this->webinfo);
			//开启注册
			$reg_open = isset($this->webinfo['reg_open']) ? $this->webinfo['reg_open'] : 0;
			View::assign('reg_open',$reg_open);
		}
	}
	//首页框架
    public function index(){
		if(MN == 'notify'){
			$notify = new \app\common\Notify();
			$notify->index();
		}else{
			if($this->isMobile()){
				return View::fetch('index/wap/index');
			}
			return View::fetch();
		}
    }
	//首页框架2
    public function index2(){
		if(MN == 'notify'){
			$notify = new \app\common\Notify();
			$notify->index();
		}else{
			if($this->isMobile()){
				return View::fetch('index/wap/index');
			}
			return View::fetch();
		}
    }
	public function lianxi(){
		if(request()->isPost()){
			$realname = input('post.realname');
			$tel = input('post.tel');
			$content = input('post.content');
			$ip = request()->ip();
			db('webmessage')->insert(['realname'=>$realname,'tel'=>$tel,'content'=>$content,'ip'=>$ip,'createtime'=>time()]);
			return json(['status'=>1,'msg'=>'提交成功']);
		}
		if($this->isMobile()){
			return View::fetch('index/wap/lianxi');
		}
		return View::fetch();
	}
	//是否是移动端
	function isMobile(){
		if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
			return true;
		}
		if (isset ($_SERVER['HTTP_USER_AGENT'])){
			$clientkeywords = array ('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile');
			if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))){
				return true;
			}
		}
		if (isset ($_SERVER['HTTP_ACCEPT'])){
			if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))){
				return true;
			}
		}
		if (isset ($_SERVER['HTTP_VIA'])){
			return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
		}
		return false;
	}
	public function news(){
		$cid = $_GET['id'] ? $_GET['id'] : 1;
		$clist = db('help_category')->where(array('status'=>1))->order('sort desc,id')->select();
		$list = db('help')->where(array('cid'=>$cid,'status'=>1))->order('sort desc,sendtime desc')->limit(10)->select();
		View::assign('clist',$clist);
		View::assign('list',$list);
		return View::fetch();
	}
	public function newsdetail(){
		$id = intval($_GET['id']);
		$info = db('help')->where(array('id'=>$id))->find();
		db('help')->where(array('id'=>$id))->inc('readcount')->update();
		View::assign('info',$info);
		return View::fetch();
	}
	public function help(){
		$where = [];
		$where[] = ['status','=',1];
		if(input('param.keyword')){
			$where[] = ['name','like','%'.input('param.keyword').'%'];
		}
		$list = db('help')->where($where)->order('sort desc')->paginate(['list_rows'=>20,'query'=>['s'=>'/index/help']]);
		// 获取分页显示
		$page = $list->render();
		// 模板变量赋值
		View::assign('list', $list);
		View::assign('page', $page);

		return View::fetch();
	}
	public function helpdetail(){
		$id = input('param.id/d');
		$info = db('help')->where(array('id'=>$id))->find();
		Db::name('help')->where(array('id'=>$id))->inc('readcount')->update();
		View::assign('info',$info);
		return View::fetch();
	}
	public function funshow(){
		return View::fetch();
	}

	//下载app
	public function downloadapp(){
		$aid = input('param.aid/d');
		if(!$aid) $aid = '1';
		$set = Db::name('admin_set')->where('aid',$aid)->find();
	    $systemtype = '';
		$androidurl = '';
		$iosurl = '';

	    $androidurl = PRE_URL.'/'.$aid.'.apk';
	    //$iosurl = PRE_URL.'/'.$aid.'.ipa';
	    
	    if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){ 
	        $systemtype = 'ios';
			//$androidurl = '';
	    }else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){ 
	         $systemtype = 'Android';
			 //$iosurl = '';
	    }
	    $isweixin = is_weixin();
	    
	    View::assign('systemtype',$systemtype);
	    View::assign('isweixin',$isweixin);
	    View::assign('iosurl',$iosurl);
	    View::assign('androidurl',$androidurl);
	    View::assign('set',$set);
	    return View::fetch();
	}
}
