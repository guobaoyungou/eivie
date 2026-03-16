<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\AiTravelPhotoDeviceService;
use think\App;
use think\Response;

/**
 * 设备管理API控制器
 * 用于旅拍设备的注册、认证、心跳等操作
 */
class AiTravelPhotoDevice extends BaseController
{
    protected $deviceService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->deviceService = new AiTravelPhotoDeviceService();
    }

    /**
     * 设备注册
     * POST /api/ai_travel_photo/device/register
     * 
     * @return Response
     */
    public function register(): Response
    {
        try {
            $params = $this->request->post();
            
            // 必填参数验证
            if (empty($params['device_code'])) {
                return json(['code' => 400, 'msg' => '设备编码不能为空']);
            }
            
            if (empty($params['bid'])) {
                return json(['code' => 400, 'msg' => '商家ID不能为空']);
            }
            
            // 调用服务层
            $result = $this->deviceService->register($params);
            
            return json([
                'code' => 200,
                'msg' => '设备注册成功',
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
     * 设备心跳
     * POST /api/ai_travel_photo/device/heartbeat
     * 
     * @return Response
     */
    public function heartbeat(): Response
    {
        try {
            $token = $this->request->header('Device-Token');
            
            if (empty($token)) {
                return json(['code' => 401, 'msg' => '缺少设备Token']);
            }
            
            // 验证Token
            $device = $this->deviceService->verifyToken($token);
            if (!$device) {
                return json(['code' => 401, 'msg' => '设备Token无效']);
            }
            
            // 获取心跳数据
            $params = $this->request->post();
            
            // 更新心跳
            $result = $this->deviceService->heartbeat($device['device_id'], $params);
            
            return json([
                'code' => 200,
                'msg' => '心跳成功',
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
     * 获取设备配置
     * GET /api/ai_travel_photo/device/config
     * 
     * @return Response
     */
    public function config(): Response
    {
        try {
            $token = $this->request->header('Device-Token');
            
            if (empty($token)) {
                return json(['code' => 401, 'msg' => '缺少设备Token']);
            }
            
            // 验证Token
            $device = $this->deviceService->verifyToken($token);
            if (!$device) {
                return json(['code' => 401, 'msg' => '设备Token无效']);
            }
            
            // 获取配置
            $config = $this->deviceService->getConfig($device['device_id']);
            
            return json([
                'code' => 200,
                'msg' => '获取配置成功',
                'data' => $config
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 设备信息
     * GET /api/ai_travel_photo/device/info
     * 
     * @return Response
     */
    public function info(): Response
    {
        try {
            $token = $this->request->header('Device-Token');
            
            if (empty($token)) {
                return json(['code' => 401, 'msg' => '缺少设备Token']);
            }
            
            // 验证Token
            $device = $this->deviceService->verifyToken($token);
            if (!$device) {
                return json(['code' => 401, 'msg' => '设备Token无效']);
            }
            
            // 获取详情
            $detail = $this->deviceService->getDeviceDetail($device['device_id']);
            
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $detail
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 上传人像（核心功能）
     * POST /api/ai_travel_photo/device/upload
     * 
     * @return Response
     */
    public function upload(): Response
    {
        try {
            // 1. 验证设备Token
            $token = $this->request->param('token', '');
            if (empty($token)) {
                $token = $this->request->header('Device-Token');
            }
            
            if (empty($token)) {
                return json(['code' => 401, 'msg' => '缺少设备Token']);
            }
            
            // 验证Token
            $device = $this->deviceService->verifyToken($token);
            if (!$device) {
                return json(['code' => 401, 'msg' => '设备Token无效']);
            }
            
            // 2. 获取上传文件
            $file = $this->request->file('file');
            if (!$file) {
                return json(['code' => 400, 'msg' => '请上传图片文件']);
            }
            
            // 3. 验证文件类型
            $allowedExt = ['jpg', 'jpeg', 'png'];
            if (!in_array(strtolower($file->extension()), $allowedExt)) {
                return json(['code' => 400, 'msg' => '只支持jpg、jpeg、png格式']);
            }
            
            // 4. 验证文件大小
            if ($file->getSize() > 10 * 1024 * 1024) {
                return json(['code' => 400, 'msg' => '图片大小不能超过10MB']);
            }
            
            // 5. 获取额外参数
            $aid = $device['aid'];
            $bid = $device['bid'];
            $mdid = $device['mdid'];
            $device_id = $device['device_id'];
            $md5 = $this->request->param('md5', '');
            
            // 6. 检查MD5去重
            if ($md5) {
                $exists = \think\facade\Db::name('ai_travel_photo_portrait')
                    ->where('aid', $aid)
                    ->where('bid', $bid)
                    ->where('md5', $md5)
                    ->find();
                    
                if ($exists) {
                    return json([
                        'code' => 200,
                        'msg' => '图片已存在，自动去重',
                        'data' => [
                            'portrait_id' => $exists['id'],
                            'is_duplicate' => true
                        ]
                    ]);
                }
            }
            
            // 7. 上传文件到OSS
            $filename = date('Ymd') . '/' . uniqid() . '.' . $file->extension();
            $ossPathPrefix = config('ai_travel_photo.oss.ai_travel_photo_path', 'ai_travel_photo/');
            $ossPath = $ossPathPrefix . 'originals/' . $filename;

            // 这里先保存到本地，实际需要上传到OSS
            $savePath = app()->getRuntimePath() . 'uploads/' . date('Ymd');
            if (!is_dir($savePath)) {
                mkdir($savePath, 0755, true);
            }
            $file->move($savePath, $filename);
            $localPath = $savePath . '/' . $filename;

            // TODO: 上传到OSS
            $ossUrl = '/uploads/' . date('Ymd') . '/' . $filename; // 暂时使用本地路径
            
            // 8. 保存人像记录
            $portraitId = \think\facade\Db::name('ai_travel_photo_portrait')->insertGetId([
                'aid' => $aid,
                'bid' => $bid,
                'mdid' => $mdid,
                'device_id' => $device_id,
                'uid' => 0, // 默认为0，后续可绑定用户
                'type' => 1, // 1=图片
                'original_url' => $ossUrl,
                'file_name' => $file->getOriginalName(),
                'file_size' => $file->getSize(),
                'width' => 0,
                'height' => 0,
                'md5' => $md5 ?: md5_file($localPath),
                'status' => 1,
                'create_time' => time(),
                'update_time' => time()
            ]);
            
            // 9. 投递抠图任务
            try {
                \think\facade\Queue::push('app\\job\\CutoutJob', [
                    'portrait_id' => $portraitId,
                    'aid' => $aid,
                    'bid' => $bid
                ], 'ai_cutout');
            } catch (\Exception $e) {
                // 队列投递失败不影响主流程
                \think\facade\Log::error('Queue push failed: ' . $e->getMessage());
            }
            
            return json([
                'code' => 200,
                'msg' => '上传成功',
                'data' => [
                    'portrait_id' => $portraitId,
                    'original_url' => $ossUrl,
                    'is_duplicate' => false
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '上传失败：' . $e->getMessage()
            ]);
        }
    }
}
