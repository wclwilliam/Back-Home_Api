-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- 主機： localhost:8889
-- 產生時間： 2026-01-30 07:50:34
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
-- 資料表結構 `subscription`
--

CREATE TABLE `subscription` (
  `SUBSCRIPTION_ID` int(11) NOT NULL COMMENT '定期定額捐款編號',
  `MEMBER_ID` int(11) NOT NULL COMMENT '會員識別編號',
  `AMOUNT` int(11) NOT NULL COMMENT '每期捐款金額',
  `START_DATE` date NOT NULL COMMENT '開始日期',
  `STATUS` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否正常扣款(1為正常，0為停止)',
  `END_DATE` date DEFAULT NULL COMMENT '結束日期',
  `ORDER_ID` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '訂單編號(綠界查詢用)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 傾印資料表的資料 `subscription`
--

INSERT INTO `subscription` (`SUBSCRIPTION_ID`, `MEMBER_ID`, `AMOUNT`, `START_DATE`, `STATUS`, `END_DATE`, `ORDER_ID`) VALUES
(134, 1, 8787, '2026-01-30', 1, NULL, 'ORDER20260130111217');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `subscription`
--
ALTER TABLE `subscription`
  ADD PRIMARY KEY (`SUBSCRIPTION_ID`),
  ADD UNIQUE KEY `ORDER_ID` (`ORDER_ID`),
  ADD KEY `MEMBER_ID` (`MEMBER_ID`) USING BTREE;

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `subscription`
--
ALTER TABLE `subscription`
  MODIFY `SUBSCRIPTION_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '定期定額捐款編號', AUTO_INCREMENT=135;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `subscription`
--
ALTER TABLE `subscription`
  ADD CONSTRAINT `subscription_ibfk_1` FOREIGN KEY (`MEMBER_ID`) REFERENCES `members` (`member_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
