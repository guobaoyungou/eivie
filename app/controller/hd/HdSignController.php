<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdSignService;
use app\model\hd\HdParticipant;
use think\facade\Db;

/**
 * 大屏互动 - 签到管理控制器
 */
class HdSignController extends HdBaseController
{
    protected $signService;

    protected function initialize()
    {
        $this->signService = new HdSignService();
    }

    /** 获取签到设置 */
    public function config(int $activity_id)
    {
        return json($this->signService->getSignConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 更新签到设置 */
    public function updateConfig(int $activity_id)
    {
        return json($this->signService->updateSignConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    /** 签到名单 */
    public function signList(int $activity_id)
    {
        $params = [
            'flag'    => input('get.flag', ''),
            'keyword' => input('get.keyword', ''),
            'page'    => input('get.page', 1),
            'limit'   => input('get.limit', 50),
        ];
        return json($this->signService->getSignList($this->getAid(), $this->getBid(), $activity_id, $params));
    }

    /** 删除签到记录 */
    public function deleteParticipant(int $activity_id, int $id)
    {
        return json($this->signService->deleteParticipant($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    /** 清空签到名单 */
    public function clearSignList(int $activity_id)
    {
        return json($this->signService->clearSignList($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 获取手机页面设计 */
    public function mobileConfig(int $activity_id)
    {
        return json($this->signService->getMobilePageConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 更新手机页面设计 */
    public function updateMobileConfig(int $activity_id)
    {
        return json($this->signService->updateMobilePageConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    /** 获取大屏密码配置 */
    public function screenPasswordConfig(int $activity_id)
    {
        return json($this->signService->getScreenPasswordConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 更新大屏密码配置 */
    public function updateScreenPasswordConfig(int $activity_id)
    {
        return json($this->signService->updateScreenPasswordConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    /** 切换参与者管理员状态 */
    public function toggleAdmin(int $activity_id, int $id)
    {
        $participant = HdParticipant::where('aid', $this->getAid())
            ->where('bid', $this->getBid())
            ->where('activity_id', $activity_id)
            ->where('id', $id)
            ->find();

        if (!$participant) {
            return json(['code' => 1, 'msg' => '记录不存在']);
        }

        // 支持批量操作时的action参数
        $action = request()->param('action');
        if ($action === 'add') {
            $participant->is_admin = 1;
        } elseif ($action === 'remove') {
            $participant->is_admin = 0;
        } else {
            // 默认切换状态
            $participant->is_admin = $participant->is_admin ? 0 : 1;
        }
        
        $participant->save();

        return json([
            'code' => 0,
            'msg'  => $participant->is_admin ? '已设为管理员' : '已取消管理员',
            'data' => ['is_admin' => $participant->is_admin],
        ]);
    }

    /** 切换参与者核销员状态 */
    public function toggleVerifier(int $activity_id, int $id)
    {
        $participant = HdParticipant::where('aid', $this->getAid())
            ->where('bid', $this->getBid())
            ->where('activity_id', $activity_id)
            ->where('id', $id)
            ->find();

        if (!$participant) {
            return json(['code' => 1, 'msg' => '记录不存在']);
        }

        // 支持批量操作时的action参数
        $action = request()->param('action');
        if ($action === 'add') {
            $participant->is_verifier = 1;
        } elseif ($action === 'remove') {
            $participant->is_verifier = 0;
        } else {
            // 默认切换状态
            $participant->is_verifier = $participant->is_verifier ? 0 : 1;
        }
        
        $participant->save();

        return json([
            'code' => 0,
            'msg'  => $participant->is_verifier ? '已设为核销员' : '已取消核销员',
            'data' => ['is_verifier' => $participant->is_verifier],
        ]);
    }

    /** 获取白名单列表 */
    public function whitelist(int $activity_id)
    {
        $page = request()->param('page', 1, 'intval');
        $limit = request()->param('limit', 20, 'intval');
        $search = request()->param('search', '');
        
        $where = [
            ['aid', '=', $this->getAid()],
            ['bid', '=', $this->getBid()],
            ['activity_id', '=', $activity_id],
        ];
        
        if (!empty($search)) {
            $where[] = ['name|phone', 'like', "%{$search}%"];
        }
        
        try {
            // 使用huodong数据库连接，使用完整表名（带前缀）
            $query = Db::connect('huodong')->table('ddwx_hd_whitelist')->where($where)->order('id', 'desc');
            $total = $query->count();
            $list = $query->page($page, $limit)->select();
            
            return json([
                'code' => 0,
                'msg' => '获取成功',
                'data' => [
                    'list' => $list ?: [],
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '获取失败: ' . $e->getMessage()]);
        }
    }

    /** 保存白名单（新增或编辑） */
    public function saveWhitelist(int $activity_id)
    {
        $id = request()->param('id', 0, 'intval');
        $name = request()->param('name', '');
        $phone = request()->param('phone', '');
        $company = request()->param('company', '');
        $position = request()->param('position', '');
        
        if (empty($name) || empty($phone)) {
            return json(['code' => 1, 'msg' => '姓名和手机号不能为空']);
        }
        
        // 验证手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return json(['code' => 1, 'msg' => '手机号格式不正确']);
        }
        
        try {
            if ($id > 0) {
                // 编辑
                $result = Db::connect('huodong')->table('ddwx_hd_whitelist')
                    ->where([
                        'id' => $id,
                        'aid' => $this->getAid(),
                        'bid' => $this->getBid(),
                        'activity_id' => $activity_id,
                    ])
                    ->update([
                        'name' => $name,
                        'phone' => $phone,
                        'company' => $company,
                        'position' => $position,
                    ]);
                
                if ($result) {
                    return json(['code' => 0, 'msg' => '编辑成功']);
                } else {
                    return json(['code' => 1, 'msg' => '编辑失败']);
                }
            } else {
                // 新增 - 检查是否已存在
                $exists = Db::connect('huodong')->table('ddwx_hd_whitelist')
                    ->where([
                        'activity_id' => $activity_id,
                        'phone' => $phone,
                    ])
                    ->find();
                
                if ($exists) {
                    return json(['code' => 1, 'msg' => '该手机号已存在']);
                }
                
$result = Db::connect('huodong')->table('ddwx_hd_whitelist')->insert([
                        'aid' => $this->getAid(),
                        'bid' => $this->getBid(),
                        'activity_id' => $activity_id,
                        'name' => $name,
                        'phone' => $phone,
                        'company' => $company,
                        'position' => $position,
                        'added_time' => date('Y-m-d H:i:s'),
                    ]);
                
                if ($result) {
                    return json(['code' => 0, 'msg' => '添加成功']);
                } else {
                    return json(['code' => 1, 'msg' => '添加失败']);
                }
            }
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '操作失败: ' . $e->getMessage()]);
        }
    }

    /** 更新白名单（兼容POST方式） */
    public function updateWhitelist(int $activity_id, int $id)
    {
        return $this->saveWhitelist($activity_id);
    }

    /** 删除白名单 */
    public function deleteWhitelist(int $activity_id, int $id = 0)
    {
        try {
            if ($id > 0) {
                // 删除单个
                $result = Db::connect('huodong')->table('ddwx_hd_whitelist')
                    ->where([
                        'id' => $id,
                        'aid' => $this->getAid(),
                        'bid' => $this->getBid(),
                        'activity_id' => $activity_id,
                    ])
                    ->delete();
                
                if ($result) {
                    return json(['code' => 0, 'msg' => '删除成功']);
                } else {
                    return json(['code' => 1, 'msg' => '删除失败']);
                }
            } else {
                // 清空白名单（activity_id参数来自路由）
                $result = Db::connect('huodong')->table('ddwx_hd_whitelist')
                    ->where([
                        'aid' => $this->getAid(),
                        'bid' => $this->getBid(),
                        'activity_id' => $activity_id,
                    ])
                    ->delete();
                
                if ($result) {
                    return json(['code' => 0, 'msg' => '清空成功']);
                } else {
                    return json(['code' => 1, 'msg' => '清空失败']);
                }
            }
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '操作失败: ' . $e->getMessage()]);
        }
    }

    /** 导入页面 */
    public function import()
    {
        $activity_id = request()->param('activity_id', 0, 'intval');
        if (!$activity_id) {
            return json(['code' => 1, 'msg' => '请指定活动ID']);
        }
        
        // 提供导入页面或返回导入配置
        return json([
            'code' => 0,
            'msg' => '导入功能准备就绪',
            'data' => [
                'activity_id' => $activity_id,
                'template_url' => '/huodong/admin/assets/templates/sign-import-template.csv',
            ]
        ]);
    }

    /** 执行导入 */
    public function doImport()
    {
        try {
            $activity_id = request()->param('activity_id', 0, 'intval');
            if (!$activity_id) {
                return json(['code' => 1, 'msg' => '请指定活动ID']);
            }
            
            // 获取上传的文件
            $file = request()->file('file');
            if (!$file) {
                return json(['code' => 1, 'msg' => '请选择要导入的文件']);
            }
            
            // 验证文件类型
            $ext = $file->getOriginalExtension();
            if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
                return json(['code' => 1, 'msg' => '只支持 CSV、Excel 文件']);
            }
            
            // 保存上传文件
            $savePath = runtime_path() . 'temp/';
            if (!is_dir($savePath)) {
                mkdir($savePath, 0755, true);
            }
            
            $saveName = 'sign_import_' . $activity_id . '_' . time() . '.' . $ext;
            $filePath = $savePath . $saveName;
            $file->move($savePath, $saveName);
            
            // 根据文件类型处理
            $result = $this->processImportFile($filePath, $activity_id, $ext);
            
            // 清理临时文件
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            return json([
                'code' => 0,
                'msg' => '导入完成',
                'data' => [
                    'success' => $result['success'],
                    'fail' => $result['fail'],
                    'total' => $result['success'] + $result['fail'],
                    'errors' => $result['errors'] ?? []
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '导入失败: ' . $e->getMessage()]);
        }
    }

    /** 处理导入文件 */
    private function processImportFile(string $filePath, int $activityId, string $ext): array
    {
        $success = 0;
        $fail = 0;
        $errors = [];
        
        // 如果是CSV文件
        if ($ext === 'csv') {
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                throw new \Exception('无法打开文件');
            }
            
            // 跳过表头
            $header = fgetcsv($handle);
            
            $aid = $this->getAid();
            $bid = $this->getBid();
            
            while (($data = fgetcsv($handle)) !== false) {
                try {
                    if (count($data) < 3) {
                        $fail++;
                        $errors[] = '数据格式不正确: ' . implode(',', $data);
                        continue;
                    }
                    
                    $nickname = trim($data[0] ?? '');
                    $signname = trim($data[1] ?? '');
                    $phone = trim($data[2] ?? '');
                    
                    if (empty($phone)) {
                        $fail++;
                        $errors[] = '手机号不能为空: ' . implode(',', $data);
                        continue;
                    }
                    
                    // 检查手机号是否已存在
                    $exists = Db::connect('huodong')->table('ddwx_hd_participant')
                        ->where([
                            'aid' => $aid,
                            'bid' => $bid,
                            'activity_id' => $activityId,
                            'phone' => $phone,
                        ])
                        ->find();
                    
                    if ($exists) {
                        $fail++;
                        $errors[] = '手机号已存在: ' . $phone;
                        continue;
                    }
                    
                    // 创建新参与者
                    $result = Db::connect('huodong')->table('ddwx_hd_participant')->insert([
                        'aid' => $aid,
                        'bid' => $bid,
                        'mid' => 0, // 默认值
                        'activity_id' => $activityId,
                        'openid' => '', // 导入用户没有openid
                        'nickname' => $nickname ?: '用户' . date('YmdHis'),
                        'avatar' => '', // 无头像
                        'signname' => $signname ?: '',
                        'phone' => $phone,
                        'company' => '',
                        'position' => '',
                        'createtime' => time(),
                        'flag' => 1, // 1:未签到，2:已签到
                        'status' => 1,
                    ]);
                    
                    if ($result) {
                        $success++;
                    } else {
                        $fail++;
                        $errors[] = '插入数据库失败: ' . implode(',', $data);
                    }
                    
                } catch (\Exception $e) {
                    $fail++;
                    $errors[] = '处理数据失败: ' . $e->getMessage();
                }
            }
            
            fclose($handle);
        } else {
            // 对于Excel文件，需要phpoffice/phpexcel包
            throw new \Exception('Excel文件导入功能需要安装PHPExcel扩展');
        }
        
        return ['success' => $success, 'fail' => $fail, 'errors' => $errors];
    }

    /** 导出签到名单 */
    public function export()
    {
        try {
            $activity_id = request()->param('activity_id', 0, 'intval');
            if (!$activity_id) {
                return json(['code' => 1, 'msg' => '请指定活动ID']);
            }
            
            $aid = $this->getAid();
            $bid = $this->getBid();
            
            // 查询参与者数据
            $list = Db::connect('huodong')->table('ddwx_hd_participant')
                ->where([
                    'aid' => $aid,
                    'bid' => $bid,
                    'activity_id' => $activity_id,
                ])
                ->order('id', 'asc')
                ->select();
            
            // CSV头部
            $headers = ['ID', '昵称', '姓名', '手机号', '签到状态', '是否管理员', '是否核销员', '签到序号', '创建时间'];
            $rows = [];
            
            foreach ($list as $item) {
                $rows[] = [
                    $item['id'] ?? '',
                    $item['nickname'] ?? '',
                    $item['signname'] ?? '',
                    $item['phone'] ?? '',
                    ($item['flag'] ?? 1) == 2 ? '已签到' : '未签到',
                    ($item['is_admin'] ?? 0) == 1 ? '是' : '否',
                    ($item['is_verifier'] ?? 0) == 1 ? '是' : '否',
                    $item['signorder'] ?? '',
                    $item['createtime'] ? date('Y-m-d H:i:s', $item['createtime']) : '',
                ];
            }
            
            // 输出CSV
            $activityTitle = Db::connect('huodong')->table('ddwx_hd_activity')
                ->where('id', $activity_id)
                ->value('title') ?? '活动' . $activity_id;
            
            $filename = '签到名单_' . $activityTitle . '_' . date('YmdHis');
            return $this->exportCsv($filename, $headers, $rows);
            
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '导出失败: ' . $e->getMessage()]);
        }
    }

    /** CSV导出辅助方法 */
    private function exportCsv(string $filename, array $headers, array $rows)
    {
        $filename = str_replace(['"', '/', '\\'], '', $filename);
        
        // BOM + CSV
        $output = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility
        $output .= implode(',', array_map([$this, 'csvEscape'], $headers)) . "\n";
        
        foreach ($rows as $row) {
            $output .= implode(',', array_map([$this, 'csvEscape'], $row)) . "\n";
        }
        
        // 设置响应头
        return response($output, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    }

    /** CSV字段转义 */
    private function csvEscape($value): string
    {
        $value = (string)$value;
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }

    /** 清空白名单 */
    public function clearWhitelist(int $activity_id)
    {
        return $this->deleteWhitelist($activity_id, 0);
    }
}
