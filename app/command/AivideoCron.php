<?php
/**
 * AI旅拍定时任务
 * 处理任务状态轮询、订单自动取消等定时任务
 * @author AI旅拍开发团队
 * @date 2026-01-19
 */

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use app\common\Aivideo;

class AivideoCron extends Command
{
    protected function configure()
    {
        $this->setName('aivideo:cron')
            ->setDescription('AI旅拍定时任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始执行AI旅拍定时任务...');

        // 轮询任务状态
        $this->pollTaskStatus();

        // 取消超时订单
        $this->cancelExpiredOrders();

        // 清理过期浏览记录
        $this->cleanExpiredBrowseHistory();

        $output->writeln('AI旅拍定时任务执行完成');
    }

    /**
     * 轮询任务状态
     */
    private function pollTaskStatus()
    {
        // 查询处理中的任务
        $tasks = Db::name('aivideo_task')
            ->where('task_status', 'processing')
            ->where('updatetime', '>', time() - 600) // 10分钟前更新的任务
            ->limit(100)
            ->select()
            ->toArray();

        foreach ($tasks as $task) {
            $result = Aivideo::checkTaskStatus($task['id']);
            if ($result['success']) {
                echo "任务 {$task['id']} 处理成功\n";
            }
        }
    }

    /**
     * 取消超时订单
     */
    private function cancelExpiredOrders()
    {
        $config = config('aivideo');
        $expireTime = time() - $config['order']['expire_time'];

        // 查询超时未支付订单
        $orders = Db::name('aivideo_order')
            ->where('pay_status', 0)
            ->where('createtime', '<', $expireTime)
            ->select()
            ->toArray();

        foreach ($orders as $order) {
            Db::startTrans();
            try {
                // 更新订单状态
                Db::name('aivideo_order')->where('id', $order['id'])->update([
                    'pay_status' => 2, // 已取消
                    'updatetime' => time(),
                ]);

                // 释放作品
                $workIds = explode(',', $order['work_ids']);
                Db::name('aivideo_work')
                    ->whereIn('id', $workIds)
                    ->where('mid', 0)
                    ->update([
                        'mid' => 0,
                        'is_free' => 1,
                    ]);

                Db::commit();
                echo "订单 {$order['ordernum']} 已取消\n";
            } catch (\Exception $e) {
                Db::rollback();
                Log::error('取消订单失败: ' . $e->getMessage());
            }
        }
    }

    /**
     * 清理过期浏览记录
     */
    private function cleanExpiredBrowseHistory()
    {
        $config = config('aivideo');
        $expireTime = time() - ($config['browse']['expire_days'] * 86400);

        // 删除过期浏览记录
        $count = Db::name('aivideo_selection')
            ->where('createtime', '<', $expireTime)
            ->delete();

        echo "清理了 {$count} 条过期浏览记录\n";
    }
}
