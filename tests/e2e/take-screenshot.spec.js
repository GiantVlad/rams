import { expect, test } from '@playwright/test';

test('take UI screenshots for analysis', async ({ page }) => {
  // Navigate to the app
  await page.goto('/');

  // If there's an active game prompt, start a new game
  const resumePrompt = page.getByRole('heading', { name: 'Active Game Found' });
  if (await resumePrompt.isVisible().catch(() => false)) {
    await page.getByRole('button', { name: 'Start New Game' }).click();
  }

  // Wait for the main menu and take a screenshot of it
  await expect(page.getByRole('button', { name: 'Start New Game' })).toBeVisible();
  await page.screenshot({ path: 'test-results/main-menu.png', fullPage: true });

  // Start a new game
  await page.getByRole('button', { name: 'Start New Game' }).click();

  // Wait for the game to start and the UI to settle
  await expect(page.getByText(/Game #\d+ · .* Phase · Turn:/)).toBeVisible();
  
  // Wait a moment for animations or initial deals to complete
  await page.waitForTimeout(2000);
  
  // Take a screenshot of the active game board
  await page.screenshot({ path: 'test-results/active-game.png', fullPage: true });
});
