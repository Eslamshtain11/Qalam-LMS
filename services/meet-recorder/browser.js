const fs = require("fs");
const { spawn, execFileSync } = require("child_process");
const { chromium } = require("playwright-core");
const { sleep } = require("./media");

const CDP_HTTP = "http://127.0.0.1:9222";

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

async function fetchVersionOnce() {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 3500);
  try {
    const r = await fetch(CDP_HTTP + "/json/version", {
      signal: controller.signal,
      cache: "no-store",
    });
    if (!r.ok) throw new Error("CDP_VERSION_HTTP_" + r.status);
    return await r.json();
  } finally {
    clearTimeout(timer);
  }
}

async function waitForCdp(attempts = 8) {
  let lastError = null;
  for (let i = 1; i <= attempts; i++) {
    try {
      const version = await fetchVersionOnce();
      const wsUrl = String(version?.webSocketDebuggerUrl || "");
      if (!wsUrl) throw new Error("CDP_WEBSOCKET_URL_MISSING");
      console.log(new Date().toISOString(), "CDP_READY", "attempt=" + i);
      return wsUrl;
    } catch (e) {
      lastError = e;
      console.log(
        new Date().toISOString(),
        "CDP_RETRY",
        "attempt=" + i,
        String(e?.message || e),
      );
      await sleep(Math.min(1000 * i, 3500));
    }
  }
  throw lastError || new Error("CDP_UNAVAILABLE");
}

function killChromium() {
  try { execFileSync("pkill", ["-9", "-f", "chromium"], { stdio: "ignore" }); } catch {}
  try { fs.rmSync("/data/chrome-profile/SingletonLock", { force: true }); } catch {}
  try { fs.rmSync("/data/chrome-profile/SingletonSocket", { force: true }); } catch {}
  try { fs.rmSync("/data/chrome-profile/SingletonCookie", { force: true }); } catch {}
}

async function restartChromium() {
  console.log(new Date().toISOString(), "CHROMIUM_RECOVERY_START");
  killChromium();
  await sleep(1500);

  const args = [
    "--no-sandbox",
    "--disable-dev-shm-usage",
    "--disable-gpu",
    "--password-store=basic",
    "--window-position=0,0",
    "--window-size=1280,720",
    "--no-first-run",
    "--no-default-browser-check",
    "--autoplay-policy=no-user-gesture-required",
    "--remote-debugging-port=9222",
    "--remote-debugging-address=127.0.0.1",
    "--user-data-dir=/data/chrome-profile",
    "https://myaccount.google.com/",
  ];

  const proc = spawn("chromium", args, {
    env: { ...process.env, DISPLAY: process.env.DISPLAY || ":99" },
    detached: true,
    stdio: ["ignore", "ignore", "ignore"],
  });
  proc.unref();

  const wsUrl = await waitForCdp(12);
  console.log(new Date().toISOString(), "CHROMIUM_RECOVERY_READY");
  return wsUrl;
}

async function connectBrowser() {
  console.log(new Date().toISOString(), "BROWSER_CONNECT_START");

  let wsUrl;
  try {
    wsUrl = await waitForCdp(5);
  } catch (firstError) {
    console.log(
      new Date().toISOString(),
      "CDP_PRIMARY_FAILED",
      String(firstError?.message || firstError),
    );
    wsUrl = await restartChromium();
  }

  let browser;
  try {
    browser = await withTimeout(
      "BROWSER_CONNECT",
      chromium.connectOverCDP(wsUrl, { timeout: 15000 }),
      18000,
    );
  } catch (firstConnectError) {
    console.log(
      new Date().toISOString(),
      "BROWSER_CONNECT_RECOVERY",
      String(firstConnectError?.message || firstConnectError),
    );
    wsUrl = await restartChromium();
    browser = await withTimeout(
      "BROWSER_CONNECT_RETRY",
      chromium.connectOverCDP(wsUrl, { timeout: 15000 }),
      18000,
    );
  }

  const contexts = browser.contexts();
  if (!contexts.length) throw new Error("No browser context");
  console.log(new Date().toISOString(), "BROWSER_CONNECTED");
  return { browser, context: contexts[0] };
}

async function domSnapshot(page) {
  return await withTimeout(
    "MEET_DOM_SNAPSHOT",
    page.evaluate(() => {
      const buttons = Array.from(document.querySelectorAll("button")).slice(0, 60).map((b) => ({
        text: String(b.innerText || "").trim(),
        aria: String(b.getAttribute("aria-label") || "").trim(),
        disabled: !!b.disabled,
      }));
      return {
        body: String(document.body?.innerText || "").slice(0, 1800),
        buttons,
      };
    }),
    5000,
  );
}

async function clickDomButton(page, labels, logName) {
  const clicked = await withTimeout(
    "MEET_DOM_CLICK",
    page.evaluate((wanted) => {
      const normalize = (s) => String(s || "").toLowerCase().replace(/\s+/g, " ").trim();
      const labels = wanted.map(normalize);
      const buttons = Array.from(document.querySelectorAll("button"));

      for (const button of buttons) {
        if (button.disabled) continue;
        const hay = normalize(
          (button.innerText || "") + " " +
          (button.getAttribute("aria-label") || "") + " " +
          (button.getAttribute("data-tooltip") || "")
        );
        if (!hay) continue;
        if (labels.some((label) => hay.includes(label))) {
          button.click();
          return hay;
        }
      }
      return null;
    }, labels),
    5000,
  );

  if (clicked) {
    console.log(new Date().toISOString(), "MEET_DOM_CLICKED", logName, clicked.slice(0, 180));
    return true;
  }
  return false;
}

async function joinMeeting(page, url) {
  console.log(new Date().toISOString(), "MEET_NAV_START");

  try {
    await page.goto(url, { waitUntil: "commit", timeout: 12000 });
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

  const pre = await domSnapshot(page);
  console.log(
    new Date().toISOString(),
    "MEET_PREJOIN_SNAPSHOT",
    JSON.stringify({
      body: pre.body.replace(/\n/g, " | ").slice(0, 900),
      buttons: pre.buttons.slice(0, 30),
    }),
  );

  const preText = String(pre.body || "");
  const allowGuestMediaSmoke = process.env.QALAM_ALLOW_GUEST_MEDIA_SMOKE === "1";
  if (/what's your name\?|sign in/i.test(preText) && !allowGuestMediaSmoke) {
    console.log(new Date().toISOString(), "MEET_AUTH_REQUIRED");
    throw new Error("GOOGLE_AUTH_REQUIRED");
  }
  if (/what's your name\?|sign in/i.test(preText) && allowGuestMediaSmoke) {
    console.log(new Date().toISOString(), "MEET_GUEST_MEDIA_SMOKE_ONLY");
  }

  await clickDomButton(page, [
    "turn off microphone", "mute microphone",
    "إيقاف الميكروفون", "كتم الميكروفون",
  ], "MIC");

  await clickDomButton(page, [
    "turn off camera", "turn camera off",
    "إيقاف الكاميرا",
  ], "CAMERA");

  const guestName = String(process.env.QALAM_BOT_NAME || "QALAM Recorder");
  const guestNameFilled = await withTimeout(
    "MEET_GUEST_NAME",
    page.evaluate((name) => {
      const inputs = Array.from(document.querySelectorAll("input"));
      const input = inputs.find((el) => {
        const type = String(el.getAttribute("type") || "text").toLowerCase();
        const aria = String(el.getAttribute("aria-label") || "").toLowerCase();
        const placeholder = String(el.getAttribute("placeholder") || "").toLowerCase();
        return type === "text" ||
          aria.includes("name") ||
          placeholder.includes("name");
      });
      if (!input) return false;

      const descriptor = Object.getOwnPropertyDescriptor(
        HTMLInputElement.prototype,
        "value",
      );
      if (descriptor?.set) descriptor.set.call(input, name);
      else input.value = name;

      input.dispatchEvent(new Event("input", { bubbles: true }));
      input.dispatchEvent(new Event("change", { bubbles: true }));
      input.dispatchEvent(new KeyboardEvent("keyup", { bubbles: true, key: "a" }));
      return true;
    }, guestName),
    5000,
  );

  if (guestNameFilled) {
    console.log(new Date().toISOString(), "MEET_GUEST_NAME_FILLED", guestName);
    await sleep(700);
  }

  const joined = await clickDomButton(page, [
    "join now",
    "الانضمام الآن",
    "ask to join",
    "طلب الانضمام",
    "join",
    "انضمام",
  ], "JOIN");

  if (!joined) {
    console.log(new Date().toISOString(), "MEET_JOIN_BUTTON_NOT_FOUND");
    const after = await domSnapshot(page).catch(() => ({ body: "" }));
    const text = after.body || "";
    if (/you can't join this video call|لا يمكنك الانضمام/i.test(text)) {
      throw new Error("Meeting admission denied");
    }
    throw new Error("Meet join button was not found");
  }

  await sleep(6000);
  console.log(new Date().toISOString(), "MEET_JOIN_FLOW_DONE");
  return "JOIN_CLICKED";
}

module.exports = { connectBrowser, joinMeeting };
