async function callControl(action, payload = {}) {
  const url = process.env.QALAM_CONTROL_URL;
  const key = process.env.QALAM_BOT_KEY;
  const res = await fetch(url, {
    method: "POST",
    headers: {
      "content-type": "application/json",
      "x-qalam-recorder-key": key,
    },
    body: JSON.stringify({ action, ...payload }),
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error("control " + action + " " + res.status + " " + JSON.stringify(json));
  }
  return json;
}

async function uploadFileToDrive(job, filePath) {
  const fs = require("fs");
  if (!fs.existsSync(filePath)) throw new Error("Recorded file missing");

  const stat = fs.statSync(filePath);
  if (stat.size < 10000) throw new Error("Recorded file too small");

  const safeTitle = String(job.title || "Qalam class").replace(/[\\/:*?"<>|]/g, "-");
  const fileName = safeTitle + " - " + job.occurrenceDate + ".mp4";

  const init = await callControl("upload_start", {
    runId: job.runId,
    fileName,
  });
  if (!init.uploadUrl) throw new Error("Upload URL missing");

  const uploaded = await fetch(init.uploadUrl, {
    method: "PUT",
    headers: {
      "Content-Type": "video/mp4",
      "Content-Length": String(stat.size),
    },
    body: fs.createReadStream(filePath),
    duplex: "half",
  });

  const result = await uploaded.json().catch(() => ({}));
  if (!uploaded.ok || !result.id) {
    throw new Error(
      "Drive upload failed " + uploaded.status + " " + JSON.stringify(result).slice(0, 500)
    );
  }

  return { fileId: String(result.id) };
}

async function completeUploadedRecording(runId, fileId, durationMinutes) {
  return callControl("complete", {
    runId,
    fileId,
    durationMinutes: Math.max(1, Number(durationMinutes || 1)),
  });
}

async function uploadRecording(job, filePath, startedAt) {
  const uploaded = await uploadFileToDrive(job, filePath);
  const durationMinutes = Math.max(1, Math.round((Date.now() - startedAt) / 60000));
  return completeUploadedRecording(job.runId, uploaded.fileId, durationMinutes);
}

module.exports = {
  callControl,
  uploadRecording,
  uploadFileToDrive,
  completeUploadedRecording,
};
