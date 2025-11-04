<?php
include 'api_db_config.php';

$conn = getDbConnection();

$method = $_SERVER['REQUEST_METHOD'];
// PUT, POST, DELETE 요청 본문 데이터 파싱
$data = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET':
        // 방명록 목록 조회
        $sql = "SELECT NO, NAME, MESSAGE, PASSWORD, CREATED_AT, DELETED FROM guestbook_entries ORDER BY CREATED_AT DESC";
        $result = $conn->query($sql);
        $entries = [];
        if ($result) {
            while($row = $result->fetch_assoc()) {
                $entries[] = $row;
            }
            echo json_encode(["success" => true, "entries" => $entries]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "조회 실패: " . $conn->error]);
        }
        break;

    case 'POST':
        // 방명록 작성
        $name = $conn->real_escape_string($data['name']);
        $message = $conn->real_escape_string($data['message']);
        $password = $conn->real_escape_string($data['password']);

        if (strlen($password) !== 4 || !ctype_digit($password)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "비밀번호는 4자리 숫자여야 합니다."]);
            break;
        }

        $stmt = $conn->prepare("INSERT INTO guestbook_entries (NAME, MESSAGE, PASSWORD, DELETED) VALUES (?, ?, ?, 'N')");
        $stmt->bind_param("sss", $name, $message, $password);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "방명록이 등록되었습니다."]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "등록 실패: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
        // 방명록 삭제 (논리적 삭제)
        $no = (int)$data['no'];
        $action = $data['action'];

        if ($action === 'delete') {
            // 삭제 플래그 'Y'로 변경
            $stmt = $conn->prepare("UPDATE guestbook_entries SET DELETED = 'Y' WHERE NO = ?");
            $stmt->bind_param("i", $no);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "방명록이 삭제되었습니다."]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "삭제 실패: " . $conn->error]);
            }
            $stmt->close();
        }
        break;
}

$conn->close();
?>
