<?php
/**
 * VoxCPM2 语音对话控制器
 * 提供管理员界面的语音对话功能（Ollama 文本 + VoxCPM2 语音合成）
 */
namespace app\controller;

use think\facade\View;
use app\service\VoiceChatService;

class VoiceChat extends Common
{
    protected $voiceChatService;

    public function initialize()
    {
        parent::initialize();
        $this->voiceChatService = new VoiceChatService();
    }

    /**
     * 语音对话页面
     */
    public function index()
    {
        return View::fetch();
    }

    /**
     * 获取可用模型列表 + VoxCPM2 服务状态（AJAX）
     */
    public function get_models()
    {
        $result = $this->voiceChatService->getModelsAndStatus();
        return json($result);
    }

    /**
     * 发送对话消息（仅文本生成，AJAX，接受 JSON body）
     */
    public function send_message()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }

        // 支持 JSON body
        $rawBody = request()->getContent();
        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            $data = [
                'model'         => input('post.model', ''),
                'messages'      => input('post.messages/a', []),
                'options'       => input('post.options/a', []),
            ];
        }

        $modelCode    = $data['model'] ?? '';
        $messages     = $data['messages'] ?? [];
        $options      = $data['options'] ?? [];

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

        $result = $this->voiceChatService->sendMessage($modelCode, $safeMessages, $options);
        return json($result);
    }

    /**
     * 语音合成接口（前端在获取文本回复后单独调用）
     */
    public function synthesize()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }

        $rawBody = request()->getContent();
        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            $data = [
                'text'          => input('post.text', ''),
                'voice_options' => input('post.voice_options/a', []),
            ];
        }

        $text = trim($data['text'] ?? '');
        $voiceOptions = $data['voice_options'] ?? [];

        if (empty($text)) {
            return json(['status' => 0, 'msg' => '合成文本不能为空']);
        }

        // 文本长度限制
        if (mb_strlen($text) > 500) {
            $text = mb_substr($text, 0, 500);
        }

        // 校验 voice_options
        $safeVoiceOptions = [];
        if (!empty($voiceOptions['control'])) {
            $safeVoiceOptions['control'] = strval($voiceOptions['control']);
        }
        if (isset($voiceOptions['cfg_value'])) {
            $safeVoiceOptions['cfg_value'] = max(0.5, min(5.0, floatval($voiceOptions['cfg_value'])));
        }
        if (isset($voiceOptions['inference_timesteps'])) {
            $safeVoiceOptions['inference_timesteps'] = max(1, min(50, intval($voiceOptions['inference_timesteps'])));
        }

        $result = $this->voiceChatService->synthesize($text, $safeVoiceOptions);
        return json($result);
    }
}
