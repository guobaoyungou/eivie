#!/usr/bin/env python3
"""Test the analyze API with a real selfie image"""
import requests
import json
import time
import base64

API_BASE = "http://127.0.0.1:8867"

# Load local selfie image
img_path = "/home/www/ai.eivie.cn/public/upload/selfie/20260411/selfie_1775873038_4735.jpg"
with open(img_path, "rb") as f:
    img_b64 = base64.b64encode(f.read()).decode("utf-8")

print(f"Image: {img_path}")
print(f"Base64 length: {len(img_b64)}")

print("\nTesting /api/analyze with real selfie...")
start = time.time()
try:
    resp = requests.post(
        f"{API_BASE}/api/analyze",
        json={"image_base64": img_b64, "detect_body_type": True},
        timeout=60,
    )
    elapsed = time.time() - start
    result = resp.json()
    print(f"Status: {resp.status_code}, Time: {elapsed:.1f}s")
    print(f"Response:\n{json.dumps(result, indent=2, ensure_ascii=False)}")
except Exception as e:
    print(f"Error: {e}")

print("\nTest complete.")
