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
        if (await item.first().isVisible({ timeout: 600 })) {
          await item.first().click({ timeout: 2500 });
          return true;
        }
      } catch {}
    }
  }
  return false;
}

async function joinMeeting(page, url) {
  await page.goto(url, { waitUntil: "commit", timeout: 30000 });
  await sleep(6000);

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

  await sleep(8000);
  const text = (await page.locator("body").innerText().catch(() => "")) || "";

  if (/you can't join this video call|لا يمكنك الانضمام/i.test(text)) {
    throw new Error("Meeting admission denied");
  }

  return text;
}

module.exports = { connectBrowser, joinMeeting };
