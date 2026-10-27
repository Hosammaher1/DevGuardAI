import { build } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import os from 'os';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

async function run() {
  const outDir = path.join(os.tmpdir(), 'devguard-frontend-dist');
  await build({
    root: __dirname,
    configFile: false,
    plugins: [react()],
    build: {
      outDir: outDir,
      emptyOutDir: true,
    },
  });
  console.log(`Vite build complete! Output at: ${outDir}`);
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});
