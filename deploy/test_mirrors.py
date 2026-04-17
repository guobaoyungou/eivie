#!/usr/bin/env python3
"""测试可用的下载源"""
import urllib.request
import time

URLS = [
    ("GitHub原站", "https://github.com/deepinsight/insightface/releases/download/v0.7/buffalo_l.zip"),
    ("GHProxy镜像", "https://mirror.ghproxy.com/https://github.com/deepinsight/insightface/releases/download/v0.7/buffalo_l.zip"),
    ("GHP.CI镜像", "https://ghp.ci/https://github.com/deepinsight/insightface/releases/download/v0.7/buffalo_l.zip"),
    ("HuggingFace镜像", "https://hf-mirror.com/datasets/buffalonoam/insightface-models/resolve/main/buffalo_l.zip"),
    ("InsightFace官网", "http://insightface.cn/files/models/buffalo_l.zip"),
]

for name, url in URLS:
    try:
        start = time.time()
        req = urllib.request.Request(url)
        req.add_header('User-Agent', 'Mozilla/5.0')
        req.add_header('Range', 'bytes=0-1023')
        resp = urllib.request.urlopen(req, timeout=10)
        data = resp.read()
        elapsed = time.time() - start
        status = resp.getcode()
        content_length = resp.headers.get('Content-Range', 'unknown')
        print(f"[OK] {name}: status={status}, got {len(data)} bytes in {elapsed:.1f}s, range={content_length}")
        resp.close()
    except Exception as e:
        elapsed = time.time() - start
        print(f"[FAIL] {name}: {e} ({elapsed:.1f}s)")
