<template>
  <div class="maintenance">
    <div class="page-header">
      <h2>维护管理</h2>
      <el-button type="primary" @click="handleCreateTask">
        <el-icon><Plus /></el-icon>
        创建维护任务
      </el-button>
    </div>
    
    <el-card>
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="设备名称">
          <el-input v-model="searchForm.equipment_name" placeholder="请输入设备名称"clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="请选择状态"clearable>
            <el-option label="全部" value="" />
            <el-option label="待处理" value="pending" />
            <el-option label="进行中" value="in_progress" />
            <el-option label="已完成" value="completed" />
            <el-option label="已取消" value="cancelled" />
          </el-select>
        </el-form-item>
        <el-form-item label="优先级">
          <el-select v-model="searchForm.priority" placeholder="请选择优先级"clearable>
            <el-option label="全部" value="" />
            <el-option label="低" value="low" />
            <el-option label="中" value="medium" />
            <el-option label="高" value="high" />
            <el-option label="紧急" value="urgent" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="resetSearch">重置</el-button>
        </el-form-item>
      </el-form>
      
      <el-table
        v-loading="loading"
        :data="maintenanceList"
        style="width: 100%"
      >
        <el-table-column prop="equipment_name" label="设备名称" />
        <el-table-column prop="status" label="状态">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="priority" label="优先级">
          <template #default="{ row }">
            <el-tag :type="getPriorityType(row.priority)">
              {{ getPriorityText(row.priority) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="assigned_to" label="负责人" />
        <el-table-column prop="start_date" label="开始日期">
          <template #default="{ row }">
            {{ row.start_date ? dayjs(row.start_date).format('YYYY-MM-DD') : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="expected_completion_date" label="预计完成日期">
          <template #default="{ row }">
            {{ row.expected_completion_date ? dayjs(row.expected_completion_date).format('YYYY-MM-DD') : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="notes" label="维护说明" />
        <el-table-column prop="completion_date" label="完成日期">
          <template #default="{ row }">
            {{ row.completion_date ? dayjs(row.completion_date).format('YYYY-MM-DD') : '-' }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="250">
          <template #default="{ row }">
            <el-button-group>
              <el-button
                size="small"
                type="primary"
                @click="handleEdit(row)"
              >
                编辑
              </el-button>
              <el-button
                size="small"
                type="success"
                :disabled="row.status === 'completed'"
                @click="handleComplete(row)"
              >
                完成
              </el-button>
              <el-button
                size="small"
                type="danger"
                :disabled="row.status === 'completed'"
                @click="handleCancel(row)"
              >
                取消
              </el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>
      
      <div class="pagination">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :total="total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>
    
    <!-- 创建/编辑维护任务对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogType === 'create' ? '创建维护任务' : '编辑维护任务'"
      width="500px"
    >
      <el-form
        ref="maintenanceFormRef"
        :model="maintenanceForm"
        :rules="rules"
        label-width="120px"
      >
        <el-form-item label="故障报告" prop="fault_report_id">
          <el-select v-model="maintenanceForm.fault_report_id" placeholder="请选择故障报告">
            <el-option
              v-for="report in faultReportList"
              :key="report.id"
              :label="`${report.equipment_name} - ${report.description}`"
              :value="report.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="负责人" prop="assigned_to">
          <el-select v-model="maintenanceForm.assigned_to" placeholder="请选择负责人">
            <el-option
              v-for="user in users"
              :key="user.id"
              :label="user.username"
              :value="user.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="优先级" prop="priority">
          <el-select v-model="maintenanceForm.priority" placeholder="请选择优先级">
            <el-option label="低" value="low" />
            <el-option label="中" value="medium" />
            <el-option label="高" value="high" />
            <el-option label="紧急" value="urgent" />
          </el-select>
        </el-form-item>
        <el-form-item label="开始日期" prop="start_date">
          <el-date-picker
            v-model="maintenanceForm.start_date"
            type="date"
            placeholder="选择日期"
          />
        </el-form-item>
        <el-form-item label="预计完成日期" prop="expected_completion_date">
          <el-date-picker
            v-model="maintenanceForm.expected_completion_date"
            type="date"
            placeholder="选择日期"
          />
        </el-form-item>
        <el-form-item label="维护说明" prop="description">
          <el-input
            v-model="maintenanceForm.description"
            type="textarea"
            rows="4"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">取消</el-button>
          <el-button type="primary" @click="handleSubmit">
            确定
          </el-button>
        </span>
      </template>
    </el-dialog>
    
    <!-- 完成维护任务对话框 -->
    <el-dialog
      v-model="completeDialogVisible"
      title="完成维护任务"
      width="500px"
    >
      <el-form
        ref="completeFormRef"
        :model="completeForm"
        :rules="completeRules"
        label-width="120px"
      >
        <el-form-item label="完成日期" prop="completion_date">
          <el-date-picker
            v-model="completeForm.completion_date"
            type="date"
            placeholder="选择日期"
          />
        </el-form-item>
        <el-form-item label="维护结果" prop="result">
          <el-input
            v-model="completeForm.result"
            type="textarea"
            rows="4"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="completeDialogVisible = false">取消</el-button>
          <el-button type="primary" @click="submitComplete">
            确定
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Plus } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import axios from 'axios'
import dayjs from 'dayjs'

// 数据列表
const maintenanceList = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const equipmentList = ref([])
const users = ref([])
const faultReportList = ref([])

// 搜索表单
const searchForm = reactive({
  equipment_name: '',
  status: '',
  priority: ''
})

// 维护任务表单
const dialogVisible = ref(false)
const dialogType = ref('create')
const maintenanceForm = reactive({
  fault_report_id: '',
  assigned_to: '',
  priority: 'medium',
  start_date: '',
  expected_completion_date: '',
  description: ''
})

// 完成维护任务表单
const completeDialogVisible = ref(false)
const currentTask = ref(null)
const completeFormRef = ref(null)
const completeForm = reactive({
  completion_date: '',
  result: ''
})

// 表单验证规则
const rules = {
  fault_report_id: [
    { required: true, message: '请选择故障报告', trigger: 'change' }
  ],
  assigned_to: [
    { required: true, message: '请选择负责人', trigger: 'change' }
  ],
  priority: [
    { required: true, message: '请选择优先级', trigger: 'change' }
  ],
  start_date: [
    { required: true, message: '请选择开始日期', trigger: 'change' }
  ],
  expected_completion_date: [
    { required: true, message: '请选择预计完成日期', trigger: 'change' }
  ],
  description: [
    { required: true, message: '请输入维护说明', trigger: 'blur' }
  ]
}

const completeRules = {
  completion_date: [
    { required: true, message: '请选择完成日期', trigger: 'change' }
  ],
  result: [
    { required: true, message: '请输入维护结果', trigger: 'blur' }
  ]
}

// 获取维护任务列表
const fetchMaintenanceList = async () => {
  try {
    loading.value = true
    const response = await axios.get('/api/maintenance', {
      params: {
        page: currentPage.value,
        per_page: pageSize.value,
        ...searchForm
      }
    })
    maintenanceList.value = response.data.data
    total.value = response.data.total
  } catch (error) {
    ElMessage.error('获取维护任务列表失败')
  } finally {
    loading.value = false
  }
}

// 获取设备列表
const fetchEquipmentList = async () => {
  try {
    const response = await axios.get('/api/equipment', {
      params: { per_page: 1000 }
    })
    equipmentList.value = response.data.data || []
  } catch (error) {
    ElMessage.error('获取设备列表失败')
  }
}

// 获取用户列表
const fetchUsers = async () => {
  try {
    const response = await axios.get('/api/users')
    users.value = response.data
  } catch (error) {
    ElMessage.error('获取用户列表失败')
  }
}

// 获取故障报告列表（只取未完成的）
const fetchFaultReportList = async () => {
  try {
    const response = await axios.get('/api/fault', {
      params: { status: 'pending' }
    })
    faultReportList.value = response.data.data || []
  } catch (error) {
    ElMessage.error('获取故障报告列表失败')
  }
}

// 状态和优先级处理
const getStatusType = (status) => {
  const types = {
    pending: 'warning',
    in_progress: 'primary',
    completed: 'success',
    cancelled: 'info'
  }
  return types[status] || 'info'
}

const getStatusText = (status) => {
  const texts = {
    pending: '待处理',
    in_progress: '进行中',
    completed: '已完成',
    cancelled: '已取消'
  }
  return texts[status] || status
}

const getPriorityType = (priority) => {
  const types = {
    low: 'info',
    medium: 'warning',
    high: 'danger',
    urgent: 'danger'
  }
  return types[priority] || 'info'
}

const getPriorityText = (priority) => {
  const texts = {
    low: '低',
    medium: '中',
    high: '高',
    urgent: '紧急'
  }
  return texts[priority] || priority
}

// 搜索和重置
const handleSearch = () => {
  currentPage.value = 1
  fetchMaintenanceList()
}

const resetSearch = () => {
  searchForm.equipment_name = ''
  searchForm.status = ''
  searchForm.priority = ''
  handleSearch()
}

// 分页处理
const handleSizeChange = (val) => {
  pageSize.value = val
  fetchMaintenanceList()
}

const handleCurrentChange = (val) => {
  currentPage.value = val
  fetchMaintenanceList()
}

// 创建维护任务
const handleCreateTask = () => {
  dialogType.value = 'create'
  Object.assign(maintenanceForm, {
    fault_report_id: '',
    assigned_to: '',
    priority: 'medium',
    start_date: '',
    expected_completion_date: '',
    description: ''
  })
  dialogVisible.value = true
}

// 编辑维护任务
const handleEdit = (row) => {
  dialogType.value = 'edit'
  Object.assign(maintenanceForm, {
    ...row,
    assigned_to: Number(row.assigned_to),
    expected_completion_date: row.expected_completion_date ? dayjs(row.expected_completion_date).format('YYYY-MM-DD') : ''
  })
  dialogVisible.value = true
}

// 提交维护任务表单
const handleSubmit = async () => {
  try {
    // 处理日期格式，确保 expected_completion_date 为 YYYY-MM-DD
    if (maintenanceForm.expected_completion_date) {
      maintenanceForm.expected_completion_date = dayjs(maintenanceForm.expected_completion_date).format('YYYY-MM-DD')
    }
    if (dialogType.value === 'create') {
      await axios.post('/api/maintenance', maintenanceForm)
      ElMessage.success('创建成功')
    } else {
      await axios.put(`/api/maintenance/${maintenanceForm.id}`, maintenanceForm)
      ElMessage.success('更新成功')
    }
    dialogVisible.value = false
    fetchMaintenanceList()
  } catch (error) {
    ElMessage.error(error.response?.data?.error || '操作失败')
  }
}

// 完成维护任务
const handleComplete = (row) => {
  currentTask.value = row
  completeForm.completion_date = ''
  completeForm.result = ''
  completeDialogVisible.value = true
}

const submitComplete = async () => {
  try {
    await axios.post(`/api/maintenance/${currentTask.value.id}/complete`, completeForm)
    ElMessage.success('维护任务已完成')
    completeDialogVisible.value = false
    fetchMaintenanceList()
  } catch (error) {
    ElMessage.error(error.response?.data?.error || '操作失败')
  }
}

// 取消维护任务
const handleCancel = (row) => {
  ElMessageBox.confirm(
    '确定要取消该维护任务吗？',
    '警告',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    }
  ).then(async () => {
    try {
      await axios.post(`/api/maintenance/${row.id}/cancel`)
      ElMessage.success('维护任务已取消')
      fetchMaintenanceList()
    } catch (error) {
      ElMessage.error(error.response?.data?.error || '操作失败')
    }
  })
}

onMounted(() => {
  fetchMaintenanceList()
  fetchEquipmentList()
  fetchUsers()
  fetchFaultReportList()
})
</script>

<style scoped>
.maintenance {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.search-form {
  margin-bottom: 20px;
}

.pagination {
  margin-top: 20px;
  text-align: right;
}
</style> 