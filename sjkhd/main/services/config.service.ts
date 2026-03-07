import Store from 'electron-store'
import { Config, defaultConfig } from '../models/config.model'
import { encrypt, decrypt, generateKey } from '../utils/crypto.util'
import { getDeviceId } from '../utils/system-info.util'
import log from 'electron-log'

/**
 * 配置管理服务
 */
class ConfigService {
  private store: Store
  private encryptionKey: Buffer
  private cachedConfig: Config | null = null

  constructor() {
    this.store = new Store({
      name: 'app-config',
      encryptionKey: undefined // 不使用 electron-store 的内置加密
    })
    
    // 使用设备ID生成加密密钥
    this.encryptionKey = generateKey(getDeviceId())
    
    log.info('配置管理服务初始化完成')
  }

  /**
   * 获取完整配置
   */
  getConfig(): Config {
    if (this.cachedConfig) {
      return this.cachedConfig
    }

    const config: Config = {
      server: this.get('server', defaultConfig.server),
      app: this.get('app', defaultConfig.app),
      watcher: this.get('watcher', defaultConfig.watcher),
      upload: this.get('upload', defaultConfig.upload)
    }

    this.cachedConfig = config
    return config
  }

  /**
   * 保存完整配置
   */
  setConfig(config: Partial<Config>): void {
    if (config.server) this.set('server', config.server)
    if (config.app) this.set('app', config.app)
    if (config.watcher) this.set('watcher', config.watcher)
    if (config.upload) this.set('upload', config.upload)
    
    this.cachedConfig = null // 清除缓存
    log.info('配置已保存')
  }

  /**
   * 获取配置项
   */
  get<T>(key: string, defaultValue?: T): T {
    return this.store.get(key, defaultValue) as T
  }

  /**
   * 设置配置项
   */
  set(key: string, value: any): void {
    this.store.set(key, value)
    this.cachedConfig = null // 清除缓存
  }

  /**
   * 删除配置项
   */
  delete(key: string): void {
    this.store.delete(key)
    this.cachedConfig = null
  }

  /**
   * 清空所有配置
   */
  clear(): void {
    this.store.clear()
    this.cachedConfig = null
    log.warn('所有配置已清空')
  }

  /**
   * 获取加密的配置项（用于敏感信息）
   */
  getSecure(key: string): string | null {
    const encryptedValue = this.store.get(key) as string | undefined
    
    if (!encryptedValue) {
      return null
    }

    try {
      return decrypt(encryptedValue, this.encryptionKey)
    } catch (error) {
      log.error(`解密配置项失败: ${key}`, error)
      return null
    }
  }

  /**
   * 设置加密的配置项（用于敏感信息）
   */
  setSecure(key: string, value: string): void {
    try {
      const encryptedValue = encrypt(value, this.encryptionKey)
      this.store.set(key, encryptedValue)
      log.info(`加密配置项已保存: ${key}`)
    } catch (error) {
      log.error(`加密配置项失败: ${key}`, error)
      throw error
    }
  }

  /**
   * 检查配置项是否存在
   */
  has(key: string): boolean {
    return this.store.has(key)
  }

  /**
   * 获取设备Token（加密存储）
   */
  getDeviceToken(): string | null {
    return this.getSecure('device.token')
  }

  /**
   * 设置设备Token（加密存储）
   */
  setDeviceToken(token: string): void {
    this.setSecure('device.token', token)
  }

  /**
   * 重置为默认配置
   */
  resetToDefault(): void {
    this.clear()
    this.setConfig(defaultConfig)
    log.info('配置已重置为默认值')
  }

  /**
   * 导出配置（用于备份）
   */
  exportConfig(): Config {
    return this.getConfig()
  }

  /**
   * 导入配置（用于恢复）
   */
  importConfig(config: Config): void {
    this.setConfig(config)
    log.info('配置已导入')
  }
}

// 单例导出
export const configService = new ConfigService()
