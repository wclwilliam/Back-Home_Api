<?php
  // 載入 CORS 設定
  require_once("../common/cors.php");
  
  // 載入資料庫連線設定 ($pdo)
  require_once("../common/conn.php");

  if($_SERVER['REQUEST_METHOD'] == "GET"){
    
    // 1. 取得 Query 參數中的 id
    $id = $_GET['id'] ?? null;

    try {
        if ($id) {
            // --- 情況 A：取得特定 ID 的單筆資料 ---
            $sql = "SELECT * FROM `IMPACT_METRICS` WHERE `IMPACT_METRICS_ID` = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "找不到該筆資料"]);
                exit();
            }
            // 轉化為與列表一致的格式
            $formatted_output = formatImpactRow($result);
        } else {
            // --- 情況 B：取得所有資料 (原本的邏輯) ---
            $sql = "SELECT * FROM `impact_metrics` ORDER BY `DATA_YEAR` DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $formatted_output = [];
            foreach ($raw_data as $row) {
                $formatted_output[] = formatImpactRow($row);
            }
        }

        // 輸出 JSON
        header('Content-Type: application/json');
        echo json_encode($formatted_output);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "資料庫錯誤：" . $e->getMessage()]);
    }
    
    $pdo = null;
    exit();
}

/**
 * 封裝格式轉換邏輯，確保單筆與多筆輸出結構相同
 */
function formatImpactRow($row) {
    return [
        "year" => (int)$row['DATA_YEAR'],
        "id" => (int)$row['IMPACT_METRICS_ID'],
        "upload_date" => $row['UPLOAD_DATE'],
        "core_metrics" => [
            "total_rescued_turtles" => (int)$row['TURTLES_IN_REHAB'],
            "hatchlings_guided_to_sea" => (int)$row['HATCHLINGS_GUIDED'],
            "patrolled_coastline_km" => (int)$row['COASTLINE_PATROLLED'],
            "professional_medical_surgeries" => (int)$row['MEDICAL_SURGERIES'],
            "turtles_released" => (int)$row['TURTLES_RELEASED'],
        ],
        "ocean_debris_removed_kg" => [
            "plastic_bottles" => (float)$row['PET_BOTTLES'],
            "iron_cans" => (float)$row['IRON_CANS'],
            "aluminum_cans" => (float)$row['ALUMINUM_CANS'],
            "waste_paper" => (float)$row['WASTE_PAPER'],
            "glass_bottles" => (float)$row['GLASS_BOTTLES'],
            "styrofoam" => (float)$row['STYROFOAM'],
            "bamboo_wood" => (float)$row['BAMBOO_WOOD'],
            "ghost_nets_fishing_gear" => (float)$row['FISHING_GEAR'],
            "unclassifiable_waste" => (float)$row['UNSORTED_WASTE']
        ]
    ];
}

// --- 錯誤處理 ---
http_response_code(403);
echo json_encode(["error" => "denied"]);
?>