const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  page.on('console', msg => console.log('PAGE LOG:', msg.text()));
  page.on('pageerror', err => console.log('PAGE ERROR:', err.message));
  page.on('response', response => {
    if (response.status() >= 400) {
      console.log(`HTTP ERROR: ${response.status()} ${response.url()}`);
    }
  });

  try {
    await page.goto('https://rams.cloud-workflow.com', { waitUntil: 'networkidle' });
    console.log('Page loaded successfully.');
    await page.screenshot({ path: 'prod-screenshot.png' });
    console.log('Screenshot saved to prod-screenshot.png');
  } catch (error) {
    console.error('Error loading page:', error);
  }

  await browser.close();
})();
