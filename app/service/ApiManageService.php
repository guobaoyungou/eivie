<?php
/**
 * API管理服务层
 * 处理API接口的扫描、查询、测试等业务逻辑
 */

namespace app\service;

use think\facade\Db;
use think\facade\Cache;
use ReflectionClass;
use ReflectionMethod;

class ApiManageService
{
    /**
     * 获取接口列表
     */
    public function getInterfaceList($aid, $page = 1, $limit = 20, $filters = [])
    {
        try {
            $where = [['aid', '=', $aid]];
            
            // 分类筛选
            if (!empty($filters['category'])) {
                $where[] = ['category', '=', $filters['category']];
            }
            
            // 关键词搜索
            if (!empty($filters['keyword'])) {
                $where[] = ['name|path', 'like', '%' . $filters['keyword'] . '%'];
            }
            
            // 请求方式筛选
            if (!empty($filters['method'])) {
                $where[] = ['method', '=', $filters['method']];
            }
            
            // 认证要求筛选
            if ($filters['auth_required'] !== '') {
                $where[] = ['auth_required', '=', $filters['auth_required']];
            }
            
            $list = Db::name('api_interface')
                ->where($where)
                ->order('sort desc, id desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);
            
            return [
                'status' => 1,
                'data' => $list->items(),
                'count' => $list->total()
            ];
        } catch (\Exception $e) {
            return ['status' => 0, 'msg' => '查询失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取接口分类列表
     */
    public function getCategories($aid)
    {
        try {
            $categories = Db::name('api_interface')
                ->where('aid', $aid)
                ->where('category', '<>', '')
                ->group('category')
                ->column('category');
            
            return $categories;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 获取接口详情
     */
    public function getInterfaceDetail($aid, $id)
    {
        try {
            $detail = Db::name('api_interface')
                ->where('aid', $aid)
                ->where('id', $id)
                ->find();
            
            if ($detail) {
                // 解析JSON字段
                $detail['request_params'] = $detail['request_params'] ? json_decode($detail['request_params'], true) : [];
                $detail['response_example'] = $detail['response_example'] ? json_decode($detail['response_example'], true) : [];
            }
            
            return $detail;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 更新接口信息
     */
    public function updateInterface($aid, $id, $data)
    {
        try {
            $updateData = [
                'name' => $data['name'],
                'category' => $data['category'],
                'description' => $data['description'],
                'tags' => $data['tags'],
                'remark' => $data['remark'],
                'status' => $data['status'],
                'auth_required' => $data['auth_required'],
                'sort' => $data['sort'],
                'update_time' => time()
            ];
            
            // 如果提供了参数和响应示例，进行JSON编码
            if (!empty($data['request_params'])) {
                $updateData['request_params'] = is_array($data['request_params']) ? 
                    json_encode($data['request_params'], JSON_UNESCAPED_UNICODE) : 
                    $data['request_params'];
            }
            
            if (!empty($data['response_example'])) {
                $updateData['response_example'] = is_array($data['response_example']) ? 
                    json_encode($data['response_example'], JSON_UNESCAPED_UNICODE) : 
                    $data['response_example'];
            }
            
            $result = Db::name('api_interface')
                ->where('aid', $aid)
                ->where('id', $id)
                ->update($updateData);
            
            if ($result !== false) {
                return ['status' => 1, 'msg' => '更新成功'];
            }
            
            return ['status' => 0, 'msg' => '更新失败'];
        } catch (\Exception $e) {
            return ['status' => 0, 'msg' => '更新失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取可扫描的控制器列表
     */
    public function getControllersForScan()
    {
        $controllers = [];
        // 兼容独立脚本和框架调用
        $controllerPath = function_exists('app_path') ? app_path() . 'controller/' : __DIR__ . '/../controller/';
        
        try {
            $this->scanDirectory($controllerPath, 'app\\controller', $controllers);
            return $controllers;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 递归扫描目录
     */
    private function scanDirectory($path, $namespace, &$controllers)
    {
        if (!is_dir($path)) {
            return;
        }
        
        $files = scandir($path);
        
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            
            $filePath = $path . $file;
            
            if (is_dir($filePath)) {
                $this->scanDirectory($filePath . '/', $namespace . '\\' . $file, $controllers);
            } elseif (pathinfo($file, PATHINFO_EXTENSION) == 'php') {
                $className = pathinfo($file, PATHINFO_FILENAME);
                $fullClassName = $namespace . '\\' . $className;
                
                // 扫描Api开头的控制器 或 AiTravelPhoto开头的控制器
                if (strpos($className, 'Api') === 0 || strpos($className, 'AiTravelPhoto') === 0) {
                    $controllers[] = [
                        'name' => $className,
                        'class' => $fullClassName,
                        'path' => $filePath
                    ];
                }
            }
        }
    }

    /**
     * 扫描接口
     */
    public function scanInterfaces($aid, $type = 'all', $controllers = [])
    {
        try {
            $newInterfaces = [];
            $updateInterfaces = [];
            $allControllers = $this->getControllersForScan();
            
            // 如果指定了控制器，只扫描指定的
            if (!empty($controllers)) {
                $allControllers = array_filter($allControllers, function($item) use ($controllers) {
                    return in_array($item['name'], $controllers);
                });
            }
            
            foreach ($allControllers as $controller) {
                $interfaces = $this->parseController($controller);
                
                foreach ($interfaces as $interface) {
                    // 检查接口是否已存在
                    $exists = Db::name('api_interface')
                        ->where('aid', $aid)
                        ->where('controller', $interface['controller'])
                        ->where('action', $interface['action'])
                        ->find();
                    
                    if ($exists) {
                        $updateInterfaces[] = array_merge($interface, ['id' => $exists['id']]);
                    } else {
                        $newInterfaces[] = $interface;
                    }
                }
            }
            
            return [
                'status' => 1,
                'msg' => '扫描完成',
                'data' => [
                    'new' => $newInterfaces,
                    'update' => $updateInterfaces,
                    'new_count' => count($newInterfaces),
                    'update_count' => count($updateInterfaces)
                ]
            ];
        } catch (\Exception $e) {
            return ['status' => 0, 'msg' => '扫描失败：' . $e->getMessage()];
        }
    }

    /**
     * 解析控制器
     */
    private function parseController($controller)
    {
        $interfaces = [];
        
        try {
            if (!class_exists($controller['class'])) {
                return $interfaces;
            }
            
            $reflection = new ReflectionClass($controller['class']);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            
            foreach ($methods as $method) {
                // 排除继承的方法和魔术方法
                if ($method->class != $controller['class'] || 
                    strpos($method->name, '__') === 0 ||
                    in_array($method->name, ['initialize', 'getdata', 'middleware'])) {
                    continue;
                }
                
                $docComment = $method->getDocComment();
                $methodName = $method->name;
                
                // 解析注释
                $parsedDoc = $this->parseDocComment($docComment);
                
                // 生成接口路径
                $controllerName = str_replace('app\\controller\\', '', $controller['class']);
                $path = '/' . strtolower(str_replace('\\', '/', $controllerName)) . '/' . $methodName;
                
                // 自动识别分类
                $category = $this->getCategoryByController($controllerName);
                
                $interfaces[] = [
                    'controller' => $controllerName,
                    'action' => $methodName,
                    'name' => $parsedDoc['title'] ?: $methodName,
                    'category' => $category,
                    'method' => $parsedDoc['method'] ?: 'POST',
                    'path' => $path,
                    'description' => $parsedDoc['description'],
                    'auth_required' => $parsedDoc['auth_required'],
                    'request_params' => '',
                    'response_example' => '',
                    'tags' => '',
                    'sort' => 0,
                    'status' => 1
                ];
            }
        } catch (\Exception $e) {
            // 记录错误但继续
        }
        
        return $interfaces;
    }

    /**
     * 解析文档注释
     */
    private function parseDocComment($docComment)
    {
        $result = [
            'title' => '',
            'description' => '',
            'method' => 'POST',
            'auth_required' => 0
        ];
        
        if (!$docComment) {
            return $result;
        }
        
        // 移除注释符号
        $lines = explode("\n", $docComment);
        $cleanLines = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^\/?\*+\/?/', '', $line);
            $line = trim($line);
            
            if (!empty($line)) {
                $cleanLines[] = $line;
            }
        }
        
        // 第一行作为标题
        if (!empty($cleanLines)) {
            $result['title'] = $cleanLines[0];
        }
        
        // 查找请求方式
        foreach ($cleanLines as $line) {
            if (preg_match('/(GET|POST|PUT|DELETE|PATCH)/i', $line, $matches)) {
                $result['method'] = strtoupper($matches[1]);
            }
            
            if (stripos($line, '@auth') !== false || stripos($line, '需要登录') !== false) {
                $result['auth_required'] = 1;
            }
        }
        
        // 其他行作为描述
        if (count($cleanLines) > 1) {
            array_shift($cleanLines);
            $result['description'] = implode("\n", $cleanLines);
        }
        
        return $result;
    }

    /**
     * 根据控制器名称识别分类
     */
    private function getCategoryByController($controllerName)
    {
        $categoryMap = [
            'ApiIndex' => '用户认证',
            'ApiAdminMember' => '会员管理',
            'ApiAdminOrder' => '订单管理',
            'ApiAdminProduct' => '商品管理',
            'ApiAdminFinance' => '财务管理',
            'ApiAdminHexiao' => '核销管理',
            'ApiAdminForm' => '表单管理',
            'ApiAdminKefu' => '客服管理',
            'ApiAdminMaidan' => '买单管理',
            'ApiAdminBusiness' => '商户管理',
            'ApiAdminIndex' => '管理后台',
            'AiTravelPhotoScene' => 'AI旅拍-场景',
            'AiTravelPhotoOrder' => 'AI旅拍-订单',
            'AiTravelPhotoAlbum' => 'AI旅拍-相册',
            'AiTravelPhotoDevice' => 'AI旅拍-设备',
            'AiTravelPhotoPortrait' => 'AI旅拍-人像',
            'AiTravelPhotoQrcode' => 'AI旅拍-二维码'
        ];
        
        foreach ($categoryMap as $key => $value) {
            if (strpos($controllerName, $key) !== false) {
                return $value;
            }
        }
        
        return '其他';
    }

    /**
     * 保存扫描结果
     */
    public function saveScanResults($aid, $interfaces)
    {
        Db::startTrans();
        
        try {
            $newCount = 0;
            $updateCount = 0;
            
            foreach ($interfaces as $interface) {
                try {
                    $interface['aid'] = $aid;
                    
                    if (isset($interface['id']) && $interface['id']) {
                        // 更新现有接口（只更新部分字段）
                        $updateData = [
                            'path' => $interface['path'],
                            'method' => $interface['method'],
                            'update_time' => time()
                        ];
                        
                        Db::name('api_interface')
                            ->where('id', $interface['id'])
                            ->update($updateData);
                        
                        $updateCount++;
                    } else {
                        // 新增接口 - 只保留表中存在的字段
                        $insertData = [
                            'aid' => $aid,
                            'controller' => $interface['controller'] ?? '',
                            'action' => $interface['action'] ?? '',
                            'name' => $interface['name'] ?? '',
                            'category' => $interface['category'] ?? '',
                            'method' => $interface['method'] ?? 'POST',
                            'path' => $interface['path'] ?? '',
                            'description' => $interface['description'] ?? '',
                            'request_params' => $interface['request_params'] ?? '',
                            'response_example' => $interface['response_example'] ?? '',
                            'auth_required' => isset($interface['auth_required']) ? intval($interface['auth_required']) : 0,
                            'status' => isset($interface['status']) ? intval($interface['status']) : 1,
                            'tags' => $interface['tags'] ?? '',
                            'sort' => isset($interface['sort']) ? intval($interface['sort']) : 0,
                            'remark' => $interface['remark'] ?? '',
                            'create_time' => time(),
                            'update_time' => time()
                        ];
                        
                        Db::name('api_interface')->insert($insertData);
                        $newCount++;
                    }
                } catch (\Exception $e) {
                    \think\facade\Log::error('保存单个接口失败', [
                        'interface' => $interface,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e; // 重新抛出异常以触发事务回滚
                }
            }
            
            Db::commit();
            
            return [
                'status' => 1,
                'msg' => "保存成功，新增{$newCount}个，更新{$updateCount}个"
            ];
        } catch (\Exception $e) {
            Db::rollback();
            
            \think\facade\Log::error('保存扫描结果失败', [
                'aid' => $aid,
                'interfaces_count' => count($interfaces),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['status' => 0, 'msg' => '保存失败：' . $e->getMessage()];
        }
    }

    /**
     * 测试接口
     */
    public function testInterface($aid, $uid, $interfaceId, $params)
    {
        $startTime = microtime(true);
        
        try {
            // 获取接口信息
            $interface = Db::name('api_interface')
                ->where('aid', $aid)
                ->where('id', $interfaceId)
                ->find();
            
            if (!$interface) {
                return ['status' => 0, 'msg' => '接口不存在'];
            }
            
            // 构建请求URL
            $path = $interface['path'];
            
            // 如果路径不是/开头，添加/
            if (strpos($path, '/') !== 0) {
                $path = '/' . $path;
            }
            
            // 如果路径是类似 api/AiTravelPhotoDevice/register 格式，转换为 ThinkPHP 路由
            if (preg_match('/^\/?api\/([A-Z][a-zA-Z0-9]+)\/([a-z][a-zA-Z0-9]*)$/', $path, $matches)) {
                // 转换为 ?s=/控制器/方法 格式
                $path = '/?s=/api/' . $matches[1] . '/' . $matches[2];
            }
            
            $url = request()->domain() . $path;
            
            \think\facade\Log::info('API测试开始', [
                'interface_id' => $interfaceId,
                'interface_name' => $interface['name'],
                'original_path' => $interface['path'],
                'final_url' => $url,
                'method' => $interface['method'],
                'params' => $params
            ]);
            
            // 发起HTTP请求
            $response = $this->sendHttpRequest($url, $interface['method'], $params);
            
            // 计算响应时间
            $responseTime = intval((microtime(true) - $startTime) * 1000);
            
            // 特殊处理404错误
            if ($response['status_code'] == 404) {
                // 记录测试日志
                $this->saveTestLog($aid, $uid, $interfaceId, $params, $response, $responseTime, 404);
                
                return [
                    'status' => 0,
                    'msg' => '接口路径未找到（404）',
                    'data' => [
                        'response' => [
                            'status_code' => 404,
                            'body' => "📋 API接口文档记录\n\n" .
                                     "接口名称：{$interface['name']}\n" .
                                     "接口路径：{$interface['path']}\n" .
                                     "请求方式：{$interface['method']}\n" .
                                     "分类：{$interface['category']}\n\n" .
                                     "⚠️ 提示：\n" .
                                     "1. 此接口路径在系统中不存在（返回404）\n" .
                                     "2. 可能原因：接口尚未部署、路由未配置、路径记录错误\n" .
                                     "3. 这是API文档管理功能，用于记录和管理接口信息\n" .
                                     "4. 如需真实调用，请确保对应的控制器和路由已配置\n\n" .
                                     "实际请求URL：{$url}\n" .
                                     "原始响应：{$response['body']}",
                            'json' => null
                        ],
                        'response_time' => $responseTime
                    ]
                ];
            }
            
            // 记录测试日志
            $this->saveTestLog($aid, $uid, $interfaceId, $params, $response, $responseTime, $response['status_code']);
            
            return [
                'status' => 1,
                'msg' => '测试完成',
                'data' => [
                    'response' => $response,
                    'response_time' => $responseTime
                ]
            ];
        } catch (\Exception $e) {
            $responseTime = intval((microtime(true) - $startTime) * 1000);
            
            // 记录失败日志
            $this->saveTestLog($aid, $uid, $interfaceId, $params, [
                'error' => $e->getMessage()
            ], $responseTime, 0);
            
            return ['status' => 0, 'msg' => '测试失败：' . $e->getMessage()];
        }
    }

    /**
     * 发送HTTP请求
     */
    private function sendHttpRequest($url, $method, $params)
    {
        // 记录请求日志
        \think\facade\Log::info('API测试请求', [
            'url' => $url,
            'method' => $method,
            'params' => $params
        ]);
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, true); // 获取响应头
        
        if (strtoupper($method) == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } elseif (strtoupper($method) == 'GET' && !empty($params)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        
        // 分离响应头和响应体
        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // 记录响应日志
        \think\facade\Log::info('API测试响应', [
            'status_code' => $statusCode,
            'body_length' => strlen($body),
            'curl_error' => $curlError,
            'response_preview' => substr($body, 0, 200)
        ]);
        
        // 如果是404，返回特殊提示
        if ($statusCode == 404) {
            return [
                'status_code' => 404,
                'body' => '接口不存在或路径错误。请检查：\n1. 路径是否正确\n2. 接口是否已部署\n3. 路由是否配置\n\n原始响应：' . $body,
                'json' => null,
                'error' => '404 Not Found'
            ];
        }
        
        return [
            'status_code' => $statusCode,
            'body' => $body ?: $curlError,
            'json' => json_decode($body, true),
            'error' => $curlError
        ];
    }

    /**
     * 保存测试日志
     */
    private function saveTestLog($aid, $uid, $interfaceId, $params, $response, $responseTime, $statusCode = 200)
    {
        try {
            Db::name('api_test_log')->insert([
                'aid' => $aid,
                'uid' => $uid,
                'interface_id' => $interfaceId,
                'request_params' => json_encode($params, JSON_UNESCAPED_UNICODE),
                'response_data' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'response_time' => $responseTime,
                'status_code' => $statusCode,
                'ip' => request()->ip(),
                'create_time' => time()
            ]);
        } catch (\Exception $e) {
            // 忽略日志保存错误
        }
    }

    /**
     * 获取测试日志
     */
    public function getTestLogs($aid, $uid, $page, $limit, $interfaceId = 0)
    {
        try {
            $where = [['aid', '=', $aid]];
            
            if ($interfaceId) {
                $where[] = ['interface_id', '=', $interfaceId];
            }
            
            $list = Db::name('api_test_log')
                ->alias('log')
                ->leftJoin('api_interface inter', 'log.interface_id = inter.id')
                ->field('log.*, inter.name as interface_name, inter.path as interface_path')
                ->where($where)
                ->order('log.id desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);
            
            return [
                'status' => 1,
                'data' => $list->items(),
                'count' => $list->total()
            ];
        } catch (\Exception $e) {
            return ['status' => 0, 'msg' => '查询失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取测试日志详情
     */
    public function getTestLogDetail($aid, $id)
    {
        try {
            $detail = Db::name('api_test_log')
                ->alias('log')
                ->leftJoin('api_interface inter', 'log.interface_id = inter.id')
                ->field('log.*, inter.name as interface_name, inter.path as interface_path')
                ->where('log.aid', $aid)
                ->where('log.id', $id)
                ->find();
            
            if ($detail) {
                $detail['request_params'] = json_decode($detail['request_params'], true);
                $detail['response_data'] = json_decode($detail['response_data'], true);
            }
            
            return $detail;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 导出文档
     */
    public function exportDocuments($aid, $ids, $format = 'markdown')
    {
        try {
            $interfaces = Db::name('api_interface')
                ->where('aid', $aid)
                ->whereIn('id', $ids)
                ->select()
                ->toArray();
            
            if (empty($interfaces)) {
                return ['status' => 0, 'msg' => '没有找到接口数据'];
            }
            
            if ($format == 'markdown') {
                $content = $this->generateMarkdown($interfaces);
                $filename = 'api_doc_' . date('YmdHis') . '.md';
            } else {
                $content = json_encode($interfaces, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                $filename = 'api_doc_' . date('YmdHis') . '.json';
            }
            
            // 保存到临时文件
            $filePath = runtime_path() . 'temp/' . $filename;
            file_put_contents($filePath, $content);
            
            return [
                'status' => 1,
                'file' => $filePath,
                'filename' => $filename
            ];
        } catch (\Exception $e) {
            return ['status' => 0, 'msg' => '导出失败：' . $e->getMessage()];
        }
    }

    /**
     * 生成Markdown文档
     */
    private function generateMarkdown($interfaces)
    {
        $markdown = "# API接口文档\n\n";
        $markdown .= "生成时间：" . date('Y-m-d H:i:s') . "\n\n";
        
        // 按分类分组
        $grouped = [];
        foreach ($interfaces as $interface) {
            $category = $interface['category'] ?: '其他';
            $grouped[$category][] = $interface;
        }
        
        foreach ($grouped as $category => $items) {
            $markdown .= "## {$category}\n\n";
            
            foreach ($items as $interface) {
                $markdown .= "### {$interface['name']}\n\n";
                $markdown .= "**接口路径**: `{$interface['path']}`\n\n";
                $markdown .= "**请求方式**: `{$interface['method']}`\n\n";
                $markdown .= "**认证要求**: " . ($interface['auth_required'] ? '是' : '否') . "\n\n";
                
                if ($interface['description']) {
                    $markdown .= "**接口描述**: {$interface['description']}\n\n";
                }
                
                if ($interface['request_params']) {
                    $markdown .= "**请求参数**:\n\n";
                    $markdown .= "```json\n";
                    $markdown .= $interface['request_params'];
                    $markdown .= "\n```\n\n";
                }
                
                if ($interface['response_example']) {
                    $markdown .= "**响应示例**:\n\n";
                    $markdown .= "```json\n";
                    $markdown .= $interface['response_example'];
                    $markdown .= "\n```\n\n";
                }
                
                $markdown .= "---\n\n";
            }
        }
        
        return $markdown;
    }
}
