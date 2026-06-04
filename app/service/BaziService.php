<?php
/**
 * 算八字服务类
 * 基于bazi-skill-dist方法论，调用豆包Seed 2.0 Pro进行八字命理分析
 * 支持从数据库配置表动态读取模型、Skill提示词、付费模式和价格
 */
namespace app\service;

use think\facade\Db;
use think\facade\Log;

class BaziService
{
    use \app\common\ApiKeyEncryptTrait;

    /** @var string 火山引擎方舟 Chat Completions API 端点 */
    const VOLCENGINE_ENDPOINT = 'https://ark.cn-beijing.volces.com/api/v3/chat/completions';

    /** @var string 默认模型（配置表无记录时的fallback） */
    const DEFAULT_MODEL = 'doubao-seed-2-0-pro-260215';

    /** @var int API 请求超时（秒） */
    const TIMEOUT = 360;

    /** @var array|null 缓存当前平台的配置 */
    protected $configCache = null;

    /**
     * 获取八字功能配置（从bazi_config表读取，无记录时返回默认值）
     * @param int $aid 平台ID
     * @return array
     */
    public function getConfig(int $aid = 0): array
    {
        if ($this->configCache !== null) {
            return $this->configCache;
        }

        $config = Db::name('bazi_config')->where('aid', $aid)->find();

        if (empty($config)) {
            // 返回默认配置，与旧版硬编码兼容
            $this->configCache = [
                'aid'             => $aid,
                'model'           => self::DEFAULT_MODEL,
                'skill_prompt'    => $this->getDefaultSkillPrompt(),
                'pay_mode'        => 'free',
                'price'           => 0,
                'preview_percent' => 50,
            ];
        } else {
            // 确保skill_prompt不为空
            if (empty($config['skill_prompt'])) {
                $config['skill_prompt'] = $this->getDefaultSkillPrompt();
            }
            $this->configCache = $config;
        }

        return $this->configCache;
    }

    /**
     * 获取默认Skill提示词（保留原硬编码内容作为fallback）
     */
    protected function getDefaultSkillPrompt(): string
    {
        $file = root_path() . 'bazi-skill-dist/SKILL.md';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if ($content !== false && !empty(trim($content))) {
                return $content;
            }
        }
        return $this->buildSystemPromptFallback();
    }

    /**
     * 硬编码的fallback系统提示词（bazi-skill-dist核心方法论）
     */
    protected function buildSystemPromptFallback(): string
    {
        return <<<'PROMPT'
你是一位精通中国传统命理学的专家，严格按八字（四柱预测学）+紫微斗数双系统方法论执行命理分析。本分析必须遵循四位大师的ensemble投票法，融合：
- 徐乐吾：格局+调候，子平真诠评注权威
- 梁湘润：三轨+金不换+四条规则，流月规则最精
- 袁树珊：命宫+小限+十六字法，见人所未见
- 韦千里：八步法+转角论，大运节点定位精准

## 核心铁律（违者无效）
1. **禁止蒙猜**：不知道就说不知道。禁用"可能""大概""应该是"。
2. **禁止唯一论**：不可只用格局/神煞/用神/身强弱任一视角断命。
3. **禁止截看大运**：一运看十年，切勿上下截看。
4. **禁止反推**：不能拿已知事实凑八字解释。
5. **禁止脚本排盘**：所有排盘必须手动推演。
6. **伦理原则**：命理学描述的是"概率性倾向"，不是"宿命决定论"。

输出禁止使用emoji。排盘结果用表格+纯文字。请确保每个步骤有典籍依据，手工推理，标注出处。
PROMPT;
    }

    /**
     * 八字命理分析
     *
     * @param array $params 用户输入参数
     * @param int $aid 平台ID
     * @return array ['status'=>1, 'data'=>[...]] 或 ['status'=>0, 'msg'=>'...']
     */
    public function calculate(array $params, int $aid = 0): array
    {
        // 1. 参数校验
        $validateResult = $this->validateParams($params);
        if ($validateResult['status'] === 0) {
            return $validateResult;
        }

        // 2. 获取配置
        $config = $this->getConfig($aid);

        // 3. 获取火山引擎 API Key
        $apiKey = $this->getVolcengineApiKey();
        if (empty($apiKey)) {
            return ['status' => 0, 'msg' => '火山引擎API Key未配置，请先在后台设置'];
        }

        // 4. 构建系统提示词（从配置表读取）
        $systemPrompt = $config['skill_prompt'];

        // 5. 构建用户提示词
        $userPrompt = $this->buildUserPrompt($params);

        // 6. 调用AI API（使用配置表中的模型）
        $model = $config['model'] ?: self::DEFAULT_MODEL;
        $result = $this->callDoubaoAPI($apiKey, $systemPrompt, $userPrompt, $model);

        return $result;
    }

    /**
     * 参数校验
     */
    protected function validateParams(array $params): array
    {
        if (empty($params['birth_date'])) {
            return ['status' => 0, 'msg' => '请填写出生日期'];
        }
        if (empty($params['birth_time'])) {
            return ['status' => 0, 'msg' => '请填写出生时间'];
        }
        if (empty($params['birth_place'])) {
            return ['status' => 0, 'msg' => '请填写出生地点'];
        }
        if (empty($params['gender']) || !in_array($params['gender'], ['男', '女'])) {
            return ['status' => 0, 'msg' => '请选择性别'];
        }

        $dateParts = explode('-', $params['birth_date']);
        if (count($dateParts) !== 3 || !checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0])) {
            return ['status' => 0, 'msg' => '出生日期格式不正确，请使用YYYY-MM-DD格式'];
        }

        $timeParts = explode(':', $params['birth_time']);
        if (count($timeParts) < 2 || $timeParts[0] === '' || $timeParts[1] === '') {
            return ['status' => 0, 'msg' => '出生时间格式不正确，请使用HH:MM格式'];
        }
        $hour = (int)$timeParts[0];
        $minute = (int)$timeParts[1];
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return ['status' => 0, 'msg' => '出生时间不合法，小时0-23，分钟0-59'];
        }

        return ['status' => 1];
    }

    /**
     * 获取火山引擎API Key（解密后）
     */
    protected function getVolcengineApiKey(): string
    {
        try {
            $keyRecord = Db::name('system_api_key')
                ->alias('k')
                ->leftJoin('model_provider p', 'p.id = k.provider_id')
                ->where('p.provider_code', 'volcengine')
                ->where('k.is_active', 1)
                ->order('k.sort asc, k.id desc')
                ->find();

            if (empty($keyRecord) || empty($keyRecord['api_key'])) {
                Log::warning('BaziService: 未找到volcengine API Key配置');
                return '';
            }

            $decrypted = $this->decryptApiKey($keyRecord['api_key']);
            if (empty($decrypted)) {
                Log::error('BaziService: volcengine API Key解密失败');
                return '';
            }

            return $decrypted;
        } catch (\Exception $e) {
            Log::error('BaziService: 获取API Key异常 - ' . $e->getMessage());
            return '';
        }
    }

    /**
     * 构建用户提示词
     */
    protected function buildUserPrompt(array $params): string
    {
        $name = $params['name'] ?? '未提供';
        $birthDate = $params['birth_date'];
        $birthTime = $params['birth_time'];
        $birthPlace = $params['birth_place'];
        $gender = $params['gender'];

        $year = (int)explode('-', $birthDate)[0];
        $currentYear = (int)date('Y');
        $age = $currentYear - $year;

        return <<<PROMPT
请为以下用户进行完整的八字+紫微斗数双系统命理分析：

姓名：{$name}
阳历出生日期：{$birthDate}
出生时间：{$birthTime}（北京时间，请做真太阳时校正）
出生地点：{$birthPlace}
性别：{$gender}

当前时间：{$currentYear}年，用户年龄约{$age}岁。

请严格按照八字十五步排盘流程+紫微斗数中州派方法，从排八字开始逐步手动推演，执行四家投票，进行双系统交叉验证，最终给出完整的命理分析报告。输出禁止使用emoji。
PROMPT;
    }

    /**
     * 保存分析记录到 bazi_order 表
     *
     * @param array $params 用户输入参数
     * @param array $result calculate()返回的完整结果
     * @param int $aid 平台ID
     * @param int $mid 用户ID
     * @param string $payMode 付费模式
     * @param float $price 价格
     * @return int|false 返回记录ID或false
     */
    public function saveRecord(array $params, array $result, int $aid, int $mid, string $payMode = 'free', float $price = 0)
    {
        try {
            $data = $result['data'] ?? [];
            $usage = $data['usage'] ?? [];

            $inputJson = json_encode([
                'name'       => $params['name'] ?? '',
                'birth_date' => $params['birth_date'] ?? '',
                'birth_time' => $params['birth_time'] ?? '',
                'birth_place' => $params['birth_place'] ?? '',
                'gender'     => $params['gender'] ?? '',
            ], JSON_UNESCAPED_UNICODE);

            $resultJson = json_encode([
                'result'    => $data['result'] ?? '',
                'reasoning' => $data['reasoning'] ?? '',
                'usage'     => $usage,
                'latency_ms' => $data['latency_ms'] ?? 0,
                'finish_reason' => $data['finish_reason'] ?? '',
            ], JSON_UNESCAPED_UNICODE);

            $orderNum = $this->generateOrdernum();

            $recordId = Db::name('bazi_order')->insertGetId([
                'aid'          => $aid,
                'mid'          => $mid,
                'ordernum'     => $orderNum,
                'payorderid'   => 0,
                'pay_status'   => ($payMode === 'free') ? 1 : 0,
                'pay_mode'     => $payMode,
                'price'        => $price,
                'pay_time'     => ($payMode === 'free') ? time() : 0,
                'transaction_id' => '',
                'input_json'   => $inputJson,
                'result_json'  => $resultJson,
                'latency_ms'   => $data['latency_ms'] ?? 0,
                'total_tokens' => $usage['total_tokens'] ?? 0,
                'create_time'  => time(),
                'update_time'  => time(),
                'ip'           => request()->ip(),
            ]);

            Log::info('BaziService: 记录保存成功', [
                'record_id' => $recordId,
                'ordernum' => $orderNum,
                'pay_mode' => $payMode,
            ]);

            return $recordId;
        } catch (\Exception $e) {
            Log::error('BaziService: 保存记录异常 - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 校验支付状态（检查payorder是否已支付）
     *
     * @param string $ordernum 订单编号
     * @return array ['paid'=>bool, 'payorder'=>array]
     */
    public function checkPayStatus(string $ordernum): array
    {
        $order = Db::name('bazi_order')->where('ordernum', $ordernum)->find();

        if (empty($order)) {
            return ['paid' => false, 'msg' => '订单不存在'];
        }

        if ($order['pay_status'] == 1) {
            return ['paid' => true, 'order' => $order];
        }

        // 检查关联的payorder
        if ($order['payorderid'] > 0) {
            $payorder = Db::name('payorder')->where('id', $order['payorderid'])->find();
            if ($payorder && $payorder['status'] == 1) {
                // 同步更新bazi_order支付状态
                Db::name('bazi_order')->where('id', $order['id'])->update([
                    'pay_status' => 1,
                    'pay_time' => $payorder['paytime'] ?? time(),
                    'transaction_id' => $payorder['paynum'] ?? '',
                    'update_time' => time(),
                ]);
                $order['pay_status'] = 1;
                return ['paid' => true, 'order' => $order];
            }
        }

        return ['paid' => false, 'order' => $order];
    }

    /**
     * 创建支付订单（关联payorder表）
     *
     * @param int $orderId bazi_order记录ID
     * @param string $ordernum 订单编号
     * @param int $aid 平台ID
     * @param int $mid 用户ID
     * @param float $price 金额
     * @return int|false payorder ID
     */
    public function createPayOrder(int $orderId, string $ordernum, int $aid, int $mid, float $price)
    {
        try {
            $payorderId = Db::name('payorder')->insertGetId([
                'aid'        => $aid,
                'bid'        => 0,
                'mid'        => $mid,
                'ordernum'   => $ordernum,
                'orderid'    => $orderId,
                'title'      => '八字命理分析',
                'money'      => $price,
                'type'       => 'bazi',
                'status'     => 0,
                'createtime' => time(),
            ]);

            if (empty($payorderId)) {
                Log::error('BaziService: payorder插入失败 - insertGetId返回空', [
                    'orderId'   => $orderId,
                    'ordernum'  => $ordernum,
                    'aid'       => $aid,
                    'mid'       => $mid,
                    'price'     => $price,
                ]);
                return false;
            }

            // 更新bazi_order关联的payorderid
            Db::name('bazi_order')->where('id', $orderId)->update([
                'payorderid' => $payorderId,
                'update_time' => time(),
            ]);

            return $payorderId;
        } catch (\Exception $e) {
            Log::error('BaziService: 创建支付订单异常 - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 生成唯一订单编号
     */
    protected function generateOrdernum(): string
    {
        return 'BAZI' . date('YmdHis') . rand(1000, 9999);
    }

    /**
     * 调用豆包API
     *
     * @param string $apiKey 解密后的API Key
     * @param string $systemPrompt 系统提示词
     * @param string $userPrompt 用户提示词
     * @param string $model 模型名称（从配置读取）
     * @return array
     */
    protected function callDoubaoAPI(string $apiKey, string $systemPrompt, string $userPrompt, string $model): array
    {
        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'stream' => false,
            'temperature' => 1,
            'top_p' => 0.95,
            'max_completion_tokens' => 32768,
            'thinking' => ['type' => 'enabled'],
        ];

        Log::info('BaziService: 开始调用豆包 API', [
            'model' => $model,
            'system_prompt_len' => mb_strlen($systemPrompt),
            'user_prompt_len' => mb_strlen($userPrompt),
        ]);

        $startTime = microtime(true);

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => self::VOLCENGINE_ENDPOINT,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT        => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $latencyMs = intval((microtime(true) - $startTime) * 1000);

            if ($response === false || $httpCode === 0) {
                Log::error('BaziService: cURL请求失败 - ' . $curlError);
                return ['status' => 0, 'msg' => '网络连接失败，请稍后重试'];
            }

            $respBody = json_decode($response, true);

            if ($httpCode !== 200) {
                $errorMsg = $respBody['error']['message'] ?? $respBody['message'] ?? "HTTP {$httpCode}";
                Log::error('BaziService: API返回错误', [
                    'http_code' => $httpCode,
                    'error' => $errorMsg,
                    'response' => substr($response, 0, 500),
                ]);
                return ['status' => 0, 'msg' => 'AI服务返回错误: ' . $errorMsg];
            }

            if (!is_array($respBody) || empty($respBody['choices'])) {
                Log::error('BaziService: 响应格式异常', ['response' => substr($response, 0, 500)]);
                return ['status' => 0, 'msg' => 'AI服务返回数据异常，请稍后重试'];
            }

            $choice = $respBody['choices'][0] ?? [];
            $message = $choice['message'] ?? [];
            $content = $message['content'] ?? '';
            $reasoningContent = $message['reasoning_content'] ?? '';
            $finishReason = $choice['finish_reason'] ?? 'unknown';

            if ($finishReason === 'content_filter') {
                Log::warning('BaziService: 内容被安全过滤');
                return ['status' => 0, 'msg' => '输入内容包含敏感信息，请修改后重试'];
            }

            $usage = $respBody['usage'] ?? [];
            $totalTokens = $usage['total_tokens'] ?? 0;

            Log::info('BaziService: API调用成功', [
                'latency_ms' => $latencyMs,
                'total_tokens' => $totalTokens,
                'content_len' => mb_strlen($content),
                'reasoning_len' => mb_strlen($reasoningContent),
            ]);

            return [
                'status' => 1,
                'data' => [
                    'result' => $content,
                    'reasoning' => $reasoningContent,
                    'usage' => [
                        'total_tokens' => $totalTokens,
                        'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                        'completion_tokens' => $usage['completion_tokens'] ?? 0,
                    ],
                    'latency_ms' => $latencyMs,
                    'finish_reason' => $finishReason,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('BaziService: 调用异常 - ' . $e->getMessage());
            return ['status' => 0, 'msg' => '系统异常: ' . $e->getMessage()];
        }
    }
}
