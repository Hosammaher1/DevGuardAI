import { spawn } from 'node:child_process';
import { resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

function endpointFor(rawUrl, name) {
  if (!rawUrl) throw new Error(`${name} is missing; run neon deploy`);

  const url = new URL(rawUrl);
  if (!['postgres:', 'postgresql:'].includes(url.protocol) || !/^ep-[a-z0-9-]+(?:-pooler)?\..+\.neon\.tech$/.test(url.hostname)) {
    throw new Error(`${name} must be a Neon Postgres URL`);
  }

  return url.hostname.split('.')[0].replace(/-pooler$/, '');
}

export function createPhpEnv(command, sourceEnv = process.env) {
  const pooled = sourceEnv.DATABASE_URL;
  const direct = sourceEnv.DATABASE_URL_UNPOOLED;
  const pooledEndpoint = endpointFor(pooled, 'DATABASE_URL');
  const directEndpoint = endpointFor(direct, 'DATABASE_URL_UNPOOLED');
  if (pooledEndpoint !== directEndpoint) {
    throw new Error('Pooled and direct URLs must use the same Neon endpoint');
  }

  const migration = command.startsWith('migrate');
  return {
    ...sourceEnv,
    DB_CONNECTION: 'pgsql',
    DB_URL: migration ? direct : pooled,
    // Older Windows libpq releases need this at process start to route Neon TLS.
    PGOPTIONS: `endpoint=${pooledEndpoint}`,
  };
}

if (process.argv[1] && pathToFileURL(resolve(process.argv[1])).href === import.meta.url) {
  const [command, ...args] = process.argv.slice(2);
  if (!command) {
    console.error('Provide an Artisan command.');
    process.exitCode = 2;
  } else {
    try {
      const env = createPhpEnv(command);
      const backend = fileURLToPath(new URL('../backend/', import.meta.url));
      const child = spawn('php', ['artisan', command, ...args], {
        cwd: backend,
        env,
        stdio: 'inherit',
      });
      child.on('error', (error) => {
        console.error(`Could not start PHP: ${error.message}`);
        process.exitCode = 1;
      });
      child.on('exit', (code) => { process.exitCode = code ?? 1; });
    } catch (error) {
      console.error(error.message);
      process.exitCode = 1;
    }
  }
}
