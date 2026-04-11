<?php
/**
 * Ollama 本地LLM 对话控制器
 * 提供管理员界面的模型对话功能
 */
namespace app\controller;

use think\facade\View;
use app\service\OllamaChatService;

class OllamaChat extends Common
{
    protected $chatService;

    public function initialize()
    {
        parent::initialize();
        $this->chatService = new OllamaChatService();
    }

    /**
     * 对话页面
     */
    public function index()
    {
        return View::fetch();
    }

    /**
     * 获取可用模型列表（AJAX）
     */
    public function get_models()
    {
        $result = $this->chatService->getAvailableModels();
        return json($result);
    }

    /**
     * 发送对话消息（AJAX，接受 JSON body）
     */
    public function send_message()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }

        // 支持 JSON body（前端以 application/json 方式提交）
        $rawBody = request()->getContent();
        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            // 回退到表单参数
            $data = [
                'model'    => input('post.model', ''),
                'messages' => input('post.messages/a', []),
                'options'  => input('post.options/a', []),
            ];
        }

        $modelCode = $data['model'] ?? '';
        $messages  = $data['messages'] ?? [];
        $options   = $data['options'] ?? [];

        if (empty($modelCode)) {
            return json(['status' => 0, 'msg' => '请选择模型']);
        }
        if (empty($messages) || !is_array($messages)) {
            return json(['status' => 0, 'msg' => '消息内容不能为空']);
        }

        // 安全校验：仅保留合法的 role/content
        $safeMessages = [];
        foreach ($messages as $msg) {
            if (!isset($msg['role']) || !isset($msg['content'])) continue;
            $role = in_array($msg['role'], ['system', 'user', 'assistant']) ? $msg['role'] : 'user';
            $safeMessages[] = ['role' => $role, 'content' => strval($msg['content'])];
        }

        if (empty($safeMessages)) {
            return json(['status' => 0, 'msg' => '消息内容不能为空']);
        }

        $result = $this->chatService->sendMessage($modelCode, $safeMessages, $options);
        return json($result);
    }
}
