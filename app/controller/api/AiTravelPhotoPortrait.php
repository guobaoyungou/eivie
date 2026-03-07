<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\AiTravelPhotoPortraitService;
use think\App;
use think\Response;

/**
 * 人像管理API控制器
 * 用于旅拍照片的上传和管理
 */
class AiTravelPhotoPortrait extends BaseController
{
    protected $portraitService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->portraitService = new AiTravelPhotoPortraitService();
    }

    /**
     * 上传人像
     * POST /api/ai_travel_photo/portrait/upload
     * 
     * @return Response
     */
    public function upload(): Response
    {
        try {
            // 验证设备Token
            $token = $this->request->header('Device-Token');
            if (empty($token)) {
                return json(['code' => 401, 'msg' => '缺少设备Token']);
            }
            
            // 验证上传文件
            $file = $this->request->file('image');
            if (!$file) {
                return json(['code' => 400, 'msg' => '请上传图片文件']);
            }
            
            // 验证文件类型
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!in_array($file->getMime(), $allowedTypes)) {
                return json(['code' => 400, 'msg' => '仅支持JPG、PNG格式图片']);
            }
            
            // 验证文件大小（最大10MB）
            if ($file->getSize() > 10 * 1024 * 1024) {
                return json(['code' => 400, 'msg' => '图片大小不能超过10MB']);
            }
            
            // 获取其他参数
            $params = $this->request->post();
            $params['device_token'] = $token;
            
            // 获取文件信息
            $fileInfo = [
                'name' => $file->getOriginalName(),
                'tmp_name' => $file->getRealPath(),
                'type' => $file->getMime(),
                'size' => $file->getSize(),
            ];
            
            // 调用服务层上传
            $result = $this->portraitService->uploadPortrait($fileInfo, $params);
            
            return json([
                'code' => 200,
                'msg' => $result['status'] == 'exists' ? '图片已存在' : '上传成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '上传失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 获取人像列表
     * GET /api/ai_travel_photo/portrait/list
     * 
     * @return Response
     */
    public function getList(): Response
    {
        try {
            $params = $this->request->get();
            
            $result = $this->portraitService->getPortraitList($params);
            
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
     * 获取人像详情
     * GET /api/ai_travel_photo/portrait/detail
     * 
     * @return Response
     */
    public function detail(): Response
    {
        try {
            $portraitId = (int)$this->request->get('portrait_id', 0);
            
            if ($portraitId <= 0) {
                return json(['code' => 400, 'msg' => '人像ID不能为空']);
            }
            
            $result = $this->portraitService->getPortraitDetail($portraitId);
            
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
     * 删除人像
     * POST /api/ai_travel_photo/portrait/delete
     * 
     * @return Response
     */
    public function delete(): Response
    {
        try {
            $portraitId = (int)$this->request->post('portrait_id', 0);
            
            if ($portraitId <= 0) {
                return json(['code' => 400, 'msg' => '人像ID不能为空']);
            }
            
            $result = $this->portraitService->deletePortrait($portraitId);
            
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
}
