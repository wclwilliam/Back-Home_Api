<?php
// 1. 載入 CORS 與資料庫連線
require_once("../common/cors.php");
require_once("../common/conn.php");

// 2. 設定回傳 JSON 格式
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 取得前端傳來的 JSON 資料
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // 檢查必要欄位 (ID)
    if (!isset($data['id']) || empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "缺少資料編號 (ID)"]);
        exit();
    }

    $id = intval($data['id']);

    try {
        // 3. 準備 SQL 更新語法 (對應資料庫扁平欄位)
        $sql = "UPDATE `IMPACT_METRICS` SET 
                `TURTLES_IN_REHAB` = :t_rehab,
                `HATCHLINGS_GUIDED` = :h_guided,
                `COASTLINE_PATROLLED` = :patrolled,
                `MEDICAL_SURGERIES` = :surgeries,
                `TURTLES_RELEASED` = :t_released,
                `PET_BOTTLES` = :pet,
                `IRON_CANS` = :iron,
                `ALUMINUM_CANS` = :alu,
                `WASTE_PAPER` = :paper,
                `GLASS_BOTTLES` = :glass,
                `STYROFOAM` = :styro,
                `BAMBOO_WOOD` = :wood,
                `FISHING_GEAR` = :gear,
                `UNSORTED_WASTE` = :unsorted,
                `UPLOAD_DATE` = NOW() 
                WHERE `IMPACT_METRICS_ID` = :id";

        $stmt = $pdo->prepare($sql);

        // 4. 綁定參數 (將前端巢狀資料拆解)
        $stmt->bindValue(':t_rehab', $data['core_metrics']['total_rescued_turtles'], PDO::PARAM_INT);
        $stmt->bindValue(':h_guided', $data['core_metrics']['hatchlings_guided_to_sea'], PDO::PARAM_INT);
        $stmt->bindValue(':patrolled', $data['core_metrics']['patrolled_coastline_km'], PDO::PARAM_INT);
        $stmt->bindValue(':surgeries', $data['core_metrics']['professional_medical_surgeries'], PDO::PARAM_INT);
        $stmt->bindValue(':t_released', $data['core_metrics']['turtles_released'], PDO::PARAM_INT);
        
        // 廢棄物部分使用 DECIMAL (浮點數)
        $stmt->bindValue(':pet', $data['ocean_debris_removed_kg']['plastic_bottles']);
        $stmt->bindValue(':iron', $data['ocean_debris_removed_kg']['iron_cans']);
        $stmt->bindValue(':alu', $data['ocean_debris_removed_kg']['aluminum_cans']);
        $stmt->bindValue(':paper', $data['ocean_debris_removed_kg']['waste_paper']);
        $stmt->bindValue(':glass', $data['ocean_debris_removed_kg']['glass_bottles']);
        $stmt->bindValue(':styro', $data['ocean_debris_removed_kg']['styrofoam']);
        $stmt->bindValue(':wood', $data['ocean_debris_removed_kg']['bamboo_wood']);
        $stmt->bindValue(':gear', $data['ocean_debris_removed_kg']['ghost_nets_fishing_gear']);
        $stmt->bindValue(':unsorted', $data['ocean_debris_removed_kg']['unclassifiable_waste']);
        
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();

        // 5. 回傳結果
        echo json_encode([
            "status" => "success",
            "message" => "年度 " . $data['year'] . " 的影響力數據已成功更新"
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "更新失敗：" . $e->getMessage()]);
    }

    $pdo = null;
    exit();
}

// 非 POST 請求處理
http_response_code(405);
echo json_encode(["status" => "error", "message" => "不允許的方法"]);
?>