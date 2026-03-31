<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdAlbumService;

/**
 * 大屏互动 - 相册PPT控制器
 */
class HdAlbumController extends HdBaseController
{
    protected $albumService;

    protected function initialize()
    {
        $this->albumService = new HdAlbumService();
    }

    /** 获取相册设置 */
    public function config(int $activity_id)
    {
        return json($this->albumService->getAlbumConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 更新相册设置 */
    public function updateConfig(int $activity_id)
    {
        return json($this->albumService->updateAlbumConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    /** 照片列表 */
    public function photos(int $activity_id)
    {
        $params = [
            'page'  => input('get.page', 1),
            'limit' => input('get.limit', 50),
        ];
        return json($this->albumService->getPhotos($this->getAid(), $this->getBid(), $activity_id, $params));
    }

    /** 添加照片 */
    public function addPhoto(int $activity_id)
    {
        return json($this->albumService->addPhoto($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    /** 批量添加照片 */
    public function batchAddPhotos(int $activity_id)
    {
        $photos = input('post.photos/a', []);
        return json($this->albumService->batchAddPhotos($this->getAid(), $this->getBid(), $activity_id, $photos));
    }

    /** 删除照片 */
    public function deletePhoto(int $activity_id, int $id)
    {
        return json($this->albumService->deletePhoto($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    /** 清空相册 */
    public function clearAlbum(int $activity_id)
    {
        return json($this->albumService->clearAlbum($this->getAid(), $this->getBid(), $activity_id));
    }
}
