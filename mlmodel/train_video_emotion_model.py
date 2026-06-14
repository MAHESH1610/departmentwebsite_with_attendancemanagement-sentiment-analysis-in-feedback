from pathlib import Path
import pickle

import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score, classification_report
from sklearn.model_selection import train_test_split

from video_features import EMOTIONS, VIDEO_EXTENSIONS, extract_video_features, video_feature_names


BASE_DIR = Path(__file__).resolve().parent
PROJECT_DIR = BASE_DIR.parent
DATASET_CANDIDATES = [
    PROJECT_DIR / "vedio dataset",
    PROJECT_DIR / "vediodataset",
    PROJECT_DIR / "video dataset",
    PROJECT_DIR / "videodataset",
]
MODEL_PATH = BASE_DIR / "video_emotion_model.pkl"


def find_dataset_dir():
    for dataset_dir in DATASET_CANDIDATES:
        if dataset_dir.exists():
            return dataset_dir

    return DATASET_CANDIDATES[0]


def load_dataset_records(dataset_dir):
    metadata_candidates = [
        dataset_dir / "metadata.csv",
        dataset_dir / "labels.csv",
        dataset_dir / "vediodataset.csv",
        dataset_dir / "vedio dataset.csv",
        dataset_dir / "video_dataset.csv",
        PROJECT_DIR / "vediodataset.csv",
        PROJECT_DIR / "vedio dataset.csv",
        PROJECT_DIR / "video_dataset.csv",
    ]

    for metadata_path in metadata_candidates:
        if metadata_path.exists():
            return load_metadata_records(metadata_path, dataset_dir)

    return load_folder_records(dataset_dir)


def load_metadata_records(metadata_path, dataset_dir):
    data = pd.read_csv(metadata_path)
    path_column = next((column for column in ["video_path", "path", "file", "filename"] if column in data.columns), None)
    label_column = next((column for column in ["emotion", "label", "class"] if column in data.columns), None)

    if path_column is None or label_column is None:
        raise ValueError("Metadata CSV must contain a video path column and an emotion/label column.")

    records = []
    for _, row in data.iterrows():
        label = str(row[label_column]).strip().lower()
        if label not in EMOTIONS:
            continue

        video_path = Path(str(row[path_column]).strip())
        if not video_path.is_absolute():
            video_path = dataset_dir / video_path

        if video_path.exists() and video_path.suffix.lower() in VIDEO_EXTENSIONS:
            records.append((video_path, label))

    return records


def load_folder_records(dataset_dir):
    records = []
    if not dataset_dir.exists():
        return records

    for emotion_dir in dataset_dir.iterdir():
        if not emotion_dir.is_dir():
            continue

        label = emotion_dir.name.strip().lower()
        if label not in EMOTIONS:
            continue

        for video_path in emotion_dir.rglob("*"):
            if video_path.is_file() and video_path.suffix.lower() in VIDEO_EXTENSIONS:
                records.append((video_path, label))

    if records:
        return records

    for video_path in dataset_dir.rglob("*"):
        if not video_path.is_file() or video_path.suffix.lower() not in VIDEO_EXTENSIONS:
            continue

        file_label = extract_label_from_name(video_path.stem)
        if file_label:
            records.append((video_path, file_label))

    return records


def extract_label_from_name(name):
    normalized = name.lower()
    for emotion in EMOTIONS:
        if f"_{emotion}" in normalized or f"-{emotion}" in normalized or f" {emotion}" in normalized:
            return emotion

    return None


def main():
    dataset_dir = find_dataset_dir()
    records = load_dataset_records(dataset_dir)
    if not records:
        raise SystemExit(
            "No video dataset found. Add videos under 'vedio dataset/<emotion>/video.mp4' "
            "or create 'vedio dataset/metadata.csv' with video_path and emotion columns."
        )

    features = []
    labels = []

    for index, (video_path, label) in enumerate(records, start=1):
        print(f"[{index}/{len(records)}] Extracting features: {video_path}")
        try:
            features.append(extract_video_features(video_path))
            labels.append(label)
        except Exception as exc:
            print(f"Skipped {video_path}: {exc}")

    if len(set(labels)) < 2:
        raise SystemExit("Training needs videos from at least two emotion classes.")

    x = pd.DataFrame(features, columns=video_feature_names())
    y = pd.Series(labels)

    stratify = y if y.value_counts().min() >= 2 else None
    x_train, x_test, y_train, y_test = train_test_split(
        x,
        y,
        test_size=0.2,
        random_state=42,
        stratify=stratify,
    )

    model = RandomForestClassifier(n_estimators=200, random_state=42, class_weight="balanced")
    model.fit(x_train, y_train)

    predictions = model.predict(x_test)
    accuracy = accuracy_score(y_test, predictions)

    with MODEL_PATH.open("wb") as model_file:
        pickle.dump(model, model_file)

    print(f"Saved model to {MODEL_PATH}")
    print(f"Validation accuracy: {accuracy:.4f}")
    print(classification_report(y_test, predictions))


if __name__ == "__main__":
    main()
