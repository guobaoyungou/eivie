import { createCipheriv, createDecipheriv, randomBytes, createHash } from 'crypto'

/**
 * 加密工具类
 */

const ALGORITHM = 'aes-256-cbc'
const KEY_LENGTH = 32 // 256 bits
const IV_LENGTH = 16 // 128 bits

/**
 * 生成加密密钥
 * @param seed 密钥种子（如MAC地址）
 */
export function generateKey(seed: string): Buffer {
  return createHash('sha256').update(seed).digest()
}

/**
 * AES-256-CBC 加密
 * @param text 待加密文本
 * @param key 加密密钥
 */
export function encrypt(text: string, key: Buffer): string {
  const iv = randomBytes(IV_LENGTH)
  const cipher = createCipheriv(ALGORITHM, key, iv)
  
  let encrypted = cipher.update(text, 'utf8', 'hex')
  encrypted += cipher.final('hex')
  
  // 将 IV 和加密内容拼接
  return iv.toString('hex') + ':' + encrypted
}

/**
 * AES-256-CBC 解密
 * @param encryptedText 加密文本（包含IV）
 * @param key 解密密钥
 */
export function decrypt(encryptedText: string, key: Buffer): string {
  const parts = encryptedText.split(':')
  if (parts.length !== 2) {
    throw new Error('Invalid encrypted text format')
  }
  
  const iv = Buffer.from(parts[0], 'hex')
  const encrypted = parts[1]
  
  const decipher = createDecipheriv(ALGORITHM, key, iv)
  
  let decrypted = decipher.update(encrypted, 'hex', 'utf8')
  decrypted += decipher.final('utf8')
  
  return decrypted
}

/**
 * 计算字符串的 MD5 哈希
 * @param text 待哈希文本
 */
export function md5(text: string): string {
  return createHash('md5').update(text).digest('hex')
}

/**
 * 计算字符串的 SHA256 哈希
 * @param text 待哈希文本
 */
export function sha256(text: string): string {
  return createHash('sha256').update(text).digest('hex')
}

/**
 * 生成随机字符串
 * @param length 字符串长度
 */
export function randomString(length: number): string {
  return randomBytes(Math.ceil(length / 2))
    .toString('hex')
    .slice(0, length)
}
