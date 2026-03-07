import { app, Menu, Tray, BrowserWindow, nativeImage } from 'electron'
import { join } from 'path'
import log from 'electron-log'

let tray: Tray | null = null

export function createTray(mainWindow: BrowserWindow): Tray {
  // 创建托盘图标
  const iconPath = join(__dirname, '../../resources/tray-icon.png')
  const icon = nativeImage.createFromPath(iconPath)
  
  tray = new Tray(icon.resize({ width: 16, height: 16 }))
  
  // 设置工具提示
  tray.setToolTip('AI旅拍商家客户端')
  
  // 创建托盘菜单
  updateTrayMenu(mainWindow, false, 0)
  
  // 点击托盘图标显示/隐藏窗口
  tray.on('click', () => {
    if (mainWindow.isVisible()) {
      mainWindow.hide()
    } else {
      mainWindow.show()
    }
  })
  
  log.info('系统托盘创建完成')
  
  return tray
}

export function updateTrayMenu(
  mainWindow: BrowserWindow,
  isWatching: boolean,
  queueLength: number
): void {
  if (!tray) return
  
  const contextMenu = Menu.buildFromTemplate([
    {
      label: '显示主窗口',
      click: () => {
        mainWindow.show()
        if (mainWindow.isMinimized()) {
          mainWindow.restore()
        }
        mainWindow.focus()
      }
    },
    { type: 'separator' },
    {
      label: isWatching ? '停止监控' : '启动监控',
      click: () => {
        // 通过主窗口发送事件来切换监控状态
        mainWindow.webContents.send('tray:toggle-watcher')
      }
    },
    {
      label: `上传队列 (${queueLength})`,
      enabled: false
    },
    { type: 'separator' },
    {
      label: '设置',
      click: () => {
        mainWindow.show()
        mainWindow.webContents.send('tray:open-settings')
      }
    },
    { type: 'separator' },
    {
      label: '退出应用',
      click: () => {
        app.quit()
      }
    }
  ])
  
  tray.setContextMenu(contextMenu)
}

export function updateTrayIcon(status: 'normal' | 'warning' | 'error'): void {
  if (!tray) return
  
  let iconName = 'tray-icon.png'
  
  switch (status) {
    case 'warning':
      iconName = 'tray-icon-warning.png'
      break
    case 'error':
      iconName = 'tray-icon-error.png'
      break
  }
  
  const iconPath = join(__dirname, '../../resources', iconName)
  const icon = nativeImage.createFromPath(iconPath)
  tray.setImage(icon.resize({ width: 16, height: 16 }))
}
