<?php
  // 載入 CORS 設定
  require_once("./common/cors.php");
  
  // 載入資料庫連線設定 ($pdo)
  require_once("./common/conn.php");

  if($_SERVER['REQUEST_METHOD'] == "GET"){
    
    $sql = "SELECT * FROM `impact_metrics` ORDER BY 'data_year' DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // 1. 先抓取原始扁平資料
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. 準備一個新陣列來存放轉換後的格式
    $formatted_data = [];

    foreach ($raw_data as $row) {
    $formatted_data[] = [
        "year" => (int)$row['DATA_YEAR'],
        "core_metrics" => [
            "total_rescued_turtles" => (int)$row['TURTLES_IN_REHAB'],
            "hatchlings_guided_to_sea" => (int)$row['HATCHLINGS_GUIDED'],
            "patrolled_coastline_km" => (int)$row['COASTLINE_PATROLLED'],
            "professional_medical_surgeries" => (int)$row['MEDICAL_SURGERIES']
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
    
    // 3. 輸出轉換後的資料
    header('Content-Type: application/json'); // 確保瀏覽器知道這是 JSON
    echo json_encode($formatted_data);
    
    $pdo = null;
    exit();
  }

  // --- 錯誤處理 ---
  http_response_code(403);
  echo json_encode(["error" => "denied"]);
?>