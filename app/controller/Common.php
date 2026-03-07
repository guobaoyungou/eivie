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
// | 公共 验证权限
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class Common extends Base
{
	public $aid;
    public $bid;
	public $uid;
	public $user;
    public $admin;
    public $sysset_webinfo;
	public $mdid = 0;
	public $auth_data = 'all';
	public $platform = 'mp';
	public $xcxaid = 0;
	public $score_weishu = 0;
    public $adminSet;
    public $is_fuwu = 0;
    public function initialize(){
		parent::initialize();
		$request = request();
        $controller = $request->controller();
        if(strpos($controller,'.') !== false){
            $controllerArr = explode('.',$controller);
            $controller = $controllerArr[1];
        }
//        $file = ROOT_PATH.'runtime/log/aaa.txt';
//        file_put_contents($file,$_POST['apifrom']);
        //如果是vueapi的请求，则不执行header跳转，直接返回状态给接口前端
		if(input('param.apifrom')=='vue'){
            $url =  (string)url($controller.'Login/index');
            //测试 end
            if(!session("?ADMIN_LOGIN")){
                echojson(['status'=>-5,'msg'=>'请重新登录','url' => $url]);die();
            }
        }else{
            if(!in_array($controller,array('login')) && !session("?ADMIN_LOGIN")){
				$logincodeurl = '';
				header('Location:'.(string)url('login/index'.$logincodeurl));die;
            }
        }
		$this->aid = session('ADMIN_AID');
		if(MN == 'business'){
			// 优先使用平台管理员进入商户时的session，如果没有则使用商户直接登录的session
			$this->uid = session('ADMIN_AUTH_UID') ?: session('ADMIN_UID');
			$this->bid = session('ADMIN_AUTH_BID') ?: session('ADMIN_BID');
		}else{
			$this->uid = session('ADMIN_UID');
			$this->bid = session('ADMIN_BID');
		}
		
		if(false){}else{
			define('aid',$this->aid);
			define('bid',$this->bid);
			define('uid',$this->uid);
		}
		$user = Db::name('admin_user')->where('id',$this->uid)->find();
		if($user['groupid']){
			$group = Db::name('admin_user_group')->where('id',$user['groupid'])->find();
			$user['auth_data'] = $group['auth_data'];
			$user['wxauth_data'] = $group['wxauth_data'];
			$user['notice_auth_data'] = $group['notice_auth_data'];
			$user['hexiao_auth_data'] = $group['hexiao_auth_data'];
			$user['mdid'] = $group['mdid'];
			$user['showtj'] = $group['showtj'];
		}
		if($user['bid'] > 0 && $user['auth_type'] == 1){
			$adminuser = Db::name('admin_user')->where('aid',$this->aid)->where('isadmin','>',0)->find();
			$user['auth_type'] = $adminuser['auth_type'];
			$user['auth_data'] = $adminuser['auth_data'];
		}
		$this->user = $user;
		$this->mdid = $user['mdid'];
        $this->sysset_webinfo = \app\common\Common::getSysset();
		if($user['auth_type']==0){
			$auth_data = json_decode($user['auth_data'],true);
			$auth_path = \app\common\Menu::blacklist();
			foreach($auth_data as $v){
				$auth_path = array_merge($auth_path,explode(',',$v));
			}
			$thispath = $controller .'/'.$request->action();
			
			// 添加权限调试日志
			\think\facade\Log::info('权限校验', [
				'controller' => $controller,
				'action' => $request->action(),
				'thispath' => $thispath,
				'has_controller_wildcard' => in_array($controller.'/*',$auth_path),
				'has_specific_path' => in_array($thispath,$auth_path),
				'has_bst_session' => session('BST_ID') ? 'yes' : 'no',
				'auth_path_sample' => array_slice($auth_path, 0, 10)
			]);
			
            if(!in_array($controller.'/*',$auth_path) && !in_array($thispath,$auth_path) && !session('BST_ID')){
                \think\facade\Log::error('访问被拒绝', [
                    'controller' => $controller,
                    'action' => $request->action(),
                    'thispath' => $thispath,
                    'uid' => $this->uid,
                    'bid' => $this->bid
                ]);
                
                if(input('param.apifrom')=='vue'){
                    echojson(['status'=>0,'msg'=>'无访问权限']);die();
                }else{
                    die('无访问权限');
                }
            
            }
			
			$this->auth_data = $auth_path;
		}else{
			$this->auth_data = 'all';
		}
        $this->is_fuwu = 0;
		View::assign('aid',$this->aid);
		View::assign('bid',$this->bid);
		View::assign('auth_data',$this->auth_data);
        View::assign('sysset_webinfo',$this->sysset_webinfo);
        View::assign('is_fuwu',$this->is_fuwu);

		$platform = \app\common\Common::getplatform(aid);
		$this->platform = $platform;
		View::assign('platform',$platform);

		$admin = Db::name('admin')->where('id',aid)->find();
        //禁用权限判断
        $this->admin = $admin;
		if($admin['domain']){
			define('PRE_URL2','https://'.$admin['domain']);//前端独立域名
		}else{
			define('PRE_URL2',PRE_URL);
		}
        $this->adminSet = Db::name('admin_set')->where('aid',aid)->find();
        //导出excel的表头配置
        $all_excel_set = \think\facade\Config::get('excel_field');;
        $excel_key = request()->controller().'/'.request()->action();
        $excel_set = $all_excel_set[$excel_key]??'';
        $excel_title = $excel_set?jsonEncode($excel_set['title']):jsonEncode([]);
        $excel_field = $excel_set?jsonEncode($excel_set['field']):jsonEncode([]);
        $excel_name = $excel_set?$excel_set['name']:'';
        View::assign('excel_title',$excel_title);
        View::assign('excel_field',$excel_field);
        View::assign('excel_name',$excel_name);
    }

    /**
     * @param array $titleArr
     * @param array $dataArr
     * @param array $typeArr 类型数组
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
	function export_excel(array $titleArr,array $dataArr,array $typeArr=[]){
		$phpexcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();//实例化
		$phpsheet = $phpexcel->getActiveSheet();
		//填充表头信息
		$widthArr = [];
		for($i = 0;$i < count($titleArr);$i++) {
			$phpsheet->setCellValue(IntToChr($i)."1","$titleArr[$i]");
			$widthArr[] = strlen($titleArr[$i]);
		}
		//填充表格信息
		for ($i = 2;$i <= count($dataArr) + 1;$i++) {
			$j = 0;
			foreach ($dataArr[$i-2] as $key=>$value) {
			    if($typeArr && $typeArr[$j] == 1)//数字
                    $phpsheet->setCellValue(IntToChr($j)."$i","$value");
			    else
				    $phpsheet->setCellValueExplicit(IntToChr($j)."$i","$value",\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				if(strlen($value) > $widthArr[$j]){
					$widthArr[$j] = strlen($value);
					if($widthArr[$j] > 25) $widthArr[$j] = 25;
				}
				$j++;
			}
		}
		//设置列宽
		foreach($widthArr as $k=>$v){
			$phpsheet->getColumnDimension(IntToChr($k))->setWidth($v+2);
		}
		$phpwriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($phpexcel,"Xlsx");
		header('Content-Disposition: attachment;filename="'.date('YmdHis').'.xlsx"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		$phpwriter->save("php://output");
        die();
	}

    /**
     * @param $file
     * @param $startrow 数据开始行，如第一行为表头则从第二行开始
     * @param $endrow 数据结束行，数据量大时分页处理
     * @param $uploadoss 是否上传oss（未开启oss不会上传）
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
	function import_excel($file='', $startrow=2, $endrow='all', $uploadoss = true){
		if(strpos(PRE_URL,$file)==0){
			$file = ROOT_PATH.ltrim(str_replace(PRE_URL,'',$file),'/');
		}
		$file = iconv("utf-8", "gb2312", $file);   //转码
		if(empty($file) OR !file_exists($file)) {
			echojson(['status'=>0,'msg'=>'file not exists!']);
		}
        $fileType  = 'Xlsx';
		$objRead = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');   //建立reader对象
		if(!$objRead->canRead($file)){
            $fileType  = 'Xls';
			$objRead = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
			if(!$objRead->canRead($file)){
				echojson(['status'=>0,'msg'=>'No Excel!']);
			}
		}
		$PHPExcel = $objRead->load($file);
		$currentSheet = $PHPExcel->getSheet(0);  //读取excel文件中的第一个工作表
		$allColumn = $currentSheet->getHighestColumn(); //取得最大的列号
        $allColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($allColumn);
        $allRow = $currentSheet->getHighestDataRow(); //取得一共有多少行
        if($endrow == 'all') {
            $endrow = $allRow;
            $pageMode = false;
        } else {
            $pageMode = true;
        }
		$exceldata = array();
		/**从第二行开始输出，因为excel表中第一行为列名*/
		for($currentRow = $startrow;$currentRow <= $endrow;$currentRow++){
			/**从第A列开始输出*/
			$erp_orders_id = array();  //声明数组
			for($currentColumn= 1;$currentColumn<= $allColumn; $currentColumn++){
				$val = $currentSheet->getCellByColumnAndRow($currentColumn,$currentRow)->getValue();/**ord()将字符转为十进制数*/
				$erp_orders_id[] = $val;
			}
			$exceldata[] = $erp_orders_id;
		}
        if($fileType == 'Xlsx'){
            //图片本地存储的路径
            $imageFilePath = "upload/".$this->aid."/" . date('Ym') . "/";
            if (!file_exists($imageFilePath)) { // 如果目录不存在则递归创建
                mkdir($imageFilePath, 0777, true);
            }

            //处理图片 PhpOffice\PhpSpreadsheet\Worksheet\Drawing类的实例;   仅支持xlsx格式文件
            foreach($currentSheet->getDrawingCollection() as $k=> $img) {
                $coordinates = $img->getCoordinates(); // 获取图片的单元格坐标
                // 5. 解析坐标的行号（将 'B3' 中的数字部分提取为行号）
                $rowIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($coordinates)[1]; // 返回整数行号
                $row = (int)$rowIndex; // 将行号转换为整数

                if ($row >= $startrow && $row <= $endrow) {
//                    dump($img->getPath());
//                    dump($row);
                    //校验文件大小超出超过限制
                    list($startColumn,$startRow) = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($img->getCoordinates());  //获取图片所在行和列
                    $startColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startColumn);
                    $filePath = $imageFilePath . date('d_His') . rand(100000, 999999) . '.' . $img->getExtension();
                    switch($img->getExtension()) {
                        case 'jpg':
                        case 'jpeg':
                            imagejpeg(imagecreatefromjpeg($img->getPath()),$filePath);
                            break;
                        case 'gif':
                            imagegif(imagecreatefromgif($img->getPath()),$filePath);
                            break;
                        case 'png':
                            imagepng(imagecreatefrompng($img->getPath()),$filePath);
                            break;
                        default:
                            echojson(['status'=>0,'msg'=>$startColumn.'行存在未知格式图片']);
                    }

//                    \think\facade\Log::write(__FILE__.__LINE__);
//                    \think\facade\Log::write($filePath);
                    if($uploadoss) $filePathNew = \app\common\Pic::uploadoss($filePath, true);
                    $exceldata[$startRow - $startrow][$startColumnIndex-1] = PRE_URL . '/' . $filePath;
                }
            }
        }
        if ($pageMode) {
            return ['data'=>$exceldata,'rows'=>$allRow];
        } else {
            return $exceldata;
        }   
	}

	public function inputlockpwd(){
		$lockpwd = input('param.lockpwd');
		$realpwd = $this->adminSet['locking_pwd'];
		if($lockpwd != $realpwd){
			session('isunlock',0);
			return json(['status'=>0,'msg'=>'密码错误']);
		}else{
			session('isunlock',1);
			return json(['status'=>1,'msg'=>'操作成功']);
		}
	}
   
}
