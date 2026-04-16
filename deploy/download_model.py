#!/usr/bin/env python3
"""Download FairFace model from alternative sources."""
import urllib.request
import os
import sys

MODEL_DIR = "/home/fairface-server/models"
MODEL_FILE = os.path.join(MODEL_DIR, "res34_fair_align_multi_7_20190809.pt")
EXPECTED_SIZE_MIN = 80_000_000  # at least 80MB

if os.path.exists(MODEL_FILE) and os.path.getsize(MODEL_FILE) > EXPECTED_SIZE_MIN:
    print(f"Model exists: {os.path.getsize(MODEL_FILE)} bytes")
    sys.exit(0)

# Google Drive file ID: 1i1L3Yqwaio7YSOCj7ftgk8ZZchPG7dmH
GD_ID = "1i1L3Yqwaio7YSOCj7ftgk8ZZchPG7dmH"

# Try Google Drive proxy services accessible from China
URLS = [
    f"https://drive.google.com/uc?export=download&id={GD_ID}&confirm=t",
    f"https://docs.google.com/uc?export=download&id={GD_ID}&confirm=t",
]

for i, url in enumerate(URLS):
    print(f"Attempt {i+1}: {url[:80]}...")
    try:
        req = urllib.request.Request(url, headers={
            "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36"
        })
        resp = urllib.request.urlopen(req, timeout=30)
        ct = resp.headers.get("Content-Type", "")
        cl = resp.headers.get("Content-Length", "0")
        print(f"  Status: {resp.status}, Content-Type: {ct}, Length: {cl}")
        
        if int(cl or 0) > EXPECTED_SIZE_MIN:
            print(f"  Downloading...")
            with open(MODEL_FILE, "wb") as f:
                total = 0
                while True:
                    chunk = resp.read(1024 * 1024)
                    if not chunk:
                        break
                    f.write(chunk)
                    total += len(chunk)
            print(f"  Done: {total / 1024 / 1024:.1f} MB")
            sys.exit(0)
        else:
            print(f"  Skipping: Content-Length too small or wrong Content-Type")
    except Exception as e:
        print(f"  Failed: {e}")

# If all fails, try using requests with session (follows redirects better)
try:
    import requests
    session = requests.Session()
    
    url = f"https://drive.google.com/uc?export=download&id={GD_ID}"
    print(f"\nTrying with requests session...")
    resp = session.get(url, stream=True, timeout=30)
    
    # Check for virus scan warning page
    for key, value in resp.cookies.items():
        if key.startswith("download_warning"):
            url = f"https://drive.google.com/uc?export=download&id={GD_ID}&confirm={value}"
            resp = session.get(url, stream=True, timeout=30)
            break
    
    ct = resp.headers.get("Content-Type", "")
    cl = int(resp.headers.get("Content-Length", 0))
    print(f"  Status: {resp.status_code}, Content-Type: {ct}, Length: {cl}")
    
    if cl > EXPECTED_SIZE_MIN:
        with open(MODEL_FILE, "wb") as f:
            total = 0
            for chunk in resp.iter_content(chunk_size=1024*1024):
                if chunk:
                    f.write(chunk)
                    total += len(chunk)
        print(f"  Done: {total / 1024 / 1024:.1f} MB")
        sys.exit(0)
except Exception as e:
    print(f"  requests method failed: {e}")

print("\n=== MANUAL DOWNLOAD REQUIRED ===")
print("Google Drive is not accessible from this server.")
print("Please download the model manually from:")
print(f"  https://drive.google.com/file/d/{GD_ID}")
print(f"Then upload to: {MODEL_FILE}")
sys.exit(1)
