#!/usr/bin/env python3
"""多线程下载 InsightFace buffalo_l 模型包"""
import os
import sys
import time
import zipfile
import threading
import urllib.request

# InsightFace buffalo_l 模型包
URL = "https://github.com/deepinsight/insightface/releases/download/v0.7/buffalo_l.zip"
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
OUTPUT_ZIP = os.path.join(SCRIPT_DIR, "models", "buffalo_l.zip")
EXTRACT_DIR = os.path.join(SCRIPT_DIR, "models", "insightface", "models")
NUM_THREADS = 16

def get_file_size(url):
    req = urllib.request.Request(url, method='HEAD')
    req.add_header('User-Agent', 'Mozilla/5.0')
    try:
        resp = urllib.request.urlopen(req, timeout=30)
        size = resp.headers.get('Content-Length')
        if size:
            return int(size)
    except Exception:
        pass
    # Try GET with Range to check if server supports it
    req = urllib.request.Request(url)
    req.add_header('User-Agent', 'Mozilla/5.0')
    req.add_header('Range', 'bytes=0-0')
    try:
        resp = urllib.request.urlopen(req, timeout=30)
        cr = resp.headers.get('Content-Range', '')
        if '/' in cr:
            return int(cr.split('/')[-1])
    except Exception:
        pass
    return None

def download_single(url, output_path):
    """单线程下载（当服务器不支持Range时）"""
    print("Using single-thread download...")
    req = urllib.request.Request(url)
    req.add_header('User-Agent', 'Mozilla/5.0')
    resp = urllib.request.urlopen(req, timeout=600)
    total = int(resp.headers.get('Content-Length', 0))
    downloaded = 0
    with open(output_path, 'wb') as f:
        while True:
            chunk = resp.read(1024 * 1024)  # 1MB chunks
            if not chunk:
                break
            f.write(chunk)
            downloaded += len(chunk)
            if total:
                pct = downloaded * 100 / total
                print(f"\r  Downloaded: {downloaded/1024/1024:.1f} / {total/1024/1024:.1f} MB ({pct:.0f}%)", end='', flush=True)
    print()
    return downloaded

def download_chunk(url, start, end, output_path, chunk_id, progress):
    req = urllib.request.Request(url)
    req.add_header('User-Agent', 'Mozilla/5.0')
    req.add_header('Range', f'bytes={start}-{end}')
    max_retries = 3
    for attempt in range(max_retries):
        try:
            resp = urllib.request.urlopen(req, timeout=300)
            data = resp.read()
            with open(output_path, 'r+b') as f:
                f.seek(start)
                f.write(data)
            progress[chunk_id] = len(data)
            print(f"  Chunk {chunk_id}: {len(data)/1024/1024:.1f} MB done")
            return
        except Exception as e:
            if attempt < max_retries - 1:
                print(f"  Chunk {chunk_id} attempt {attempt+1} failed: {e}, retrying...")
                time.sleep(2)
            else:
                print(f"  Chunk {chunk_id} FAILED after {max_retries} attempts: {e}")
                progress[chunk_id] = -1

def main():
    os.makedirs(os.path.dirname(OUTPUT_ZIP), exist_ok=True)
    os.makedirs(EXTRACT_DIR, exist_ok=True)

    # Check if already extracted
    target_dir = os.path.join(EXTRACT_DIR, "buffalo_l")
    if os.path.isdir(target_dir) and len(os.listdir(target_dir)) >= 4:
        print(f"buffalo_l models already exist at {target_dir}")
        print("Files:", os.listdir(target_dir))
        return

    print(f"Downloading: {URL}")
    print(f"Output: {OUTPUT_ZIP}")

    # GitHub redirects releases to a CDN, follow redirects
    try:
        req = urllib.request.Request(URL)
        req.add_header('User-Agent', 'Mozilla/5.0')
        resp = urllib.request.urlopen(req, timeout=30)
        final_url = resp.geturl()
        resp.close()
        if final_url != URL:
            print(f"Redirected to: {final_url}")
    except Exception as e:
        print(f"Error checking URL: {e}")
        final_url = URL

    file_size = get_file_size(final_url)
    
    start_time = time.time()
    
    if file_size and file_size > 1024 * 1024:
        print(f"File size: {file_size/1024/1024:.1f} MB")
        print(f"Using {NUM_THREADS} threads")

        # Create output file
        with open(OUTPUT_ZIP, 'wb') as f:
            f.seek(file_size - 1)
            f.write(b'\0')

        chunk_size = file_size // NUM_THREADS
        progress = {}
        threads = []

        for i in range(NUM_THREADS):
            start = i * chunk_size
            end = (i + 1) * chunk_size - 1 if i < NUM_THREADS - 1 else file_size - 1
            t = threading.Thread(target=download_chunk, args=(final_url, start, end, OUTPUT_ZIP, i, progress))
            threads.append(t)
            t.start()

        for t in threads:
            t.join()

        elapsed = time.time() - start_time
        total_downloaded = sum(v for v in progress.values() if v > 0)
        failed = sum(1 for v in progress.values() if v <= 0)

        print(f"\nDownload complete in {elapsed:.1f}s")
        print(f"Downloaded: {total_downloaded/1024/1024:.1f} MB / {file_size/1024/1024:.1f} MB")

        if failed > 0:
            print(f"WARNING: {failed} chunks failed, falling back to single-thread")
            os.remove(OUTPUT_ZIP)
            download_single(final_url, OUTPUT_ZIP)
    else:
        print("Cannot determine file size or server does not support Range, using single-thread")
        download_single(final_url, OUTPUT_ZIP)

    elapsed = time.time() - start_time
    actual_size = os.path.getsize(OUTPUT_ZIP)
    print(f"File saved: {actual_size/1024/1024:.1f} MB in {elapsed:.1f}s")

    # Extract
    print(f"\nExtracting to {EXTRACT_DIR}...")
    try:
        with zipfile.ZipFile(OUTPUT_ZIP, 'r') as zf:
            zf.extractall(EXTRACT_DIR)
        print("Extraction complete!")
        print("Files:", os.listdir(target_dir) if os.path.isdir(target_dir) else "NOT FOUND")
    except Exception as e:
        print(f"Extraction failed: {e}")
        sys.exit(1)

    # Cleanup zip
    os.remove(OUTPUT_ZIP)
    print("Cleaned up zip file.")
    print("SUCCESS!")

if __name__ == "__main__":
    main()
