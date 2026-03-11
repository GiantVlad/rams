import { expect, test } from '@playwright/test'

test('open main page, start a new game, and exit', async ({ page }) => {
  await page.goto('/')

  const resumePrompt = page.getByRole('heading', { name: 'Active Game Found' })
  if (await resumePrompt.isVisible().catch(() => false)) {
    await page.getByRole('button', { name: 'Start New Game' }).click()
  }

  await expect(page.getByRole('button', { name: 'Start New Game' })).toBeVisible()
  await page.getByRole('button', { name: 'Start New Game' }).click()

  await expect(page.getByText(/Game #\d+ · .* Phase · Turn:/)).toBeVisible()
  await page.waitForTimeout(3_000)
})
