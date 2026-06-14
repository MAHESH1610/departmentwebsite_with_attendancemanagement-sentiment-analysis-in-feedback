from pathlib import Path
import pickle

import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score, classification_report
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline


BASE_DIR = Path(__file__).resolve().parent
PROJECT_DIR = BASE_DIR.parent
DATASET_PATH = PROJECT_DIR / "emotion_dataset_10000.csv"
MODEL_PATH = BASE_DIR / "text_emotion_model.pkl"


def main():
    dataset = pd.read_csv(DATASET_PATH)
    dataset = dataset.dropna(subset=["text", "emotion"])

    x_train, x_test, y_train, y_test = train_test_split(
        dataset["text"],
        dataset["emotion"],
        test_size=0.2,
        random_state=42,
        stratify=dataset["emotion"],
    )

    model = Pipeline([
        ("tfidf", TfidfVectorizer(ngram_range=(1, 2), min_df=2)),
        ("classifier", LogisticRegression(max_iter=1000, class_weight="balanced")),
    ])

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
