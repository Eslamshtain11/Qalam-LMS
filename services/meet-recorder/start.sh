#!/bin/sh
set -eu

echo QALAM_INTEGRATED_BOOT

apt-get update -qq
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
  chromium ffmpeg pulseaudio pulseaudio-utils xvfb x11vnc websockify novnc git curl >/dev/null

rm -f /data/chrome-profile/SingletonLock \
      /data/chrome-profile/SingletonSocket \
      /data/chrome-profile/SingletonCookie 2>/dev/null || true

export DISPLAY=:99
export PULSE_SINK=qalamrec

Xvfb :99 -screen 0 1280x720x24 -ac >/tmp/xvfb.log 2>&1 &
sleep 1

pulseaudio --daemonize=yes --exit-idle-time=-1 --disable-shm=yes >/tmp/pulse.log 2>&1 || true
sleep 1

if ! pactl list short sources 2>/dev/null | grep -q qalamrec.monitor; then
  pactl load-module module-null-sink sink_name=qalamrec rate=48000 channels=2 \
    sink_properties=device.description=QalamRecorder >/dev/null
fi
pactl set-default-sink qalamrec
pactl set-sink-mute qalamrec 0 || true

x11vnc \
  -display :99 \
  -forever \
  -shared \
  -rfbport 5900 \
  -listen 127.0.0.1 \
  -passwd "$QALAM_VNC_PASSWORD" \
  >/tmp/x11vnc.log 2>&1 &

websockify \
  --web=/usr/share/novnc \
  8080 \
  127.0.0.1:5900 \
  >/tmp/websockify.log 2>&1 &

chromium \
  --no-sandbox \
  --disable-dev-shm-usage \
  --disable-gpu \
  --password-store=basic \
  --window-position=0,0 \
  --window-size=1280,720 \
  --no-first-run \
  --no-default-browser-check \
  --autoplay-policy=no-user-gesture-required \
  --remote-debugging-port=9222 \
  --remote-debugging-address=127.0.0.1 \
  --remote-allow-origins=* \
  --user-data-dir=/data/chrome-profile \
  'https://accounts.google.com/signin/v2/identifier?service=accountsettings&continue=https%3A%2F%2Fmyaccount.google.com%2F' \
  >/tmp/chromium.log 2>&1 &

for i in $(seq 1 30); do
  if curl -fsS http://127.0.0.1:9222/json/version >/dev/null 2>&1; then
    echo QALAM_CHROMIUM_READY
    break
  fi
  sleep 1
done

rm -rf /tmp/qalam-src
git clone --depth=1 https://github.com/Eslamshtain11/Qalam-LMS.git /tmp/qalam-src >/tmp/git.log 2>&1

cd /tmp/qalam-src/services/meet-recorder
npm install --omit=dev >/tmp/npm.log 2>&1

echo QALAM_RECORDER_PROCESS_START
exec node index.js
