import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path' // 新增引入

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src') // 关键修复
    }
  },
  server: {
    proxy: {
      '/api': {
        target: 'http://laboratory-control-system/',
        changeOrigin: true,
        rewrite: path => path.replace(/^\/api/, '/api')
      }
    },
    hmr: {
      overlay: false // 禁用错误覆盖层
    }
  }
})