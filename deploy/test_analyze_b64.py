#!/usr/bin/env python3
"""Test the analyze API with a synthetic face image (base64)"""
import requests
import json
import time
import base64
import io
from PIL import Image, ImageDraw

# Create a synthetic image with a face-like shape for testing
img = Image.new("RGB", (400, 500), (200, 180, 160))
draw = ImageDraw.Draw(img)
# Draw a simple face-like shape
# Head oval
draw.ellipse([120, 60, 280, 280], fill=(220, 190, 170), outline=(100, 80, 60))
# Eyes
draw.ellipse([160, 140, 185, 160], fill=(255, 255, 255))
draw.ellipse([215, 140, 240, 160], fill=(255, 255, 255))
draw.ellipse([167, 145, 180, 158], fill=(50, 30, 20))
draw.ellipse([222, 145, 235, 158], fill=(50, 30, 20))
# Nose
draw.line([(200, 165), (195, 200), (205, 200)], fill=(180, 150, 130), width=2)
# Mouth
draw.arc([175, 200, 225, 235], 0, 180, fill=(180, 100, 100), width=2)

# Convert to base64
buf = io.BytesIO()
img.save(buf, format="JPEG")
img_b64 = base64.b64encode(buf.getvalue()).decode("utf-8")

print(f"Test image: {img.size}, base64 length: {len(img_b64)}")

# Test API
API_BASE = "http://127.0.0.1:8867"

print("\n1. Testing /api/health...")
try:
    r = requests.get(f"{API_BASE}/api/health", timeout=5)
    print(f"   Status: {r.status_code}, Response: {r.json()}")
except Exception as e:
    print(f"   Error: {e}")

print("\n2. Testing /api/analyze with synthetic image (base64)...")
start = time.time()
try:
    resp = requests.post(
        f"{API_BASE}/api/analyze",
        json={"image_base64": img_b64, "detect_body_type": True},
        timeout=30,
    )
    elapsed = time.time() - start
    result = resp.json()
    print(f"   Status: {resp.status_code}, Time: {elapsed:.1f}s")
    print(f"   Response: {json.dumps(result, indent=2, ensure_ascii=False)}")
except Exception as e:
    print(f"   Error: {e}")

# Also test with a real-looking face using random pixels if no face detected
print("\n3. Testing /api/analyze with no-face image...")
no_face_img = Image.new("RGB", (200, 200), (100, 150, 200))
buf2 = io.BytesIO()
no_face_img.save(buf2, format="JPEG")
b64_2 = base64.b64encode(buf2.getvalue()).decode("utf-8")
start = time.time()
try:
    resp2 = requests.post(
        f"{API_BASE}/api/analyze",
        json={"image_base64": b64_2},
        timeout=30,
    )
    elapsed = time.time() - start
    result2 = resp2.json()
    print(f"   Status: {resp2.status_code}, Time: {elapsed:.1f}s")
    print(f"   Response: {json.dumps(result2, indent=2, ensure_ascii=False)}")
except Exception as e:
    print(f"   Error: {e}")

print("\nAll tests complete.")
