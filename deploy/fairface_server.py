#!/usr/bin/env python3
"""
InsightFace + FairFace 一体化人物属性识别 API 服务
==================================================

将 InsightFace（人脸检测 + 对齐）与 FairFace（属性分类）整合为
单一 REST API 服务，供场景模板自动标签系统调用。

功能流水线：
    1. InsightFace SCRFD/RetinaFace → 人脸检测 + 5点对齐
    2. FairFace ResNet-34 → 性别、年龄段、人种分类
    3. （可选）全身区域比例 → 体型粗估

部署步骤:
    1. 安装依赖:
       pip install insightface onnxruntime-gpu fastapi uvicorn python-multipart \
                   Pillow numpy requests torch torchvision
       # CPU 环境可用 onnxruntime 替代 onnxruntime-gpu
    2. 下载 FairFace 预训练权重:
       将 res34_fair_align_multi_7_20190809.pt 放到 --fairface-weights 指定路径
       下载地址: https://drive.google.com/file/d/1i1L3Yqwaio7YSOCj7ftgk8ZZchPG7dmH
    3. 启动服务:
       python fairface_server.py --host 0.0.0.0 --port 8867
    4. 指定权重路径:
       python fairface_server.py --fairface-weights ./models/fairface_model.pt

API 端点:
    GET  /api/health    — 健康检查
    GET  /api/info      — 模型信息与能力
    POST /api/analyze   — 人物图片属性识别
"""

import os
import io
import sys
import base64
import logging
import argparse
import time
from typing import Optional, List, Dict, Any

import numpy as np
from PIL import Image
import requests
import uvicorn
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field

# --------------- 日志配置 ---------------
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[logging.StreamHandler(sys.stdout)],
)
logger = logging.getLogger("fairface_server")

# --------------- 全局变量 ---------------
insight_app = None          # InsightFace FaceAnalysis 实例
fairface_model = None       # FairFace PyTorch 模型
fairface_device = None      # torch device
fairface_transform = None   # torchvision 预处理 transform

model_info = {
    "name": "InsightFace + FairFace",
    "version": "1.0.0",
    "detection": "InsightFace (SCRFD/RetinaFace)",
    "classification": "FairFace ResNet-34",
    "capabilities": [
        "face_detection",
        "face_alignment",
        "gender_classification",
        "age_classification",
        "race_classification",
        "body_type_estimation",
    ],
}

# FairFace 标签定义
FAIRFACE_RACE_LABELS = [
    "White", "Black", "Latino_Hispanic",
    "East Asian", "Southeast Asian", "Indian", "Middle Eastern"
]
FAIRFACE_GENDER_LABELS = ["Male", "Female"]
FAIRFACE_AGE_LABELS = [
    "0-2", "3-9", "10-19", "20-29",
    "30-39", "40-49", "50-59", "60-69", "70+"
]


# --------------- 请求/响应模型 ---------------
class AnalyzeRequest(BaseModel):
    """人物属性识别请求"""
    image_url: Optional[str] = Field(None, description="图片远程URL（与 image_base64 二选一）")
    image_base64: Optional[str] = Field(None, description="图片Base64编码（与 image_url 二选一）")
    detect_body_type: Optional[bool] = Field(True, description="是否启用体型检测，默认 true")


class FaceResult(BaseModel):
    """单张人脸识别结果"""
    bbox: List[float] = Field(description="人脸边界框 [x1, y1, x2, y2]")
    bbox_area: float = Field(description="人脸框面积，用于判断主体人物")
    gender: str = Field(description="性别: Male / Female")
    gender_confidence: float = Field(description="性别置信度 0~1")
    age_group: str = Field(description="年龄分段")
    age_confidence: float = Field(description="年龄置信度 0~1")
    race: str = Field(description="人种分类")
    race_confidence: float = Field(description="人种置信度 0~1")
    body_type: Optional[str] = Field(None, description="体型分类（需全身图）")
    body_type_confidence: Optional[float] = Field(None, description="体型置信度")


class AnalyzeResponse(BaseModel):
    """识别响应"""
    status: str = "success"
    faces: List[FaceResult] = []
    face_count: int = 0
    analysis_time: float = 0.0


# --------------- FastAPI 应用 ---------------
app = FastAPI(
    title="InsightFace + FairFace 人物属性识别 API",
    description="场景模板自动标签识别服务 — InsightFace 检测 + FairFace 属性分类",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)


# --------------- 工具函数 ---------------
def load_image_from_url(url: str, timeout: int = 15) -> Image.Image:
    """从 URL 下载图片并返回 PIL Image"""
    headers = {
        "User-Agent": "Mozilla/5.0 (FairFace-Server/1.0)",
    }
    resp = requests.get(url, headers=headers, timeout=timeout, stream=True)
    resp.raise_for_status()
    return Image.open(io.BytesIO(resp.content)).convert("RGB")


def load_image_from_base64(b64_data: str) -> Image.Image:
    """从 Base64 解码图片"""
    # 兼容 data:image/xxx;base64, 前缀
    if "," in b64_data:
        b64_data = b64_data.split(",", 1)[1]
    img_bytes = base64.b64decode(b64_data)
    return Image.open(io.BytesIO(img_bytes)).convert("RGB")


def align_face_for_fairface(img_np: np.ndarray, kps: np.ndarray) -> Image.Image:
    """
    使用 InsightFace 检测到的 5 点关键点进行仿射对齐，
    裁剪出 224×224 的对齐人脸区域供 FairFace 推理。
    基于 InsightFace 的 norm_crop 方法。
    """
    from insightface.utils.face_align import norm_crop
    aligned = norm_crop(img_np, kps, image_size=224)
    return Image.fromarray(aligned[:, :, ::-1])  # BGR → RGB


def crop_face_by_bbox(pil_img: Image.Image, bbox: List[float], padding: float = 0.3) -> Image.Image:
    """
    当没有关键点时的降级策略：直接按 bbox 裁剪 + padding。
    """
    w, h = pil_img.size
    x1, y1, x2, y2 = bbox
    fw, fh = x2 - x1, y2 - y1
    cx, cy = (x1 + x2) / 2, (y1 + y2) / 2
    side = max(fw, fh) * (1 + padding)
    nx1 = max(0, int(cx - side / 2))
    ny1 = max(0, int(cy - side / 2))
    nx2 = min(w, int(cx + side / 2))
    ny2 = min(h, int(cy + side / 2))
    cropped = pil_img.crop((nx1, ny1, nx2, ny2))
    return cropped.resize((224, 224), Image.BILINEAR)


def estimate_body_type(pil_img: Image.Image, bbox: List[float]) -> Dict[str, Any]:
    """
    基于人脸位置在全图中的比例粗估体型。
    - 若人脸占全图比例很大（>30%），说明是大头照/半身照，无法判断体型
    - 若人脸占比小（<10%），且在上部1/4区域，说明全身照可进一步分析
    这里做简单的启发式判断；精准体型需额外模型。
    """
    img_w, img_h = pil_img.size
    x1, y1, x2, y2 = bbox
    face_area = (x2 - x1) * (y2 - y1)
    img_area = img_w * img_h
    face_ratio = face_area / img_area if img_area > 0 else 1.0

    # 人脸占比超过 15% → 非全身照，无法估计体型
    if face_ratio > 0.15:
        return {"body_type": None, "body_type_confidence": None}

    # 人脸中心在图片上部 1/3 → 可能全身照
    face_cy = (y1 + y2) / 2
    if face_cy / img_h > 0.35:
        return {"body_type": None, "body_type_confidence": None}

    # 简单启发：人脸宽度与图片宽度之比判断体型
    face_w = x2 - x1
    body_width_ratio = face_w / img_w if img_w > 0 else 0

    if body_width_ratio < 0.06:
        return {"body_type": "slim", "body_type_confidence": 0.5}
    elif body_width_ratio < 0.10:
        return {"body_type": "average", "body_type_confidence": 0.5}
    elif body_width_ratio < 0.14:
        return {"body_type": "muscular", "body_type_confidence": 0.4}
    else:
        return {"body_type": "heavy", "body_type_confidence": 0.4}


def age_to_group(age: int) -> str:
    """将整数年龄转换为 FairFace 风格的年龄分组"""
    if age <= 2:
        return "0-2"
    elif age <= 9:
        return "3-9"
    elif age <= 19:
        return "10-19"
    elif age <= 29:
        return "20-29"
    elif age <= 39:
        return "30-39"
    elif age <= 49:
        return "40-49"
    elif age <= 59:
        return "50-59"
    elif age <= 69:
        return "60-69"
    else:
        return "70+"


def extract_insightface_attrs(face) -> Dict[str, Any]:
    """
    从 InsightFace 人脸对象中提取性别和年龄属性（InsightFace-Only 模式）。
    InsightFace 的 buffalo_l 包含 genderage.onnx 模型，可直接提供性别和年龄。
    人种分类在此模式下返回 Unknown，待 FairFace 模型可用时再启用。
    """
    # InsightFace face.gender: 0=Female, 1=Male
    # InsightFace face.age: integer age
    gender_val = getattr(face, "gender", None)
    age_val = getattr(face, "age", None)

    if gender_val is not None:
        gender = "Male" if int(gender_val) == 1 else "Female"
        gender_conf = 0.85  # InsightFace 不提供分类置信度，使用默认值
    else:
        gender = "Unknown"
        gender_conf = 0.0

    if age_val is not None:
        age_group = age_to_group(int(age_val))
        age_conf = 0.75  # 默认置信度
    else:
        age_group = "Unknown"
        age_conf = 0.0

    return {
        "gender": gender,
        "gender_confidence": gender_conf,
        "age_group": age_group,
        "age_confidence": age_conf,
        "race": "Unknown",
        "race_confidence": 0.0,
    }


def run_fairface_inference(face_img: Image.Image) -> Dict[str, Any]:
    """
    对对齐后的 224×224 人脸图片运行 FairFace 模型推理，
    返回 gender / age_group / race 及其置信度。
    """
    import torch

    global fairface_model, fairface_device, fairface_transform

    if fairface_model is None:
        raise RuntimeError("FairFace 模型未加载")

    # 预处理
    input_tensor = fairface_transform(face_img).unsqueeze(0).to(fairface_device)

    with torch.no_grad():
        outputs = fairface_model(input_tensor)

    # FairFace 模型输出: outputs 是一个包含多个输出的元组/列表
    # 标准 FairFace: race_outputs(7), gender_outputs(2), age_outputs(9)
    if isinstance(outputs, (tuple, list)):
        race_out, gender_out, age_out = outputs[0], outputs[1], outputs[2]
    else:
        # 如果是单输出模型，尝试切分
        race_out = outputs[:, :7]
        gender_out = outputs[:, 7:9]
        age_out = outputs[:, 9:18]

    # Softmax 得到概率
    race_probs = torch.nn.functional.softmax(race_out, dim=1).cpu().numpy()[0]
    gender_probs = torch.nn.functional.softmax(gender_out, dim=1).cpu().numpy()[0]
    age_probs = torch.nn.functional.softmax(age_out, dim=1).cpu().numpy()[0]

    race_idx = int(np.argmax(race_probs))
    gender_idx = int(np.argmax(gender_probs))
    age_idx = int(np.argmax(age_probs))

    return {
        "gender": FAIRFACE_GENDER_LABELS[gender_idx],
        "gender_confidence": round(float(gender_probs[gender_idx]), 4),
        "age_group": FAIRFACE_AGE_LABELS[age_idx],
        "age_confidence": round(float(age_probs[age_idx]), 4),
        "race": FAIRFACE_RACE_LABELS[race_idx],
        "race_confidence": round(float(race_probs[race_idx]), 4),
    }


# --------------- API 端点 ---------------
@app.get("/api/health")
async def health_check():
    """健康检查"""
    return {
        "status": "ok",
        "insightface_loaded": insight_app is not None,
        "fairface_loaded": fairface_model is not None,
        "mode": "full" if fairface_model is not None else "insightface_only",
        "model": model_info["name"],
    }


@app.get("/api/info")
async def get_model_info():
    """模型信息与能力"""
    return {
        "status": "ok",
        "model": model_info,
        "labels": {
            "gender": FAIRFACE_GENDER_LABELS,
            "age_group": FAIRFACE_AGE_LABELS,
            "race": FAIRFACE_RACE_LABELS,
            "body_type": ["slim", "average", "muscular", "heavy"],
        },
    }


@app.post("/api/analyze")
async def analyze_image(req: AnalyzeRequest):
    """
    人物图片属性识别（一体化流程）

    1. InsightFace 检测人脸 → 定位 + 5点关键点
    2. 对齐裁剪 224×224
    3. FairFace 推理 → 性别 / 年龄段 / 人种
    4. （可选）启发式体型估计
    """
    if insight_app is None:
        raise HTTPException(status_code=503, detail="InsightFace 模型未完成加载")

    if not req.image_url and not req.image_base64:
        raise HTTPException(status_code=400, detail="image_url 和 image_base64 至少提供一个")

    start_time = time.time()

    try:
        # 1. 加载图片
        if req.image_url:
            pil_img = load_image_from_url(req.image_url)
        else:
            pil_img = load_image_from_base64(req.image_base64)

        img_np = np.array(pil_img)[:, :, ::-1]  # RGB → BGR (InsightFace 期望 BGR)

        # 2. InsightFace 人脸检测
        faces_detected = insight_app.get(img_np)

        if not faces_detected:
            return AnalyzeResponse(
                status="success",
                faces=[],
                face_count=0,
                analysis_time=round(time.time() - start_time, 3),
            )

        # 3. 逐脸处理
        results: List[FaceResult] = []
        for face in faces_detected:
            bbox = face.bbox.tolist()  # [x1, y1, x2, y2]
            bbox_area = (bbox[2] - bbox[0]) * (bbox[3] - bbox[1])

            if fairface_model is not None:
                # FairFace 完整模式: 对齐裁剪 + FairFace 推理
                kps = getattr(face, "kps", None)
                if kps is not None and len(kps) >= 5:
                    face_img = align_face_for_fairface(img_np, kps)
                else:
                    face_img = crop_face_by_bbox(pil_img, bbox)
                attrs = run_fairface_inference(face_img)
            else:
                # InsightFace-Only 模式: 使用 InsightFace 内置的 genderage 模型
                attrs = extract_insightface_attrs(face)

            # 体型估计（可选）
            body_info = {"body_type": None, "body_type_confidence": None}
            if req.detect_body_type:
                body_info = estimate_body_type(pil_img, bbox)

            results.append(FaceResult(
                bbox=[round(v, 1) for v in bbox],
                bbox_area=round(bbox_area, 1),
                gender=attrs["gender"],
                gender_confidence=attrs["gender_confidence"],
                age_group=attrs["age_group"],
                age_confidence=attrs["age_confidence"],
                race=attrs["race"],
                race_confidence=attrs["race_confidence"],
                body_type=body_info["body_type"],
                body_type_confidence=body_info["body_type_confidence"],
            ))

        # 按面积降序排序（最大面积 = 主体人物排第一）
        results.sort(key=lambda r: r.bbox_area, reverse=True)

        analysis_time = round(time.time() - start_time, 3)
        logger.info(f"分析完成: 检测到 {len(results)} 张人脸, 耗时 {analysis_time}s")

        return AnalyzeResponse(
            status="success",
            faces=results,
            face_count=len(results),
            analysis_time=analysis_time,
        )

    except requests.exceptions.RequestException as e:
        logger.error(f"图片下载失败: {e}")
        raise HTTPException(status_code=400, detail=f"图片下载失败: {str(e)}")
    except Exception as e:
        logger.error(f"分析失败: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"分析失败: {str(e)}")


# --------------- 模型加载 ---------------
def load_insightface(det_model: str = "buffalo_l", ctx_id: int = 0, model_root: str = None):
    """加载 InsightFace 人脸检测模型"""
    global insight_app
    import insightface
    from insightface.app import FaceAnalysis

    # 默认模型根目录为项目 deploy/models/insightface
    if model_root is None:
        model_root = os.path.join(os.path.dirname(os.path.abspath(__file__)), "models", "insightface")
    os.makedirs(model_root, exist_ok=True)

    logger.info(f"正在加载 InsightFace 模型: {det_model}, root={model_root}")
    insight_app = FaceAnalysis(
        name=det_model,
        root=model_root,
        providers=["CPUExecutionProvider"],
    )
    # det_size 设置检测分辨率，(640,640) 是默认值，越大越慢但检出率更高
    insight_app.prepare(ctx_id=ctx_id, det_size=(640, 640))
    logger.info("InsightFace 模型加载完成!")


def load_fairface(weights_path: str, device_str: str = "auto"):
    """
    加载 FairFace ResNet-34 分类模型

    FairFace 模型结构：
    - 基于 ResNet-34，最后替换为 3 个分类头:
      - race (7 类), gender (2 类), age (9 类)
    """
    import torch
    import torchvision
    from torchvision import transforms

    global fairface_model, fairface_device, fairface_transform

    # 确定设备
    if device_str == "auto":
        fairface_device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    else:
        fairface_device = torch.device(device_str)

    logger.info(f"正在加载 FairFace 模型: {weights_path} (device={fairface_device})")

    # 构建 FairFace 模型结构（ResNet-34 + 3 分类头）
    model = torchvision.models.resnet34(pretrained=False)
    # 替换最后的全连接层为 3 个分类头
    model.fc = torch.nn.Linear(model.fc.in_features, 18)  # 7+2+9=18

    # 尝试加载权重
    state_dict = torch.load(weights_path, map_location=fairface_device)

    # 兼容两种权重格式
    if "fc.weight" in state_dict and state_dict["fc.weight"].shape[0] == 18:
        # 单 fc 输出 18 维（合并模式）
        model.load_state_dict(state_dict, strict=False)
        # 包装成多头输出
        class FairFaceWrapper(torch.nn.Module):
            def __init__(self, base):
                super().__init__()
                self.base = base
            def forward(self, x):
                out = self.base(x)  # (B, 18)
                return out[:, :7], out[:, 7:9], out[:, 9:18]
        fairface_model = FairFaceWrapper(model).to(fairface_device).eval()
    else:
        # 尝试多头模式（race_fc / gender_fc / age_fc）
        class FairFaceMultiHead(torch.nn.Module):
            def __init__(self):
                super().__init__()
                base = torchvision.models.resnet34(pretrained=False)
                # 移除原始 fc
                self.features = torch.nn.Sequential(*list(base.children())[:-1])
                in_features = 512  # ResNet-34 最后一层特征维度
                self.race_fc = torch.nn.Linear(in_features, 7)
                self.gender_fc = torch.nn.Linear(in_features, 2)
                self.age_fc = torch.nn.Linear(in_features, 9)

            def forward(self, x):
                feat = self.features(x).flatten(1)
                return self.race_fc(feat), self.gender_fc(feat), self.age_fc(feat)

        multi_model = FairFaceMultiHead()
        multi_model.load_state_dict(state_dict, strict=False)
        fairface_model = multi_model.to(fairface_device).eval()

    # 图像预处理 transform（FairFace 标准预处理）
    fairface_transform = transforms.Compose([
        transforms.Resize((224, 224)),
        transforms.ToTensor(),
        transforms.Normalize(
            mean=[0.485, 0.456, 0.406],
            std=[0.229, 0.224, 0.225],
        ),
    ])

    logger.info("FairFace 模型加载完成!")


# --------------- 入口 ---------------
if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="InsightFace + FairFace 一体化人物属性识别 API")
    parser.add_argument("--host", default="0.0.0.0", help="监听地址 (默认: 0.0.0.0)")
    parser.add_argument("--port", type=int, default=8867, help="监听端口 (默认: 8867)")
    parser.add_argument("--det-model", default="buffalo_l",
                        help="InsightFace 检测模型名称 (默认: buffalo_l)")
    parser.add_argument("--fairface-weights",
                        default=os.path.join(os.path.dirname(os.path.abspath(__file__)), "models", "res34_fair_align_multi_7_20190809.pt"),
                        help="FairFace 模型权重文件路径")
    parser.add_argument("--insightface-root",
                        default=os.path.join(os.path.dirname(os.path.abspath(__file__)), "models", "insightface"),
                        help="InsightFace 模型根目录 (默认: deploy/models/insightface)")
    parser.add_argument("--device", default="auto",
                        help="PyTorch 设备: auto / cpu / cuda / cuda:0 等")
    parser.add_argument("--gpu-id", type=int, default=0,
                        help="InsightFace GPU ID (默认: 0, -1=CPU)")
    parser.add_argument("--workers", type=int, default=1,
                        help="Worker 数量 (GPU模型建议为1)")

    args = parser.parse_args()

    # 加载模型
    load_insightface(det_model=args.det_model, ctx_id=args.gpu_id, model_root=args.insightface_root)

    # FairFace 模型可选加载：如果权重文件存在则加载，否则使用 InsightFace-Only 模式
    if os.path.isfile(args.fairface_weights):
        load_fairface(weights_path=args.fairface_weights, device_str=args.device)
    else:
        logger.warning(f"FairFace 权重文件不存在: {args.fairface_weights}")
        logger.warning("服务将以 InsightFace-Only 模式运行（性别+年龄可用，人种分类不可用）")
        logger.warning("如需启用完整功能，请下载 FairFace 权重并通过 --fairface-weights 指定路径")

    # 启动服务
    logger.info(f"InsightFace + FairFace API Server 启动于 http://{args.host}:{args.port}")
    logger.info(f"API 文档: http://{args.host}:{args.port}/docs")
    uvicorn.run(app, host=args.host, port=args.port, workers=args.workers)
