import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';

const manifestPath = process.env.LAKBAY_QA_MANIFEST || 'storage/app/qa-demo/app/qa-campaign/manifest.json';
const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
const phpOptions = process.env.QA_PHP_INI ? ['-c', process.env.QA_PHP_INI] : [];
const pngFixture = process.env.QA_PNG_FIXTURE || 'storage/app/qa-demo/app/private/fixtures/qa-image.png';
const artifactRoot = process.env.LAKBAY_QA_ARTIFACT_ROOT || 'storage/app/qa-campaign-browser';
fs.mkdirSync(artifactRoot, { recursive: true });

function totp(secret) {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    const bits = [...secret.toUpperCase().replace(/[^A-Z2-7]/g, '')].map(c => alphabet.indexOf(c).toString(2).padStart(5, '0')).join('');
    const key = Buffer.from(bits.match(/.{8}/g).map(b => parseInt(b, 2)));
    const counter = Buffer.alloc(8);
    counter.writeBigUInt64BE(BigInt(Math.floor(Date.now() / 30000)));
    const digest = crypto.createHmac('sha1', key).update(counter).digest();
    return String((digest.readUInt32BE(digest[19] & 15) & 0x7fffffff) % 1000000).padStart(6, '0');
}

async function submitLoginStep(page, buttonName, secret = null) {
    for (let attempt = 0; attempt < 3; attempt++) {
        if (secret) {
            const code = page.getByLabel(/6-digit code/);
            await code.fill('');
            await code.pressSequentially(totp(secret));
        }
        await Promise.all([
            page.waitForResponse(response => response.request().method() === 'POST' && response.url().endsWith('/update')),
            page.getByRole('button', { name: buttonName, exact: true }).click(),
        ]);
        const limited = page.getByText('Too many login attempts', { exact: true });
        if (!await limited.waitFor({ state: 'visible', timeout: 1500 }).then(() => true, () => false)) return;
        const notice = await page.getByText(/Please try again in \d+ seconds/).first().innerText();
        const seconds = Number(notice.match(/\d+/)[0]);
        if (seconds > 60) throw new Error(`Login retry interval exceeds test limit: ${seconds}s`);
        console.log(`QA login rate limit: waiting ${seconds + 1}s before retry ${attempt + 1}`);
        await page.waitForTimeout((seconds + 1) * 1000);
    }
    throw new Error('Login remained rate limited after three attempts.');
}

async function login(page, role = 'hei') {
    const account = manifest.accounts[role];
    const sessionPath = path.join(artifactRoot, `session-${role}.json`);
    if (fs.existsSync(sessionPath)) {
        await page.context().addCookies(JSON.parse(fs.readFileSync(sessionPath, 'utf8')).cookies);
        await page.goto(`/${account.panel}/overview`);
        if (page.url().endsWith('/overview')) return;
    }
    await page.goto(`/${account.panel}/login`);
    await page.getByLabel('Email address').fill(account.email);
    await page.locator('input[type="password"]').fill(account.password);
    await submitLoginStep(page, 'Sign in');
    if (account.secret) {
        await submitLoginStep(page, 'Confirm sign in', account.secret);
    } else {
        await expect(page).toHaveURL(/multi-factor-authentication\/set-up/);
    }
    if (await page.getByRole('heading', { name: /Set up two-factor/ }).count()) {
        await page.getByRole('button', { name: 'Set up', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Set up authenticator app' })).toBeVisible();
        const secret = (await page.getByRole('dialog').innerText()).match(/\b[A-Z2-7]{16,64}\b/)?.[0];
        if (!secret) throw new Error('MFA setup secret not found in visible enrollment form.');
        account.secret = secret;
        fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));
        await page.getByLabel(/6-digit code/).first().pressSequentially(totp(secret));
        await page.getByLabel(/current password/i).fill(account.password);
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await page.getByRole('button', { name: 'Enable authenticator app', exact: true }).click();
        await page.getByRole('button', { name: 'Continue', exact: true }).click();
    }
    await expect(page).toHaveURL(new RegExp(`/${account.panel}/(overview|$)`));
    await page.context().storageState({ path: sessionPath });
}

test('public pages and anonymous protected retrieval', async ({ page }) => {
    for (const url of ['/', '/guidelines', '/faq']) {
        const response = await page.goto(url);
        expect(response.status()).toBe(200);
        await expect(page.locator('body')).toContainText('LAKBAY');
    }
    const response = await page.request.get(`/calendars/${manifest.records.calendar}/cloca`, { maxRedirects: 0 });
    expect(response.status()).toBe(302);
});

test('HEI signs in using real MFA enrollment', async ({ page }) => {
    await login(page);
    await expect(page.getByText('Annual calendars (CLOCA)', { exact: true })).toBeVisible();
});

export { login, manifest, artifactRoot };


const annexRoutes = {
    B: `/calendars/${manifest.records.calendar}/cloca`,
    C: `/affidavits/${manifest.records.saa}/saa`,
    D: `/activities/${manifest.records.activity}/diagnostic`,
    E: `/activities/${manifest.records.activity}/racp`,
    F: `/certifications/${manifest.records.certification}/print`,
    G: `/completion-drafts/${manifest.records.roc}/print`,
    H: `/incident-drafts/${manifest.records.incident}/print`,
    I: `/change-drafts/${manifest.records.change}/print`,
    J: `/activities/${manifest.records.activity}/itinerary`,
};

for (const [annex, url] of Object.entries(annexRoutes)) {
    test(`Annex ${annex}: actual HTML preview and PDF download`, async ({ page }) => {
        await login(page);
        const preview = await page.goto(url);
        expect(preview.status()).toBe(200);
        await expect(page.locator('body')).toContainText(/Fictional|fictional/);
        await page.screenshot({ path: path.join(artifactRoot, `annex-${annex}-preview.png`), fullPage: true });
        const response = await page.request.get(`${url}?download=1`);
        expect(response.status()).toBe(200);
        expect(response.headers()['content-type']).toContain('application/pdf');
        const pdf = await response.body();
        expect(pdf.subarray(0, 5).toString()).toBe('%PDF-');
        fs.writeFileSync(path.join(artifactRoot, `annex-${annex}.pdf`), pdf);
    });
}

test('Annex L: response-only consent preview and download', async ({ page }) => {
    await login(page);
    await page.goto(`/activity-versions/${manifest.records.version}/consent`);
    await expect(page.locator('body')).toContainText('Consent');
    const form = page.locator('form');
    const token = await form.locator('input[name="_token"]').inputValue();
    const data = { _token: token, output: 'pdf', student_name: 'Fictional Student', program_year_level: 'BA History, Year 2' };
    const response = await page.request.post(`/activity-versions/${manifest.records.version}/consent`, { form: data });
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('application/pdf');
    fs.writeFileSync(path.join(artifactRoot, 'annex-L.pdf'), await response.body());
});

test('another HEI cannot open campaign preparations or document outputs', async ({ page }) => {
    await login(page, 'other_hei');
    for (const url of Object.values(annexRoutes)) {
        const response = await page.request.get(url, { maxRedirects: 0 });
        expect([403, 404]).toContain(response.status());
    }
});

for (const role of ['preparer', 'reviewer', 'monitor', 'director', 'records', 'analyst', 'case', 'admin']) {
    test(`${role}: MFA, panel navigation and role-specific monitoring creation`, async ({ page }) => {
        await login(page, role);
        const links = [...new Set(await page.getByRole('navigation', { name: 'Sidebar navigation' }).getByRole('link').evaluateAll(items => items.map(item => item.href)))];
        const coverage = [];
        for (const url of links) {
            const response = await page.request.get(url);
            coverage.push({ url, status: response.status() });
            expect(response.status(), `${role}: ${url}`).toBe(200);
            expect(await response.text()).not.toContain('Internal Server Error');
        }
        fs.writeFileSync(path.join(artifactRoot, `navigation-${role}.json`), JSON.stringify(coverage, null, 2));
        if (manifest.accounts[role].panel === 'ched') {
            await page.goto('/ched/monitoring-records');
            const create = page.getByRole('link', { name: 'New monitoring record' });
            if (role === 'monitor') {
                await expect(create).toBeVisible();
                await create.click();
                await expect(page.getByText('Annex M compliance matrix (14 items)', { exact: true })).toBeVisible();
                await expect(page.getByLabel(/Period covered/)).toBeVisible();
            } else {
                await expect(create).toHaveCount(0);
            }
        }
    });
}

test('mobile public layout and keyboard navigation', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/');
    await page.keyboard.press('Tab');
    expect(await page.evaluate(() => document.activeElement.tagName)).not.toBe('BODY');
    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(390);
    await page.screenshot({ path: path.join(artifactRoot, 'public-mobile.png'), fullPage: true });
});

test('Annex M: monitor completes all fourteen checks and prints saved record', async ({ page }) => {
    await login(page, 'monitor');
    await page.goto('/ched/monitoring-records/create');
    await page.getByLabel(/Institution id/).selectOption(String(manifest.records.institution));
    await page.getByLabel(/Period covered/).fill('QA-CAMPAIGN-20261008 first semester');
    await page.getByLabel(/Date of monitoring/).fill('2026-10-08');
    await page.getByLabel(/Type of monitoring/).selectOption('desk');
    await page.getByLabel(/HEI respondent/).fill('Fictional OSAS Coordinator');
    await page.getByLabel(/Respondent position/).fill('OSAS Coordinator');
    const compliance = page.locator('select[id$=".compliance"]');
    await expect(compliance).toHaveCount(14);
    for (let index = 0; index < 14; index++) await compliance.nth(index).selectOption(index === 8 ? 'no' : 'yes');
    await page.getByLabel(/Operational overall rating/).selectOption('non_compliant');
    await page.getByLabel(/Deficiencies noted/).fill('Fictional emergency-contact evidence needs updating.');
    await page.getByLabel(/Good practices/).fill('Clear institutional records and documented consultation.');
    await page.getByLabel(/Suggested measures/).fill('Update the emergency-contact list and retain verification.');
    await page.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(page).toHaveURL(/\/ched\/monitoring-records\/\d+$/);
    const preview = page.getByRole('link', { name: 'Preview / print monitoring (Annex M)', exact: true });
    await expect(preview).toBeVisible();
    const url = await preview.getAttribute('href');
    const response = await page.request.get(`${url}?download=1`);
    expect(response.status()).toBe(200);
    fs.writeFileSync(path.join(artifactRoot, 'annex-M.pdf'), await response.body());
    manifest.records.monitoring_url = url;
    fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));
});

test('CHED reviewer requests a specific correction through the actual dialog', async ({ page }) => {
    await login(page, 'reviewer');
    await page.goto(`/ched/submissions/${manifest.records.submission_id}`);
    const review = page.getByRole('button', { name: 'Review', exact: true });
    if (!await review.count()) {
        await expect(page.locator('body')).toContainText(/corrections_requested|superseded/);
        return;
    }
    await review.click();
    await page.getByLabel(/Outcome/).selectOption('corrections_requested');
    await page.getByLabel(/Remarks/).fill('QA-CAMPAIGN: clarify the alternate museum venue and preserve the prior version.');
    await page.getByRole('button', { name: 'Submit', exact: true }).click();
    await expect(page.getByText('Review recorded', { exact: true })).toBeVisible();
    await page.reload();
    await expect(page.locator('body')).toContainText('corrections_requested');
});

test('HEI correction submission creates a frozen revised package', async ({ page }) => {
    await login(page);
    if (manifest.records.corrected_submission_id) {
        await page.goto(`/hei/submissions/${manifest.records.corrected_submission_id}`);
        await expect(page.locator('body')).toContainText(manifest.records.corrected_reference);
        return;
    }
    const errors = [];
    page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
    await page.goto(`/hei/calendars/${manifest.records.calendar}`);
    await page.getByRole('button', { name: 'Submit / resubmit', exact: true }).click();
    const policyValue = await page.getByLabel(/Policy id/).locator('option').filter({ hasText: /CMO.*24/ }).first().getAttribute('value');
    await page.getByLabel(/Policy id/).selectOption(policyValue);
    await page.getByLabel(/Previous correction request/).selectOption(String(manifest.records.submission_id));
    await expect(page.getByLabel('Status', { exact: false }).first()).toBeVisible();
    await page.getByLabel(/Matching certification draft/).selectOption(String(manifest.records.certification));
    await page.getByLabel(/Matching SAA draft/).selectOption(String(manifest.records.saa));
    const statuses = page.locator('select[id$=".status"]');
    for (let index = 0; index < await statuses.count(); index++) {
        const status = statuses.nth(index);
        const id = await status.getAttribute('id');
        const group = id.slice(0, -'.status'.length);
        if (await status.inputValue() === 'certified_retained') {
            await page.locator(`[id="${group}.reason"]`).fill('FICTIONAL certification: retained in QA OSAS records.');
        } else {
            await status.selectOption('submitted');
            const field = page.locator(`[wire\\:partial="schema-component::${group}.document_ids"]`);
            await field.locator('.fi-select-input-btn').click();
            const options = page.getByRole('listbox').getByRole('option');
            await expect(options.first()).toBeVisible();
            const labels = await options.allTextContents();
            for (const label of labels) await options.filter({ hasText: label.trim() }).click();
            await page.getByRole('heading', { name: 'Submit compliance package', exact: true }).click();
        }
    }
    await expect(page.getByText('Submit compliance package', { exact: true })).toBeVisible();
    fs.writeFileSync(path.join(artifactRoot, 'resubmit-form.html'), await page.content());
    await page.screenshot({ path: path.join(artifactRoot, 'resubmit-form.png'), fullPage: true });
    const submittedResponse = page.waitForResponse(response => response.url().includes('/update') && response.request().postData()?.includes('callMountedAction'));
    await page.getByRole('button', { name: 'Submit', exact: true }).click();
    fs.writeFileSync(path.join(artifactRoot, 'resubmit-response.json'), await (await submittedResponse).text());
    await expect(page.getByRole('heading', { name: 'Submit compliance package', exact: true })).toHaveCount(0);
    expect(errors.filter(error => error.includes('Entangle Error'))).toEqual([]);
    await page.goto('/hei/submissions');
    await page.getByRole('link', { name: 'View', exact: true }).first().click();
    manifest.records.corrected_submission_id = Number(page.url().split('/').pop());
    manifest.records.corrected_reference = (await page.locator('body').innerText()).match(/IV-A-2026-[A-Z0-9]{10}/)[0];
    fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));
});

test('corrected package receives completeness review and Annex K signed acknowledgment', async ({ page }) => {
    await login(page, 'reviewer');
    const id = manifest.records.corrected_submission_id;
    expect(id).toBeTruthy();
    await page.goto(`/ched/submissions/${id}`);
    const review = page.getByRole('button', { name: 'Review', exact: true });
    if (await review.count()) {
        await review.click();
        await page.getByLabel(/Outcome/).selectOption('complete');
        await page.getByLabel(/Remarks/).fill('FICTIONAL QA: complete revised calendar evidence, original version preserved.');
        await page.getByRole('button', { name: 'Submit', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Review', exact: true })).toHaveCount(0);
    }
    await page.context().clearCookies();
    await login(page, 'director');
    await page.goto(`/ched/submissions/${id}`);
    const prepare = page.getByRole('button', { name: 'Prepare letter (Annex K)', exact: true });
    if (await prepare.count()) {
        await prepare.click();
        await page.getByLabel(/Reference/).fill(`QA-CAMPAIGN-K-${id}`);
        await page.getByLabel(/Addressee name/).fill('Fictional Maria Cruz');
        await page.getByLabel(/Recipient position/).fill('President');
        await page.getByLabel(/Dear/).fill('President Cruz');
        await page.getByLabel(/CHEDRO Director name/).fill('Fictional Director Santos');
        await page.getByLabel(/I inspected the submitted/).check();
        await page.getByRole('button', { name: 'Submit', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Prepare letter (Annex K)', exact: true })).toHaveCount(0);
        await page.reload();
        const preview = page.getByRole('link', { name: 'Preview / print letter (Annex K)', exact: true });
        await expect(preview).toBeVisible();
        const url = await preview.getAttribute('href');
        const pdf = await page.request.get(`${url}?download=1`);
        expect(pdf.status()).toBe(200);
        fs.writeFileSync(path.join(artifactRoot, 'annex-K.pdf'), await pdf.body());
        await page.getByRole('button', { name: 'Record signed acknowledgment', exact: true }).click();
        await page.getByLabel(/Document id/).selectOption({ label: 'qa-acknowledgment.pdf' });
        await page.getByRole('button', { name: 'Submit', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Record signed acknowledgment', exact: true })).toHaveCount(0);
        await page.reload();
    }
    const original = page.getByRole('link', { name: 'Signed acknowledgment', exact: true });
    await expect(original).toBeVisible();
    const originalResponse = await page.request.get(await original.getAttribute('href'));
    expect(originalResponse.status()).toBe(200);
    expect(originalResponse.headers()['content-type']).toContain('application/pdf');
});

for (const scenario of [
    { type: 'roc', resource: 'completion-report-drafts', action: 'File signed ROC', document: 'qa-completion.pdf' },
    { type: 'incident', resource: 'incident-report-drafts', action: 'Send initial notice / file signed report' },
    { type: 'change', resource: 'change-report-drafts', action: 'Send immediate notice / file signed report' },
]) {
    test(`HEI ${scenario.type}: actual filing freezes the preparation`, async ({ page }) => {
        await login(page);
        await page.goto(`/hei/${scenario.resource}/${manifest.records[scenario.type]}`);
        const action = page.getByRole('button', { name: scenario.action, exact: true });
        if (await action.count()) {
            await action.click();
            if (scenario.document) {
                await page.getByRole('combobox', { name: /Clean signed completion-report PDF/ }).click();
                await page.getByRole('option', { name: scenario.document, exact: true }).click();
            }
            await page.getByRole('button', { name: /^(Confirm|Submit)$/ }).click();
            await expect(page.getByRole('heading', { name: scenario.action, exact: true })).toHaveCount(0);
            await page.reload();
        }
        await expect(action).toHaveCount(0);
        await expect(page.getByRole('button', { name: /Edit .* preparation/ })).toHaveCount(0);
    });
}

test('HEI PNG upload completes, stays quarantined, then genuine scan permits preview', async ({ page }) => {
    await login(page);
    await page.goto('/hei/documents');
    await page.getByRole('button', { name: 'New document', exact: true }).click();
    await page.getByLabel(/Institution id/).selectOption(String(manifest.records.institution));
    await page.getByLabel(/Document type id/).selectOption({ label: 'Monitoring and Evaluation' });
    const filename = `qa-campaign-upload-${Date.now()}.png`;
    const png = fs.readFileSync(pngFixture);
    await page.locator('input[type="file"]').setInputFiles({ name: filename, mimeType: 'image/png', buffer: png });
    await expect(page.locator('.filepond--file-status-main')).toHaveText(/Upload complete|Upload completed/, { timeout: 30000 });
    await page.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'New document', exact: true })).toHaveCount(0);
    const row = page.locator('tr').filter({ hasText: filename });
    await expect(row).toContainText(/pending/i);
    const php = process.env.QA_PHP || path.join(process.env.LOCALAPPDATA, 'lakbay-dev-tools/php/php.exe');
    const output = execFileSync(php, [...phpOptions, 'artisan', 'queue:work', '--env=local', '--stop-when-empty', '--tries=1', '--timeout=180', '--no-interaction'], { timeout: 240000, encoding: 'utf8' });
    fs.writeFileSync(path.join(artifactRoot, 'queue-worker.txt'), output);
    await page.reload();
    await expect(page.locator('tr').filter({ hasText: filename })).toContainText(/clean/i);
    await page.locator('tr').filter({ hasText: filename }).getByRole('link', { name: 'View', exact: true }).click();
    const preview = page.getByRole('link', { name: 'Open image', exact: true });
    await expect(preview).toBeVisible();
    const response = await page.request.get(await preview.getAttribute('href'));
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('image/png');
});

for (const form of [
    { annex: 'B', resource: 'calendars', key: 'calendar', action: 'Edit CLOCA header / signatories', field: 'HEI address for this CLOCA' },
    { annex: 'C', resource: 'accountability-affidavits', key: 'saa', action: 'Edit SAA draft', field: 'Affiant position' },
    { annex: 'F', resource: 'certification-drafts', key: 'certification', action: 'Edit certification draft', field: 'Designation / position' },
]) {
    test(`Annex ${form.annex}: browser field save leaves submitted preparation unchanged`, async ({ page }) => {
        await login(page);
        await page.goto(`/hei/submissions/${manifest.records.corrected_submission_id}`);
        const preserved = page.getByRole('link', { name: new RegExp(`Preserved.*Annex ${form.annex}`) });
        const snapshotUrl = await preserved.getAttribute('href');
        const before = await page.request.get(snapshotUrl);
        const beforeHtml = await before.text();
        await page.goto(`/hei/${form.resource}/${manifest.records[form.key]}`);
        await page.getByRole('button', { name: form.action, exact: true }).click();
        const field = page.getByLabel(form.field, { exact: true });
        await expect(field).toBeVisible();
        await field.fill('Fictional QA updated print detail');
        await page.getByRole('button', { name: 'Submit', exact: true }).click();
        await expect(page.getByRole('heading', { name: form.action, exact: true })).toHaveCount(0);
        const after = await page.request.get(snapshotUrl);
        expect(await after.text()).toBe(beforeHtml);
    });
}

test('scanner failure preserves quarantine until a genuine successful retry', async ({ page }) => {
    await login(page);
    await page.goto('/hei/documents');
    await page.getByRole('button', { name: 'New document', exact: true }).click();
    await page.getByLabel(/Institution id/).selectOption(String(manifest.records.institution));
    await page.getByLabel(/Document type id/).selectOption({ label: 'Monitoring and Evaluation' });
    const filename = `qa-scanner-failure-${Date.now()}.png`;
    await page.locator('input[type="file"]').setInputFiles({ name: filename, mimeType: 'image/png', buffer: fs.readFileSync(pngFixture) });
    await expect(page.locator('.filepond--file-status-main')).toHaveText(/Upload complete|Upload completed/, { timeout: 30000 });
    await page.getByRole('button', { name: 'Create', exact: true }).click();
    const row = page.locator('tr').filter({ hasText: filename });
    await expect(row).toContainText(/pending/i);
    const php = process.env.QA_PHP || path.join(process.env.LOCALAPPDATA, 'lakbay-dev-tools/php/php.exe');
    const failure = execFileSync(php, [...phpOptions, 'artisan', 'queue:work', '--env=local', '--once', '--no-interaction'], { encoding: 'utf8', timeout: 180000, env: { ...process.env, LAKBAY_SCANNER_COMMAND: 'missing-scanner-QA-only.exe' } });
    expect(failure).toContain('FAIL');
    fs.writeFileSync(path.join(artifactRoot, 'scanner-failure-worker.txt'), failure);
    await page.reload();
    await expect(page.locator('tr').filter({ hasText: filename })).toContainText(/pending/i);
    await page.locator('tr').filter({ hasText: filename }).getByRole('link', { name: 'View', exact: true }).click();
    await expect(page.getByRole('link', { name: 'Open image', exact: true })).toHaveCount(0);
    await expect(page.getByRole('link', { name: 'Download', exact: true })).toHaveCount(0);
    execFileSync(php, [...phpOptions, 'artisan', 'queue:work', '--env=local', '--stop-when-empty', '--no-interaction'], { encoding: 'utf8', timeout: 240000 });
    await page.reload();
    const preview = page.getByRole('link', { name: 'Open image', exact: true });
    await expect(preview).toBeVisible();
    expect((await page.request.get(await preview.getAttribute('href'))).status()).toBe(200);
});

test('classification: HEI saves a recommendation and confirms the saved revision', async ({ page }) => {
    await login(page);
    await page.goto(`/hei/activities/${manifest.records.activity}/revise`);
    await expect(page.getByText('Classification assessment and confirmation', { exact: true })).toBeVisible();
    await page.getByRole('combobox', { name: /^Eligible classification rules/ }).selectOption({ label: 'CMO24-GAP2-EVALUATION-V2 / candidate' });
    await page.getByRole('textbox', { name: 'Original planning date', exact: true }).fill('2026-01-01');
    const regular = page.getByRole('switch', { name: 'Regular institutional process', exact: true });
    if (await regular.getAttribute('aria-checked') !== 'true') await regular.click();
    await page.getByRole('textbox', { name: 'Faculty / HEI supervision and approval reference', exact: true }).fill('Fictional QA faculty approval');
    await page.getByRole('textbox', { name: 'Actual conduct date/time (if applicable)', exact: true }).fill('2026-10-01T09:00');
    await page.getByRole('textbox', { name: 'Actual conduct evidence reference', exact: true }).fill('Fictional QA conduct record');
    await page.getByRole('textbox', { name: 'Change / applicability explanation', exact: true }).fill('Fictional QA assessment enrollment; preserve filed originals');
    await page.getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect(page.getByText('Saved', { exact: true })).toBeVisible();
    await page.goto(`/hei/activities/${manifest.records.activity}`);
    await page.getByRole('button', { name: 'Classification history and requirements', exact: true }).click();
    await expect(page.getByRole('dialog')).toContainText('recommended');
    await expect(page.getByRole('dialog')).toContainText('Formal RCD deadline anchor: unresolved');
    await page.getByRole('button', { name: 'Close', exact: true }).first().click();
    await page.getByRole('button', { name: 'Confirm saved classification', exact: true }).click();
    await page.getByRole('textbox', { name: /^Authorized confirmation \/ change explanation/ }).fill('Fictional authorized QA confirmation');
    await page.getByRole('dialog').getByRole('button', { name: 'Submit', exact: true }).click();
    await expect(page.getByRole('dialog')).not.toBeVisible();
    await page.reload();
    await page.getByRole('button', { name: 'Classification history and requirements', exact: true }).click();
    await expect(page.getByRole('dialog')).toContainText('Fictional authorized QA confirmation');
    await expect(page.getByRole('dialog')).toContainText('confirmed');
});
