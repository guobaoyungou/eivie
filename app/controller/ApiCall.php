<?php

namespace app\controller;

use app\BaseController;
use app\service\ApiCallService;
use app\model\ApiCallLog;
use think\facade\View;

/**
 * API调用控制器
 * Class ApiCall
 * @package app\controller
 */
class ApiCall extends BaseController
{
    protected $aid;
    protected $bid;
    protected $mdid;
    protected $uid;
    
    public function initialize()
    {
        parent::initialize();
        $this->aid = input('param.aid/d', 0);
        $this->bid = input('param.bid/d', 0);
        $this->mdid = input('param.mdid/d', 0);
        $this->uid = session('uid') ?? 1;
        
        define('aid', $this->aid);
    }
    
    /**
     * 调用API接口
     */
    public function call()
    {
        $apiCode = input('param.api_code', '');
        $params = input('post.', []);
        
        if(empty($apiCode)){
            return json([
                'code' => 400,
                'msg' => 'API代码不能为空'
            ]);
        }
        
        $service = new ApiCallService();
        $result = $service->call(
            $apiCode,
            $params,
            $this->aid,
            $this->bid,
            $this->mdid,
            $this->uid
        );
        
        return json($result);
    }
    
    /**
     * 调用日志列表
     */
    public function logs()
    {
        if(request()->isAjax()){
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 15);
            
            $where = [];
            
            $apiConfigId = input('param.api_config_id/d', 0);
            if($apiConfigId > 0){
                $where[] = ['api_config_id', '=', $apiConfigId];
            }
            
            $callerUid = input('param.caller_uid/d', 0);
            if($callerUid > 0){
                $where[] = ['caller_uid', '=', $callerUid];
            }
            
            $isSuccess = input('param.is_success', '');
            if($isSuccess !== ''){
                $where[] = ['is_success', '=', $isSuccess];
            }
            
            $startTime = input('param.start_time', '');
            $endTime = input('param.end_time', '');
            if($startTime && $endTime){
                $where[] = ['call_time', 'between', [strtotime($startTime), strtotime($endTime)]];
            }
            
            $logs = ApiCallLog::with(['apiConfig'])
                ->where($where)
                ->order('call_time', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);
            
            return json([
                'code' => 0,
                'msg' => '获取成功',
                'count' => $logs->total(),
                'data' => $logs->items()
            ]);
        }
        
        return View::fetch('api_config/logs');
    }
    
    /**
     * 日志详情
     */
    public function logDetail()
    {
        $id = input('param.id/d', 0);
        
        $log = ApiCallLog::with(['apiConfig', 'caller'])->find($id);
        
        if(!$log){
            return json([
                'code' => 404,
                'msg' => '日志不存在'
            ]);
        }
        
        return json([
            'code' => 0,
            'msg' => '获取成功',
            'data' => $log
        ]);
    }
}
