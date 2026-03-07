<template>
  <div class="home-view">
    <el-row :gutter="20">
      <!-- 设备状态卡片 -->
      <el-col :span="8">
        <el-card class="status-card">
          <template #header>
            <div class="card-header">
              <el-icon><Monitor /></el-icon>
              <span>设备状态</span>
            </div>
          </template>
          <div class="card-content">
            <div class="status-item">
              <span class="label">设备名称：</span>
              <span class="value">未注册</span>
            </div>
            <div class="status-item">
              <span class="label">在线状态：</span>
              <el-tag type="info" size="small">离线</el-tag>
            </div>
            <div class="status-item">
              <span class="label">最后心跳：</span>
              <span class="value">--</span>
            </div>
          </div>
        </el-card>
      </el-col>

      <!-- 监控状态卡片 -->
      <el-col :span="8">
        <el-card class="status-card">
          <template #header>
            <div class="card-header">
              <el-icon><FolderOpened /></el-icon>
              <span>监控状态</span>
            </div>
          </template>
          <div class="card-content">
            <div class="status-item">
              <span class="label">监控文件夹：</span>
              <span class="value">0 个</span>
            </div>
            <div class="status-item">
              <span class="label">监控状态：</span>
              <el-tag type="info" size="small">未启动</el-tag>
            </div>
            <div class="status-item">
              <span class="label">检测文件：</span>
              <span class="value">0 个</span>
            </div>
          </div>
        </el-card>
      </el-col>

      <!-- 上传状态卡片 -->
      <el-col :span="8">
        <el-card class="status-card">
          <template #header>
            <div class="card-header">
              <el-icon><Upload /></el-icon>
              <span>上传状态</span>
            </div>
          </template>
          <div class="card-content">
            <div class="status-item">
              <span class="label">队列长度：</span>
              <span class="value">0</span>
            </div>
            <div class="status-item">
              <span class="label">成功/失败：</span>
              <span class="value">0 / 0</span>
            </div>
            <div class="status-item">
              <span class="label">当前速度：</span>
              <span class="value">0 KB/s</span>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 统计信息 -->
    <el-card class="statistics-card" style="margin-top: 20px;">
      <template #header>
        <div class="card-header">
          <el-icon><DataAnalysis /></el-icon>
          <span>今日统计</span>
        </div>
      </template>
      <el-row :gutter="20">
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-label">上传文件数</div>
            <div class="stat-value">0</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-label">上传流量</div>
            <div class="stat-value">0 MB</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-label">平均速度</div>
            <div class="stat-value">0 KB/s</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-label">成功率</div>
            <div class="stat-value">0%</div>
          </div>
        </el-col>
      </el-row>
    </el-card>

    <!-- 快捷操作 -->
    <el-card class="actions-card" style="margin-top: 20px;">
      <template #header>
        <div class="card-header">
          <el-icon><Operation /></el-icon>
          <span>快捷操作</span>
        </div>
      </template>
      <div class="actions-buttons">
        <el-button type="primary" :icon="VideoPlay">启动监控</el-button>
        <el-button :icon="VideoPause">停止监控</el-button>
        <el-button :icon="Setting">打开设置</el-button>
        <el-button :icon="Document">查看日志</el-button>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { Monitor, FolderOpened, Upload, DataAnalysis, Operation, VideoPlay, VideoPause, Setting, Document } from '@element-plus/icons-vue'
</script>

<style scoped>
.home-view {
  width: 100%;
}

.status-card {
  height: 200px;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
}

.card-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.status-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.status-item .label {
  color: var(--el-text-color-secondary);
  font-size: 14px;
}

.status-item .value {
  color: var(--el-text-color-primary);
  font-size: 14px;
  font-weight: 500;
}

.stat-item {
  text-align: center;
  padding: 20px 0;
}

.stat-label {
  color: var(--el-text-color-secondary);
  font-size: 14px;
  margin-bottom: 8px;
}

.stat-value {
  color: var(--el-color-primary);
  font-size: 28px;
  font-weight: bold;
}

.actions-buttons {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}
</style>
