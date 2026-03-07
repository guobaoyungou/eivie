import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import electron from 'vite-plugin-electron'
import renderer from 'vite-plugin-electron-renderer'
import { resolve } from 'path'

export default defineConfig({
  plugins: [
    vue(),
    electron([
      {
        entry: 'main/index.ts',
        vite: {
          build: {
            outDir: 'dist/main',
            rollupOptions: {
              external: ['electron', 'chokidar', 'electron-log', 'electron-store']
            }
          }
        }
      },
      {
        entry: 'preload/index.ts',
        onstart(options) {
          options.reload()
        },
        vite: {
          build: {
            outDir: 'dist/preload',
            rollupOptions: {
              external: ['electron']
            }
          }
        }
      }
    ]),
    renderer()
  ],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'renderer'),
      '@main': resolve(__dirname, 'main'),
      '@preload': resolve(__dirname, 'preload'),
      '@renderer': resolve(__dirname, 'renderer')
    }
  },
  root: 'renderer',
  build: {
    outDir: '../dist/renderer',
    emptyOutDir: true
  },
  server: {
    port: 5173
  }
})
