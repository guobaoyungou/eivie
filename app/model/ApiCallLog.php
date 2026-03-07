<?php

namespace app\model;

use think\Model;

/**
 * API调用日志模型
 * Class ApiCallLog
 * @package app\model
 */
class ApiCallLog extends Model
{
    // 设置表名
    protected $name = 'api_call_log';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动写入时间戳字段（仅创建时间）
    protected $autoWriteTimestamp = false;
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'api_config_id' => 'integer',
        'caller_aid' => 'integer',
        'caller_bid' => 'integer',
        'caller_mdid' => 'integer',
        'caller_uid' => 'integer',
        'status_code' => 'integer',
        'is_success' => 'integer',
        'consumed_units' => 'float',
        'charge_amount' => 'float',
        'balance_before' => 'float',
        'balance_after' => 'float',
        'response_time' => 'integer',
        'call_time' => 'integer',
    ];
    
    // JSON字段
    protected $json = ['request_params', 'response_data'];
    protected $jsonAssoc = true;
    
    /**
     * 关联API配置
     */
    public function apiConfig()
    {
        return $this->hasOne(ApiConfig::class, 'id', 'api_config_id');
    }
    
    /**
     * 关联调用用户
     */
    public function caller()
    {
        return $this->hasOne(\app\model\Member::class, 'id', 'caller_uid');
    }
    
    /**
     * 获取成功状态文本
     */
    public function getIsSuccessTextAttr($value, $data)
    {
        $status = [0 => '失败', 1 => '成功'];
        return $status[$data['is_success']] ?? '未知';
    }
    
    /**
     * 获取调用时间文本
     */
    public function getCallTimeTextAttr($value, $data)
    {
        return date('Y-m-d H:i:s', $data['call_time']);
    }
    
    /**
     * 获取响应时间文本
     */
    public function getResponseTimeTextAttr($value, $data)
    {
        $time = $data['response_time'];
        if ($time < 1000) {
            return $time . 'ms';
        }
        return round($time / 1000, 2) . 's';
    }
    
    /**
     * 搜索器：API配置ID
     */
    public function searchApiConfigIdAttr($query, $value)
    {
        if ($value) {
            $query->where('api_config_id', $value);
        }
    }
    
    /**
     * 搜索器：调用者用户ID
     */
    public function searchCallerUidAttr($query, $value)
    {
        if ($value) {
            $query->where('caller_uid', $value);
        }
    }
    
    /**
     * 搜索器：调用者平台ID
     */
    public function searchCallerAidAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('caller_aid', $value);
        }
    }
    
    /**
     * 搜索器：调用者商家ID
     */
    public function searchCallerBidAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('caller_bid', $value);
        }
    }
    
    /**
     * 搜索器：调用者门店ID
     */
    public function searchCallerMdidAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('caller_mdid', $value);
        }
    }
    
    /**
     * 搜索器：成功状态
     */
    public function searchIsSuccessAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('is_success', $value);
        }
    }
    
    /**
     * 搜索器：请求ID
     */
    public function searchRequestIdAttr($query, $value)
    {
        if ($value) {
            $query->where('request_id', $value);
        }
    }
    
    /**
     * 搜索器：调用时间范围
     */
    public function searchCallTimeRangeAttr($query, $value)
    {
        if ($value && is_array($value) && count($value) == 2) {
            $query->whereBetweenTime('call_time', $value[0], $value[1]);
        }
    }
    
    /**
     * 记录API调用日志
     */
    public static function recordLog($data)
    {
        $log = new self();
        $log->api_config_id = $data['api_config_id'] ?? 0;
        $log->caller_aid = $data['caller_aid'] ?? 0;
        $log->caller_bid = $data['caller_bid'] ?? 0;
        $log->caller_mdid = $data['caller_mdid'] ?? 0;
        $log->caller_uid = $data['caller_uid'] ?? 0;
        $log->request_id = $data['request_id'] ?? '';
        $log->request_params = $data['request_params'] ?? [];
        $log->response_data = $data['response_data'] ?? [];
        $log->status_code = $data['status_code'] ?? 0;
        $log->is_success = $data['is_success'] ?? 0;
        $log->error_message = $data['error_message'] ?? '';
        $log->consumed_units = $data['consumed_units'] ?? 0;
        $log->charge_amount = $data['charge_amount'] ?? 0;
        $log->balance_before = $data['balance_before'] ?? 0;
        $log->balance_after = $data['balance_after'] ?? 0;
        $log->response_time = $data['response_time'] ?? 0;
        $log->ip_address = $data['ip_address'] ?? '';
        $log->call_time = $data['call_time'] ?? time();
        
        try {
            $log->save();
            return $log->id;
        } catch (\Exception $e) {
            // 日志记录失败不影响主流程
            trace('API调用日志记录失败: ' . $e->getMessage(), 'error');
            return 0;
        }
    }
    
    /**
     * 获取调用统计
     */
    public static function getStatistics($params = [])
    {
        $query = self::field([
            'COUNT(*) as total_calls',
            'SUM(CASE WHEN is_success=1 THEN 1 ELSE 0 END) as success_calls',
            'SUM(CASE WHEN is_success=0 THEN 1 ELSE 0 END) as failed_calls',
            'SUM(charge_amount) as total_amount',
            'AVG(response_time) as avg_response_time',
            'SUM(consumed_units) as total_units'
        ]);
        
        // 应用筛选条件
        if (isset($params['api_config_id'])) {
            $query->where('api_config_id', $params['api_config_id']);
        }
        
        if (isset($params['caller_uid'])) {
            $query->where('caller_uid', $params['caller_uid']);
        }
        
        if (isset($params['caller_aid'])) {
            $query->where('caller_aid', $params['caller_aid']);
        }
        
        if (isset($params['start_time'])) {
            $query->where('call_time', '>=', $params['start_time']);
        }
        
        if (isset($params['end_time'])) {
            $query->where('call_time', '<=', $params['end_time']);
        }
        
        $result = $query->find();
        
        if ($result) {
            // 计算成功率
            $result['success_rate'] = $result['total_calls'] > 0 
                ? round(($result['success_calls'] / $result['total_calls']) * 100, 2) 
                : 0;
                
            // 格式化响应时间
            $result['avg_response_time'] = round($result['avg_response_time'], 2);
            
            // 格式化金额
            $result['total_amount'] = round($result['total_amount'], 2);
        }
        
        return $result;
    }
    
    /**
     * 获取热门API排行
     */
    public static function getTopApis($limit = 10, $startTime = null, $endTime = null)
    {
        $query = self::field([
            'api_config_id',
            'COUNT(*) as call_count',
            'SUM(charge_amount) as total_amount',
            'SUM(CASE WHEN is_success=1 THEN 1 ELSE 0 END) as success_count'
        ])->group('api_config_id')
          ->order('call_count', 'desc')
          ->limit($limit);
        
        if ($startTime) {
            $query->where('call_time', '>=', $startTime);
        }
        
        if ($endTime) {
            $query->where('call_time', '<=', $endTime);
        }
        
        $list = $query->select();
        
        // 关联API配置信息
        foreach ($list as &$item) {
            $apiConfig = ApiConfig::find($item['api_config_id']);
            $item['api_name'] = $apiConfig ? $apiConfig->api_name : '未知';
            $item['api_code'] = $apiConfig ? $apiConfig->api_code : '';
            $item['success_rate'] = $item['call_count'] > 0 
                ? round(($item['success_count'] / $item['call_count']) * 100, 2) 
                : 0;
        }
        
        return $list;
    }
    
    /**
     * 清理过期日志
     */
    public static function cleanExpiredLogs($days = 90)
    {
        $expireTime = time() - ($days * 86400);
        
        try {
            $count = self::where('call_time', '<', $expireTime)->delete();
            return $count;
        } catch (\Exception $e) {
            trace('清理过期日志失败: ' . $e->getMessage(), 'error');
            return 0;
        }
    }
}
