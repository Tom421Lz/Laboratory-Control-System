<template>
  <div class="login-container">
    <el-card class="login-card">
      <template #header>
        <h2>实验室管理系统</h2>
      </template>
      <!-- <el-link @click="router.push('/register')">去注册</el-link> -->
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
            placeholder="用户名"
            prefix-icon="User"
          />
        </el-form-item>
        
        <el-form-item prop="password">
          <el-input
            v-model="loginForm.password"
            type="password"
            placeholder="密码"
            prefix-icon="Lock"
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
            登录
          </el-button>
        </el-form-item>
        
      </el-form>
    </el-card>
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



const rules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' }
  ]
}

const handleLogin = async () => {
  // 先校验表单
  loginFormRef.value.validate(async (valid) => {
    if (!valid) return; // 校验不通过直接返回

    try {
      loading.value = true
      console.log('loginForm:', loginForm)
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
.login-container {
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: #f0f2f5;
}

.login-card {
  width: 400px;
}

.login-card :deep(.el-card__header) {
  text-align: center;
  padding: 20px;
}

.login-button {
  width: 100%;
}
</style> 