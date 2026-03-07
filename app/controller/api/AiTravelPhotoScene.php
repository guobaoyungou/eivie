<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\AiTravelPhotoSceneService;
use think\App;
use think\Response;

/**
 * 场景管理API控制器
 */
class AiTravelPhotoScene extends BaseController
{
    protected $sceneService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->sceneService = new AiTravelPhotoSceneService();
    }

    /**
     * 获取场景列表
     * GET /api/ai_travel_photo/scene/list
     * 
     * @return Response
     */
    public function getList(): Response
    {
        try {
            $params = $this->request->get();
            
            // 标记为C端请求，仅返回公共场景
            $params['is_client'] = true;
            
            // 只返回上架的场景
            $params['status'] = 1;
            
            $result = $this->sceneService->getSceneList($params);
            
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
     * 获取场景详情
     * GET /api/ai_travel_photo/scene/detail
     * 
     * @return Response
     */
    public function detail(): Response
    {
        try {
            $sceneId = (int)$this->request->get('scene_id', 0);
            
            if ($sceneId <= 0) {
                return json(['code' => 400, 'msg' => '场景ID不能为空']);
            }
            
            // C端访问，需要校验权限
            $result = $this->sceneService->getSceneDetail($sceneId, true);
            
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
     * 获取热门场景
     * GET /api/ai_travel_photo/scene/hot
     * 
     * @return Response
     */
    public function hot(): Response
    {
        try {
            $bid = (int)$this->request->get('bid', 0);
            $limit = (int)$this->request->get('limit', 10);
            
            $result = $this->sceneService->getHotScenes($bid, $limit);
            
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
     * 获取推荐场景
     * GET /api/ai_travel_photo/scene/recommend
     * 
     * @return Response
     */
    public function recommend(): Response
    {
        try {
            $bid = (int)$this->request->get('bid', 0);
            $limit = (int)$this->request->get('limit', 6);
            
            $result = $this->sceneService->getRecommendScenes($bid, $limit);
            
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
     * 获取场景分类
     * GET /api/ai_travel_photo/scene/categories
     * 
     * @return Response
     */
    public function categories(): Response
    {
        try {
            $bid = (int)$this->request->get('bid', 0);
            
            $result = $this->sceneService->getCategories($bid);
            
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
