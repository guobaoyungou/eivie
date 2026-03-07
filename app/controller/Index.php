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
use app\service\ModelSquareService;

class Index extends BaseController
{
	public $webinfo;
	public function initialize(){
		if(MN == 'notify' || MN == 'notify2' || MN == 'notify3' || MN == 'linghuoxinpay' || MN == 'linghuoxinsign' || MN == 'payreturn'){

		}else{
			$this->webinfo = Db::name('sysset')->where(['name'=>'webinfo'])->value('value');
			$this->webinfo = json_decode($this->webinfo,true);
			if(!$this->webinfo['showweb'] && request()->action() != 'downloadapp'){
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
		}elseif(MN == 'notify_global'){
           $notify = new \app\common\NotifyAbroad();
           $notify->index();
		}elseif(MN == 'notify2'){
			$notify = new \app\common\Notify2();
			$notify->index();
		}elseif(MN == 'notify3'){
           \app\custom\Chain::notify();
        }if(MN == 'notify_v3_transfer'){
            $notify = new \app\common\NotifyV3();
            $notify->transfer();
        }elseif(MN == 'linghuoxinpay' || MN == 'linghuoxinsign'){
          }elseif(MN == 'payreturn'){
            $notify = new \app\common\PayReturn();
            $notify->index();
        }else{
			// 模板三：AI创作平台（响应式单页，不区分PC/移动端）
			if($this->webinfo['showweb']==3){
				return $this->index3();
			}
			if($this->isMobile()){
				return View::fetch('index/wap/index');
			}
            if($this->webinfo['showweb']==2 && request()->action() != 'downloadapp'){
				return View::fetch('index2/index');
			}
			return View::fetch();
		}
    }
	
	public function lianxi(){

		\think\facade\Request::filter(['strip_tags','htmlspecialchars']);

		if(request()->isPost()){
			$realname = input('post.realname');
			$tel = input('post.tel');
			$content = input('post.content');
            $captcha = trim(input('post.captcha'));
            if($captcha == ''){
                return json(['status'=>0,'msg'=>'验证码不能为空']);
            }elseif(!captcha_check($captcha)){
                return json(['status'=>0,'msg'=>'验证码错误']);
            }
			$ip = request()->ip();
			db('webmessage')->insert(['realname'=>$realname,'tel'=>$tel,'content'=>$content,'ip'=>$ip,'createtime'=>time()]);
			return json(['status'=>1,'msg'=>'提交成功']);
		}
		if($this->webinfo['showweb']==3){
			return View::fetch('index3/lianxi');
		}
		if($this->isMobile()){
			return View::fetch('index/wap/lianxi');
		}
		if($this->webinfo['showweb']==2 && request()->action() != 'downloadapp'){
			return View::fetch('index2/lianxi');
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
		$where = [];
		$where[] = ['cid','=',$cid];
		$where[] = ['status','=',1];
		$list = db('help')->where($where)->order('sort desc,sendtime desc')->limit(10)->select();
		View::assign('clist',$clist);
		View::assign('list',$list);
		return View::fetch();
	}
	public function newsdetail(){
		$id = intval($_GET['id']);
		$where = [];
		$where[] = ['id','=',$id];
		$where[] = ['status','=',1];
		$info = db('help')->where($where)->find();
		db('help')->where($where)->inc('readcount')->update();
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

		if($this->webinfo['showweb']==3){
			return View::fetch('index3/help');
		}
		if($this->webinfo['showweb']==2 && request()->action() != 'downloadapp'){
			return View::fetch('index2/help');
		}
		return View::fetch();
	}
	public function helpdetail(){
		$id = input('param.id/d');
		$where = [];
		$where[] = ['id','=',$id];
		$where[] = ['status','=',1];
		$info = db('help')->where($where)->find();
		Db::name('help')->where($where)->inc('readcount')->update();
		View::assign('info',$info);
		if($this->webinfo['showweb']==3){
			return View::fetch('index3/helpdetail');
		}
		if($this->webinfo['showweb']==2 && request()->action() != 'downloadapp'){
			return View::fetch('index2/helpdetail');
		}
		return View::fetch();
	}
	public function funshow(){
		if($this->webinfo['showweb']==3){
			return $this->index3();
		}
		if($this->webinfo['showweb']==2 && request()->action() != 'downloadapp'){
			return View::fetch('index2/funshow');
		}
		return View::fetch('index');
	}

	//下载app
	public function downloadapp(){
		$aid = input('param.aid/d');
		if(!$aid) $aid = '1';
		$set = Db::name('admin_set')->where('aid',$aid)->find();
		$appinfo = Db::name('admin_setapp_app')->where('aid',$aid)->find();
	    $systemtype = '';
		$androidurl = '';
		$iosurl = '';
		if($appinfo['androidurl']){
			$androidurl = $appinfo['androidurl'];
		}elseif($set['androidurl']){
			$androidurl = $set['androidurl'];
		}else{
			$androidurl = PRE_URL.'/'.$aid.'.apk';
		}
		if($appinfo['iosurl']){
			$iosurl = $appinfo['iosurl'];
		}elseif($set['iosurl']){
			$iosurl = $set['iosurl'];
		}
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
    public function newslist(){
	    }
    public function newscontent(){
        }

	// =================================================================
	// 模板三：AI创作平台 — 首页渲染 & AJAX接口
	// =================================================================

	/**
	 * 模板三首页渲染（响应式单页）
	 */
	private function index3(){
		$service = new ModelSquareService();

		// 供应商列表（启用状态，用于Tab渲染）
		$provider_list = $service->getActiveProviderList();
		View::assign('provider_list', $provider_list);

		// 推荐模型列表（首屏热门模型Tab数据：仅加载 is_recommend=1 的推荐模型）
		$recommend_models = $service->getRecommendModels();
		// 解析 capability_tags
		foreach ($recommend_models as &$rm) {
			if (isset($rm['capability_tags']) && is_string($rm['capability_tags'])) {
				$rm['capability_tags'] = json_decode($rm['capability_tags'], true) ?: [];
			}
			if (!is_array($rm['capability_tags'])) {
				$rm['capability_tags'] = [];
			}
		}
		unset($rm);
		View::assign('recommend_models', $recommend_models);

		// 图片场景分类
		$photo_categories = Db::name('generation_scene_category')
			->field('id, name')
			->where('generation_type', 1)
			->where('status', 1)
			->order('sort desc, id')
			->select()
			->toArray();
		View::assign('photo_categories', $photo_categories);

		// 视频场景分类
		$video_categories = Db::name('generation_scene_category')
			->field('id, name')
			->where('generation_type', 2)
			->where('status', 1)
			->order('sort desc, id')
			->select()
			->toArray();
		View::assign('video_categories', $video_categories);

		// 图片场景模板（首屏12条）
		$photo_scenes = Db::name('generation_scene_template')
			->field('id, template_name, cover_image, base_price, use_count, description')
			->where('generation_type', 1)
			->where('status', 1)
			->order('sort desc, id desc')
			->limit(12)
			->select()
			->toArray();
		View::assign('photo_scenes', $photo_scenes);

		// 视频场景模板（首屏12条）
		$video_scenes = Db::name('generation_scene_template')
			->field('id, template_name, cover_image, base_price, use_count, description')
			->where('generation_type', 2)
			->where('status', 1)
			->order('sort desc, id desc')
			->limit(12)
			->select()
			->toArray();
		View::assign('video_scenes', $video_scenes);

		return View::fetch('index3/index');
	}

	/**
	 * AJAX: 场景模板列表（分类筛选 + 分页）
	 */
	public function scene_list(){
		if(!request()->isAjax()){
			return json(['code'=>-1,'msg'=>'非法请求']);
		}
		$generation_type = input('param.generation_type/d', 1);
		$category_id = input('param.category_id/d', 0);
		$page = input('param.page/d', 1);
		$limit = input('param.limit/d', 12);
		if($limit > 50) $limit = 50;
		if($page < 1) $page = 1;

		$where = [];
		$where[] = ['generation_type', '=', $generation_type];
		$where[] = ['status', '=', 1];
		if($category_id > 0){
			$where[] = ['category_ids', 'like', '%' . $category_id . '%'];
		}

		$list = Db::name('generation_scene_template')
			->field('id, template_name, cover_image, base_price, use_count, description')
			->where($where)
			->order('sort desc, id desc')
			->page($page, $limit)
			->select()
			->toArray();

		return json(['code'=>0, 'msg'=>'success', 'data'=>$list]);
	}

	/**
	 * AJAX: 搜索模型/模板
	 */
	public function search(){
		if(!request()->isAjax()){
			return json(['code'=>-1,'msg'=>'非法请求']);
		}
		$keyword = input('param.keyword', '');
		$type = input('param.type/d', 1);
		if(empty($keyword)){
			return json(['code'=>0, 'msg'=>'success', 'data'=>[]]);
		}

		$keyword = '%' . $keyword . '%';

		// 搜索场景模板
		$list = Db::name('generation_scene_template')
			->field('id, template_name, cover_image, base_price, use_count, description')
			->where('generation_type', $type)
			->where('status', 1)
			->where('template_name', 'like', $keyword)
			->order('sort desc, id desc')
			->limit(20)
			->select()
			->toArray();

		return json(['code'=>0, 'msg'=>'success', 'data'=>$list]);
	}

	/**
	 * AJAX: 模型广场列表（分页）
	 */
	public function model_list(){
		if(!request()->isAjax()){
			return json(['code'=>-1,'msg'=>'非法请求']);
		}
		$page = input('param.page/d', 1);
		$limit = input('param.limit/d', 20);
		$is_recommend = input('param.is_recommend/d', 0);
		if($limit > 50) $limit = 50;
		if($page < 1) $page = 1;

		$where = [];
		$where[] = ['m.is_active', '=', 1];
		if($is_recommend == 1){
			$where[] = ['m.is_recommend', '=', 1];
		}

		$list = Db::name('model_info')
			->alias('m')
			->leftJoin('model_provider p', 'm.provider_id = p.id')
			->leftJoin('model_type t', 'm.type_id = t.id')
			->field('m.id, m.model_name, m.description, m.is_recommend, m.capability_tags, p.provider_name, p.logo as provider_logo, t.type_name')
			->where($where)
			->order('m.is_recommend desc, m.sort asc, m.id desc')
			->page($page, $limit)
			->select()
			->toArray();

		// 解析 capability_tags
		foreach($list as &$item){
			if(isset($item['capability_tags']) && is_string($item['capability_tags'])){
				$item['capability_tags'] = json_decode($item['capability_tags'], true) ?: [];
			}
			if(!is_array($item['capability_tags'])) $item['capability_tags'] = [];
		}
		unset($item);

		return json(['code'=>0, 'msg'=>'success', 'data'=>$list]);
	}

	/**
	 * AJAX: 按供应商获取模型列表（供应商Tab懒加载）
	 */
	public function model_list_by_provider(){
		if(!request()->isAjax()){
			return json(['code'=>-1,'msg'=>'非法请求']);
		}
		$provider_id = input('param.provider_id/d', 0);
		$page = input('param.page/d', 1);
		$limit = input('param.limit/d', 20);
		if($limit > 50) $limit = 50;
		if($page < 1) $page = 1;
		if($provider_id <= 0){
			return json(['code'=>-1,'msg'=>'参数错误']);
		}

		$service = new ModelSquareService();
		$list = $service->getModelsByProvider($provider_id, $page, $limit);

		// 解析 capability_tags
		foreach($list as &$item){
			if(isset($item['capability_tags']) && is_string($item['capability_tags'])){
				$item['capability_tags'] = json_decode($item['capability_tags'], true) ?: [];
			}
			if(!is_array($item['capability_tags'])) $item['capability_tags'] = [];
		}
		unset($item);

		return json(['code'=>0, 'msg'=>'success', 'data'=>$list]);
	}

	/**
	 * AJAX: 获取模型详情（生成任务弹窗用）
	 */
	public function model_detail(){
		if(!request()->isAjax()){
			return json(['code'=>-1,'msg'=>'非法请求']);
		}
		$id = input('param.id/d', 0);
		if($id <= 0){
			return json(['code'=>-1,'msg'=>'参数错误']);
		}

		$service = new ModelSquareService();
		$info = $service->getModelFrontDetail($id);
		if(!$info){
			return json(['code'=>-1,'msg'=>'模型不存在或已禁用']);
		}

		// 附加该模型关联的推荐场景模板（弹窗第四排用）
		$info['scene_templates'] = $service->getModelSceneTemplates($id, 8);

		return json(['code'=>0, 'msg'=>'success', 'data'=>$info]);
	}

	/**
	 * 照片生成页
	 */
	public function photo_generation(){
		if($this->webinfo['showweb']!=3){
			header('Location:'.(string)url('Index/index'));
			die;
		}

		// 获取推荐的照片模板（显示在右侧）
		$recommend_templates = Db::name('generation_scene_template')
			->field('id, template_name, cover_image, base_price, use_count')
			->where('generation_type', 1)
			->where('status', 1)
			->order('use_count desc, sort desc, id desc')
			->limit(10)
			->select()
			->toArray();
		View::assign('recommend_templates', $recommend_templates);

		return View::fetch('index3/photo_generation');
	}

	/**
	 * 视频生成页
	 */
	public function video_generation(){
		if($this->webinfo['showweb']!=3){
			header('Location:'.(string)url('Index/index'));
			die;
		}

		// 获取推荐的视频模板（显示在右侧）
		$recommend_templates = Db::name('generation_scene_template')
			->field('id, template_name, cover_image, base_price, use_count')
			->where('generation_type', 2)
			->where('status', 1)
			->order('use_count desc, sort desc, id desc')
			->limit(10)
			->select()
			->toArray();
		View::assign('recommend_templates', $recommend_templates);

		return View::fetch('index3/video_generation');
	}
}
