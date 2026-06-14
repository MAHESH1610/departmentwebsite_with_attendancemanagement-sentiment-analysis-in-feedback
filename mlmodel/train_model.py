from pathlib import Path
import pickle

import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score
from sklearn.model_selection import train_test_split


BASE_DIR = Path(__file__).resolve().parent
DATASET_PATH = BASE_DIR / "encoded_scholarship_dataset_changed.csv"
MODEL_PATH = BASE_DIR / "classifier.pkl"
FEATURE_COLUMNS = ["mark", "attendance", "backlogs", "family_income", "sports_or_not"]
TARGET_COLUMN = "approval"


def main():
    dataset = pd.read_csv(DATASET_PATH)
    x = dataset[FEATURE_COLUMNS]
    y = dataset[TARGET_COLUMN]

    x_train, x_test, y_train, y_test = train_test_split(
        x,
        y,
        test_size=0.2,
        random_state=42,
        stratify=y,
    )

    model = RandomForestClassifier(n_estimators=150, random_state=42)
    model.fit(x_train, y_train)

    predictions = model.predict(x_test)
    accuracy = accuracy_score(y_test, predictions)

    with MODEL_PATH.open("wb") as model_file:
        pickle.dump(model, model_file)

    print(f"Saved model to {MODEL_PATH}")
    print(f"Validation accuracy: {accuracy:.4f}")


if __name__ == "__main__":
    main()
