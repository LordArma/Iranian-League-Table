const { chromium } = require(process.env.PW_PATH || 'playwright');
const BASE = process.env.ILT_BASE || 'http://localhost:8089';
const shots = process.argv[2];
(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1400, height: 1100 } });
  const page = await ctx.newPage();
  const errs = [];
  page.on('pageerror', (e) => errs.push(e.message));
  await page.goto(BASE + '/wp-login.php');
  await page.fill('#user_login', 'iltadmin');
  await page.fill('#user_pass', 'ilt-test-pass-123');
  await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);

  await page.goto(BASE + '/wp-admin/admin.php?page=ilt-builder');
  await page.click('.ilt-segmented label:has(input[value="advanced"])');
  await page.selectOption('#ilt-preset', 'persiangulf');
  await page.fill('#ilt-field-title_font', '18');
  await page.waitForTimeout(2500);
  await page.screenshot({ path: shots + '/fa-builder.png' });
  const headFont = await page.evaluate(() => getComputedStyle(document.querySelector('#ilt-preview').shadowRoot.querySelector('thead th')).fontSize);
  console.log('preview header font-size:', headFont);

  await page.goto(BASE + '/wp-admin/admin.php?page=ilt-settings');
  await page.screenshot({ path: shots + '/fa-settings.png' });
  await page.goto(BASE + '/wp-admin/admin.php?page=ilt-tables');
  await page.screenshot({ path: shots + '/fa-tables.png' });

  // Classic editor: button → modal → insert.
  await page.goto(BASE + '/wp-admin/post-new.php');
  await page.click('.ilt-insert-button');
  const frame = await (await page.waitForSelector('#TB_iframeContent')).contentFrame();
  await frame.waitForSelector('#ilt-insert');
  await frame.click('.ilt-card-choice:has(input[value="kowsar"])');
  await page.waitForTimeout(800);
  await page.screenshot({ path: shots + '/fa-modal.png' });
  await frame.click('#ilt-insert');
  await page.waitForTimeout(500);
  await page.click('#content-html').catch(() => {});
  const content = await page.inputValue('#content');
  console.log('editor content after insert:', JSON.stringify(content));
  console.log('modal closed:', !(await page.isVisible('#TB_window')));
  console.log('js errors:', JSON.stringify(errs));
  await browser.close();
})().catch((e) => { console.error(e); process.exit(2); });
