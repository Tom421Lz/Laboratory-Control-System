<template>
  <div class="dashboard">
    <el-row :gutter="20">
      <el-col :span="6">
        <el-card shadow="hover">
          <template #header>
            <div class="card-header">
              <span>设备总数</span>
              <el-icon><Tools /></el-icon>
            </div>
          </template>
          <div class="card-value">{{ stats.equipmentCount }}</div>
        </el-card>
      </el-col>
      
      <el-col :span="6">
        <el-card shadow="hover">
          <template #header>
            <div class="card-header">
              <span>故障设备</span>
              <el-icon><Warning /></el-icon>
            </div>
          </template>
          <div class="card-value warning">{{ stats.faultyEquipmentCount }}</div>
        </el-card>
      </el-col>
      
      <el-col :span="6">
        <el-card shadow="hover">
          <template #header>
            <div class="card-header">
              <span>待处理维护</span>
              <el-icon><Setting /></el-icon>
            </div>
          </template>
          <div class="card-value warning">{{ stats.pendingMaintenanceCount }}</div>
        </el-card>
      </el-col>
      
      <el-col :span="6">
        <el-card shadow="hover">
          <template #header>
            <div class="card-header">
              <span>资源总数</span>
              <el-icon><Box /></el-icon>
            </div>
          </template>
          <div class="card-value">{{ stats.resourceCount }}</div>
        </el-card>
      </el-col>
    </el-row>
    
    <el-row :gutter="20" class="mt-20">
      <el-col :span="24">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>近30天故障与维护趋势</span>
            </div>
          </template>
          <v-chart :option="lineChartOption" autoresize style="height: 320px; width: 100%" />
        </el-card>
      </el-col>
    </el-row>
    
    <el-row :gutter="20" class="mt-20">
      <el-col :span="12">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>最近故障报告</span>
            </div>
          </template>
          <el-table :data="recentFaults" style="width: 100%">
            <el-table-column prop="equipment_name" label="设备名称" />
            <el-table-column prop="severity" label="严重程度">
              <template #default="{ row }">
                <el-tag :type="getSeverityType(row.severity)">
                  {{ row.severity }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="created_at" label="报告时间" />
          </el-table>
        </el-card>
      </el-col>
      
      <el-col :span="12">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>最近维护任务</span>
            </div>
          </template>
          <el-table :data="recentMaintenance" style="width: 100%">
            <el-table-column prop="equipment_name" label="设备名称" />
            <el-table-column prop="status" label="状态">
              <template #default="{ row }">
                <el-tag :type="getStatusType(row.status)">
                  {{ row.status }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="assigned_to" label="负责人" />
          </el-table>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Tools, Warning, Setting, Box } from '@element-plus/icons-vue'
import axios from 'axios'
import { use } from 'echarts/core'
import VChart from 'vue-echarts'
import { LineChart } from 'echarts/charts'
import { GridComponent, TooltipComponent, LegendComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'

use([LineChart, GridComponent, TooltipComponent, LegendComponent, CanvasRenderer])

const stats = ref({
  equipmentCount: 0,
  faultyEquipmentCount: 0,
  pendingMaintenanceCount: 0,
  resourceCount: 0
})

// 最近故障报告
const recentFaults = ref([])

// 最近维护任务 
const recentMaintenance = ref([])

const recentStats = ref([])
const lineChartOption = ref({})

const getSeverityType = (severity) => {
  const types = {
    low: 'info',
    medium: 'warning',
    high: 'danger',
    critical: 'danger'
  }
  return types[severity] || 'info'
}

const getStatusType = (status) => {
  const types = {
    pending: 'warning',
    in_progress: 'primary',
    completed: 'success',
    cancelled: 'info'
  }
  return types[status] || 'info'
}

const fetchDashboardData = async () => {
  try {
    const response = await axios.get('/api/dashboard')
    const { stats: statsData, recentFaults: faults, recentMaintenance: maintenance, recentStats: statsArr } = response.data
    
    stats.value = statsData
    recentFaults.value = faults
    recentMaintenance.value = maintenance
    recentStats.value = statsArr
    // 配置折线图
    lineChartOption.value = {
      tooltip: { trigger: 'axis' },
      legend: { data: ['故障报告', '维护完成'] },
      grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
      xAxis: {
        type: 'category',
        data: statsArr.map(item => item.date)
      },
      yAxis: { type: 'value' },
      series: [
        {
          name: '故障报告',
          type: 'line',
          data: statsArr.map(item => item.fault_count),
          smooth: true
        },
        {
          name: '维护完成',
          type: 'line',
          data: statsArr.map(item => item.maintenance_count),
          smooth: true
        }
      ]
    }
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error)
  }
}

onMounted(() => {
  fetchDashboardData()
})
</script>

<style scoped>
.dashboard {
  padding: 20px;
}

.mt-20 {
  margin-top: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-value {
  font-size: 24px;
  font-weight: bold;
  text-align: center;
  color: #409EFF;
}

.card-value.warning {
  color: #E6A23C;
}

.el-card {
  margin-bottom: 20px;
}
</style> 