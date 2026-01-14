import cv2
import sys
import os
import json
import numpy as np

def get_dominant_color(img, mask):
    # Calculate mean color in the masked area
    if mask is None:
        mean_val = cv2.mean(img)
    else:
        mean_val = cv2.mean(img, mask=mask)
    # mean_val is (B, G, R, Alpha)
    return "{:02x}{:02x}{:02x}".format(int(mean_val[2]), int(mean_val[1]), int(mean_val[0]))

def segment_image(image_path, output_dir):
    if not os.path.exists(output_dir):
        os.makedirs(output_dir)

    # Load image
    img = cv2.imread(image_path)
    if img is None:
        return json.dumps({"error": f"Could not read image at {image_path}"})

    # Resize if too large (speed up)
    height, width = img.shape[:2]
    max_dim = 1500
    scale = 1.0
    if max(height, width) > max_dim:
        scale = max_dim / max(height, width)
        img = cv2.resize(img, (0, 0), fx=scale, fy=scale)

    # Convert to grayscale
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

    # Blur to reduce noise
    blurred = cv2.GaussianBlur(gray, (5, 5), 0)

    # Canny Edge Detection
    # Thresholds: low_thresh, high_thresh. 
    # Use Otsu's thresholding on the blurred image to guess good values?
    # Or fixed values. 30, 100 is often a good starting point.
    edges = cv2.Canny(blurred, 30, 150)

    # Dilate edges to close gaps and connect contours
    # Using a larger kernel to merge nearby edges of the same part
    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (7, 7))
    dilated = cv2.dilate(edges, kernel, iterations=2)
    
    # Close operations to fill small holes
    closed = cv2.morphologyEx(dilated, cv2.MORPH_CLOSE, kernel, iterations=2)

    # Find contours
    contours, _ = cv2.findContours(closed, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    results = []
    margin = 10 
    min_area = 1000 * (scale * scale) # Adjust min area based on scale? Actually scale < 1 so area is smaller.
    # If we scaled down, the area is smaller. 
    # Let's say min_area 500 for 1500px image.
    real_min_area = 500

    for i, cnt in enumerate(contours):
        area = cv2.contourArea(cnt)
        if area < real_min_area:
            continue

        # Create mask for color extraction
        mask = np.zeros(gray.shape, np.uint8)
        cv2.drawContours(mask, [cnt], 0, 255, -1)

        # Get bounding rect
        x, y, w, h = cv2.boundingRect(cnt)
        
        # Color extraction
        dominant_color_hex = get_dominant_color(img, mask)

        # Add margin for crop image
        x_m = max(0, x - margin)
        y_m = max(0, y - margin)
        w_m = min(img.shape[1] - x_m, w + 2*margin)
        h_m = min(img.shape[0] - y_m, h + 2*margin)

        # Crop
        crop = img[y_m:y_m+h_m, x_m:x_m+w_m]
        
        # Save crop
        crop_filename = f"crop_{i}.jpg"
        crop_path = os.path.join(output_dir, crop_filename)
        cv2.imwrite(crop_path, crop)

        results.append({
            "path": crop_path,
            "x": x,
            "y": y,
            "w": w,
            "h": h,
            "area": area,
            "color_hex": dominant_color_hex
        })

    # If no contours found (e.g. only one part filling the image?), 
    # return the whole image as one result.
    if not results:
        crop_filename = "full_crop.jpg"
        crop_path = os.path.join(output_dir, crop_filename)
        cv2.imwrite(crop_path, img)
        results.append({
            "path": crop_path,
            "x": 0,
            "y": 0,
            "w": width,
            "h": height,
            "area": width * height,
            "color_hex": get_dominant_color(img, None)
        })

    return json.dumps(results)

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Usage: python segment_parts.py <image_path> <output_dir>"}))
        sys.exit(1)

    image_path = sys.argv[1]
    output_dir = sys.argv[2]
    
    try:
        print(segment_image(image_path, output_dir))
    except Exception as e:
        # Print error in JSON format so PHP can parse it
        print(json.dumps({"error": str(e)}))
