import { expect, test } from '@playwright/test'

test('can open and close the rules popup', async ({ page }) => {
  await page.goto('/')

  // Find and click the Rules button
  const rulesBtn = page.locator('button.rules-btn', { hasText: 'Rules' })
  await expect(rulesBtn).toBeVisible()
  await rulesBtn.click()

  // Verify the rules popup is displayed
  const rulesHeading = page.getByRole('heading', { name: 'How to Play Rams' })
  await expect(rulesHeading).toBeVisible()

  // Verify some rule text is present
  await expect(page.getByText('Each player starts with 20 points.')).toBeVisible()

  // Close the popup
  const closeBtn = page.locator('.close-btn')
  await expect(closeBtn).toBeVisible()
  await closeBtn.click()

  // Verify the popup is closed
  await expect(rulesHeading).toBeHidden()
})
