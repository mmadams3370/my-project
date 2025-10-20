from flask import Flask, request, jsonify
import pandas as pd
from statsmodels.tsa.holtwinters import ExponentialSmoothing

app = Flask(__name__)

sales_data = None
model = None

@app.route('/train', methods=['POST'])
def train():
    global model, sales_data
    file = request.files['file']
    sales_data = pd.read_csv(file)
    ts = sales_data['quantity']
    model = ExponentialSmoothing(ts, trend='add', seasonal=None).fit()
    return jsonify({'message': 'Model trained'})

@app.route('/forecast', methods=['GET'])
def forecast():
    global model
    if model is None:
        return jsonify({'error': 'Model not trained'})
    preds = model.forecast(7).tolist()
    return jsonify({'forecast': preds})

if __name__ == '__main__':
    app.run(port=5000)
