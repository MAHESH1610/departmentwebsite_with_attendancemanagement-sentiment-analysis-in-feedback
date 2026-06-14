from pathlib import Path
import pickle
import sys


MODEL_PATH = Path(__file__).resolve().with_name("text_emotion_model.pkl")


def predict_emotion(text):
    if not text.strip():
        return "neutral"

    with MODEL_PATH.open("rb") as model_file:
        model = pickle.load(model_file)

    return str(model.predict([text])[0])


def main():
    text = " ".join(sys.argv[1:])
    print(predict_emotion(text))


if __name__ == "__main__":
    main()
