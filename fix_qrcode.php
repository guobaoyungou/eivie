<?php
// 修复：在 markGenerationFailed 中补充 saveToQrcode 逻辑
// 当所有 generation 完成且有成功结果时，确保创建 qrcode 记录

$filePath = '/home/www/ai.eivie.cn/app/job/ImageGenerationJob.php';
$content = file_get_contents($filePath);

// 在 markGenerationFailed 方法中，synthesis_status 更新后、trace之前，插入 qrcode 创建逻辑
$oldCode = '                    trace("Portrait {$portraitId} synthesis_status 更新为 {$synthesisStatus}（成功:{$successCount}）", \'info\');';

$newCode = '                    // 补充：有成功结果时创建选片记录（qrcode表）
                    if ($successCount > 0) {
                        try {
                            $portrait = Db::name(\'ai_travel_photo_portrait\')->where(\'id\', $portraitId)->find();
                            if ($portrait) {
                                $qrcodeExists = Db::name(\'ai_travel_photo_qrcode\')->where(\'portrait_id\', $portraitId)->find();
                                if (!$qrcodeExists) {
                                    $bid = $portrait[\'bid\'] ?? 0;
                                    $aid = $portrait[\'aid\'] ?? 0;
                                    $qrcodeValue = \'synth_\' . $portraitId . \'_\' . time();
                                    Db::name(\'ai_travel_photo_qrcode\')->insertGetId([
                                        \'aid\' => $aid,
                                        \'bid\' => $bid,
                                        \'portrait_id\' => $portraitId,
                                        \'qrcode\' => $qrcodeValue,
                                        \'status\' => 1,
                                        \'create_time\' => time(),
                                        \'update_time\' => time(),
                                    ]);
                                    trace("markGenerationFailed 补创建qrcode: portrait_id={$portraitId}, qrcode={$qrcodeValue}", \'info\');
                                }
                            }
                        } catch (\Throwable $qrEx) {
                            trace(\'markGenerationFailed 创建qrcode异常: \' . $qrEx->getMessage(), \'error\');
                        }
                    }

                    trace("Portrait {$portraitId} synthesis_status 更新为 {$synthesisStatus}（成功:{$successCount}）", \'info\');';

if (strpos($content, $oldCode) === false) {
    echo "ERROR: 未找到目标代码\n";
    exit(1);
}

$content = str_replace($oldCode, $newCode, file_get_contents($filePath));
file_put_contents($filePath, $content);
echo "OK: 已在 markGenerationFailed 中补充 qrcode 创建逻辑\n";
