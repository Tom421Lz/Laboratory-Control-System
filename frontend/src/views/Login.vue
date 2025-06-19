<template>
  <div class="login-bg">
    <div class="login-main">
      <!-- 左侧背景与图片 -->
      <div class="login-left">
        <img class="logo" src="/logo-sztu.png" alt="SZTU" />
        <div class="system-title">深圳技术大学实验室管理系统</div>
        <div class="campus-images">
          <img v-for="(img, i) in images" :key="i" :src="img" class="campus-img" />
        </div>
      </div>
      <!-- 右侧登录表单 -->
      <div class="login-right">
        <el-card class="login-card">
          <template #header>
            <div class="login-card-title">账号登录</div>
          </template>
          <el-form
            ref="loginFormRef"
            :model="loginForm"
            :rules="rules"
            label-width="0"
            @keyup.enter="handleLogin"
          >
            <el-form-item prop="username">
              <el-input
                v-model="loginForm.username"
                placeholder="用户名/邮箱"
                :prefix-icon="User"
              />
            </el-form-item>
            <el-form-item prop="password">
              <el-input
                v-model="loginForm.password"
                type="password"
                placeholder="密码区分大小写"
                :prefix-icon="Lock"
                show-password
              />
            </el-form-item>
            <el-form-item>
              <el-button
                type="primary"
                :loading="loading"
                class="login-button"
                @click="handleLogin"
              >
                立即登录
              </el-button>
            </el-form-item>
          </el-form>
          <div class="login-extra">
            <el-checkbox v-model="rememberMe">记住密码</el-checkbox>
            <el-link type="primary" @click="router.push('/register')">注册</el-link>
            <el-link type="info" style="float:right">忘记密码?</el-link>
          </div>
        </el-card>
        <div class="login-footer">
          <div class="tips">
            <b>重要提示</b><br>
            1. 推荐使用 Chrome、Firefox、Edge 浏览器<br>
            2. 忘记密码请联系管理员
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { User, Lock } from '@element-plus/icons-vue'
import axios from 'axios'

const loading = ref(false)
const router = useRouter()
const loginForm = reactive({
  username: '',
  password: ''
})
const loginFormRef = ref(null)
const rememberMe = ref(false)
const images = [
  '/campus1.png',
  '/campus2.png',
  '/campus3.png',
  '/campus4.png'
]

const rules = {
  username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }]
}

const handleLogin = async () => {
  loginFormRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      loading.value = true
      const response = await axios.post('/api/login', {
        username: loginForm.username,
        password: loginForm.password
      })
      const { token, user } = response.data
      localStorage.setItem('token', token)
      localStorage.setItem('username', user.username)
      localStorage.setItem('role', user.role)
      ElMessage.success('登录成功')
      router.push('/')
    } catch (error) {
      ElMessage.error(error.response?.data?.error || '登录失败')
    } finally {
      loading.value = false
    }
  })
}
</script>

<style scoped>
.login-bg {
  min-height: 100vh;
  width: 100vw;
  background: url('/bg-sztu.png') no-repeat center center fixed;
  background-size: cover;
  display: flex;
  align-items: center;
  justify-content: center;
}
.login-main {
  display: flex;
  width: 1100px;
  min-height: 600px;
  background: rgba(255,255,255,0.95);
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.18);
  overflow: hidden;
}
.login-left {
  flex: 1.2;
  background: transparent;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
}
.logo {
  width: 120px;
  margin-bottom: 16px;
}
.system-title {
  font-size: 28px;
  font-weight: bold;
  color: #1677ff;
  margin-bottom: 32px;
  text-shadow: 0 2px 8px #fff;
}
.campus-images {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  justify-content: center;
}
.campus-img {
  width: 180px;
  height: 110px;
  object-fit: cover;
  border-radius: 8px;
  box-shadow: 0 2px 8px #e0e7ef;
}
.login-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 32px 0 32px;
}
.login-card {
  width: 360px;
  margin-bottom: 24px;
  background: #f8fbff;
  border-radius: 12px;
  box-shadow: 0 2px 12px #e0e7ef;
}
.login-card-title {
  text-align: center;
  font-size: 22px;
  color: #1677ff;
  font-weight: bold;
  letter-spacing: 2px;
}
.login-button {
  width: 100%;
}
.login-extra {
  margin-top: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 13px;
}
.login-footer {
  margin-top: 32px;
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
}
.tips {
  font-size: 13px;
  color: #666;
  line-height: 1.7;
}
</style> 