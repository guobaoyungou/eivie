import log from 'electron-log'

/**
 * 重试策略工具类
 */

/**
 * 重试配置接口
 */
export interface RetryOptions {
  maxRetries: number
  baseDelay: number
  maxDelay: number
  exponential: boolean
  onRetry?: (attempt: number, error: Error) => void
}

/**
 * 默认重试配置
 */
const defaultRetryOptions: RetryOptions = {
  maxRetries: 5,
  baseDelay: 5000, // 5秒
  maxDelay: 60000, // 60秒
  exponential: true
}

/**
 * 计算重试延迟时间
 * @param attempt 当前尝试次数（从1开始）
 * @param options 重试配置
 */
export function calculateRetryDelay(attempt: number, options: Partial<RetryOptions> = {}): number {
  const opts = { ...defaultRetryOptions, ...options }
  
  if (!opts.exponential) {
    return opts.baseDelay
  }
  
  // 指数退避：baseDelay * 2^(attempt-1)
  const delay = opts.baseDelay * Math.pow(2, attempt - 1)
  
  return Math.min(delay, opts.maxDelay)
}

/**
 * 执行带重试的异步操作
 * @param fn 要执行的异步函数
 * @param options 重试配置
 */
export async function retryAsync<T>(
  fn: () => Promise<T>,
  options: Partial<RetryOptions> = {}
): Promise<T> {
  const opts = { ...defaultRetryOptions, ...options }
  let lastError: Error | null = null
  
  for (let attempt = 1; attempt <= opts.maxRetries; attempt++) {
    try {
      return await fn()
    } catch (error) {
      lastError = error as Error
      
      if (attempt < opts.maxRetries) {
        const delay = calculateRetryDelay(attempt, opts)
        
        log.warn(`操作失败，第 ${attempt} 次重试，${delay}ms 后重试:`, error)
        
        if (opts.onRetry) {
          opts.onRetry(attempt, lastError)
        }
        
        await sleep(delay)
      }
    }
  }
  
  log.error(`操作失败，已达到最大重试次数 ${opts.maxRetries}`)
  throw lastError
}

/**
 * 延迟执行
 * @param ms 延迟时间（毫秒）
 */
export function sleep(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms))
}

/**
 * 带超时的 Promise
 * @param promise 原始 Promise
 * @param timeoutMs 超时时间（毫秒）
 * @param errorMessage 超时错误消息
 */
export async function withTimeout<T>(
  promise: Promise<T>,
  timeoutMs: number,
  errorMessage: string = 'Operation timed out'
): Promise<T> {
  let timeoutHandle: NodeJS.Timeout
  
  const timeoutPromise = new Promise<never>((_, reject) => {
    timeoutHandle = setTimeout(() => {
      reject(new Error(errorMessage))
    }, timeoutMs)
  })
  
  try {
    return await Promise.race([promise, timeoutPromise])
  } finally {
    clearTimeout(timeoutHandle!)
  }
}

/**
 * 重试策略：线性退避
 * 延迟时间为：baseDelay * attempt
 */
export function linearBackoff(attempt: number, baseDelay: number = 5000): number {
  return baseDelay * attempt
}

/**
 * 重试策略：指数退避
 * 延迟时间为：baseDelay * 2^(attempt-1)
 */
export function exponentialBackoff(attempt: number, baseDelay: number = 5000, maxDelay: number = 60000): number {
  const delay = baseDelay * Math.pow(2, attempt - 1)
  return Math.min(delay, maxDelay)
}

/**
 * 重试策略：固定延迟
 */
export function fixedDelay(baseDelay: number = 5000): number {
  return baseDelay
}

/**
 * 判断错误是否可重试
 * @param error 错误对象
 */
export function isRetryableError(error: any): boolean {
  // 网络错误通常可重试
  if (error.code === 'ECONNREFUSED' || 
      error.code === 'ETIMEDOUT' ||
      error.code === 'ENOTFOUND' ||
      error.code === 'ENETUNREACH') {
    return true
  }
  
  // HTTP 5xx 错误可重试
  if (error.response && error.response.status >= 500) {
    return true
  }
  
  // HTTP 429 (Too Many Requests) 可重试
  if (error.response && error.response.status === 429) {
    return true
  }
  
  return false
}
