#!/usr/bin/env python3
"""Test Google Drive accessibility and try to download FairFace weights"""
import urllib.request
import time
import sys
import os

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
OUTPUT = os.path.join(SCRIPT_DIR, "models", "res34_fair_align_multi_7_20190809.pt")

# Try Google Drive
print("Testing Google Drive access...")
url = "https://drive.google.com/uc?export=download&id=1i1L3Yqwaio7YSOCj7ftgk8ZZchPG7dmH"
start = time.time()
try:
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
    resp = urllib.request.urlopen(req, timeout=10)
    print(f"Google Drive OK: status={resp.getcode()}, time={time.time()-start:.1f}s")
    content_type = resp.headers.get("Content-Type", "")
    content_length = resp.headers.get("Content-Length", "unknown")
    print(f"  Content-Type: {content_type}")
    print(f"  Content-Length: {content_length}")
    
    # Check if it's the confirm page (large files need confirmation)
    if "text/html" in content_type:
        print("  -> Got confirmation page, need to handle cookies/confirm...")
        html = resp.read().decode("utf-8", errors="ignore")
        resp.close()
        # Look for confirm token
        import re
        confirm_match = re.search(r'confirm=([0-9A-Za-z_-]+)', html)
        if confirm_match:
            confirm_token = confirm_match.group(1)
            confirm_url = f"{url}&confirm={confirm_token}"
            print(f"  -> Confirm token: {confirm_token}")
            req2 = urllib.request.Request(confirm_url, headers={"User-Agent": "Mozilla/5.0"})
            resp2 = urllib.request.urlopen(req2, timeout=30)
            print(f"  -> Confirmed download: status={resp2.getcode()}")
            # Download the file
            total = int(resp2.headers.get("Content-Length", 0))
            downloaded = 0
            with open(OUTPUT, "wb") as f:
                while True:
                    chunk = resp2.read(1024 * 1024)
                    if not chunk:
                        break
                    f.write(chunk)
                    downloaded += len(chunk)
                    if total:
                        print(f"\r  Downloaded: {downloaded/1024/1024:.1f}/{total/1024/1024:.1f} MB ({downloaded*100/total:.0f}%)", end="", flush=True)
                    else:
                        print(f"\r  Downloaded: {downloaded/1024/1024:.1f} MB", end="", flush=True)
            print()
            print(f"SUCCESS! Saved to {OUTPUT} ({downloaded/1024/1024:.1f} MB)")
            sys.exit(0)
        else:
            # Try direct download (new Google Drive format)
            # uuid format
            uuid_match = re.search(r'id="downloadForm".*?action="(.*?)"', html, re.DOTALL)
            if uuid_match:
                download_url = uuid_match.group(1)
                print(f"  -> Found form action: {download_url}")
            
            # Try downloading anyway
            print("  -> Trying direct download with cookies...")
    else:
        # Direct download
        total = int(content_length) if content_length != "unknown" else 0
        downloaded = 0
        with open(OUTPUT, "wb") as f:
            while True:
                chunk = resp.read(1024 * 1024)
                if not chunk:
                    break
                f.write(chunk)
                downloaded += len(chunk)
                if total:
                    print(f"\r  Downloaded: {downloaded/1024/1024:.1f}/{total/1024/1024:.1f} MB ({downloaded*100/total:.0f}%)", end="", flush=True)
                else:
                    print(f"\r  Downloaded: {downloaded/1024/1024:.1f} MB", end="", flush=True)
        print()
        print(f"SUCCESS! Saved to {OUTPUT} ({downloaded/1024/1024:.1f} MB)")
        sys.exit(0)
    resp.close()
except Exception as e:
    print(f"Google Drive FAILED: {e} ({time.time()-start:.1f}s)")

# Try gdown as fallback
print("\nTrying gdown...")
try:
    import gdown
    gdown.download(id="1i1L3Yqwaio7YSOCj7ftgk8ZZchPG7dmH", output=OUTPUT, quiet=False)
    if os.path.exists(OUTPUT) and os.path.getsize(OUTPUT) > 10 * 1024 * 1024:
        print(f"SUCCESS via gdown! File size: {os.path.getsize(OUTPUT)/1024/1024:.1f} MB")
        sys.exit(0)
except Exception as e:
    print(f"gdown FAILED: {e}")

print("\nAll download methods failed.")
print("Please manually download the FairFace weights and place them at:")
print(f"  {OUTPUT}")
sys.exit(1)
