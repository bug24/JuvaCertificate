import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

export default defineConfig(({ mode }) => ({
  plugins: [react()],
  base: "/",
  server: mode === "development" ? {
    proxy: {
      "/api": {
        target: "http://127.0.0.1:8088",
        changeOrigin: false,
        secure: false
      }
    }
  } : undefined,
  build: {
    outDir: "dist",
    emptyOutDir: true
  }
}));