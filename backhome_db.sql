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

CREATE TABLE `impact_metrics` (
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
-- 傾印資料表的資料 `admin_user`
--

INSERT INTO `admin_user`
(`admin_id`, `admin_name`, `admin_pwd`, `admin_role`)
VALUES
('admin001', '系統管理員', '$2y$10$n.vPvLKvr9cYYQK.T7qHAOvsqi1Z19DPy/pDNSdwkS/qjb5kXqufW', 'super');

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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
