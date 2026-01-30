-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- 主機： localhost:8889
-- 產生時間： 2026-01-30 07:50:00
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
(3, 2023, '2024-02-10 14:20:00', 'reports/financial_report_2023.png'),
(4, 2022, '2023-01-20 09:00:00', 'reports/financial_report_2022.png'),
(5, 2021, '2022-03-05 16:45:00', 'reports/financial_report_2021.png');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `financial_reports`
--
ALTER TABLE `financial_reports`
  ADD PRIMARY KEY (`FINANCIAL_REPORT_ID`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `financial_reports`
--
ALTER TABLE `financial_reports`
  MODIFY `FINANCIAL_REPORT_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '徵信資料編號', AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
