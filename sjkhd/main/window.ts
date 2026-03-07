import { BrowserWindow, shell } from 'electron'
import { join } from 'path'
import log from 'electron-log'

const is = {
  dev: process.env.NODE_ENV === 'development'
}

export function createMainWindow(): BrowserWindow {
  const mainWindow = new BrowserWindow({
    width: 1200,
    height: 800,
    minWidth: 1000,
    minHeight: 600,
    show: false,
    autoHideMenuBar: true,
    title: 'AI旅拍商家客户端',
    webPreferences: {
      preload: join(__dirname, '../preload/index.js'),
      sandbox: false,
      contextIsolation: true,
      nodeIntegration: false,
      webSecurity: true
    }
  })

  // 窗口准备显示时
  mainWindow.once('ready-to-show', () => {
    mainWindow.show()
    log.info('主窗口显示')
  })

  // 处理外部链接
  mainWindow.webContents.setWindowOpenHandler((details) => {
    shell.openExternal(details.url)
    return { action: 'deny' }
  })

  // 加载应用
  if (is.dev) {
    mainWindow.loadURL('http://localhost:5173')
    // 开发环境打开开发者工具
    mainWindow.webContents.openDevTools()
  } else {
    mainWindow.loadFile(join(__dirname, '../renderer/index.html'))
  }

  // 窗口关闭
  mainWindow.on('closed', () => {
    log.info('主窗口关闭')
  })

  return mainWindow
}
