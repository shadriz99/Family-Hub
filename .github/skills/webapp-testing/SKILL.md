---
name: webapp-testing
description: "Use after any Family Hub change affecting UI or browser behavior, together with premium-frontend-ui and web-design-reviewer. Verify interactions, validation, navigation, and regressions using available browser tools."
---

# Web Application Testing

This skill enables comprehensive testing and debugging of local web applications using Playwright automation.

## Family Hub coordination

Follow the mandatory coordinated UI workflow in [repository instructions](../../copilot-instructions.md). Run after implementation and visual inspection, reuse their browser session/evidence, and recheck interactions after fixes. Pure documentation or backend-only changes without browser impact do not trigger it. Prefer integrated browser tools; do not install Playwright automatically. Report blocked checks and programmatic-click fallbacks, and preserve shared browser pages and reviewer servers.

You should use the Playwright MCP Server to undertake the work if possible. If the MCP Server is unavailable, you can run the code in a local Node.js environment with Playwright installed.

## When to Use This Skill

Use this skill when you need to:

- Test frontend functionality in a real browser
- Verify UI behavior and interactions
- Debug web application issues
- Capture screenshots for documentation or debugging
- Inspect browser console logs
- Validate form submissions and user flows
- Check responsive design across viewports

## Prerequisites

- Node.js installed on the system
- A locally running web application (or accessible URL)
- Use available browser tooling; any additional dependency requires the project's approval process.

## Core Capabilities

### 1. Browser Automation

- Navigate to URLs
- Click buttons and links
- Fill form fields
- Select dropdowns
- Handle dialogs and alerts

### 2. Verification

- Assert element presence
- Verify text content
- Check element visibility
- Validate URLs
- Test responsive behavior

### 3. Debugging

- Capture screenshots
- View console logs
- Inspect network requests
- Debug failed tests

## Usage Examples

### Example 1: Basic Navigation Test

```javascript
// Navigate to a page and verify title
await page.goto("http://localhost:3000");
const title = await page.title();
console.log("Page title:", title);
```

### Example 2: Form Interaction

```javascript
// Fill out and submit a form
await page.fill("#username", "testuser");
await page.fill("#password", "password123");
await page.click('button[type="submit"]');
await page.waitForURL("**/dashboard");
```

### Example 3: Screenshot Capture

```javascript
// Capture a screenshot for debugging
await page.screenshot({ path: "debug.png", fullPage: true });
```

## Guidelines

1. **Always verify the app is running** - Check that the local server is accessible before running tests
2. **Use explicit waits** - Wait for elements or navigation to complete before interacting
3. **Capture screenshots on failure** - Take screenshots to help debug issues
4. **Clean up resources** - Close only temporary browser resources you created for this check, never reviewer-owned/shared pages
5. **Handle timeouts gracefully** - Set reasonable timeouts for slow operations
6. **Test incrementally** - Start with simple interactions before complex flows
7. **Use selectors wisely** - Prefer data-testid or role-based selectors over CSS classes

## Common Patterns

### Pattern: Wait for Element

```javascript
await page.waitForSelector("#element-id", { state: "visible" });
```

### Pattern: Check if Element Exists

```javascript
const exists = (await page.locator("#element-id").count()) > 0;
```

### Pattern: Get Console Logs

```javascript
page.on("console", (msg) => console.log("Browser log:", msg.text()));
```

### Pattern: Handle Errors

```javascript
try {
  await page.click("#button");
} catch (error) {
  await page.screenshot({ path: "error.png" });
  throw error;
}
```

## Limitations

- Requires Node.js environment
- Cannot test native mobile apps (use React Native Testing Library instead)
- May have issues with complex authentication flows
- Some modern frameworks may require specific configuration

## Helper Functions

Some helper functions are available in [`test-helper.js`](./assets/test-helper.js) to simplify common tasks like waiting for elements, capturing screenshots, and handling errors. You can import and use these functions in your tests to improve readability and maintainability.
