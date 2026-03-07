import { createReadStream, createWriteStream, statSync, existsSync, readdirSync } from 'fs'
import { createHash } from 'crypto'
import { basename, extname, join } from 'path'
import log from 'electron-log'

/**
 * 文件工具类
 */

/**
 * 文件信息接口
 */
export interface FileInfo {
  path: string
  name: string
  size: number
  extension: string
  md5?: string
  createdAt: number
  modifiedAt: number
}

/**
 * 获取文件信息
 * @param filePath 文件路径
 */
export function getFileInfo(filePath: string): FileInfo | null {
  try {
    if (!existsSync(filePath)) {
      return null
    }

    const stats = statSync(filePath)
    
    if (!stats.isFile()) {
      return null
    }

    return {
      path: filePath,
      name: basename(filePath),
      size: stats.size,
      extension: extname(filePath).toLowerCase(),
      createdAt: stats.birthtimeMs,
      modifiedAt: stats.mtimeMs
    }
  } catch (error) {
    log.error('获取文件信息失败:', error)
    return null
  }
}

/**
 * 计算文件 MD5
 * @param filePath 文件路径
 */
export async function calculateFileMD5(filePath: string): Promise<string> {
  return new Promise((resolve, reject) => {
    const hash = createHash('md5')
    const stream = createReadStream(filePath)

    stream.on('data', (data) => {
      hash.update(data)
    })

    stream.on('end', () => {
      resolve(hash.digest('hex'))
    })

    stream.on('error', (error) => {
      reject(error)
    })
  })
}

/**
 * 检查文件是否为图片
 * @param filePath 文件路径
 */
export function isImageFile(filePath: string): boolean {
  const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.webp']
  const ext = extname(filePath).toLowerCase()
  return imageExtensions.includes(ext)
}

/**
 * 检查文件是否稳定（大小不再变化）
 * @param filePath 文件路径
 * @param waitTime 等待时间（毫秒）
 */
export async function isFileStable(filePath: string, waitTime: number = 2000): Promise<boolean> {
  try {
    const initialSize = statSync(filePath).size
    
    await new Promise(resolve => setTimeout(resolve, waitTime))
    
    const finalSize = statSync(filePath).size
    
    return initialSize === finalSize
  } catch (error) {
    log.error('检查文件稳定性失败:', error)
    return false
  }
}

/**
 * 检查文件是否为临时文件
 * @param fileName 文件名
 */
export function isTempFile(fileName: string): boolean {
  // 以 . 或 ~ 开头的文件视为临时文件
  return fileName.startsWith('.') || fileName.startsWith('~')
}

/**
 * 格式化文件大小
 * @param bytes 字节数
 */
export function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B'
  
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

/**
 * 扫描目录下的所有文件
 * @param dirPath 目录路径
 * @param recursive 是否递归
 */
export function scanDirectory(dirPath: string, recursive: boolean = false): string[] {
  const files: string[] = []
  
  try {
    if (!existsSync(dirPath)) {
      return files
    }

    const items = readdirSync(dirPath, { withFileTypes: true })
    
    for (const item of items) {
      const fullPath = join(dirPath, item.name)
      
      if (item.isFile()) {
        files.push(fullPath)
      } else if (item.isDirectory() && recursive) {
        files.push(...scanDirectory(fullPath, true))
      }
    }
  } catch (error) {
    log.error('扫描目录失败:', error)
  }
  
  return files
}

/**
 * 检查文件大小是否在允许范围内
 * @param size 文件大小（字节）
 * @param minSize 最小大小（KB）
 * @param maxSize 最大大小（MB）
 */
export function isFileSizeValid(size: number, minSize: number = 10, maxSize: number = 10): boolean {
  const minBytes = minSize * 1024
  const maxBytes = maxSize * 1024 * 1024
  
  return size >= minBytes && size <= maxBytes
}
