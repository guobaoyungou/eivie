# InsightFace + FairFace 一体化识别服务 — 独立服务器部署指南

> **版本**: v1.0 | **适用**: `deploy/fairface_server.py` | **默认端口**: 8867

---

## 目录

1. [服务器要求](#1-服务器要求)
2. [环境准备](#2-环境准备)
3. [项目文件部署](#3-项目文件部署)
4. [模型权重下载](#4-模型权重下载)
5. [安装 Python 依赖](#5-安装-python-依赖)
6. [启动与验证](#6-启动与验证)
7. [生产环境部署（Systemd）](#7-生产环境部署systemd)
8. [Nginx 反向代理（可选）](#8-nginx-反向代理可选)
9. [PHP 后端连接配置](#9-php-后端连接配置)
10. [防火墙与安全](#10-防火墙与安全)
11. [运维与监控](#11-运维与监控)
12. [常见问题排查](#12-常见问题排查)

---

## 1. 服务器要求

### 1.1 硬件要求

| 配置项 | 最低要求 | 推荐配置 |
|--------|---------|---------|
| **CPU** | 4 核 | 8 核+ |
| **内存** | 4 GB | 8 GB+ |
| **磁盘** | 10 GB 可用空间 | 20 GB+ SSD |
| **GPU**（可选） | NVIDIA GPU ≥ 4GB 显存 | NVIDIA T4 / RTX 3060+ |

> **说明**：
> - **有 GPU**：推理速度约 50~100ms/张，推荐生产环境使用
> - **无 GPU**：纯 CPU 推理速度约 500~2000ms/张，低频使用可接受
> - InsightFace 人脸检测模型（buffalo_l）首次运行约占用 1.5 GB 内存
> - FairFace ResNet-34 约占用 0.5 GB 内存/显存

### 1.2 软件要求

| 软件 | 版本要求 |
|------|---------|
| **操作系统** | Ubuntu 20.04+ / CentOS 7+ / Debian 10+ |
| **Python** | 3.8+ (推荐 3.10) |
| **pip** | 最新版 |
| **NVIDIA 驱动** | ≥ 470（仅 GPU 模式） |
| **CUDA** | ≥ 11.6（仅 GPU 模式） |

---

## 2. 环境准备

### 2.1 安装 Python 3.10（如系统自带版本 < 3.8）

**Ubuntu/Debian:**

```bash
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:deadsnakes/ppa
sudo apt install -y python3.10 python3.10-venv python3.10-dev python3-pip
```

**CentOS 7/8:**

```bash
sudo yum install -y epel-release
sudo yum install -y python310 python310-pip python310-devel
# 或通过源码编译
```

### 2.2 验证 Python 版本

```bash
python3 --version
# 输出应 >= 3.8，如 Python 3.10.x
```

### 2.3 GPU 环境检查（可选）

如果服务器有 NVIDIA GPU：

```bash
# 检查 NVIDIA 驱动
nvidia-smi

# 输出类似：
# NVIDIA-SMI 535.129.03   Driver Version: 535.129.03   CUDA Version: 12.2
# GPU 0: NVIDIA T4 (UUID: ...)
```

如果 `nvidia-smi` 不可用，说明需要先安装 NVIDIA 驱动：

```bash
# Ubuntu
sudo apt install -y nvidia-driver-535
sudo reboot
```

---

## 3. 项目文件部署

### 3.1 创建部署目录

```bash
sudo mkdir -p /opt/fairface-server
sudo mkdir -p /opt/fairface-server/models
sudo mkdir -p /opt/fairface-server/logs
```

### 3.2 上传服务文件

将 `deploy/fairface_server.py` 从主服务器传输到目标服务器：

```bash
# 在主服务器（ai.eivie.cn）上执行：
scp /home/www/ai.eivie.cn/deploy/fairface_server.py \
    user@目标服务器IP:/opt/fairface-server/fairface_server.py
```

或者手动复制该文件到目标服务器的 `/opt/fairface-server/fairface_server.py`。

### 3.3 最终目录结构

```
/opt/fairface-server/
├── fairface_server.py                          # 主程序文件
├── models/
│   └── res34_fair_align_multi_7_20190809.pt    # FairFace 权重（下一步下载）
├── logs/
│   └── fairface.log                            # 运行日志
└── venv/                                       # Python 虚拟环境（下一步创建）
```

---

## 4. 模型权重下载

需要下载 **两个模型**，其中 InsightFace 的模型会在首次启动时自动下载。

### 4.1 FairFace 权重（必须手动下载）

```bash
cd /opt/fairface-server/models

# 方式一：Google Drive 下载（推荐，需要科学上网或浏览器下载后上传）
# 下载地址: https://drive.google.com/file/d/1i1L3Yqwaio7YSOCj7ftgk8ZZchPG7dmH
# 文件名: res34_fair_align_multi_7_20190809.pt（约 83MB）

# 方式二：使用 gdown 工具下载
pip3 install gdown
gdown "https://drive.google.com/uc?id=1i1L3Yqwaio7YSOCj7ftgk8ZZchPG7dmH" \
      -O /opt/fairface-server/models/res34_fair_align_multi_7_20190809.pt

# 方式三：浏览器下载后 scp 上传
scp res34_fair_align_multi_7_20190809.pt \
    user@目标服务器IP:/opt/fairface-server/models/
```

**验证权重文件：**

```bash
ls -lh /opt/fairface-server/models/res34_fair_align_multi_7_20190809.pt
# 文件大小约 83MB
```

### 4.2 InsightFace 模型（自动下载）

InsightFace 的 `buffalo_l` 模型包会在 **首次启动时自动下载** 到 `~/.insightface/models/buffalo_l/`。

> 如果服务器无法访问外网，需要手动下载并放置：

```bash
# 手动下载地址（任选一个）：
# https://github.com/deepinsight/insightface/releases/download/v0.7/buffalo_l.zip
# 解压后放到以下路径：

mkdir -p ~/.insightface/models/buffalo_l
# 将解压出的文件放到 ~/.insightface/models/buffalo_l/ 目录下
```

`buffalo_l` 模型包内容（约 320MB 解压后）：

```
~/.insightface/models/buffalo_l/
├── 1k3d68.onnx
├── 2d106det.onnx
├── det_10g.onnx          # ← SCRFD 人脸检测（核心）
├── genderage.onnx
└── w600k_r50.onnx        # ← ArcFace 人脸特征提取
```

---

## 5. 安装 Python 依赖

### 5.1 创建虚拟环境

```bash
cd /opt/fairface-server
python3 -m venv venv
source venv/bin/activate
```

### 5.2 升级 pip

```bash
pip install --upgrade pip setuptools wheel
```

### 5.3 安装依赖包

**GPU 环境（推荐）：**

```bash
# PyTorch（GPU 版本 - 根据 CUDA 版本选择）
# CUDA 11.8:
pip install torch torchvision --index-url https://download.pytorch.org/whl/cu118

# CUDA 12.1:
pip install torch torchvision --index-url https://download.pytorch.org/whl/cu121

# 其他依赖
pip install insightface onnxruntime-gpu fastapi uvicorn python-multipart \
            Pillow numpy requests
```

**CPU 环境：**

```bash
pip install torch torchvision --index-url https://download.pytorch.org/whl/cpu

pip install insightface onnxruntime fastapi uvicorn python-multipart \
            Pillow numpy requests
```

### 5.4 验证安装

```bash
source /opt/fairface-server/venv/bin/activate

python3 -c "
import torch
import insightface
import fastapi
import torchvision

print(f'PyTorch: {torch.__version__}')
print(f'CUDA available: {torch.cuda.is_available()}')
if torch.cuda.is_available():
    print(f'CUDA device: {torch.cuda.get_device_name(0)}')
print(f'InsightFace: {insightface.__version__}')
print(f'FastAPI: {fastapi.__version__}')
print(f'torchvision: {torchvision.__version__}')
print('所有依赖安装成功!')
"
```

预期输出：

```
PyTorch: 2.x.x
CUDA available: True  (GPU环境) / False (CPU环境)
CUDA device: NVIDIA T4  (GPU环境)
InsightFace: 0.7.x
FastAPI: 0.1xx.x
torchvision: 0.x.x
所有依赖安装成功!
```

---

## 6. 启动与验证

### 6.1 首次手动启动（测试）

```bash
cd /opt/fairface-server
source venv/bin/activate

python fairface_server.py \
    --host 0.0.0.0 \
    --port 8867 \
    --det-model buffalo_l \
    --fairface-weights ./models/res34_fair_align_multi_7_20190809.pt \
    --device auto \
    --gpu-id 0
```

> **注意**：首次启动时 InsightFace 会自动下载 `buffalo_l` 模型包（约 320MB），请耐心等待。

启动成功后输出类似：

```
2026-04-16 10:00:00 [INFO] 正在加载 InsightFace 模型: buffalo_l
2026-04-16 10:00:05 [INFO] InsightFace 模型加载完成!
2026-04-16 10:00:05 [INFO] 正在加载 FairFace 模型: ./models/res34_fair_align_multi_7_20190809.pt (device=cuda:0)
2026-04-16 10:00:06 [INFO] FairFace 模型加载完成!
2026-04-16 10:00:06 [INFO] InsightFace + FairFace API Server 启动于 http://0.0.0.0:8867
2026-04-16 10:00:06 [INFO] API 文档: http://0.0.0.0:8867/docs
INFO:     Started server process [12345]
INFO:     Waiting for application startup.
INFO:     Application startup complete.
INFO:     Uvicorn running on http://0.0.0.0:8867 (Press CTRL+C to quit)
```

### 6.2 验证服务

**在目标服务器上执行：**

```bash
# 1. 健康检查
curl http://127.0.0.1:8867/api/health
```

预期响应：

```json
{
  "status": "ok",
  "insightface_loaded": true,
  "fairface_loaded": true,
  "model": "InsightFace + FairFace"
}
```

```bash
# 2. 模型信息
curl http://127.0.0.1:8867/api/info
```

```bash
# 3. 测试识别（使用任意公开可访问的人像图片 URL）
curl -X POST http://127.0.0.1:8867/api/analyze \
     -H "Content-Type: application/json" \
     -d '{
       "image_url": "https://upload.wikimedia.org/wikipedia/commons/thumb/a/a7/Camponotus_flavomarginatus_ant.jpg/320px-Camponotus_flavomarginatus_ant.jpg",
       "detect_body_type": true
     }'
```

> 上面使用了一张蚂蚁图片测试"无人脸"场景，预期返回 `face_count: 0`。
> 请替换为实际人像图片 URL 进行完整测试。

```bash
# 4. 测试有人脸的图片
curl -X POST http://127.0.0.1:8867/api/analyze \
     -H "Content-Type: application/json" \
     -d '{
       "image_url": "你的人像图片URL"
     }'
```

预期响应结构：

```json
{
  "status": "success",
  "faces": [
    {
      "bbox": [102.3, 85.1, 245.7, 289.4],
      "bbox_area": 29287.5,
      "gender": "Female",
      "gender_confidence": 0.9521,
      "age_group": "20-29",
      "age_confidence": 0.8734,
      "race": "East Asian",
      "race_confidence": 0.9182,
      "body_type": null,
      "body_type_confidence": null
    }
  ],
  "face_count": 1,
  "analysis_time": 0.342
}
```

### 6.3 交互式 API 文档

启动后可访问浏览器查看自动生成的 API 文档：

```
http://目标服务器IP:8867/docs
```

---

## 7. 生产环境部署（Systemd）

### 7.1 创建 Systemd 服务文件

```bash
sudo tee /etc/systemd/system/fairface-server.service > /dev/null << 'EOF'
[Unit]
Description=InsightFace + FairFace 人物属性识别 API 服务
After=network.target
Wants=network-online.target

[Service]
Type=simple
User=root
Group=root
WorkingDirectory=/opt/fairface-server
Environment="PATH=/opt/fairface-server/venv/bin:/usr/local/bin:/usr/bin"

ExecStart=/opt/fairface-server/venv/bin/python \
    /opt/fairface-server/fairface_server.py \
    --host 0.0.0.0 \
    --port 8867 \
    --det-model buffalo_l \
    --fairface-weights /opt/fairface-server/models/res34_fair_align_multi_7_20190809.pt \
    --device auto \
    --gpu-id 0 \
    --workers 1

Restart=always
RestartSec=10
StartLimitBurst=5
StartLimitIntervalSec=60

# 日志输出
StandardOutput=append:/opt/fairface-server/logs/fairface.log
StandardError=append:/opt/fairface-server/logs/fairface.log

# 资源限制
LimitNOFILE=65536
LimitNPROC=4096

# 环境变量（GPU 相关）
Environment="CUDA_VISIBLE_DEVICES=0"
Environment="OMP_NUM_THREADS=4"

[Install]
WantedBy=multi-user.target
EOF
```

> **纯 CPU 环境** 修改点：
> - 将 `--gpu-id 0` 改为 `--gpu-id -1`
> - 将 `--device auto` 改为 `--device cpu`
> - 删除 `CUDA_VISIBLE_DEVICES` 行

### 7.2 启用并启动服务

```bash
# 重新加载 systemd 配置
sudo systemctl daemon-reload

# 启动服务
sudo systemctl start fairface-server

# 设为开机自启
sudo systemctl enable fairface-server

# 查看状态
sudo systemctl status fairface-server
```

### 7.3 常用管理命令

```bash
# 启动
sudo systemctl start fairface-server

# 停止
sudo systemctl stop fairface-server

# 重启
sudo systemctl restart fairface-server

# 查看状态
sudo systemctl status fairface-server

# 查看实时日志
tail -f /opt/fairface-server/logs/fairface.log

# 查看 journalctl 日志
journalctl -u fairface-server -f --no-pager
```

### 7.4 日志轮转配置

```bash
sudo tee /etc/logrotate.d/fairface-server > /dev/null << 'EOF'
/opt/fairface-server/logs/fairface.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
    su root root
}
EOF
```

---

## 8. Nginx 反向代理（可选）

如果需要通过域名或 443 端口访问识别服务：

```nginx
# /etc/nginx/conf.d/fairface-api.conf

upstream fairface_backend {
    server 127.0.0.1:8867;
    keepalive 32;
}

server {
    listen 80;
    server_name fairface-api.your-domain.com;  # 替换为实际域名

    # 如有 HTTPS 需求取消下面注释
    # listen 443 ssl;
    # ssl_certificate     /path/to/cert.pem;
    # ssl_certificate_key /path/to/key.pem;

    location / {
        proxy_pass http://fairface_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # 超时设置（识别可能较耗时）
        proxy_connect_timeout 10s;
        proxy_read_timeout 60s;
        proxy_send_timeout 30s;

        # 禁用缓冲（流式传输）
        proxy_buffering off;

        # 文件大小限制（支持 Base64 图片上传）
        client_max_body_size 20m;
    }

    # 健康检查端点
    location /api/health {
        proxy_pass http://fairface_backend/api/health;
        access_log off;
    }
}
```

```bash
# 检查 nginx 配置
sudo nginx -t

# 重载 nginx
sudo systemctl reload nginx
```

---

## 9. PHP 后端连接配置

在 PHP 主服务器（ai.eivie.cn）上，将识别服务地址改为远程服务器。

### 9.1 通过后台界面配置（推荐）

登录管理后台：`https://ai.eivie.cn/?s=/Backstage/index`

进入 **系统 → 系统设置 → 基础设置**，找到 **「场景模板自动标签识别」** 区域：

| 配置项 | 值 |
|--------|---|
| **启用自动标签** | 开启 |
| **FairFace 服务地址** | `http://目标服务器IP:8867`（替换为实际 IP） |
| **请求超时时间** | 30 秒（远程服务器可适当增大，如 60） |

点击 **提交** 保存。

### 9.2 通过配置文件配置（备用）

编辑 `/home/www/ai.eivie.cn/config/auto_tagging.php`：

```php
return [
    // ← 修改为远程服务器地址
    'fairface_api_url' => 'http://远程服务器IP:8867',

    // 远程调用建议适当增加超时
    'fairface_timeout' => 60,

    // 其他配置保持不变...
];
```

> ⚠️ **注意**：后台系统设置中的配置优先级高于 config 文件，两处都修改可确保生效。

### 9.3 连通性验证

在 PHP 主服务器上测试能否访问远程识别服务：

```bash
# 在 ai.eivie.cn 服务器上执行
curl -v http://远程服务器IP:8867/api/health
```

如果返回 `{"status": "ok", ...}` 则连通正常。

---

## 10. 防火墙与安全

### 10.1 防火墙开放端口

**目标服务器（运行识别服务的服务器）：**

```bash
# UFW (Ubuntu)
sudo ufw allow from PHP主服务器IP to any port 8867

# firewalld (CentOS)
sudo firewall-cmd --permanent --add-rich-rule='rule family="ipv4" source address="PHP主服务器IP" port protocol="tcp" port="8867" accept'
sudo firewall-cmd --reload

# iptables
sudo iptables -A INPUT -p tcp -s PHP主服务器IP --dport 8867 -j ACCEPT
```

> ⚠️ **安全建议**：**不要** 将 8867 端口对公网全部开放，仅允许 PHP 主服务器 IP 访问。

### 10.2 安全加固建议

| 措施 | 说明 |
|------|------|
| IP 白名单 | 仅允许 PHP 主服务器 IP 访问 8867 端口 |
| 内网部署 | 如两台服务器在同一内网，使用内网 IP 通信 |
| API Key 认证 | 可在 Nginx 层添加简单的 Header 认证 |
| HTTPS | 如走公网传输，建议配置 HTTPS |

### 10.3 可选：添加简单 API Key 认证

在 Nginx 中添加 Header 校验：

```nginx
location / {
    # 校验请求头中的 API Key
    if ($http_x_api_key != "你的密钥字符串") {
        return 403;
    }
    proxy_pass http://fairface_backend;
    # ...其他配置
}
```

PHP 端调用时在 `AutoTaggingService.php` 的 `callFairFaceApi()` 方法中添加对应 Header。

---

## 11. 运维与监控

### 11.1 健康检查脚本

```bash
sudo tee /opt/fairface-server/health_check.sh > /dev/null << 'SCRIPT'
#!/bin/bash
# FairFace 服务健康检查

ENDPOINT="http://127.0.0.1:8867/api/health"
LOG="/opt/fairface-server/logs/health_check.log"

response=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "$ENDPOINT")

if [ "$response" != "200" ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') [ALERT] 服务异常! HTTP=$response, 正在重启..." >> "$LOG"
    systemctl restart fairface-server
    echo "$(date '+%Y-%m-%d %H:%M:%S') [INFO] 服务已重启" >> "$LOG"
else
    echo "$(date '+%Y-%m-%d %H:%M:%S') [OK] 服务正常" >> "$LOG"
fi
SCRIPT

chmod +x /opt/fairface-server/health_check.sh
```

### 11.2 定时健康检查（Crontab）

```bash
# 每 5 分钟检查一次
(crontab -l 2>/dev/null; echo "*/5 * * * * /opt/fairface-server/health_check.sh") | crontab -
```

### 11.3 GPU 监控（有 GPU 时）

```bash
# 实时查看 GPU 使用情况
watch -n 1 nvidia-smi

# 查看服务进程 GPU 占用
nvidia-smi --query-compute-apps=pid,name,used_memory --format=csv
```

### 11.4 性能基准参考

| 硬件 | 单图推理时间 | 适合场景 |
|------|------------|---------|
| NVIDIA T4 | ~80ms | 生产环境 |
| NVIDIA RTX 3060 | ~50ms | 生产环境 |
| NVIDIA V100 | ~30ms | 高并发 |
| CPU (8核 Xeon) | ~800ms | 低频/测试 |
| CPU (4核) | ~1500ms | 仅测试 |

---

## 12. 常见问题排查

### Q1: 启动时报 `InsightFace 模型下载失败`

**原因**：服务器无法访问 GitHub / InsightFace 官方模型仓库。

**解决**：手动下载 `buffalo_l` 模型包并放置到正确位置：

```bash
# 在能访问外网的机器上下载
wget https://github.com/deepinsight/insightface/releases/download/v0.7/buffalo_l.zip

# 上传到目标服务器并解压
mkdir -p ~/.insightface/models/buffalo_l
unzip buffalo_l.zip -d ~/.insightface/models/buffalo_l/
```

### Q2: 报错 `CUDA out of memory`

**解决**：

```bash
# 方案一：切换为 CPU 模式
python fairface_server.py --device cpu --gpu-id -1

# 方案二：限制 GPU 显存（在 systemd 中添加）
Environment="PYTORCH_CUDA_ALLOC_CONF=max_split_size_mb:512"
```

### Q3: `onnxruntime` 报 `CUDAExecutionProvider not available`

**原因**：安装的是 CPU 版 onnxruntime。

**解决**：

```bash
pip uninstall onnxruntime onnxruntime-gpu -y
pip install onnxruntime-gpu
```

> 如果 GPU 驱动版本与 onnxruntime-gpu 不兼容，请检查[兼容矩阵](https://onnxruntime.ai/docs/execution-providers/CUDA-ExecutionProvider.html#requirements)。

### Q4: FairFace 权重加载报 `KeyError` 或 `shape mismatch`

**原因**：权重文件版本与代码不匹配。

**解决**：确保使用的是 `res34_fair_align_multi_7_20190809.pt` 这个特定文件。代码已内置两种权重格式的兼容加载逻辑（单fc合并18维 / 多头分离模式）。

### Q5: PHP 端报 `FairFace 服务请求失败: Connection refused`

**检查清单**：

```bash
# 1. 确认服务正在运行
sudo systemctl status fairface-server

# 2. 确认端口监听
ss -tlnp | grep 8867

# 3. 从 PHP 服务器测试连通性
curl http://远程IP:8867/api/health

# 4. 检查防火墙
sudo ufw status          # Ubuntu
sudo firewall-cmd --list-all  # CentOS
```

### Q6: 识别结果为空（`face_count: 0`）

**可能原因**：

- 图片中确实没有人脸
- 图片 URL 无法从识别服务器下载（检查图片是否需要认证/是否为内网地址）
- 图片分辨率过低 / 人脸太小

**排查**：

```bash
# 检查图片从识别服务器是否可下载
curl -I "图片URL"

# 使用日志查看详细信息
tail -50 /opt/fairface-server/logs/fairface.log
```

### Q7: 服务响应缓慢

**优化建议**：

```bash
# 1. 确认 GPU 是否正在使用（应使用 GPU 而非 CPU）
nvidia-smi

# 2. 确保 OMP 线程数合理
export OMP_NUM_THREADS=4

# 3. 检查是否有其他进程抢占 GPU 资源
nvidia-smi --query-compute-apps=pid,name,used_memory --format=csv
```

---

## 快速部署流程（TL;DR）

```bash
# 1. 创建目录
sudo mkdir -p /opt/fairface-server/models /opt/fairface-server/logs

# 2. 上传 fairface_server.py
scp deploy/fairface_server.py user@目标IP:/opt/fairface-server/

# 3. 创建虚拟环境 + 安装依赖
cd /opt/fairface-server
python3 -m venv venv && source venv/bin/activate
pip install --upgrade pip
pip install torch torchvision --index-url https://download.pytorch.org/whl/cu118
pip install insightface onnxruntime-gpu fastapi uvicorn python-multipart Pillow numpy requests

# 4. 下载 FairFace 权重
pip install gdown
gdown "https://drive.google.com/uc?id=1i1L3Yqwaio7YSOCj7ftgk8ZZchPG7dmH" \
      -O models/res34_fair_align_multi_7_20190809.pt

# 5. 测试启动
python fairface_server.py --host 0.0.0.0 --port 8867

# 6. 验证
curl http://127.0.0.1:8867/api/health

# 7. 配置 systemd 开机自启（见第 7 节）

# 8. 在 PHP 后台系统设置中将 FairFace 服务地址改为远程 IP
```
