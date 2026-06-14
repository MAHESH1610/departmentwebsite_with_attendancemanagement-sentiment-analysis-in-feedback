from pathlib import Path
import math
import wave

import cv2
import numpy as np


EMOTIONS = {"angry", "disgust", "fear", "happy", "neutral", "sad", "surprise"}
VIDEO_EXTENSIONS = {".mp4", ".avi", ".mov", ".webm", ".mkv", ".mpeg", ".mpg"}
AUDIO_EXTENSIONS = {".wav"}


def resolve_path(path):
    path = Path(path)
    if path.is_absolute():
        return path

    return Path(__file__).resolve().parent.parent / path


def find_haar_cascade(name):
    cascade_path = Path(cv2.data.haarcascades) / name
    if cascade_path.exists():
        return str(cascade_path)

    return ""


FACE_CASCADE = cv2.CascadeClassifier(find_haar_cascade("haarcascade_frontalface_default.xml"))
SMILE_CASCADE = cv2.CascadeClassifier(find_haar_cascade("haarcascade_smile.xml"))


def video_feature_names():
    return [
        "frame_count_sampled",
        "avg_brightness",
        "std_brightness",
        "avg_saturation",
        "std_saturation",
        "avg_motion",
        "std_motion",
        "avg_face_count",
        "max_face_count",
        "avg_face_area_ratio",
        "avg_smile_count",
        "audio_rms",
        "audio_zero_crossing_rate",
        "audio_duration_seconds",
    ]


def extract_video_features(video_path, max_frames=40):
    video_path = resolve_path(video_path)
    capture = cv2.VideoCapture(str(video_path))

    if not capture.isOpened():
        raise ValueError(f"Cannot open video: {video_path}")

    total_frames = int(capture.get(cv2.CAP_PROP_FRAME_COUNT) or 0)
    step = max(total_frames // max_frames, 1)

    brightness_values = []
    saturation_values = []
    motion_values = []
    face_counts = []
    face_area_ratios = []
    smile_counts = []
    previous_gray = None
    sampled = 0
    frame_index = 0

    while sampled < max_frames:
        ok, frame = capture.read()
        if not ok:
            break

        if frame_index % step != 0:
            frame_index += 1
            continue

        sampled += 1
        frame_index += 1
        frame = cv2.resize(frame, (320, 240))
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        hsv = cv2.cvtColor(frame, cv2.COLOR_BGR2HSV)

        brightness_values.append(float(np.mean(gray)))
        saturation_values.append(float(np.mean(hsv[:, :, 1])))

        if previous_gray is not None:
            motion_values.append(float(np.mean(cv2.absdiff(gray, previous_gray))))
        previous_gray = gray

        faces = FACE_CASCADE.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(32, 32))
        face_counts.append(float(len(faces)))

        frame_area = frame.shape[0] * frame.shape[1]
        total_face_area = 0.0
        smile_total = 0.0

        for (x, y, w, h) in faces:
            total_face_area += (w * h) / frame_area
            roi_gray = gray[y:y + h, x:x + w]
            smiles = SMILE_CASCADE.detectMultiScale(roi_gray, scaleFactor=1.7, minNeighbors=20, minSize=(16, 16))
            smile_total += len(smiles)

        face_area_ratios.append(float(total_face_area))
        smile_counts.append(float(smile_total))

    capture.release()

    audio_features = extract_audio_features(find_audio_sidecar(video_path))

    return [
        float(sampled),
        safe_mean(brightness_values),
        safe_std(brightness_values),
        safe_mean(saturation_values),
        safe_std(saturation_values),
        safe_mean(motion_values),
        safe_std(motion_values),
        safe_mean(face_counts),
        max(face_counts) if face_counts else 0.0,
        safe_mean(face_area_ratios),
        safe_mean(smile_counts),
        audio_features["rms"],
        audio_features["zero_crossing_rate"],
        audio_features["duration_seconds"],
    ]


def find_audio_sidecar(video_path):
    for extension in AUDIO_EXTENSIONS:
        candidate = video_path.with_suffix(extension)
        if candidate.exists():
            return candidate

    return None


def extract_audio_features(audio_path):
    if audio_path is None or not Path(audio_path).exists():
        return {"rms": 0.0, "zero_crossing_rate": 0.0, "duration_seconds": 0.0}

    with wave.open(str(audio_path), "rb") as audio_file:
        frame_count = audio_file.getnframes()
        sample_rate = audio_file.getframerate()
        raw_frames = audio_file.readframes(frame_count)
        sample_width = audio_file.getsampwidth()

    if sample_width == 1:
        samples = np.frombuffer(raw_frames, dtype=np.uint8).astype(np.float32) - 128
    elif sample_width == 2:
        samples = np.frombuffer(raw_frames, dtype=np.int16).astype(np.float32)
    elif sample_width == 4:
        samples = np.frombuffer(raw_frames, dtype=np.int32).astype(np.float32)
    else:
        return {"rms": 0.0, "zero_crossing_rate": 0.0, "duration_seconds": 0.0}

    if len(samples) == 0:
        return {"rms": 0.0, "zero_crossing_rate": 0.0, "duration_seconds": 0.0}

    rms = float(math.sqrt(np.mean(np.square(samples))))
    zero_crossings = np.where(np.diff(np.signbit(samples)))[0]
    zero_crossing_rate = float(len(zero_crossings) / len(samples))
    duration = float(frame_count / sample_rate) if sample_rate else 0.0

    return {"rms": rms, "zero_crossing_rate": zero_crossing_rate, "duration_seconds": duration}


def safe_mean(values):
    return float(np.mean(values)) if values else 0.0


def safe_std(values):
    return float(np.std(values)) if values else 0.0
