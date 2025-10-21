#!/usr/bin/env python3
import sys
try:
    import cv2
    import numpy as np
    print("OK")
    sys.exit(0)
except ImportError as e:
    print(f"ERROR: {e}")
    sys.exit(1)