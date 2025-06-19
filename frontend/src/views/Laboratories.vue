<template>
  <div class="laboratories">
    <div class="page-header">
      <h2>实验室管理</h2>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        添加实验室
      </el-button>
    </div>
    <el-row :gutter="24">
      <el-col :span="6" v-for="lab in laboratories" :key="lab.id">
        <el-tooltip :content="`位置：${lab.location || ''}\n描述：${lab.description || ''}`" placement="top" effect="dark">
          <el-card class="lab-card">
            <template #header>
              <div class="lab-header">
                <el-icon class="lab-icon"><Box /></el-icon>
                <span class="lab-title">{{ lab.name }}</span>
              </div>
            </template>
            <div class="lab-info">
              <div>设备数量：<b>{{ lab.equipment_count }}</b></div>
              <div>损毁设备：<b style="color: #F56C6C">{{ lab.faulty_count }}</b></div>
            </div>
            <div class="lab-actions">
              <el-button size="small" @click="handleEdit(lab)">编辑</el-button>
              <el-button size="small" type="danger" @click="handleDelete(lab)">删除</el-button>
            </div>
          </el-card>
        </el-tooltip>
      </el-col>
    </el-row>
    <el-dialog v-model="dialogVisible" :title="dialogType === 'add' ? '添加实验室' : '编辑实验室'" width="400px">
      <el-form :model="labForm" :rules="labRules" ref="labFormRef" label-width="80px">
        <el-form-item label="名称" prop="name">
          <el-input v-model="labForm.name" />
        </el-form-item>
        <el-form-item label="位置" prop="location">
          <el-input v-model="labForm.location" />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input v-model="labForm.description" type="textarea" rows="2" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">取消</el-button>
          <el-button type="primary" @click="handleSubmit">确定</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue'
import { Plus, Box } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import axios from 'axios'

const laboratories = ref([])
const dialogVisible = ref(false)
const dialogType = ref('add')
const labFormRef = ref(null)
const labForm = reactive({
  id: null,
  name: '',
  location: '',
  description: ''
})
const labRules = {
  name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
  location: [{ required: true, message: '请输入位置', trigger: 'blur' }],
  description: [{ max: 200, message: '描述不能超过200字', trigger: 'blur' }]
}

const fetchLaboratories = async () => {
  const res = await axios.get('/api/laboratories')
  laboratories.value = res.data.map(lab => ({
    ...lab,
    equipment_count: lab.equipment_count || 0,
    faulty_count: lab.faulty_count || 0
  }))
}

const handleAdd = () => {
  dialogType.value = 'add'
  nextTick(() => labFormRef.value?.resetFields())
  Object.assign(labForm, { id: null, name: '', location: '', description: '' })
  dialogVisible.value = true
}
const handleEdit = (lab) => {
  dialogType.value = 'edit'
  nextTick(() => labFormRef.value?.clearValidate())
  Object.assign(labForm, lab)
  dialogVisible.value = true
}
const handleDelete = (lab) => {
  ElMessageBox.confirm(`确定要删除实验室"${lab.name}"吗？`, '警告', {
    confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning'
  }).then(async () => {
    await axios.delete(`/api/laboratories/${lab.id}`)
    ElMessage.success('删除成功')
    fetchLaboratories()
  }).catch(() => {})
}
const handleSubmit = async () => {
  await labFormRef.value.validate()
  if (dialogType.value === 'add') {
    await axios.post('/api/laboratories', labForm)
    ElMessage.success('添加成功')
  } else {
    await axios.put(`/api/laboratories/${labForm.id}`, labForm)
    ElMessage.success('更新成功')
  }
  dialogVisible.value = false
  fetchLaboratories()
}
onMounted(fetchLaboratories)
</script>

<style scoped>
.laboratories {
  padding: 20px;
  min-height: 100vh;
  background: url('/bg-sztu.jpg') no-repeat center center fixed;
  background-size: cover;
}
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.lab-card { margin-bottom: 24px; }
.lab-header { display: flex; align-items: center; }
.lab-icon { font-size: 32px; margin-right: 10px; color: #409EFF; }
.lab-title { font-size: 20px; font-weight: bold; }
.lab-info { margin: 16px 0; font-size: 16px; }
.lab-actions { display: flex; gap: 10px; }
</style> 