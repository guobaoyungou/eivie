#!/usr/bin/env python3
"""多线程下载 PyTorch CPU wheel"""
import os
import sys
import time
import threading
import urllib.request

URL = "https://download.pytorch.org/whl/cpu/torchvision-0.23.0%2Bcpu-cp39-cp39-manylinux_2_28_x86_64.whl"
OUTPUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "torchvision-0.23.0+cpu-cp39-cp39-manylinux_2_28_x86_64.whl")
NUM_THREADS = 16

def get_file_size(url):
    req = urllib.request.Request(url, method='HEAD')
    req.add_header('User-Agent', 'Mozilla/5.0')
    resp = urllib.request.urlopen(req, timeout=30)
    return int(resp.headers['Content-Length'])

def download_chunk(url, start, end, output_path, chunk_id, progress):
    req = urllib.request.Request(url)
    req.add_header('User-Agent', 'Mozilla/5.0')
    req.add_header('Range', f'bytes={start}-{end}')
    try:
        resp = urllib.request.urlopen(req, timeout=120)
        data = resp.read()
        with open(output_path, 'r+b') as f:
            f.seek(start)
            f.write(data)
        progress[chunk_id] = len(data)
        print(f"  Chunk {chunk_id}: {len(data)/1024/1024:.1f} MB done")
    except Exception as e:
        print(f"  Chunk {chunk_id} error: {e}, retrying...")
        try:
            resp = urllib.request.urlopen(req, timeout=300)
            data = resp.read()
            with open(output_path, 'r+b') as f:
                f.seek(start)
                f.write(data)
            progress[chunk_id] = len(data)
            print(f"  Chunk {chunk_id}: {len(data)/1024/1024:.1f} MB done (retry)")
        except Exception as e2:
            print(f"  Chunk {chunk_id} FAILED: {e2}")
            progress[chunk_id] = -1

def main():
    print(f"Downloading: {URL}")
    print(f"Output: {OUTPUT}")
    
    try:
        file_size = get_file_size(URL)
    except Exception as e:
        print(f"Failed to get file size: {e}")
        sys.exit(1)
    
    print(f"File size: {file_size/1024/1024:.1f} MB")
    print(f"Using {NUM_THREADS} threads")
    
    # Create output file
    with open(OUTPUT, 'wb') as f:
        f.seek(file_size - 1)
        f.write(b'\0')
    
    chunk_size = file_size // NUM_THREADS
    progress = {}
    threads = []
    
    start_time = time.time()
    
    for i in range(NUM_THREADS):
        start = i * chunk_size
        end = (i + 1) * chunk_size - 1 if i < NUM_THREADS - 1 else file_size - 1
        t = threading.Thread(target=download_chunk, args=(URL, start, end, OUTPUT, i, progress))
        threads.append(t)
        t.start()
    
    for t in threads:
        t.join()
    
    elapsed = time.time() - start_time
    total_downloaded = sum(v for v in progress.values() if v > 0)
    failed = sum(1 for v in progress.values() if v <= 0)
    
    print(f"\nDownload complete in {elapsed:.1f}s")
    print(f"Downloaded: {total_downloaded/1024/1024:.1f} MB / {file_size/1024/1024:.1f} MB")
    print(f"Speed: {total_downloaded/elapsed/1024/1024:.1f} MB/s")
    
    if failed > 0:
        print(f"WARNING: {failed} chunks failed!")
        sys.exit(1)
    
    if total_downloaded != file_size:
        print(f"WARNING: Size mismatch! Expected {file_size}, got {total_downloaded}")
        sys.exit(1)
    
    print("SUCCESS! Wheel file ready for installation.")

if __name__ == "__main__":
    main()
