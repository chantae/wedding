<?php
// MariaDB 접속 정보 설정 (고객님 정보로 설정)
define('DB_SERVER', '222.122.39.40');
define('DB_USERNAME', 'cksxo3938');
define('DB_PASSWORD', 'wcx133156!');
define('DB_NAME', 'cksxo3938'); // 데이터베이스 이름

// 오류 보고 설정 (개발 환경에서만 사용 권장)
ini_set('display_errors', 1);
error_reporting(E_ALL);

function getDbConnection() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(["success" => false, "message" => "Database Connection Failed: " . $conn->connect_error]));
    }
    
    // 문자 인코딩 설정 (한글 깨짐 방지)
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// 응답을 JSON 형식으로 설정
header('Content-Type: application/json');
?>
