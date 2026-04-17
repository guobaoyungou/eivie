#!/usr/bin/env python3
"""Test all required imports"""
try:
    import torch
    print("torch=" + torch.__version__)
except Exception as e:
    print("torch FAILED: " + str(e))

try:
    import torchvision
    print("torchvision=" + torchvision.__version__)
except Exception as e:
    print("torchvision FAILED: " + str(e))

try:
    import insightface
    print("insightface=" + insightface.__version__)
except Exception as e:
    print("insightface FAILED: " + str(e))

try:
    import onnxruntime
    print("onnxruntime=" + onnxruntime.__version__)
except Exception as e:
    print("onnxruntime FAILED: " + str(e))

try:
    import fastapi
    print("fastapi=" + fastapi.__version__)
except Exception as e:
    print("fastapi FAILED: " + str(e))

try:
    import uvicorn
    print("uvicorn OK")
except Exception as e:
    print("uvicorn FAILED: " + str(e))

try:
    import PIL
    print("Pillow OK")
except Exception as e:
    print("Pillow FAILED: " + str(e))

try:
    import numpy
    print("numpy=" + numpy.__version__)
except Exception as e:
    print("numpy FAILED: " + str(e))

print("\nAll dependency checks complete.")
