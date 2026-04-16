<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdLotteryService;

/**
 * 大屏互动 - 抽奖管理控制器
 */
class HdLotteryController extends HdBaseController
{
    protected $lotteryService;

    protected function initialize()
    {
        $this->lotteryService = new HdLotteryService();
    }

    // —— 奖品 ——

    public function prizes(int $activity_id)
    {
        return json($this->lotteryService->getPrizes($this->getAid(), $this->getBid(), $activity_id));
    }

    public function createPrize(int $activity_id)
    {
        return json($this->lotteryService->createPrize($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    public function updatePrize(int $activity_id, int $id)
    {
        return json($this->lotteryService->updatePrize($this->getAid(), $this->getBid(), $activity_id, $id, input('post.')));
    }

    public function deletePrize(int $activity_id, int $id)
    {
        error_log("[HdLotteryController] deletePrize: method=" . request()->method() . " activity_id={$activity_id} id={$id} aid={$this->getAid()} bid={$this->getBid()} post_data=" . json_encode(input('post.')));
        return json($this->lotteryService->deletePrize($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    // —— 抽奖轮次 ——

    public function rounds(int $activity_id)
    {
        return json($this->lotteryService->getRounds($this->getAid(), $this->getBid(), $activity_id));
    }

    public function createRound(int $activity_id)
    {
        return json($this->lotteryService->createRound($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    public function updateRound(int $activity_id, int $id)
    {
        return json($this->lotteryService->updateRound($this->getAid(), $this->getBid(), $activity_id, $id, input('post.')));
    }

    public function deleteRound(int $activity_id, int $id)
    {
        return json($this->lotteryService->deleteRound($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    public function resetRound(int $activity_id, int $id)
    {
        return json($this->lotteryService->resetRound($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    // —— 抽奖主题 ——

    public function themes(int $activity_id)
    {
        return json($this->lotteryService->getThemes($this->getAid(), $this->getBid(), $activity_id));
    }

    public function createTheme(int $activity_id)
    {
        return json($this->lotteryService->createTheme($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    public function updateTheme(int $activity_id, int $id)
    {
        return json($this->lotteryService->updateTheme($this->getAid(), $this->getBid(), $activity_id, $id, input('post.')));
    }

    public function deleteTheme(int $activity_id, int $id)
    {
        return json($this->lotteryService->deleteTheme($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    // —— 手机抽奖 ——

    public function choujiangConfig(int $activity_id)
    {
        return json($this->lotteryService->getChoujiangConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    public function updateChoujiangConfig(int $activity_id)
    {
        return json($this->lotteryService->updateChoujiangConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    // —— 导入抽奖 ——

    public function importList(int $activity_id)
    {
        $params = [
            'keyword'   => input('get.keyword', ''),
            'is_winner' => input('get.is_winner', ''),
            'page'      => input('get.page', 1),
            'limit'     => input('get.limit', 50),
        ];
        return json($this->lotteryService->getImportList($this->getAid(), $this->getBid(), $activity_id, $params));
    }

    public function batchImport(int $activity_id)
    {
        $items = input('post.items/a', []);
        return json($this->lotteryService->batchImport($this->getAid(), $this->getBid(), $activity_id, $items));
    }

    public function clearImportList(int $activity_id)
    {
        return json($this->lotteryService->clearImportList($this->getAid(), $this->getBid(), $activity_id));
    }

    // —— 中奖名单 ——

    public function winners(int $activity_id)
    {
        $params = [
            'round_id' => input('get.round_id', ''),
            'prize_id' => input('get.prize_id', ''),
            'status'   => input('get.status', ''),
            'page'     => input('get.page', 1),
            'limit'    => input('get.limit', 50),
        ];
        return json($this->lotteryService->getWinners($this->getAid(), $this->getBid(), $activity_id, $params));
    }

    public function givePrize(int $activity_id, int $id)
    {
        return json($this->lotteryService->givePrize($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    public function cancelPrize(int $activity_id, int $id)
    {
        return json($this->lotteryService->cancelPrize($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    public function deleteWinner(int $activity_id, int $id)
    {
        return json($this->lotteryService->deleteWinner($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    public function clearWinners(int $activity_id)
    {
        $params = ['round_id' => input('post.round_id', '')];
        return json($this->lotteryService->clearWinners($this->getAid(), $this->getBid(), $activity_id, $params));
    }

    // —— 内定名单 ——

    public function designated(int $activity_id)
    {
        $params = [
            'page'  => input('get.page', 1),
            'limit' => input('get.limit', 50),
        ];
        return json($this->lotteryService->getDesignated($this->getAid(), $this->getBid(), $activity_id, $params));
    }

    public function addDesignated(int $activity_id)
    {
        return json($this->lotteryService->addDesignated($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    public function cancelDesignated(int $activity_id, int $id)
    {
        return json($this->lotteryService->cancelDesignated($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    public function searchUsers(int $activity_id)
    {
        $params = ['keyword' => input('get.keyword', '')];
        return json($this->lotteryService->searchUsers($this->getAid(), $this->getBid(), $activity_id, $params));
    }

    // —— 幸运手机号 ——

    public function luckyPhoneRecords(int $activity_id)
    {
        $params = [
            'page'  => input('get.page', 1),
            'limit' => input('get.limit', 50),
        ];
        return json($this->lotteryService->getLuckyPhoneRecords($this->getAid(), $this->getBid(), $activity_id, $params));
    }

    // —— 幸运号码 ——

    public function luckyNumberConfig(int $activity_id)
    {
        return json($this->lotteryService->getLuckyNumberConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    public function updateLuckyNumberConfig(int $activity_id)
    {
        return json($this->lotteryService->updateLuckyNumberConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    public function luckyNumberRecords(int $activity_id)
    {
        $params = [
            'page'  => input('get.page', 1),
            'limit' => input('get.limit', 50),
        ];
        return json($this->lotteryService->getLuckyNumberRecords($this->getAid(), $this->getBid(), $activity_id, $params));
    }

    // —— 大屏显示设置 ——

    public function screenSettings(int $activity_id)
    {
        return json($this->lotteryService->getScreenSettings($this->getAid(), $this->getBid(), $activity_id));
    }

    public function updateScreenSettings(int $activity_id)
    {
        return json($this->lotteryService->updateScreenSettings($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    public function resetScreenBackground(int $activity_id)
    {
        return json($this->lotteryService->resetScreenBackground($this->getAid(), $this->getBid(), $activity_id));
    }
}
