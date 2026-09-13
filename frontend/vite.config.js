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
    // Mirrors vercel.json's rewrites: a handful of API responses embed a
    // relative /api/files/... URL (avatar, QR code, payment proof) instead
    // of an absolute one, specifically so the browser requests it through
    // the same origin the page was loaded from - same-origin is what lets
    // the Sanctum session cookie travel. Without this proxy, a relative
    // path in dev would hit the Vite dev server itself (5173) instead of
    // the Laravel API and 404.
    proxy: {
      '/api': { target: 'http://localhost:8000', changeOrigin: true },
      '/sanctum': { target: 'http://localhost:8000', changeOrigin: true },
    },
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
