<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\AiTravelPhotoAlbumService;
use think\App;
use think\Response;

/**
 * 用户相册API控制器
 */
class AiTravelPhotoAlbum extends BaseController
{
    protected $albumService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->albumService = new AiTravelPhotoAlbumService();
    }

    /**
     * 获取相册列表
     * GET /api/ai_travel_photo/album/list
     * 
     * @return Response
     */
    public function getList(): Response
    {
        try {
            $uid = (int)$this->request->get('uid', 0);
            
            if ($uid <= 0) {
                return json(['code' => 401, 'msg' => '请先登录']);
            }
            
            $params = $this->request->get();
            
            $result = $this->albumService->getAlbumList($uid, $params);
            
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取相册详情
     * GET /api/ai_travel_photo/album/detail
     * 
     * @return Response
     */
    public function detail(): Response
    {
        try {
            $albumId = (int)$this->request->get('album_id', 0);
            $uid = (int)$this->request->get('uid', 0);
            
            if ($uid <= 0) {
                return json(['code' => 401, 'msg' => '请先登录']);
            }
            
            if ($albumId <= 0) {
                return json(['code' => 400, 'msg' => '相册ID不能为空']);
            }
            
            $result = $this->albumService->getAlbumDetail($albumId, $uid);
            
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 下载照片
     * GET /api/ai_travel_photo/album/download
     * 
     * @return Response
     */
    public function download(): Response
    {
        try {
            $albumId = (int)$this->request->get('album_id', 0);
            $uid = (int)$this->request->get('uid', 0);
            
            if ($uid <= 0) {
                return json(['code' => 401, 'msg' => '请先登录']);
            }
            
            if ($albumId <= 0) {
                return json(['code' => 400, 'msg' => '相册ID不能为空']);
            }
            
            $result = $this->albumService->downloadPhoto($albumId, $uid);
            
            return json([
                'code' => 200,
                'msg' => '获取下载链接成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 批量下载
     * POST /api/ai_travel_photo/album/batch_download
     * 
     * @return Response
     */
    public function batchDownload(): Response
    {
        try {
            $albumIds = $this->request->post('album_ids', []);
            $uid = (int)$this->request->post('uid', 0);
            
            if ($uid <= 0) {
                return json(['code' => 401, 'msg' => '请先登录']);
            }
            
            if (empty($albumIds)) {
                return json(['code' => 400, 'msg' => '请选择要下载的照片']);
            }
            
            $result = $this->albumService->batchDownload($albumIds, $uid);
            
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 生成分享链接
     * POST /api/ai_travel_photo/album/share
     * 
     * @return Response
     */
    public function share(): Response
    {
        try {
            $albumId = (int)$this->request->post('album_id', 0);
            $uid = (int)$this->request->post('uid', 0);
            $expireTime = (int)$this->request->post('expire_time', 86400);
            
            if ($uid <= 0) {
                return json(['code' => 401, 'msg' => '请先登录']);
            }
            
            if ($albumId <= 0) {
                return json(['code' => 400, 'msg' => '相册ID不能为空']);
            }
            
            $result = $this->albumService->generateShareLink($albumId, $uid, $expireTime);
            
            return json([
                'code' => 200,
                'msg' => '分享链接生成成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 查看分享的相册
     * GET /api/ai_travel_photo/album/shared
     * 
     * @return Response
     */
    public function shared(): Response
    {
        try {
            $shareToken = $this->request->get('token', '');
            
            if (empty($shareToken)) {
                return json(['code' => 400, 'msg' => '分享token不能为空']);
            }
            
            $result = $this->albumService->getSharedAlbum($shareToken);
            
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 设置收藏
     * POST /api/ai_travel_photo/album/favorite
     * 
     * @return Response
     */
    public function setFavorite(): Response
    {
        try {
            $albumId = (int)$this->request->post('album_id', 0);
            $uid = (int)$this->request->post('uid', 0);
            $isFavorite = (bool)$this->request->post('is_favorite', true);
            
            if ($uid <= 0) {
                return json(['code' => 401, 'msg' => '请先登录']);
            }
            
            if ($albumId <= 0) {
                return json(['code' => 400, 'msg' => '相册ID不能为空']);
            }
            
            $result = $this->albumService->setFavorite($albumId, $uid, $isFavorite);
            
            return json([
                'code' => 200,
                'msg' => $isFavorite ? '收藏成功' : '取消收藏成功',
                'data' => ['result' => $result]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 删除相册
     * POST /api/ai_travel_photo/album/delete
     * 
     * @return Response
     */
    public function delete(): Response
    {
        try {
            $albumId = (int)$this->request->post('album_id', 0);
            $uid = (int)$this->request->post('uid', 0);
            
            if ($uid <= 0) {
                return json(['code' => 401, 'msg' => '请先登录']);
            }
            
            if ($albumId <= 0) {
                return json(['code' => 400, 'msg' => '相册ID不能为空']);
            }
            
            $result = $this->albumService->deleteAlbum($albumId, $uid);
            
            return json([
                'code' => 200,
                'msg' => '删除成功',
                'data' => ['result' => $result]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取相册统计
     * GET /api/ai_travel_photo/album/stats
     * 
     * @return Response
     */
    public function stats(): Response
    {
        try {
            $uid = (int)$this->request->get('uid', 0);
            
            if ($uid <= 0) {
                return json(['code' => 401, 'msg' => '请先登录']);
            }
            
            $result = $this->albumService->getAlbumStats($uid);
            
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }
}
