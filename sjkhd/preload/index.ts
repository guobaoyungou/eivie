import { contextBridge, ipcRenderer } from 'electron'

/**
 * 预加载脚本 - 提供安全的 IPC 通信桥接
 * 将主进程功能通过 contextBridge 暴露给渲染进程
 */

// 定义暴露给渲染进程的 API
const electronAPI = {
  // ==================== 设备管理 API ====================
  device: {
    register: (data: {
      deviceCode: string
      deviceName: string
      bid: number
      mdid?: number
    }) => ipcRenderer.invoke('device:register', data),
    
    getInfo: () => ipcRenderer.invoke('device:info'),
    
    onStatusChange: (callback: (status: any) => void) => {
      ipcRenderer.on('device:status-change', (_event, status) => callback(status))
    }
  },

  // ==================== 文件监控 API ====================
  watcher: {
    start: (paths: string[]) => ipcRenderer.invoke('watcher:start', paths),
    
    stop: () => ipcRenderer.invoke('watcher:stop'),
    
    onFileDetected: (callback: (fileInfo: any) => void) => {
      ipcRenderer.on('watcher:file-detected', (_event, fileInfo) => callback(fileInfo))
    },
    
    onToggle: (callback: () => void) => {
      ipcRenderer.on('tray:toggle-watcher', () => callback())
    }
  },

  // ==================== 上传管理 API ====================
  upload: {
    add: (filePath: string) => ipcRenderer.invoke('upload:add', filePath),
    
    pause: (taskId: string) => ipcRenderer.invoke('upload:pause', taskId),
    
    resume: (taskId: string) => ipcRenderer.invoke('upload:resume', taskId),
    
    onProgress: (callback: (data: any) => void) => {
      ipcRenderer.on('upload:progress', (_event, data) => callback(data))
    },
    
    onSuccess: (callback: (data: any) => void) => {
      ipcRenderer.on('upload:success', (_event, data) => callback(data))
    },
    
    onFailed: (callback: (data: any) => void) => {
      ipcRenderer.on('upload:failed', (_event, data) => callback(data))
    }
  },

  // ==================== 心跳服务 API ====================
  heartbeat: {
    start: () => ipcRenderer.invoke('heartbeat:start'),
    
    stop: () => ipcRenderer.invoke('heartbeat:stop'),
    
    onStatusChange: (callback: (status: any) => void) => {
      ipcRenderer.on('heartbeat:status', (_event, status) => callback(status))
    }
  },

  // ==================== 配置管理 API ====================
  config: {
    get: (key: string) => ipcRenderer.invoke('config:get', key),
    
    set: (key: string, value: any) => ipcRenderer.invoke('config:set', key, value),
    
    onOpenSettings: (callback: () => void) => {
      ipcRenderer.on('tray:open-settings', () => callback())
    }
  },

  // ==================== 日志管理 API ====================
  log: {
    get: (options: { level?: string; limit?: number }) => 
      ipcRenderer.invoke('log:get', options)
  },

  // ==================== 统计信息 API ====================
  statistics: {
    get: () => ipcRenderer.invoke('statistics:get')
  },

  // ==================== 系统信息 API ====================
  system: {
    getPlatform: () => process.platform,
    getVersion: () => process.versions.electron
  }
}

// 将 API 暴露给渲染进程
contextBridge.exposeInMainWorld('electronAPI', electronAPI)

// 类型声明（供 TypeScript 使用）
export type ElectronAPI = typeof electronAPI
