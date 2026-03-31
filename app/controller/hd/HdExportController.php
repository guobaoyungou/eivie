<?php
declare(strict_types=1);

namespace app\controller\hd;

use think\facade\Db;
use app\model\hd\HdActivity;
use app\model\hd\HdParticipant;
use app\model\hd\HdWallMessage;
use app\model\hd\HdPrize;

/**
 * 大屏互动 - 数据导出控制器
 * 支持参与者、上墙消息、中奖记录 CSV 导出
 */
class HdExportController extends HdBaseController
{
    /**
     * 导出参与者数据
     * GET /api/hd/export/participants/:activity_id
     */
    public function participants(int $activity_id)
    {
        $activity = $this->getMyActivity($activity_id);
        if (!$activity) {
            return $this->error('活动不存在');
        }

        $list = HdParticipant::where('activity_id', $activity_id)
            ->order('signorder asc, id asc')
            ->select()
            ->toArray();

        $headers = ['ID', '昵称', '头像', 'OpenID', '签到序号', '签名', '签到状态', '创建时间'];
        $rows = [];
        foreach ($list as $item) {
            $rows[] = [
                $item['id'],
                $item['nickname'] ?? '',
                $item['avatar'] ?? '',
                $item['openid'] ?? '',
                $item['signorder'] ?? '',
                $item['signname'] ?? '',
                ($item['flag'] ?? 1) == 2 ? '已签到' : '未签到',
                $item['createtime'] ? date('Y-m-d H:i:s', (int)$item['createtime']) : '',
            ];
        }

        $filename = '参与者_' . $activity->title . '_' . date('YmdHis');
        return $this->exportCsv($filename, $headers, $rows);
    }

    /**
     * 导出上墙消息
     * GET /api/hd/export/messages/:activity_id
     */
    public function messages(int $activity_id)
    {
        $activity = $this->getMyActivity($activity_id);
        if (!$activity) {
            return $this->error('活动不存在');
        }

        $list = HdWallMessage::where('activity_id', $activity_id)
            ->order('id asc')
            ->select()
            ->toArray();

        $headers = ['ID', '昵称', '内容', '图片', '审核状态', '创建时间'];
        $rows = [];
        foreach ($list as $item) {
            $rows[] = [
                $item['id'],
                $item['nickname'] ?? '',
                $item['content'] ?? '',
                $item['imgurl'] ?? '',
                ($item['is_approved'] ?? 0) == 1 ? '已通过' : '待审核',
                $item['createtime'] ? date('Y-m-d H:i:s', (int)$item['createtime']) : '',
            ];
        }

        $filename = '上墙消息_' . $activity->title . '_' . date('YmdHis');
        return $this->exportCsv($filename, $headers, $rows);
    }

    /**
     * 导出中奖记录
     * GET /api/hd/export/lottery/:activity_id
     */
    public function lottery(int $activity_id)
    {
        $activity = $this->getMyActivity($activity_id);
        if (!$activity) {
            return $this->error('活动不存在');
        }

        // 查询中奖记录（奖品表关联参与者）
        $winners = Db::name('hd_choujiang_user')
            ->alias('cu')
            ->leftJoin('hd_prize p', 'cu.prize_id = p.id')
            ->leftJoin('hd_participant pt', 'cu.participant_id = pt.id')
            ->where('cu.activity_id', $activity_id)
            ->field('cu.id, pt.nickname, pt.openid, p.name as prize_name, p.level as prize_level, cu.createtime')
            ->order('cu.id asc')
            ->select()
            ->toArray();

        $headers = ['ID', '中奖人', 'OpenID', '奖品名称', '奖品等级', '中奖时间'];
        $rows = [];
        foreach ($winners as $item) {
            $rows[] = [
                $item['id'],
                $item['nickname'] ?? '',
                $item['openid'] ?? '',
                $item['prize_name'] ?? '',
                $item['prize_level'] ?? '',
                $item['createtime'] ? date('Y-m-d H:i:s', (int)$item['createtime']) : '',
            ];
        }

        $filename = '中奖记录_' . $activity->title . '_' . date('YmdHis');
        return $this->exportCsv($filename, $headers, $rows);
    }

    /**
     * 获取当前商家的活动
     */
    protected function getMyActivity(int $activityId): ?HdActivity
    {
        return HdActivity::where('id', $activityId)
            ->where('aid', $this->getAid())
            ->where('bid', $this->getBid())
            ->find();
    }

    /**
     * 输出 CSV 下载
     */
    protected function exportCsv(string $filename, array $headers, array $rows)
    {
        $filename = str_replace(['"', '/', '\\'], '', $filename);

        // BOM + CSV
        $output = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility
        $output .= implode(',', array_map([$this, 'csvEscape'], $headers)) . "\n";

        foreach ($rows as $row) {
            $output .= implode(',', array_map([$this, 'csvEscape'], $row)) . "\n";
        }

        return response($output, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    }

    /**
     * CSV 字段转义
     */
    protected function csvEscape($value): string
    {
        $value = (string)$value;
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }
}
