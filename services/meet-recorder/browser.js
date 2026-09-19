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

  const version = await withTimeout(
    "CDP_VERSION",
    fetch("http://127.0.0.1:9222/json/version").then((r) => {
      if (!r.ok) throw new Error("CDP_VERSION_HTTP_" + r.status);
      return r.json();
    }),
    5000,
  );

  const wsUrl = String(version?.webSocketDebuggerUrl || "");
  if (!wsUrl) throw new Error("CDP_WEBSOCKET_URL_MISSING");

  const browser = await withTimeout(
    "BROWSER_CONNECT",
    chromium.connectOverCDP(wsUrl, { timeout: 10000 }),
    12000,
  );

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

  const pre = await domSnapshot(page);
  console.log(
    new Date().toISOString(),
    "MEET_PREJOIN_SNAPSHOT",
    JSON.stringify({
      body: pre.body.replace(/\n/g, " | ").slice(0, 900),
      buttons: pre.buttons.slice(0, 30),
    }),
  );

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

  // Meet's active-call page can block normal DOM evaluation for long periods.
  // Once the enabled Join button has been clicked successfully, continue via CDP recording
  // instead of waiting on another DOM snapshot.
  await sleep(6000);
  console.log(new Date().toISOString(), "MEET_JOIN_FLOW_DONE");
  return "JOIN_CLICKED";
}

module.exports = { connectBrowser, joinMeeting };
