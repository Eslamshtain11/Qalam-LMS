const fs = require("fs");
const { callControl, uploadRecording } = require("./control");
const {
  ensurePulse,
  startRecording,
  stopRecording,
  finalizeRecording,
  cleanupRecording,
  sleep,
} = require("./media");
const { connectBrowser, joinMeeting } = require("./browser");

const POLL_MS = Number(process.env.QALAM_POLL_MS || 10000);
const GRACE_SECONDS = Number(process.env.QALAM_GRACE_SECONDS || 20);

let active = false;
const log = (...args) => console.log(new Date().toISOString(), ...args);

async function runJob(job) {
  active = true;
  const startedAt = Date.now();
  const basePath = "/tmp/qalam-" + job.runId;
  const filePath = basePath + ".mp4";

  let page = null;
  let recording = null;

  try {
    log("JOB_START", job.runId);

    await ensurePulse();

    const connected = await connectBrowser();
    const existingPages = connected.context.pages();
    page = existingPages[0] || await Promise.race([
      connected.context.newPage(),
      sleep(8000).then(() => { throw new Error("BROWSER_NEW_PAGE_TIMEOUT"); }),
    ]);
    log("BROWSER_PAGE_READY", page.url(), "existing=" + existingPages.length);

    await joinMeeting(page, job.meetUrl);
    log("MEET_JOINED", job.runId);

    recording = await startRecording(page, basePath);
    await sleep(2500);

    if (recording.video.exitCode !== null) {
      throw new Error("Video recorder exited immediately");
    }
    if (recording.audio.exitCode !== null) {
      log("AUDIO_CAPTURE_WARNING", "parec exited early; silent fallback will be used");
    }

    const requested = Math.max(10, Number(job.plannedSeconds || 60));
    const maxTestSeconds = Number(process.env.QALAM_TEST_MAX_RECORD_SECONDS || 0);
    const planned = maxTestSeconds > 0 ? Math.min(requested, maxTestSeconds) : requested;
    const recordSeconds = planned + GRACE_SECONDS;

    log("RECORDING_STARTED", job.runId, recordSeconds);
    await sleep(recordSeconds * 1000);

    log("RECORDING_STOPPING", job.runId);
    await stopRecording(recording);

    const capture = finalizeRecording(recording, filePath);
    log(
      "RECORDED_FILE_READY",
      job.runId,
      "bytes=" + capture.finalSize,
      "video=" + capture.videoSize,
      "audio=" + capture.audioSize
    );

    log("UPLOAD_PHASE_START", job.runId);
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
    await stopRecording(recording).catch(() => {});
    // Keep the persistent Chromium tab alive for the next scheduled job.
    cleanupRecording(recording, filePath);
    active = false;
  }
}

async function loop() {
  await ensurePulse();
  log("QALAM_RECORDER_READY");
  log("SCHEDULE_POLLING_ENABLED");

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
