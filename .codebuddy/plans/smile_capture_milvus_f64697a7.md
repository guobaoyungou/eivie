---
name: smile_capture_milvus
overview: 在旅拍系统的人像管理页面增加"笑脸抓拍"功能，实现Web端连续笑脸识别+抓拍+上传，并将人脸特征存入Milvus向量数据库
todos:
  - id: deploy-milvus
    content: 使用Docker部署Milvus向量数据库（4核8G配置）
    status: completed
  - id: create-migration
    content: 创建数据库迁移脚本，添加人像特征向量字段
    status: completed
    dependencies:
      - deploy-milvus
  - id: install-milvus-php
    content: 安装PHP Milvus SDK客户端
    status: completed
    dependencies:
      - deploy-milvus
  - id: create-milvus-service
    content: 创建MilvusService提供人脸向量存储和检索功能
    status: completed
    dependencies:
      - install-milvus-php
  - id: create-milvus-config
    content: 创建Milvus配置文件
    status: completed
    dependencies:
      - deploy-milvus
  - id: add-smile-capture-frontend
    content: 在前端页面添加笑脸抓拍按钮和弹窗
    status: completed
  - id: implement-smile-detection
    content: 实现face-api.js笑脸检测逻辑
    status: completed
    dependencies:
      - add-smile-capture-frontend
  - id: add-backend-api
    content: 后端添加笑脸抓拍上传API接口
    status: completed
    dependencies:
      - create-milvus-service
      - create-migration
  - id: integrate-upload
    content: 集成图片上传和人像记录创建逻辑
    status: completed
    dependencies:
      - add-backend-api
  - id: test-smile-capture
    content: 测试笑脸抓拍功能（识别准确性、自动抓拍、存储）
    status: completed
    dependencies:
      - integrate-upload
---

## 用户需求

在旅拍-AI旅拍-人像管理页面中，"批量上传人像"按钮旁边增加"笑脸抓拍"功能，实现以下核心需求：

1. Web端连续笑脸识别（使用face-api.js开源库）
2. 笑脸持续0.5秒后自动抓拍
3. 抓拍的图片自动上传到人像库
4. 人脸特征向量数据存入Milvus向量数据库（需部署Milvus）

## 产品概述

- 功能位置：旅拍 → AI旅拍 → 人像管理页面
- 新增"笑脸抓拍"按钮，位于现有"批量上传人像"按钮旁边
- 用户点击后打开摄像头预览窗口，系统实时检测笑脸
- 检测到笑脸持续0.5秒后自动抓拍并上传到人像库

## 核心功能

- 摄像头实时预览
- 实时笑脸检测与可视化提示
- 笑脸持续0.5秒自动触发抓拍
- 自动提取人脸128维特征向量
- 图片和特征向量一并上传存储
- 特征向量存入Milvus用于后续人脸比对

## 技术选型

- **前端笑脸识别**：face-api.js（开源，MIT协议）
- **向量数据库**：Milvus（Docker部署，4核8G）
- **后端框架**：ThinkPHP（现有项目）
- **图片存储**：阿里云OSS（现有）

## 技术架构

### 系统架构

```
┌─────────────────────────────────────────────────────────┐
│  前端 (portrait_list.html)                              │
│  ├── face-api.js 笑脸检测                               │
│  ├── 摄像头预览 (getUserMedia)                          │
│  ├── 笑脸持续检测 (0.5秒阈值)                          │
│  └── 特征向量提取 + 图片上传                            │
└─────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────┐
│  后端 API (AiTravelPhotoController)                     │
│  ├── 笑脸抓拍上传接口                                   │
│  ├── 人像数据存储 (AiTravelPhotoPortraitService)        │
│  └── Milvus客户端 (人脸特征向量存储)                    │
└─────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────┐
│  存储层                                                  │
│  ├── MySQL (人像表 + face_embedding字段)               │
│  ├── OSS (原始图片/缩略图)                              │
│  └── Milvus (人脸特征向量集合)                          │
└─────────────────────────────────────────────────────────┘
```

### 目录结构

```
project-root/
├── static/
│   └── index3/
│       └── js/
│           └── smile-capture.js    # [NEW] 笑脸抓拍前端逻辑
├── app/
│   ├── controller/
│   │   └── AiTravelPhoto.php       # [MODIFY] 添加笑脸抓拍API
│   ├── service/
│   │   ├── AiTravelPhotoPortraitService.php  # [MODIFY] 添加人像上传逻辑
│   │   └── MilvusService.php        # [NEW] Milvus向量服务
│   └── view/
│       └── ai_travel_photo/
│           └── portrait_list.html   # [MODIFY] 添加笑脸抓拍按钮和弹窗
├── config/
│   └── milvus.php                   # [NEW] Milvus配置文件
├── database/
│   └── migrations/
│       └── add_face_embedding.sql    # [NEW] 添加特征向量字段
├── docker/
│   └── milvus/
│       └── docker-compose.yml        # [NEW] Milvus Docker配置
└── vendor/ (通过Composer安装)
    └── milvus-sdk-php/               # [NEW] Milvus PHP客户端
```

## 实现细节

### 1. Milvus部署

- 使用Docker Compose方式部署Milvus单机版
- 资源配置：4核8G
- 端口：19530 (Milvus), 9091 (MinIO)
- 创建Collection：face_features
- 向量维度：128维（face-api.js FaceNet产生）

### 2. 数据库扩展

- 人像表添加字段：face_embedding (TEXT类型，存储JSON序列化的128维向量)
- 迁移脚本：add_face_embedding.sql

### 3. 前端实现

- 引入face-api.js（从CDN或本地）
- 加载TinyFaceDetector模型（轻量级，适合Web）
- 实现：摄像头授权 → 视频流 → 人脸检测 → 笑脸检测 → 计时触发 → 抓拍 → 上传
- 弹窗设计：摄像头预览区域 + 状态提示 + 关闭按钮

### 4. 后端实现

- API接口：POST /ai_travel_photo/smile_capture_upload
- 参数：image(base64), mdid(门店ID), device_id(设备ID)
- 流程：接收图片 → 存储到OSS → 创建人像记录 → 返回结果

### 5. 性能考虑

- 前端使用TinyFaceDetector（轻量模型，检测速度快）
- 笑脸检测阈值设置合理值（避免误触发）
- 抓拍图片直接base64上传，减少前端处理
- 特征向量存储在Milvus，不存入MySQL（性能考虑）