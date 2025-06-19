import axios from 'axios'

// 维护任务API
export const fetchMaintenanceListAPI = (params) => {
  return axios.get('/api/maintenance', { params })
}

export const fetchEquipmentListAPI = () => {
  return axios.get('/api/equipment')
}

export const fetchUsersAPI = () => {
  return axios.get('/api/users')
}
export const fetchLaboratoriesAPI = () => {
  return axios.get('/api/laboratories')
}
export const createMaintenanceAPI = (data) => {
  return axios.post('/api/maintenance', data)
}

export const updateMaintenanceAPI = (id, data) => {
  return axios.put(`/api/maintenance/${id}`, data)
}

export const completeMaintenanceAPI = (id, data) => {
  return axios.post(`/api/maintenance/${id}/complete`, data)
}

export const cancelMaintenanceAPI = (id) => {
  return axios.post(`/api/maintenance/${id}/cancel`)
}