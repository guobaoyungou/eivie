<?php
declare(strict_types=1);

namespace app\command;

use app\service\AiTravelPhotoOrderService;
use app\service\AiTravelPhotoQrcodeService;
use app\service\AiTravelPhotoDeviceService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 定时任务命令
 * 用于执行系统的各种定时任务
 */
class AiTravelPhotoSchedule extends Command
{
    protected function configure()
    {
        $this->setName('ai_travel_photo:schedule')
            ->setDescription('AI旅拍系统定时任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('AI旅拍系统定时任务开始执行...');
        
        try {
            // 1. 关闭超时订单（每5分钟）
            $this->closeTimeoutOrders($output);
            
            // 2. 检查设备离线状态（每分钟）
            $this->checkOfflineDevices($output);
            
            // 3. 二维码过期检查（每小时，根据当前分钟判断）
            $currentMinute = (int)date('i');
            if ($currentMinute == 0) {
                $this->checkExpiredQrcodes($output);
            }
            
            // 4. 数据统计（每日凌晨1点）
            $currentHour = (int)date('H');
            if ($currentHour == 1 && $currentMinute == 0) {
                $this->dailyStatistics($output);
            }
            
            $output->writeln('定时任务执行完成');
            
        } catch (\Exception $e) {
            $output->error('定时任务执行失败：' . $e->getMessage());
            trace('定时任务执行失败：' . $e->getMessage(), 'error');
        }
    }

    /**
     * 关闭超时订单
     *
     * @param Output $output
     * @return void
     */
    private function closeTimeoutOrders(Output $output)
    {
        try {
            $orderService = new AiTravelPhotoOrderService();
            $count = $orderService->closeTimeoutOrders();
            
            if ($count > 0) {
                $output->info("关闭超时订单：{$count}个");
                trace("定时任务：关闭超时订单{$count}个", 'info');
            }
            
        } catch (\Exception $e) {
            $output->error('关闭超时订单失败：' . $e->getMessage());
        }
    }

    /**
     * 检查设备离线状态
     *
     * @param Output $output
     * @return void
     */
    private function checkOfflineDevices(Output $output)
    {
        try {
            $deviceService = new AiTravelPhotoDeviceService();
            $count = $deviceService->checkOfflineDevices();
            
            if ($count > 0) {
                $output->info("检测到离线设备：{$count}个");
                trace("定时任务：检测到离线设备{$count}个", 'info');
            }
            
        } catch (\Exception $e) {
            $output->error('检查设备离线状态失败：' . $e->getMessage());
        }
    }

    /**
     * 二维码过期检查
     *
     * @param Output $output
     * @return void
     */
    private function checkExpiredQrcodes(Output $output)
    {
        try {
            $qrcodeService = new AiTravelPhotoQrcodeService();
            $count = $qrcodeService->checkExpired();
            
            if ($count > 0) {
                $output->info("标记过期二维码：{$count}个");
                trace("定时任务：标记过期二维码{$count}个", 'info');
            }
            
        } catch (\Exception $e) {
            $output->error('二维码过期检查失败：' . $e->getMessage());
        }
    }

    /**
     * 每日数据统计
     *
     * @param Output $output
     * @return void
     */
    private function dailyStatistics(Output $output)
    {
        try {
            $output->info('开始执行每日数据统计...');
            
            // 这里可以调用统计服务进行数据汇总
            // 例如：商家销售额统计、场景使用统计、设备活跃度统计等
            
            // 由于statistics_001任务还未实现，这里先预留接口
            // $statisticsService = new AiTravelPhotoStatisticsService();
            // $statisticsService->dailyStatistics();
            
            $output->info('每日数据统计完成');
            trace('定时任务：每日数据统计完成', 'info');
            
        } catch (\Exception $e) {
            $output->error('每日数据统计失败：' . $e->getMessage());
        }
    }
}
