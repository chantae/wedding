<?php
include 'api_db_config.php';

$conn = getDbConnection();

$method = $_SERVER['REQUEST_METHOD'];
// PUT, POST, DELETE 요청 본문 데이터 파싱
$data = json_decode(file_get_contents("php://input"), true);

// 함수: 총 예약 인원 조회
function getTotalBusCount($conn) {
    // DELETED='N'인 항목만 합산
    $sql = "SELECT SUM(COUNT) as total FROM bus_reservations WHERE DELETED = 'N'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['total'];
    }
    return 0;
}

switch ($method) {
    case 'GET':
        // 예약 목록 및 총 인원 조회
        $sql = "SELECT NO, NAME, PHONE, COUNT, PASSWORD, DATE_FORMAT(CREATED_AT, '%Y-%m-%d') as DATE, TIME(CREATED_AT) as TIME, DELETED FROM bus_reservations ORDER BY CREATED_AT DESC";
        $result = $conn->query($sql);
        $reservations = [];
        if ($result) {
            while($row = $result->fetch_assoc()) {
                $reservations[] = $row;
            }
            echo json_encode(["success" => true, "reservations" => $reservations]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "조회 실패: " . $conn->error]);
        }
        break;

    case 'POST':
        // 예약 추가
        $name = $conn->real_escape_string($data['name']);
        $phone = $conn->real_escape_string($data['phone']);
        $count = (int)$data['count'];
        $password = $conn->real_escape_string($data['password']);
        
        $current_count = getTotalBusCount($conn);
        $max_capacity = 44;

        if (($current_count + $count) > $max_capacity) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "예약 가능 인원을 초과합니다."]);
            break;
        }
        
        if (strlen($password) !== 4 || !ctype_digit($password)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "비밀번호 형식이 올바르지 않습니다."]);
            break;
        }


        $stmt = $conn->prepare("INSERT INTO bus_reservations (NAME, PHONE, COUNT, PASSWORD, DELETED) VALUES (?, ?, ?, ?, 'N')");
        // 's' (string), 's' (string), 'i' (integer), 's' (string)
        $stmt->bind_param("ssis", $name, $phone, $count, $password); 
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "예약이 완료되었습니다."]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "예약 실패: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
        // 예약 취소 (논리적 삭제)
        $no = (int)$data['no'];
        $action = $data['action'];

        if ($action === 'cancel') {
            // 취소 플래그 'Y'로 변경
            $stmt = $conn->prepare("UPDATE bus_reservations SET DELETED = 'Y' WHERE NO = ?"); 
            $stmt->bind_param("i", $no);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "예약이 취소되었습니다."]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "취소 실패: " . $stmt->error]);
            }
            $stmt->close();
        }
        break;
        
    case 'DELETE':
        // 전체 삭제 (관리자용)
        $password = $data['password'];
        if ($password === '1331') {
            $sql = "DELETE FROM bus_reservations";
            if ($conn->query($sql)) {
                 echo json_encode(["success" => true, "message" => "모든 예약 정보가 삭제되었습니다."]);
            } else {
                 http_response_code(500);
                 echo json_encode(["success" => false, "message" => "전체 삭제 실패: " . $conn->error]);
            }
        } else {
             http_response_code(401); // Unauthorized
             echo json_encode(["success" => false, "message" => "관리자 비밀번호가 올바르지 않습니다."]);
        }
        break;
}

$conn->close();
?>
