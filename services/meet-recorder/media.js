const fs = require("fs");
const { spawn, execFileSync } = require("child_process");

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function exec(cmd, args = []) {
  try {
    return execFileSync(cmd, args, {
      stdio: ["ignore", "pipe", "pipe"],
      maxBuffer: 4 * 1024 * 1024,
    }).toString();
  } catch {
    return "";
  }
}

async function ensurePulse() {
  let info = exec("pactl", ["info"]);
  if (!info.includes("Server Name")) {
    spawn("pulseaudio", ["--daemonize=yes", "--exit-idle-time=-1", "--disable-shm=yes"], {
      stdio: "ignore",
    });
    await sleep(1200);
    info = exec("pactl", ["info"]);
  }
  if (!info.includes("Server Name")) throw new Error("PulseAudio unavailable");

  const modules = exec("pactl", ["list", "short", "modules"]).split("\n").filter(Boolean);
  for (const line of modules) {
    if (line.includes("module-suspend-on-idle")) {
      exec("pactl", ["unload-module", line.split(/\s+/)[0]]);
    }
  }

  let sources = exec("pactl", ["list", "short", "sources"]);
  if (!sources.includes("qalamrec.monitor")) {
    exec("pactl", [
      "load-module",
      "module-null-sink",
      "sink_name=qalamrec",
      "rate=48000",
      "channels=2",
      "sink_properties=device.description=QalamRecorder",
    ]);
    await sleep(400);
    sources = exec("pactl", ["list", "short", "sources"]);
  }
  if (!sources.includes("qalamrec.monitor")) {
    throw new Error("qalamrec.monitor unavailable");
  }

  exec("pactl", ["set-default-sink", "qalamrec"]);
  exec("pactl", ["set-sink-mute", "qalamrec", "0"]);
}

function capturePaths(basePath) {
  return {
    video: basePath + ".video.mkv",
    audio: basePath + ".audio.raw",
  };
}

async function startRecording(page, basePath) {
  const paths = capturePaths(basePath);
  for (const p of Object.values(paths)) {
    try { fs.rmSync(p, { force: true }); } catch {}
  }

  const cdp = await page.context().newCDPSession(page);
  let latestFrame = null;
  let firstFrameResolve;
  const firstFrame = new Promise((resolve) => { firstFrameResolve = resolve; });

  cdp.on("Page.screencastFrame", async (event) => {
    latestFrame = Buffer.from(event.data, "base64");
    firstFrameResolve?.();
    firstFrameResolve = null;
    try {
      await cdp.send("Page.screencastFrameAck", { sessionId: event.sessionId });
    } catch {}
  });

  await cdp.send("Page.startScreencast", {
    format: "jpeg",
    quality: 72,
    maxWidth: 1280,
    maxHeight: 720,
    everyNthFrame: 1,
  });

  await Promise.race([
    firstFrame,
    sleep(8000).then(() => { throw new Error("Chromium screencast produced no frame"); }),
  ]);

  const video = spawn("ffmpeg", [
    "-y",
    "-hide_banner",
    "-loglevel", "warning",
    "-f", "image2pipe",
    "-framerate", "10",
    "-vcodec", "mjpeg",
    "-i", "pipe:0",
    "-an",
    "-vf", "scale=trunc(iw/2)*2:trunc(ih/2)*2",
    "-c:v", "libx264",
    "-preset", "veryfast",
    "-crf", "28",
    "-pix_fmt", "yuv420p",
    "-f", "matroska",
    paths.video,
  ], {
    stdio: ["pipe", "ignore", "pipe"],
  });

  let videoTail = "";
  let canWrite = true;
  video.stderr.on("data", (d) => {
    videoTail = (videoTail + String(d)).slice(-6000);
  });
  video.stdin.on("drain", () => { canWrite = true; });
  video.on("exit", (code, signal) => {
    console.log(new Date().toISOString(), "VIDEO_RECORDER_EXIT",
      "code=" + code, "signal=" + signal,
      videoTail.replace(/\n/g, " | ").slice(-1200));
  });

  const frameTimer = setInterval(() => {
    if (!latestFrame || !canWrite || video.exitCode !== null) return;
    try {
      canWrite = video.stdin.write(latestFrame);
    } catch {}
  }, 100);

  const audioFile = fs.createWriteStream(paths.audio);
  const audio = spawn("parec", [
    "--device=qalamrec.monitor",
    "--format=s16le",
    "--rate=48000",
    "--channels=2",
    "--latency-msec=50",
  ], {
    env: { ...process.env, PULSE_SINK: "qalamrec" },
    stdio: ["ignore", "pipe", "pipe"],
  });

  let audioTail = "";
  audio.stdout.pipe(audioFile);
  audio.stderr.on("data", (d) => {
    audioTail = (audioTail + String(d)).slice(-4000);
  });
  audio.on("exit", (code, signal) => {
    console.log(new Date().toISOString(), "AUDIO_RECORDER_EXIT",
      "code=" + code, "signal=" + signal,
      audioTail.replace(/\n/g, " | ").slice(-800));
  });

  return { video, audio, audioFile, paths, cdp, frameTimer };
}

async function waitExit(proc, ms) {
  if (!proc || proc.exitCode !== null) return;
  await Promise.race([
    new Promise((resolve) => proc.once("exit", resolve)),
    sleep(ms),
  ]);
}

async function stopRecording(session) {
  if (!session) return;
  const { video, audio, audioFile, cdp, frameTimer } = session;

  if (frameTimer) clearInterval(frameTimer);
  if (cdp) {
    await Promise.race([
      cdp.send("Page.stopScreencast").catch(() => {}),
      sleep(1500),
    ]);
  }

  if (video && video.exitCode === null) {
    try { video.stdin.end(); } catch {}
    await waitExit(video, 8000);
    if (video.exitCode === null) {
      try { video.kill("SIGINT"); } catch {}
      await waitExit(video, 3000);
    }
    if (video.exitCode === null) {
      try { video.kill("SIGKILL"); } catch {}
      await waitExit(video, 1000);
    }
  }

  if (audio && audio.exitCode === null) {
    try { audio.kill("SIGINT"); } catch {}
    await waitExit(audio, 3000);
    if (audio.exitCode === null) {
      try { audio.kill("SIGTERM"); } catch {}
      await waitExit(audio, 1500);
    }
    if (audio.exitCode === null) {
      try { audio.kill("SIGKILL"); } catch {}
    }
  }

  if (audioFile && !audioFile.closed) {
    await new Promise((resolve) => {
      audioFile.once("close", resolve);
      setTimeout(resolve, 1000);
    });
  }

  if (cdp) {
    await Promise.race([
      cdp.detach().catch(() => {}),
      sleep(1000),
    ]);
  }
}

function finalizeRecording(session, outputPath) {
  const { paths } = session;
  const videoSize = fs.existsSync(paths.video) ? fs.statSync(paths.video).size : 0;
  const audioSize = fs.existsSync(paths.audio) ? fs.statSync(paths.audio).size : 0;

  console.log(new Date().toISOString(), "CAPTURE_PARTS",
    "video=" + videoSize, "audio=" + audioSize);

  if (videoSize < 10000) {
    throw new Error("Video capture missing or too small: " + videoSize);
  }

  try { fs.rmSync(outputPath, { force: true }); } catch {}

  let args;
  if (audioSize >= 19200) {
    args = [
      "-y", "-hide_banner", "-loglevel", "warning",
      "-i", paths.video,
      "-f", "s16le", "-ar", "48000", "-ac", "2", "-i", paths.audio,
      "-map", "0:v:0", "-map", "1:a:0",
      "-c:v", "copy",
      "-c:a", "aac", "-b:a", "128k",
      "-af", "apad",
      "-shortest",
      "-movflags", "+faststart",
      outputPath,
    ];
  } else {
    console.log(new Date().toISOString(), "AUDIO_FALLBACK_SILENCE");
    args = [
      "-y", "-hide_banner", "-loglevel", "warning",
      "-i", paths.video,
      "-f", "lavfi", "-i", "anullsrc=r=48000:cl=stereo",
      "-map", "0:v:0", "-map", "1:a:0",
      "-c:v", "copy",
      "-c:a", "aac", "-b:a", "128k",
      "-shortest",
      "-movflags", "+faststart",
      outputPath,
    ];
  }

  execFileSync("ffmpeg", args, {
    stdio: ["ignore", "ignore", "pipe"],
    maxBuffer: 8 * 1024 * 1024,
  });

  const finalSize = fs.existsSync(outputPath) ? fs.statSync(outputPath).size : 0;
  console.log(new Date().toISOString(), "FINAL_RECORDING_READY", "bytes=" + finalSize);
  if (finalSize < 10000) {
    throw new Error("Final recording missing or too small: " + finalSize);
  }
  return { videoSize, audioSize, finalSize };
}

function cleanupRecording(session, outputPath) {
  if (session?.paths) {
    for (const p of Object.values(session.paths)) {
      try { fs.rmSync(p, { force: true }); } catch {}
    }
  }
  try { fs.rmSync(outputPath, { force: true }); } catch {}
}

module.exports = {
  ensurePulse,
  startRecording,
  stopRecording,
  finalizeRecording,
  cleanupRecording,
  sleep,
};
