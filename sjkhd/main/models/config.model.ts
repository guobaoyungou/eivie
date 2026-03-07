/**
 * 配置数据模型
 */

/**
 * 服务器配置
 */
export interface ServerConfig {
  apiBaseUrl: string
  timeout: number
  retryTimes: number
}

/**
 * 应用配置
 */
export interface AppConfig {
  language: string
  theme: 'light' | 'dark'
  autoStart: boolean
  minimizeToTray: boolean
  closeToTray: boolean
}

/**
 * 监控配置
 */
export interface WatcherConfig {
  watchPaths: string[]
  scanInterval: number
  fileStableTime: number
  allowedExtensions: string[]
  minFileSize: number
  maxFileSize: number
}

/**
 * 上传配置
 */
export interface UploadConfig {
  concurrency: number
  autoUpload: boolean
  maxRetries: number
  uploadTimeout: number
}

/**
 * 完整配置
 */
export interface Config {
  server: ServerConfig
  app: AppConfig
  watcher: WatcherConfig
  upload: UploadConfig
}

/**
 * 默认配置
 */
export const defaultConfig: Config = {
  server: {
    apiBaseUrl: process.env.API_BASE_URL || 'http://localhost:3000',
    timeout: 120000,
    retryTimes: 3
  },
  app: {
    language: 'zh-CN',
    theme: 'light',
    autoStart: false,
    minimizeToTray: true,
    closeToTray: true
  },
  watcher: {
    watchPaths: [],
    scanInterval: 60,
    fileStableTime: 2,
    allowedExtensions: ['.jpg', '.jpeg', '.png'],
    minFileSize: 10,
    maxFileSize: 10
  },
  upload: {
    concurrency: 3,
    autoUpload: true,
    maxRetries: 5,
    uploadTimeout: 300000
  }
}
