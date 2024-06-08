<?php
session_start();


header('Content-Type: application/json');

// Увімкнення виведення помилок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Використовуйте абсолютний шлях, якщо відносний шлях не працює
    $db = new SQLite3('../backend/volunteers.db');
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to connect to the database: ' . $e->getMessage()]);
    exit();
}
// Перевірка логіна і пароля
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action']) && $_GET['action'] == 'login') {
    $valid_login = 'a';
    $valid_password = 'b';

    $login = isset($_POST['login']) ? $_POST['login'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Додатковий налагоджувальний вихід
    error_log("Received Login attempt: login = $login, password = $password");

    if ($login === $valid_login && $password === $valid_password) {
        $_SESSION['authenticated'] = true;
        error_log("Login successful, session authenticated set.");
        echo json_encode(['success' => true]);
    } else {
        error_log("Login failed: invalid credentials.");
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
    exit();
}
// Обробка запиту та надсилання новин на сторінку news.html
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_news') {
    $result = $db->query('SELECT title, date, content FROM news ORDER BY date DESC');
    $news = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $news[] = $row;
    }

    echo json_encode(['news' => $news]);
    exit();
}
// Перевірка наступної дати відвідування
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action']) && $_GET['action'] == 'check_next_date') {
    $dovidka = $_POST['dovidka'] ?? '';
    $response = ['success' => false];

    // Пошук останнього візиту
    $query = $db->prepare('SELECT next_date FROM arrivals WHERE dovidka = :dovidka ORDER BY arrival_date DESC LIMIT 1');
    $query->bindValue(':dovidka', $dovidka, SQLITE3_TEXT);
    $result = $query->execute();
    $arrival = $result->fetchArray(SQLITE3_ASSOC);

    if ($arrival) {
        $response['success'] = true;
        $response['next_date'] = $arrival['next_date'];
    }

    echo json_encode($response);
    exit();
}

// Перевірка авторизації для захищених даних
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    error_log("Unauthorized access attempt.");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}



// Перевірка існування таблиці VPO
$result = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='VPO'");
if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Table VPO does not exist']);
    exit();
}

// Додатковий налагоджувальний вихід для перевірки сесій
error_log("Session authenticated: " . (isset($_SESSION['authenticated']) ? 'true' : 'false'));





// Пошук переселенця
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action']) && $_GET['action'] == 'search_vpo') {
    $response = ['success' => false];

    $vpoName = $_POST['vpo_name'] ?? '';
    $dovidka = $_POST['dovidka'] ?? '';

    $query = '';
    if (!empty($vpoName)) {
        $query = $db->prepare('SELECT * FROM VPO WHERE vpo_name = :vpo_name');
        $query->bindValue(':vpo_name', $vpoName, SQLITE3_TEXT);
    } elseif (!empty($dovidka)) {
        $query = $db->prepare('SELECT * FROM VPO WHERE dovidka = :dovidka');
        $query->bindValue(':dovidka', $dovidka, SQLITE3_TEXT);
    }

    if ($query) {
        $result = $query->execute();
        $vpo = $result->fetchArray(SQLITE3_ASSOC);

        if ($vpo) {
            $response['success'] = true;
            $response['vpo'] = $vpo;

            // Пошук останнього візиту
            $arrivalQuery = $db->prepare('SELECT * FROM arrivals WHERE dovidka = :dovidka ORDER BY arrival_date DESC LIMIT 1');
            $arrivalQuery->bindValue(':dovidka', $vpo['dovidka'], SQLITE3_TEXT);
            $arrivalResult = $arrivalQuery->execute();
            $arrival = $arrivalResult->fetchArray(SQLITE3_ASSOC);

            if ($arrival) {
                $response['arrival'] = $arrival;
                $today = date('Y-m-d');
                $response['can_get_help'] = ($arrival['next_date'] <= $today);
                $response['next_date_default'] = date('Y-m-d', strtotime('next Thursday + 4 weeks', strtotime($today)));
            } else {
                $response['arrival'] = "Отримує вперше";
                $response['can_get_help'] = true;
                $response['next_date_default'] = date('Y-m-d', strtotime('next Thursday + 4 weeks'));
            }
        }
    }

    echo json_encode($response);
    exit();
}

// Запис даних про видачу допомоги
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action']) && $_GET['action'] == 'record_assistance') {
    $dovidka = $_POST['dovidka'] ?? '';
    $nextDate = $_POST['next_date'] ?? '';
    $arrivalDate = date('Y-m-d');

    $query = $db->prepare('INSERT INTO arrivals (dovidka, arrival_date, next_date) VALUES (:dovidka, :arrival_date, :next_date)');
    $query->bindValue(':dovidka', $dovidka, SQLITE3_TEXT);
    $query->bindValue(':arrival_date', $arrivalDate, SQLITE3_TEXT);
    $query->bindValue(':next_date', $nextDate, SQLITE3_TEXT);

    if ($query->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }

    exit();
}



// Оновлення новин
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action']) && $_GET['action'] == 'add_news') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $date = date('Y-m-d');

    $query = $db->prepare('INSERT INTO news (title, date, content) VALUES (:title, :date, :content)');
    $query->bindValue(':title', $title, SQLITE3_TEXT);
    $query->bindValue(':date', $date, SQLITE3_TEXT);
    $query->bindValue(':content', $content, SQLITE3_TEXT);

    if ($query->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }

    exit();
}



// Додавання нового переселенця
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action']) && $_GET['action'] == 'add_vpo') {
    $vpo_name = $_POST['vpo_name'] ?? '';
    $replacement_date = $_POST['replacement_date'] ?? '';
    $dovidka = $_POST['dovidka'] ?? '';
    $region = $_POST['region'] ?? '';
    $city = $_POST['city'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $address = $_POST['address'] ?? '';

    // Перевірка, чи переселенець з таким номером довідки вже існує
    $query = $db->prepare('SELECT COUNT(*) AS count FROM VPO WHERE dovidka = :dovidka');
    $query->bindValue(':dovidka', $dovidka, SQLITE3_TEXT);
    $result = $query->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if ($row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Переселенець з таким номером довідки вже існує']);
    } else {
        // Додавання нового переселенця
        $insert = $db->prepare('INSERT INTO VPO (vpo_name, replacement_date, dovidka, region, city, mobile, address) VALUES (:vpo_name, :replacement_date, :dovidka, :region, :city, :mobile, :address)');
        $insert->bindValue(':vpo_name', $vpo_name, SQLITE3_TEXT);
        $insert->bindValue(':replacement_date', $replacement_date, SQLITE3_TEXT);
        $insert->bindValue(':dovidka', $dovidka, SQLITE3_TEXT);
        $insert->bindValue(':region', $region, SQLITE3_TEXT);
        $insert->bindValue(':city', $city, SQLITE3_TEXT);
        $insert->bindValue(':mobile', $mobile, SQLITE3_TEXT);
        $insert->bindValue(':address', $address, SQLITE3_TEXT);

        if ($insert->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Сталася помилка при додаванні переселенця']);
        }
    }

    exit();
}

// Отримати статистику за роками
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_yearly_statistics') {
    $result = $db->query('SELECT strftime("%Y", arrival_date) AS year, COUNT(*) AS count FROM arrivals GROUP BY year ORDER BY year');
    $years = [];
    $counts = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $years[] = $row['year'];
        $counts[] = $row['count'];
    }

    echo json_encode(['labels' => $years, 'counts' => $counts]);
    exit();
}

// Отримати статистику за місяцями
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_monthly_statistics') {
    $result = $db->query('SELECT strftime("%Y-%m", arrival_date) AS month, COUNT(*) AS count FROM arrivals GROUP BY month ORDER BY month');
    $months = [];
    $counts = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $months[] = $row['month'];
        $counts[] = $row['count'];
    }

    echo json_encode(['labels' => $months, 'counts' => $counts]);
    exit();
}
// Update VPO information
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action']) && $_GET['action'] == 'update_vpo') {
    $vpoId = $_POST['vpo_id'] ?? '';
    $vpoName = $_POST['vpo_name'] ?? '';
    $replacementDate = $_POST['replacement_date'] ?? '';
    $dovidka = $_POST['dovidka'] ?? '';
    $region = $_POST['region'] ?? '';
    $city = $_POST['city'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $address = $_POST['address'] ?? '';

    if ($dovidka) {
        $query = $db->prepare('UPDATE VPO SET vpo_name = :vpo_name, replacement_date = :replacement_date, dovidka = :dovidka, region = :region, city = :city, mobile = :mobile, address = :address WHERE id = :id');
        $query->bindValue(':vpo_name', $vpoName, SQLITE3_TEXT);
        $query->bindValue(':replacement_date', $replacementDate, SQLITE3_TEXT);
        $query->bindValue(':dovidka', $dovidka, SQLITE3_TEXT);
        $query->bindValue(':region', $region, SQLITE3_TEXT);
        $query->bindValue(':city', $city, SQLITE3_TEXT);
        $query->bindValue(':mobile', $mobile, SQLITE3_TEXT);
        $query->bindValue(':address', $address, SQLITE3_TEXT);
        $query->bindValue(':id', $vpoId, SQLITE3_INTEGER);

        if ($query->execute()) {
            $updatedVPO = [
                'vpo_name' => $vpoName,
                'replacement_date' => $replacementDate,
                'dovidka' => $dovidka,
                'region' => $region,
                'city' => $city,
                'mobile' => $mobile,
                'address' => $address
            ];
            echo json_encode(['success' => true, 'updatedVPO' => $updatedVPO]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update VPO information']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    }
    exit();
}

?>