import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  root: path.resolve(__dirname, 'src'),
  build: {
    outDir: path.resolve(__dirname, '../../public/vendor/admin-core'),
    emptyOutDir: true,
    rollupOptions: {
      input: path.resolve(__dirname, 'src/main.jsx')
    }
  },
  resolve: {
    alias: {
      '@admin-core': path.resolve(__dirname, 'src')
    }
  }
});
