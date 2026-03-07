<?php

namespace app\controller;

use app\BaseController;
use app\model\ApiCallLog;
use app\model\ApiConfig;
use think\facade\View;

/**
 * API统计监控控制器
 * Class ApiStatistics
 * @package app\controller
 */
class ApiStatistics extends BaseController
{
    protected $aid;
    
    public function initialize()
    {
        parent::initialize();
        $this->aid = input('param.aid/d', 0);
        define('aid', $this->aid);
    }
    
    /**
     * 统计首页
     */
    public function index()
    {
        return View::fetch('api_config/statistics');
    }
    
    /**
     * 概览数据
     */
    public function overview()
    {
        $startTime = input('param.start_time', date('Y-m-d 00:00:00'));
        $endTime = input('param.end_time', date('Y-m-d 23:59:59'));
        
        $params = [
            'start_time' => strtotime($startTime),
            'end_time' => strtotime($endTime)
        ];
        
        if($this->aid > 0){
            $params['caller_aid'] = $this->aid;
        }
        
        $stats = ApiCallLog::getStatistics($params);
        
        return json([
            'code' => 0,
            'msg' => '获取成功',
            'data' => $stats
        ]);
    }
    
    /**
     * 趋势分析
     */
    public function trend()
    {
        $days = input('param.days/d', 7); // 默认7天
        $apiConfigId = input('param.api_config_id/d', 0);
        
        $data = [];
        for($i = $days - 1; $i >= 0; $i--){
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $startTime = strtotime($date . ' 00:00:00');
            $endTime = strtotime($date . ' 23:59:59');
            
            $where = [
                ['call_time', '>=', $startTime],
                ['call_time', '<=', $endTime]
            ];
            
            if($apiConfigId > 0){
                $where[] = ['api_config_id', '=', $apiConfigId];
            }
            
            if($this->aid > 0){
                $where[] = ['caller_aid', '=', $this->aid];
            }
            
            $totalCalls = ApiCallLog::where($where)->count();
            $successCalls = ApiCallLog::where($where)->where('is_success', 1)->count();
            $totalAmount = ApiCallLog::where($where)->where('is_success', 1)->sum('charge_amount');
            
            $data[] = [
                'date' => $date,
                'total_calls' => $totalCalls,
                'success_calls' => $successCalls,
                'success_rate' => $totalCalls > 0 ? round($successCalls / $totalCalls * 100, 2) : 0,
                'total_amount' => round($totalAmount, 2)
            ];
        }
        
        return json([
            'code' => 0,
            'msg' => '获取成功',
            'data' => $data
        ]);
    }
    
    /**
     * 热门API排行
     */
    public function topApis()
    {
        $limit = input('param.limit/d', 10);
        $startTime = input('param.start_time', date('Y-m-d 00:00:00', strtotime('-7 days')));
        $endTime = input('param.end_time', date('Y-m-d 23:59:59'));
        
        $topApis = ApiCallLog::getTopApis(
            $limit,
            strtotime($startTime),
            strtotime($endTime)
        );
        
        return json([
            'code' => 0,
            'msg' => '获取成功',
            'data' => $topApis
        ]);
    }
    
    /**
     * 用户使用统计
     */
    public function userStats()
    {
        $uid = input('param.uid/d', 0);
        $startTime = input('param.start_time', date('Y-m-d 00:00:00', strtotime('-30 days')));
        $endTime = input('param.end_time', date('Y-m-d 23:59:59'));
        
        if($uid == 0){
            return json([
                'code' => 400,
                'msg' => '用户ID不能为空'
            ]);
        }
        
        $stats = ApiCallLog::getStatistics([
            'caller_uid' => $uid,
            'start_time' => strtotime($startTime),
            'end_time' => strtotime($endTime)
        ]);
        
        return json([
            'code' => 0,
            'msg' => '获取成功',
            'data' => $stats
        ]);
    }
}
