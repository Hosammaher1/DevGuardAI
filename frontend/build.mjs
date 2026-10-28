import { build } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.resolve(__dirname, '../backend/public');

async function run() {
  console.log('Building frontend to ' + outDir + '...');
  await build({
    configFile: false,
    root: __dirname,
    plugins: [react()],
    build: {
      outDir: outDir,
      emptyOutDir: false,
    },
  });
  console.log('Frontend build completed successfully in ' + outDir);
}

run().catch((err) => {
  console.error('Build failed:', err);
  process.exit(1);
});
