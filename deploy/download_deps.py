#!/usr/bin/env python3
"""Download PyTorch CPU wheels for offline installation."""
import urllib.request
import os
import sys

DOWNLOAD_DIR = "/home/fairface-server/wheels"
os.makedirs(DOWNLOAD_DIR, exist_ok=True)

WHEELS = [
    ("https://download.pytorch.org/whl/cpu/torch-2.8.0%2Bcpu-cp39-cp39-manylinux_2_28_x86_64.whl",
     "torch-2.8.0+cpu-cp39-cp39-manylinux_2_28_x86_64.whl"),
    ("https://download.pytorch.org/whl/cpu/torchvision-0.23.0%2Bcpu-cp39-cp39-manylinux_2_28_x86_64.whl",
     "torchvision-0.23.0+cpu-cp39-cp39-manylinux_2_28_x86_64.whl"),
]

for url, filename in WHEELS:
    filepath = os.path.join(DOWNLOAD_DIR, filename)
    if os.path.exists(filepath):
        size = os.path.getsize(filepath)
        print(f"Already exists: {filename} ({size} bytes), skipping")
        continue
    print(f"Downloading {filename} ...")
    try:
        urllib.request.urlretrieve(url, filepath)
        size = os.path.getsize(filepath)
        print(f"Done: {filename} ({size} bytes)")
    except Exception as e:
        print(f"Failed: {filename} - {e}")
        sys.exit(1)

print("All wheels downloaded successfully")
