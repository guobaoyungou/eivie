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
// | 商家列表    
// +----------------------------------------------------------------------
//管理员中心 - 商家列表
namespace app\controller;
use think\facade\Db;
class ApiAdminBusiness extends ApiAdmin
{	
	public function initialize(){
		parent::initialize();
//        if(!in_array(request()->action(),['searchCode','detail','decscore'])){
//            if(bid != 0) die(json_encode(['status'=>-4,'msg'=>'无权限操作']));
//        }
        if(!$this->auth_data['user_multi_business']){
            die(json_encode(['status'=>-4,'msg'=>'无权限操作']));
        }
	}
	public function index(){
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		if(input('param.keyword')){
			$where[] = ['id|name|tel','like','%'.input('param.keyword').'%'];
		}
		$datalist = Db::name('business')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if($datalist){
			foreach($datalist as &$v){
                if($v['sales_quota']>0){
                    $v['syquota'] = $v['sales_quota']-$v['total_sales_quota'];
                }else{
                    $v['syquota'] = '无限制';
                }
			}
			unset($v);
		}else{
			$datalist = [];
		}
		if($pagenum == 1){
			$count = Db::name('business')->where($where)->count();
		}
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['count'] = $count;
		$rdata['datalist'] = $datalist;
		$rdata['auth_data'] = $this->auth_data;
		return $this->json($rdata);
	}

	public function setquota(){
	    }
	
	public function getUserBusinessList(){
	    }
    //选择进入的商家
    public function selectBusiness(){
        }
    //删除商户
    public function deleteBusiness(){
        }
    public function editBusiness(){
        }
}