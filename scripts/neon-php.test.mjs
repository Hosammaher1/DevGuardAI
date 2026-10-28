import assert from 'node:assert/strict';
import test from 'node:test';
import { createPhpEnv } from './neon-php.mjs';

const pooled = 'postgresql://user:secret@ep-quiet-hill-123-pooler.us-east-2.aws.neon.tech/neondb?sslmode=require';
const direct = 'postgresql://user:secret@ep-quiet-hill-123.us-east-2.aws.neon.tech/neondb?sslmode=require';

test('app commands use the pooled URL and pass the Neon endpoint to PHP', () => {
  const env = createPhpEnv('serve', {
    DATABASE_URL: pooled,
    DATABASE_URL_UNPOOLED: direct,
    APP_ENV: 'local',
  });

  assert.equal(env.DB_CONNECTION, 'pgsql');
  assert.equal(env.DB_URL, pooled);
  assert.equal(env.PGOPTIONS, 'endpoint=ep-quiet-hill-123');
  assert.equal(env.APP_ENV, 'local');
});

test('migrations use the direct URL', () => {
  const env = createPhpEnv('migrate', {
    DATABASE_URL: pooled,
    DATABASE_URL_UNPOOLED: direct,
  });

  assert.equal(env.DB_URL, direct);
  assert.equal(env.PGOPTIONS, 'endpoint=ep-quiet-hill-123');
});

test('missing or mismatched URLs fail before starting PHP', () => {
  assert.throws(() => createPhpEnv('serve', { DATABASE_URL: pooled }), /DATABASE_URL_UNPOOLED/);
  assert.throws(() => createPhpEnv('serve', {
    DATABASE_URL: pooled,
    DATABASE_URL_UNPOOLED: 'postgresql://user:secret@ep-other-456.us-east-2.aws.neon.tech/neondb',
  }), /same Neon endpoint/);
});
