import { app, BrowserWindow } from 'electron'
import { join } from 'path'
import { createMainWindow } from './window'
import { createTray } from './tray'
import { registerIpcHandlers } from './ipc-handlers'
import log from 'electron-log'

// 配置日志
log.transports.file.level = 'info'
log.transports.file.maxSize = 50 * 1024 * 1024 // 50MB
log.transports.file.format = '[{y}-{m}-{d} {h}:{i}:{s}.{ms}] [{level}] {text}'

let mainWindow: BrowserWindow | null = null

// 禁用硬件加速（可选，某些系统上可能需要）
// app.disableHardwareAcceleration()

// 确保单例应用
const gotTheLock = app.requestSingleInstanceLock()

if (!gotTheLock) {
  app.quit()
} else {
  app.on('second-instance', () => {
    // 当尝试打开第二个实例时，聚焦主窗口
    if (mainWindow) {
      if (mainWindow.isMinimized()) mainWindow.restore()
      mainWindow.focus()
    }
  })

  // 应用准备就绪
  app.whenReady().then(async () => {
    log.info('应用启动...')
    
    // 注册 IPC 处理器
    registerIpcHandlers()
    
    // 创建主窗口
    mainWindow = createMainWindow()
    
    // 创建系统托盘
    createTray(mainWindow)
    
    log.info('应用启动完成')
  })

  // 所有窗口关闭
  app.on('window-all-closed', () => {
    // 在 macOS 上，除非用户明确使用 Cmd + Q 退出，否则保持应用和菜单栏活跃
    if (process.platform !== 'darwin') {
      app.quit()
    }
  })

  // 应用激活（macOS）
  app.on('activate', () => {
    // 在 macOS 上，当点击 dock 图标并且没有其他窗口打开时，重新创建窗口
    if (BrowserWindow.getAllWindows().length === 0) {
      mainWindow = createMainWindow()
    }
  })

  // 应用退出前
  app.on('before-quit', () => {
    log.info('应用退出...')
    // 在这里执行清理工作
  })

  // 未捕获的异常
  process.on('uncaughtException', (error) => {
    log.error('未捕获的异常:', error)
  })

  // 未处理的 Promise 拒绝
  process.on('unhandledRejection', (reason) => {
    log.error('未处理的 Promise 拒绝:', reason)
  })
}

export { mainWindow }
