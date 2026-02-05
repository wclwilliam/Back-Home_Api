<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year = $_POST['data_year'] ?? '';
    $file = $_FILES['csv_file'] ?? null;

    if (!$year || !$file) {
        echo json_encode(["status" => "error", "message" => "缺少必要資料"]);
        exit();
    }

    try {//判斷年分有沒有重複
        $sql = "SELECT COUNT(*) FROM `IMPACT_METRICS` WHERE `DATA_YEAR` = ?";
        $checkStmt = $pdo->prepare($sql);
        $checkStmt->execute([$year]);

        // 使用 fetchColumn() 直接取得 COUNT(*) 的數值
        $count = $checkStmt->fetchColumn();
        if ($count > 0) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "資料年分不可重複"
            ]);
            exit();
        }
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "錯誤：" . $e->getMessage()
        ]);
        exit();
    }

    // 開啟並讀取 CSV
    if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
        $headers = fgetcsv($handle); // 讀取第一行(標頭)
        $values = fgetcsv($handle);  // 讀取第二行(數據)
        fclose($handle);

        // 將標頭與數據結合為關聯陣列
        $row = array_combine($headers, $values);

        try {
            $sql = "INSERT INTO `IMPACT_METRICS` 
                    (`DATA_YEAR`, `UPLOAD_DATE`, `TURTLES_IN_REHAB`, `TURTLES_RELEASED`, `HATCHLINGS_GUIDED`, 
                     `COASTLINE_PATROLLED`, `MEDICAL_SURGERIES`, `TOTAL_WASTE`, `PET_BOTTLES`, `IRON_CANS`, 
                     `ALUMINUM_CANS`, `WASTE_PAPER`, `GLASS_BOTTLES`, `STYROFOAM`, `BAMBOO_WOOD`, 
                     `FISHING_GEAR`, `UNSORTED_WASTE`) 
                    VALUES (:year, NOW(), :t_rehab, :t_released, :h_guided, :patrolled, :surgeries, :total, 
                            :pet, :iron, :alu, :paper, :glass, :styro, :wood, :gear, :unsorted)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':year' => $year,
                ':t_rehab' => $row['TURTLES_IN_REHAB'],
                ':t_released' => $row['TURTLES_RELEASED'],
                ':h_guided' => $row['HATCHLINGS_GUIDED'],
                ':patrolled' => $row['COASTLINE_PATROLLED'],
                ':surgeries' => $row['MEDICAL_SURGERIES'],
                ':total' => $row['TOTAL_WASTE'],
                ':pet' => $row['PET_BOTTLES'],
                ':iron' => $row['IRON_CANS'],
                ':alu' => $row['ALUMINUM_CANS'],
                ':paper' => $row['WASTE_PAPER'],
                ':glass' => $row['GLASS_BOTTLES'],
                ':styro' => $row['STYROFOAM'],
                ':wood' => $row['BAMBOO_WOOD'],
                ':gear' => $row['FISHING_GEAR'],
                ':unsorted' => $row['UNSORTED_WASTE']
            ]);

            echo json_encode(["status" => "success", "message" => "數據已匯入"]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "寫入失敗: " . $e->getMessage()]);
        }
    }
}