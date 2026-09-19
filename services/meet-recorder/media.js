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
  if (!info.includes("Server Name")) throw new Error("PulseAudio unavailable");

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
  if (!sources.includes("qalamrec.monitor")) throw new Error("Recorder audio source unavailable");
  exec("pactl", ["set-default-sink", "qalamrec"]);
}

function startRecording(filePath) {
  const args = [
    "-y",
    "-thread_queue_size", "1024",
    "-f", "x11grab",
    "-video_size", "1280x720",
    "-framerate", "20",
    "-i", DISPLAY + ".0",
    "-thread_queue_size", "1024",
    "-f", "pulse",
    "-i", "qalamrec.monitor",
    "-c:v", "libx264",
    "-preset", "veryfast",
    "-crf", "28",
    "-pix_fmt", "yuv420p",
    "-c:a", "aac",
    "-b:a", "128k",
    "-movflags", "+faststart",
    filePath,
  ];
  return spawn("ffmpeg", args, {
    env: { ...process.env, DISPLAY },
    stdio: ["pipe", "ignore", "pipe"],
  });
}

async function stopRecording(proc) {
  if (!proc || proc.exitCode !== null) return;
  try { proc.stdin.write("q"); } catch {}
  await Promise.race([
    new Promise((resolve) => proc.once("exit", resolve)),
    sleep(10000),
  ]);
  if (proc.exitCode === null) {
    try { proc.kill("SIGTERM"); } catch {}
    await sleep(2000);
  }
}

module.exports = { ensurePulse, startRecording, stopRecording, sleep };
