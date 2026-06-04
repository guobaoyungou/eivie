<?php
/**
 * 人像多用户关联数据检查脚本
 * 用于排查多用户关联是否在数据库中正确存储
 */

// 数据库配置
$config = [
    'hostname' => 'localhost',
    'database' => 'guobaoyungou_cn',
    'username' => 'guobaoyungou_cn',
    'password' => '5ArfhRr9xzyScrF5',
    'hostport' => '3306',
];

$prefix = 'ddwx_';

try {
    $dsn = "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "<h2>人像多用户关联数据检查</h2>";
    echo "<style>
        body { font-family: 'Microsoft YaHei', Arial, sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .ok { color: green; }
        .warn { color: orange; }
        .error { color: red; }
        h3 { margin-top: 30px; border-bottom: 2px solid #333; padding-bottom: 5px; }
        .count-badge { background: #007cba; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
    </style>";

    // === 1. 检查关联表总数据 ===
    echo "<h3>1. 关联表 (ai_travel_photo_portrait_user) 总览</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(DISTINCT portrait_id) as unique_portraits, COUNT(DISTINCT user_openid) as unique_users FROM {$prefix}ai_travel_photo_portrait_user");
    $stats = $stmt->fetch();
    echo "<table><tr><th>总关联记录</th><th>关联的人像数量</th><th>关联的用户数量</th></tr>";
    echo "<tr><td>{$stats['total']}</td><td>{$stats['unique_portraits']}</td><td>{$stats['unique_users']}</td></tr></table>";

    // === 2. 找出有多个用户关联的人像 ===
    echo "<h3>2. 多用户关联的人像 (关联用户数 > 1)</h3>";
    $stmt = $pdo->query("
        SELECT portrait_id, COUNT(*) as user_count, GROUP_CONCAT(user_openid ORDER BY id ASC SEPARATOR '\n') as all_openids
        FROM {$prefix}ai_travel_photo_portrait_user
        GROUP BY portrait_id
        HAVING user_count > 1
        ORDER BY user_count DESC
    ");
    $multiUsers = $stmt->fetchAll();

    if (empty($multiUsers)) {
        echo "<p class='warn'>⚠️ 没有发现多用户关联的记录！所有 portrait 最多只有 1 个关联用户。</p>";
        echo "<p>这就解释了为什么页面只显示一个用户 —— 数据库中没有多个用户关联同一人像的数据。</p>";
    } else {
        echo "<p class='ok'>✓ 发现 " . count($multiUsers) . " 个人像存在多用户关联：</p>";
        echo "<table><tr><th>Portrait ID</th><th>关联用户数</th><th>所有 OpenID</th></tr>";
        foreach ($multiUsers as $row) {
            $openids = explode("\n", $row['all_openids']);
            $display = '';
            foreach ($openids as $oid) {
                $short = mb_substr($oid, 0, 6) . '...' . mb_substr($oid, -4);
                $display .= $short . "<br>";
            }
            echo "<tr><td>{$row['portrait_id']}</td><td><span class='count-badge'>{$row['user_count']}</span></td><td>{$display}</td></tr>";
        }
        echo "</table>";
    }

    // === 3. 列出所有关联记录（最近20条） ===
    echo "<h3>3. 关联表最新20条记录</h3>";
    $stmt = $pdo->query("
        SELECT id, portrait_id, user_openid, create_time
        FROM {$prefix}ai_travel_photo_portrait_user
        ORDER BY id DESC
        LIMIT 20
    ");
    $records = $stmt->fetchAll();
    if (!empty($records)) {
        echo "<table><tr><th>ID</th><th>Portrait ID</th><th>User OpenID (短)</th><th>User OpenID (完整)</th><th>创建时间</th></tr>";
        foreach ($records as $r) {
            $short = mb_substr($r['user_openid'], 0, 6) . '...' . mb_substr($r['user_openid'], -4);
            echo "<tr><td>{$r['id']}</td><td>{$r['portrait_id']}</td><td>{$short}</td><td style='font-size:11px;word-break:break-all'>{$r['user_openid']}</td><td>{$r['create_time']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warn'>⚠️ 关联表为空！没有任何关联记录。</p>";
    }

    // === 4. 检查前端查询与数据匹配 ===
    echo "<h3>4. 人像表与关联表匹配检查</h3>";
    echo "<p>以下检查控制器代码中 <code>\$portraitUserMap[\$item['id']]</code> 能否正确匹配：</p>";

    // 取几个 portrait 表的 id，检查它们的类型和关联表中的 portrait_id 类型
    $stmt = $pdo->query("SELECT id FROM {$prefix}ai_travel_photo_portrait ORDER BY id DESC LIMIT 10");
    $portraitIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $matchIssues = [];
    foreach ($portraitIds as $pid) {
        // 检查关联表
        $stmt2 = $pdo->prepare("SELECT COUNT(*) as cnt, GROUP_CONCAT(user_openid) as oids FROM {$prefix}ai_travel_photo_portrait_user WHERE portrait_id = ?");
        $stmt2->execute([$pid]);
        $result = $stmt2->fetch();

        // 也检查下字符串类型的匹配
        $stmt3 = $pdo->prepare("SELECT COUNT(*) as cnt FROM {$prefix}ai_travel_photo_portrait_user WHERE portrait_id = ?");
        $stmt3->execute([(string)$pid]);
        $resultStr = $stmt3->fetch();

        if ($result['cnt'] != $resultStr['cnt']) {
            $matchIssues[] = "Portrait ID {$pid}: int查询={$result['cnt']}条, string查询={$resultStr['cnt']}条 (不匹配!)";
        }

        if ($result['cnt'] > 0) {
            echo "<p>Portrait ID <b>{$pid}</b>: 关联 <b>{$result['cnt']}</b> 个用户 → " . htmlspecialchars($result['oids']) . "</p>";
        }
    }

    if (!empty($matchIssues)) {
        echo "<p class='error'>❌ 发现类型匹配问题：</p>";
        foreach ($matchIssues as $issue) {
            echo "<p class='error'>{$issue}</p>";
        }
    } else {
        echo "<p class='ok'>✓ 类型匹配正常，int 和 string 查询结果一致</p>";
    }

    // === 5. 检查关联表结构 ===
    echo "<h3>5. 关联表结构</h3>";
    $stmt = $pdo->query("DESCRIBE {$prefix}ai_travel_photo_portrait_user");
    $columns = $stmt->fetchAll();
    echo "<table><tr><th>字段</th><th>类型</th><th>Null</th><th>Key</th><th>默认值</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td><td>{$col['Extra']}</td></tr>";
    }
    echo "</table>";

    // === 结论 ===
    echo "<h3>6. 诊断结论</h3>";
    if (empty($multiUsers)) {
        echo "<p class='error'>❌ <b>根本原因：数据库中没有任何人像有多个用户关联。</b></p>";
        echo "<p>虽然您说测试过多用户关联，但关联表 <code>{$prefix}ai_travel_photo_portrait_user</code> 中每个人像最多只有 1 条记录。</p>";
        echo "<p>这可能是以下原因之一：</p>";
        echo "<ul>";
        echo "<li>多用户关联写入时可能被覆盖（INSERT 变成了 REPLACE 或 UPDATE）</li>";
        echo "<li>可能存在唯一索引限制，导致同一个 portrait_id 只能关联一个用户</li>";
        echo "<li>用户关联写入的代码可能存在 bug</li>";
        echo "</ul>";
    } else {
        echo "<p class='ok'>✓ 数据库中存在多用户关联记录。</p>";
        echo "<p>问题出在前端数据匹配环节，需检查 PHP 的 <code>\$portraitUserMap[\$item['id']]</code> 类型匹配。</p>";
    }

} catch (Exception $e) {
    echo "<h2 class='error'>数据库连接失败</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
