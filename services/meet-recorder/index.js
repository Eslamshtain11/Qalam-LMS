const fs = require("fs");
const { callControl, uploadRecording } = require("./control");
const { ensurePulse, startRecording, stopRecording, sleep } = require("./media");
const { connectBrowser, joinMeeting } = require("./browser");

const POLL_MS = Number(process.env.QALAM_POLL_MS || 10000);
const GRACE_SECONDS = Number(process.env.QALAM_GRACE_SECONDS || 20);

let active = false;

const log = (...args) => console.log(new Date().toISOString(), ...args);

async function runJob(job) {
  active = true;
  const startedAt = Date.now();
  const filePath = "/tmp/qalam-" + job.runId + ".mp4";

  let browser = null;
  let page = null;
  let recorder = null;

  try {
    log("JOB_START", job.runId);

    await ensurePulse();

    const connected = await connectBrowser();
    browser = connected.browser;
    page = await connected.context.newPage();

    await joinMeeting(page, job.meetUrl);
    log("MEET_JOINED", job.runId);

    recorder = startRecording(filePath);
    await sleep(2500);

    if (recorder.exitCode !== null) {
      throw new Error("Recorder exited immediately");
    }

    const planned = Math.max(60, Number(job.plannedSeconds || 120));
    const deadline = Date.now() + (planned + GRACE_SECONDS) * 1000;

    log("RECORDING_STARTED", job.runId, planned + GRACE_SECONDS);

    while (Date.now() < deadline) {
      await sleep(10000);
      const text = await page.locator("body").innerText().catch(() => "");
      if (/you left the meeting|you've left|لقد غادرت|meeting ended|تم إنهاء الاجتماع/i.test(text)) {
        break;
      }
    }

    await stopRecording(recorder);
    recorder = null;

    await page.close().catch(() => {});
    page = null;

    const result = await uploadRecording(job, filePath, startedAt);
    log("RECORDING_COMPLETED", JSON.stringify(result));
  } catch (e) {
    const message = String(e?.stack || e?.message || e);
    log("JOB_FAILED", message);

    try {
      await callControl("fail", {
        runId: job.runId,
        message: message.slice(0, 900),
      });
    } catch {}
  } finally {
    await stopRecording(recorder).catch(() => {});
    if (page) await page.close().catch(() => {});
    if (browser) await browser.close().catch(() => {});
    try { fs.rmSync(filePath, { force: true }); } catch {}
    active = false;
  }
}

async function loop() {
  await ensurePulse();
  log("QALAM_RECORDER_READY");

  while (true) {
    try {
      if (!active) {
        const next = await callControl("next");
        log("POLL_NEXT", next.job?.runId || "null");
        if (next.job) await runJob(next.job);
      }
    } catch (e) {
      log("POLL_ERROR", String(e?.stack || e?.message || e));
    }

    await sleep(POLL_MS);
  }
}

loop().catch((e) => {
  log("FATAL", String(e?.stack || e?.message || e));
  process.exit(1);
});
