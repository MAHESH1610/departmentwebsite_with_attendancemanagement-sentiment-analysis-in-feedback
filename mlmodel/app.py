from http.server import BaseHTTPRequestHandler, HTTPServer
import json
from pathlib import Path
import pickle


MODEL_PATH = Path(__file__).with_name("classifier.pkl")
FEATURE_COLUMNS = ["mark", "attendance", "backlogs", "family_income", "sports_or_not"]
classifier = None
MODEL_LOAD_ERROR = None

try:
    with MODEL_PATH.open("rb") as model_file:
        classifier = pickle.load(model_file)
except (ImportError, ModuleNotFoundError, OSError) as exc:
    MODEL_LOAD_ERROR = exc


def predict_values(mark, attendance, backlogs, family_income, sports_or_not):
    mark = int(mark)
    attendance = int(attendance)
    backlogs = int(backlogs)
    family_income = int(family_income)
    sports_or_not = int(sports_or_not)

    if classifier is not None:
        features = [[mark, attendance, backlogs, family_income, sports_or_not]]
        prediction = classifier.predict(features)
        prediction_value = int(prediction[0])
    else:
        prediction_value = int(
            mark >= 80
            and attendance >= 85
            and backlogs == 0
            and family_income <= 300000
        )

    status = "Eligible" if prediction_value == 1 else "Not Eligible"

    return {"prediction": prediction_value, "status": status}


try:
    from fastapi import FastAPI
    from scholarship import Scholarship

    app = FastAPI()

    @app.post("/predict")
    def predict(data: Scholarship):
        values = data.dict()
        return predict_values(
            values["mark"],
            values["attendance"],
            values["backlogs"],
            values["family_income"],
            values["sports_or_not"],
        )
except ModuleNotFoundError:
    app = None


class PredictionHandler(BaseHTTPRequestHandler):
    def do_POST(self):
        if self.path != "/predict":
            self.send_json({"error": "Not found"}, 404)
            return

        content_length = int(self.headers.get("Content-Length", 0))
        raw_body = self.rfile.read(content_length)

        try:
            data = json.loads(raw_body.decode("utf-8"))
            result = predict_values(
                data["mark"],
                data["attendance"],
                data["backlogs"],
                data["family_income"],
                data["sports_or_not"],
            )
            self.send_json(result)
        except (KeyError, TypeError, ValueError, json.JSONDecodeError) as exc:
            self.send_json({"error": str(exc)}, 400)

    def log_message(self, format, *args):
        return

    def send_json(self, payload, status_code=200):
        response = json.dumps(payload).encode("utf-8")
        self.send_response(status_code)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(response)))
        self.end_headers()
        self.wfile.write(response)


if __name__ == "__main__":
    if MODEL_LOAD_ERROR is not None:
        print(f"Warning: classifier.pkl could not be loaded: {MODEL_LOAD_ERROR}")
        print("Using fallback scholarship eligibility rules.")

    server = HTTPServer(("127.0.0.1", 5001), PredictionHandler)
    print("Scholarship prediction service running at http://127.0.0.1:5001/predict")
    server.serve_forever()
