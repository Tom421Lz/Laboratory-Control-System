<template>
  <div class="equipment">
    <div class="page-header">
      <h2>设备管理</h2>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        添加设备
      </el-button>
    </div>
    
    <el-card>
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="设备名称">
          <el-input v-model="searchForm.name" placeholder="请输入设备名称" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="请选择状态">
            <el-option label="全部" value="" />
            <el-option label="正常" value="operational" />
            <el-option label="故障" value="faulty" />
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
        :data="equipmentList"
        style="width: 100%"
      >
        <el-table-column prop="name" label="设备名称" />
        <el-table-column prop="serial_number" label="序列号" />
        <el-table-column prop="status" label="状态">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="laboratory.name" label="所属实验室" />
        <el-table-column prop="purchase_date" label="购买日期" />
        <el-table-column prop="warranty_expiry" label="保修到期" />
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
                type="warning"
                @click="handleReportFault(row)"
              >
                报修
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
    
    <!-- 添加/编辑设备对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogType === 'add' ? '添加设备' : '编辑设备'"
      width="500px"
    >
      <el-form
        ref="equipmentForm"
        :model="equipmentForm"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="设备名称" prop="name">
          <el-input v-model="equipmentForm.name" />
        </el-form-item>
        <el-form-item label="序列号" prop="serial_number">
          <el-input v-model="equipmentForm.serial_number" />
        </el-form-item>
        <el-form-item label="所属实验室" prop="laboratory_id">
          <el-select v-model="equipmentForm.laboratory_id" placeholder="请选择实验室">
            <el-option
              v-for="lab in laboratories"
              :key="lab.id"
              :label="lab.name"
              :value="lab.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="购买日期" prop="purchase_date">
          <el-date-picker
            v-model="equipmentForm.purchase_date"
            type="date"
            placeholder="选择日期"
          />
        </el-form-item>
        <el-form-item label="保修到期" prop="warranty_expiry">
          <el-date-picker
            v-model="equipmentForm.warranty_expiry"
            type="date"
            placeholder="选择日期"
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
    
    <!-- 报修对话框 -->
    <el-dialog
      v-model="faultDialogVisible"
      title="设备报修"
      width="500px"
    >
      <el-form
        ref="faultForm"
        :model="faultForm"
        :rules="faultRules"
        label-width="100px"
      >
        <el-form-item label="故障描述" prop="description">
          <el-input
            v-model="faultForm.description"
            type="textarea"
            rows="4"
          />
        </el-form-item>
        <el-form-item label="严重程度" prop="severity">
          <el-select v-model="faultForm.severity" placeholder="请选择严重程度">
            <el-option label="轻微" value="low" />
            <el-option label="中等" value="medium" />
            <el-option label="严重" value="high" />
            <el-option label="紧急" value="critical" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="faultDialogVisible = false">取消</el-button>
          <el-button type="primary" @click="submitFaultReport">
            提交
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
const equipmentList = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const laboratories = ref([])

// 搜索表单
const searchForm = reactive({
  name: '',
  status: ''
})

// 设备表单
const dialogVisible = ref(false)
const dialogType = ref('add')
const equipmentForm = reactive({
  name: '',
  serial_number: '',
  laboratory_id: '',
  purchase_date: '',
  warranty_expiry: ''
})

// 报修表单
const faultDialogVisible = ref(false)
const currentEquipment = ref(null)
const faultForm = reactive({
  description: '',
  severity: 'medium'
})

// 表单验证规则
const rules = {
  name: [
    { required: true, message: '请输入设备名称', trigger: 'blur' }
  ],
  laboratory_id: [
    { required: true, message: '请选择所属实验室', trigger: 'change' }
  ]
}

const faultRules = {
  description: [
    { required: true, message: '请输入故障描述', trigger: 'blur' }
  ],
  severity: [
    { required: true, message: '请选择严重程度', trigger: 'change' }
  ]
}

// 获取设备列表
const fetchEquipmentList = async () => {
  try {
    loading.value = true
    const response = await axios.get('/api/equipment', {
      params: {
        page: currentPage.value,
        per_page: pageSize.value,
        ...searchForm
      }
    })
    equipmentList.value = response.data.data
    total.value = response.data.total
  } catch (error) {
    ElMessage.error('获取设备列表失败')
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

// 状态处理
const getStatusType = (status) => {
  const types = {
    operational: 'success',
    faulty: 'danger',
    maintenance: 'warning',
    disposed: 'info'
  }
  return types[status] || 'info'
}

const getStatusText = (status) => {
  const texts = {
    operational: '正常',
    faulty: '故障',
    maintenance: '维护中',
    disposed: '已报废'
  }
  return texts[status] || status
}

// 搜索和重置
const handleSearch = () => {
  currentPage.value = 1
  fetchEquipmentList()
}

const resetSearch = () => {
  searchForm.name = ''
  searchForm.status = ''
  handleSearch()
}

// 分页处理
const handleSizeChange = (val) => {
  pageSize.value = val
  fetchEquipmentList()
}

const handleCurrentChange = (val) => {
  currentPage.value = val
  fetchEquipmentList()
}

// 添加设备
const handleAdd = () => {
  dialogType.value = 'add'
  Object.assign(equipmentForm, {
    name: '',
    serial_number: '',
    laboratory_id: '',
    purchase_date: '',
    warranty_expiry: ''
  })
  dialogVisible.value = true
}

// 编辑设备
const handleEdit = (row) => {
  dialogType.value = 'edit'
  Object.assign(equipmentForm, row)
  dialogVisible.value = true
}

// 提交设备表单
const handleSubmit = async () => {
  try {
    if (dialogType.value === 'add') {
      await axios.post('/api/equipment', equipmentForm)
      ElMessage.success('添加成功')
    } else {
      await axios.put(`/api/equipment/${equipmentForm.id}`, equipmentForm)
      ElMessage.success('更新成功')
    }
    dialogVisible.value = false
    fetchEquipmentList()
  } catch (error) {
    ElMessage.error(error.response?.data?.error || '操作失败')
  }
}

// 删除设备
const handleDelete = (row) => {
  ElMessageBox.confirm(
    '确定要删除该设备吗？',
    '警告',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    }
  ).then(async () => {
    try {
      await axios.delete(`/api/equipment/${row.id}`)
      ElMessage.success('删除成功')
      fetchEquipmentList()
    } catch (error) {
      ElMessage.error(error.response?.data?.error || '删除失败')
    }
  })
}

// 报修处理
const handleReportFault = (row) => {
  currentEquipment.value = row
  faultForm.description = ''
  faultForm.severity = 'medium'
  faultDialogVisible.value = true
}

const submitFaultReport = async () => {
  try {
    await axios.post('/api/equipment/fault', {
      equipment_id: currentEquipment.value.id,
      ...faultForm
    })
    ElMessage.success('报修成功')
    faultDialogVisible.value = false
    fetchEquipmentList()
  } catch (error) {
    ElMessage.error(error.response?.data?.error || '报修失败')
  }
}

onMounted(() => {
  fetchEquipmentList()
  fetchLaboratories()
})
</script>

<style scoped>
.equipment {
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