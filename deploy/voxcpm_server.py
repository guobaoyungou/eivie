#!/usr/bin/env python3
"""
VoxCPM2 API Server — 模型广场接入用 REST API 服务
=================================================

将 VoxCPM2 TTS 模型封装为标准 REST API，供模型广场 PHP 后端调用。
用户只需在 GPU 服务器上部署此脚本，然后在模型广场配置 IP:Port 即可使用。

部署步骤:
    1. 安装依赖:
       pip install voxcpm fastapi uvicorn python-multipart soundfile numpy
    2. 启动服务:
       python voxcpm_server.py --host 0.0.0.0 --port 8866
    3. 指定模型路径 (可选):
       python voxcpm_server.py --model openbmb/VoxCPM2 --port 8866
    4. 禁用降噪器以节省显存 (可选):
       python voxcpm_server.py --no-denoiser --port 8866

API 端点:
    GET  /api/health       — 健康检查
    GET  /api/info         — 模型信息与能力
    POST /api/tts          — 文本转语音 (含声音设计)
    POST /api/clone        — 声音克隆 (可控克隆 + 极致克隆)
    POST /api/tts/stream   — 流式文本转语音
"""

import os
import io
import sys
import base64
import logging
import argparse
import tempfile
import time
import uuid
from typing import Optional, List

import numpy as np
import soundfile as sf
import uvicorn
from fastapi import FastAPI, HTTPException, UploadFile, File, Form
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse, StreamingResponse
from pydantic import BaseModel, Field

# --------------- 日志配置 ---------------
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[logging.StreamHandler(sys.stdout)],
)
logger = logging.getLogger("voxcpm_server")

# --------------- 全局变量 ---------------
voxcpm_model = None
model_info = {
    "name": "VoxCPM2",
    "version": "2.0",
    "sample_rate": 48000,
    "languages": 30,
    "capabilities": [
        "text_to_speech",
        "voice_design",
        "controllable_clone",
        "ultimate_clone",
        "streaming",
    ],
}


# --------------- 请求/响应模型 ---------------
class TTSRequest(BaseModel):
    """文本转语音请求"""
    text: str = Field(..., description="要合成的文本内容")
    control: Optional[str] = Field(None, description="声音设计描述 (性别、年龄、语气、情绪、语速等)")
    cfg_value: Optional[float] = Field(2.0, description="CFG引导强度 (越高越贴合描述)")
    inference_timesteps: Optional[int] = Field(10, description="LocDiT推理步数 (越多质量越高但更慢)")
    normalize: Optional[bool] = Field(True, description="是否进行文本规范化")


class CloneRequest(BaseModel):
    """声音克隆请求 (JSON模式, 音频通过base64传递)"""
    text: str = Field(..., description="要合成的目标文本")
    reference_audio_base64: str = Field(..., description="参考音频 Base64 编码 (WAV格式)")
    control: Optional[str] = Field(None, description="风格控制指令 (情绪/语速/语气)")
    prompt_text: Optional[str] = Field(None, description="参考音频的文字转录 (极致克隆模式)")
    ultimate_clone: Optional[bool] = Field(False, description="是否启用极致克隆模式")
    cfg_value: Optional[float] = Field(2.0, description="CFG引导强度")
    inference_timesteps: Optional[int] = Field(10, description="LocDiT推理步数")


class TTSResponse(BaseModel):
    """语音合成响应"""
    status: str = "success"
    audio_base64: str = Field(..., description="生成的音频 Base64 编码 (WAV格式)")
    sample_rate: int = Field(48000, description="采样率")
    duration: float = Field(0.0, description="音频时长 (秒)")
    generation_time: float = Field(0.0, description="生成耗时 (秒)")


class ErrorResponse(BaseModel):
    """错误响应"""
    status: str = "error"
    error: str
    detail: Optional[str] = None


# --------------- FastAPI 应用 ---------------
app = FastAPI(
    title="VoxCPM2 API Server",
    description="VoxCPM2 语音合成 REST API — 模型广场接入服务",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)


def wav_to_base64(wav_data: np.ndarray, sample_rate: int) -> str:
    """将 numpy 音频数据转为 Base64 编码的 WAV"""
    buffer = io.BytesIO()
    sf.write(buffer, wav_data, sample_rate, format="WAV")
    buffer.seek(0)
    return base64.b64encode(buffer.read()).decode("utf-8")


def base64_to_wav_file(b64_data: str) -> str:
    """将 Base64 音频解码并保存为临时 WAV 文件, 返回文件路径"""
    audio_bytes = base64.b64decode(b64_data)
    tmp_file = tempfile.NamedTemporaryFile(suffix=".wav", delete=False)
    tmp_file.write(audio_bytes)
    tmp_file.close()
    return tmp_file.name


@app.get("/api/health")
async def health_check():
    """健康检查"""
    return {
        "status": "ok",
        "model_loaded": voxcpm_model is not None,
        "model": model_info["name"],
        "sample_rate": model_info["sample_rate"],
    }


@app.get("/api/info")
async def get_model_info():
    """获取模型信息与能力"""
    return {
        "status": "ok",
        "model": model_info,
        "supported_languages": [
            "zh", "en", "ja", "ko", "fr", "de", "es", "pt", "ru", "it",
            "ar", "my", "da", "nl", "fi", "el", "he", "hi", "id", "km",
            "lo", "ms", "no", "pl", "sv", "sw", "tl", "th", "tr", "vi",
        ],
        "supported_dialects": [
            "四川话", "粤语", "吴语", "东北话", "河南话",
            "陕西话", "山东话", "天津话", "闽南话",
        ],
    }


@app.post("/api/tts", response_model=TTSResponse)
async def text_to_speech(req: TTSRequest):
    """
    文本转语音 (含声音设计)

    - 纯文本合成: 仅提供 text
    - 声音设计: 提供 text + control (自然语言描述目标声音)

    声音设计示例:
      control = "年轻女性，温柔甜美，语速偏慢"
      text = "你好，欢迎使用 VoxCPM2 语音合成服务。"
    """
    if voxcpm_model is None:
        raise HTTPException(status_code=503, detail="VoxCPM模型未加载")

    try:
        start_time = time.time()

        # 构建合成文本 (声音设计通过括号前缀实现)
        synth_text = req.text
        if req.control:
            synth_text = f"({req.control}){req.text}"

        logger.info(f"TTS 请求: text_len={len(req.text)}, control={'yes' if req.control else 'no'}")

        wav = voxcpm_model.generate(
            text=synth_text,
            cfg_value=req.cfg_value or 2.0,
            inference_timesteps=req.inference_timesteps or 10,
        )

        generation_time = time.time() - start_time
        sample_rate = voxcpm_model.tts_model.sample_rate
        duration = len(wav) / sample_rate

        audio_b64 = wav_to_base64(wav, sample_rate)

        logger.info(f"TTS 完成: duration={duration:.2f}s, gen_time={generation_time:.2f}s")

        return TTSResponse(
            status="success",
            audio_base64=audio_b64,
            sample_rate=sample_rate,
            duration=round(duration, 3),
            generation_time=round(generation_time, 3),
        )

    except Exception as e:
        logger.error(f"TTS 生成失败: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/api/clone", response_model=TTSResponse)
async def voice_clone(req: CloneRequest):
    """
    声音克隆

    - 可控克隆: 提供 reference_audio_base64 + text, 可选 control
    - 极致克隆: 提供 reference_audio_base64 + text + prompt_text, 设 ultimate_clone=true
    """
    if voxcpm_model is None:
        raise HTTPException(status_code=503, detail="VoxCPM模型未加载")

    ref_wav_path = None
    try:
        start_time = time.time()

        # 解码参考音频
        ref_wav_path = base64_to_wav_file(req.reference_audio_base64)

        # 构建合成文本
        synth_text = req.text
        if req.control and not req.ultimate_clone:
            synth_text = f"({req.control}){req.text}"

        logger.info(
            f"Clone 请求: text_len={len(req.text)}, "
            f"ultimate={req.ultimate_clone}, control={'yes' if req.control else 'no'}"
        )

        generate_kwargs = {
            "text": synth_text,
            "reference_wav_path": ref_wav_path,
            "cfg_value": req.cfg_value or 2.0,
            "inference_timesteps": req.inference_timesteps or 10,
        }

        # 极致克隆模式
        if req.ultimate_clone and req.prompt_text:
            generate_kwargs["prompt_wav_path"] = ref_wav_path
            generate_kwargs["prompt_text"] = req.prompt_text

        wav = voxcpm_model.generate(**generate_kwargs)

        generation_time = time.time() - start_time
        sample_rate = voxcpm_model.tts_model.sample_rate
        duration = len(wav) / sample_rate

        audio_b64 = wav_to_base64(wav, sample_rate)

        logger.info(f"Clone 完成: duration={duration:.2f}s, gen_time={generation_time:.2f}s")

        return TTSResponse(
            status="success",
            audio_base64=audio_b64,
            sample_rate=sample_rate,
            duration=round(duration, 3),
            generation_time=round(generation_time, 3),
        )

    except Exception as e:
        logger.error(f"Clone 生成失败: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))

    finally:
        if ref_wav_path and os.path.exists(ref_wav_path):
            os.unlink(ref_wav_path)


@app.post("/api/clone/upload")
async def voice_clone_upload(
    text: str = Form(...),
    reference_audio: UploadFile = File(...),
    control: Optional[str] = Form(None),
    prompt_text: Optional[str] = Form(None),
    ultimate_clone: Optional[bool] = Form(False),
    cfg_value: Optional[float] = Form(2.0),
    inference_timesteps: Optional[int] = Form(10),
):
    """
    声音克隆 (文件上传模式)
    通过 multipart/form-data 上传参考音频文件
    """
    if voxcpm_model is None:
        raise HTTPException(status_code=503, detail="VoxCPM模型未加载")

    ref_wav_path = None
    try:
        start_time = time.time()

        # 保存上传的音频文件
        audio_bytes = await reference_audio.read()
        tmp_file = tempfile.NamedTemporaryFile(suffix=".wav", delete=False)
        tmp_file.write(audio_bytes)
        tmp_file.close()
        ref_wav_path = tmp_file.name

        synth_text = text
        if control and not ultimate_clone:
            synth_text = f"({control}){text}"

        generate_kwargs = {
            "text": synth_text,
            "reference_wav_path": ref_wav_path,
            "cfg_value": cfg_value or 2.0,
            "inference_timesteps": inference_timesteps or 10,
        }

        if ultimate_clone and prompt_text:
            generate_kwargs["prompt_wav_path"] = ref_wav_path
            generate_kwargs["prompt_text"] = prompt_text

        wav = voxcpm_model.generate(**generate_kwargs)

        generation_time = time.time() - start_time
        sample_rate = voxcpm_model.tts_model.sample_rate
        duration = len(wav) / sample_rate
        audio_b64 = wav_to_base64(wav, sample_rate)

        return JSONResponse({
            "status": "success",
            "audio_base64": audio_b64,
            "sample_rate": sample_rate,
            "duration": round(duration, 3),
            "generation_time": round(generation_time, 3),
        })

    except Exception as e:
        logger.error(f"Clone Upload 生成失败: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))

    finally:
        if ref_wav_path and os.path.exists(ref_wav_path):
            os.unlink(ref_wav_path)


@app.post("/api/tts/stream")
async def text_to_speech_stream(req: TTSRequest):
    """
    流式文本转语音
    返回 audio/wav 流式响应
    """
    if voxcpm_model is None:
        raise HTTPException(status_code=503, detail="VoxCPM模型未加载")

    try:
        synth_text = req.text
        if req.control:
            synth_text = f"({req.control}){req.text}"

        def audio_stream():
            chunks = []
            for chunk in voxcpm_model.generate_streaming(text=synth_text):
                chunks.append(chunk)
            # 合并所有chunk后输出完整WAV
            wav = np.concatenate(chunks)
            buffer = io.BytesIO()
            sf.write(buffer, wav, voxcpm_model.tts_model.sample_rate, format="WAV")
            buffer.seek(0)
            yield buffer.read()

        return StreamingResponse(audio_stream(), media_type="audio/wav")

    except Exception as e:
        logger.error(f"TTS Stream 失败: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))


# --------------- 模型加载 ---------------
def load_model(model_path: str, load_denoiser: bool = False):
    """加载 VoxCPM2 模型"""
    global voxcpm_model
    from voxcpm import VoxCPM

    logger.info(f"正在加载 VoxCPM2 模型: {model_path}")
    logger.info(f"降噪器: {'启用' if load_denoiser else '禁用'}")

    voxcpm_model = VoxCPM.from_pretrained(
        model_path,
        load_denoiser=load_denoiser,
    )

    model_info["sample_rate"] = voxcpm_model.tts_model.sample_rate
    logger.info(f"模型加载完成! 采样率: {model_info['sample_rate']}Hz")


# --------------- 入口 ---------------
if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="VoxCPM2 API Server")
    parser.add_argument("--host", default="0.0.0.0", help="监听地址 (默认: 0.0.0.0)")
    parser.add_argument("--port", type=int, default=8866, help="监听端口 (默认: 8866)")
    parser.add_argument("--model", default="openbmb/VoxCPM2", help="模型路径或HuggingFace repo ID")
    parser.add_argument("--no-denoiser", action="store_true", help="禁用降噪器以节省显存")
    parser.add_argument("--workers", type=int, default=1, help="Worker数量 (GPU模型建议为1)")

    args = parser.parse_args()

    # 加载模型
    load_model(args.model, load_denoiser=not args.no_denoiser)

    # 启动服务
    logger.info(f"VoxCPM2 API Server 启动于 http://{args.host}:{args.port}")
    logger.info(f"API 文档: http://{args.host}:{args.port}/docs")
    uvicorn.run(app, host=args.host, port=args.port, workers=args.workers)
