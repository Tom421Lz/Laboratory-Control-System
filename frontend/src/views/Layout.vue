<template>
  <el-container class="layout-container">
    <el-aside width="200px">
      <el-menu
        :default-active="activeMenu"
        class="el-menu-vertical"
        :router="true"
        background-color="#304156"
        text-color="#bfcbd9"
        active-text-color="#409EFF"
      >
        <el-menu-item index="/">
          <el-icon><Monitor /></el-icon>
          <span>仪表盘</span>
        </el-menu-item>
        <el-menu-item index="/equipment">
          <el-icon><Tools /></el-icon>
          <span>设备管理</span>
        </el-menu-item>
        <el-menu-item index="/resources">
          <el-icon><Box /></el-icon>
          <span>资源管理</span>
        </el-menu-item>
        <el-menu-item index="/maintenance">
          <el-icon><Setting /></el-icon>
          <span>维护管理</span>
        </el-menu-item>
        <el-menu-item index="/laboratories" v-if="isAdmin">
          <el-icon><Box /></el-icon>
          <span>实验室管理</span>
        </el-menu-item>
        <el-menu-item index="/users" v-if="isAdmin">
          <el-icon><User /></el-icon>
          <span>用户管理</span>
        </el-menu-item>
      </el-menu>
    </el-aside>
    
    <el-container>
      <el-header>
        <div class="header-left">
          <h2>实验室管理系统</h2>
        </div>
        <div class="header-right">
          <el-dropdown>
            <span class="user-info">
              {{ username }}
              <el-tag size="small" type="info" style="margin-left: 8px;">{{ roleLabel }}</el-tag>
              <el-icon><ArrowDown /></el-icon>
            </span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item @click="handleLogout">退出登录</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </el-header>
      
      <el-main>
        <router-view></router-view>
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Monitor, Tools, Box, Setting, User, ArrowDown } from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()

const username = ref(localStorage.getItem('username') || '')
const role = ref(localStorage.getItem('role') || '')

const isAdmin = computed(() => localStorage.getItem('role') === 'admin')

const activeMenu = computed(() => route.path)

const roleLabel = computed(() => {
  switch (role.value) {
    case 'admin': return '管理员';
    case 'teacher': return '教师';
    case 'student': return '学生';
    case 'supplier': return '供应商';
    default: return role.value;
  }
})

function handleLogout() {
  localStorage.removeItem('token')
  localStorage.removeItem('username')
  localStorage.removeItem('role')
  window.location.href = '/login'
}
</script>

<style scoped>
.layout-container {
  height: 100vh;
}

.el-aside {
  background-color: #304156;
  color: #fff;
}

.el-menu {
  border-right: none;
}

.el-header {
  background-color: #fff;
  color: #333;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #dcdfe6;
}

.header-right {
  display: flex;
  align-items: center;
}

.user-info {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.el-main {
  background-color: #f0f2f5;
  padding: 20px;
}
</style> 