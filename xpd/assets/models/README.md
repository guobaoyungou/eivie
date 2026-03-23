# face-api.js 模型文件说明

本目录用于存放 face-api.js 的预训练模型文件。

## 使用 CDN 方式加载（当前方案）

当前前端模板使用 jsdelivr CDN 直接加载模型文件，无需本地存放。

CDN 基础路径：`https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1/model/`

## 所需模型

| 模型文件 | 大小 | 用途 |
|---------|------|------|
| tiny_face_detector_model-weights_manifest.json | ~1KB | TinyFaceDetector 模型清单 |
| tiny_face_detector_model-shard1 | ~190KB | TinyFaceDetector 模型权重 |
| face_landmark_68_model-weights_manifest.json | ~1KB | 68 点人脸关键点模型清单 |
| face_landmark_68_model-shard1 | ~350KB | 68 点人脸关键点模型权重 |
| face_recognition_model-weights_manifest.json | ~1KB | 人脸识别模型清单 |
| face_recognition_model-shard1 | ~6.2MB | 人脸识别模型权重（128维特征向量）|
| face_recognition_model-shard2 | ~200KB | 人脸识别模型权重续 |

## 本地部署（可选）

如需本地部署模型文件（离线环境），执行以下命令：

```bash
cd /home/www/ai.eivie.cn/xpd/assets/models
# 下载模型文件
wget https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1/model/tiny_face_detector_model-weights_manifest.json
wget https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1/model/tiny_face_detector_model-shard1
wget https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1/model/face_landmark_68_model-weights_manifest.json
wget https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1/model/face_landmark_68_model-shard1
wget https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1/model/face_recognition_model-weights_manifest.json
wget https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1/model/face_recognition_model-shard1
wget https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1/model/face_recognition_model-shard2
```

然后修改前端模板中的模型加载路径为本地路径。
