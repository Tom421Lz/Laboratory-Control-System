<template>
  <div class="resources">
    <div class="page-header">
      <h2>资源管理</h2>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        添加资源
      </el-button>
    </div>
    
    <el-card>
      <el-form 
        ref="searchFormRef"
        :inline="true" 
        :model="searchForm"
        class="search-form"
      >
        <el-form-item label="资源名称">
          <el-input 
            v-model="searchForm.name" 
            placeholder="请输入资源名称" 
            clearable
          />
        </el-form-item>
        <el-form-item label="类型">
          <el-select 
            v-model="searchForm.type" 
            placeholder="请选择类型"
            clearable
          >
            <el-option label="全部" value="" />
            <el-option label="钥匙" value="key" />
            <el-option label="服务器" value="server" />
            <el-option label="桌椅" value="furniture" />
            <el-option label="书籍" value="book" />
            <el-option label="其他" value="other" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select 
            v-model="searchForm.status" 
            placeholder="请选择状态"
            clearable
          >
            <el-option label="全部" value="" />
            <el-option label="可用" value="available" />
            <el-option label="使用中" value="in_use" />
            <el-option label="维护中" value="maintenance" />
            <el-option label="已报废" value="disposed" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="resetSearch">重置</el-button>
        </el-form-item>
      </el-form>
      
      <el-table
        v-loading="loading"
        :data="resourceList"
        style="width: 100%"
        border
        stripe
      >
        <el-table-column prop="name" label="资源名称" min-width="120" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            {{ getTypeText(row.type) }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="laboratory.name" label="所属实验室" min-width="150" />
        <el-table-column prop="description" label="描述" show-overflow-tooltip min-width="200" />
        <el-table-column label="操作" width="300" fixed="right">
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
                :disabled="row.status !== 'available'"
                @click="handleAllocate(row)"
              >
                分配
              </el-button>
              <el-button
                size="small"
                type="danger"
                @click="handleDelete(row)"
              >
                删除
              </el-button>
              <el-button
                size="small"
                type="warning"
                v-if="row.status === 'in_use' && row.current_allocation_id"
                @click="handleReturn(row)"
              >
                归还
              </el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>
      
      <div class="pagination">
        <el-pagination
          :current-page="currentPage"
          :page-size="pageSize"
          :total="total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>
    
    <!-- 添加/编辑资源对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogType === 'add' ? '添加资源' : '编辑资源'"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="resourceFormRef"
        :model="resourceForm"
        :rules="resourceRules"
        label-width="100px"
      >
        <el-form-item label="资源名称" prop="name">
          <el-input v-model="resourceForm.name" />
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-select 
            v-model="resourceForm.type" 
            placeholder="请选择类型"
          >
            <el-option label="钥匙" value="key" />
            <el-option label="服务器" value="server" />
            <el-option label="桌椅" value="furniture" />
            <el-option label="书籍" value="book" />
            <el-option label="其他" value="other" />
          </el-select>
        </el-form-item>
        <el-form-item label="所属实验室" prop="laboratory_id">
          <el-select 
            v-model="resourceForm.laboratory_id" 
            placeholder="请选择实验室"
            filterable
          >
            <el-option
              v-for="lab in laboratories"
              :key="lab.id"
              :label="lab.name"
              :value="lab.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="resourceForm.description"
            type="textarea"
            rows="3"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">取消</el-button>
          <el-button 
            type="primary" 
            @click="handleSubmit"
            :loading="submitting"
          >
            确定
          </el-button>
        </span>
      </template>
    </el-dialog>
    
    <!-- 分配资源对话框 -->
    <el-dialog
      v-model="allocateDialogVisible"
      title="分配资源"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="allocateFormRef"
        :model="allocateForm"
        :rules="allocateRules"
        label-width="100px"
      >
        <el-form-item label="分配用户" prop="user_id">
          <el-select 
            v-model="allocateForm.user_id" 
            placeholder="请选择用户"
            filterable
          >
            <el-option
              v-for="user in users"
              :key="user.id"
              :label="user.username"
              :value="user.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="预计归还" prop="expected_return_date">
          <el-date-picker
            v-model="allocateForm.expected_return_date"
            type="date"
            placeholder="选择日期"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>
        <el-form-item label="备注" prop="notes">
          <el-input
            v-model="allocateForm.notes"
            type="textarea"
            rows="3"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="allocateDialogVisible = false">取消</el-button>
          <el-button 
            type="primary" 
            @click="submitAllocation"
            :loading="submittingAllocation"
          >
            确定
          </el-button>
        </span>
      </template>
    </el-dialog>
    
    <!-- 归还资源对话框 -->
    <el-dialog v-model="returnDialogVisible" title="归还资源" width="500px">
      <el-form ref="returnFormRef" :model="returnForm" :rules="returnRules" label-width="100px">
        <el-form-item label="归还备注" prop="notes">
          <el-input v-model="returnForm.notes" type="textarea" rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="returnDialogVisible = false">取消</el-button>
          <el-button type="primary" @click="submitReturn" :loading="submittingReturn">确定</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue'
import { Plus } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import axios from 'axios'
import { debounce } from 'lodash-es'

// 数据列表
const resourceList = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const laboratories = ref([])
const users = ref([])

// 搜索表单
const searchFormRef = ref(null)
const searchForm = reactive({
  name: '',
  type: '',
  status: ''
})

// 资源表单
const dialogVisible = ref(false)
const dialogType = ref('add')
const resourceFormRef = ref(null)
const submitting = ref(false)
const resourceForm = reactive({
  name: '',
  type: '',
  laboratory_id: '',
  description: ''
})

// 分配表单
const allocateDialogVisible = ref(false)
const allocateFormRef = ref(null)
const currentResource = ref(null)
const submittingAllocation = ref(false)
const allocateForm = reactive({
  user_id: '',
  expected_return_date: '',
  notes: ''
})

// 归还表单
const returnDialogVisible = ref(false)
const returnFormRef = ref(null)
const currentReturnAllocationId = ref(null)
const submittingReturn = ref(false)
const returnForm = reactive({ notes: '' })
const returnRules = { notes: [{ max: 200, message: '不能超过 200 个字符', trigger: 'blur' }] }

// 表单验证规则
const resourceRules = {
  name: [
    { required: true, message: '请输入资源名称', trigger: 'blur' },
    { min: 2, max: 50, message: '长度在 2 到 50 个字符', trigger: 'blur' }
  ],
  type: [
    { required: true, message: '请选择资源类型', trigger: 'change' }
  ],
  laboratory_id: [
    { required: true, message: '请选择所属实验室', trigger: 'change' }
  ],
  description: [
    { max: 500, message: '不能超过 500 个字符', trigger: 'blur' }
  ]
}

const allocateRules = {
  user_id: [
    { required: true, message: '请选择分配用户', trigger: 'change' }
  ],
  expected_return_date: [
    { required: true, message: '请选择预计归还日期', trigger: 'change' }
  ],
  notes: [
    { max: 200, message: '不能超过 200 个字符', trigger: 'blur' }
  ]
}

// 获取资源列表
const fetchResourceList = async () => {
  try {
    loading.value = true
    const response = await axios.get('/api/resources', {
      params: {
        page: currentPage.value,
        per_page: pageSize.value,
        ...searchForm
      }
    })
    resourceList.value = response.data.data
    total.value = response.data.total
  } catch (error) {
    if (error.response) {
      ElMessage.error(error.response.data.message || '获取资源列表失败')
    } else if (error.request) {
      ElMessage.error('网络错误，请检查网络连接')
    } else {
      ElMessage.error('请求失败，请重试')
    }
  } finally {
    loading.value = false
  }
}

// 获取实验室列表
const fetchLaboratories = async () => {
  try {
    const response = await axios.get('/api/laboratories')
    laboratories.value = response.data
  } catch (error) {
    ElMessage.error('获取实验室列表失败')
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

// 类型和状态处理
const getTypeText = (type) => {
  const types = {
    key: '钥匙',
    server: '服务器',
    furniture: '桌椅',
    book: '书籍',
    other: '其他'
  }
  return types[type] || type
}

const getStatusType = (status) => {
  const types = {
    available: 'success',
    in_use: 'warning',
    maintenance: 'info',
    disposed: 'danger'
  }
  return types[status] || 'info'
}

const getStatusText = (status) => {
  const texts = {
    available: '可用',
    in_use: '使用中',
    maintenance: '维护中',
    disposed: '已报废'
  }
  return texts[status] || status
}

// 搜索和重置
const handleSearch = debounce(() => {
  currentPage.value = 1
  fetchResourceList()
}, 300)

const resetSearch = () => {
  searchForm.name = ''
  searchForm.type = ''
  searchForm.status = ''
  handleSearch()
}

// 分页处理
const handleSizeChange = (val) => {
  pageSize.value = val
  currentPage.value = 1
  fetchResourceList()
}

const handleCurrentChange = (val) => {
  currentPage.value = val
  fetchResourceList()
}

// 添加资源
const handleAdd = () => {
  dialogType.value = 'add'
  nextTick(() => {
    resourceFormRef.value?.resetFields()
  })
  Object.assign(resourceForm, {
    name: '',
    type: '',
    laboratory_id: '',
    description: ''
  })
  dialogVisible.value = true
}

// 编辑资源
const handleEdit = (row) => {
  dialogType.value = 'edit'
  nextTick(() => {
    resourceFormRef.value?.clearValidate()
  })
  Object.assign(resourceForm, row)
  dialogVisible.value = true
}

// 提交资源表单
const handleSubmit = async () => {
  try {
    const valid = await resourceFormRef.value.validate()
    if (!valid) return
    
    submitting.value = true
    if (dialogType.value === 'add') {
      await axios.post('/api/resources', resourceForm)
      ElMessage.success('添加成功')
    } else {
      await axios.put(`/api/resources/${resourceForm.id}`, resourceForm)
      ElMessage.success('更新成功')
    }
    dialogVisible.value = false
    fetchResourceList()
  } catch (error) {
    let errorMessage = '操作失败'
    if (error.response) {
      errorMessage = error.response.data.message || errorMessage
    }
    ElMessage.error(errorMessage)
  } finally {
    submitting.value = false
  }
}

// 删除资源
const handleDelete = (row) => {
  ElMessageBox.confirm(
    `确定要删除资源 "${row.name}" 吗？`,
    '警告',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
      beforeClose: async (action, instance, done) => {
        if (action === 'confirm') {
          instance.confirmButtonLoading = true
          try {
            await axios.delete(`/api/resources/${row.id}`)
            ElMessage.success('删除成功')
            fetchResourceList()
            done()
          } catch (error) {
            ElMessage.error(error.response?.data?.message || '删除失败')
          } finally {
            instance.confirmButtonLoading = false
          }
        } else {
          done()
        }
      }
    }
  )
}

// 分配资源
const handleAllocate = (row) => {
  currentResource.value = row
  nextTick(() => {
    allocateFormRef.value?.resetFields()
  })
  allocateForm.user_id = ''
  allocateForm.expected_return_date = ''
  allocateForm.notes = ''
  allocateDialogVisible.value = true
}

const submitAllocation = async () => {
  try {
    const valid = await allocateFormRef.value.validate()
    if (!valid) return
    
    submittingAllocation.value = true
    await axios.post('/api/resources/allocate', {
      resource_id: currentResource.value.id,
      ...allocateForm
    })
    ElMessage.success('分配成功')
    allocateDialogVisible.value = false
    fetchResourceList()
  } catch (error) {
    let errorMessage = '分配失败'
    if (error.response) {
      errorMessage = error.response.data.message || errorMessage
    }
    ElMessage.error(errorMessage)
  } finally {
    submittingAllocation.value = false
  }
}

// 归还资源
const handleReturn = (row) => {
  currentReturnAllocationId.value = row.current_allocation_id || ''
  returnForm.notes = ''
  returnDialogVisible.value = true
}

const submitReturn = async () => {
  try {
    const valid = await returnFormRef.value.validate()
    if (!valid) return
    submittingReturn.value = true
    await axios.post('/api/resources/return', {
      allocation_id: currentReturnAllocationId.value,
      ...returnForm
    })
    ElMessage.success('归还成功')
    returnDialogVisible.value = false
    fetchResourceList()
  } catch (error) {
    let errorMessage = '归还失败'
    if (error.response) errorMessage = error.response.data.message || errorMessage
    ElMessage.error(errorMessage)
  } finally {
    submittingReturn.value = false
  }
}

onMounted(() => {
  fetchResourceList()
  fetchLaboratories()
  fetchUsers()
})
</script>

<style scoped>
.resources {
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

@media (max-width: 768px) {
  .search-form :deep(.el-form-item) {
    margin-right: 0;
    margin-bottom: 10px;
    width: 100%;
  }
  
  .search-form :deep(.el-form-item__content) {
    width: 100%;
  }
  
  .search-form :deep(.el-input),
  .search-form :deep(.el-select) {
    width: 100%;
  }
}
</style>