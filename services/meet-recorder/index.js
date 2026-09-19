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
const { connectBrowser, joinMeeting, closeBrowserGracefully } = require("./browser");

const POLL_MS = Number(process.env.QALAM_POLL_MS || 10000);
const GRACE_SECONDS = Number(process.env.QALAM_GRACE_SECONDS || 20);
const RECORDING_MODE = String(process.env.QALAM_RECORDING_MODE || "native_primary").toLowerCase();
const NATIVE_CONFIRM_SECONDS = Number(process.env.QALAM_NATIVE_CONFIRM_SECONDS || 35);
const ACTIVITY_POLL_SECONDS = Number(process.env.QALAM_ACTIVITY_POLL_SECONDS || 15);
const NO_SHOW_SECONDS = Number(process.env.QALAM_NO_SHOW_SECONDS || 1800);
const HARD_MAX_RECORD_SECONDS = Number(process.env.QALAM_HARD_MAX_RECORD_SECONDS || 21600);
const LEAVE_CONFIRM_CHECKS = Number(process.env.QALAM_LEAVE_CONFIRM_CHECKS || 3);

let active = false;
let shuttingDown = false;
const log = (...args) => console.log(new Date().toISOString(), ...args);

async function gracefulShutdown(signal) {
  if (shuttingDown) return;
  shuttingDown = true;
  log("GRACEFUL_SHUTDOWN_START", signal);
  try {
    await closeBrowserGracefully();
  } catch (e) {
    log("GRACEFUL_SHUTDOWN_WARNING", String(e?.message || e));
  }
  log("GRACEFUL_SHUTDOWN_DONE", signal);
  process.exit(0);
}

process.on("SIGTERM", () => { void gracefulShutdown("SIGTERM"); });
process.on("SIGINT", () => { void gracefulShutdown("SIGINT"); });

async function waitForNativeRecording(runId) {
  const deadline = Date.now() + Math.max(10, NATIVE_CONFIRM_SECONDS) * 1000;
  let lastState = null;

  while (Date.now() < deadline) {
    try {
      const status = await callControl("native_status", { runId });
      lastState = status?.state || null;
      log("NATIVE_RECORDING_STATE", runId, lastState || "none");
      if (["STARTED", "ENDED", "FILE_GENERATED"].includes(lastState)) {
        return status;
      }
    } catch (e) {
      log("NATIVE_RECORDING_CHECK_WARNING", String(e?.message || e));
    }
    await sleep(5000);
  }

  return lastState ? { state: lastState } : null;
}

async function waitForMeetingLifecycle(runId, plannedSeconds) {
  const startedAt = Date.now();
  const plannedStopAt = startedAt + Math.max(90, plannedSeconds) * 1000;
  const noShowWindow = Math.min(
    Math.max(300, plannedSeconds + GRACE_SECONDS),
    Math.max(300, NO_SHOW_SECONDS),
  );
  const noShowAt = startedAt + noShowWindow * 1000;
  const hardStopAt = startedAt + Math.max(600, HARD_MAX_RECORD_SECONDS) * 1000;

  let hadOtherParticipant = false;
  let aloneChecks = 0;
  let consecutiveApiFailures = 0;

  while (Date.now() < hardStopAt) {
    try {
      const activity = await callControl("meeting_activity", { runId });
      consecutiveApiFailures = 0;

      const count = Number(activity?.activeParticipantCount || 0);
      const found = activity?.found === true;
      log(
        "MEETING_ACTIVITY",
        runId,
        "found=" + found,
        "active=" + count,
        "ended=" + (activity?.conferenceEnded === true),
      );

      if (activity?.conferenceEnded === true) {
        return { reason: "conference_ended", activeParticipantCount: count };
      }

      if (found && count > 1) {
        hadOtherParticipant = true;
        aloneChecks = 0;
      } else if (found && hadOtherParticipant && count <= 1) {
        aloneChecks += 1;
        if (aloneChecks >= Math.max(2, LEAVE_CONFIRM_CHECKS)) {
          return { reason: "participants_left", activeParticipantCount: count };
        }
      } else if (found && !hadOtherParticipant && Date.now() >= noShowAt) {
        return { reason: "no_show_timeout", activeParticipantCount: count };
      }
    } catch (e) {
      consecutiveApiFailures += 1;
      log("MEETING_ACTIVITY_WARNING", runId, String(e?.message || e));
    }

    // If Google activity telemetry is unavailable, preserve the old schedule-based
    // stop as a safe fallback instead of recording forever.
    if (consecutiveApiFailures >= 4 && Date.now() >= plannedStopAt) {
      return { reason: "activity_api_unavailable" };
    }

    await sleep(Math.max(5, ACTIVITY_POLL_SECONDS) * 1000);
  }

  return { reason: "hard_cap" };
}

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

    const joinResult = await joinMeeting(page, job.meetUrl);
    const joinMode = String(joinResult?.mode || "unknown");
    log("MEET_JOINED", job.runId, "mode=" + joinMode);

    await callControl("host_ready", { runId: job.runId });
    log("HOST_READY", job.runId);

    const requested = Math.max(10, Number(job.plannedSeconds || 60));
    const maxTestSeconds = Number(process.env.QALAM_TEST_MAX_RECORD_SECONDS || 0);
    const planned = maxTestSeconds > 0 ? Math.min(requested, maxTestSeconds) : requested;
    const recordSeconds = planned + GRACE_SECONDS;

    if (RECORDING_MODE !== "custom" && joinMode === "authenticated") {
      const native = await waitForNativeRecording(job.runId);
      if (native && ["STARTED", "ENDED", "FILE_GENERATED"].includes(native.state)) {
        await callControl("native_started", { runId: job.runId });
        log("NATIVE_RECORDING_CONFIRMED", job.runId, native.state);
        log("NATIVE_PRIMARY_ACTIVE", job.runId, recordSeconds);
        const lifecycle = await waitForMeetingLifecycle(job.runId, planned);
        log("NATIVE_PRIMARY_HANDOFF", job.runId, JSON.stringify(lifecycle));
        return;
      }

      if (RECORDING_MODE === "native_only") {
        throw new Error("Native Google Meet recording was not confirmed");
      }

      log("NATIVE_RECORDING_NOT_CONFIRMED_FALLBACK_CUSTOM", job.runId);
    } else if (RECORDING_MODE !== "custom" && joinMode !== "authenticated") {
      if (RECORDING_MODE === "native_only") {
        throw new Error("Native Google Meet recording requires an authenticated host/co-host");
      }
      log("AUTH_UNAVAILABLE_FALLBACK_CUSTOM", job.runId, "joinMode=" + joinMode);
    }

    recording = await startRecording(page, basePath);
    await sleep(2500);

    if (recording.video.exitCode !== null) {
      throw new Error("Video recorder exited immediately");
    }
    if (recording.audio.exitCode !== null) {
      log("AUDIO_CAPTURE_WARNING", "parec exited early; silent fallback will be used");
    }

    await callControl("started", { runId: job.runId });
    log("FALLBACK_RECORDING_STARTED", job.runId, recordSeconds);
    const lifecycle = await waitForMeetingLifecycle(job.runId, planned);
    log("RECORDING_STOP_CONDITION", job.runId, JSON.stringify(lifecycle));

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
  log(
    "RECORDING_MODE",
    RECORDING_MODE,
    "nativeConfirmSeconds=" + NATIVE_CONFIRM_SECONDS,
    "zeroTouch=" + (process.env.QALAM_ZERO_TOUCH !== "0"),
    "activityPollSeconds=" + ACTIVITY_POLL_SECONDS,
    "noShowSeconds=" + NO_SHOW_SECONDS,
    "hardMaxSeconds=" + HARD_MAX_RECORD_SECONDS,
  );
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
