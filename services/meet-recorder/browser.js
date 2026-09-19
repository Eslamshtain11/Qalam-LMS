const { chromium } = require("playwright-core");
const { sleep } = require("./media");

async function withTimeout(label, promise, ms) {
  let timer;
  try {
    return await Promise.race([
      promise,
      new Promise((_, reject) => {
        timer = setTimeout(() => reject(new Error(label + "_TIMEOUT")), ms);
      }),
    ]);
  } finally {
    if (timer) clearTimeout(timer);
  }
}

async function connectBrowser() {
  console.log(new Date().toISOString(), "BROWSER_CONNECT_START");
  const browser = await withTimeout(
    "BROWSER_CONNECT",
    chromium.connectOverCDP("http://127.0.0.1:9222"),
    12000,
  );
  const contexts = browser.contexts();
  if (!contexts.length) throw new Error("No browser context");
  console.log(new Date().toISOString(), "BROWSER_CONNECTED");
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
          console.log(new Date().toISOString(), "MEET_CLICKED", label);
          return true;
        }
      } catch {}
    }
  }
  return false;
}

async function joinMeeting(page, url) {
  console.log(new Date().toISOString(), "MEET_NAV_START");

  try {
    await page.goto(url, { waitUntil: "commit", timeout: 8000 });
    console.log(new Date().toISOString(), "MEET_NAV_COMMIT");
  } catch (e) {
    console.log(new Date().toISOString(), "MEET_NAV_TIMEOUT_CONTINUE", String(e?.message || e));
  }

  await sleep(9000);

  const currentUrl = page.url();
  console.log(new Date().toISOString(), "MEET_NAV_URL", currentUrl);
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
    console.log(new Date().toISOString(), "MEET_JOIN_BUTTON_NOT_FOUND");
    try { await page.keyboard.press("Enter"); } catch {}
  }

  await sleep(9000);

  const text = await page.locator("body").innerText({ timeout: 5000 }).catch(() => "");
  const sample = String(text || "").replace(/\n/g, " | ").slice(0, 700);
  console.log(new Date().toISOString(), "MEET_BODY_SAMPLE", sample);

  if (/you can't join this video call|لا يمكنك الانضمام/i.test(text)) {
    throw new Error("Meeting admission denied");
  }

  if (/sign in|تسجيل الدخول/i.test(text) &&
      !/leave call|مغادرة المكالمة|people|الأشخاص|participant|مشارك/i.test(text)) {
    throw new Error("Google session is not authenticated");
  }

  console.log(new Date().toISOString(), "MEET_JOIN_FLOW_DONE");
  return text;
}

module.exports = { connectBrowser, joinMeeting };
