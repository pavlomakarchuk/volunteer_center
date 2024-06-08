<?php
header('Content-Type: application/json');

try {
    // Підключення до бази даних
    $db = new SQLite3('../backend/volunteers.db');

    // Пошук найближчого четверга
    $nearest_thursday = date('Y-m-d', strtotime('next Thursday'));

    // Пошук даних для параметрів C, D, E, F, G, H, I, J, K
    $data = [];
    for ($i = 0; $i <= 8; $i++) {
        $date_thursday = date('Y-m-d', strtotime("next Thursday - $i week"));
        if ($i == 0) {
            $param = "C";
        } else {
            $param = chr(68 + $i - 1); // Переводимо 0 -> C, 1 -> D, ..., 7 -> K
        }
        $stmt = $db->prepare("SELECT COUNT(*) as count 
            FROM arrivals 
            WHERE next_date = :date_thursday 
            AND NOT EXISTS (
                SELECT 1 
                FROM arrivals as A2 
                WHERE A2.dovidka = arrivals.dovidka 
                AND A2.arrival_date > arrivals.arrival_date
            )");
        $stmt->bindValue(':date_thursday', $date_thursday, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if ($row) {
            $data[$param] = $row["count"];
        } else {
            $data[$param] = 0;
        }
    }

    // Debugging: Print the collected data
    error_log("Data collected for prediction: " . json_encode($data));

    // Виконання Python скрипту для прогнозування
    $json_data = json_encode($data);
    $escaped_json_data = escapeshellarg($json_data);
    $result = shell_exec("python ../python_scripts/predict.py $escaped_json_data");

    // Debugging: Print the result of the Python script execution
    error_log("Result from Python script: " . $result);

    if ($result) {
        $prediction = json_decode($result, true);
        if (isset($prediction['prediction'])) {
            echo json_encode(['success' => true, 'prediction' => $prediction['prediction'], 'parameters' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Не вдалося здійснити прогноз']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Помилка при виконанні скрипта']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Помилка підключення до бази даних: ' . $e->getMessage()]);
}
?>
