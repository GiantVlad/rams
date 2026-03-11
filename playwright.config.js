// Local QA config: starts the full app stack and keeps the browser visible.
export default {
  testDir: './tests/e2e',
  fullyParallel: false,
  workers: 1,
  retries: 0,
  timeout: 60_000,
  use: {
    baseURL: 'http://127.0.0.1:5173',
    headless: false,
    launchOptions: {
      slowMo: 800,
    },
    trace: 'on-first-retry',
    video: 'off',
    screenshot: 'only-on-failure',
  },
  webServer: [
    {
      command: 'php artisan serve --host=127.0.0.1 --port=8000',
      cwd: './api',
      port: 8000,
      reuseExistingServer: true,
      timeout: 120_000,
    },
    {
      command: 'npm run dev -- --host 127.0.0.1 --port 5173',
      cwd: './web',
      port: 5173,
      reuseExistingServer: true,
      timeout: 120_000,
    },
    {
      command: 'node websocket-server.js',
      cwd: '.',
      port: 8080,
      reuseExistingServer: true,
      timeout: 120_000,
    },
  ],
}
