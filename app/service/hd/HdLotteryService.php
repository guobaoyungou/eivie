<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Log;
use app\model\hd\HdActivity;
use app\model\hd\HdActivityFeature;
use app\model\hd\HdPrize;
use app\model\hd\HdLotteryConfig;
use app\model\hd\HdLotteryTheme;
use app\model\hd\HdChoujiangConfig;
use app\model\hd\HdImportlottery;
use app\model\hd\HdLotteryWinner;
use app\model\hd\HdLotteryDesignated;
use app\model\hd\HdParticipant;

/**
 * 大屏互动 - 抽奖管理服务
 * 功能：大屏抽奖轮次、奖品、抽奖主题、手机抽奖、摇大奖、导入抽奖
 */
class HdLotteryService
{
    // ========================================================
    // 奖品管理
    // ========================================================

    /**
     * 奖品列表
     */
    public function getPrizes(int $aid, int $bid, int $activityId, array $params = []): array
    {
        $where = [['aid', '=', $aid], ['bid', '=', $bid], ['activity_id', '=', $activityId]];
        $list = HdPrize::where($where)->order('sort asc, id asc')->select()->toArray();
        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 转换奖品级别
     * @param mixed $typeValue 类型值（数字或中文名称）
     * @return int 转换后的数字类型
     */
    private function convertPrizeType($typeValue): int
    {
        // 如果是数字，直接返回
        if (is_numeric($typeValue)) {
            return (int)$typeValue;
        }
        
        // 中文名称到数字的映射
        $nameMap = [
            '普通奖品（无级别）' => 0,
            '普通奖品' => 0,
            '一等奖' => 1,
            '二等奖' => 2,
            '三等奖' => 3,
            '四等奖' => 4,
            '五等奖' => 5,
        ];
        
        $value = trim((string)$typeValue);
        if (isset($nameMap[$value])) {
            return $nameMap[$value];
        }
        
        // 尝试将字符串转换为数字
        if (is_numeric($value)) {
            return (int)$value;
        }
        
        // 默认值
        return 0;
    }
    
    /**
     * 转换数字类型为中文名称
     * @param int $type 数字类型
     * @return string 中文名称
     */
    private function convertPrizeTypeToName(int $type): string
    {
        $nameMap = [
            0 => '普通奖品（无级别）',
            1 => '一等奖',
            2 => '二等奖',
            3 => '三等奖',
            4 => '四等奖',
            5 => '五等奖',
        ];
        
        return $nameMap[$type] ?? (string)$type;
    }
    
    /**
     * 创建奖品
     */
    public function createPrize(int $aid, int $bid, int $activityId, array $data): array
    {
        error_log("[HdLotteryService] createPrize called: aid={$aid}, bid={$bid}, activityId={$activityId}, data=" . json_encode($data));
        $typeValue = $data['type'] ?? 1;
        // 转换中文级别名称为数字
        $type = $this->convertPrizeType($typeValue);
        
        // 检查同一活动中奖品级别是否已存在
        $exists = HdPrize::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->where('type', $type)
            ->find();
        if ($exists) {
            // 获取中文级别名称用于提示
            $typeName = $this->convertPrizeTypeToName($type);
            return ['code' => 1, 'msg' => '奖品级别 ' . $typeName . ' 已存在，请勿重复添加'];
        }
        
        $prize = new HdPrize();
        $prize->aid = $aid;
        $prize->bid = $bid;
        $prize->activity_id = $activityId;
        $prize->name = $data['name'] ?? ($data['prizename'] ?? '');
        $prize->prizename = $data['prizename'] ?? ($data['name'] ?? '');
        $prize->image = $data['image'] ?? '';
        $prize->imageid = $data['imageid'] ?? ($data['image'] ?? '');
        $prize->total_num = (int)($data['total_num'] ?? ($data['num'] ?? 0));
        $prize->num = (int)($data['num'] ?? ($data['total_num'] ?? 0));
        $prize->leftnum = (int)($data['num'] ?? ($data['total_num'] ?? 0));
        $prize->used_num = 0;
        $prize->type = $type;
        $prize->draw_count = (int)($data['draw_count'] ?? 1);
        $prize->plug_name = $data['plug_name'] ?? '';
        $prize->sort = (int)($data['sort'] ?? 0);
        $prize->createtime = time();
        $prize->save();

        return ['code' => 0, 'msg' => '创建成功', 'data' => $prize->toArray()];
    }

    /**
     * 更新奖品
     */
    public function updatePrize(int $aid, int $bid, int $activityId, int $id, array $data): array
    {
        $prize = HdPrize::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$prize) {
            return ['code' => 1, 'msg' => '奖品不存在'];
        }

        // 如果type字段被修改，检查奖品级别是否重复
        if (isset($data['type'])) {
            $newType = $this->convertPrizeType($data['type']);
            // 只有在type确实变化时才检查
            if ($newType != $prize->type) {
                $exists = HdPrize::where('aid', $aid)->where('bid', $bid)
                    ->where('activity_id', $activityId)
                    ->where('type', $newType)
                    ->where('id', '<>', $id) // 排除当前奖品
                    ->find();
                if ($exists) {
                    $typeName = $this->convertPrizeTypeToName($newType);
                    return ['code' => 1, 'msg' => '奖品级别 ' . $typeName . ' 已存在，请选择其他级别'];
                }
            }
        }

        if (isset($data['name'])) $prize->name = $data['name'];
        if (isset($data['prizename'])) $prize->prizename = $data['prizename'];
        if (isset($data['image'])) $prize->image = $data['image'];
        if (isset($data['imageid'])) $prize->imageid = $data['imageid'];
        if (isset($data['total_num'])) $prize->total_num = (int)$data['total_num'];
        if (isset($data['num'])) {
            $oldNum = (int)$prize->num;
            $newNum = (int)$data['num'];
            $prize->num = $newNum;
            $prize->leftnum = max(0, (int)$prize->leftnum + ($newNum - $oldNum));
        }
        if (isset($data['type'])) $prize->type = $this->convertPrizeType($data['type']);
        if (isset($data['draw_count'])) $prize->draw_count = (int)$data['draw_count'];
        if (isset($data['plug_name'])) $prize->plug_name = $data['plug_name'];
        if (isset($data['sort'])) $prize->sort = (int)$data['sort'];
        $prize->save();

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 删除奖品
     */
    public function deletePrize(int $aid, int $bid, int $activityId, int $id): array
    {
        error_log("[HdLotteryService] deletePrize called: aid={$aid}, bid={$bid}, activityId={$activityId}, id={$id}");
        $prize = HdPrize::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$prize) {
            error_log("[HdLotteryService] deletePrize: prize not found");
            return ['code' => 1, 'msg' => '奖品不存在'];
        }
        $prize->delete();
        error_log("[HdLotteryService] deletePrize: success");
        return ['code' => 0, 'msg' => '删除成功'];
    }

    // ========================================================
    // 抽奖轮次管理
    // ========================================================

    /**
     * 抽奖轮次列表
     */
    public function getRounds(int $aid, int $bid, int $activityId): array
    {
        $list = HdLotteryConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->order('round_num asc, id asc')
            ->select()->toArray();

        // 附加奖品信息
        foreach ($list as &$item) {
            if ($item['prize_id']) {
                $prize = HdPrize::find($item['prize_id']);
                $item['prize_name'] = $prize ? $prize->name : '';
            } else {
                $item['prize_name'] = '';
            }
        }
        unset($item);

        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 创建抽奖轮次
     */
    public function createRound(int $aid, int $bid, int $activityId, array $data): array
    {
        $maxRound = (int)HdLotteryConfig::where('activity_id', $activityId)->max('round_num');

        $round = new HdLotteryConfig();
        $round->aid = $aid;
        $round->bid = $bid;
        $round->activity_id = $activityId;
        $round->round_name = $data['round_name'] ?? ($data['title'] ?? '第' . ($maxRound + 1) . '轮活动');
        $round->title = $data['title'] ?? ($data['round_name'] ?? '第' . ($maxRound + 1) . '轮活动');
        $round->round_num = $maxRound + 1;
        $round->prize_id = (int)($data['prize_id'] ?? 0);
        $round->win_num = (int)($data['win_num'] ?? 1);
        $round->is_repeat = (int)($data['is_repeat'] ?? 0);
        $round->show_type = $data['show_type'] ?? 'normal';
        $round->win_again = (int)($data['win_again'] ?? 1);
        $round->show_style = $data['show_style'] ?? 'nickname';
        $round->theme_id = (int)($data['theme_id'] ?? 0);
        $round->status = 1;
        $round->winners = '';
        $round->createtime = time();
        $round->save();

        return ['code' => 0, 'msg' => '创建成功', 'data' => $round->toArray()];
    }

    /**
     * 更新抽奖轮次
     */
    public function updateRound(int $aid, int $bid, int $activityId, int $id, array $data): array
    {
        $round = HdLotteryConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$round) {
            return ['code' => 1, 'msg' => '轮次不存在'];
        }

        if (isset($data['round_name'])) $round->round_name = $data['round_name'];
        if (isset($data['title'])) {
            $round->title = $data['title'];
            $round->round_name = $data['title'];
        }
        if (isset($data['prize_id'])) $round->prize_id = (int)$data['prize_id'];
        if (isset($data['win_num'])) $round->win_num = (int)$data['win_num'];
        if (isset($data['is_repeat'])) $round->is_repeat = (int)$data['is_repeat'];
        if (isset($data['show_type'])) $round->show_type = $data['show_type'];
        if (isset($data['win_again'])) $round->win_again = (int)$data['win_again'];
        if (isset($data['show_style'])) $round->show_style = $data['show_style'];
        if (isset($data['theme_id'])) $round->theme_id = (int)$data['theme_id'];
        if (isset($data['status'])) $round->status = (int)$data['status'];
        $round->save();

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 删除抽奖轮次
     */
    public function deleteRound(int $aid, int $bid, int $activityId, int $id): array
    {
        $round = HdLotteryConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$round) {
            return ['code' => 1, 'msg' => '轮次不存在'];
        }
        $round->delete();
        return ['code' => 0, 'msg' => '删除成功'];
    }

    /**
     * 重置抽奖轮次（清空中奖者）
     */
    public function resetRound(int $aid, int $bid, int $activityId, int $id): array
    {
        $round = HdLotteryConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$round) {
            return ['code' => 1, 'msg' => '轮次不存在'];
        }

        // 清空中奖记录表对应的记录，恢复奖品剩余数量
        $winners = HdLotteryWinner::where('round_id', $id)->where('activity_id', $activityId)->select();
        foreach ($winners as $w) {
            if ($w->prize_id) {
                HdPrize::where('id', $w->prize_id)->inc('leftnum')->update();
            }
        }
        HdLotteryWinner::where('round_id', $id)->where('activity_id', $activityId)->delete();

        $round->winners = '';
        $round->status = 1;
        $round->save();
        return ['code' => 0, 'msg' => '轮次已重置'];
    }

    // ========================================================
    // 抽奖主题管理
    // ========================================================

    /**
     * 抽奖主题列表
     */
    public function getThemes(int $aid, int $bid, int $activityId): array
    {
        $list = HdLotteryTheme::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->order('is_default desc, id asc')
            ->select()->toArray();
        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 创建抽奖主题
     */
    public function createTheme(int $aid, int $bid, int $activityId, array $data): array
    {
        $theme = new HdLotteryTheme();
        $theme->aid = $aid;
        $theme->bid = $bid;
        $theme->activity_id = $activityId;
        $theme->name = $data['name'] ?? '';
        $theme->bg_image = $data['bg_image'] ?? '';
        $theme->config = $data['config'] ?? [];
        $theme->is_default = (int)($data['is_default'] ?? 0);
        $theme->createtime = time();
        $theme->save();

        return ['code' => 0, 'msg' => '创建成功', 'data' => $theme->toArray()];
    }

    /**
     * 更新抽奖主题
     */
    public function updateTheme(int $aid, int $bid, int $activityId, int $id, array $data): array
    {
        $theme = HdLotteryTheme::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$theme) {
            return ['code' => 1, 'msg' => '主题不存在'];
        }

        if (isset($data['name'])) $theme->name = $data['name'];
        if (isset($data['bg_image'])) $theme->bg_image = $data['bg_image'];
        if (isset($data['config'])) $theme->config = $data['config'];
        if (isset($data['is_default'])) {
            if ((int)$data['is_default'] === 1) {
                // 将其他主题设为非默认
                HdLotteryTheme::where('activity_id', $activityId)->where('id', '<>', $id)
                    ->update(['is_default' => 0]);
            }
            $theme->is_default = (int)$data['is_default'];
        }
        $theme->save();

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 删除抽奖主题
     */
    public function deleteTheme(int $aid, int $bid, int $activityId, int $id): array
    {
        $theme = HdLotteryTheme::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$theme) {
            return ['code' => 1, 'msg' => '主题不存在'];
        }
        $theme->delete();
        return ['code' => 0, 'msg' => '删除成功'];
    }

    // ========================================================
    // 手机抽奖管理
    // ========================================================

    /**
     * 获取手机抽奖配置
     */
    public function getChoujiangConfig(int $aid, int $bid, int $activityId): array
    {
        $config = HdChoujiangConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->find();
        return ['code' => 0, 'data' => $config ? $config->toArray() : null];
    }

    /**
     * 更新手机抽奖配置
     */
    public function updateChoujiangConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $config = HdChoujiangConfig::where('activity_id', $activityId)->find();
        if (!$config) {
            $config = new HdChoujiangConfig();
            $config->aid = $aid;
            $config->bid = $bid;
            $config->activity_id = $activityId;
            $config->createtime = time();
        }

        if (isset($data['max_times'])) $config->max_times = (int)$data['max_times'];
        if (isset($data['bg_image'])) $config->bg_image = $data['bg_image'];
        if (isset($data['config'])) $config->config = $data['config'];
        $config->save();

        return ['code' => 0, 'msg' => '手机抽奖配置已更新'];
    }

    // ========================================================
    // 导入抽奖管理
    // ========================================================

    /**
     * 导入抽奖名单
     */
    public function getImportList(int $aid, int $bid, int $activityId, array $params = []): array
    {
        $where = [['aid', '=', $aid], ['bid', '=', $bid], ['activity_id', '=', $activityId]];

        if (!empty($params['keyword'])) {
            $where[] = ['name|phone|code', 'like', '%' . $params['keyword'] . '%'];
        }
        if (isset($params['is_winner']) && $params['is_winner'] !== '') {
            $where[] = ['is_winner', '=', (int)$params['is_winner']];
        }

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 50);

        $list = HdImportlottery::where($where)->page($page, $limit)->order('id desc')->select()->toArray();
        $count = HdImportlottery::where($where)->count();

        return ['code' => 0, 'data' => ['list' => $list, 'count' => $count]];
    }

    /**
     * 批量导入抽奖名单
     */
    public function batchImport(int $aid, int $bid, int $activityId, array $items): array
    {
        $now = time();
        $insertData = [];
        foreach ($items as $item) {
            $insertData[] = [
                'aid'         => $aid,
                'bid'         => $bid,
                'activity_id' => $activityId,
                'name'        => $item['name'] ?? '',
                'phone'       => $item['phone'] ?? '',
                'code'        => $item['code'] ?? '',
                'extra'       => isset($item['extra']) ? json_encode($item['extra'], JSON_UNESCAPED_UNICODE) : '',
                'is_winner'   => 0,
                'prize_id'    => 0,
                'createtime'  => $now,
            ];
        }

        if ($insertData) {
            Db::name('hd_importlottery')->insertAll($insertData);
        }

        return ['code' => 0, 'msg' => '导入成功', 'data' => ['count' => count($insertData)]];
    }

    /**
     * 清空导入抽奖名单
     */
    public function clearImportList(int $aid, int $bid, int $activityId): array
    {
        HdImportlottery::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->delete();
        return ['code' => 0, 'msg' => '名单已清空'];
    }

    // ========================================================
    // 中奖名单管理
    // ========================================================

    /**
     * 获取中奖名单
     */
    public function getWinners(int $aid, int $bid, int $activityId, array $params = []): array
    {
        $where = [['aid', '=', $aid], ['bid', '=', $bid], ['activity_id', '=', $activityId]];

        if (!empty($params['round_id'])) {
            $where[] = ['round_id', '=', (int)$params['round_id']];
        }
        if (!empty($params['prize_id'])) {
            $where[] = ['prize_id', '=', (int)$params['prize_id']];
        }
        if (!empty($params['status'])) {
            $where[] = ['status', '=', (int)$params['status']];
        }

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 50);

        $list = HdLotteryWinner::where($where)->page($page, $limit)
            ->order('win_time desc, id desc')->select()->toArray();

        // 附加轮次名称和奖品名称
        $roundIds = array_unique(array_column($list, 'round_id'));
        $prizeIds = array_unique(array_column($list, 'prize_id'));
        $rounds = $roundIds ? HdLotteryConfig::whereIn('id', $roundIds)->column('title,show_type', 'id') : [];
        $prizes = $prizeIds ? HdPrize::whereIn('id', $prizeIds)->column('prizename', 'id') : [];

        foreach ($list as &$item) {
            $item['round_name'] = isset($rounds[$item['round_id']]) ? ($rounds[$item['round_id']]['title'] ?: '') : '';
            $item['show_type'] = isset($rounds[$item['round_id']]) ? ($rounds[$item['round_id']]['show_type'] ?: 'normal') : 'normal';
            $item['prize_name'] = $prizes[$item['prize_id']] ?? '';
        }
        unset($item);

        $count = HdLotteryWinner::where($where)->count();
        return ['code' => 0, 'data' => ['list' => $list, 'count' => $count]];
    }

    /**
     * 发奖操作
     */
    public function givePrize(int $aid, int $bid, int $activityId, int $id): array
    {
        $winner = HdLotteryWinner::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$winner) {
            return ['code' => 1, 'msg' => '中奖记录不存在'];
        }
        if ($winner->status == HdLotteryWinner::STATUS_GIVEN) {
            return ['code' => 1, 'msg' => '已经发奖，无需重复操作'];
        }
        $winner->status = HdLotteryWinner::STATUS_GIVEN;
        $winner->give_time = time();
        $winner->save();
        return ['code' => 0, 'msg' => '发奖成功'];
    }

    /**
     * 取消发奖
     */
    public function cancelPrize(int $aid, int $bid, int $activityId, int $id): array
    {
        $winner = HdLotteryWinner::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$winner) {
            return ['code' => 1, 'msg' => '中奖记录不存在'];
        }
        $winner->status = HdLotteryWinner::STATUS_NOT_GIVEN;
        $winner->give_time = 0;
        $winner->save();
        return ['code' => 0, 'msg' => '已取消发奖'];
    }

    /**
     * 删除中奖记录
     */
    public function deleteWinner(int $aid, int $bid, int $activityId, int $id): array
    {
        $winner = HdLotteryWinner::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$winner) {
            return ['code' => 1, 'msg' => '中奖记录不存在'];
        }
        // 恢复奖品剩余数量
        if ($winner->prize_id) {
            HdPrize::where('id', $winner->prize_id)->inc('leftnum')->update();
        }
        $winner->delete();
        return ['code' => 0, 'msg' => '删除成功'];
    }

    /**
     * 清空中奖记录
     */
    public function clearWinners(int $aid, int $bid, int $activityId, array $params = []): array
    {
        $where = [['aid', '=', $aid], ['bid', '=', $bid], ['activity_id', '=', $activityId]];
        if (!empty($params['round_id'])) {
            $where[] = ['round_id', '=', (int)$params['round_id']];
        }

        // 恢复所有奖品剩余数量
        $winners = HdLotteryWinner::where($where)->select();
        foreach ($winners as $w) {
            if ($w->prize_id) {
                HdPrize::where('id', $w->prize_id)->inc('leftnum')->update();
            }
        }
        HdLotteryWinner::where($where)->delete();

        return ['code' => 0, 'msg' => '中奖记录已清空'];
    }

    // ========================================================
    // 内定名单管理
    // ========================================================

    /**
     * 获取内定名单
     */
    public function getDesignated(int $aid, int $bid, int $activityId, array $params = []): array
    {
        $where = [['aid', '=', $aid], ['bid', '=', $bid], ['activity_id', '=', $activityId]];

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 50);

        $list = HdLotteryDesignated::where($where)->page($page, $limit)
            ->order('id desc')->select()->toArray();

        // 附加用户信息和奖品信息
        $partIds = array_unique(array_column($list, 'participant_id'));
        $prizeIds = array_unique(array_column($list, 'prize_id'));
        $parts = $partIds ? HdParticipant::whereIn('id', $partIds)->column('nickname,avatar,phone', 'id') : [];
        $prizes = $prizeIds ? HdPrize::whereIn('id', $prizeIds)->column('prizename', 'id') : [];

        foreach ($list as &$item) {
            $p = $parts[$item['participant_id']] ?? [];
            $item['nickname'] = $p['nickname'] ?? '';
            $item['avatar'] = $p['avatar'] ?? '';
            $item['phone'] = $p['phone'] ?? '';
            $item['prize_name'] = $prizes[$item['prize_id']] ?? '';
        }
        unset($item);

        $count = HdLotteryDesignated::where($where)->count();
        return ['code' => 0, 'data' => ['list' => $list, 'count' => $count]];
    }

    /**
     * 添加内定
     */
    public function addDesignated(int $aid, int $bid, int $activityId, array $data): array
    {
        $participantId = (int)($data['user_id'] ?? ($data['participant_id'] ?? 0));
        $prizeId = (int)($data['prize_id'] ?? 0);
        $designated = (int)($data['designated'] ?? 2);

        if (!$participantId) {
            return ['code' => 1, 'msg' => '请选择用户'];
        }

        // 检查是否已内定
        $exists = HdLotteryDesignated::where('activity_id', $activityId)
            ->where('participant_id', $participantId)->find();
        if ($exists) {
            // 更新内定设置
            $exists->prize_id = $prizeId;
            $exists->designated = $designated;
            $exists->save();
            return ['code' => 0, 'msg' => '内定设置已更新'];
        }

        $record = new HdLotteryDesignated();
        $record->aid = $aid;
        $record->bid = $bid;
        $record->activity_id = $activityId;
        $record->participant_id = $participantId;
        $record->prize_id = $prizeId;
        $record->designated = $designated;
        $record->plug_name = $data['plug_name'] ?? '';
        $record->createtime = time();
        $record->save();

        return ['code' => 0, 'msg' => '内定设置成功'];
    }

    /**
     * 取消内定
     */
    public function cancelDesignated(int $aid, int $bid, int $activityId, int $id): array
    {
        $record = HdLotteryDesignated::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$record) {
            return ['code' => 1, 'msg' => '内定记录不存在'];
        }
        $record->delete();
        return ['code' => 0, 'msg' => '已取消内定'];
    }

    /**
     * 搜索可内定人员（已签到的参与者）
     */
    public function searchUsers(int $aid, int $bid, int $activityId, array $params = []): array
    {
        $where = [
            ['aid', '=', $aid],
            ['bid', '=', $bid],
            ['activity_id', '=', $activityId],
            ['flag', '=', HdParticipant::FLAG_SIGNED],
        ];

        if (!empty($params['keyword'])) {
            $where[] = ['nickname|signname|phone', 'like', '%' . $params['keyword'] . '%'];
        }

        $list = HdParticipant::where($where)->limit(50)
            ->field('id,nickname,avatar,phone,signname')
            ->order('id desc')->select()->toArray();

        return ['code' => 0, 'data' => ['list' => $list]];
    }

    // ========================================================
    // 幸运手机号 / 幸运号码管理
    // ========================================================

    /**
     * 幸运手机号记录（从中奖记录中筛选手机号中奖）
     */
    public function getLuckyPhoneRecords(int $aid, int $bid, int $activityId, array $params = []): array
    {
        $where = [
            ['aid', '=', $aid],
            ['bid', '=', $bid],
            ['activity_id', '=', $activityId],
            ['phone', '<>', ''],
        ];

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 50);

        $list = HdLotteryWinner::where($where)->page($page, $limit)
            ->order('win_time desc')->select()->toArray();
        $count = HdLotteryWinner::where($where)->count();

        return ['code' => 0, 'data' => ['list' => $list, 'count' => $count]];
    }

    /**
     * 幸运号码配置（存储在活动 screen_config 中）
     */
    public function getLuckyNumberConfig(int $aid, int $bid, int $activityId): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)
            ->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config;
        if (is_string($screenConfig)) {
            $screenConfig = json_decode($screenConfig, true) ?: [];
        }
        $luckyConfig = $screenConfig['lucky_number'] ?? [
            'enabled' => 0,
            'min' => 0,
            'max' => 999,
            'digit' => 3,
            'prize_name' => '',
        ];

        return ['code' => 0, 'data' => $luckyConfig];
    }

    /**
     * 更新幸运号码配置
     */
    public function updateLuckyNumberConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)
            ->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config;
        if (is_string($screenConfig)) {
            $screenConfig = json_decode($screenConfig, true) ?: [];
        }

        $screenConfig['lucky_number'] = [
            'enabled' => (int)($data['enabled'] ?? 0),
            'min' => (int)($data['min'] ?? 0),
            'max' => (int)($data['max'] ?? 999),
            'digit' => (int)($data['digit'] ?? 3),
            'prize_name' => $data['prize_name'] ?? '',
        ];

        $activity->screen_config = json_encode($screenConfig, JSON_UNESCAPED_UNICODE);
        $activity->save();

        return ['code' => 0, 'msg' => '幸运号码配置已更新'];
    }

    /**
     * 幸运号码中奖记录（从中奖记录中获取）
     */
    public function getLuckyNumberRecords(int $aid, int $bid, int $activityId, array $params = []): array
    {
        // 幸运号码中奖记录复用 winner 表，通过 verify_code 字段存储幸运号码
        $where = [
            ['aid', '=', $aid],
            ['bid', '=', $bid],
            ['activity_id', '=', $activityId],
            ['verify_code', '<>', ''],
        ];

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 50);

        $list = HdLotteryWinner::where($where)->page($page, $limit)
            ->order('win_time desc')->select()->toArray();
        $count = HdLotteryWinner::where($where)->count();

        return ['code' => 0, 'data' => ['list' => $list, 'count' => $count]];
    }

    /**
     * 获取大屏显示设置（存储在活动 screen_config 中）
     */
    public function getScreenSettings(int $aid, int $bid, int $activityId): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)
            ->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config;
        if (is_string($screenConfig)) {
            $screenConfig = json_decode($screenConfig, true) ?: [];
        }

        $settings = $screenConfig['lottery_screen'] ?? [
            'display_mode'              => 'nickname',
            'template'                  => 'gold',
            'screen_enabled'            => 0,
            'screen_mode'               => 'normal',
            'screen_animation_duration' => 3000,
            'background'                => null,
        ];

        return ['code' => 0, 'data' => $settings];
    }

    /**
     * 更新大屏显示设置
     */
    public function updateScreenSettings(int $aid, int $bid, int $activityId, array $data): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)
            ->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config;
        if (is_string($screenConfig)) {
            $screenConfig = json_decode($screenConfig, true) ?: [];
        }

        $screenConfig['lottery_screen'] = [
            'display_mode'              => $data['display_mode'] ?? 'nickname',
            'template'                  => $data['template'] ?? 'gold',
            'screen_enabled'            => (int)($data['screen_enabled'] ?? 0),
            'screen_mode'               => $data['screen_mode'] ?? 'normal',
            'screen_animation_duration' => (int)($data['screen_animation_duration'] ?? 3000),
            'background'                => $data['background'] ?? ($screenConfig['lottery_screen']['background'] ?? null),
        ];

        $activity->screen_config = json_encode($screenConfig, JSON_UNESCAPED_UNICODE);
        $activity->save();

        return ['code' => 0, 'msg' => '大屏显示设置已更新'];
    }

    /**
     * 重置大屏背景
     */
    public function resetScreenBackground(int $aid, int $bid, int $activityId): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)
            ->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config;
        if (is_string($screenConfig)) {
            $screenConfig = json_decode($screenConfig, true) ?: [];
        }

        if (isset($screenConfig['lottery_screen']['background'])) {
            $screenConfig['lottery_screen']['background'] = null;
            $activity->screen_config = json_encode($screenConfig, JSON_UNESCAPED_UNICODE);
            $activity->save();
        }

        return ['code' => 0, 'msg' => '大屏背景已重置'];
    }
}
