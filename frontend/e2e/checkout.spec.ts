import { expect, test } from "@playwright/test";

// One real checkout flow, end to end against the real backend (seeded via
// `php artisan migrate:fresh --seed`): browse -> add to cart -> pick date ->
// pick time slot -> fill checkout form -> submit -> land on the order's
// confirmation/status page. Exercises the Release 1/2 contract changes
// (postcode-free pickup path, time_slot_id, extra details, payment_method)
// as one path, not each in isolation — that's what unit tests are for.
test("customer can place a pickup order end to end", async ({ page }) => {
  await page.goto("/");

  // Add the first in-stock product's first variant to the cart.
  await page.getByRole("button", { name: "Add", exact: true }).first().click();
  await expect(page.getByText(/Your order \(1\)/)).toBeVisible();

  // Pick the first open, orderable preorder date.
  await page.getByRole("button", { name: /Choose order date/ }).click();
  const dateOption = page
    .getByRole("button")
    .filter({ hasText: /left$/ })
    .first();
  await dateOption.click();

  // Fulfilment already defaults to pickup — picking a date triggers the
  // slot fetch for pickup; give the network round trip a moment to land
  // before deciding whether a slot picker is required.
  await page.waitForLoadState("networkidle").catch(() => {});

  // If this date has active slots for pickup, one must be chosen before
  // checkout is enabled (plan §0a) — pick the first slot that isn't full.
  const slotSection = page.getByText("Choose a time slot");
  if (await slotSection.isVisible().catch(() => false)) {
    const availableSlot = page
      .locator("button", { hasText: /·/ })
      .filter({ hasNotText: "Full" })
      .first();
    await availableSlot.click();
  }

  const checkoutButton = page.getByRole("button", { name: "Checkout", exact: true });
  await expect(checkoutButton).toBeEnabled();
  await checkoutButton.click();

  await expect(page).toHaveURL(/\/checkout$/);

  await page.getByLabel("Full name").fill("Test Customer");
  await page.getByLabel(/Phone/).fill("+60123456789");
  await page.getByLabel(/Email/).fill("test-customer@example.com");

  await page.getByRole("button", { name: /Place order/ }).click();

  await expect(page).toHaveURL(/\/orders\//, { timeout: 15_000 });
  await expect(page.getByText(/^Order /)).toBeVisible();
});
