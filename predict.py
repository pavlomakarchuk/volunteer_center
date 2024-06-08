import sys
import json
import numpy as np
import pandas as pd
from sklearn.linear_model import LinearRegression
import joblib 

def parse_input_data(input_data):
    # Remove curly braces and split the string into key-value pairs
    data = input_data.strip('{}').split(', ')
    
    features = {}
    for item in data:
        key, value = item.split(':')
        key = key.strip()  # Remove any leading/trailing whitespace
        value = value.strip()  # Remove any leading/trailing whitespace
        features[key] = [float(value)]
    
    return features

# Запис діагностичної інформації
with open('../log.txt', 'w') as log_file:
    log_file.write('Starting script\n')
    log_file.write(f'Arguments: {sys.argv[1]}\n')

    try:
        # Завантаження моделі
        log_file.write('1\n')
        with open('../prediction_model.pkl', 'rb') as model_file:
            model = joblib.load(model_file)
        log_file.write('2\n')

        # Отримання даних з аргументів командного рядка
        input_data = sys.argv[1]
        log_file.write('3\n')

        # Формування вхідних даних для моделі
        features = parse_input_data(input_data)
        log_file.write('31\n')
        
        df = pd.DataFrame(features)
        log_file.write('4\n')

        # Здійснення прогнозу
        prediction = model.predict(df)
        if prediction[0] < 0:
            prediction[0] = float(features['C'][0])  # Assuming 'C' is used as fallback
        log_file.write('5\n')

        # Виведення результату
        result = {'prediction': round(prediction[0])}
        log_file.write('6\n')
        log_file.write(f'Result: {result}\n')
        log_file.write('7\n')
        print(json.dumps(result))
        log_file.write('8\n')

    except Exception as e:
        # Запис помилки в лог
        log_file.write(f'Error: {str(e)}\n')
        print(json.dumps({'prediction': None, 'error': str(e)}))
