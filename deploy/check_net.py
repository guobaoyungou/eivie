#!/usr/bin/env python3
"""Check network accessibility and download FairFace model."""
import urllib.request
import sys
import os

# Test connectivity
sites = [
    ("Google", "https://www.google.com"),
    ("HF-Mirror", "https://hf-mirror.com"),
    ("GitHub", "https://github.com"),
    ("ModelScope", "https://modelscope.cn"),
]

for name, url in sites:
    try:
        r = urllib.request.urlopen(url, timeout=8)
        print(f"{name}: OK ({r.status})")
    except Exception as e:
        print(f"{name}: FAIL ({e})")

# Try to download FairFace model from various sources
MODEL_DIR = "/home/fairface-server/models"
MODEL_FILE = os.path.join(MODEL_DIR, "res34_fair_align_multi_7_20190809.pt")

if os.path.exists(MODEL_FILE):
    size = os.path.getsize(MODEL_FILE)
    print(f"\nModel already exists: {MODEL_FILE} ({size} bytes)")
    sys.exit(0)

# Alternative download URLs to try
URLS = [
    # Direct Google Drive (via proxy-friendly URL)
    "https://drive.usercontent.google.com/download?id=1i1L3Yqwaio7YSOCj7ftgk8ZZchPG7dmH&confirm=t",
    # GitHub releases mirror (if available)
    "https://ghproxy.com/https://github.com/dchen236/FairFace/releases/download/v1.0/res34_fair_align_multi_7_20190809.pt",
]

for url in URLS:
    print(f"\nTrying: {url[:80]}...")
    try:
        req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
        resp = urllib.request.urlopen(req, timeout=15)
        content_length = resp.headers.get("Content-Length", "unknown")
        print(f"  Response: {resp.status}, Content-Length: {content_length}")
        if resp.status == 200 and content_length != "unknown" and int(content_length) > 1000000:
            print(f"  Downloading to {MODEL_FILE}...")
            with open(MODEL_FILE, "wb") as f:
                total = 0
                while True:
                    chunk = resp.read(1024 * 1024)
                    if not chunk:
                        break
                    f.write(chunk)
                    total += len(chunk)
                    print(f"  Downloaded: {total / 1024 / 1024:.1f} MB", end="\r")
            print(f"\n  Done! Total: {total / 1024 / 1024:.1f} MB")
            sys.exit(0)
        else:
            print(f"  Skipping - unexpected response")
    except Exception as e:
        print(f"  Failed: {e}")

print("\nAll download attempts failed. Please manually download the model.")
print("Google Drive: https://drive.google.com/file/d/1i1L3Yqwaio7YSOCj7ftgk8ZZchPG7dmH")
