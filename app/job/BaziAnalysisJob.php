<?php
/**
 * 八字分析异步队列任务
 * 后台执行AI分析，完成后保存结果并推送公众号模板消息
 */
namespace app\job;

use think\facade\Db;
use think\facade\Log;
use think\queue\Job;

class BaziAnalysisJob
{
    public $delay = 1;

    public function fire(Job $job, $data)
    {
        if ($job->attempts() > 3) {
            $this->failed($job, $data);
            $job->delete();
            return;
        }

        $recordId = $data['record_id'] ?? 0;
        $aid = $data['aid'] ?? 1;
        $openid = $data['openid'] ?? '';

        Log::info('BaziAnalysisJob: 开始执行分析 #' . $recordId);

        // 1. 读取记录
        $order = Db::name('bazi_order')->where('id', $recordId)->find();
        if (empty($order)) {
            Log::error('BaziAnalysisJob: 记录不存在 #' . $recordId);
            $job->delete();
            return;
        }

        // 如果已经分析过，跳过
        if (!empty($order['result_json'])) {
            Log::info('BaziAnalysisJob: 记录已完成分析 #' . $recordId);
            $job->delete();
            return;
        }

        // 2. 解析输入参数
        $params = json_decode($order['input_json'], true) ?: [];
        if (empty($params)) {
            Log::error('BaziAnalysisJob: 输入参数为空 #' . $recordId);
            $job->delete();
            return;
        }

        // 3. 调用BaziService执行分析
        try {
            $service = new \app\service\BaziService();
            $result = $service->calculate($params, $aid);

            $now = time();
            $updateData = ['update_time' => $now];

            if ($result['status'] === 1) {
                $data = $result['data'] ?? [];
                $usage = $data['usage'] ?? [];

                $resultJson = json_encode([
                    'result'    => $data['result'] ?? '',
                    'reasoning' => $data['reasoning'] ?? '',
                    'usage'     => $usage,
                    'latency_ms' => $data['latency_ms'] ?? 0,
                    'finish_reason' => $data['finish_reason'] ?? '',
                ], JSON_UNESCAPED_UNICODE);

                $updateData['result_json'] = $resultJson;
                $updateData['latency_ms'] = $data['latency_ms'] ?? 0;
                $updateData['total_tokens'] = $usage['total_tokens'] ?? 0;

                // 如果是预测后付费模式，生成预览文本
                if ($order['pay_mode'] === 'predict_then_pay') {
                    $config = $service->getConfig($aid);
                    $previewPercent = intval($config['preview_percent']);
                    $fullResult = $data['result'] ?? '';
                    $fullLen = mb_strlen($fullResult);
                    $previewLen = intval($fullLen * $previewPercent / 100);
                    $previewText = mb_substr($fullResult, 0, $previewLen);
                    $updateData['preview_text'] = $previewText;
                }

                Db::name('bazi_order')->where('id', $recordId)->update($updateData);

                Log::info('BaziAnalysisJob: 分析完成 #' . $recordId, [
                    'latency_ms' => $data['latency_ms'] ?? 0,
                    'total_tokens' => $usage['total_tokens'] ?? 0,
                ]);

                // 4. 推送公众号模板消息通知
                $this->sendNotification($aid, $order, $openid);

            } else {
                // 分析失败，记录错误
                $updateData['result_json'] = json_encode([
                    'result' => '',
                    'reasoning' => '',
                    'error' => $result['msg'] ?? '分析失败',
                ], JSON_UNESCAPED_UNICODE);
                Db::name('bazi_order')->where('id', $recordId)->update($updateData);

                Log::error('BaziAnalysisJob: 分析失败 #' . $recordId . ' - ' . ($result['msg'] ?? 'unknown'));
            }

            $job->delete();

        } catch (\Exception $e) {
            Log::error('BaziAnalysisJob: 异常 #' . $recordId . ' - ' . $e->getMessage());
            
            if ($job->attempts() >= 3) {
                $job->delete();
            } else {
                $job->release($this->delay);
            }
        }
    }

    /**
     * 发送公众号模板消息通知
     */
    protected function sendNotification(int $aid, array $order, string $openid): void
    {
        // 获取openid：优先使用传入的，其次从transaction_id字段读取
        if (empty($openid)) {
            $openid = $order['transaction_id'] ?? '';
        }

        if (empty($openid)) {
            Log::info('BaziAnalysisJob: 无openid，跳过模板消息推送 #' . $order['id']);
            return;
        }

        try {
            $resultUrl = PRE_URL . '/?s=/bazi/result&ordernum=' . urlencode($order['ordernum']);

            // 截取结果摘要作为first内容
            $resultData = json_decode($order['result_json'] ?? '{}', true);
            $resultText = $resultData['result'] ?? '';
            $summary = mb_strlen($resultText) > 100 ? mb_substr($resultText, 0, 100) . '...' : $resultText;

            $content = [
                'first'    => '您的八字命理分析已完成，请点击查看详细报告。',
                'keyword1' => '八字命理分析',
                'keyword2' => date('Y-m-d H:i', $order['create_time'] ?? time()),
                'remark'   => $summary,
            ];

            // 发送模板消息（复用已有的表单提交通知模板 tmpl_formsub）
            $rs = \app\common\Wechat::sendtmpl($aid, $openid, 'tmpl_formsub', $content, $resultUrl);
            Log::info('BaziAnalysisJob: 模板消息推送结果 #' . $order['id'], ['rs' => $rs]);

        } catch (\Exception $e) {
            Log::error('BaziAnalysisJob: 模板消息推送异常 #' . $order['id'] . ' - ' . $e->getMessage());
        }
    }

    public function failed($job, $data)
    {
        $jobInfo = Db::name('jobs')->where('id', $job->getJobId())->find();
        if ($jobInfo) {
            Db::name('jobs_failed')->insert($jobInfo);
        }
        Log::error('BaziAnalysisJob: 任务最终失败', ['data' => $data]);
    }
}
