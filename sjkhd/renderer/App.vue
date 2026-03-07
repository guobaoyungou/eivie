<template>
  <div id="app" class="app-container">
    <el-container>
      <!-- 侧边栏导航 -->
      <el-aside width="200px" class="sidebar">
        <div class="logo">
          <h2>AI旅拍客户端</h2>
        </div>
        <el-menu
          :default-active="activeMenu"
          class="sidebar-menu"
          @select="handleMenuSelect"
        >
          <el-menu-item index="home">
            <el-icon><Monitor /></el-icon>
            <span>监控状态</span>
          </el-menu-item>
          <el-menu-item index="upload">
            <el-icon><Upload /></el-icon>
            <span>上传列表</span>
          </el-menu-item>
          <el-menu-item index="settings">
            <el-icon><Setting /></el-icon>
            <span>设置</span>
          </el-menu-item>
          <el-menu-item index="logs">
            <el-icon><Document /></el-icon>
            <span>日志</span>
          </el-menu-item>
          <el-menu-item index="about">
            <el-icon><InfoFilled /></el-icon>
            <span>关于</span>
          </el-menu-item>
        </el-menu>
      </el-aside>

      <!-- 主内容区 -->
      <el-container>
        <el-header class="header">
          <div class="header-title">{{ pageTitle }}</div>
          <div class="header-actions">
            <el-tag :type="deviceStatus.type" size="large">
              {{ deviceStatus.text }}
            </el-tag>
          </div>
        </el-header>

        <el-main class="main-content">
          <!-- 动态内容区域 -->
          <component :is="currentView" />
        </el-main>
      </el-container>
    </el-container>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, markRaw } from 'vue'
import { Monitor, Upload, Setting, Document, InfoFilled } from '@element-plus/icons-vue'
import HomeView from './views/Home.vue'

// 当前激活的菜单
const activeMenu = ref('home')

// 页面标题映射
const pageTitles: Record<string, string> = {
  home: '监控状态',
  upload: '上传列表',
  settings: '设置',
  logs: '日志查看',
  about: '关于'
}

// 当前页面标题
const pageTitle = computed(() => pageTitles[activeMenu.value] || '首页')

// 设备状态
const deviceStatus = ref({
  type: 'info',
  text: '未连接'
})

// 当前视图组件（使用 markRaw 避免响应式包装）
const currentView = computed(() => {
  switch (activeMenu.value) {
    case 'home':
      return markRaw(HomeView)
    default:
      return markRaw(HomeView)
  }
})

// 菜单选择处理
const handleMenuSelect = (index: string) => {
  activeMenu.value = index
}

// 初始化
onMounted(() => {
  console.log('应用已加载')
  // TODO: 检查设备状态
  checkDeviceStatus()
})

// 检查设备状态
async function checkDeviceStatus() {
  try {
    const result = await window.electronAPI.device.getInfo()
    if (result.success && result.data) {
      deviceStatus.value = {
        type: 'success',
        text: '已连接'
      }
    } else {
      deviceStatus.value = {
        type: 'warning',
        text: '未注册'
      }
    }
  } catch (error) {
    console.error('检查设备状态失败:', error)
    deviceStatus.value = {
      type: 'danger',
      text: '连接错误'
    }
  }
}
</script>

<style scoped>
.app-container {
  height: 100vh;
  overflow: hidden;
}

.el-container {
  height: 100%;
}

.sidebar {
  background-color: var(--el-bg-color);
  border-right: 1px solid var(--el-border-color);
  display: flex;
  flex-direction: column;
}

.logo {
  padding: 20px;
  text-align: center;
  border-bottom: 1px solid var(--el-border-color);
}

.logo h2 {
  margin: 0;
  font-size: 16px;
  color: var(--el-text-color-primary);
}

.sidebar-menu {
  flex: 1;
  border-right: none;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 20px;
  border-bottom: 1px solid var(--el-border-color);
  background-color: var(--el-bg-color);
}

.header-title {
  font-size: 18px;
  font-weight: 500;
  color: var(--el-text-color-primary);
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.main-content {
  padding: 20px;
  background-color: var(--el-bg-color-page);
  overflow-y: auto;
}
</style>
