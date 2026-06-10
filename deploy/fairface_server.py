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
    GET  /api/health             — 健康检查
    GET  /api/info               — 模型信息与能力
    POST /api/analyze            — 人物图片属性识别
    POST /api/extract_embedding  — 人脸特征向量提取（512维 L2 归一化）
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
import cv2
import onnxruntime as ort
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
genderage_direct = None     # GenderAgeDirect 实例 — 绕过 InsightFace SDK 直接推理

model_info = {
    "name": "InsightFace buffalo_l (Primary) + FairFace (Race Only) + EmotiEffLib (Emotion)",
    "version": "2.1.0",
    "detection": "InsightFace (SCRFD/RetinaFace)",
    "classification": {
        "gender_age": "InsightFace buffalo_l genderage.onnx (direct ONNX Runtime — float age + softmax confidence)",
        "race": "FairFace ResNet-34",
        "emotion": "EmotiEffLib enet_b2_8 ONNX (8-class facial expression)",
    },
    "capabilities": [
        "face_detection",
        "face_alignment",
        "face_embedding_extraction",
        "gender_classification",
        "age_classification",
        "precise_float_age",
        "age_confidence_interval",
        "race_classification",
        "body_type_estimation",
        "emotion_recognition",
        "landmark_heuristic_analysis",
    ],
}

# EmotiEffLib 表情标签映射：8类原始 → 6类中文
EMOTIEFFLIB_EMOTION_MAP = {
    "Neutral":    "平静",
    "Happiness":  "微笑",
    "Sadness":    "伤心",
    "Surprise":   "惊讶",
    "Anger":      "生气",
    "Fear":       "恐惧",
    "Contempt":   "平静",   # 蔑视 → 归入平静
    "Disgust":    "生气",   # 厌恶 → 归入生气
}

# EmotiEffLib 全局实例（首次使用时延迟加载）
emotion_recognizer = None

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


class ExtractEmbeddingRequest(BaseModel):
    """人脸特征提取请求"""
    image_url: Optional[str] = Field(None, description="图片远程URL（与 image_base64 二选一）")
    image_base64: Optional[str] = Field(None, description="图片Base64编码（与 image_url 二选一）")
    max_faces: Optional[int] = Field(5, description="最多返回的人脸数量，默认5")


class FaceResult(BaseModel):
    """单张人脸识别结果"""
    bbox: List[float] = Field(description="人脸边界框 [x1, y1, x2, y2]")
    bbox_area: float = Field(description="人脸框面积，用于判断主体人物")
    gender: str = Field(description="性别: Male / Female")
    gender_confidence: float = Field(description="性别置信度 0~1 (softmax from raw logits)")
    age_group: str = Field(description="年龄分段（向后兼容）")
    age_confidence: float = Field(description="年龄置信度 0~1 (基于检测质量动态计算)")
    age: Optional[float] = Field(None, description="精确浮点年龄，如 28.67")
    age_lower: Optional[float] = Field(None, description="年龄置信区间下界")
    age_upper: Optional[float] = Field(None, description="年龄置信区间上界")
    race: str = Field(description="人种分类")
    race_confidence: float = Field(description="人种置信度 0~1")
    body_type: Optional[str] = Field(None, description="体型分类（需全身图）")
    body_type_confidence: Optional[float] = Field(None, description="体型置信度")
    gender_model: Optional[str] = Field(None, description="性别/年龄识别模型: insightface_buffalo_l / fairface")


class AnalyzeResponse(BaseModel):
    """识别响应"""
    status: str = "success"
    faces: List[FaceResult] = []
    face_count: int = 0
    analysis_time: float = 0.0


class EmbeddingFace(BaseModel):
    """单张人脸特征提取结果"""
    bbox: List[float] = Field(description="人脸边界框 [x1, y1, x2, y2]")
    bbox_area: float = Field(description="人脸框面积")
    embedding: List[float] = Field(description="512维L2归一化人脸特征向量")
    embedding_dim: int = Field(description="向量维度")
    det_score: float = Field(description="人脸检测置信度 0~1")


class ExtractEmbeddingResponse(BaseModel):
    """特征提取响应"""
    status: str = "success"
    faces: List[EmbeddingFace] = []
    face_count: int = 0
    extraction_time: float = 0.0


# --------------- 扩展分析模型 ---------------
class ExtendedAnalyzeRequest(BaseModel):
    """扩展人物属性识别请求（表情 + 关键点推断）"""
    image_url: Optional[str] = Field(None, description="图片远程URL")
    image_base64: Optional[str] = Field(None, description="图片Base64编码")


class EmotionScores(BaseModel):
    """6类表情概率分值"""
    calm: float = Field(0.0, description="平静")
    happy: float = Field(0.0, description="微笑/开心")
    sad: float = Field(0.0, description="伤心")
    surprised: float = Field(0.0, description="惊讶")
    angry: float = Field(0.0, description="生气")
    fear: float = Field(0.0, description="恐惧")


class ExtendedFaceResult(FaceResult):
    """扩展人脸分析结果（继承基础属性 + 表情 + Phase2预留）"""
    emotion: EmotionScores = Field(description="6类表情概率分值")
    emotion_primary: str = Field("", description="主表情标签（最高分对应中文）")
    # Phase 2 预留字段
    glasses_type: str = Field("", description="眼镜类型: none/sunglasses/eyeglasses")
    eyelid_type: str = Field("", description="眼皮类型")
    skin_tone: str = Field("", description="肤色")
    hair_length: str = Field("", description="发长")


class ExtendedAnalyzeResponse(BaseModel):
    """扩展识别响应"""
    status: str = "success"
    faces: List[ExtendedFaceResult] = []
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


# --------------- 表情识别（EmotiEffLib） ---------------
def load_emotion_recognizer():
    """延迟加载 EmotiEffLib 表情识别模型"""
    global emotion_recognizer
    if emotion_recognizer is not None:
        return emotion_recognizer
    try:
        from emotiefflib.facial_analysis import EmotiEffLibRecognizerOnnx
        emotion_recognizer = EmotiEffLibRecognizerOnnx(model_name='enet_b2_8')
        logger.info(f"EmotiEffLib 表情识别模型加载完成 (labels={list(emotion_recognizer.idx_to_emotion_class.values())})")
    except Exception as e:
        logger.error(f"EmotiEffLib 加载失败: {e}", exc_info=True)
        emotion_recognizer = None
    return emotion_recognizer


def recognize_emotion(face_img_np: np.ndarray) -> Dict[str, float]:
    """
    对单张对齐人脸图片进行表情识别

    Args:
        face_img_np: BGR 人脸图像 (numpy array, 任意尺寸)

    Returns:
        dict: 6类中文表情概率分值 {'平静': 0.x, '微笑': 0.x, ...}
    """
    recognizer = load_emotion_recognizer()
    if recognizer is None:
        return {"平静": 0.0, "微笑": 0.0, "伤心": 0.0, "惊讶": 0.0, "生气": 0.0, "恐惧": 0.0}

    try:
        features = recognizer.extract_features(face_img_np)
        preds, scores = recognizer.classify_emotions(features, logits=False)
        raw_probs = scores[0]  # (8,) class probabilities

        # 映射到 6 类中文
        result = {"平静": 0.0, "微笑": 0.0, "伤心": 0.0, "惊讶": 0.0, "生气": 0.0, "恐惧": 0.0}
        for idx, label_en in recognizer.idx_to_emotion_class.items():
            cn_label = EMOTIEFFLIB_EMOTION_MAP.get(label_en, "平静")
            result[cn_label] += float(raw_probs[idx])

        # 归一化（因 Contempt/Disgust 归并可能导致总和 > 1.0）
        total = sum(result.values())
        if total > 0:
            result = {k: round(v / total, 4) for k, v in result.items()}

        return result
    except Exception as e:
        logger.error(f"表情识别失败: {e}", exc_info=True)
        return {"平静": 0.0, "微笑": 0.0, "伤心": 0.0, "惊讶": 0.0, "生气": 0.0, "恐惧": 0.0}


# --------------- 106关键点启发式推断 ---------------
# 2d106det 关键点索引定义（WFLW 标准）
# 0-32: 人脸轮廓 | 33-42: 左眉 | 43-51: 右眉 | 52-59: 鼻梁
# 60-67: 鼻尖 | 68-75: 左眼 | 76-82: 右眼 | 83-96: 嘴外轮廓 | 97-105: 嘴内轮廓
LEFT_EYE_PTS = list(range(68, 76))   # 左眼 8个点
RIGHT_EYE_PTS = list(range(76, 83))  # 右眼 7个点
MOUTH_OUTER_PTS = list(range(83, 97))  # 嘴外轮廓 14个点

def eye_aspect_ratio(eye_pts: np.ndarray) -> float:
    """计算眼部纵横比 (EAR)，值越低表示眼睛越闭合"""
    # 垂直距离：上眼睑中间点到下眼睑中间点
    v1 = np.linalg.norm(eye_pts[2] - eye_pts[6])  # 中间垂直
    v2 = np.linalg.norm(eye_pts[3] - eye_pts[5])  # 偏内侧垂直
    # 水平距离：眼角到眼角
    h = np.linalg.norm(eye_pts[0] - eye_pts[4])
    if h < 1e-6:
        return 1.0
    return (v1 + v2) / (2.0 * h)


def mouth_aspect_ratio(mouth_pts: np.ndarray) -> float:
    """计算嘴部纵横比 (MAR)，值越高表示嘴张得越大"""
    # 垂直距离：上唇中间点到下唇中间点
    v = np.linalg.norm(mouth_pts[3] - mouth_pts[9])  # 上下唇中心
    # 水平距离：嘴角到嘴角
    h = np.linalg.norm(mouth_pts[0] - mouth_pts[6])
    if h < 1e-6:
        return 0.0
    return v / h


def lip_corner_delta(mouth_pts: np.ndarray) -> float:
    """判断嘴角上扬/下垂，正值 = 微笑"""
    left_corner = mouth_pts[0]   # 左嘴角
    right_corner = mouth_pts[6]  # 右嘴角
    mid_top = mouth_pts[3]       # 上唇中点
    # 嘴角连线的中点 vs 上唇中点的垂直偏移
    corner_mid_y = (left_corner[1] + right_corner[1]) / 2.0
    face_h = 100.0  # 归一化参考尺度
    return (corner_mid_y - mid_top[1]) / face_h


def extract_landmarks(face) -> Optional[np.ndarray]:
    """从 InsightFace face 对象提取 106 点关键点数组"""
    try:
        lmk = getattr(face, "landmark_2d_106", None)
        if lmk is None:
            lmk = getattr(face, "landmark", None)
        if lmk is not None and len(lmk) >= 106:
            return np.array(lmk[:106])
    except Exception:
        pass
    return None


def landmark_heuristic_analysis(landmarks: np.ndarray) -> Dict[str, Any]:
    """
    基于 106 点关键点进行启发式推断：眼睛开合、张嘴程度、嘴角方向

    Returns:
        dict with ear_left, ear_right, mar, lip_corner_delta
    """
    result = {"ear_left": None, "ear_right": None, "mar": None, "lip_corner_delta": None}
    try:
        left_eye = landmarks[LEFT_EYE_PTS]
        right_eye = landmarks[RIGHT_EYE_PTS]
        mouth = landmarks[MOUTH_OUTER_PTS]

        result["ear_left"] = round(float(eye_aspect_ratio(left_eye)), 4)
        result["ear_right"] = round(float(eye_aspect_ratio(right_eye)), 4)
        result["mar"] = round(float(mouth_aspect_ratio(mouth)), 4)
        result["lip_corner_delta"] = round(float(lip_corner_delta(mouth)), 4)
    except Exception as e:
        logger.debug(f"关键点启发式推断失败: {e}")
    return result


def age_to_group(age: float) -> str:
    """将浮点年龄转换为 FairFace 风格的年龄分组"""
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


class GenderAgeDirect:
    """
    绕过 InsightFace SDK 的 Attribute 封装，直接用 onnxruntime 推理 genderage.onnx。
    
    InsightFace SDK 在 attribute.py 第87行做了 age = int(np.round(pred[2]*100))，
    将浮点年龄整数化并丢失精度。本类直接获取原始 3 维输出：
      - pred[0]: Female logit → softmax 可得性别置信度（与 SDK 一致：argmax=0→Female）
      - pred[1]: Male logit
      - pred[2]: 年龄归一化值 → ×100 得浮点年龄，如 28.67 非整数 29
    
    预处理与 InsightFace Attribute 类完全一致：自动检测模型是否内置归一化层，
    有 Sub+Mul 节点则 mean=0.0/std=1.0（透传），否则 mean=127.5/std=128.0
    """
    def __init__(self, model_path: str):
        self.session = ort.InferenceSession(model_path, providers=['CPUExecutionProvider'])
        self.input_name = self.session.get_inputs()[0].name  # 'data'
        self.input_size = (96, 96)  # genderage 输入尺寸

        # 自动检测 ONNX 模型图是否内置了归一化层（Sub/Mul），
        # 与 InsightFace SDK attribute.py 一致：
        #   - 有内置 Sub+Mul → 模型自己做归一化 → 透传 mean=0, std=1
        #   - 无内置归一化  → 需要外部归一化 → mean=127.5, std=128.0
        import onnx as _onnx
        _model = _onnx.load(model_path)
        _graph = _model.graph
        find_sub = False
        find_mul = False
        for _nid, _node in enumerate(_graph.node[:8]):
            if _node.name.startswith('Sub') or _node.name.startswith('_minus'):
                find_sub = True
            if _node.name.startswith('Mul') or _node.name.startswith('_mul'):
                find_mul = True
            if _nid < 3 and _node.name == 'bn_data':
                find_sub = True
                find_mul = True

        if find_sub and find_mul:
            # buffalo_l genderage.onnx 等内置归一化的模型
            self.input_mean = 0.0
            self.input_std = 1.0
        else:
            self.input_mean = 127.5
            self.input_std = 128.0

        logger.info(
            f"GenderAgeDirect input params: mean={self.input_mean}, std={self.input_std}"
            f" (detected sub={find_sub}, mul={find_mul})"
        )

    def predict(self, aligned_face_bgr: np.ndarray, det_score: float = 1.0,
                face_ratio: float = 0.1) -> Dict[str, Any]:
        """
        对对齐后的人脸区域直接推理，返回完整的性别/年龄/置信区间数据。

        Args:
            aligned_face_bgr: BGR 人脸图像（任意尺寸，会被缩放至 96×96）
            det_score: 人脸检测置信度（来自 InsightFace detection），用于年龄置信区间计算
            face_ratio: 人脸面积占全图比例，用于年龄置信区间质量因子

        Returns:
            dict with gender, gender_confidence, age(float), age_group,
                 age_confidence, age_lower, age_upper, model
        """
        # 预处理：与 InsightFace Attribute 类完全一致
        input_size = self.input_size
        blob = cv2.dnn.blobFromImage(
            aligned_face_bgr,
            1.0 / self.input_std,
            input_size,
            (self.input_mean, self.input_mean, self.input_mean),
            swapRB=True
        )
        pred = self.session.run(None, {self.input_name: blob})[0][0]
        # pred: [male_logit, female_logit, age_normalized]

        # --- 性别：softmax 得到真实置信度 ---
        # ⚠️ InsightFace buffalo_l genderage.onnx 输出索引：
        #   pred[0] = Female logit, pred[1] = Male logit
        #   与 SDK attribute.py 一致：np.argmax(pred[:2]) → 0=Female, 1=Male
        gender_logits = pred[:2]
        # 数值稳定 softmax
        gender_exp = np.exp(gender_logits - np.max(gender_logits))
        gender_probs = gender_exp / gender_exp.sum()
        gender_idx = int(np.argmax(gender_probs))
        gender = "Female" if gender_idx == 0 else "Male"
        gender_confidence = float(round(gender_probs[gender_idx], 4))

        # --- 年龄：pred[2] × 100 得到浮点年龄 ---
        age = float(round(pred[2] * 100.0, 2))

        # --- 年龄置信区间 ---
        # age_confidence 直接使用 det_score（人脸检测置信度），不乘人脸占比系数。
        # 因为 genderage.onnx 是回归模型（非分类），没有天然的分类置信度，
        # 人脸占比的影响通过置信区间 margin 体现即可。
        # 之前的 face_size_quality 衰减导致全身照/半身照 age_confidence 低于 0.7 阈值，
        # 性别和年龄标签被 PHP 端丢弃。
        face_size_quality = min(1.0, face_ratio / 0.05)  # 人脸占比 < 5% → 置信区间放宽
        age_confidence = float(round(det_score, 4))
        # 置信区间 margin 仍反映 face_size_quality：小脸 → 宽区间
        margin = round((1.0 - det_score * face_size_quality) * 15.0, 1)
        age_lower = float(round(max(0.0, age - margin), 1))
        age_upper = float(round(age + margin, 1))

        return {
            "gender": gender,
            "gender_confidence": gender_confidence,
            "age_group": age_to_group(age),
            "age_confidence": age_confidence,
            "age": age,
            "age_lower": age_lower,
            "age_upper": age_upper,
            "model": "insightface_buffalo_l",
        }


def extract_insightface_genderage_direct(face, pil_img, bbox, img_np) -> Dict[str, Any]:
    """
    使用 GenderAgeDirect 绕过 InsightFace SDK 封装，
    直接从 genderage.onnx 原始输出获取精确浮点年龄 + softmax 性别置信度。

    Args:
        face: InsightFace 检测到的人脸对象（含 bbox, kps, det_score）
        pil_img: 原始 PIL 图片
        bbox: 人脸边界框
        img_np: BGR numpy 数组

    Returns:
        dict with full gender/age/confidence data
    """
    if genderage_direct is None:
        # 降级：GenderAgeDirect 未加载，使用 InsightFace SDK 内置属性
        logger.warning("GenderAgeDirect 未加载，回退到 InsightFace SDK 内置 age/gender")
        fallback = extract_insightface_attrs_fallback(face)
        return fallback

    det_score = float(getattr(face, "det_score", 1.0))
    # 计算人脸占比
    img_w, img_h = pil_img.size
    face_area = (bbox[2] - bbox[0]) * (bbox[3] - bbox[1])
    img_area = img_w * img_h
    face_ratio = face_area / max(img_area, 1)

    # 获取对齐人脸（96×96 BGR）
    # 注：InsightFace norm_crop 要求 image_size 可被 112 或 128 整除，
    # 因此先对齐到 112，再缩放到 96×96（genderage.onnx 输入尺寸）
    kps = getattr(face, "kps", None)
    if kps is not None and len(kps) >= 5:
        from insightface.utils.face_align import norm_crop
        aligned = norm_crop(img_np, kps, image_size=112)
        aligned_bgr = cv2.resize(aligned, (96, 96))
    else:
        # 降级：bbox 裁剪缩放
        x1, y1, x2, y2 = [int(v) for v in bbox]
        x1, y1 = max(0, x1), max(0, y1)
        x2, y2 = min(img_w, x2), min(img_h, y2)
        face_crop = np.array(pil_img.crop((x1, y1, x2, y2)))[:, :, ::-1]  # RGB→BGR
        aligned_bgr = cv2.resize(face_crop, (96, 96))

    return genderage_direct.predict(aligned_bgr, det_score=det_score, face_ratio=face_ratio)


def extract_insightface_attrs_fallback(face) -> Dict[str, Any]:
    """
    降级方案：当 GenderAgeDirect 不可用时，使用 InsightFace SDK 内置的 face.gender/face.age。
    保留整数年龄 + 硬编码置信度（向后兼容）。
    
    ⚠️ 性别不再返回 "Unknown"，永远二选一（默认回退 Male）。
    """
    gender_val = getattr(face, "gender", None)
    age_val = getattr(face, "age", None)

    if gender_val is not None:
        gender = "Male" if int(gender_val) == 1 else "Female"
        gender_conf = 0.85
    else:
        gender = "Male"  # 不再返回 Unknown，永远二选一
        gender_conf = 0.0

    if age_val is not None:
        age_int = int(age_val)
        age_group = age_to_group(float(age_int))
        age_conf = 0.75
        age_float = float(age_int)
        age_low = max(0.0, age_float - 5.0)
        age_high = age_float + 5.0
    else:
        age_group = ""
        age_conf = 0.0
        age_float = None
        age_low = None
        age_high = None

    return {
        "gender": gender,
        "gender_confidence": gender_conf,
        "age_group": age_group,
        "age_confidence": age_conf,
        "age": age_float,
        "age_lower": age_low,
        "age_upper": age_high,
        "race": "",
        "race_confidence": 0.0,
        "model": "insightface_builtin",
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
@app.post("/api/extract_embedding")
async def extract_embedding(req: ExtractEmbeddingRequest):
    """
    人脸特征向量提取

    使用 InsightFace 检测人脸并返回 512 维 L2 归一化特征向量 (normed_embedding)。
    统一后端提取，替代前端 face-api.js 的 128 维向量，确保所有来源使用同一模型。
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

        img_np = np.array(pil_img)[:, :, ::-1]  # RGB → BGR

        # 2. InsightFace 人脸检测
        faces_detected = insight_app.get(img_np)

        if not faces_detected:
            return ExtractEmbeddingResponse(
                status="success",
                faces=[],
                face_count=0,
                extraction_time=round(time.time() - start_time, 3),
            )

        # 3. 按面积降序排序，取前 max_faces 张
        faces_sorted = sorted(faces_detected, key=lambda f: (f.bbox[2]-f.bbox[0])*(f.bbox[3]-f.bbox[1]), reverse=True)
        faces_sorted = faces_sorted[:req.max_faces]

        # 4. 提取 normed_embedding
        results: List[EmbeddingFace] = []
        for face in faces_sorted:
            emb = getattr(face, "normed_embedding", None)
            if emb is None:
                emb = getattr(face, "embedding", None)
            if emb is None:
                continue

            emb_list = emb.tolist()
            bbox = face.bbox.tolist()
            bbox_area = (bbox[2] - bbox[0]) * (bbox[3] - bbox[1])
            det_score = float(getattr(face, "det_score", 0.0))

            results.append(EmbeddingFace(
                bbox=[round(v, 1) for v in bbox],
                bbox_area=round(bbox_area, 1),
                embedding=emb_list,
                embedding_dim=len(emb_list),
                det_score=round(det_score, 4),
            ))

        extraction_time = round(time.time() - start_time, 3)
        logger.info(f"特征提取完成: {len(results)} 张人脸, 维度={results[0].embedding_dim if results else 0}, 耗时 {extraction_time}s")

        return ExtractEmbeddingResponse(
            status="success",
            faces=results,
            face_count=len(results),
            extraction_time=extraction_time,
        )

    except requests.exceptions.RequestException as e:
        logger.error(f"图片下载失败: {e}")
        raise HTTPException(status_code=400, detail=f"图片下载失败: {str(e)}")
    except Exception as e:
        logger.error(f"特征提取失败: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"特征提取失败: {str(e)}")


@app.get("/api/health")
async def health_check():
    """健康检查"""
    return {
        "status": "ok",
        "insightface_loaded": insight_app is not None,
        "insightface_genderage_direct": genderage_direct is not None,
        "fairface_loaded": fairface_model is not None,
        "emotion_loaded": emotion_recognizer is not None,
        "mode": "insightface_primary" if genderage_direct is not None else "fallback",
        "fairface_race_enabled": fairface_model is not None,
        "model": model_info["name"],
        "capabilities": {
            "gender_age": "InsightFace buffalo_l genderage (direct ONNX Runtime — float age + softmax)",
            "race": "FairFace ResNet-34" if fairface_model is not None else "disabled",
            "emotion": "EmotiEffLib enet_b2_8 ONNX" if emotion_recognizer is not None else "disabled",
            "landmark_analysis": True,
        },
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
    人物图片属性识别（一体化流程 — InsightFace buffalo_l 优先）

    1. InsightFace SCRFD 检测人脸 → 定位 + 5点关键点
    2. GenderAgeDirect (genderage.onnx 直接推理) → 精确浮点年龄 + softmax 性别置信度 + 置信区间
    3. （可选）FairFace → 仅补充人种分类
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

        # 3. 逐脸处理：InsightFace genderage 优先，FairFace 仅补充人种
        results: List[FaceResult] = []
        for face in faces_detected:
            bbox = face.bbox.tolist()  # [x1, y1, x2, y2]
            bbox_area = (bbox[2] - bbox[0]) * (bbox[3] - bbox[1])

            # --- 主要流程：InsightFace genderage 直接推理（首选） ---
            attrs = extract_insightface_genderage_direct(face, pil_img, bbox, img_np)

            # --- 补充流程：FairFace 仅用于人种分类 + 儿童年龄交叉验证 ---
            if fairface_model is not None:
                kps = getattr(face, "kps", None)
                if kps is not None and len(kps) >= 5:
                    face_img = align_face_for_fairface(img_np, kps)
                else:
                    face_img = crop_face_by_bbox(pil_img, bbox)
                fairface_result = run_fairface_inference(face_img)
                # 仅使用 FairFace 的人种分类结果（性别以 InsightFace 为准）
                attrs["race"] = fairface_result["race"]
                attrs["race_confidence"] = fairface_result["race_confidence"]

                # --- 年龄交叉验证（儿童/低年龄段修正） ---
                # InsightFace buffalo_l genderage.onnx 训练集以成人面孔为主，
                # 对儿童及青少年年龄预测系统性偏高（如 5 岁 → 32 岁）。
                # 策略：当 InsightFace 年龄比 FairFace 年龄上界高出 ≥5 岁时，
                # 以 FairFace 结果覆盖（FairFace 对儿童分类更可靠）。
                insightface_age = attrs.get("age", None)
                fairface_age_group = fairface_result.get("age_group", "")
                fairface_age_conf = fairface_result.get("age_confidence", 0)

                if insightface_age is not None and fairface_age_group and fairface_age_conf >= 0.3:
                    # 解析 FairFace age_group：「10-19」→ low=10, high=19
                    ff_parts = fairface_age_group.split("-")
                    if len(ff_parts) >= 2:
                        ff_age_low = int(ff_parts[0])
                        ff_age_max = int(ff_parts[1])
                        # 年龄差 = InsightFace预测 - FairFace上界
                        age_gap = insightface_age - ff_age_max
                        if age_gap >= 5:
                            logger.warning(
                                f"年龄交叉验证触发(差距={age_gap:.1f}岁): "
                                f"InsightFace={insightface_age}岁({attrs['age_group']}), "
                                f"FairFace={fairface_age_group}(conf={fairface_age_conf}), "
                                f"使用 FairFace 年龄覆盖"
                            )
                            attrs["age_group"] = fairface_age_group
                            attrs["age_confidence"] = fairface_age_conf
                            attrs["age"] = float(round((ff_age_low + ff_age_max) / 2.0, 1))
                            attrs["age_lower"] = float(ff_age_low)
                            attrs["age_upper"] = float(ff_age_max)
                            attrs["model"] = "insightface_buffalo_l+fairface_age_override"

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
                age=attrs.get("age"),
                age_lower=attrs.get("age_lower"),
                age_upper=attrs.get("age_upper"),
                race=attrs["race"],
                race_confidence=attrs["race_confidence"],
                body_type=body_info["body_type"],
                body_type_confidence=body_info["body_type_confidence"],
                gender_model=attrs.get("model"),
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


@app.post("/api/analyze/extended")
async def analyze_extended(req: ExtendedAnalyzeRequest):
    """
    扩展人物属性识别（基础属性 + 表情识别 + 关键点启发式推断）

    复用现有 InsightFace 人脸检测结果，额外进行：
    1. EmotiEffLib 表情识别（8类 → 6类中文概率分值）
    2. 106点关键点启发式推断：
       - EAR（眼睛开合度）
       - MAR（张嘴程度）
       - 嘴角方向（微笑/皱眉判断）
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

        img_np = np.array(pil_img)[:, :, ::-1]

        # 2. InsightFace 人脸检测（复用基础流程）
        faces_detected = insight_app.get(img_np)

        if not faces_detected:
            return ExtendedAnalyzeResponse(
                status="success",
                faces=[],
                face_count=0,
                analysis_time=round(time.time() - start_time, 3),
            )

        # 3. 逐脸处理
        results: List[ExtendedFaceResult] = []
        for face in faces_detected:
            bbox = face.bbox.tolist()
            bbox_area = (bbox[2] - bbox[0]) * (bbox[3] - bbox[1])

            # --- 基础属性（复用 /api/analyze 逻辑） ---
            attrs = extract_insightface_genderage_direct(face, pil_img, bbox, img_np)

            if fairface_model is not None:
                kps = getattr(face, "kps", None)
                face_img = align_face_for_fairface(img_np, kps) if (kps is not None and len(kps) >= 5) else crop_face_by_bbox(pil_img, bbox)
                fairface_result = run_fairface_inference(face_img)
                attrs["race"] = fairface_result["race"]
                attrs["race_confidence"] = fairface_result["race_confidence"]

                # 年龄交叉验证（儿童修正）
                insightface_age = attrs.get("age", None)
                fairface_age_group = fairface_result.get("age_group", "")
                fairface_age_conf = fairface_result.get("age_confidence", 0)
                if insightface_age is not None and fairface_age_group and fairface_age_conf >= 0.3:
                    ff_parts = fairface_age_group.split("-")
                    if len(ff_parts) >= 2:
                        ff_age_low, ff_age_max = int(ff_parts[0]), int(ff_parts[1])
                        if insightface_age - ff_age_max >= 5:
                            attrs["age_group"] = fairface_age_group
                            attrs["age_confidence"] = fairface_age_conf
                            attrs["age"] = float(round((ff_age_low + ff_age_max) / 2.0, 1))
                            attrs["age_lower"] = float(ff_age_low)
                            attrs["age_upper"] = float(ff_age_max)
                            attrs["model"] = "insightface_buffalo_l+fairface_age_override"

            # --- 扩展：表情识别 ---
            kps_for_align = getattr(face, "kps", None)
            if kps_for_align is not None and len(kps_for_align) >= 5:
                emotion_face = align_face_for_fairface(img_np, kps_for_align)
                emotion_np = np.array(emotion_face)[:, :, ::-1]  # RGB → BGR
            else:
                emotion_np = np.array(crop_face_by_bbox(pil_img, bbox))[:, :, ::-1]

            emotion_scores_dict = recognize_emotion(emotion_np)
            primary_emotion = max(emotion_scores_dict, key=emotion_scores_dict.get) if emotion_scores_dict else ""

            # --- 扩展：关键点启发式推断 ---
            landmarks = extract_landmarks(face)
            lm_result = landmark_heuristic_analysis(landmarks) if landmarks is not None else {}

            # 构建 EmotionScores
            emotion = EmotionScores(
                calm=emotion_scores_dict.get("平静", 0.0),
                happy=emotion_scores_dict.get("微笑", 0.0),
                sad=emotion_scores_dict.get("伤心", 0.0),
                surprised=emotion_scores_dict.get("惊讶", 0.0),
                angry=emotion_scores_dict.get("生气", 0.0),
                fear=emotion_scores_dict.get("恐惧", 0.0),
            )

            results.append(ExtendedFaceResult(
                bbox=[round(v, 1) for v in bbox],
                bbox_area=round(bbox_area, 1),
                gender=attrs["gender"],
                gender_confidence=attrs["gender_confidence"],
                age_group=attrs["age_group"],
                age_confidence=attrs["age_confidence"],
                age=attrs.get("age"),
                age_lower=attrs.get("age_lower"),
                age_upper=attrs.get("age_upper"),
                race=attrs["race"],
                race_confidence=attrs["race_confidence"],
                body_type=None,
                body_type_confidence=None,
                gender_model=attrs.get("model"),
                emotion=emotion,
                emotion_primary=primary_emotion,
                # Phase 2 预留字段
                glasses_type="",
                eyelid_type="",
                skin_tone="",
                hair_length="",
            ))

        results.sort(key=lambda r: r.bbox_area, reverse=True)
        analysis_time = round(time.time() - start_time, 3)
        logger.info(f"扩展分析完成: {len(results)} 张人脸, 耗时 {analysis_time}s")

        return ExtendedAnalyzeResponse(
            status="success",
            faces=results,
            face_count=len(results),
            analysis_time=analysis_time,
        )

    except requests.exceptions.RequestException as e:
        logger.error(f"图片下载失败: {e}")
        raise HTTPException(status_code=400, detail=f"图片下载失败: {str(e)}")
    except Exception as e:
        logger.error(f"扩展分析失败: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"扩展分析失败: {str(e)}")


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


def load_genderage_direct(genderage_path: str):
    """加载 GenderAgeDirect — 绕过 InsightFace SDK，直接推理 genderage.onnx"""
    global genderage_direct
    logger.info(f"正在加载 GenderAgeDirect: {genderage_path}")
    genderage_direct = GenderAgeDirect(genderage_path)
    logger.info("GenderAgeDirect 加载完成! (precise float age + softmax confidence)")


# --------------- 入口 ---------------
if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="InsightFace buffalo_l (Primary) + FairFace (Race Only) 人物属性识别 API")
    parser.add_argument("--host", default="0.0.0.0", help="监听地址 (默认: 0.0.0.0)")
    parser.add_argument("--port", type=int, default=8867, help="监听端口 (默认: 8867)")
    parser.add_argument("--det-model", default="buffalo_l",
                        help="InsightFace 检测模型名称 (默认: buffalo_l)")
    parser.add_argument("--genderage-model",
                        default=os.path.join(os.path.dirname(os.path.abspath(__file__)), "models", "insightface", "models", "buffalo_l", "genderage.onnx"),
                        help="InsightFace genderage.onnx 路径 (默认: deploy/models/insightface/models/buffalo_l/genderage.onnx)")
    parser.add_argument("--fairface-weights",
                        default=os.path.join(os.path.dirname(os.path.abspath(__file__)), "models", "res34_fair_align_multi_7_20190809.pt"),
                        help="FairFace 模型权重文件路径（可选，仅用于人种分类）")
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

    # GenderAgeDirect 加载（首选模型，绕过 InsightFace SDK 封装获取精确浮点年龄）
    if os.path.isfile(args.genderage_model):
        load_genderage_direct(args.genderage_model)
    else:
        logger.warning(f"genderage.onnx 不存在: {args.genderage_model}")
        logger.warning("服务将使用 InsightFace SDK 内置 age/gender（整数年龄 + 硬编码置信度）")

    # FairFace 可选加载：仅用于人种分类
    if os.path.isfile(args.fairface_weights):
        load_fairface(weights_path=args.fairface_weights, device_str=args.device)
        logger.info("FairFace 已加载，人种分类可用")
    else:
        logger.warning(f"FairFace 权重文件不存在: {args.fairface_weights}")
        logger.warning("人种分类将返回 Unknown")

    # 启动服务
    logger.info(f"InsightFace buffalo_l (Primary) + FairFace (Race Only) API Server 启动于 http://{args.host}:{args.port}")
    logger.info(f"API 文档: http://{args.host}:{args.port}/docs")
    uvicorn.run(app, host=args.host, port=args.port, workers=args.workers)
