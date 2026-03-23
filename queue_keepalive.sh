#!/bin/bash
# 队列worker守护脚本 - 检查worker是否运行，如果没有则启动
cd /home/www/ai.eivie.cn

# 检查 ai_image_generation 队列
if ! pgrep -f "queue:work --queue ai_image_generation" > /dev/null; then
    echo "[$(date)] Starting ai_image_generation worker..." >> /tmp/queue_keepalive.log
    nohup php think queue:work --queue ai_image_generation --tries 3 --timeout 300 >> /tmp/queue_ai_image.log 2>&1 &
fi

# 检查 ai_cutout 队列
if ! pgrep -f "queue:work --queue ai_cutout" > /dev/null; then
    echo "[$(date)] Starting ai_cutout worker..." >> /tmp/queue_keepalive.log
    nohup php think queue:work --queue ai_cutout --tries 3 --timeout 120 >> /tmp/queue_ai_cutout.log 2>&1 &
fi
