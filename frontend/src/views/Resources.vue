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
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="资源名称">
          <el-input v-model="searchForm.name" placeholder="请输入资源名称" />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="searchForm.type" placeholder="请选择类型">
            <el-option label="全部" value="" />
            <el-option label="钥匙" value="key" />
            <el-option label="服务器" value="server" />
            <el-option label="桌椅" value="furniture" />
            <el-option label="书籍" value="book" />
            <el-option label="其他" value="other" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="请选择状态">
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
      >
        <el-table-column prop="name" label="资源名称" />
        <el-table-column prop="type" label="类型">
          <template #default="{ row }">
            {{ getTypeText(row.type) }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="laboratory.name" label="所属实验室" />
        <el-table-column prop="description" label="描述" show-overflow-tooltip />
        <el-table-column label="操作" width="300">
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
    
    <!-- 添加/编辑资源对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogType === 'add' ? '添加资源' : '编辑资源'"
      width="500px"
    >
      <el-form
        ref="resourceForm"
        :model="resourceForm"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="资源名称" prop="name">
          <el-input v-model="resourceForm.name" />
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-select v-model="resourceForm.type" placeholder="请选择类型">
            <el-option label="钥匙" value="key" />
            <el-option label="服务器" value="server" />
            <el-option label="桌椅" value="furniture" />
            <el-option label="书籍" value="book" />
            <el-option label="其他" value="other" />
          </el-select>
        </el-form-item>
        <el-form-item label="所属实验室" prop="laboratory_id">
          <el-select v-model="resourceForm.laboratory_id" placeholder="请选择实验室">
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
          <el-button type="primary" @click="handleSubmit">
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
    >
      <el-form
        ref="allocateForm"
        :model="allocateForm"
        :rules="allocateRules"
        label-width="100px"
      >
        <el-form-item label="分配用户" prop="user_id">
          <el-select v-model="allocateForm.user_id" placeholder="请选择用户">
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
          <el-button type="primary" @click="submitAllocation">
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

// 数据列表
const resourceList = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const laboratories = ref([])
const users = ref([])

// 搜索表单
const searchForm = reactive({
  name: '',
  type: '',
  status: ''
})

// 资源表单
const dialogVisible = ref(false)
const dialogType = ref('add')
const resourceForm = reactive({
  name: '',
  type: '',
  laboratory_id: '',
  description: ''
})

// 分配表单
const allocateDialogVisible = ref(false)
const currentResource = ref(null)
const allocateForm = reactive({
  user_id: '',
  expected_return_date: '',
  notes: ''
})

// 表单验证规则
const rules = {
  name: [
    { required: true, message: '请输入资源名称', trigger: 'blur' }
  ],
  type: [
    { required: true, message: '请选择资源类型', trigger: 'change' }
  ],
  laboratory_id: [
    { required: true, message: '请选择所属实验室', trigger: 'change' }
  ]
}

const allocateRules = {
  user_id: [
    { required: true, message: '请选择分配用户', trigger: 'change' }
  ],
  expected_return_date: [
    { required: true, message: '请选择预计归还日期', trigger: 'change' }
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
    ElMessage.error('获取资源列表失败')
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
const handleSearch = () => {
  currentPage.value = 1
  fetchResourceList()
}

const resetSearch = () => {
  searchForm.name = ''
  searchForm.type = ''
  searchForm.status = ''
  handleSearch()
}

// 分页处理
const handleSizeChange = (val) => {
  pageSize.value = val
  fetchResourceList()
}

const handleCurrentChange = (val) => {
  currentPage.value = val
  fetchResourceList()
}

// 添加资源
const handleAdd = () => {
  dialogType.value = 'add'
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
  Object.assign(resourceForm, row)
  dialogVisible.value = true
}

// 提交资源表单
const handleSubmit = async () => {
  try {
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
    ElMessage.error(error.response?.data?.error || '操作失败')
  }
}

// 删除资源
const handleDelete = (row) => {
  ElMessageBox.confirm(
    '确定要删除该资源吗？',
    '警告',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    }
  ).then(async () => {
    try {
      await axios.delete(`/api/resources/${row.id}`)
      ElMessage.success('删除成功')
      fetchResourceList()
    } catch (error) {
      ElMessage.error(error.response?.data?.error || '删除失败')
    }
  })
}

// 分配资源
const handleAllocate = (row) => {
  currentResource.value = row
  allocateForm.user_id = ''
  allocateForm.expected_return_date = ''
  allocateForm.notes = ''
  allocateDialogVisible.value = true
}

const submitAllocation = async () => {
  try {
    await axios.post('/api/resources/allocate', {
      resource_id: currentResource.value.id,
      ...allocateForm
    })
    ElMessage.success('分配成功')
    allocateDialogVisible.value = false
    fetchResourceList()
  } catch (error) {
    ElMessage.error(error.response?.data?.error || '分配失败')
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
</style> 