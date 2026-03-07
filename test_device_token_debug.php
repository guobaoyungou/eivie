<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>设备令牌生成调试工具</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 6px;
            border-left: 4px solid #4CAF50;
        }
        .test-section h2 {
            color: #4CAF50;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        button:hover {
            background: #45a049;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .result.info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4CAF50;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            vertical-align: middle;
            margin-left: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .debug-info {
            margin-top: 10px;
            padding: 10px;
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 设备令牌生成调试工具</h1>
        
        <div class="test-section">
            <h2>测试1：直接AJAX调用</h2>
            <div class="form-group">
                <label>设备名称：</label>
                <input type="text" id="deviceName" value="测试设备001" placeholder="请输入设备名称">
            </div>
            <div class="form-group">
                <label>门店ID：</label>
                <input type="number" id="mdid" value="1" placeholder="请输入门店ID">
            </div>
            <button onclick="testDirectCall()" id="btnTest1">测试生成令牌</button>
            <div id="result1"></div>
        </div>
        
        <div class="test-section">
            <h2>测试2：检查网络请求</h2>
            <button onclick="checkNetwork()">检查网络连接</button>
            <div id="result2"></div>
        </div>
        
        <div class="test-section">
            <h2>测试3：查看日志</h2>
            <button onclick="viewLogs()">查看最新日志</button>
            <div id="result3"></div>
        </div>
    </div>

    <script>
        // 测试1：直接AJAX调用
        function testDirectCall() {
            const deviceName = document.getElementById('deviceName').value;
            const mdid = document.getElementById('mdid').value;
            const resultDiv = document.getElementById('result1');
            const btn = document.getElementById('btnTest1');
            
            if (!deviceName) {
                resultDiv.innerHTML = '<div class="result error">请输入设备名称</div>';
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = '测试中<span class="loading"></span>';
            resultDiv.innerHTML = '<div class="result info">正在发送请求...</div>';
            
            const startTime = Date.now();
            
            fetch('<?php echo $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"]; ?>/index.php/AiTravelPhoto/device_generate_token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'device_name=' + encodeURIComponent(deviceName) + '&mdid=' + mdid
            })
            .then(response => {
                const duration = Date.now() - startTime;
                console.log('响应状态:', response.status);
                console.log('响应头:', response.headers);
                
                return response.text().then(text => ({
                    status: response.status,
                    statusText: response.statusText,
                    text: text,
                    duration: duration
                }));
            })
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '测试生成令牌';
                
                let result = '<div class="result ' + (data.status === 200 ? 'success' : 'error') + '">';
                result += '<strong>HTTP状态:</strong> ' + data.status + ' ' + data.statusText + '\n';
                result += '<strong>响应时间:</strong> ' + data.duration + 'ms\n';
                result += '<strong>响应内容:</strong>\n' + data.text;
                result += '</div>';
                
                result += '<div class="debug-info">';
                result += '<strong>调试信息：</strong><br>';
                result += '请求URL: ' + window.location.origin + '/index.php/AiTravelPhoto/device_generate_token<br>';
                result += '请求方法: POST<br>';
                result += '请求参数: device_name=' + deviceName + '&mdid=' + mdid;
                result += '</div>';
                
                resultDiv.innerHTML = result;
                
                // 尝试解析JSON
                try {
                    const json = JSON.parse(data.text);
                    console.log('解析后的JSON:', json);
                    if (json.status === 1 && json.data && json.data.device_token) {
                        resultDiv.innerHTML += '<div class="result success" style="margin-top:10px;">';
                        resultDiv.innerHTML += '<strong>✅ 令牌生成成功！</strong><br>';
                        resultDiv.innerHTML += '<textarea readonly style="width:100%;height:60px;margin-top:10px;padding:5px;font-family:monospace;">' + json.data.device_token + '</textarea>';
                        resultDiv.innerHTML += '</div>';
                    }
                } catch (e) {
                    console.error('JSON解析失败:', e);
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = '测试生成令牌';
                
                resultDiv.innerHTML = '<div class="result error">';
                resultDiv.innerHTML += '<strong>❌ 请求失败</strong>\n';
                resultDiv.innerHTML += '错误信息: ' + error.message + '\n';
                resultDiv.innerHTML += '\n可能原因：\n';
                resultDiv.innerHTML += '1. 网络连接问题\n';
                resultDiv.innerHTML += '2. 服务器未响应\n';
                resultDiv.innerHTML += '3. 跨域问题\n';
                resultDiv.innerHTML += '4. PHP错误导致请求中断';
                resultDiv.innerHTML += '</div>';
                
                console.error('请求错误:', error);
            });
        }
        
        // 测试2：检查网络
        function checkNetwork() {
            const resultDiv = document.getElementById('result2');
            resultDiv.innerHTML = '<div class="result info">检查中...</div>';
            
            const tests = [
                {
                    name: '当前页面访问',
                    url: window.location.href
                },
                {
                    name: 'AiTravelPhoto控制器访问',
                    url: '<?php echo $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"]; ?>/index.php/AiTravelPhoto/device_list'
                },
                {
                    name: 'device_generate_token方法访问',
                    url: '<?php echo $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"]; ?>/index.php/AiTravelPhoto/device_generate_token'
                }
            ];
            
            let results = '';
            let completed = 0;
            
            tests.forEach((test, index) => {
                fetch(test.url, {
                    method: 'HEAD',
                    cache: 'no-cache'
                })
                .then(response => {
                    results += `${index + 1}. ${test.name}: ✅ ${response.status} ${response.statusText}\n`;
                })
                .catch(error => {
                    results += `${index + 1}. ${test.name}: ❌ ${error.message}\n`;
                })
                .finally(() => {
                    completed++;
                    if (completed === tests.length) {
                        resultDiv.innerHTML = '<div class="result info">' + results + '</div>';
                    }
                });
            });
        }
        
        // 测试3：查看日志
        function viewLogs() {
            const resultDiv = document.getElementById('result3');
            resultDiv.innerHTML = '<div class="result info">正在读取日志...</div>';
            
            fetch('<?php echo $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"]; ?>/index.php/AiTravelPhoto/test', {
                method: 'GET'
            })
            .then(response => response.text())
            .then(html => {
                resultDiv.innerHTML = '<div class="result info">';
                resultDiv.innerHTML += '<strong>测试方法输出：</strong><br>';
                resultDiv.innerHTML += '<iframe srcdoc="' + html.replace(/"/g, '&quot;') + '" style="width:100%;height:400px;border:1px solid #ddd;margin-top:10px;"></iframe>';
                resultDiv.innerHTML += '</div>';
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="result error">读取失败: ' + error.message + '</div>';
            });
        }
        
        // 页面加载时自动检查
        window.onload = function() {
            console.log('调试工具已加载');
            console.log('当前URL:', window.location.href);
            console.log('API地址:', window.location.origin + '/index.php/AiTravelPhoto/device_generate_token');
        };
    </script>
</body>
</html>
