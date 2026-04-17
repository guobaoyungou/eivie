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

	/**
	 * 控制器中间件
	 * 存储空间预检中间件仅在上传和创建生成订单时触发
	 */
	protected $middleware = [
		'StorageQuotaCheck' => ['only' => ['upload_image', 'create_generation_order']],
	];

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
        }elseif(MN == 'notify_v3_transfer'){
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
			->field('id, template_name, cover_image, gif_cover, generation_type, base_price, use_count, description')
			->where('generation_type', 1)
			->where('status', 1)
			->order('sort desc, id desc')
			->limit(12)
			->select()
			->toArray();
		$this->_convertScenePriceToScore($photo_scenes);
		View::assign('photo_scenes', $photo_scenes);

		// 视频场景模板（首屏12条）
		$video_scenes = Db::name('generation_scene_template')
			->field('id, template_name, cover_image, gif_cover, generation_type, base_price, use_count, description')
			->where('generation_type', 2)
			->where('status', 1)
			->order('sort desc, id desc')
			->limit(12)
			->select()
			->toArray();
		$this->_convertScenePriceToScore($video_scenes);
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
			->field('id, template_name, cover_image, gif_cover, generation_type, base_price, use_count, description')
			->where($where)
			->order('sort desc, id desc')
			->page($page, $limit)
			->select()
			->toArray();
		$this->_convertScenePriceToScore($list);

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
			->field('id, template_name, cover_image, gif_cover, generation_type, base_price, use_count, description')
			->where('generation_type', $type)
			->where('status', 1)
			->where('template_name', 'like', $keyword)
			->order('sort desc, id desc')
			->limit(20)
			->select()
			->toArray();
		$this->_convertScenePriceToScore($list);

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
		$sceneTemplates = $service->getModelSceneTemplates($id, 8);
		$this->_convertScenePriceToScore($sceneTemplates);
		$info['scene_templates'] = $sceneTemplates;

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

	/**
	 * 短剧项目列表页
	 */
	public function short_drama(){
		if($this->webinfo['showweb']!=3){
			header('Location:'.(string)url('Index/index'));
			die;
		}

		return View::fetch('index3/short_drama');
	}

	/**
	 * 短剧创作画布页
	 */
	public function short_drama_canvas(){
		if($this->webinfo['showweb']!=3){
			header('Location:'.(string)url('Index/index'));
			die;
		}

		$projectId = input('param.project_id/d', 0);
		$project = [];
		if($projectId > 0){
			$project = Db::name('workflow_project')
				->where('id', $projectId)
				->find();
			if(!$project) $project = [];
		}
		View::assign('project', $project);

		return View::fetch('index3/short_drama_canvas');
	}

	/**
	 * AJAX: 场景模板详情（PC官网弹窗用，无需aid鉴权）
	 */
	public function scene_template_detail(){
		if(!request()->isAjax()){
			return json(['status'=>0,'msg'=>'非法请求']);
		}
		$templateId = input('param.template_id/d', 0);
		if(!$templateId){
			return json(['status'=>0,'msg'=>'缺少模板ID']);
		}

		$template = Db::name('generation_scene_template')
			->where('id', $templateId)
			->where('status', 1)
			->find();
		if(!$template){
			return json(['status'=>0,'msg'=>'模板不存在或已下架']);
		}

		// 价格计算（游客身份，memberLevelId=0）
		$service = new \app\service\GenerationService();
		$priceInfo = $service->calculateTemplatePrice($template, 0);

		// 获取积分兑换配置，用于价格转积分
		$cmService = new \app\service\CreativeMemberService();
		$scoreConfig = $cmService->getScorePayConfig(1);
		$exchangeRate = $scoreConfig['exchange_rate'];

		// 所有等级价格
		$allPrices = [];
		if($template['lvprice'] == 1){
			$lvpriceData = is_string($template['lvprice_data'])
				? json_decode($template['lvprice_data'], true)
				: ($template['lvprice_data'] ?: []);
			if(!empty($lvpriceData)){
				$levelIds = array_keys($lvpriceData);
				$levels = Db::name('member_level')->where('id','in',$levelIds)->column('name','id');
				foreach($lvpriceData as $lid=>$lprice){
					$moneyPrice = floatval($lprice);
					$allPrices[] = ['level_id'=>$lid,'level_name'=>$levels[$lid]??'未知等级','price'=>($moneyPrice > 0) ? $cmService->moneyToScore($moneyPrice, $exchangeRate) : 0];
				}
			}
		}

		// 解析默认参数
		$defaultParams = is_string($template['default_params'])
			? json_decode($template['default_params'], true)
			: ($template['default_params'] ?: []);

		// 参考图
		$refImage = '';
		if(!empty($defaultParams['image'])){
			$refImage = $defaultParams['image'];
		} elseif(!empty($defaultParams['first_frame_image'])){
			$refImage = $defaultParams['first_frame_image'];
		}

		// 模型能力
		$modelCapability = ['max_images'=>1,'supported_ratios'=>['1:1'],'supported_sizes'=>[]];
		if(!empty($template['model_id'])){
			$modelInfo = Db::name('model_info')
				->where('id',$template['model_id'])
				->field('id,model_code,model_name,input_schema')
				->find();
			if($modelInfo){
				$inputSchema = is_string($modelInfo['input_schema'])
					? json_decode($modelInfo['input_schema'], true)
					: ($modelInfo['input_schema'] ?: []);
				$props = $inputSchema['properties'] ?? $inputSchema;
				if(isset($props['n'])){
					$maxN = intval($props['n']['maximum'] ?? $props['n']['max'] ?? 9);
					$modelCapability['max_images'] = $maxN > 0 ? $maxN : 9;
				}
				$sizeEnum = [];
				if(isset($props['size'])){
					$sizeEnum = $props['size']['enum'] ?? $props['size']['options'] ?? [];
					$modelCapability['supported_sizes'] = $sizeEnum;
				}
				if(!empty($sizeEnum)){
					$parsedRatios = $this->_parseSupportedRatios($sizeEnum);
					if(!empty($parsedRatios)){
						$modelCapability['supported_ratios'] = $parsedRatios;
					}
				}
				$modelCapability['model_name'] = $modelInfo['model_name'] ?? '';
				$modelCapability['model_code'] = $modelInfo['model_code'] ?? '';
			}
		}
		if(count($modelCapability['supported_ratios']) <= 1){
			$modelCapability['supported_ratios'] = ['1:1','2:3','3:2','3:4','4:3','9:16','16:9','4:5','5:4','21:9'];
		}

		// 示例图
		$sampleImages = [];
		if(!empty($template['source_record_id'])){
			$outputs = Db::name('generation_output')
				->where('record_id',$template['source_record_id'])
				->field('output_url,thumbnail_url,output_type')
				->limit(4)->select()->toArray();
			foreach($outputs as $out){
				$sampleImages[] = $out['thumbnail_url'] ?: $out['output_url'];
			}
		}

		// 证件照类型名称
		$idPhotoTypeMap = [0=>'',1=>'身份证照',2=>'护照/港澳通行证',3=>'驾驶证',4=>'一寸照',5=>'二寸照'];
		$isIdPhoto = intval($template['is_id_photo'] ?? 0);
		$idPhotoType = intval($template['id_photo_type'] ?? 0);
		$idPhotoTypeName = ($isIdPhoto == 1) ? ($idPhotoTypeMap[$idPhotoType] ?? '') : '';

		$result = [
			'id' => $template['id'],
			'template_name' => $template['template_name'],
			'cover_image' => $template['cover_image'],
			'gif_cover' => $template['gif_cover'] ?? '',
			'ref_image' => $refImage,
			'description' => $template['description'],
			'prompt' => $defaultParams['prompt'] ?? '',
			'generation_type' => intval($template['generation_type'] ?? 1),
			'price' => (floatval($priceInfo['price']) > 0) ? $cmService->moneyToScore(floatval($priceInfo['price']), $exchangeRate) : 0,
			'base_price' => (floatval($priceInfo['base_price']) > 0) ? $cmService->moneyToScore(floatval($priceInfo['base_price']), $exchangeRate) : 0,
			'price_unit' => '积分',
			'price_unit_text' => '按积分计费',
			'is_member_price' => $priceInfo['is_member_price'],
			'use_count' => intval($template['use_count'] ?? 0),
			'output_quantity' => intval($template['output_quantity'] ?? 1),
			'prompt_visible' => intval($template['prompt_visible'] ?? 1),
			'is_id_photo' => $isIdPhoto,
			'id_photo_type' => $idPhotoType,
			'id_photo_type_name' => $idPhotoTypeName,
			'all_prices' => $allPrices,
			'sample_images' => $sampleImages,
			'model_capability' => $modelCapability,
			'default_params' => $defaultParams
		];

		return json(['status'=>1,'msg'=>'获取成功','data'=>$result]);
	}

	/**
	 * AJAX: 创建生成订单（PC官网弹窗用，无需aid鉴权）
	 */
	public function create_generation_order(){
		if(!request()->isAjax() || !request()->isPost()){
			return json(['status'=>0,'msg'=>'非法请求']);
		}

		$templateId = input('post.template_id/d', 0);
		$modelId = input('post.model_id/d', 0);
		$generationType = input('post.generation_type/d', 1);
		$prompt = input('post.prompt', '');
		$refImages = input('post.ref_images/a', []);
		$quantity = input('post.quantity/d', 0);
		$ratio = input('post.ratio', '');
		$quality = input('post.quality', '');

		if(!$templateId && !$modelId){
			return json(['status'=>0,'msg'=>'请选择场景模板或模型']);
		}
		$prompt = trim($prompt);
		if(mb_strlen($prompt) < 2){
			return json(['status'=>0,'msg'=>'请填写提示词（至少2个字符）']);
		}
		if(mb_strlen($prompt) > 2000){
			return json(['status'=>0,'msg'=>'提示词不能超过2000个字符']);
		}

		// 从session获取会员身份（PC网站使用cookie session）
		$sessionId = \think\facade\Session::getId();
		$mid = 0;
		$aid = 1; // 默认aid
		$memberLevelId = 0;

		if($sessionId){
			$mid = cache($sessionId . '_mid');
			if($mid){
				$member = Db::name('member')->where('id', $mid)->field('id,aid,levelid')->find();
				if($member){
					$aid = $member['aid'];
					$memberLevelId = intval($member['levelid']);
				} else {
					$mid = 0;
				}
			}
		}

		if(!$mid){
			return json(['status'=>0,'msg'=>'请先登录后再生成']);
		}

		$orderService = new \app\service\GenerationOrderService();
		
		// 模型直选分支：template_id=0 && model_id>0
		if(!$templateId && $modelId > 0){
			$result = $orderService->createOrderByModel([
				'aid' => $aid,
				'bid' => 0,
				'mid' => $mid,
				'model_id' => $modelId,
				'generation_type' => $generationType,
				'user_prompt' => $prompt,
				'ref_images' => $refImages,
				'quantity' => $quantity,
				'ratio' => $ratio,
				'quality' => $quality
			]);
			return json($result);
		}
		
		// 模板驱动分支：template_id>0
		$result = $orderService->createOrderWithParams([
			'aid' => $aid,
			'bid' => 0,
			'mid' => $mid,
			'scene_id' => $templateId,
			'generation_type' => $generationType,
			'member_level_id' => $memberLevelId,
			'user_prompt' => $prompt,
			'ref_images' => $refImages,
			'quantity' => $quantity,
			'ratio' => $ratio,
			'quality' => $quality
		]);

		return json($result);
	}

	/**
	 * 辅助：从size枚举解析比例列表
	 */
	private function _parseSupportedRatios($sizeEnum){
		$map = [
			'512x512'=>'1:1','1024x1024'=>'1:1','2048x2048'=>'1:1',
			'512x768'=>'2:3','1024x1536'=>'2:3','2048x3072'=>'2:3',
			'768x512'=>'3:2','1536x1024'=>'3:2','3072x2048'=>'3:2',
			'384x512'=>'3:4','768x1024'=>'3:4','1536x2048'=>'3:4',
			'512x384'=>'4:3','1024x768'=>'4:3','2048x1536'=>'4:3',
			'360x640'=>'9:16','720x1280'=>'9:16','1440x2560'=>'9:16',
			'640x360'=>'16:9','1280x720'=>'16:9','2560x1440'=>'16:9',
			'512x640'=>'4:5','1024x1280'=>'4:5','2048x2560'=>'4:5',
			'640x512'=>'5:4','1280x1024'=>'5:4','2560x2048'=>'5:4',
			'1260x540'=>'21:9','2520x1080'=>'21:9','3780x1620'=>'21:9',
		];
		$ratios = [];
		foreach($sizeEnum as $size){
			$size = str_replace('*','x',strtolower(trim($size)));
			if(isset($map[$size]) && !in_array($map[$size],$ratios)){
				$ratios[] = $map[$size];
			}
		}
		return $ratios;
	}

	/**
	 * 将场景模板列表中的 base_price（人民币）转换为积分值
	 * 根据系统设置的 ai_score_exchange_rate 兑换比例计算
	 * @param array &$list 场景模板数组（引用传递）
	 */
	private function _convertScenePriceToScore(&$list){
		if(empty($list)) return;
		$cmService = new \app\service\CreativeMemberService();
		$scoreConfig = $cmService->getScorePayConfig(1);
		$exchangeRate = $scoreConfig['exchange_rate'];
		foreach($list as &$item){
			if(isset($item['base_price'])){
				$money = floatval($item['base_price']);
				$item['base_price'] = ($money > 0) ? $cmService->moneyToScore($money, $exchangeRate) : 0;
			}
		}
		unset($item);
	}

	/**
	 * AJAX: 检查登录状态（PC官网用）
	 */
	public function check_login(){
		if(!request()->isAjax()){
			return json(['status'=>0,'msg'=>'非法请求']);
		}
		$sessionId = \think\facade\Session::getId();
		$mid = 0;
		if($sessionId){
			$mid = cache($sessionId . '_mid');
		}
		if($mid){
			$member = Db::name('member')->where('id', $mid)->field('id,nickname,headimg,money,score,levelid,tel')->find();
			if($member){
				// 查询会员等级
				$level_name = '普通会员';
				$level_icon = '';
				if(!empty($member['levelid'])){
					$level = Db::name('member_level')->where('id', $member['levelid'])->field('name,icon')->find();
					if($level){
						$level_name = $level['name'] ?: '普通会员';
						$level_icon = $level['icon'] ?: '';
					}
				}
				// 手机号脱敏
				$tel = '';
				if(!empty($member['tel']) && strlen($member['tel']) >= 7){
					$tel = substr($member['tel'], 0, 3) . '****' . substr($member['tel'], -4);
				}
				// 查询创作会员订阅状态
				$has_creative_member = false;
				$creative_version = '';
				$creative_version_name = '';
				$creative_expire_text = '';
				$creative_remaining_score = 0;
				try {
					$activeSub = Db::name('creative_member_subscription')
						->where('mid', $member['id'])
						->where('status', 1)
						->where('expire_time', '>', time())
						->order('id desc')
						->find();
					if($activeSub){
						$has_creative_member = true;
						$creative_version = $activeSub['version_code'] ?? '';
						$creative_remaining_score = intval($activeSub['remaining_score'] ?? 0);
						$creative_expire_text = date('Y-m-d', $activeSub['expire_time']);
						// 查询版本名称
						$planInfo = Db::name('creative_member_plan')
							->where('id', $activeSub['plan_id'])
							->field('version_name')
							->find();
						$creative_version_name = $planInfo['version_name'] ?? ucfirst($creative_version);
					}
				} catch(\Exception $e) {
					// 表不存在时忽略
				}

				return json(['status'=>1,'msg'=>'已登录','data'=>[
					'mid' => $member['id'],
					'nickname' => $member['nickname'] ?? '',
					'headimg' => $member['headimg'] ?? '',
					'money' => number_format(floatval($member['money']), 2, '.', ''),
					'score' => intval($member['score']),
					'level_name' => $level_name,
					'level_icon' => $level_icon,
					'tel' => $tel,
					'has_creative_member' => $has_creative_member,
					'creative_version' => $creative_version,
					'creative_version_name' => $creative_version_name,
					'creative_expire_text' => $creative_expire_text,
					'creative_remaining_score' => $creative_remaining_score
				]]);
			}
		}
		return json(['status'=>0,'msg'=>'未登录']);
	}

	/**
	 * AJAX: 退出登录（PC官网用）
	 */
	public function logout(){
		if(!request()->isAjax()){
			return json(['status'=>0,'msg'=>'非法请求']);
		}
		$sessionId = \think\facade\Session::getId();
		if($sessionId){
			cache($sessionId . '_mid', null);
			Db::name('session')->where('session_id', $sessionId)->delete();
		}
		return json(['status'=>1,'msg'=>'已退出登录']);
	}

	/**
	 * AJAX: 发送短信验证码（PC官网登录用）
	 */
	public function send_sms(){
		if(!request()->isAjax() || !request()->isPost()){
			return json(['status'=>0,'msg'=>'非法请求']);
		}
		$tel = trim(input('post.tel'));
		$defaultAid = 1;
		if(!checkTel($defaultAid, $tel)){
			return json(['status'=>0,'msg'=>'请输入正确的手机号']);
		}
		// 防频刷：60秒内只能发一次
		$sessionId = \think\facade\Session::getId();
		$lastSendTime = cache($sessionId . '_sms_time');
		if($lastSendTime && (time() - $lastSendTime) < 60){
			$remain = 60 - (time() - $lastSendTime);
			return json(['status'=>0,'msg'=>'请'.$remain.'秒后再试']);
		}
		$code = rand(100000, 999999);
		cache($sessionId . '_smscode', md5($tel.'-'.$code), 600);
		cache($sessionId . '_smscodetimes', 0);
		cache($sessionId . '_sms_time', time(), 120);

		// 读取短信配置，直接调用对应通道，避免Sms::send()内部强制status=1的问题
		$smsset = Db::name('admin_set_sms')->where('aid', $defaultAid)->find();
		if(!$smsset || $smsset['status'] != 1){
			return json(['status'=>0,'msg'=>'短信功能未开启，请在后台-系统设置-短信设置中配置']);
		}
		if(!$smsset['accesskey'] || !$smsset['accesssecret']){
			return json(['status'=>0,'msg'=>'短信参数未配置，请在后台-短信设置中填写AccessKey']);
		}
		$signName = $smsset['sign_name'];
		if(!$signName){
			return json(['status'=>0,'msg'=>'短信签名未配置，请在后台-短信设置中填写签名']);
		}
		if($smsset['tmpl_smscode_st'] != 1){
			return json(['status'=>0,'msg'=>'短信验证码模板未开启']);
		}
		$templateCode = $smsset['tmpl_smscode'];
		if(!$templateCode){
			return json(['status'=>0,'msg'=>'短信验证码模板ID未配置']);
		}
		$templateParam = ['code' => $code];

		if($smsset['smstype'] == 1){
			// 阿里云短信
			$rs = \app\common\Sms::alisms($defaultAid, $smsset['accesskey'], $smsset['accesssecret'], $signName, $templateCode, $tel, $templateParam);
		}elseif($smsset['smstype'] == 2){
			// 腾讯云短信
			if(!$smsset['sdkappid']){
				return json(['status'=>0,'msg'=>'腾讯云短信AppID未配置']);
			}
			if($smsset['code_length'] == 1){
				// code_length=1时需要截取参数长度，直接通过Sms::send走
				$rs = \app\common\Sms::send($defaultAid, $tel, 'tmpl_smscode', $templateParam);
				// Sms::send内部强制status=1，需通过msg/error判断实际结果
				if(!empty($rs['error']) || (isset($rs['msg']) && !in_array($rs['msg'], ['发送成功','操作成功']))){
					$errorMsg = $rs['error'] ?? ($rs['msg'] ?? '短信发送失败');
					return json(['status'=>0,'msg'=>'短信发送失败：'.$errorMsg]);
				}
				return json(['status'=>1,'msg'=>'发送成功']);
			}
			$rs = \app\common\Sms::tencentsms($defaultAid, $smsset['accesskey'], $smsset['accesssecret'], $smsset['sdkappid'], $signName, $templateCode, $tel, $templateParam);
		}elseif($smsset['smstype'] == 4){
			// 定制短信通道
			$rs = \app\common\Sms::customChannelSms($defaultAid, $smsset['accesskey'], $smsset['accesssecret'], $signName, $templateCode, $tel, $templateParam);
		}else{
			return json(['status'=>0,'msg'=>'不支持的短信通道类型']);
		}

		// 检查真实发送结果
		if(!$rs || (isset($rs['status']) && $rs['status'] != 1)){
			$errorMsg = $rs['error'] ?? ($rs['msg'] ?? '短信发送失败');
			return json(['status'=>0,'msg'=>'短信发送失败：'.$errorMsg]);
		}
		return json(['status'=>1,'msg'=>'发送成功']);
	}

	/**
	 * AJAX: 手机验证码登录（PC官网用，自动注册新会员）
	 */
	public function phone_login(){
		if(!request()->isAjax() || !request()->isPost()){
			return json(['status'=>0,'msg'=>'非法请求']);
		}
		$tel = trim(input('post.tel'));
		$smscode = trim(input('post.smscode'));
		$defaultAid = 1;

		if(!checkTel($defaultAid, $tel)){
			return json(['status'=>0,'msg'=>'请输入正确的手机号']);
		}
		if(!$smscode){
			return json(['status'=>0,'msg'=>'请输入验证码']);
		}

		$sessionId = \think\facade\Session::getId();
		// 验证短信验证码
		$cachedCode = cache($sessionId . '_smscode');
		$smscodetimes = cache($sessionId . '_smscodetimes') ?: 0;
		if(md5($tel.'-'.$smscode) != $cachedCode || $smscodetimes > 5){
			cache($sessionId . '_smscodetimes', $smscodetimes + 1);
			return json(['status'=>0,'msg'=>'验证码错误或已过期']);
		}
		// 清除验证码缓存
		cache($sessionId . '_smscode', null);
		cache($sessionId . '_smscodetimes', null);

		// 查找或创建会员
		$isNewUser = false;
		$member = Db::name('member')->where('aid', $defaultAid)->where('tel', $tel)->find();
		if(!$member){
			// 自动注册新会员
			$isNewUser = true;
			$data = [
				'aid' => $defaultAid,
				'tel' => $tel,
				'nickname' => substr($tel,0,3).'****'.substr($tel,-4),
				'sex' => 3,
				'headimg' => PRE_URL.'/static/img/touxiang.png',
				'createtime' => time(),
				'last_visittime' => time(),
				'platform' => 'h5'
			];
			$mid = \app\model\Member::add($defaultAid, $data);
		}else{
			$mid = $member['id'];
		}

		// 缓存会员session映射（7天）
		cache($sessionId . '_mid', $mid, 7*86400);

		// 写入session表
		Db::name('session')->where('session_id', $sessionId)->delete();
		Db::name('session')->insert([
			'session_id' => $sessionId,
			'aid' => $defaultAid,
			'mid' => $mid,
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
			'login_time' => time(),
			'login_ip' => request()->ip(),
			'platform' => 'h5'
		]);

		// 获取会员信息返回
		$memberInfo = Db::name('member')->where('id', $mid)->field('id,nickname,headimg,tel')->find();
		return json(['status'=>1,'msg'=>'登录成功','data'=>[
			'mid' => $memberInfo['id'],
			'nickname' => $memberInfo['nickname'] ?? '',
			'headimg' => $memberInfo['headimg'] ?? '',
			'tel' => $memberInfo['tel'] ? substr($memberInfo['tel'],0,3).'****'.substr($memberInfo['tel'],-4) : '',
			'is_new_user' => $isNewUser
		]]);
	}

	/**
	 * AJAX: 图片上传（PC官网弹窗用，无需aid鉴权）
	 */
	public function upload_image(){
		if(!request()->isPost()){
			return json(['status'=>0,'msg'=>'非法请求']);
		}

		$file = request()->file('file');
		if(!$file){
			$errorNo = isset($_FILES['file']['error']) ? $_FILES['file']['error'] : 4;
			$errMap = [
				1 => '上传的文件超过了 upload_max_filesize 限制',
				2 => '上传文件大小超过了表单限制',
				3 => '文件只有部分被上传',
				4 => '没有文件被上传',
				6 => '找不到临时文件夹',
				7 => '文件写入失败',
			];
			return json(['status'=>0,'msg'=>$errMap[$errorNo] ?? '上传失败']);
		}

		$defaultAid = 1;

		try {
			// 验证文件类型
			$allowedExt = ['jpg','jpeg','png','gif','bmp','webp'];
			$ext = strtolower($file->getOriginalExtension() ?: 'png');
			if(!in_array($ext, $allowedExt)){
				return json(['status'=>0,'msg'=>'不支持的图片格式，仅支持: '.implode(',',$allowedExt)]);
			}

			// 验证文件大小（10MB）
			if($file->getSize() > 10 * 1024 * 1024){
				return json(['status'=>0,'msg'=>'图片大小不能超过10MB']);
			}

			// 保存文件
			$savename = \think\facade\Filesystem::putFile(''.$defaultAid, $file);
			$filepath = 'upload/' . str_replace('\\', '/', $savename);

			// 缩略图处理
			if(in_array($ext, ['jpg','jpeg','png','bmp','webp'])){
				$remote = Db::name('sysset')->where('name','remote')->value('value');
				$remote = json_decode($remote, true);
				$maxwidth = ($remote['thumb']==1 ? $remote['thumb_width'] : 0);
				$maxheight = ($remote['thumb']==1 ? $remote['thumb_height'] : 0);
				$thumb_quality = $remote['thumb_quality'] ?? 100;

				if($maxwidth > 0 && $maxheight > 0){
					$size = getimagesize(ROOT_PATH . $filepath);
					$width = $size[0] ?? 0;
					$height = $size[1] ?? 0;
					if($width > $maxwidth || $height > $maxheight){
						$image = \think\Image::open(ROOT_PATH . $filepath);
						$thumbpath = substr($filepath, 0, strlen($filepath) - strlen($ext) - 1) . '_thumb.' . $ext;
						$image->thumb($maxwidth, $maxheight)->save(ROOT_PATH . $thumbpath, null, $thumb_quality);
						$filepath = $thumbpath;
					}
				}
			}

			$url = PRE_URL . '/' . $filepath;

			// OSS上传
			$url = \app\common\Pic::uploadoss($url, false, false);
			if($url === false){
				$url = PRE_URL . '/' . $filepath;
			}

			// 记录到用户云端存储空间
			try {
				$uploadMid = $this->_getLoginMid();
				if($uploadMid > 0){
					$uploadAid = 1;
					$uploadFileSize = $file->getSize();
					$storageService = new \app\service\StorageService();
					$storageService->addFile($uploadAid, $uploadMid, [
						'file_url' => $url,
						'thumbnail_url' => $url,
						'file_type' => 'image',
						'source_type' => 'upload',
						'source_id' => 0,
						'file_size' => $uploadFileSize,
					]);
				}
			} catch(\Exception $e) {
				// 存储记录失败不影响上传结果
			}

			return json(['status'=>1,'msg'=>'上传成功','url'=>$url]);

		} catch (\think\exception\ValidateException $e) {
			return json(['status'=>0,'msg'=>$e->getMessage()]);
		} catch (\Exception $e) {
			return json(['status'=>0,'msg'=>'上传失败: '.$e->getMessage()]);
		}
	}

	/**
	 * 创作会员订阅页面
	 */
	public function creative_member(){
		if($this->webinfo['showweb']!=3){
			header('Location:'.(string)url('Index/index')); die;
		}
		return View::fetch('index3/creative_member');
	}

	/**
	 * 创作中心页面
	 */
	public function creative_center(){
		if($this->webinfo['showweb']!=3){
			header('Location:'.(string)url('Index/index')); die;
		}
		return View::fetch('index3/creative_center');
	}

	/**
	 * AJAX: 获取创作会员套餐列表（PC官网用）
	 */
	public function creative_member_plans(){
		if(!request()->isAjax()){
			return json(['status'=>0,'msg'=>'非法请求']);
		}
		$defaultAid = 1;
		$mid = 0;
		$sessionId = \think\facade\Session::getId();
		if($sessionId){
			$mid = cache($sessionId . '_mid');
		}
		try {
			$service = new \app\service\CreativeMemberService();
			$result = $service->getPlanList($defaultAid, intval($mid));
			return json(['status'=>1,'msg'=>'success','data'=>$result]);
		} catch(\Exception $e) {
			return json(['status'=>0,'msg'=>'获取套餐列表失败']);
		}
	}

	/**
	 * AJAX: 购买创作会员（PC官网用）
	 */
	public function buy_creative_member(){
		if(!request()->isAjax() || !request()->isPost()){
			return json(['status'=>0,'msg'=>'非法请求']);
		}
		$sessionId = \think\facade\Session::getId();
		$mid = 0;
		if($sessionId){
			$mid = cache($sessionId . '_mid');
		}
		if(!$mid){
			return json(['status'=>0,'msg'=>'请先登录']);
		}
		$planId = intval(input('post.plan_id'));
		$purchaseMode = input('post.purchase_mode', '');
		if(!$planId || !$purchaseMode){
			return json(['status'=>0,'msg'=>'参数错误']);
		}
		$defaultAid = 1;
		try {
			$service = new \app\service\CreativeMemberService();
			$result = $service->buyCreativeMember($defaultAid, intval($mid), $planId, $purchaseMode);
			return json($result);
		} catch(\Exception $e) {
			return json(['status'=>0,'msg'=>'购买失败']);
		}
	}

	/**
	 * AJAX: 获取生成任务列表（创作中心用）
	 */
	public function generation_tasks(){
		if(!request()->isAjax()){
			return json(['code'=>-1,'msg'=>'非法请求']);
		}
		$sessionId = \think\facade\Session::getId();
		$mid = 0;
		if($sessionId){
			$mid = cache($sessionId . '_mid');
		}
		if(!$mid){
			return json(['code'=>-1,'msg'=>'请先登录']);
		}
		$status = input('param.status', 'all');
		$page = input('param.page/d', 1);
		$limit = input('param.limit/d', 10);
		if($limit > 50) $limit = 50;
		if($page < 1) $page = 1;

		$where = [];
		$where[] = ['mid', '=', $mid];
		if($status != 'all'){
			$statusMap = [
				'pending' => 0,
				'processing' => 1,
				'completed' => 2,
				'failed' => 3
			];
			if(isset($statusMap[$status])){
				$where[] = ['status', '=', $statusMap[$status]];
			}
		}

		$list = Db::name('generation_record')
			->field('id, prompt, generation_type, status, createtime')
			->where($where)
			->order('id desc')
			->page($page, $limit)
			->select()
			->toArray();

		return json(['code'=>0, 'msg'=>'success', 'data'=>$list]);
	}

	/**
	 * AJAX: 获取生成作品列表（创作中心用）
	 */
	public function generation_works(){
		if(!request()->isAjax()){
			return json(['code'=>-1,'msg'=>'非法请求']);
		}
		$sessionId = \think\facade\Session::getId();
		$mid = 0;
		if($sessionId){
			$mid = cache($sessionId . '_mid');
		}
		if(!$mid){
			return json(['code'=>-1,'msg'=>'请先登录']);
		}
		$page = input('param.page/d', 1);
		$limit = input('param.limit/d', 12);
		if($limit > 50) $limit = 50;
		if($page < 1) $page = 1;

		$list = Db::name('generation_output')
			->alias('o')
			->leftJoin('generation_record r', 'o.record_id = r.id')
			->field('o.id, o.output_url, o.thumbnail_url, r.prompt, r.createtime')
			->where('r.mid', $mid)
			->where('r.status', 2)
			->order('o.id desc')
			->page($page, $limit)
			->select()
			->toArray();

		return json(['code'=>0, 'msg'=>'success', 'data'=>$list]);
	}

	// =================================================================
	// H5 用户交互流程与支付功能
	// =================================================================

	/**
	 * 获取当前登录用户信息（内部辅助）
	 */
	private function _getLoginMid(){
		$sessionId = \think\facade\Session::getId();
		if($sessionId){
			return intval(cache($sessionId . '_mid'));
		}
		return 0;
	}

	/**
	 * 余额充值页面
	 */
	public function recharge(){
		if($this->webinfo['showweb']!=3){
			header('Location:'.(string)url('Index/index')); die;
		}
		return View::fetch('index3/recharge');
	}

	/**
	 * AJAX: 获取充值配置
	 */
	public function recharge_config(){
		if(!request()->isAjax()) return json(['status'=>0,'msg'=>'非法请求']);
		$mid = $this->_getLoginMid();
		if(!$mid) return json(['status'=>0,'msg'=>'请先登录']);

		$defaultAid = 1;
		$set = Db::name('admin_set')->where('aid', $defaultAid)->find();

		// 检查充值功能是否开启
		if(empty($set['recharge'])){
			return json(['status'=>0,'msg'=>'充值功能暂未开放']);
		}

		// 充值档位
		$recharge_set = Db::name('sysset')->where('name','recharge')->value('value');
		$recharge_set = $recharge_set ? json_decode($recharge_set, true) : [];
		$levels = [];
		if(!empty($recharge_set['list'])){
			foreach($recharge_set['list'] as $item){
				$levels[] = [
					'money' => floatval($item['money'] ?? 0),
					'give_money' => floatval($item['give_money'] ?? 0),
					'give_score' => intval($item['give_score'] ?? 0),
				];
			}
		}
		if(empty($levels)){
			// 默认档位
			$levels = [
				['money'=>10,'give_money'=>0,'give_score'=>0],
				['money'=>50,'give_money'=>0,'give_score'=>0],
				['money'=>100,'give_money'=>5,'give_score'=>0],
				['money'=>200,'give_money'=>15,'give_score'=>0],
				['money'=>500,'give_money'=>50,'give_score'=>10],
				['money'=>1000,'give_money'=>120,'give_score'=>30],
			];
		}

		$min_amount = floatval($recharge_set['min_amount'] ?? 1);
		$custom_amount = isset($recharge_set['custom_amount']) ? intval($recharge_set['custom_amount']) : 1;

		return json(['status'=>1,'msg'=>'success','data'=>[
			'levels' => $levels,
			'custom_amount' => (bool)$custom_amount,
			'min_amount' => $min_amount > 0 ? $min_amount : 1,
		]]);
	}

	/**
	 * AJAX: 创建充值订单
	 */
	public function create_recharge_order(){
		if(!request()->isAjax() || !request()->isPost()) return json(['status'=>0,'msg'=>'非法请求']);
		$mid = $this->_getLoginMid();
		if(!$mid) return json(['status'=>0,'msg'=>'请先登录']);

		$money = floatval(input('post.money'));
		$defaultAid = 1;

		if($money <= 0) return json(['status'=>0,'msg'=>'请输入正确的充值金额']);

		$set = Db::name('admin_set')->where('aid', $defaultAid)->find();
		if(empty($set['recharge'])) return json(['status'=>0,'msg'=>'充值功能暂未开放']);

		// 最低充值金额校验
		$recharge_minimum = floatval($set['recharge_minimum'] ?? 0);
		if($recharge_minimum > 0 && $money < $recharge_minimum){
			return json(['status'=>0,'msg'=>'最低充值金额为'.$recharge_minimum.'元']);
		}

		// 生成订单号
		$ordernum = 'RC' . date('YmdHis') . rand(1000,9999);

		Db::startTrans();
		try {
			// 创建 recharge_order（表字段：aid,mid,money,ordernum,createtime,status,platform）
			$rechargeData = [
				'aid' => $defaultAid,
				'mid' => $mid,
				'ordernum' => $ordernum,
				'money' => $money,
				'status' => 0,
				'createtime' => time(),
				'platform' => 'h5',
			];
			$rechargeOrderId = Db::name('recharge_order')->insertGetId($rechargeData);

			// 创建 payorder（通过标准方式，确保字段完整）
			$payorderId = \app\model\Payorder::createorder(
				$defaultAid, 0, $mid,
				'recharge',
				$rechargeOrderId,
				$ordernum,
				'余额充值¥'.$money,
				$money
			);
			// 更新 recharge_order 关联 payorderid
			Db::name('recharge_order')->where('id', $rechargeOrderId)->update(['payorderid' => $payorderId]);
			// 标记平台
			Db::name('payorder')->where('id', $payorderId)->update(['platform' => 'h5']);

			Db::commit();
			return json(['status'=>1,'msg'=>'订单创建成功','data'=>['ordernum'=>$ordernum]]);
		} catch(\Exception $e) {
			Db::rollback();
			return json(['status'=>0,'msg'=>'创建订单失败：'.$e->getMessage()]);
		}
	}

	/**
	 * 积分购买页面
	 */
	public function score_shop(){
		if($this->webinfo['showweb']!=3){
			header('Location:'.(string)url('Index/index')); die;
		}
		return View::fetch('index3/score_shop');
	}

	/**
	 * AJAX: 获取积分购买配置
	 */
	public function score_config(){
		if(!request()->isAjax()) return json(['status'=>0,'msg'=>'非法请求']);
		$mid = $this->_getLoginMid();
		if(!$mid) return json(['status'=>0,'msg'=>'请先登录']);

		$defaultAid = 1;
		$set = Db::name('admin_set')->where('aid', $defaultAid)->find();

		// 积分购买配置
		$score_set = Db::name('sysset')->where('name','score_shop')->value('value');
		$score_set = $score_set ? json_decode($score_set, true) : [];

		$levels = [];
		if(!empty($score_set['list'])){
			foreach($score_set['list'] as $item){
				$levels[] = [
					'score' => intval($item['score'] ?? 0),
					'price' => floatval($item['price'] ?? 0),
					'give_score' => intval($item['give_score'] ?? 0),
				];
			}
		}

		if(empty($levels)){
			// 默认档位
			$scorein_money = floatval($set['scorein_money'] ?? 1);
			$scorein_score = intval($set['scorein_score'] ?? 1);
			if($scorein_money > 0 && $scorein_score > 0){
				$ratio = $scorein_score / $scorein_money;
				$levels = [
					['score'=>intval(10*$ratio),'price'=>10,'give_score'=>0],
					['score'=>intval(50*$ratio),'price'=>50,'give_score'=>intval(5*$ratio)],
					['score'=>intval(100*$ratio),'price'=>100,'give_score'=>intval(15*$ratio)],
					['score'=>intval(200*$ratio),'price'=>200,'give_score'=>intval(30*$ratio)],
					['score'=>intval(500*$ratio),'price'=>500,'give_score'=>intval(100*$ratio)],
				];
			} else {
				$levels = [
					['score'=>100,'price'=>10,'give_score'=>0],
					['score'=>500,'price'=>50,'give_score'=>50],
					['score'=>1000,'price'=>100,'give_score'=>150],
				];
			}
		}

		return json(['status'=>1,'msg'=>'success','data'=>['levels'=>$levels]]);
	}

	/**
	 * AJAX: 创建积分购买订单
	 */
	public function create_score_order(){
		if(!request()->isAjax() || !request()->isPost()) return json(['status'=>0,'msg'=>'非法请求']);
		$mid = $this->_getLoginMid();
		if(!$mid) return json(['status'=>0,'msg'=>'请先登录']);

		$score = intval(input('post.score'));
		$money = floatval(input('post.money'));
		$defaultAid = 1;

		if($score <= 0 || $money <= 0) return json(['status'=>0,'msg'=>'参数错误']);

		// 查找赠送积分
		$give_score = 0;
		$score_set = Db::name('sysset')->where('name','score_shop')->value('value');
		$score_set = $score_set ? json_decode($score_set, true) : [];
		if(!empty($score_set['list'])){
			foreach($score_set['list'] as $item){
				if(intval($item['score']) == $score && abs(floatval($item['price']) - $money) < 0.01){
					$give_score = intval($item['give_score'] ?? 0);
					break;
				}
			}
		}

		$total_score = $score + $give_score;
		$ordernum = 'SP' . date('YmdHis') . rand(1000,9999);

		Db::startTrans();
		try {
			// 创建 payorder（score字段存储购买积分总数，type=score_buy用于回调处理）
			$payorderId = \app\model\Payorder::createorder(
				$defaultAid, 0, $mid,
				'score_buy',
				0,
				$ordernum,
				'积分购买'.$score.'积分',
				$money,
				$total_score
			);
			Db::name('payorder')->where('id', $payorderId)->update(['platform' => 'h5']);

			Db::commit();
			return json(['status'=>1,'msg'=>'订单创建成功','data'=>['ordernum'=>$ordernum]]);
		} catch(\Exception $e) {
			Db::rollback();
			return json(['status'=>0,'msg'=>'创建订单失败：'.$e->getMessage()]);
		}
	}

	/**
	 * 会员等级页面
	 */
	public function member_level(){
		if($this->webinfo['showweb']!=3){
			header('Location:'.(string)url('Index/index')); die;
		}
		return View::fetch('index3/member_level');
	}
	
	/**
	 * AI旅拍首页
	 */
	public function home(){
		if(empty($this->webinfo) || $this->webinfo['showweb']!=3){
			header('Location:'.(string)url('Index/index')); die;
		}
		return View::fetch('index3/home');
	}
	
	/**
	 * AI旅拍落地页
	 */
	public function travel_photo(){
		if($this->webinfo['showweb']!=3){
			header('Location:'.(string)url('Index/index')); die;
		}
		return View::fetch('index3/travel_photo');
	}

	/**
	 * AJAX: 获取等级列表
	 */
	public function level_list(){
		if(!request()->isAjax()) return json(['status'=>0,'msg'=>'非法请求']);
		$mid = $this->_getLoginMid();
		if(!$mid) return json(['status'=>0,'msg'=>'请先登录']);

		$defaultAid = 1;
		$member = Db::name('member')->where('id', $mid)->field('id,levelid,money,score')->find();
		if(!$member) return json(['status'=>0,'msg'=>'用户不存在']);

		// 当前等级
		$currentSort = 0;
		$current_level = ['id'=>0,'name'=>'普通会员','icon'=>'','sort'=>0];
		if($member['levelid'] > 0){
			$cl = Db::name('member_level')->where('id', $member['levelid'])->where('aid', $defaultAid)->find();
			if($cl){
				$current_level = ['id'=>$cl['id'],'name'=>$cl['name'],'icon'=>$cl['icon'] ?? '','sort'=>intval($cl['sort'])];
				$currentSort = intval($cl['sort']);
			}
		}

		// 获取会员累计数据用于条件判断（与系统ApiMy保持一致：shop_order取status=3已完成）
		$totalOrderMoney = Db::name('shop_order')->where('aid', $defaultAid)->where('mid', $mid)->where('status', 3)->sum('totalprice') ?: 0;
		$totalRechargeMoney = Db::name('recharge_order')->where('aid', $defaultAid)->where('mid', $mid)->where('status', 1)->sum('money') ?: 0;

		// 所有等级
		$allLevels = Db::name('member_level')
			->where('aid', $defaultAid)
			->order('sort asc, id asc')
			->select()
			->toArray();

		$levels = [];
		foreach($allLevels as $lv){
			$lvSort = intval($lv['sort']);
			$canApply = intval($lv['can_apply'] ?? 0);
			$applyPaymoney = floatval($lv['apply_paymoney'] ?? 0);
			$applyCheck = intval($lv['apply_check'] ?? 0);
			// 处理NULL值（数据库中默认等级这两个字段可能为NULL）
			$applyOrdermoney = floatval(empty($lv['apply_ordermoney']) ? 0 : $lv['apply_ordermoney']);
			$applyRechargemoney = floatval(empty($lv['apply_rechargemoney']) ? 0 : $lv['apply_rechargemoney']);

			// 构建升级条件描述（条件间用"或"连接，与系统保持一致）
			$applyConditions = [];
			if($applyOrdermoney > 0) $applyConditions[] = '累计消费满¥'.number_format($applyOrdermoney,2);
			if($applyRechargemoney > 0) $applyConditions[] = '累计充值满¥'.number_format($applyRechargemoney,2);
			$conditionText = $applyConditions ? implode(' 或 ', $applyConditions) : '';
			if($applyPaymoney > 0){
				$payText = ($lv['apply_paytxt'] ?: '升级费用').'¥'.number_format($applyPaymoney,2);
				$conditionText = $conditionText ? $conditionText . '，并支付' . $payText : $payText;
			}
			if(!$conditionText && $lv['isdefault']) $conditionText = '默认等级';

			// 判断是否可以申请升级（仅能向上升级，且can_apply=1）
			// 条件判断使用OR逻辑（与系统ApiMy.php保持一致）：满足任一条件即可申请
			$realCanApply = 0;
			$needPay = 0;
			$meetCondition = false;

			if($canApply == 1 && $lvSort > $currentSort){
				// OR逻辑：无条件 / 满足订单金额 / 满足充值金额 → 任一即可
				if($applyOrdermoney <= 0 && $applyRechargemoney <= 0){
					$meetCondition = true; // 无金额条件，直接可申请
				}
				if($applyOrdermoney > 0 && $totalOrderMoney >= $applyOrdermoney){
					$meetCondition = true; // 消费金额条件满足
				}
				if($applyRechargemoney > 0 && $totalRechargeMoney >= $applyRechargemoney){
					$meetCondition = true; // 充值金额条件满足
				}

				if($meetCondition){
					$realCanApply = 1;
					if($applyPaymoney > 0) $needPay = 1;
				}
			}

			$item = [
				'id' => $lv['id'],
				'name' => $lv['name'],
				'icon' => $lv['icon'] ?? '',
				'sort' => $lvSort,
				'condition_text' => $conditionText,
				'discount' => floatval($lv['discount'] ?? 0),
				'can_apply' => $realCanApply,
				'need_pay' => $needPay,
				'apply_paymoney' => $applyPaymoney,
				'apply_check' => $applyCheck,
				'is_default' => intval($lv['isdefault'] ?? 0),
				'explain' => $lv['explain'] ?? '',
				'meet_condition' => $meetCondition ? 1 : 0,
				'can_apply_setting' => $canApply, // 后台是否开启了申请升级
			];
			$levels[] = $item;
		}

		return json(['status'=>1,'msg'=>'success','data'=>[
			'current_level' => $current_level,
			'levels' => $levels,
		]]);
	}

	/**
	 * AJAX: 申请等级
	 */
	public function apply_level(){
		if(!request()->isAjax() || !request()->isPost()) return json(['status'=>0,'msg'=>'非法请求']);
		$mid = $this->_getLoginMid();
		if(!$mid) return json(['status'=>0,'msg'=>'请先登录']);

		$level_id = intval(input('post.level_id'));
		if(!$level_id) return json(['status'=>0,'msg'=>'参数错误']);

		$defaultAid = 1;
		$member = Db::name('member')->where('id', $mid)->find();
		if(!$member) return json(['status'=>0,'msg'=>'用户不存在']);

		$level = Db::name('member_level')->where('id', $level_id)->where('aid', $defaultAid)->find();
		if(!$level) return json(['status'=>0,'msg'=>'等级不存在']);

		// 必须开启申请
		if(intval($level['can_apply'] ?? 0) != 1){
			return json(['status'=>0,'msg'=>'该等级暂不可申请']);
		}

		// 等级排序校验：只能向上升级
		$currentSort = 0;
		if($member['levelid'] > 0){
			$currentSort = intval(Db::name('member_level')->where('id', $member['levelid'])->value('sort') ?: 0);
		}
		if(intval($level['sort']) <= $currentSort){
			return json(['status'=>0,'msg'=>'您当前等级已等于或高于该等级']);
		}

		// 检查申请条件（OR逻辑，与系统ApiMy.php保持一致）
		$applyOrdermoney = floatval(empty($level['apply_ordermoney']) ? 0 : $level['apply_ordermoney']);
		$applyRechargemoney = floatval(empty($level['apply_rechargemoney']) ? 0 : $level['apply_rechargemoney']);

		$canapply = 0;
		if($applyOrdermoney <= 0 && $applyRechargemoney <= 0){
			$canapply = 1; // 无金额条件，直接可申请
		}
		if($applyOrdermoney > 0){
			$totalOrderMoney = Db::name('shop_order')->where('aid', $defaultAid)->where('mid', $mid)->where('status', 3)->sum('totalprice') ?: 0;
			if($totalOrderMoney >= $applyOrdermoney){
				$canapply = 1; // 消费金额条件满足
			}
		}
		if($applyRechargemoney > 0){
			$totalRechargeMoney = Db::name('recharge_order')->where('aid', $defaultAid)->where('mid', $mid)->where('status', 1)->sum('money') ?: 0;
			if($totalRechargeMoney >= $applyRechargemoney){
				$canapply = 1; // 充值金额条件满足
			}
		}

		if(!$canapply){
			$msg = '不满足申请条件';
			if($applyOrdermoney > 0) $msg = '需累计消费满¥'.number_format($applyOrdermoney,2);
			if($applyRechargemoney > 0) $msg = '需累计充值满¥'.number_format($applyRechargemoney,2);
			if($applyOrdermoney > 0 && $applyRechargemoney > 0) $msg = '需累计消费满¥'.number_format($applyOrdermoney,2).'或充值满¥'.number_format($applyRechargemoney,2);
			return json(['status'=>0,'msg'=>$msg]);
		}

		// 检查是否有待审核的记录
		$hasPending = Db::name('member_levelup_order')->where('aid', $defaultAid)->where('mid', $mid)->where('levelid', $level_id)->where('status', 1)->where('type', 0)->find();
		if($hasPending){
			return json(['status'=>0,'msg'=>'您已经提交过该等级的申请，请等待审核']);
		}

		$applyPaymoney = floatval($level['apply_paymoney'] ?? 0);

		// 构建升级订单数据（所有升级操作都创建member_levelup_order记录）
		$ordernum = 'LV' . date('YmdHis') . rand(1000,9999);
		$orderData = [
			'aid' => $defaultAid,
			'mid' => $mid,
			'levelid' => $level_id,
			'ordernum' => $ordernum,
			'totalprice' => $applyPaymoney,
			'title' => '升级成为'.$level['name'],
			'createtime' => time(),
			'beforelevelid' => intval($member['levelid']),
			'platform' => 'h5',
			'pid' => intval($member['pid'] ?? 0),
			'type' => 0, // 0=升级
		];

		if($applyPaymoney > 0){
			// 付费等级：创建member_levelup_order + payorder
			Db::startTrans();
			try {
				$orderData['status'] = 0; // 待支付
				$levelupOrderId = Db::name('member_levelup_order')->insertGetId($orderData);

				$payorderId = \app\model\Payorder::createorder(
					$defaultAid, 0, $mid,
					'member_levelup',
					$levelupOrderId,
					$ordernum,
					'升级成为'.$level['name'],
					$applyPaymoney
				);
				Db::name('member_levelup_order')->where('id', $levelupOrderId)->update(['payorderid' => $payorderId]);
				Db::name('payorder')->where('id', $payorderId)->update(['platform' => 'h5']);

				Db::commit();
				return json(['status'=>1,'msg'=>'请完成支付','data'=>[
					'need_pay' => true,
					'ordernum' => $ordernum,
					'price' => number_format($applyPaymoney, 2, '.', ''),
				]]);
			} catch(\Exception $e) {
				Db::rollback();
				return json(['status'=>0,'msg'=>'创建订单失败：'.$e->getMessage()]);
			}
		} else {
			// 免费等级：创建member_levelup_order记录 + 调用系统标准升级流程
			$orderData['status'] = 1; // 已支付（免费）
			$levelupOrderId = Db::name('member_levelup_order')->insertGetId($orderData);

			// 调用系统标准的升级处理流程（包含审核判断、分销提成、等级有效期等逻辑）
			\app\model\Payorder::member_levelup_pay($levelupOrderId);

			if(intval($level['apply_check'] ?? 0) == 1){
				return json(['status'=>1,'msg'=>'申请已提交，请等待审核','data'=>['need_pay'=>false]]);
			} else {
				return json(['status'=>1,'msg'=>'升级成功','data'=>['need_pay'=>false]]);
			}
		}
	}

	/**
	 * 个人中心页面
	 */
	public function user_center(){
		if($this->webinfo['showweb']!=3){
			header('Location:'.(string)url('Index/index')); die;
		}
		return View::fetch('index3/user_center');
	}

	/**
	 * AJAX: 获取个人中心数据
	 */
	public function user_center_data(){
		if(!request()->isAjax()) return json(['status'=>0,'msg'=>'非法请求']);
		$mid = $this->_getLoginMid();
		if(!$mid) return json(['status'=>0,'msg'=>'请先登录']);

		$member = Db::name('member')->where('id', $mid)->find();
		if(!$member) return json(['status'=>0,'msg'=>'用户不存在']);

		// 等级信息
		$level_name = '普通会员';
		$level_icon = '';
		if(!empty($member['levelid'])){
			$level = Db::name('member_level')->where('id', $member['levelid'])->field('name,icon')->find();
			if($level){
				$level_name = $level['name'] ?: '普通会员';
				$level_icon = $level['icon'] ?: '';
			}
		}

		// 手机号脱敏
		$tel = '';
		if(!empty($member['tel']) && strlen($member['tel']) >= 7){
			$tel = substr($member['tel'], 0, 3) . '****' . substr($member['tel'], -4);
		}

		// 创作会员状态
		$creative_member = ['has_creative_member'=>false];
		try {
			$activeSub = Db::name('creative_member_subscription')
				->where('mid', $mid)->where('status', 1)->where('expire_time', '>', time())
				->order('id desc')->find();
			if($activeSub){
				$creative_member['has_creative_member'] = true;
				$creative_member['creative_version'] = $activeSub['version_code'] ?? '';
				$creative_member['creative_remaining_score'] = intval($activeSub['remaining_score'] ?? 0);
				$creative_member['creative_expire_text'] = date('Y-m-d', $activeSub['expire_time']);
				$planInfo = Db::name('creative_member_plan')->where('id', $activeSub['plan_id'])->field('version_name')->find();
				$creative_member['creative_version_name'] = $planInfo['version_name'] ?? '';
			}
		} catch(\Exception $e) {}

		// 订单统计
		$order_stats = ['total'=>0,'pending'=>0];
		try {
			$order_stats['total'] = Db::name('payorder')->where('mid', $mid)->count();
			$order_stats['pending'] = Db::name('payorder')->where('mid', $mid)->where('status', 0)->count();
		} catch(\Exception $e) {}

		return json(['status'=>1,'msg'=>'success','data'=>[
			'userinfo' => [
				'mid' => $member['id'],
				'nickname' => $member['nickname'] ?? '',
				'headimg' => $member['headimg'] ?? '',
				'tel' => $tel,
				'realname' => $member['realname'] ?? '',
			],
			'money' => number_format(floatval($member['money']), 2, '.', ''),
			'score' => intval($member['score']),
			'level_name' => $level_name,
			'level_icon' => $level_icon,
			'creative_member' => $creative_member,
			'order_stats' => $order_stats,
		]]);
	}

	/**
	 * AJAX: H5支付调起（统一入口）
	 */
	public function h5_pay(){
		if(!request()->isAjax() || !request()->isPost()) return json(['status'=>0,'msg'=>'非法请求']);
		$mid = $this->_getLoginMid();
		if(!$mid) return json(['status'=>0,'msg'=>'请先登录']);

		$ordernum = input('post.ordernum', '');
		$pay_type = input('post.pay_type', '');
		$order_type = input('post.order_type', '');

		if(!$ordernum || !$pay_type) return json(['status'=>0,'msg'=>'参数错误']);

		$defaultAid = 1;

		// 查询订单
		$payorder = Db::name('payorder')->where('ordernum', $ordernum)->where('mid', $mid)->find();
		if(!$payorder) return json(['status'=>0,'msg'=>'订单不存在']);
		if($payorder['status'] == 1) return json(['status'=>0,'msg'=>'订单已支付']);

		$price = floatval($payorder['money']);
		$title = $payorder['title'] ?: '订单支付';
		// payorder.type作为Wxpay/Alipay的tablename参数，用于notify回调路由
		$tablename = $payorder['type'] ?: 'recharge';
		if($price <= 0) return json(['status'=>0,'msg'=>'订单金额异常']);

		// 获取应用支付配置（支持PC端独立配置）
		$platform = input('post.platform', 'h5');
		if(!in_array($platform, ['h5', 'pc'])) $platform = 'h5';

		// PC端走独立支付逻辑
		if($platform === 'pc'){
			return $this->_pcPay($defaultAid, $mid, $payorder, $pay_type, $title, $ordernum, $price, $tablename);
		}

		$appinfo = \app\common\System::appinfo($defaultAid, $platform);

		// 检测浏览器UA
		$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
		$isWechat = (strpos($ua, 'micromessenger') !== false);

		if($pay_type === 'wxpay'){
			// 检查微信支付配置
			if(empty($appinfo['wxpay_mchid']) || empty($appinfo['wxpay_mchkey'])){
				return json(['status'=>0,'msg'=>'微信支付未配置']);
			}

			if($isWechat){
				// 微信浏览器内：JSAPI支付
				$member = Db::name('member')->where('id', $mid)->find();
				$openid = $member['mpopenid'] ?? '';
				if(empty($openid)) return json(['status'=>0,'msg'=>'请先绑定微信账号']);
				$rs = \app\common\Wxpay::build_mp($defaultAid, 0, $mid, $title, $ordernum, $price, $tablename, '', $openid);
				if($rs['status'] == 1){
					return json(['status'=>1,'msg'=>'success','data'=>[
						'pay_method' => 'jsapi',
						'jsapi_params' => $rs['data']
					]]);
				}
				return json(['status'=>0,'msg'=>$rs['msg'] ?? '支付创建失败']);
			} else {
				// 普通浏览器：Native支付（二维码）
				$rs = \app\common\Wxpay::build_pay_native_h5($defaultAid, 0, $mid, $title, $ordernum, $price, $tablename, '', $platform);
				if($rs['status'] == 1 && !empty($rs['data']['pay_wx_qrcode_url'])){
					return json(['status'=>1,'msg'=>'success','data'=>[
						'pay_method' => 'qrcode',
						'qrcode_url' => $rs['data']['pay_wx_qrcode_url']
					]]);
				}
				// 回退到H5支付
				$rs2 = \app\common\Wxpay::build_h5($defaultAid, 0, $mid, $title, $ordernum, $price, $tablename);
				if($rs2['status'] == 1 && !empty($rs2['data']['mweb_url'])){
					return json(['status'=>1,'msg'=>'success','data'=>[
						'pay_method' => 'redirect',
						'redirect_url' => $rs2['data']['mweb_url']
					]]);
				}
				return json(['status'=>0,'msg'=>$rs['msg'] ?? $rs2['msg'] ?? '微信支付创建失败']);
			}

		} elseif($pay_type === 'alipay'){
			// 检查支付宝配置
			if(empty($appinfo['ali_appid']) || empty($appinfo['ali_privatekey']) || empty($appinfo['ali_publickey'])){
				return json(['status'=>0,'msg'=>'支付宝支付未配置']);
			}

			$return_url = PRE_URL . '/?s=/index/recharge';
			$alipay = new \app\common\Alipay();
			$rs = $alipay->build_h5($defaultAid, 0, $mid, $title, $ordernum, $price, $tablename, '', $return_url);

			if($rs['status'] == 1){
				// 支付宝返回的可能是表单HTML或跳转URL
				$payUrl = '';
				if(is_object($rs['data']) && isset($rs['data']->body)){
					$payUrl = $rs['data']->body;
				} elseif(is_string($rs['data'])){
					$payUrl = $rs['data'];
				}
				return json(['status'=>1,'msg'=>'success','data'=>[
					'pay_method' => 'redirect',
					'redirect_url' => $payUrl
				]]);
			}
			return json(['status'=>0,'msg'=>$rs['msg'] ?? '支付宝支付创建失败']);

		} else {
			return json(['status'=>0,'msg'=>'不支持的支付方式']);
		}
	}

	/**
	 * PC端专属支付逻辑
	 * 微信：V3 Native下单（二维码）
	 * 支付宝：当面付预创建（二维码）/ 电脑网站支付（表单）
	 * pay_type=all 时同时返回微信和支付宝二维码，供前端双码弹窗展示
	 */
	private function _pcPay($aid, $mid, $payorder, $pay_type, $title, $ordernum, $price, $tablename){
		$appinfo = \app\common\System::appinfo($aid, 'pc');

		// ===== 双码模式：同时返回微信和支付宝二维码 =====
		if($pay_type === 'all'){
			$result = ['wxpay_qrcode' => '', 'alipay_qrcode' => ''];

			// 尝试生成微信二维码
			if(!empty($appinfo['wxpay_mchid']) && !empty($appinfo['wxpay_mchkey_v3']) && !empty($appinfo['wxpay_apiclient_key']) && !empty($appinfo['wxpay_serial_no'])){
				try {
					$rs = \app\common\Wxpay::build_native_v3($aid, 0, $mid, $title, $ordernum, $price, $tablename);
					if($rs['status'] == 1 && !empty($rs['data']['pay_wx_qrcode_url'])){
						$result['wxpay_qrcode'] = $rs['data']['pay_wx_qrcode_url'];
					}
				} catch(\Exception $e){
					\think\facade\Log::error('PC双码-微信异常: '.$e->getMessage());
				}
			}

			// 尝试生成支付宝二维码（仅当配置为当面付模式时才能生成二维码）
			$ali_pc_pay_type = isset($appinfo['ali_pc_pay_type']) ? intval($appinfo['ali_pc_pay_type']) : 2;
			if($ali_pc_pay_type == 0 && !empty($appinfo['ali_appid']) && !empty($appinfo['ali_privatekey']) && !empty($appinfo['ali_publickey'])){
				try {
					$rs = \app\common\Alipay::build_precreate($aid, 0, $mid, $title, $ordernum, $price, $tablename);
					if($rs['status'] == 1 && !empty($rs['data']['qrcode_url'])){
						$result['alipay_qrcode'] = $rs['data']['qrcode_url'];
					}
				} catch(\Exception $e){
					\think\facade\Log::error('PC双码-支付宝异常: '.$e->getMessage());
				}
			}

			if(!empty($result['wxpay_qrcode']) || !empty($result['alipay_qrcode'])){
				return json(['status'=>1,'msg'=>'success','data'=>[
					'pay_method' => 'dual_qrcode',
					'wxpay_qrcode' => $result['wxpay_qrcode'],
					'alipay_qrcode' => $result['alipay_qrcode'],
				]]);
			}

			// 双码均失败：尝试降级到单一支付方式（表单跳转模式）
			if(!empty($appinfo['ali_appid']) && !empty($appinfo['ali_privatekey']) && !empty($appinfo['ali_publickey'])){
				if($ali_pc_pay_type == 2 || $ali_pc_pay_type == 0){
					// 手机网站支付（当面付precreate失败也降级到此）
					$rs = \app\common\Alipay::build_wap_pay($aid, 0, $mid, $title, $ordernum, $price, $tablename);
					if($rs['status'] == 1 && !empty($rs['data']['form_html'])){
						return json(['status'=>1,'msg'=>'success','data'=>[
							'pay_method' => 'form',
							'form_html' => $rs['data']['form_html'],
						]]);
					}
				} elseif($ali_pc_pay_type == 1){
					$rs = \app\common\Alipay::build_page_pay($aid, 0, $mid, $title, $ordernum, $price, $tablename);
					if($rs['status'] == 1 && !empty($rs['data']['form_html'])){
						return json(['status'=>1,'msg'=>'success','data'=>[
							'pay_method' => 'form',
							'form_html' => $rs['data']['form_html'],
						]]);
					}
				}
			}

			return json(['status'=>0,'msg'=>'没有可用的支付方式，请检查支付配置（微信需填写证书序列号，支付宝需开通对应产品）']);
		}

		// ===== 单一支付方式 =====
		if($pay_type === 'wxpay'){
			// 检查微信V3支付配置
			if(empty($appinfo['wxpay_mchid']) || empty($appinfo['wxpay_mchkey_v3']) || empty($appinfo['wxpay_apiclient_key']) || empty($appinfo['wxpay_serial_no'])){
				return json(['status'=>0,'msg'=>'微信支付未配置（V3）']);
			}

			// PC端：微信V3 Native下单
			$rs = \app\common\Wxpay::build_native_v3($aid, 0, $mid, $title, $ordernum, $price, $tablename);
			if($rs['status'] == 1 && !empty($rs['data']['pay_wx_qrcode_url'])){
				return json(['status'=>1,'msg'=>'success','data'=>[
					'pay_method' => 'qrcode',
					'qrcode_url' => $rs['data']['pay_wx_qrcode_url']
				]]);
			}
			return json(['status'=>0,'msg'=>$rs['msg'] ?? '微信支付创建失败']);

		} elseif($pay_type === 'alipay'){
			// 检查支付宝配置
			if(empty($appinfo['ali_appid']) || empty($appinfo['ali_privatekey']) || empty($appinfo['ali_publickey'])){
				return json(['status'=>0,'msg'=>'支付宝支付未配置']);
			}

			// 根据配置的支付模式选择调用方式
			$ali_pc_pay_type = isset($appinfo['ali_pc_pay_type']) ? intval($appinfo['ali_pc_pay_type']) : 2;

			if($ali_pc_pay_type == 1){
				// 电脑网站支付（alipay.trade.page.pay）- 跳转支付宝收银台
				$rs = \app\common\Alipay::build_page_pay($aid, 0, $mid, $title, $ordernum, $price, $tablename);
				if($rs['status'] == 1 && !empty($rs['data']['form_html'])){
					return json(['status'=>1,'msg'=>'success','data'=>[
						'pay_method' => 'form',
						'form_html' => $rs['data']['form_html'],
					]]);
				}
				// 降级尝试当面付二维码
				$rs2 = \app\common\Alipay::build_precreate($aid, 0, $mid, $title, $ordernum, $price, $tablename);
				if($rs2['status'] == 1 && !empty($rs2['data']['qrcode_url'])){
					return json(['status'=>1,'msg'=>'success','data'=>[
						'pay_method' => 'qrcode',
						'qrcode_url' => $rs2['data']['qrcode_url'],
					]]);
				}
				return json(['status'=>0,'msg'=>$rs['msg'] ?? '支付宝支付创建失败']);

			} elseif($ali_pc_pay_type == 2){
				// 手机网站支付（alipay.trade.wap.pay）- 跳转支付宝H5收银台
				$rs = \app\common\Alipay::build_wap_pay($aid, 0, $mid, $title, $ordernum, $price, $tablename);
				if($rs['status'] == 1 && !empty($rs['data']['form_html'])){
					return json(['status'=>1,'msg'=>'success','data'=>[
						'pay_method' => 'form',
						'form_html' => $rs['data']['form_html'],
					]]);
				}
				return json(['status'=>0,'msg'=>$rs['msg'] ?? '支付宝支付创建失败']);

			} else {
				// 当面付预创建（alipay.trade.precreate）- 二维码扫码支付
				$rs = \app\common\Alipay::build_precreate($aid, 0, $mid, $title, $ordernum, $price, $tablename);
				if($rs['status'] == 1 && !empty($rs['data']['qrcode_url'])){
					return json(['status'=>1,'msg'=>'success','data'=>[
						'pay_method' => 'qrcode',
						'qrcode_url' => $rs['data']['qrcode_url'],
					]]);
				}
				return json(['status'=>0,'msg'=>$rs['msg'] ?? '支付宝支付创建失败']);
			}

		} else {
			return json(['status'=>0,'msg'=>'不支持的支付方式']);
		}
	}

	/**
	 * 支付宝同步回跳（PC端电脑网站支付完成后浏览器回跳）
	 * 不用于判断支付是否成功，仅引导用户回到网站
	 */
	public function alipay_return(){
		$out_trade_no = input('param.out_trade_no', '');
		$trade_no = input('param.trade_no', '');
		$total_amount = input('param.total_amount', '');

		// 查询订单状态
		$paid = false;
		if($out_trade_no){
			$trade_parts = explode('D', $out_trade_no);
			$payorder = Db::name('payorder')->where('ordernum', $trade_parts[0])->find();
			if($payorder && $payorder['status'] == 1){
				$paid = true;
			}
		}

		// 重定向到首页或充值页面，带支付结果参数
		$redirect_url = PRE_URL . '/?s=/index/recharge&pay_result=' . ($paid ? 'success' : 'pending');
		if($out_trade_no){
			$redirect_url .= '&ordernum=' . urlencode($trade_parts[0] ?? $out_trade_no);
		}
		header('Location: ' . $redirect_url);
		exit;
	}

	/**
	 * AJAX: 轮询支付状态
	 */
	public function check_pay_status(){
		if(!request()->isAjax()) return json(['status'=>0,'msg'=>'非法请求']);
		$mid = $this->_getLoginMid();
		if(!$mid) return json(['status'=>0,'msg'=>'请先登录']);

		$ordernum = input('param.ordernum', '');
		if(!$ordernum) return json(['status'=>0,'msg'=>'参数错误']);

		$payorder = Db::name('payorder')->where('ordernum', $ordernum)->where('mid', $mid)->find();
		if(!$payorder) return json(['status'=>0,'msg'=>'订单不存在']);

		return json(['status'=>1,'msg'=>'success','data'=>[
			'paid' => ($payorder['status'] == 1),
			'ordernum' => $ordernum,
		]]);
	}

	/**
	 * AJAX: 获取可用支付方式
	 */
	public function pay_config(){
		if(!request()->isAjax()) return json(['status'=>0,'msg'=>'非法请求']);
		$mid = $this->_getLoginMid();
		if(!$mid) return json(['status'=>0,'msg'=>'请先登录']);

		$defaultAid = 1;
		$platform = input('param.platform', 'h5');
		if(!in_array($platform, ['h5', 'pc'])) $platform = 'h5';
		$appinfo = \app\common\System::appinfo($defaultAid, $platform);

		$pay_types = [];
		// 检查微信支付
		if($platform === 'pc'){
			// PC端检查V3配置字段
			if(!empty($appinfo['wxpay']) && $appinfo['wxpay'] == 1 && !empty($appinfo['wxpay_mchid']) && !empty($appinfo['wxpay_mchkey_v3']) && !empty($appinfo['wxpay_apiclient_key']) && !empty($appinfo['wxpay_serial_no'])){
				// sign_type=1时额外检查公钥配置
				$wxpay_ok = true;
				if(isset($appinfo['sign_type']) && $appinfo['sign_type'] == 1){
					if(empty($appinfo['public_key_id']) || empty($appinfo['public_key_pem'])){
						$wxpay_ok = false;
					}
				}
				if($wxpay_ok){
					$pay_types[] = ['id'=>'wxpay','name'=>'微信支付','mode'=>'qrcode'];
				}
			}
		} else {
			if(!empty($appinfo['wxpay']) && $appinfo['wxpay'] == 1 && !empty($appinfo['wxpay_mchid']) && !empty($appinfo['wxpay_mchkey'])){
				$pay_types[] = ['id'=>'wxpay','name'=>'微信支付','mode'=>'jsapi'];
			}
		}
		// 检查支付宝
		if(!empty($appinfo['alipay']) && $appinfo['alipay'] == 1 && !empty($appinfo['ali_appid']) && !empty($appinfo['ali_privatekey']) && !empty($appinfo['ali_publickey'])){
			$ali_pc_pay_type = isset($appinfo['ali_pc_pay_type']) ? intval($appinfo['ali_pc_pay_type']) : 2;
			$ali_mode = ($ali_pc_pay_type == 0) ? 'qrcode' : 'form';
			$pay_types[] = ['id'=>'alipay','name'=>'支付宝支付','mode'=>$ali_mode];
		}

		return json(['status'=>1,'msg'=>'success','data'=>['pay_types'=>$pay_types]]);
	}

	// =================================================================
	// 云端存储空间管理
	// =================================================================

	/**
	 * 用户空间页面
	 */
	public function user_storage(){
		if($this->webinfo['showweb']!=3){
			header('Location:'.(string)url('Index/index')); die;
		}
		return View::fetch('index3/user_storage');
	}

	/**
	 * AJAX: 获取用户存储空间信息
	 */
	public function user_storage_info(){
		if(!request()->isAjax()) return json(['status'=>0,'msg'=>'非法请求']);
		$mid = $this->_getLoginMid();
		if(!$mid) return json(['status'=>0,'msg'=>'请先登录']);

		$defaultAid = 1;
		try {
			$service = new \app\service\StorageService();
			$info = $service->getUserStorageInfo($defaultAid, intval($mid));
			return json(['status'=>1,'msg'=>'success','data'=>$info]);
		} catch(\Exception $e) {
			return json(['status'=>0,'msg'=>'获取存储信息失败']);
		}
	}

	/**
	 * AJAX: 获取用户文件列表
	 */
	public function user_storage_files(){
		if(!request()->isAjax()) return json(['status'=>0,'msg'=>'非法请求']);
		$mid = $this->_getLoginMid();
		if(!$mid) return json(['status'=>0,'msg'=>'请先登录']);

		$defaultAid = 1;
		$filters = [
			'file_type' => input('param.file_type', 'all'),
			'source_type' => input('param.source_type', 'all'),
			'page' => input('param.page/d', 1),
			'limit' => input('param.limit/d', 20),
		];

		try {
			$service = new \app\service\StorageService();
			$result = $service->getUserStorageFiles($defaultAid, intval($mid), $filters);
			return json(['status'=>1,'msg'=>'success','data'=>$result]);
		} catch(\Exception $e) {
			return json(['status'=>0,'msg'=>'获取文件列表失败']);
		}
	}

	/**
	 * AJAX: 删除用户文件
	 */
	public function delete_storage_file(){
		if(!request()->isAjax() || !request()->isPost()) return json(['status'=>0,'msg'=>'非法请求']);
		$mid = $this->_getLoginMid();
		if(!$mid) return json(['status'=>0,'msg'=>'请先登录']);

		$fileIds = input('post.file_ids/a', []);
		if(empty($fileIds)){
			return json(['status'=>0,'msg'=>'请选择要删除的文件']);
		}

		$defaultAid = 1;
		try {
			$service = new \app\service\StorageService();
			$result = $service->deleteFiles($defaultAid, intval($mid), $fileIds);
			return json($result);
		} catch(\Exception $e) {
			return json(['status'=>0,'msg'=>'删除失败']);
		}
	}

	/**
	 * AJAX: 存储空间预检
	 */
	public function check_storage_quota(){
		if(!request()->isAjax() || !request()->isPost()) return json(['status'=>0,'msg'=>'非法请求']);
		$mid = $this->_getLoginMid();
		if(!$mid) return json(['status'=>0,'msg'=>'请先登录']);

		$requiredBytes = input('post.required_bytes/d', 0);
		$defaultAid = 1;
		try {
			$service = new \app\service\StorageService();
			$result = $service->checkQuota($defaultAid, intval($mid), $requiredBytes);
			return json(['status'=>1,'msg'=>'success','data'=>$result]);
		} catch(\Exception $e) {
			return json(['status'=>0,'msg'=>'配额检查失败']);
		}
	}

	/**
	 * 文件下载代理（解决第三方链接跨域下载问题）
	 */
	public function download_storage_file(){
		$mid = $this->_getLoginMid();
		if(!$mid){
			header('HTTP/1.1 403 Forbidden');
			echo '请先登录'; exit;
		}

		$fileId = input('param.file_id/d', 0);
		if(!$fileId){
			header('HTTP/1.1 400 Bad Request');
			echo '参数错误'; exit;
		}

		$defaultAid = 1;
		$file = \app\model\UserStorageFile::getById($fileId);
		if(!$file || $file['mid'] != $mid || $file['is_deleted']){
			header('HTTP/1.1 404 Not Found');
			echo '文件不存在'; exit;
		}

		$url = $file['file_url'];
		if(empty($url)){
			header('HTTP/1.1 404 Not Found');
			echo '文件地址为空'; exit;
		}

		// 检测文件名称和扩展名
		$ext = 'jpg';
		if($file['file_type'] === 'video') $ext = 'mp4';
		$urlPath = parse_url($url, PHP_URL_PATH);
		if($urlPath){
			$pathExt = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
			if(in_array($pathExt, ['jpg','jpeg','png','gif','webp','mp4','mov','avi','webm'])){
				$ext = $pathExt;
			}
		}
		$filename = ($file['file_type'] === 'video' ? 'video' : 'image') . '_' . $file['id'] . '.' . $ext;

		// 代理下载
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 5,
			CURLOPT_TIMEOUT => 120,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_USERAGENT => 'Mozilla/5.0',
		]);
		$content = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'application/octet-stream';
		curl_close($ch);

		if($httpCode != 200 || $content === false){
			header('HTTP/1.1 502 Bad Gateway');
			echo '文件下载失败，链接可能已过期'; exit;
		}

		header('Content-Type: ' . $contentType);
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Length: ' . strlen($content));
		header('Cache-Control: no-cache');
		echo $content;
		exit;
	}

	/**
	 * AJAX: 获取PC端登录配置（公众号关注引导）
	 */
	public function pc_login_config(){
		if(!request()->isAjax()){
			return json(['status'=>0,'msg'=>'非法请求']);
		}
		$defaultAid = 1;
		$info = Db::name('admin_setapp_pc')->where('aid', $defaultAid)->find();
		$data = [
			'require_follow' => 0,
			'follow_guide_text' => '扫码关注公众号后即可登录',
			'follow_appname' => '',
			'new_user_follow_guide' => 0
		];
		if($info){
			$data['require_follow'] = intval($info['require_follow'] ?? 0);
			$data['follow_guide_text'] = $info['follow_guide_text'] ?: '扫码关注公众号后即可登录';
			$data['follow_appname'] = $info['follow_appname'] ?? '';
			$data['new_user_follow_guide'] = intval($info['new_user_follow_guide'] ?? 0);
		}
		// 检查公众号是否已配置（必须有公众号才能使用扫码登录）
		$mpappinfo = Db::name('admin_setapp_mp')->where('aid', $defaultAid)->find();
		$data['mp_configured'] = ($mpappinfo && !empty($mpappinfo['appid'])) ? 1 : 0;
		return json(['status'=>1,'data'=>$data]);
	}

	/**
	 * AJAX: 创建微信扫码登录票据（生成带参数临时二维码）
	 */
	public function create_qr_login_ticket(){
		if(!request()->isAjax()){
			return json(['status'=>0,'msg'=>'非法请求']);
		}
		$defaultAid = 1;

		// 检查是否开启了扫码登录
		$pcInfo = Db::name('admin_setapp_pc')->where('aid', $defaultAid)->find();
		if(!$pcInfo || !$pcInfo['require_follow']){
			return json(['status'=>0,'msg'=>'扫码登录未开启']);
		}

		// 获取公众号access_token
		$accessToken = \app\common\Wechat::access_token($defaultAid, 'mp');
		if(!$accessToken){
			return json(['status'=>0,'msg'=>'公众号未配置或access_token获取失败']);
		}

		// 生成唯一场景值
		$sceneStr = 'pclogin_' . bin2hex(random_bytes(16));
		$expireSeconds = 300; // 5分钟过期

		// 调用微信API创建临时带参数二维码
		$url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $accessToken;
		$postData = json_encode([
			'expire_seconds' => $expireSeconds,
			'action_name' => 'QR_STR_SCENE',
			'action_info' => [
				'scene' => ['scene_str' => $sceneStr]
			]
		], JSON_UNESCAPED_UNICODE);

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $postData,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
		]);
		$result = curl_exec($ch);
		curl_close($ch);

		$res = json_decode($result, true);
		if(!$res || !isset($res['ticket'])){
			$errMsg = isset($res['errmsg']) ? $res['errmsg'] : '未知错误';
			return json(['status'=>0,'msg'=>'生成二维码失败: '.$errMsg]);
		}

		// 将场景值存入缓存，状态为pending
		cache($sceneStr, ['status' => 'pending', 'openid' => '', 'mid' => 0, 'create_time' => time()], $expireSeconds + 60);

		// 返回二维码图片URL和场景值
		$qrUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($res['ticket']);
		return json(['status'=>1,'data'=>[
			'scene_str' => $sceneStr,
			'qr_url' => $qrUrl,
			'expire_seconds' => $expireSeconds
		]]);
	}

	/**
	 * AJAX: 查询扫码登录状态
	 */
	public function check_qr_login_status(){
		if(!request()->isAjax()){
			return json(['status'=>0,'msg'=>'非法请求']);
		}
		$sceneStr = trim(input('get.scene_str'));
		if(!$sceneStr || strpos($sceneStr, 'pclogin_') !== 0){
			return json(['status'=>0,'msg'=>'无效的场景值']);
		}

		$data = cache($sceneStr);
		if(!$data){
			return json(['status'=>0,'msg'=>'二维码已过期','data'=>['expired'=>true]]);
		}

		if($data['status'] === 'confirmed' && $data['mid'] > 0){
			// 登录成功，建立session
			$mid = $data['mid'];
			$sessionId = \think\facade\Session::getId();

			// 缓存会员session映射（7天）
			cache($sessionId . '_mid', $mid, 7*86400);

			// 写入session表
			Db::name('session')->where('session_id', $sessionId)->delete();
			Db::name('session')->insert([
				'session_id' => $sessionId,
				'aid' => 1,
				'mid' => $mid,
				'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
				'login_time' => time(),
				'login_ip' => request()->ip(),
				'platform' => 'h5'
			]);

			// 获取会员信息返回
			$memberInfo = Db::name('member')->where('id', $mid)->field('id,nickname,headimg,tel')->find();

			// 清理场景缓存
			cache($sceneStr, null);

			return json(['status'=>1,'data'=>[
				'login_status' => 'success',
				'mid' => $memberInfo['id'],
				'nickname' => $memberInfo['nickname'] ?? '',
				'headimg' => $memberInfo['headimg'] ?? '',
				'tel' => $memberInfo['tel'] ? substr($memberInfo['tel'],0,3).'****'.substr($memberInfo['tel'],-4) : ''
			]]);
		}

		return json(['status'=>1,'data'=>['login_status'=>'pending']]);
	}

	/**
	 * AJAX: 获取模板生成图片列表（幻灯片弹窗用）
	 */
	public function template_images(){
		if(!request()->isAjax()){
			return json(['status'=>0,'msg'=>'非法请求']);
		}
		$templateId = input('param.template_id/d', 0);
		$limit = input('param.limit/d', 50);
		if($limit > 100) $limit = 100;
		if($limit < 1) $limit = 1;
		if(!$templateId){
			return json(['status'=>0,'msg'=>'缺少模板ID']);
		}

		$template = Db::name('generation_scene_template')
			->where('id', $templateId)
			->where('status', 1)
			->field('id, template_name, cover_image, source_record_id')
			->find();
		if(!$template){
			return json(['status'=>0,'msg'=>'模板不存在或已下架']);
		}

		$images = [];

		// 1. 查询该模板关联的所有成功生成记录的图片输出
		$recordIds = Db::name('generation_record')
			->where('scene_id', $templateId)
			->where('status', 2) // STATUS_SUCCESS
			->order('create_time desc')
			->limit($limit)
			->column('id');

		if(!empty($recordIds)){
			$outputs = Db::name('generation_output')
				->whereIn('record_id', $recordIds)
				->where('output_type', 'image')
				->field('output_url, thumbnail_url, width, height')
				->order('create_time desc')
				->limit($limit)
				->select()
				->toArray();
			foreach($outputs as $out){
				if(!empty($out['output_url'])){
					$rawUrl = $out['output_url'];
					$rawThumb = $out['thumbnail_url'] ?: $rawUrl;
					$images[] = [
						'output_url' => $this->_toWebpUrl($rawUrl, 'main'),
						'thumbnail_url' => $this->_toWebpUrl($rawThumb, 'thumb'),
						'width' => intval($out['width']),
						'height' => intval($out['height'])
					];
				}
			}
		}

		// 2. 如果没有图片，尝试从 source_record_id 获取
		if(empty($images) && !empty($template['source_record_id'])){
			$srcOutputs = Db::name('generation_output')
				->where('record_id', $template['source_record_id'])
				->where('output_type', 'image')
				->field('output_url, thumbnail_url, width, height')
				->order('sort asc')
				->limit($limit)
				->select()
				->toArray();
			foreach($srcOutputs as $out){
				if(!empty($out['output_url'])){
					$rawUrl = $out['output_url'];
					$rawThumb = $out['thumbnail_url'] ?: $rawUrl;
					$images[] = [
						'output_url' => $this->_toWebpUrl($rawUrl, 'main'),
						'thumbnail_url' => $this->_toWebpUrl($rawThumb, 'thumb'),
						'width' => intval($out['width']),
						'height' => intval($out['height'])
					];
				}
			}
		}

		// 3. 兜底：使用模板自身的 cover_image
		if(empty($images) && !empty($template['cover_image'])){
			$coverUrl = $template['cover_image'];
			$images[] = [
				'output_url' => $this->_toWebpUrl($coverUrl, 'main'),
				'thumbnail_url' => $this->_toWebpUrl($coverUrl, 'thumb'),
				'width' => 0,
				'height' => 0
			];
		}

		return json([
			'status' => 1,
			'msg' => '获取成功',
			'data' => [
				'template_name' => $template['template_name'],
				'images' => $images,
				'total' => count($images)
			]
		]);
	}

	/**
	 * 辅助：将图片URL转为压缩后的WebP格式
	 * 通过云存储数据万象/图片处理参数实现，已是webp格式的不再追加
	 *
	 * @param string $url 原始URL
	 * @param string $mode 'main'主图(quality=85) | 'thumb'缩略图(thumbnail/200x + quality=75)
	 * @return string 处理后的URL
	 */
	private function _toWebpUrl($url, $mode = 'main'){
		if(empty($url)) return $url;

		// 已经是webp格式的图片，无需格式转换，但缩略图模式仍需压缩尺寸
		$ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
		$isWebp = ($ext === 'webp');

		// 已包含处理参数的不再追加
		if(strpos($url, 'imageMogr2') !== false || strpos($url, 'x-oss-process') !== false){
			return $url;
		}

		$sep = (strpos($url, '?') !== false) ? '&' : '?';

		// 腾讯云COS（数据万象 imageMogr2）
		if(strpos($url, 'myqcloud.com') !== false){
			if($mode === 'thumb'){
				// 缩略图：限宽200px + webp + quality/75
				$params = 'imageMogr2/thumbnail/200x';
				if(!$isWebp) $params .= '/format/webp';
				$params .= '/quality/75';
				return $url . $sep . $params;
			} else {
				// 主图：限宽1200px + webp + quality/85
				$params = 'imageMogr2/thumbnail/1200x';
				if(!$isWebp) $params .= '/format/webp';
				$params .= '/quality/85';
				return $url . $sep . $params;
			}
		}

		// 阿里云OSS（图片处理）
		$remoteset = Db::name('sysset')->where('name', 'remote')->value('value');
		$remoteset = json_decode($remoteset, true);
		$isAliOss = (intval($remoteset['type'] ?? 0) == 2 && !empty($remoteset['alioss']['url'])
			&& strpos($url, $remoteset['alioss']['url']) === 0);

		if($isAliOss){
			if($mode === 'thumb'){
				$proc = 'x-oss-process=image/resize,w_200';
				if(!$isWebp) $proc .= '/format,webp';
				$proc .= '/quality,q_75';
				return $url . $sep . $proc;
			} else {
				$proc = 'x-oss-process=image/resize,w_1200';
				if(!$isWebp) $proc .= '/format,webp';
				$proc .= '/quality,q_85';
				return $url . $sep . $proc;
			}
		}

		// 其他存储（无法服务端转换，直接返回）
		return $url;
	}
}
