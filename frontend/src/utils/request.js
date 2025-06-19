import axios from 'axios'
import { ElMessage, ElMessageBox } from 'element-plus'
import router from '../router'

// 创建axios实例
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  timeout: 10000, // 延长超时时间
  headers: {
    'Content-Type': 'application/json'
  },
  withCredentials: true // 允许携带cookie
})

// 请求拦截器
api.interceptors.request.use(
  config => {
    // 从更安全的存储获取token
    const token = localStorage.getItem('token') || sessionStorage.getItem('token')
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    } else if (!config._retry && !['/login', '/refresh-token'].some(path => config.url.includes(path))) {
      // 非登录/刷新token接口且无token时跳转登录
      router.push('/login')
      return Promise.reject(new Error('未授权，请先登录'))
    }
    
    // 添加请求标识
    config.headers['X-Request-ID'] = generateRequestId()
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// 响应拦截器
api.interceptors.response.use(
  response => {
    // 处理特殊业务状态码
    if (response.data?.code && response.data.code !== 200) {
      return handleBusinessError(response.data)
    }
    return response.data
  },
  async error => {
    const originalRequest = error.config
    
    // 处理401 token过期
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true
      
      try {
        // 尝试刷新token
        const newToken = await refreshToken()
        localStorage.setItem('token', newToken)
        originalRequest.headers.Authorization = `Bearer ${newToken}`
        return api(originalRequest)
      } catch (refreshError) {
        // 刷新token失败，强制登出
        await clearAuthAndRedirect()
        return Promise.reject(refreshError)
      }
    }
    
    // 其他错误处理
    return handleNetworkError(error)
  }
)

// 错误处理函数
function handleBusinessError(data) {
  const { code, message } = data
  const errorMap = {
    400: '请求参数错误',
    403: '没有权限执行此操作',
    404: '资源不存在',
    429: '请求过于频繁',
    500: '服务器内部错误'
  }
  
  const errorMessage = message || errorMap[code] || '业务处理失败'
  
  // 特定错误特殊处理
  if (code === 403) {
    ElMessageBox.confirm('权限不足，请联系管理员', '警告', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
  } else {
    ElMessage.error(errorMessage)
  }
  
  return Promise.reject(new Error(errorMessage))
}

// 网络错误处理
function handleNetworkError(error) {
  if (error.code === 'ECONNABORTED') {
    ElMessage.error('请求超时，请检查网络连接')
  } else if (!error.response) {
    ElMessage.error('网络异常，请检查网络设置')
  } else {
    const status = error.response.status
    const message = error.response.data?.message || `服务器错误 [${status}]`
    ElMessage.error(message)
  }
  return Promise.reject(error)
}

// 刷新token逻辑
async function refreshToken() {
  const refreshToken = localStorage.getItem('refresh_token')
  if (!refreshToken) throw new Error('无有效刷新令牌')
  
  const { data } = await axios.post('/auth/refresh', { refreshToken })
  if (!data?.token) throw new Error('刷新令牌失败')
  
  return data.token
}

// 清除认证信息并跳转
async function clearAuthAndRedirect() {
  localStorage.removeItem('token')
  localStorage.removeItem('refresh_token')
  localStorage.removeItem('user')
  
  await router.push('/login')
  ElMessage.error('登录已过期，请重新登录')
}

// 生成唯一请求ID
function generateRequestId() {
  return Date.now().toString(36) + Math.random().toString(36).substring(2)
}

export default api