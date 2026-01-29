-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- 主機： localhost:8889
-- 產生時間： 2026-01-24 08:32:16
-- 伺服器版本： 5.7.24
-- PHP 版本： 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫: `backhome_db`
--

-- --------------------------------------------------------

--
-- 資料表結構 `impact_metrics`
--

CREATE TABLE IF NOT EXISTS `impact_metrics` (
  `IMPACT_METRICS_ID` int(11) NOT NULL COMMENT '我們的影響力編號',
  `DATA_YEAR` year(4) NOT NULL COMMENT '資料年份',
  `UPLOAD_DATE` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上傳時間',
  `TURTLES_IN_REHAB` int(11) NOT NULL DEFAULT '0' COMMENT '治療中海龜總數',
  `TURTLES_RELEASED` int(11) NOT NULL DEFAULT '0' COMMENT '治療中已野放海龜總數',
  `HATCHLINGS_GUIDED` int(11) NOT NULL DEFAULT '0' COMMENT '引導入海幼龜數量',
  `COASTLINE_PATROLLED` int(11) NOT NULL DEFAULT '0' COMMENT '巡邏海岸線公里數',
  `MEDICAL_SURGERIES` int(11) NOT NULL DEFAULT '0' COMMENT '專業醫療手術場次',
  `TOTAL_WASTE` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '廢棄物總計',
  `PET_BOTTLES` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '寶特瓶重量',
  `IRON_CANS` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '鐵罐重量',
  `ALUMINUM_CANS` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '鋁罐重量',
  `WASTE_PAPER` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '廢紙重量',
  `GLASS_BOTTLES` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '玻璃瓶重量',
  `STYROFOAM` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '保麗龍重量',
  `BAMBOO_WOOD` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '竹木重量',
  `FISHING_GEAR` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '廢漁具漁網重量',
  `UNSORTED_WASTE` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '無法分類廢棄物重量'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--
-- 傾印資料表的資料 `impact_metrics`
--

INSERT INTO `impact_metrics` (`IMPACT_METRICS_ID`, `DATA_YEAR`, `UPLOAD_DATE`, `TURTLES_IN_REHAB`, `TURTLES_RELEASED`, `HATCHLINGS_GUIDED`, `COASTLINE_PATROLLED`, `MEDICAL_SURGERIES`, `TOTAL_WASTE`, `PET_BOTTLES`, `IRON_CANS`, `ALUMINUM_CANS`, `WASTE_PAPER`, `GLASS_BOTTLES`, `STYROFOAM`, `BAMBOO_WOOD`, `FISHING_GEAR`, `UNSORTED_WASTE`) VALUES
(1, 2025, '2026-01-24 11:06:29', 988, 0, 624, 24816, 142, '42500.00', '8500.00', '1200.00', '950.00', '2100.00', '3400.00', '4200.00', '5800.00', '12500.00', '3850.00'),
(2, 2024, '2026-01-24 11:06:29', 856, 0, 551, 21500, 128, '34800.00', '6800.00', '1100.00', '850.00', '1800.00', '2900.00', '3500.00', '4600.00', '10200.00', '3050.00'),
(3, 2023, '2026-01-24 11:06:29', 720, 0, 492, 18200, 95, '28500.00', '5400.00', '950.00', '720.00', '1400.00', '2200.00', '2800.00', '3900.00', '8500.00', '2630.00'),
(4, 2022, '2026-01-24 11:06:29', 642, 0, 438, 16500, 87, '25280.00', '4800.00', '850.00', '650.00', '1250.00', '1950.00', '2400.00', '3500.00', '7600.00', '2280.00'),
(5, 2021, '2026-01-24 11:06:29', 578, 0, 395, 14800, 76, '22290.00', '4200.00', '760.00', '580.00', '1100.00', '1700.00', '2100.00', '3100.00', '6800.00', '1950.00'),
(6, 2020, '2026-01-24 11:06:29', 485, 0, 342, 12600, 64, '19530.00', '3650.00', '680.00', '520.00', '980.00', '1480.00', '1850.00', '2750.00', '5900.00', '1720.00'),
(7, 2019, '2026-01-24 11:06:29', 412, 0, 298, 10900, 55, '16960.00', '3100.00', '590.00', '460.00', '850.00', '1280.00', '1600.00', '2400.00', '5200.00', '1480.00'),
(8, 2018, '2026-01-24 11:06:29', 356, 0, 264, 9500, 47, '14810.00', '2650.00', '520.00', '410.00', '740.00', '1120.00', '1380.00', '2100.00', '4600.00', '1290.00'),
(9, 2017, '2026-01-24 11:06:29', 298, 0, 225, 8200, 38, '12530.00', '2180.00', '450.00', '360.00', '630.00', '950.00', '1180.00', '1800.00', '3900.00', '1080.00'),
(10, 2016, '2026-01-24 11:06:29', 242, 0, 186, 7100, 31, '10800.00', '1850.00', '390.00', '310.00', '540.00', '820.00', '1020.00', '1550.00', '3400.00', '920.00'),
(11, 2015, '2026-01-24 11:06:29', 198, 0, 152, 6200, 26, '9350.00', '1580.00', '340.00', '270.00', '470.00', '710.00', '890.00', '1350.00', '2950.00', '790.00'),
(12, 2014, '2026-01-24 11:06:29', 162, 0, 124, 5400, 21, '8130.00', '1350.00', '295.00', '235.00', '410.00', '620.00', '780.00', '1180.00', '2580.00', '680.00');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `impact_metrics`
--
ALTER TABLE `impact_metrics`
  ADD PRIMARY KEY (`IMPACT_METRICS_ID`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `impact_metrics`
--
ALTER TABLE `impact_metrics`
  MODIFY `IMPACT_METRICS_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '我們的影響力編號', AUTO_INCREMENT=13;
COMMIT;

--
-- 資料表結構 `admin_user`
--

CREATE TABLE `admin_user` (
  `admin_id` VARCHAR(20) NOT NULL COMMENT '管理者帳號 / ID',
  `admin_name` VARCHAR(50) NOT NULL COMMENT '管理者姓名',
  `admin_pwd` VARCHAR(100) NOT NULL COMMENT '管理者登入密碼（雜湊）',
  `admin_role` VARCHAR(10) NOT NULL COMMENT '管理者角色權限',
  `admin_active` BOOLEAN NOT NULL DEFAULT 1 COMMENT '管理者帳號啟用狀態',
  `admin_created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '管理者帳號建立時間',
  `admin_last_login_time` DATETIME NULL COMMENT '管理者最後登入時間',
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='後台管理者';

--
-- 傾印資料表的資料 `admin_user`
--

INSERT INTO `admin_user`
(`admin_id`, `admin_name`, `admin_pwd`, `admin_role`)
VALUES
('admin001', '系統管理員', '$2y$10$n.vPvLKvr9cYYQK.T7qHAOvsqi1Z19DPy/pDNSdwkS/qjb5kXqufW', 'super');

--
-- 資料表結構 `members`
--

CREATE TABLE `members` (
  `member_id` INT NOT NULL AUTO_INCREMENT COMMENT '會員識別編號',
  `member_realname` VARCHAR(50) NOT NULL COMMENT '會員姓名',
  `member_email` VARCHAR(100) NOT NULL COMMENT '會員 Email（登入帳號）',
  `member_password` VARCHAR(255) NOT NULL COMMENT '會員登入密碼（bcrypt 雜湊）',
  `member_phone` VARCHAR(20) DEFAULT NULL COMMENT '聯絡電話',
  `id_number` VARCHAR(10) DEFAULT NULL COMMENT '身分證字號',
  `birthday` DATE DEFAULT NULL COMMENT '出生年月日',
  `emergency` VARCHAR(20) DEFAULT NULL COMMENT '緊急聯絡人',
  `emergency_tel` VARCHAR(20) DEFAULT NULL COMMENT '緊急聯絡電話',
  `email_verified_at` DATETIME DEFAULT NULL COMMENT 'Email 驗證成功時間',
  `member_active` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '會員啟用狀態（0未啟用/1啟用）',
  `member_created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '會員建立時間',
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `uk_members_email` (`member_email`)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COMMENT='前台會員資料';

--
-- 傾印資料表的資料 `members`
--

INSERT INTO `members`
(
  `member_realname`,
  `member_email`,
  `member_password`
)
VALUES
(
  '王小明',
  'test01@example.com',
  '$2b$10$abcdefghijklmnopqrstuv1234567890abcdefghijklmn'
);

--
-- 資料表結構 `member_email_verification`
--

CREATE TABLE `member_email_verification` (
  `verification_id` INT NOT NULL AUTO_INCREMENT COMMENT '驗證流水號',
  `member_id` INT NOT NULL COMMENT '對應會員識別編號',
  `code_hash` VARCHAR(255) NOT NULL COMMENT '驗證碼雜湊（不要存明碼）',
  `expires_at` DATETIME NOT NULL COMMENT '過期時間',
  `verified_at` DATETIME DEFAULT NULL COMMENT '驗證成功時間',
  `used_at` DATETIME DEFAULT NULL COMMENT '註冊使用時間',
  `attempts` INT NOT NULL DEFAULT 0 COMMENT '輸入錯誤次數',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  PRIMARY KEY (`verification_id`),
  CONSTRAINT `fk_verification_member`
    FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COMMENT='會員註冊 Email 驗證碼';

--
-- 資料表結構 `RESCUES`
--

CREATE TABLE `RESCUES` (
  `RESCUE_ID` int(11) NOT NULL COMMENT '海龜救援編號',
  `UPLOAD_DATE` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上傳時間',
  `TURTLE_NAME` varchar(50) NOT NULL COMMENT '海龜姓名',
  `SPECIES` varchar(10) NOT NULL COMMENT '品種',
  `LOCATION` varchar(20) NOT NULL COMMENT '發現地點',
  `STORY_CONTENT` varchar(200) NOT NULL COMMENT '受傷原因與故事文案',
  `RECOVERY_STATUS` varchar(10) NOT NULL COMMENT '目前救治階段',
  `IMAGE_PATH` varchar(255) NOT NULL COMMENT '照片路徑'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='海龜救援';


--
-- 已傾印資料表的索引
--
ALTER TABLE `RESCUES`
  ADD PRIMARY KEY (`RESCUE_ID`);

--
-- 傾印資料表的資料 `RESCUES`
-- 更新說明：RECOVERY_STATUS 已從數字改為對應的中文狀態
--

INSERT INTO `RESCUES` (`RESCUE_ID`, `UPLOAD_DATE`, `TURTLE_NAME`, `SPECIES`, `LOCATION`, `STORY_CONTENT`, `RECOVERY_STATUS`, `IMAGE_PATH`) VALUES
(1, NOW(), '波波', '綠蠵龜', '小琉球美人洞', '波波被發現時漂浮在海面上無法下潛，經X光檢查發現腸道內充滿大量塑膠垃圾，導致氣體堆積。目前正在進行排便治療與點滴輸液，精神狀況稍有起色，但仍需密切觀察排便狀況。志工們每天都期待能看到牠順利排出更多塑膠。', '醫療照護', 'savedcases/bobo.png'),
(2, NOW(), '琥珀', '玳瑁', '台東三仙台沿岸', '民眾通報發現琥珀擱淺在礁岩區，背甲有明顯的船槳螺旋槳切割傷，傷口深可見骨。剛送抵中心，獸醫團隊正在進行傷口清創與細菌採樣，並評估是否需要進行緊急縫合手術。這兩週將是觀察感染是否擴散的關鍵危險期。', '入院檢查', 'savedcases/amber.png'),
(3, NOW(), '抹茶', '綠蠵龜', '墾丁白沙灣', '抹茶因誤食魚鉤導致食道受傷，經過內視鏡手術成功取出魚鉤後，食道傷口癒合良好。目前已開始嘗試主動攝食，食慾不錯，喜歡吃新鮮的海藻，體重正在穩定回升中。偶爾牠還會追著水槽裡的蔬菜到處跑呢。', '休養觀察', 'savedcases/matcha.png'),
(4, NOW(), '海海', '綠蠵龜', '澎湖望安沙灘', '海海是去年底被廢棄漁網纏繞的前肢截肢個體。經過半年的復健，牠已經完全適應了三肢游泳的生活，泳速與潛水能力都達到野放標準。獸醫評估健康無虞，預計下週裝上衛星發報器後野放。大家雖有不捨，但也祝福牠能在大海自在遨遊。', '準備野放', 'savedcases/haihai.png'),
(5, NOW(), '點點', '玳瑁', '宜蘭外澳沙灘', '點點被發現時體型消瘦，且背甲上附著大量藤壺，顯示已長期活動力低下。血液檢查顯示有嚴重貧血與脫水現象，目前安置在加護水槽中，每日給予高營養針劑治療。今天早上牠終於有力氣稍微抬起頭換氣了。', '醫療照護', 'savedcases/dot.png'),
(6, NOW(), '勇士', '綠蠵龜', '新北貢寮', '勇士曾經因肺炎導致無法潛水，經過三個月的抗生素治療與隔離照護，肺部陰影完全消失。於昨日在志工與獸醫的見證下，在發現地順利重返大海，瞬間消失在浪花中。看著那堅定的背影，所有努力都值得了。', '重返大海', 'savedcases/warrior.png'),
(7, NOW(), '可可', '玳瑁', '蘭嶼朗島', '可可是一隻體型嬌小的幼龜，被發現時卡在消波塊縫隙中。外觀無明顯外傷，但有輕微營養不良。經過兩週的調養，現在精神奕奕，看到照顧員會激動地拍水討食。活潑可愛的模樣，成為了中心裡的開心果。', '休養觀察', 'savedcases/coco.png'),
(8, NOW(), '阿草', '綠蠵龜', '苗栗後龍海灘', '阿草在退潮時被困在淺灘，無法自行回到海中。初步外觀檢查發現右後肢有腫脹情形，懷疑是舊傷感染或骨折。目前暫時安置在觀察池，等待詳細的血液報告與影像檢查結果。牠靜靜趴在池底，似乎知道我們正在幫牠。', '入院檢查', 'savedcases/grass.png'),
(9, NOW(), '大丸子', '綠蠵龜', '屏東後壁湖', '大丸子誤入定置網，雖被漁民即時救起，但因長時間無法浮出水面換氣而有輕微嗆水現象，導致肺部感染。目前正在接受霧化治療，呼吸雜音已逐漸減少。每次做完治療後，牠都會舒服地瞇起眼睛休息。', '醫療照護', 'savedcases/ball.png'),
(10, NOW(), '金金', '玳瑁', '花蓮七星潭', '金金因背甲疑似遭受撞擊而有裂痕，所幸未傷及內臟。經過修補手術後，背甲裂縫已用醫療級樹脂固定。目前傷口乾燥無感染，正在淺水池中練習負重游泳，以防背甲癒合不正。雖然游得還不快，但牠非常努力練習平衡。', '休養觀察', 'savedcases/gold.png');

-- ==========================================
--  志工活動模組 (Volunteer Activities Module)
--  包含：種類、活動主表、收藏、留言、留言按讚、留言檢舉
-- ==========================================

-- 為了避免匯入時因順序問題報錯，先暫時忽略外來鍵檢查
SET FOREIGN_KEY_CHECKS = 0;

-- ========================================================
--  志工活動模組 (修正版)
-- ========================================================

-- --------------------------------------------------------
-- 1. 活動種類 (ACTIVITY_CATEGORIES)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ACTIVITY_CATEGORIES` (
  `CATEGORY_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '活動種類編號 (PK)',
  `CATEGORY_VALUE` varchar(50) NOT NULL COMMENT '活動種類名稱',
  PRIMARY KEY (`CATEGORY_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 2. 志工活動主表 (ACTIVITIES)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ACTIVITIES` (
  `ACTIVITY_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '活動編號 (PK)',
  `ACTIVITY_TITLE` varchar(100) NOT NULL COMMENT '活動標題',
  `ACTIVITY_CATEGORY_ID` int(11) NOT NULL COMMENT '活動類別編號 (FK)',
  `ACTIVITY_DESCRIPTION` text NOT NULL COMMENT '活動簡介',
  `ACTIVITY_NOTES` text COMMENT '注意事項',
  `ACTIVITY_LOCATION` varchar(100) NOT NULL COMMENT '活動地點',
  `ACTIVITY_LOCATION_AREA` varchar(20) NOT NULL COMMENT '活動區域(北部/中部/南部/離島)',
  `ACTIVITY_COVER_IMAGE` varchar(255) NOT NULL COMMENT '封面圖片路徑',
  `ACTIVITY_START_DATETIME` datetime NOT NULL COMMENT '活動開始時間',
  `ACTIVITY_END_DATETIME` datetime NOT NULL COMMENT '活動結束時間',
  `ACTIVITY_MAX_PEOPLE` int(11) NOT NULL COMMENT '人數上限',
  `ACTIVITY_SIGNUP_PEOPLE` int(11) NOT NULL DEFAULT '0' COMMENT '已報名人數',
  `ACTIVITY_SIGNUP_START_DATETIME` datetime NOT NULL COMMENT '報名開始時間',
  `ACTIVITY_SIGNUP_END_DATETIME` datetime NOT NULL COMMENT '報名截止時間',
  `ACTIVITY_STATUS` tinyint(4) NOT NULL DEFAULT '0' COMMENT '狀態 (0:草稿, 1:發布, 2:取消)',
  `ADMIN_ID` varchar(20) NOT NULL COMMENT '發布管理者ID', 
  `ACTIVITY_CREATED_AT` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  PRIMARY KEY (`ACTIVITY_ID`),
  FOREIGN KEY (`ACTIVITY_CATEGORY_ID`) REFERENCES `ACTIVITY_CATEGORIES` (`CATEGORY_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 3. 活動收藏 (FAVORITES)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `FAVORITES` (
    `MEMBER_ID` INT NOT NULL COMMENT '會員ID',
    `ACTIVITY_ID` INT NOT NULL COMMENT '活動ID',
    `CREATED_AT` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`MEMBER_ID`, `ACTIVITY_ID`),
    FOREIGN KEY (`ACTIVITY_ID`) REFERENCES `ACTIVITIES`(`ACTIVITY_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 4. 心得留言 (REVIEWS)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `REVIEWS` (
  `REVIEW_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '心得編號',
  `USER_ID` int(11) NOT NULL COMMENT '會員編號',
  `ACTIVITY_ID` int(11) NOT NULL COMMENT '活動編號',
  `RATING` tinyint(4) NOT NULL DEFAULT 5 COMMENT '評分(1-5)',
  `CONTENT` text NOT NULL COMMENT '心得內容',
  `IS_VISIBLE` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否顯示(1:顯示, 0:隱藏)',
  `LIKE` int(11) NOT NULL DEFAULT 0 COMMENT '按讚數',
  `CREATED_AT` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`REVIEW_ID`),
  FOREIGN KEY (`ACTIVITY_ID`) REFERENCES `ACTIVITIES` (`ACTIVITY_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 5. 留言按讚紀錄 (REVIEW_LIKES)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `REVIEW_LIKES` (
    `REVIEW_ID` INT NOT NULL,
    `USER_ID` INT NOT NULL,
    `CREATED_AT` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`REVIEW_ID`, `USER_ID`),
    FOREIGN KEY (`REVIEW_ID`) REFERENCES `REVIEWS`(`REVIEW_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 6. 留言檢舉 (REVIEW_REPORTS)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `REVIEW_REPORTS` (
    `REPORT_ID` INT NOT NULL AUTO_INCREMENT,
    `REVIEW_ID` INT NOT NULL,
    `USER_ID` INT NOT NULL COMMENT '檢舉人ID',
    `REPORT_REASON` VARCHAR(255) NOT NULL COMMENT '檢舉原因',
    `CREATED_AT` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`REPORT_ID`),
    FOREIGN KEY (`REVIEW_ID`) REFERENCES `REVIEWS`(`REVIEW_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
--  寫入預設測試資料 (Seeding)
-- ==========================================

-- 1. 活動種類
INSERT INTO `ACTIVITY_CATEGORIES` (`CATEGORY_ID`, `CATEGORY_VALUE`) VALUES
(1, '淨灘'),
(2, '照護'),
(3, '巡守');

-- 2. 活動主表
INSERT INTO `ACTIVITIES` 
(`ACTIVITY_TITLE`, `ACTIVITY_CATEGORY_ID`, `ACTIVITY_DESCRIPTION`, `ACTIVITY_NOTES`, `ACTIVITY_LOCATION`, `ACTIVITY_LOCATION_AREA`, `ACTIVITY_COVER_IMAGE`, `ACTIVITY_START_DATETIME`, `ACTIVITY_END_DATETIME`, `ACTIVITY_MAX_PEOPLE`, `ACTIVITY_SIGNUP_PEOPLE`, `ACTIVITY_SIGNUP_START_DATETIME`, `ACTIVITY_SIGNUP_END_DATETIME`, `ACTIVITY_STATUS`, `ADMIN_ID`) 
VALUES
(
    '小琉球夏季淨灘大作戰', 
    1, 
    '夏天到了，讓我們一起捲起袖子，還給海龜一個乾淨的產卵沙灘！活動當天提供免費午餐與保險。', 
    '1. 請自備環保杯與防曬用品。\n2. 建議穿著包覆性鞋類，避免受傷。', 
    '小琉球中澳沙灘', 
    '離島', 
    'activity_01.jpg', 
    '2025-08-20 09:00:00', '2025-08-20 12:00:00', 
    50, 12, 
    '2025-07-01 00:00:00', '2025-08-15 23:59:59', 
    1, 'admin01'
),
(
    '海龜救傷志工培訓營', 
    2, 
    '想要深入了解如何救援受傷海龜嗎？本課程邀請專業獸醫進行授課，機會難得！', 
    '需年滿18歲方可報名。', 
    '海洋大學', 
    '北部', 
    'activity_02.jpg', 
    '2024-12-10 13:00:00', '2024-12-10 17:00:00', 
    30, 30, 
    '2024-11-01 00:00:00', '2024-11-30 23:59:59', 
    1, 'admin01'
);

-- 3. 心得留言 (注意：LIKE 加上了反引號)
INSERT INTO `REVIEWS` (`USER_ID`, `ACTIVITY_ID`, `RATING`, `CONTENT`, `LIKE`, `CREATED_AT`) VALUES
(1, 2, 5, '獸醫講得非常詳細，學到很多急救知識！', 3, '2024-12-11 10:00:00'),
(2, 2, 4, '希望能有更多實作的機會。', 0, '2024-12-11 14:30:00');

-- 4. 收藏
INSERT INTO `FAVORITES` (`MEMBER_ID`, `ACTIVITY_ID`) VALUES (1, 1);

-- 5. 留言按讚
INSERT INTO `REVIEW_LIKES` (`REVIEW_ID`, `USER_ID`) VALUES (1, 3);


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;



SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
--
-- 資料表結構 `NEWS`
--

CREATE TABLE `NEWS` (
  `NEWS_ID` int(11) NOT NULL COMMENT '文章編號',
  `ADMIN_ID` varchar(20) NOT NULL COMMENT '發布管理者帳號/ID',
  `NEWS_TITLE` varchar(100) NOT NULL COMMENT '文章標題',
  `NEWS_CATEGORY` varchar(50) NOT NULL COMMENT '文章分類',
  `NEWS_PUBLISHED_AT` datetime NOT NULL COMMENT '發布時間',
  `NEWS_CONTENT` longtext NOT NULL COMMENT '文章內容',
  `NEWS_IMAGE_PATH` varchar(255) NOT NULL COMMENT '文章圖片',
  `NEWS_STATUS` varchar(10) NOT NULL COMMENT '文章狀態'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='最新消息';

--
-- 傾印資料表的資料 `NEWS`
--

INSERT INTO `NEWS` (`NEWS_ID`, `ADMIN_ID`, `NEWS_TITLE`, `NEWS_CATEGORY`, `NEWS_PUBLISHED_AT`, `NEWS_CONTENT`, `NEWS_IMAGE_PATH`, `NEWS_STATUS`) VALUES
(1, 'editor_mark', '還給海龜乾淨的家:2026 小琉球春季大型淨灘招募啟動', '重要公告', '2026-01-25 14:30:00', '春暖花開,正是海龜準備洄游的季節。邀請您與我們一同前往小琉球,清除沙灘廢棄物,迎接海龜媽媽回家。', 'news/news1.png', 'published'),
(2, 'editor_lisa', '【緊急異動】因強烈颱風接近,本週六「海龜生態講座」延期', '異動通知', '2026-01-20 10:00:00', '考量志工與學員安全,原定本週六於基隆舉辦的生態講座將延期至下個月,詳細退費或保留資格辦法請見內文。', 'news/news2.jpg', 'published'),
(3, 'editor_sarah', '好消息!重傷綠蠵龜「安安」康復,將於本週日進行野放', '重要公告', '2026-01-18 16:20:00', '經過獸醫團隊長達半年的細心照料,誤食塑膠袋的綠蠵龜「安安」終於恢復健康,誠摯邀請大家到場給予祝福。', 'news/news3.png', 'published'),
(4, 'admin_alex', '守護海洋安全第一:【龜途】2025 綠蠵龜棲地守護淨灘活動延期通知', '重要公告', '2026-01-16 09:00:00', '親愛的用戶您好,本平台將於下週六凌晨進行例行性系統維護,預計暫停服務 4 小時,造成不便敬請見諒。', 'news/news4.png', 'published'),
(5, 'editor_mark', '【資安升級】會員系統更新說明,請盡快重設您的密碼', '重要公告', '2026-01-15 08:00:00', '為了加強個人資料保護,我們已全面升級會員加密系統。請所有會員登入後依照指示重設密碼,以確保帳戶安全。', 'news/news5.jpg', 'published'),
(6, 'admin_system', '【系統維護】網站伺服器將於 2026/02/01 暫停服務兩小時', '重要公告', '2026-01-14 09:00:00', '為了提供更穩定的連線品質,本站將於 2026 年 2 月 1 日凌晨 02:00 至 04:00 進行伺服器維護,期間將暫停所有報名與捐款功能。', 'news/news6.jpg', 'published'),
(7, 'editor_lisa', '【名額釋出】澎湖望安巡守隊追加 5 名志工,欲報從速', '異動通知', '2026-01-12 11:45:00', '由於部分志工行程異動,我們釋出了 5 個寶貴的名額!這是一次難得的機會,額滿後系統將自動關閉報名。', 'news/news7.png', 'published'),
(8, 'editor_sarah', '海廢藝術展「塑縛」將於駁二特區盛大開幕', '重要公告', '2026-01-10 13:15:00', '由 10 位藝術家利用淨灘撿拾的海洋廢棄物創作,展覽將於本週末在駁二藝術特區登場,歡迎免費參觀。', 'news/news8.jpg', 'published'),
(9, 'admin_system', '【服務暫停】線上捐款功能將於 1/8 上午暫停維護', '異動通知', '2026-01-05 09:00:00', '配合金流服務商系統維護,本站將於 1/8 上午 10:00 至 12:00 暫停信用卡捐款服務,造成不便敬請見諒。', 'news/news9.png', 'published'),
(10, 'editor_mark', '2025 年度海龜保育成果報告書正式上線', '重要公告', '2026-01-01 10:00:00', '感謝大家過去一年的支持!2025 年我們共救援了 12 隻海龜,並清除了超過 3 噸的海廢。詳細數據請下載完整報告。', 'news/news10.png', 'published'),
(11, 'editor_lisa', '【取消公告】1/20 北海岸淨灘活動因豪雨特報取消', '異動通知', '2025-12-28 15:30:00', '氣象局發布豪雨特報,且沿海風浪過大,為顧及參與者安全,原定本週日的北海岸淨灘活動確定取消,不另擇期。', 'news/news11.jpg', 'published'),
(12, 'editor_sarah', '別讓吸管成為兇手!「減塑生活 30 天」挑戰賽開跑', '重要公告', '2025-12-20 12:00:00', '每天一個減塑小任務,連續 30 天,養成愛護海洋的好習慣。完成挑戰還有機會獲得限量環保餐具組!', 'news/news12.jpg', 'published'),
(13, 'editor_mark', '發現受傷海龜怎麼辦?「海龜救援 123」標準流程教學', '重要公告', '2025-12-15 14:00:00', '在海邊發現擱淺或受傷的海龜時,千萬不要急著把牠推回海裡!請記住這三個步驟:1. 撥打 118、2. 記錄地點、3. 保濕覆蓋。', 'news/news13.png', 'published'),
(14, 'editor_lisa', '【報名額滿】2026 寒假小小海龜保育員體驗營已截止', '異動通知', '2025-12-10 09:30:00', '感謝各位家長熱烈支持,寒假體驗營兩梯次皆已額滿。系統已關閉報名表單,若有釋出名額將另行公告。', 'news/news14.jpg', 'published'),
(15, 'admin_system', '網站新功能:即時海龜追蹤地圖 Beta 版上線', '重要公告', '2025-12-05 11:00:00', '想知道我們野放的海龜游到哪裡了嗎?全新的「海龜追蹤地圖」上線囉!快來看看「小海」是不是又游回小琉球了。', 'news/news15.jpg', 'published'),
(16, 'editor_sarah', '與海洋共生:在地漁民合作計畫啟動,推廣友善漁具', '重要公告', '2025-11-28 10:00:00', '為了減少海龜誤觸漁網溺斃的悲劇,我們與屏東在地漁會合作,推廣使用對海龜更友善的圓形魚鉤與逃脫裝置。', 'news/news16.jpg', 'published'),
(17, 'editor_mark', '【暫停服務】辦公室春節期間暫停行政服務通知', '異動通知', '2025-11-20 08:00:00', '本協會辦公室將於春節期間暫停行政服務,但海龜救援專線 24 小時仍有人員值班,若遇緊急狀況請直接撥打專線。', 'news/news17.jpg', 'published'),
(18, 'editor_lisa', '【活動改期】原定 11/15 墾丁淨灘活動順延一週', '異動通知', '2025-11-10 16:00:00', '因場地協調因素,墾丁後壁湖淨灘活動將順延至 11/22 舉行。已報名的志工無需重新報名,無法參加者請回信告知。', 'news/news18.png', 'published'),
(19, 'editor_sarah', '國際志工日特別企劃:聽資深保育員講故事', '重要公告', '2025-11-01 13:00:00', '他們在海邊守了 20 年,只為看著小海龜平安游向大海。國際志工日,來聽聽這些默默付出的無名英雄的故事。', 'news/news19.png', 'published'),
(20, 'admin_system', '【故障排除】圖片上傳失敗問題已修復完成', '重要公告', '2025-10-25 17:00:00', '稍早部分使用者反應無法上傳淨灘成果照片,工程團隊已修復此問題,目前功能已恢復正常,感謝您的耐心等候。', 'news/news20.jpg', 'published'),
(21, 'editor_mark', '學術新發現!台灣東部海域發現玳瑁海龜新覓食熱點', '重要公告', '2025-10-18 11:00:00', '海洋大學研究團隊透過衛星發報器追蹤,在台東外海發現極為罕見的玳瑁群聚覓食熱點,這對劃設保護區有重大意義。', 'news/news21.png', 'published'),
(22, 'editor_lisa', '募資計畫啟動:為海龜醫院添購新的 X 光機', '重要公告', '2025-10-10 10:00:00', '原本的設備已老舊不堪使用,我們需要您的力量,為海龜醫院添購數位 X 光機,讓受傷海龜能獲得更精準的診斷。', 'news/news22.jpg', 'published'),
(23, 'editor_sarah', '賞龜公約推廣:不觸摸、不追逐、不驚擾', '重要公告', '2025-10-01 14:00:00', '在水下遇見海龜好興奮?請保持 5 公尺以上距離,你的觸摸可能會讓海龜受到驚嚇甚至感染細菌。愛牠,請保持距離。', 'news/news23.png', 'published'),
(24, 'editor_mark', '【期限延長】2025 秋季淨灘活動報名延長三天', '異動通知', '2025-09-25 10:00:00', '回應大家的熱情,我們決定將報名截止時間延長至 9/28!還沒報名的朋友請把握最後機會,一起為海洋盡一份心力。', 'news/news24.jpg', 'published'),
(25, 'admin_system', '網站隱私權政策與 Cookie 條款更新通知', '重要公告', '2025-09-20 09:00:00', '我們更新了隱私權政策與 Cookie 使用條款,以符合最新的個資保護法規。繼續使用本網站即代表您同意新版條款。', 'news/news25.jpg', 'published'),
(26, 'editor_lisa', '中秋連假海邊烤肉?請記得「無痕海洋」原則', '重要公告', '2025-09-15 16:00:00', '中秋佳節在海邊賞月烤肉固然愜意,但請務必帶走所有垃圾與炭灰,避免流入海洋成為海龜的致命食物。', 'news/news26.jpg', 'published'),
(27, 'editor_sarah', '【影片】海龜視角看世界!GoPro 意外拍下的驚奇畫面', '重要公告', '2025-09-05 12:00:00', '潛水客遺落的相機意外記錄下了海龜一天的生活。從覓食到浮出水面換氣,帶你用第一人稱視角體驗海龜日常。', 'news/news27.jpg', 'published'),
(28, 'editor_mark', '【緊急招募】颱風過境急需 50 位志工清理宜蘭外澳沙灘', '異動通知', '2025-08-30 08:00:00', '颱風帶來大量海漂垃圾,若不盡快清理將被捲回海中。我們臨時發起緊急淨灘,本週六早上需要你的雙手支援!', 'news/news28.jpg', 'published'),
(29, 'editor_lisa', '【地點更換】親子環保 DIY 教室改至 B1 會議室舉行', '異動通知', '2025-08-20 10:00:00', '原定於一樓大廳舉辦的親子環保 DIY 活動,因場地設備檢修,將改至地下一樓 B1 會議室舉行,時間維持不變。', 'news/news29.png', 'published'),
(30, 'admin_system', '【系統公告】資料庫備份作業通知,系統將暫時停止營運', '重要公告', '2025-08-15 02:00:00', '系統將進行每月例行性資料庫備份,預計耗時 30 分鐘。作業期間網站瀏覽可能會有短暫延遲,不影響資料存取。', 'news/news30.jpg', 'published'),
(31, 'editor_sarah', '海洋污染成隱形殺手,研究顯示 8 成海龜體內含微塑膠', '重要公告', '2025-08-10 14:30:00', '一份最新的跨國研究指出,全球海洋中的海龜皆面臨微塑膠威脅,這將影響牠們的生殖能力與免疫系統。', 'news/news31.jpg', 'published');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `NEWS`
--
ALTER TABLE `NEWS`
  ADD PRIMARY KEY (`NEWS_ID`),
  ADD KEY `FK_ADMIN_ID` (`ADMIN_ID`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `NEWS`
--
ALTER TABLE `NEWS`
  MODIFY `NEWS_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '文章編號', AUTO_INCREMENT=32;
COMMIT;

--
-- 資料表結構 `donations`
--

CREATE TABLE `donations` (
  `DONATION_ID` int(11) NOT NULL COMMENT '捐款編號',
  `MEMBER_ID` int(11) NOT NULL COMMENT '會員識別編號',
  `AMOUNT` int(11) NOT NULL COMMENT '捐款金額',
  `DONATION_DATE` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '捐款時間',
  `SUBSCRIPTION_ID` int(11) DEFAULT NULL COMMENT '定期定額捐款編號',
  `PAYMENT_METHOD` varchar(20) NOT NULL COMMENT '金流類型(信用卡、LINE_PAY)',
  `DONATION_TYPE` varchar(20) NOT NULL COMMENT '捐款類型(單次捐款、定期定額)',
  `TRANSACTION_ID` varchar(100) NOT NULL COMMENT '交易編號'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 傾印資料表的資料 `donations`
--

INSERT INTO `donations` (`DONATION_ID`, `MEMBER_ID`, `AMOUNT`, `DONATION_DATE`, `SUBSCRIPTION_ID`, `PAYMENT_METHOD`, `DONATION_TYPE`, `TRANSACTION_ID`) VALUES
(1, 1, 1000, '2025-12-01 10:00:00', 101, '信用卡', '定期定額', 'TXN202512010001'),
(2, 2, 500, '2025-12-02 14:30:00', NULL, 'LINE_PAY', '單次捐款', 'TXN202512020002'),
(3, 3, 2000, '2025-12-03 09:15:00', NULL, '信用卡', '單次捐款', 'TXN202512030003'),
(4, 4, 300, '2025-12-05 18:20:00', 102, 'LINE_PAY', '定期定額', 'TXN202512050004'),
(5, 5, 5000, '2025-12-07 11:00:00', NULL, '信用卡', '單次捐款', 'TXN202512070005'),
(6, 1, 1000, '2026-01-01 10:00:00', 101, '信用卡', '定期定額', 'TXN202601010006'),
(7, 6, 1500, '2026-01-02 20:45:00', NULL, 'LINE_PAY', '單次捐款', 'TXN202601020007'),
(8, 7, 600, '2026-01-04 13:10:00', 103, '信用卡', '定期定額', 'TXN202601040008'),
(9, 8, 200, '2026-01-05 15:55:00', NULL, 'LINE_PAY', '單次捐款', 'TXN202601050009'),
(10, 9, 1200, '2026-01-06 17:30:00', NULL, '信用卡', '單次捐款', 'TXN202601060010'),
(11, 10, 800, '2026-01-08 12:00:00', 104, 'LINE_PAY', '定期定額', 'TXN202601080011'),
(12, 4, 300, '2026-01-05 18:20:00', 102, 'LINE_PAY', '定期定額', 'TXN202601050012'),
(13, 2, 100, '2026-01-10 22:15:00', NULL, 'LINE_PAY', '單次捐款', 'TXN202601100013'),
(14, 3, 2500, '2026-01-12 08:40:00', NULL, '信用卡', '單次捐款', 'TXN202601120014'),
(15, 5, 500, '2026-01-15 14:00:00', NULL, 'LINE_PAY', '單次捐款', 'TXN202601150015'),
(19, 10, 555, '2026-01-28 13:23:08', NULL, '信用卡', '單次捐款', '2601281322465864');

-- --------------------------------------------------------

--
-- 資料表結構 `financial_reports`
--

CREATE TABLE `financial_reports` (
  `FINANCIAL_REPORT_ID` int(11) NOT NULL COMMENT '徵信資料編號',
  `DATA_YEAR` year(4) NOT NULL COMMENT '資料年份',
  `UPLOAD_DATE` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上傳時間',
  `FILE_PATH` varchar(255) NOT NULL COMMENT '檔案路徑'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 傾印資料表的資料 `financial_reports`
--

INSERT INTO `financial_reports` (`FINANCIAL_REPORT_ID`, `DATA_YEAR`, `UPLOAD_DATE`, `FILE_PATH`) VALUES
(1, 2025, '2026-01-26 16:34:03', 'reports/financial_report_2025.png'),
(2, 2024, '2025-01-15 10:30:00', 'reports/financial_report_2024.png'),
(3, 2023, '2024-02-10 14:20:00', 'reports/financial_report_2023.png'),
(4, 2022, '2023-01-20 09:00:00', 'reports/financial_report_2022.png'),
(5, 2021, '2022-03-05 16:45:00', 'reports/financial_report_2021.png');

-- 
-- ------------------------------------------------------

--
-- 資料表結構 `subscription`
--

CREATE TABLE `subscription` (
  `SUBSCRIPTION_ID` int(11) NOT NULL COMMENT '定期定額捐款編號',
  `MEMBER_ID` int(11) NOT NULL COMMENT '會員識別編號',
  `AMOUNT` int(11) NOT NULL COMMENT '每期捐款金額',
  `START_DATE` date NOT NULL COMMENT '開始日期',
  `STATUS` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否正常扣款(1為正常，0為停止)',
  `END_DATE` date DEFAULT NULL COMMENT '結束日期'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 傾印資料表的資料 `subscription`
--

INSERT INTO `subscription` (`SUBSCRIPTION_ID`, `MEMBER_ID`, `AMOUNT`, `START_DATE`, `STATUS`, `END_DATE`) VALUES
(101, 1, 1000, '2025-01-01', 1, NULL),
(102, 4, 300, '2025-03-15', 1, NULL),
(103, 7, 600, '2024-06-01', 1, NULL),
(104, 10, 800, '2024-11-20', 1, NULL),
(105, 2, 500, '2023-01-01', 0, '2024-01-01'),
(106, 3, 2000, '2025-05-10', 1, NULL),
(107, 5, 1500, '2024-02-28', 1, NULL),
(108, 6, 200, '2024-08-15', 1, NULL),
(109, 8, 100, '2023-05-05', 0, '2023-11-05'),
(110, 9, 3000, '2025-09-01', 1, NULL),
(111, 11, 500, '2025-10-12', 1, NULL),
(112, 12, 1000, '2024-12-25', 1, NULL),
(113, 13, 300, '2024-04-01', 1, NULL),
(114, 14, 1200, '2023-07-20', 0, '2025-07-20'),
(115, 15, 2500, '2025-01-20', 1, NULL);

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`DONATION_ID`),
  ADD KEY `fk_donation_subscription` (`SUBSCRIPTION_ID`);

--
-- 資料表索引 `financial_reports`
--
ALTER TABLE `financial_reports`
  ADD PRIMARY KEY (`FINANCIAL_REPORT_ID`);


--
-- 資料表索引 `subscription`
--
ALTER TABLE `subscription`
  ADD PRIMARY KEY (`SUBSCRIPTION_ID`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `donations`
--
ALTER TABLE `donations`
  MODIFY `DONATION_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '捐款編號', AUTO_INCREMENT=20;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `financial_reports`
--
ALTER TABLE `financial_reports`
  MODIFY `FINANCIAL_REPORT_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '徵信資料編號', AUTO_INCREMENT=6;


--
-- 使用資料表自動遞增(AUTO_INCREMENT) `subscription`
--
ALTER TABLE `subscription`
  MODIFY `SUBSCRIPTION_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '定期定額捐款編號', AUTO_INCREMENT=116;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `fk_donation_subscription` FOREIGN KEY (`SUBSCRIPTION_ID`) REFERENCES `subscription` (`SUBSCRIPTION_ID`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;
