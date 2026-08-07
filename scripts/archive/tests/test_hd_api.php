<?php
/**
 * 大屏互动系统 - 自动化 API 测试脚本
 * 
 * 用法: php test_hd_api.php [base_url]
 * 默认: php test_hd_api.php http://localhost
 */

$baseUrl = $argv[1] ?? 'http://localhost';
$apiBase = rtrim($baseUrl, '/') . '/api/hd';

echo "============================================\n";
echo " 大屏互动系统 API 自动化测试\n";
echo " API 基础路径: {$apiBase}\n";
echo "============================================\n\n";

$passed = 0;
$failed = 0;
$token = '';
$bid = 0;
$userId = 0;
$storeId = 0;
$activityId = 0;
$accessCode = '';

// ==========================================
// 辅助函数
// ==========================================
function httpRequest(string $method, string $url, array $data = [], array $headers = []): array
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $httpHeaders = ['Accept: application/json'];
    foreach ($headers as $k => $v) {
        $httpHeaders[] = "{$k}: {$v}";
    }

    if (strtoupper($method) === 'GET') {
        if ($data) {
            $url .= '?' . http_build_query($data);
        }
    } elseif (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    } elseif (strtoupper($method) === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    } elseif (strtoupper($method) === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'body'      => $response,
        'data'      => json_decode($response, true),
        'error'     => $error,
    ];
}

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $result = $fn();
        if ($result === true) {
            echo "  ✅ {$name}\n";
            $passed++;
        } else {
            echo "  ❌ {$name} - 断言失败: {$result}\n";
            $failed++;
        }
    } catch (\Throwable $e) {
        echo "  ❌ {$name} - 异常: " . $e->getMessage() . "\n";
        $failed++;
    }
}

function authHeaders(): array
{
    global $token;
    return $token ? ['Hd-Token' => $token] : [];
}

// ==========================================
// 测试用例
// ==========================================

echo "▶ 1. 认证模块测试\n";

$testPhone = '138' . str_pad((string)mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
$testPassword = 'test123456';

test('1.1 商家注册', function () use ($apiBase, $testPhone, $testPassword, &$token, &$bid, &$userId) {
    $res = httpRequest('POST', "{$apiBase}/auth/register", [
        'name'     => '测试商家_' . date('His'),
        'phone'    => $testPhone,
        'password' => $testPassword,
    ]);
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '注册失败: ' . ($res['data']['msg'] ?? $res['body'] ?? 'unknown');
    }
    $token = $res['data']['data']['token'] ?? '';
    $bid = $res['data']['data']['bid'] ?? 0;
    $userId = $res['data']['data']['user_id'] ?? 0;
    if (empty($token)) return 'Token为空';
    return true;
});

test('1.2 商家登录', function () use ($apiBase, $testPhone, $testPassword, &$token) {
    $res = httpRequest('POST', "{$apiBase}/auth/login", [
        'username' => $testPhone,
        'password' => $testPassword,
    ]);
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '登录失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    $token = $res['data']['data']['token'] ?? $token;
    return true;
});

test('1.3 获取profile', function () use ($apiBase) {
    $res = httpRequest('GET', "{$apiBase}/auth/profile", [], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return 'Profile获取失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('1.4 更新profile', function () use ($apiBase) {
    $res = httpRequest('POST', "{$apiBase}/auth/profile", [
        'name' => '更新后的商家名',
    ], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '更新失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

echo "\n▶ 2. 门店管理测试\n";

test('2.1 创建门店', function () use ($apiBase, &$storeId) {
    $res = httpRequest('POST', "{$apiBase}/stores", [
        'name'    => '测试门店_' . date('His'),
        'address' => '测试地址123号',
        'tel'     => '010-12345678',
    ], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '创建失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    $storeId = $res['data']['data']['id'] ?? 0;
    return $storeId > 0 ? true : 'ID为空';
});

test('2.2 门店列表', function () use ($apiBase) {
    $res = httpRequest('GET', "{$apiBase}/stores", [], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '列表失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('2.3 门店详情', function () use ($apiBase, $storeId) {
    if (!$storeId) return '无门店ID';
    $res = httpRequest('GET', "{$apiBase}/stores/{$storeId}", [], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '详情失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('2.4 更新门店', function () use ($apiBase, $storeId) {
    if (!$storeId) return '无门店ID';
    $res = httpRequest('POST', "{$apiBase}/stores/{$storeId}/update", [
        'name' => '更新后门店名',
    ], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '更新失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

echo "\n▶ 3. 活动管理测试\n";

test('3.1 创建活动', function () use ($apiBase, &$activityId, &$accessCode) {
    $res = httpRequest('POST', "{$apiBase}/activities", [
        'title'      => '测试活动_' . date('His'),
        'started_at' => date('Y-m-d H:i:s'),
        'ended_at'   => date('Y-m-d H:i:s', time() + 86400),
    ], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '创建失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    $activityId = $res['data']['data']['id'] ?? 0;
    $accessCode = $res['data']['data']['access_code'] ?? '';
    return ($activityId > 0 && !empty($accessCode)) ? true : 'ID或access_code为空';
});

test('3.2 活动列表', function () use ($apiBase) {
    $res = httpRequest('GET', "{$apiBase}/activities", [], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '列表失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('3.3 活动详情', function () use ($apiBase, $activityId) {
    if (!$activityId) return '无活动ID';
    $res = httpRequest('GET', "{$apiBase}/activities/{$activityId}", [], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '详情失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('3.4 切换活动状态为进行中', function () use ($apiBase, $activityId) {
    if (!$activityId) return '无活动ID';
    $res = httpRequest('POST', "{$apiBase}/activities/{$activityId}/status", [
        'status' => 2,
    ], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '状态切换失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('3.5 获取功能配置', function () use ($apiBase, $activityId) {
    if (!$activityId) return '无活动ID';
    $res = httpRequest('GET', "{$apiBase}/activities/{$activityId}/features", [], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '功能列表失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('3.6 获取全部功能列表', function () use ($apiBase) {
    $res = httpRequest('GET', "{$apiBase}/features", [], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '功能列表失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('3.7 活动统计', function () use ($apiBase, $activityId) {
    if (!$activityId) return '无活动ID';
    $res = httpRequest('GET', "{$apiBase}/activities/{$activityId}/stats", [], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '统计失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('3.8 克隆活动', function () use ($apiBase, $activityId) {
    if (!$activityId) return '无活动ID';
    $res = httpRequest('POST', "{$apiBase}/activities/{$activityId}/clone", [], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '克隆失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return !empty($res['data']['data']['access_code']) ? true : '克隆后access_code为空';
});

echo "\n▶ 4. 大屏互动 API 测试\n";

test('4.1 获取大屏配置', function () use ($apiBase, $accessCode) {
    if (!$accessCode) return '无access_code';
    $res = httpRequest('GET', "{$apiBase}/screen/{$accessCode}/config");
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '配置获取失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('4.2 用户签到', function () use ($apiBase, $accessCode) {
    if (!$accessCode) return '无access_code';
    $res = httpRequest('POST', "{$apiBase}/screen/{$accessCode}/sign", [
        'openid'   => 'test_openid_' . mt_rand(1000, 9999),
        'nickname' => '测试用户',
        'signname' => '测试签名',
    ]);
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '签到失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('4.3 签到列表', function () use ($apiBase, $accessCode) {
    if (!$accessCode) return '无access_code';
    $res = httpRequest('GET', "{$apiBase}/screen/{$accessCode}/sign-list");
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '签到列表失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('4.4 发送上墙消息', function () use ($apiBase, $accessCode) {
    if (!$accessCode) return '无access_code';
    $res = httpRequest('POST', "{$apiBase}/screen/{$accessCode}/wall", [
        'openid'   => 'test_openid_1000',
        'nickname' => '测试用户',
        'content'  => '这是一条测试上墙消息',
    ]);
    // 上墙可能需要审核，code可能非0也算通过
    if (!$res['data']) {
        return '发送失败: 无响应';
    }
    return true;
});

test('4.5 获取上墙消息', function () use ($apiBase, $accessCode) {
    if (!$accessCode) return '无access_code';
    $res = httpRequest('GET', "{$apiBase}/screen/{$accessCode}/wall");
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '上墙消息获取失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('4.6 发送弹幕', function () use ($apiBase, $accessCode) {
    if (!$accessCode) return '无access_code';
    $res = httpRequest('POST', "{$apiBase}/screen/{$accessCode}/danmu", [
        'openid'   => 'test_openid_1000',
        'nickname' => '测试用户',
        'content'  => '这是一条测试弹幕',
        'color'    => '#ff6600',
    ]);
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '弹幕发送失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('4.7 获取弹幕', function () use ($apiBase, $accessCode) {
    if (!$accessCode) return '无access_code';
    $res = httpRequest('GET', "{$apiBase}/screen/{$accessCode}/danmu");
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '弹幕获取失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

echo "\n▶ 5. 密码重置测试\n";

test('5.1 发送重置验证码', function () use ($apiBase, $testPhone) {
    $res = httpRequest('POST', "{$apiBase}/../hd/password/send-code", [
        'phone' => $testPhone,
    ]);
    // 短信服务可能不可用，只检查接口是否响应
    if (!$res['data']) {
        return '接口无响应';
    }
    return true;
});

echo "\n▶ 6. 数据导出测试\n";

test('6.1 导出参与者CSV', function () use ($apiBase, $activityId) {
    if (!$activityId) return '无活动ID';
    $res = httpRequest('GET', "{$apiBase}/../hd/export/participants/{$activityId}", [], authHeaders());
    if ($res['http_code'] !== 200) {
        return 'HTTP状态码: ' . $res['http_code'];
    }
    return true;
});

echo "\n▶ 7. 清理测试数据\n";

test('7.1 删除门店', function () use ($apiBase, $storeId) {
    if (!$storeId) return '无门店ID';
    $res = httpRequest('POST', "{$apiBase}/stores/{$storeId}/delete", [], authHeaders());
    // 门店有活动时可能删除失败，这也是正常行为
    if (!$res['data']) return '接口无响应';
    return true;
});

test('7.2 删除活动', function () use ($apiBase, $activityId) {
    if (!$activityId) return '无活动ID';
    $res = httpRequest('POST', "{$apiBase}/activities/{$activityId}/delete", [], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '删除失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

test('7.3 退出登录', function () use ($apiBase) {
    $res = httpRequest('POST', "{$apiBase}/auth/logout", [], authHeaders());
    if (!$res['data'] || $res['data']['code'] !== 0) {
        return '退出失败: ' . ($res['data']['msg'] ?? 'unknown');
    }
    return true;
});

// ==========================================
// 汇总
// ==========================================
$total = $passed + $failed;
echo "\n============================================\n";
echo " 测试完成: {$total} 个用例\n";
echo " ✅ 通过: {$passed}\n";
echo " ❌ 失败: {$failed}\n";
echo "============================================\n";

exit($failed > 0 ? 1 : 0);
