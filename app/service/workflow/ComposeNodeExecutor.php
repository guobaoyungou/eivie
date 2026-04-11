<?php
/**
 * 合成节点执行器
 * 通过 FFmpeg 将视频/音频片段、字幕按时间轴组装为最终 9:16 成片
 */
namespace app\service\workflow;

use app\model\WorkflowNode;
use app\model\WorkflowProject;
use think\facade\Log;

class ComposeNodeExecutor implements NodeExecutorInterface
{
    /**
     * 执行视频合成
     */
    public function execute(WorkflowNode $node, array $inputData): array
    {
        $config = $node->config_params;
        if (is_string($config)) {
            $config = json_decode($config, true);
        }

        // 视频片段来自上游视频节点
        $clips = $inputData['clips'] ?? [];
        // 音频片段来自上游配音节点
        $audioClips = $inputData['audio_clips'] ?? [];

        // 合成参数
        $subtitleStyle = $config['subtitle_style'] ?? [
            'font_size' => 28,
            'font_color' => 'white',
            'position'   => 'bottom',
        ];
        $transition     = $config['transition'] ?? 'fade';      // 转场效果
        $bgmUrl         = $config['bgm_url'] ?? '';             // 背景音乐
        $outputFormat   = $config['output_format'] ?? 'mp4';

        if (empty($clips)) {
            return ['status' => 0, 'msg' => '无视频片段数据，请先连接视频节点'];
        }

        // 过滤有效的视频片段
        $validClips = array_filter($clips, function($c) {
            return !empty($c['video_url']);
        });

        if (empty($validClips)) {
            return ['status' => 0, 'msg' => '所有视频片段均为空，无法合成'];
        }

        try {
            // 构建FFmpeg合成命令
            $composeResult = $this->composeWithFFmpeg(
                array_values($validClips),
                $audioClips,
                $subtitleStyle,
                $transition,
                $bgmUrl
            );

            if ($composeResult['success']) {
                $outputUrl = $composeResult['output_url'];

                // 更新项目的最终输出
                WorkflowProject::where('id', $node->project_id)->update([
                    'output_video_url' => $outputUrl,
                ]);

                return [
                    'status' => 1,
                    'msg'    => '成片合成完成',
                    'data'   => [
                        'final_video' => [
                            'url'        => $outputUrl,
                            'duration'   => $composeResult['duration'] ?? 0,
                            'resolution' => '1080x1920',
                            'file_size'  => $composeResult['file_size'] ?? 0,
                            'format'     => $outputFormat,
                        ],
                    ],
                ];
            } else {
                return ['status' => 0, 'msg' => $composeResult['error'] ?? '合成失败'];
            }
        } catch (\Exception $e) {
            Log::error('视频合成异常: ' . $e->getMessage());
            return ['status' => 0, 'msg' => '合成异常: ' . $e->getMessage()];
        }
    }

    /**
     * 使用FFmpeg合成视频
     */
    protected function composeWithFFmpeg($clips, $audioClips, $subtitleStyle, $transition, $bgmUrl)
    {
        $tempDir = runtime_path() . 'workflow_compose/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $timestamp = time() . '_' . uniqid();
        $fileListPath = $tempDir . 'filelist_' . $timestamp . '.txt';
        $outputPath   = $tempDir . 'output_' . $timestamp . '.mp4';

        // 下载视频片段到本地（FFmpeg需要本地文件）
        $localFiles = [];
        foreach ($clips as $i => $clip) {
            $videoUrl = $clip['video_url'];
            $localPath = $tempDir . 'clip_' . $timestamp . '_' . $i . '.mp4';

            // 如果是本地路径直接使用，否则下载
            if (file_exists($videoUrl)) {
                $localPath = $videoUrl;
            } elseif (filter_var($videoUrl, FILTER_VALIDATE_URL)) {
                $content = @file_get_contents($videoUrl);
                if ($content !== false) {
                    file_put_contents($localPath, $content);
                } else {
                    Log::warning("无法下载视频片段: {$videoUrl}");
                    continue;
                }
            } else {
                continue;
            }

            $localFiles[] = $localPath;
        }

        if (empty($localFiles)) {
            return ['success' => false, 'error' => '无有效的本地视频文件'];
        }

        // 生成FFmpeg concat文件列表
        $fileListContent = '';
        foreach ($localFiles as $file) {
            $fileListContent .= "file '" . str_replace("'", "'\\''", $file) . "'\n";
        }
        file_put_contents($fileListPath, $fileListContent);

        // 构建FFmpeg命令
        // 输出规范：1080x1920, H.264, 30fps, AAC 48kHz 128kbps
        $ffmpegCmd = "ffmpeg -y -f concat -safe 0 -i {$fileListPath}";

        // 添加音频轨道（仅当有有效音频时）
        $hasAudio = false;
        if (!empty($audioClips)) {
            $audioFiles = [];
            foreach ($audioClips as $j => $audio) {
                $audioUrl = $audio['audio_url'] ?? '';
                if (empty($audioUrl)) continue;
                
                $audioLocalPath = $tempDir . 'audio_' . $timestamp . '_' . $j . '.wav';
                if (file_exists($audioUrl)) {
                    // 本地文件（包括静音占位）
                    $audioFiles[] = $audioUrl;
                } elseif (filter_var($audioUrl, FILTER_VALIDATE_URL)) {
                    $audioContent = @file_get_contents($audioUrl);
                    if ($audioContent !== false) {
                        file_put_contents($audioLocalPath, $audioContent);
                        $audioFiles[] = $audioLocalPath;
                    }
                }
            }

            if (!empty($audioFiles)) {
                // 合并所有音频文件为一个
                $mergedAudioPath = $tempDir . 'merged_audio_' . $timestamp . '.wav';
                if (count($audioFiles) === 1) {
                    // 只有一个音频文件，直接使用
                    $mergedAudioPath = $audioFiles[0];
                    $hasAudio = true;
                } else {
                    $audioListPath = $tempDir . 'audiolist_' . $timestamp . '.txt';
                    $audioListContent = '';
                    foreach ($audioFiles as $af) {
                        $audioListContent .= "file '" . str_replace("'", "'\\''", $af) . "'\n";
                    }
                    file_put_contents($audioListPath, $audioListContent);

                    $mergeAudioCmd = "ffmpeg -y -f concat -safe 0 -i {$audioListPath} -c copy {$mergedAudioPath} 2>&1";
                    exec($mergeAudioCmd);
                    $hasAudio = file_exists($mergedAudioPath);
                }

                if ($hasAudio) {
                    $ffmpegCmd .= " -i {$mergedAudioPath} -map 0:v -map 1:a -shortest";
                }
            }
        }

        // 视频编码参数（抖音兼容）
        $ffmpegCmd .= " -vf 'scale=1080:1920:force_original_aspect_ratio=decrease,pad=1080:1920:(ow-iw)/2:(oh-ih)/2'";
        $ffmpegCmd .= " -c:v libopenh264 -r 30";
        $ffmpegCmd .= " -c:a aac -b:a 128k -ar 48000";
        $ffmpegCmd .= " -movflags +faststart";
        $ffmpegCmd .= " {$outputPath} 2>&1";

        $output = [];
        $returnCode = 0;
        exec($ffmpegCmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($outputPath)) {
            Log::error('FFmpeg合成失败: ' . implode("\n", $output));
            return [
                'success' => false,
                'error'   => 'FFmpeg合成失败: ' . implode("\n", array_slice($output, -5)),
            ];
        }

        $fileSize = filesize($outputPath);

        // 获取视频时长
        $durationCmd = "ffprobe -v error -show_entries format=duration -of csv=p=0 {$outputPath} 2>&1";
        $durationOutput = trim(shell_exec($durationCmd) ?: '0');
        $duration = floatval($durationOutput);

        // 将成片复制到静态公开目录，确保Web可访问
        $publicDir = ROOT_PATH . 'static/workflow/videos/';
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        $publicFile = $publicDir . basename($outputPath);
        copy($outputPath, $publicFile);
        $outputUrl = '/static/workflow/videos/' . basename($outputPath);

        // 清理临时文件
        @unlink($fileListPath);
        @unlink($outputPath);

        return [
            'success'    => true,
            'output_url' => $outputUrl,
            'duration'   => round($duration, 2),
            'file_size'  => $fileSize,
        ];
    }
}
