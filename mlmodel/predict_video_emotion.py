from pathlib import Path
import pickle
import sys

import pandas as pd

from video_features import extract_video_features, resolve_path, video_feature_names


MODEL_PATH = Path(__file__).resolve().with_name("video_emotion_model.pkl")


def predict_video_emotion(video_path):
    if not video_path:
        return "Not analyzed"

    resolved_path = resolve_path(video_path)
    if not resolved_path.exists() or not MODEL_PATH.exists():
        return "Not analyzed"

    with MODEL_PATH.open("rb") as model_file:
        model = pickle.load(model_file)

    features = pd.DataFrame([extract_video_features(resolved_path)], columns=video_feature_names())
    return str(model.predict(features)[0])


def main():
    video_path = " ".join(sys.argv[1:])
    print(predict_video_emotion(video_path))


if __name__ == "__main__":
    main()
