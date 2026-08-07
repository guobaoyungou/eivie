#!/bin/bash
# 诊断订单列表API问题

echo "======================================"
echo "订单列表API诊断"
echo "======================================"
echo ""

echo "1. 检查数据库连接..."
mysql -u guobaoyungou_cn -p'5ArfhRr9xzyScrF5' -h localhost guobaoyungou_cn -e "SELECT '连接成功' as status" 2>&1 | grep -v Warning

echo ""
echo "2. 检查用户1的订单数量..."
mysql -u guobaoyungou_cn -p'5ArfhRr9xzyScrF5' -h localhost guobaoyungou_cn -e "
SELECT 
  '商城订单' as type, COUNT(*) as count FROM ddwx_shop_order WHERE aid = 1 AND mid = 1
UNION ALL
SELECT 
  '选片订单' as type, COUNT(*) as count FROM ddwx_ai_travel_photo_order WHERE aid = 1 AND (uid = 1 OR openid = (SELECT mpopenid FROM ddwx_member WHERE id = 1))
" 2>&1 | grep -v Warning

echo ""
echo "3. 检查session表..."
mysql -u guobaoyungou_cn -p'5ArfhRr9xzyScrF5' -h localhost guobaoyungou_cn -e "
SELECT session_id, aid, mid, platform, FROM_UNIXTIME(login_time) as login_time 
FROM ddwx_session 
WHERE session_id = 'f8fc9caf821fa8d480379d9506a80c5e'
" 2>&1 | grep -v Warning

echo ""
echo "4. 检查最近的session记录..."
mysql -u guobaoyungou_cn -p'5ArfhRr9xzyScrF5' -h localhost guobaoyungou_cn -e "
SELECT session_id, aid, mid, platform, FROM_UNIXTIME(login_time) as login_time 
FROM ddwx_session 
WHERE mid = 1 
ORDER BY login_time DESC 
LIMIT 5
" 2>&1 | grep -v Warning

echo ""
echo "5. 测试API响应（无登录状态）..."
curl -s -X POST "https://ai.eivie.cn/?s=/ApiUnifiedOrder/orderlist&aid=1" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "st=all&pagenum=1&pernum=10" \
  | python3 -c "import sys, json; data = json.load(sys.stdin); print('Status:', data.get('status', 'N/A')); print('Msg:', data.get('msg', 'N/A')); print('Data count:', len(data.get('datalist', [])))" 2>/dev/null

echo ""
echo "======================================"
echo "诊断完成"
echo "======================================"
