const { chromium } = require("playwright-core");
const { sleep } = require("./media");

async function connectBrowser() {
  const browser = await chromium.connectOverCDP("http://127.0.0.1:9222");
  const contexts = browser.contexts();
  if (!contexts.length) throw new Error("No browser context");
  return { browser, context: contexts[0] };
}

async function clickAny(page, labels) {
  for (const label of labels) {
    const rx = new RegExp(label, "i");
    const candidates = [
      page.getByRole("button", { name: rx }),
      page.getByText(rx, { exact: false }),
    ];
    for (const item of candidates) {
      try {
        if (await item.first().isVisible({ timeout: 700 })) {
          await item.first().click({ timeout: 2500 });
          return true;
        }
      } catch {}
    }
  }
  return false;
}

async function joinMeeting(page, url) {
  const cdp = await page.context().newCDPSession(page);
  try {
    await cdp.send("Page.enable");
    const nav = await cdp.send("Page.navigate", { url });
    if (nav?.errorText) throw new Error("Meet navigation failed: " + nav.errorText);
  } finally {
    try { await cdp.detach(); } catch {}
  }

  // Do not wait for DOMContentLoaded/network-idle: Meet is a long-lived WebRTC app
  // and those lifecycle events can stall in containerized Chromium.
  await sleep(8000);

  const currentUrl = page.url();
  if (!currentUrl.includes("meet.google.com/")) {
    throw new Error("Meet did not open; current URL: " + currentUrl);
  }

  await clickAny(page, [
    "Turn off microphone",
    "Mute microphone",
    "إيقاف الميكروفون",
    "كتم الميكروفون",
  ]);
  await clickAny(page, [
    "Turn off camera",
    "Turn camera off",
    "إيقاف الكاميرا",
  ]);

  const clicked = await clickAny(page, [
    "Join now",
    "الانضمام الآن",
    "Join",
    "انضمام",
    "Ask to join",
    "طلب الانضمام",
  ]);

  if (!clicked) {
    try { await page.keyboard.press("Enter"); } catch {}
  }

  await sleep(9000);
  const text = (await page.locator("body").innerText().catch(() => "")) || "";

  if (/you can't join this video call|لا يمكنك الانضمام/i.test(text)) {
    throw new Error("Meeting admission denied");
  }

  if (/sign in|تسجيل الدخول/i.test(text) &&
      !/leave call|مغادرة المكالمة|people|الأشخاص|participant|مشارك/i.test(text)) {
    throw new Error("Google session is not authenticated");
  }

  return text;
}

module.exports = { connectBrowser, joinMeeting };
