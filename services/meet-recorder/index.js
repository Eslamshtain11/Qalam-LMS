const fs = require("fs");
const { callControl, uploadRecording } = require("./control");
const { ensurePulse, startRecording, stopRecording, sleep } = require("./media");
const { connectBrowser, joinMeeting } = require("./browser");

const POLL_MS = Number(process.env.QALAM_POLL_MS || 10000);
const GRACE_SECONDS = Number(process.env.QALAM_GRACE_SECONDS || 20);

const MANUAL_RUN_ID = process.env.QALAM_MANUAL_RUN_ID || "";
const MANUAL_MEET_URL = process.env.QALAM_MANUAL_MEET_URL || "";
const MANUAL_TITLE = process.env.QALAM_MANUAL_TITLE || "Qalam recorder test";
const MANUAL_DATE = process.env.QALAM_MANUAL_DATE || "2026-09-19";
const MANUAL_SECONDS = Number(process.env.QALAM_MANUAL_SECONDS || 45);
const MANUAL_MARKER = "/data/qalam-manual-test-done";

let active = false;

const log = (...args) => console.log(new Date().toISOString(), ...args);

async function runJob(job) {
  active = true;
  const startedAt = Date.now();
  const filePath = "/tmp/qalam-" + job.runId + ".mp4";

  let page = null;
  let recorder = null;

  try {
    log("JOB_START", job.runId);

    await ensurePulse();

    const connected = await connectBrowser();
    page = await connected.context.newPage();

    await joinMeeting(page, job.meetUrl);
    log("MEET_JOINED", job.runId);

    recorder = startRecording(filePath);
    await sleep(2500);

    if (recorder.exitCode !== null) {
      throw new Error("Recorder exited immediately");
    }

    const planned = Math.max(30, Number(job.plannedSeconds || 60));
    const recordSeconds = planned + GRACE_SECONDS;

    log("RECORDING_STARTED", job.runId, recordSeconds);

    await sleep(recordSeconds * 1000);

    log("RECORDING_STOPPING", job.runId);
    await stopRecording(recorder);
    recorder = null;

    const size = fs.existsSync(filePath) ? fs.statSync(filePath).size : 0;
    log("RECORDED_FILE_READY", job.runId, "bytes=" + size);
    if (size < 10000) {
      throw new Error("Recorded file missing or too small: " + size);
    }

    await page.close().catch(() => {});
    page = null;

    log("UPLOAD_PHASE_START", job.runId);
    const result = await uploadRecording(job, filePath, startedAt);
    log("RECORDING_COMPLETED", JSON.stringify(result));
    return true;
  } catch (e) {
    const message = String(e?.stack || e?.message || e);
    log("JOB_FAILED", message);

    try {
      await callControl("fail", {
        runId: job.runId,
        message: message.slice(0, 900),
      });
    } catch {}

    return false;
  } finally {
    await stopRecording(recorder).catch(() => {});
    if (page) await page.close().catch(() => {});
    try { fs.rmSync(filePath, { force: true }); } catch {}
    active = false;
  }
}

async function visibleAccountPage() {
  try {
    const connected = await connectBrowser();
    return connected.context.pages().some((page) => {
      try {
        return new URL(page.url()).hostname === "myaccount.google.com";
      } catch {
        return false;
      }
    });
  } catch {
    return false;
  }
}

async function runManualTestWhenReady() {
  if (!MANUAL_RUN_ID || !MANUAL_MEET_URL || fs.existsSync(MANUAL_MARKER)) return;

  log("MANUAL_TEST_WAITING");

  while (!fs.existsSync(MANUAL_MARKER)) {
    if (await visibleAccountPage()) {
      const job = {
        runId: MANUAL_RUN_ID,
        title: MANUAL_TITLE,
        meetUrl: MANUAL_MEET_URL,
        occurrenceDate: MANUAL_DATE,
        plannedSeconds: MANUAL_SECONDS,
      };

      const ok = await runJob(job);
      if (ok) {
        fs.writeFileSync(MANUAL_MARKER, new Date().toISOString());
        log("MANUAL_TEST_COMPLETED");
        return;
      }

      log("MANUAL_TEST_RETRY");
      await sleep(10000);
    } else {
      await sleep(5000);
    }
  }
}

async function loop() {
  await ensurePulse();
  log("QALAM_RECORDER_READY");

  await runManualTestWhenReady();

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
