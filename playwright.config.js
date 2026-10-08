import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Browser',
    workers: 1,
    timeout: 90000,
    expect: { timeout: 15000 },
    outputDir: `${process.env.LAKBAY_QA_ARTIFACT_ROOT || 'storage/app/qa-campaign-browser'}/results`,
    reporter: [['list'], ['html', { outputFolder: `${process.env.LAKBAY_QA_ARTIFACT_ROOT || 'storage/app/qa-campaign-browser'}/report`, open: 'never' }]],
    use: { baseURL: process.env.LAKBAY_QA_URL || 'http://127.0.0.1:8001', viewport: { width: 1440, height: 1000 }, screenshot: 'only-on-failure', trace: 'retain-on-failure' },
});
