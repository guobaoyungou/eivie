#!/usr/bin/env python3
"""Test the analyze API endpoint"""
import requests
import json
import time

API_BASE = "http://127.0.0.1:8867"

# Test with a publicly accessible image with a person
# Using a common test image URL
test_urls = [
    "https://upload.wikimedia.org/wikipedia/commons/thumb/a/a7/Camponotus_flavomarginatus_ant.jpg/320px-Camponotus_flavomarginatus_ant.jpg",
    "https://picsum.photos/id/1005/400/600",
]

for url in test_urls:
    print(f"Testing with image: {url[:80]}...")
    start = time.time()
    try:
        resp = requests.post(
            f"{API_BASE}/api/analyze",
            json={"image_url": url, "detect_body_type": True},
            timeout=30,
        )
        elapsed = time.time() - start
        result = resp.json()
        print(f"Status: {resp.status_code}, Time: {elapsed:.1f}s")
        print(json.dumps(result, indent=2, ensure_ascii=False))
    except Exception as e:
        print(f"Error: {e}")
    print()

print("Test complete.")
