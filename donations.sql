-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- 主機： localhost:8889
-- 產生時間： 2026-01-30 07:49:15
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
(55, 1, 5566, '2026-01-29 18:35:04', NULL, '信用卡', '單次捐款', '2601291834458806'),
(60, 1, 5000, '2026-01-29 19:40:46', NULL, 'LINE_PAY', '單次捐款', '2026012902335025210'),
(62, 1, 8787, '2026-01-30 11:12:40', 134, '信用卡', '定期定額', '2601301112169265'),
(63, 1, 9898, '2026-01-30 11:16:31', NULL, 'LINE_PAY', '單次捐款', '2026013002335076510'),
(64, 1, 5151, '2026-01-30 11:17:33', NULL, '信用卡', '單次捐款', '2601301117109271');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`DONATION_ID`),
  ADD KEY `fk_donation_subscription` (`SUBSCRIPTION_ID`),
  ADD KEY `MEMBER_ID` (`MEMBER_ID`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `donations`
--
ALTER TABLE `donations`
  MODIFY `DONATION_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '捐款編號', AUTO_INCREMENT=65;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`MEMBER_ID`) REFERENCES `members` (`member_id`),
  ADD CONSTRAINT `fk_donation_subscription` FOREIGN KEY (`SUBSCRIPTION_ID`) REFERENCES `subscription` (`SUBSCRIPTION_ID`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
