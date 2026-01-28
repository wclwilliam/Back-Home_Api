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