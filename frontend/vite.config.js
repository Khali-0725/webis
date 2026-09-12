import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'node:path';

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      // import.meta.dirname, not __dirname: Vite's native config loader (the
      // future default) does not provide CommonJS globals.
      '@': path.resolve(import.meta.dirname, './src'),
    },
  },
  server: {
    port: 5173,
    strictPort: true,
  },
  build: {
    outDir: 'dist',
    sourcemap: false,
    // No manual chunk map: Vite 8 bundles with Rolldown, which splits vendor
    // code sensibly on its own. Hand-tuning chunks here was premature
    // optimisation and the object form is no longer supported.
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./tests/setup.js'],
    include: ['tests/**/*.test.{js,jsx}', 'src/**/*.test.{js,jsx}'],
  },
});
