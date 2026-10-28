import { copyFileSync, cpSync, mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const source = fileURLToPath(new URL('../frontend/dist/', import.meta.url));
const destination = fileURLToPath(new URL('../backend/public/', import.meta.url));

mkdirSync(destination, { recursive: true });
copyFileSync(`${source}index.html`, `${destination}index.html`);
cpSync(`${source}assets`, `${destination}assets`, { recursive: true, force: true });
console.log('Copied frontend build to Laravel public directory.');
