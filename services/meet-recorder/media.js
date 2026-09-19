const { spawn, execFileSync } = require("child_process");

const DISPLAY = process.env.DISPLAY || ":99";
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function exec(cmd, args = []) {
  try {
    return execFileSync(cmd, args, { stdio: ["ignore", "pipe", "pipe"] }).toString();
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

  if (!info.includes("Server Name")) {
    throw new Error("PulseAudio unavailable");
  }

  const modules = exec("pactl", ["list", "short", "modules"])
    .split("\n")
    .filter(Boolean);

  for (const line of modules) {
    if (line.includes("module-suspend-on-idle")) {
      const id = line.split(/\s+/)[0];
      exec("pactl", ["unload-module", id]);
    }
  }

  let sources = exec("pactl", ["list", "short", "sources"]);
  if (!sources.includes("qalamrec.monitor")) {
    exec("pactl", [
      "load-module",
      "module-null-sink",
      "sink_name=qalamrec",
      "sink_properties=device.description=QalamRecorder",
    ]);
    await sleep(300);
    sources = exec("pactl", ["list", "short", "sources"]);
  }

  if (!sources.includes("qalamrec.monitor")) {
    throw new Error("Recorder audio source unavailable");
  }

  exec("pactl", ["set-default-sink", "qalamrec"]);
  exec("pactl", ["set-sink-mute", "qalamrec", "0"]);
}

function startRecording(filePath) {
  const args = [
    "-y",
    "-loglevel", "info",
    "-thread_queue_size", "2048",
    "-f", "x11grab",
    "-video_size", "1280x720",
    "-framerate", "20",
    "-i", DISPLAY + ".0",
    "-thread_queue_size", "2048",
    "-f", "pulse",
    "-i", "qalamrec.monitor",
    "-map", "0:v:0",
    "-map", "1:a:0",
    "-c:v", "libx264",
    "-preset", "veryfast",
    "-crf", "28",
    "-pix_fmt", "yuv420p",
    "-c:a", "aac",
    "-b:a", "128k",
    "-movflags", "+faststart",
    filePath,
  ];

  const proc = spawn("ffmpeg", args, {
    env: { ...process.env, DISPLAY },
    stdio: ["pipe", "ignore", "pipe"],
  });

  let tail = "";
  proc.stderr.on("data", (chunk) => {
    tail = (tail + String(chunk)).slice(-12000);
  });

  proc.on("error", (err) => {
    console.log(new Date().toISOString(), "FFMPEG_PROCESS_ERROR", err.message);
  });

  proc.on("exit", (code, signal) => {
    console.log(
      new Date().toISOString(),
      "FFMPEG_EXIT",
      "code=" + code,
      "signal=" + signal,
      tail.replace(/\n/g, " | ").slice(-2500)
    );
  });

  return proc;
}

async function stopRecording(proc) {
  if (!proc || proc.exitCode !== null) return;

  try {
    proc.stdin.write("q");
  } catch {}

  await Promise.race([
    new Promise((resolve) => proc.once("exit", resolve)),
    sleep(10000),
  ]);

  if (proc.exitCode === null) {
    try { proc.kill("SIGTERM"); } catch {}
    await sleep(2000);
  }

  if (proc.exitCode === null) {
    try { proc.kill("SIGKILL"); } catch {}
    await sleep(500);
  }
}

module.exports = { ensurePulse, startRecording, stopRecording, sleep };
