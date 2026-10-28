# Neon Postgres setup

This project links to Neon project `bold-king-71114165`, branch `production`. Neon provides Postgres; Laravel serves the API and built React app.

1. Install the Neon CLI (`npm i -g neon@latest`) and sign in with `neon login`.
2. In the repository root, run `neon init -y --no-agent-setup --org-id org-silent-union-42023949 --project-id bold-king-71114165 --branch production --services postgres`, then `neon deploy`. This writes the pooled `DATABASE_URL` and direct `DATABASE_URL_UNPOOLED` to ignored `.env.local`.
3. Set up `backend/.env` with a local `APP_KEY`. Keep credentials in the ignored env files; do not expose the database URL to Vite or React.
4. Install the PHP and frontend dependencies if needed, then run `npm run build`, `npm run db:migrate`, and `npm start` from the root. The app listens on `http://127.0.0.1:8087`.

`npm start` uses the pooled URL for Laravel requests. `npm run db:migrate` uses the direct URL for schema changes. The launcher passes Neon's endpoint ID before PHP starts so older Windows libpq clients can connect. `GET /ready` returns 200 only when Laravel can query Postgres; `GET /health` checks the app process only.
