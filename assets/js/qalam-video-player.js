/* Qalam LMS Video Player 0.4.2
 * Custom presentation/control layer over the official YouTube IFrame Player API.
 * YouTube native controls are disabled; all pointer interaction is intercepted.
 */
(function () {
    'use strict';

    var apiPromise = null;
    var playerRegistry = new WeakMap();

    function loadYouTubeApi() {
        if (window.YT && typeof window.YT.Player === 'function') {
            return Promise.resolve(window.YT);
        }
        if (apiPromise) {
            return apiPromise;
        }

        apiPromise = new Promise(function (resolve, reject) {
            var resolved = false;
            var previousReady = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = function () {
                if (typeof previousReady === 'function') {
                    try { previousReady(); } catch (e) { /* preserve other integrations */ }
                }
                resolved = true;
                resolve(window.YT);
            };

            if (!document.getElementById('qalam-youtube-iframe-api')) {
                var script = document.createElement('script');
                script.id = 'qalam-youtube-iframe-api';
                script.src = 'https://www.youtube.com/iframe_api';
                script.async = true;
                script.onerror = function () { reject(new Error('YouTube API failed to load')); };
                (document.head || document.documentElement).appendChild(script);
            }

            window.setTimeout(function () {
                if (!resolved && window.YT && typeof window.YT.Player === 'function') {
                    resolved = true;
                    resolve(window.YT);
                }
            }, 2500);

            window.setTimeout(function () {
                if (!resolved) {
                    reject(new Error('YouTube API timeout'));
                }
            }, 12000);
        });

        return apiPromise;
    }

    function formatTime(seconds) {
        seconds = Math.max(0, Math.floor(Number(seconds) || 0));
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var secs = seconds % 60;
        if (hours > 0) {
            return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        }
        return String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    }

    function qualityLabel(value) {
        var labels = {
            highres: '4K+', hd2160: '4K', hd1440: '2K', hd1080: '1080p',
            hd720: '720p', large: '480p', medium: '360p', small: '240p', tiny: '144p',
            default: 'تلقائي'
        };
        return labels[value] || 'تلقائي';
    }

    function q(root, selector) {
        return root.querySelector(selector);
    }

    function qa(root, selector) {
        return Array.prototype.slice.call(root.querySelectorAll(selector));
    }

    function QalamVideoPlayer(root) {
        this.root = root;
        this.host = q(root, '[data-qalam-youtube-host]');
        this.videoId = root.getAttribute('data-video-id') || '';
        this.origin = root.getAttribute('data-origin') || '';
        this.player = null;
        this.ready = false;
        this.dragging = false;
        this.hideTimer = null;
        this.tickTimer = null;
        this.toastTimer = null;
        this.pseudoFullscreen = false;
        this.syncTimer = null;
        this.tracking = this.readTrackingData();
        this.maxWatchTime = Number(this.tracking.best_watch_time || 0);

        this.progress = q(root, '[data-qalam-video-progress]');
        this.volume = q(root, '[data-qalam-video-volume]');
        this.currentTime = q(root, '[data-qalam-current-time]');
        this.duration = q(root, '[data-qalam-duration]');
        this.speedLabel = q(root, '[data-qalam-speed-label]');
        this.qualityLabel = q(root, '[data-qalam-quality-label]');
        this.loading = q(root, '[data-qalam-video-loading]');
        this.toast = q(root, '[data-qalam-video-toast]');
        this.speedMenu = q(root, '[data-qalam-speed-options]');
        this.qualityMenu = q(root, '[data-qalam-quality-status]');
        this.captionBox = q(root, '[data-qalam-video-caption]');
        this.captionToggle = q(root, '[data-qalam-caption-toggle]');
        this.subtitleUrl = root.getAttribute('data-qalam-subtitle-url') || '';
        this.subtitleCues = [];
        this.captionsEnabled = false;
        try { this.ads = JSON.parse(root.getAttribute('data-qalam-video-ads') || '[]'); } catch (e) { this.ads = []; }
        this.adCues = []; this.adShown = {}; this.adActive = false; this.adResume = false; this.adTimer = null;
        this.adOverlay = q(root, '[data-qalam-video-ad]'); this.adMedia = q(root, '[data-qalam-video-ad-media]');
        this.adSkip = q(root, '[data-qalam-video-ad-skip]'); this.adCountdown = q(root, '[data-qalam-video-ad-countdown]');

        this.root.classList.add('qalam-video-is-loading', 'qalam-video-is-paused');
        this.bindUi();
        this.boot();
    }

    QalamVideoPlayer.prototype.boot = function () {
        var self = this;
        loadYouTubeApi().then(function (YT) {
            var playerVars = {
                autoplay: 0,
                controls: 0,
                disablekb: 1,
                fs: 0,
                iv_load_policy: 3,
                playsinline: 1,
                rel: 0,
                cc_load_policy: 0,
                enablejsapi: 1
            };
            if (self.origin) {
                playerVars.origin = self.origin;
            }

            self.player = new YT.Player(self.host, {
                videoId: self.videoId,
                width: '100%',
                height: '100%',
                playerVars: playerVars,
                events: {
                    onReady: function (event) { self.onReady(event); },
                    onStateChange: function (event) { self.onStateChange(event); },
                    onPlaybackRateChange: function (event) { self.onRateChange(event); },
                    onPlaybackQualityChange: function (event) { self.onQualityChange(event); },
                    onError: function (event) { self.onError(event); }
                }
            });
        }).catch(function () {
            self.showError('تعذر تحميل مشغل الفيديو. حدّث الصفحة وحاول مرة أخرى.');
        });
    };

    QalamVideoPlayer.prototype.onReady = function () {
        this.ready = true;
        this.root.classList.remove('qalam-video-is-loading');
        if (this.loading) {
            this.loading.setAttribute('hidden', 'hidden');
        }
        this.syncVolume();
        this.buildSpeedMenu();
        this.buildQualityMenu();
        this.loadCaptions();
        this.bindCaptionToggle();
        this.bindAdUi();
        this.updateTimeline(true);
        this.applyLessonTrackingPolicy();
        this.restoreWatchPosition();
        this.publishTutorPlayerAdapter();
        this.startTicker();
        this.showControls(false);
    };

    QalamVideoPlayer.prototype.onStateChange = function (event) {
        if (!window.YT) { return; }
        var state = event.data;
        var playing = state === window.YT.PlayerState.PLAYING;
        var buffering = state === window.YT.PlayerState.BUFFERING;

        this.root.classList.toggle('qalam-video-is-playing', playing);
        this.root.classList.toggle('qalam-video-is-paused', !playing);
        this.root.classList.toggle('qalam-video-is-buffering', buffering);

        if (playing) {
            this.startProgressSync();
            this.scheduleHide();
        } else {
            this.stopProgressSync();
            if (state === window.YT.PlayerState.PAUSED) { this.syncLessonProgress(false); }
            if (state === window.YT.PlayerState.ENDED) { this.syncLessonProgress(true); }
            this.showControls(false);
        }
        this.updateTimeline(true);
    };

    QalamVideoPlayer.prototype.onRateChange = function (event) {
        var rate = Number(event.data) || 1;
        if (this.speedLabel) {
            this.speedLabel.textContent = String(rate).replace(/\.0$/, '') + '×';
        }
        this.markSpeed(rate);
    };

    QalamVideoPlayer.prototype.onQualityChange = function (event) {
        var label = qualityLabel(event.data);
        if (this.qualityLabel) {
            this.qualityLabel.textContent = label;
        }
        this.renderQualityStatus(label);
    };

    QalamVideoPlayer.prototype.onError = function (event) {
        var messages = {
            2: 'رابط الفيديو غير صالح.',
            5: 'تعذر تشغيل الفيديو في المتصفح الحالي.',
            100: 'الفيديو غير متاح أو تم حذفه.',
            101: 'صاحب الفيديو لا يسمح بتشغيله خارج YouTube.',
            150: 'صاحب الفيديو لا يسمح بتشغيله خارج YouTube.'
        };
        this.showError(messages[event.data] || 'حدث خطأ أثناء تشغيل الفيديو.');
    };

    QalamVideoPlayer.prototype.showError = function (message) {
        this.root.classList.remove('qalam-video-is-loading');
        this.root.classList.add('qalam-video-has-error');
        if (this.loading) {
            this.loading.removeAttribute('hidden');
            this.loading.innerHTML = '<span class="qalam-video-error-mark">!</span><span>' + message + '</span>';
        }
    };

    QalamVideoPlayer.prototype.readTrackingData = function () {
        var scope = this.root.closest('.tutor-lesson-video-wrapper') || this.root.closest('.tutor-course-topic-single-body') || document;
        var input = scope.querySelector ? scope.querySelector('#tutor_video_tracking_information') : null;
        if (!input) { input = document.getElementById('tutor_video_tracking_information'); }
        if (!input || !input.value) { return {}; }
        try { return JSON.parse(input.value) || {}; } catch (e) { return {}; }
    };

    QalamVideoPlayer.prototype.isTrackingRestricted = function () {
        return Boolean(this.tracking && this.tracking.strict_mode && this.tracking.control_video_lesson_completion && this.tracking.is_enrolled && !this.tracking.lesson_completed);
    };

    QalamVideoPlayer.prototype.applyLessonTrackingPolicy = function () {
        if (!this.isTrackingRestricted()) { return; }
        var duration = Number(this.safeCall('getDuration', 0)) || Number(this.tracking.video_duration || 0);
        var required = Number(this.tracking.required_percentage || 0);
        var watched = Number(this.tracking.best_watch_time || 0);
        if (duration > 0 && required > 0 && (watched / duration * 100) < required) {
            var button = document.querySelector('button[name="complete_lesson_btn"]');
            if (button) { button.disabled = true; }
        }
    };

    QalamVideoPlayer.prototype.restoreWatchPosition = function () {
        var saved = Number(this.tracking.best_watch_time || 0);
        if (!this.ready || saved <= 1) { return; }
        var duration = Number(this.safeCall('getDuration', 0)) || 0;
        if (duration > 0 && saved < duration - 2) {
            this.safeCall('seekTo', null, [Math.floor(saved), true]);
            this.maxWatchTime = Math.max(this.maxWatchTime, saved);
        }
    };

    QalamVideoPlayer.prototype.publishTutorPlayerAdapter = function () {
        var self = this;
        var adapter = {
            provider: 'youtube',
            get currentTime() { return Number(self.safeCall('getCurrentTime', 0)) || 0; },
            set currentTime(value) { self.safeCall('seekTo', null, [Number(value) || 0, true]); },
            get duration() { return Number(self.safeCall('getDuration', 0)) || 0; },
            get playing() { return self.root.classList.contains('qalam-video-is-playing'); },
            play: function () { self.safeCall('playVideo'); },
            pause: function () { self.safeCall('pauseVideo'); },
            stop: function () { self.safeCall('stopVideo'); },
            embed: { seekTo: function (time) { self.safeCall('seekTo', null, [Number(time) || 0, true]); } },
            qalamPlayer: true
        };
        window.TutorLessonPlayer = adapter;
        try {
            window.dispatchEvent(new CustomEvent('tutorLessonPlayerReady', { detail: { player: adapter } }));
        } catch (e) { /* compatibility event is best-effort */ }
    };

    QalamVideoPlayer.prototype.startProgressSync = function () {
        var self = this;
        this.stopProgressSync();
        if (!this.tracking || !this.tracking.post_id) { return; }
        this.syncTimer = window.setInterval(function () { self.syncLessonProgress(false); }, 10000);
    };

    QalamVideoPlayer.prototype.stopProgressSync = function () {
        if (this.syncTimer) { window.clearInterval(this.syncTimer); this.syncTimer = null; }
    };

    QalamVideoPlayer.prototype.syncLessonProgress = function (ended) {
        if (!this.ready || !this.tracking || !this.tracking.post_id || !window._tutorobject || !window._tutorobject.ajaxurl) { return; }
        var current = Number(this.safeCall('getCurrentTime', 0)) || 0;
        var duration = Number(this.safeCall('getDuration', 0)) || Number(this.tracking.video_duration || 0);
        this.maxWatchTime = Math.max(this.maxWatchTime, current);
        this.enableCompleteButtonIfEligible(current, duration);

        var nonceKey = window._tutorobject.nonce_key;
        var body = new URLSearchParams();
        body.set('action', 'sync_video_playback');
        body.set('post_id', String(this.tracking.post_id));
        body.set('currentTime', String(current));
        body.set('duration', String(duration));
        if (ended) { body.set('is_ended', '1'); }
        if (nonceKey && window._tutorobject[nonceKey]) { body.set(nonceKey, window._tutorobject[nonceKey]); }

        try {
            window.fetch(window._tutorobject.ajaxurl, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString(), keepalive: Boolean(ended)
            }).catch(function () { /* playback must never fail because tracking failed */ });
        } catch (e) { /* no-op */ }
    };

    QalamVideoPlayer.prototype.enableCompleteButtonIfEligible = function (current, duration) {
        if (!this.isTrackingRestricted() || !duration) { return; }
        var required = Number(this.tracking.required_percentage || 0);
        if (required <= 0 || (current / duration * 100) < required) { return; }
        var button = document.querySelector('button[name="complete_lesson_btn"]');
        if (!button) { return; }
        button.disabled = false;
        var next = button.nextElementSibling;
        if (next && next.classList && next.classList.contains('tutor-tooltip')) { next.remove(); }
    };

    QalamVideoPlayer.prototype.bindUi = function () {
        var self = this;

        qa(this.root, '[data-qalam-video-action]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                self.handleAction(button.getAttribute('data-qalam-video-action'));
                self.showControls(true);
            });
        });

        var shield = q(this.root, '[data-qalam-video-toggle]');
        if (shield) {
            shield.addEventListener('click', function () {
                self.togglePlayback();
                self.showControls(true);
            });
            shield.addEventListener('dblclick', function (event) {
                event.preventDefault();
                self.toggleFullscreen();
            });
        }

        if (this.progress) {
            this.progress.addEventListener('pointerdown', function () { self.dragging = true; self.showControls(false); });
            this.progress.addEventListener('input', function () {
                var duration = self.safeCall('getDuration', 0);
                var ratio = Number(self.progress.value) / 1000;
                if (self.currentTime) { self.currentTime.textContent = formatTime(duration * ratio); }
                self.paintRange(self.progress, ratio * 100, '--qalam-progress');
            });
            this.progress.addEventListener('change', function () {
                var duration = self.safeCall('getDuration', 0);
                var target = duration * (Number(self.progress.value) / 1000);
                if (self.isTrackingRestricted() && target > self.maxWatchTime + 2) {
                    target = Math.min(duration, self.maxWatchTime);
                    self.toastMessage('أكمل المشاهدة أولًا');
                }
                if (duration > 0) { self.safeCall('seekTo', null, [target, true]); }
                self.dragging = false;
                self.scheduleHide();
            });
            this.progress.addEventListener('pointerup', function () { self.dragging = false; });
        }

        if (this.volume) {
            this.volume.addEventListener('input', function () {
                var value = Math.max(0, Math.min(100, Number(self.volume.value) || 0));
                if (self.ready) {
                    self.safeCall('setVolume', null, [value]);
                    if (value > 0 && self.safeCall('isMuted', false)) { self.safeCall('unMute'); }
                }
                self.root.classList.toggle('qalam-video-is-muted', value === 0);
                self.paintRange(self.volume, value, '--qalam-volume');
                self.showControls(false);
            });
        }

        qa(this.root, '[data-qalam-video-menu-button]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var name = button.getAttribute('data-qalam-video-menu-button');
                self.toggleMenu(name, button);
                self.showControls(false);
            });
        });

        this.root.addEventListener('mousemove', function () { self.showControls(true); });
        this.root.addEventListener('pointermove', function () { self.showControls(true); });
        this.root.addEventListener('touchstart', function () { self.showControls(true); }, { passive: true });
        this.root.addEventListener('contextmenu', function (event) { event.preventDefault(); });
        this.root.addEventListener('dragstart', function (event) { event.preventDefault(); });
        this.root.addEventListener('keydown', function (event) { self.onKeyDown(event); });

        document.addEventListener('click', function (event) {
            if (!self.root.contains(event.target)) { self.closeMenus(); }
        });
        document.addEventListener('fullscreenchange', function () { self.syncFullscreenState(); });
        document.addEventListener('webkitfullscreenchange', function () { self.syncFullscreenState(); });
    };

    QalamVideoPlayer.prototype.onKeyDown = function (event) {
        var tag = String(event.target && event.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'button' || tag === 'select' || tag === 'textarea') { return; }

        if (event.code === 'Space' || event.key === ' ') {
            event.preventDefault(); this.togglePlayback();
        } else if (event.key === 'ArrowLeft') {
            event.preventDefault(); this.seekBy(-5);
        } else if (event.key === 'ArrowRight') {
            event.preventDefault(); this.seekBy(5);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault(); this.changeVolume(5);
        } else if (event.key === 'ArrowDown') {
            event.preventDefault(); this.changeVolume(-5);
        } else if (String(event.key).toLowerCase() === 'm') {
            event.preventDefault(); this.toggleMute();
        } else if (String(event.key).toLowerCase() === 'f') {
            event.preventDefault(); this.toggleFullscreen();
        } else if (event.key === 'Escape' && this.pseudoFullscreen) {
            this.exitPseudoFullscreen();
        }
        this.showControls(true);
    };

    QalamVideoPlayer.prototype.handleAction = function (action) {
        if (action === 'toggle') { this.togglePlayback(); }
        else if (action === 'backward') { this.seekBy(-10); }
        else if (action === 'forward') { this.seekBy(10); }
        else if (action === 'mute') { this.toggleMute(); }
        else if (action === 'fullscreen') { this.toggleFullscreen(); }
    };

    QalamVideoPlayer.prototype.togglePlayback = function () {
        if (!this.ready || !window.YT) { return; }
        var state = this.safeCall('getPlayerState', -1);
        if (state === window.YT.PlayerState.PLAYING || state === window.YT.PlayerState.BUFFERING) {
            this.safeCall('pauseVideo');
        } else {
            this.safeCall('playVideo');
        }
    };

    QalamVideoPlayer.prototype.seekBy = function (amount) {
        if (!this.ready) { return; }
        var duration = this.safeCall('getDuration', 0);
        var current = this.safeCall('getCurrentTime', 0);
        var next = Math.max(0, Math.min(duration || current + amount, current + amount));
        if (this.isTrackingRestricted() && amount > 0 && next > this.maxWatchTime + 2) {
            next = Math.min(duration || this.maxWatchTime, this.maxWatchTime);
            this.toastMessage('أكمل المشاهدة أولًا');
        }
        this.safeCall('seekTo', null, [next, true]);
        this.toastMessage((amount > 0 ? '+' : '−') + Math.abs(amount) + ' ث');
        this.updateTimeline(true);
    };

    QalamVideoPlayer.prototype.changeVolume = function (amount) {
        var value = Math.max(0, Math.min(100, this.safeCall('getVolume', 100) + amount));
        this.safeCall('setVolume', null, [value]);
        if (value > 0) { this.safeCall('unMute'); }
        if (this.volume) { this.volume.value = String(value); }
        this.syncVolume();
    };

    QalamVideoPlayer.prototype.toggleMute = function () {
        if (!this.ready) { return; }
        if (this.safeCall('isMuted', false) || this.safeCall('getVolume', 100) === 0) {
            this.safeCall('unMute');
            if (this.safeCall('getVolume', 0) === 0) { this.safeCall('setVolume', null, [70]); }
        } else {
            this.safeCall('mute');
        }
        this.syncVolume();
    };

    QalamVideoPlayer.prototype.syncVolume = function () {
        if (!this.ready) { return; }
        var muted = this.safeCall('isMuted', false);
        var value = muted ? 0 : this.safeCall('getVolume', 100);
        this.root.classList.toggle('qalam-video-is-muted', muted || value === 0);
        if (this.volume) {
            this.volume.value = String(value);
            this.paintRange(this.volume, value, '--qalam-volume');
        }
    };

    QalamVideoPlayer.prototype.buildQualityMenu = function () {
        if (!this.qualityMenu) { return; }
        this.renderQualityStatus('تلقائي');
    };

    QalamVideoPlayer.prototype.renderQualityStatus = function (currentLabel) {
        if (!this.qualityMenu) { return; }
        var label = currentLabel || (this.qualityLabel ? this.qualityLabel.textContent : 'تلقائي');
        this.qualityMenu.innerHTML = '';
        var box = document.createElement('div');
        box.className = 'qalam-video-quality-auto';
        var title = document.createElement('strong');
        title.textContent = label === 'تلقائي' ? 'الجودة: تلقائية' : 'الجودة الحالية: ' + label;
        var info = document.createElement('span');
        info.textContent = 'الجودة بيديرها YouTube تلقائيًا. قلم يعرض الجودة الحالية فقط لأن YouTube لا يتيح اختيارها يدويًا من الـIFrame API.';
        var badge = document.createElement('small');
        badge.textContent = 'الوضع: تلقائي';
        box.appendChild(title); box.appendChild(info); box.appendChild(badge);
        this.qualityMenu.appendChild(box);
    };

    QalamVideoPlayer.prototype.buildSpeedMenu = function () {
        if (!this.speedMenu || !this.ready) { return; }
        var self = this;
        var rates = this.safeCall('getAvailablePlaybackRates', [1]);
        if (!Array.isArray(rates) || !rates.length) { rates = [1]; }
        this.speedMenu.innerHTML = '';
        rates.slice().reverse().forEach(function (rate) {
            var button = document.createElement('button');
            button.type = 'button';
            button.setAttribute('role', 'menuitemradio');
            button.setAttribute('aria-checked', rate === 1 ? 'true' : 'false');
            button.setAttribute('data-qalam-rate', String(rate));
            button.textContent = String(rate).replace(/\.0$/, '') + '×';
            button.addEventListener('click', function (event) {
                event.preventDefault(); event.stopPropagation();
                self.safeCall('setPlaybackRate', null, [rate]);
                self.markSpeed(rate);
                if (self.speedLabel) { self.speedLabel.textContent = String(rate).replace(/\.0$/, '') + '×'; }
                self.closeMenus();
                self.showControls(true);
            });
            self.speedMenu.appendChild(button);
        });
    };

    QalamVideoPlayer.prototype.markSpeed = function (rate) {
        qa(this.root, '[data-qalam-rate]').forEach(function (button) {
            button.setAttribute('aria-checked', Number(button.getAttribute('data-qalam-rate')) === Number(rate) ? 'true' : 'false');
        });
    };

    QalamVideoPlayer.prototype.toggleMenu = function (name, button) {
        var menu = q(this.root, '[data-qalam-video-menu="' + name + '"]');
        if (!menu) { return; }
        var opening = menu.hasAttribute('hidden');
        this.closeMenus();
        if (opening) {
            menu.removeAttribute('hidden');
            button.setAttribute('aria-expanded', 'true');
            this.root.classList.add('qalam-video-menu-open');
        }
    };

    QalamVideoPlayer.prototype.closeMenus = function () {
        qa(this.root, '[data-qalam-video-menu]').forEach(function (menu) { menu.setAttribute('hidden', 'hidden'); });
        qa(this.root, '[data-qalam-video-menu-button]').forEach(function (button) { button.setAttribute('aria-expanded', 'false'); });
        this.root.classList.remove('qalam-video-menu-open');
    };

    QalamVideoPlayer.prototype.bindCaptionToggle = function () {
        var self = this;
        if (!this.captionToggle) { return; }
        this.captionToggle.addEventListener('click', function (event) {
            event.preventDefault(); event.stopPropagation();
            if (!self.subtitleUrl) { return; }
            self.setQalamCaptions(!self.captionsEnabled);
            self.showControls(true);
        });
    };

    QalamVideoPlayer.prototype.setQalamCaptions = function (enabled) {
        this.captionsEnabled = Boolean(enabled && this.subtitleUrl && this.subtitleCues.length);
        if (this.captionToggle) {
            this.captionToggle.setAttribute('aria-pressed', this.captionsEnabled ? 'true' : 'false');
            this.captionToggle.textContent = this.captionsEnabled ? 'إيقاف' : 'تشغيل';
        }
        this.root.classList.toggle('qalam-captions-enabled', this.captionsEnabled);
        if (!this.captionsEnabled && this.captionBox) {
            this.captionBox.hidden = true;
            this.captionBox.textContent = '';
        } else if (this.captionsEnabled) {
            this.updateCaption(Number(this.safeCall('getCurrentTime', 0)) || 0);
        }
    };

    QalamVideoPlayer.prototype.parseSubtitleTime = function (value) {
        var parts = String(value || '').replace(',', '.').trim().split(':');
        if (parts.length < 2) { return 0; }
        var sec = 0;
        if (parts.length === 3) { sec += Number(parts[0]) * 3600 + Number(parts[1]) * 60 + Number(parts[2]); }
        else { sec += Number(parts[0]) * 60 + Number(parts[1]); }
        return Number(sec) || 0;
    };

    QalamVideoPlayer.prototype.loadCaptions = function () {
        var self = this;
        if (!this.subtitleUrl) { if (this.captionToggle) { this.captionToggle.disabled = true; } return; }
        window.fetch(this.subtitleUrl, {credentials:'same-origin'}).then(function (response) {
            if (!response.ok) { throw new Error('subtitle'); }
            return response.text();
        }).then(function (text) {
            text = text.replace(/^WEBVTT[^\n]*\n+/i, '').replace(/\r/g, '');
            var blocks = text.split(/\n\s*\n/); var cues = [];
            blocks.forEach(function (block) {
                var lines = block.split('\n').filter(Boolean); if (!lines.length) { return; }
                var timingIndex = lines.findIndex(function (line) { return line.indexOf('-->') !== -1; });
                if (timingIndex < 0) { return; }
                var timing = lines[timingIndex].split('-->');
                var start = self.parseSubtitleTime(timing[0]); var end = self.parseSubtitleTime(String(timing[1]).split(/\s+/)[0]);
                var body = lines.slice(timingIndex + 1).join('\n').replace(/<[^>]+>/g, '').trim();
                if (end > start && body) { cues.push({start:start,end:end,text:body}); }
            });
            self.subtitleCues = cues;
            if (self.captionToggle) {
                self.captionToggle.disabled = !cues.length;
                self.captionToggle.setAttribute('title', cues.length ? 'تشغيل أو إيقاف ترجمة قلم' : 'ملف الترجمة لا يحتوي مقاطع صالحة');
            }
            self.setQalamCaptions(false);
        }).catch(function () { if (self.captionToggle) { self.captionToggle.disabled = true; self.captionToggle.textContent='غير متاحة'; } });
    };

    QalamVideoPlayer.prototype.updateCaption = function (current) {
        if (!this.captionBox || !this.captionsEnabled || !this.subtitleCues.length || this.adActive) { return; }
        var cue = null;
        for (var i=0;i<this.subtitleCues.length;i++) { if (current >= this.subtitleCues[i].start && current <= this.subtitleCues[i].end) { cue=this.subtitleCues[i]; break; } }
        if (cue) { this.captionBox.textContent = cue.text; this.captionBox.hidden = false; }
        else { this.captionBox.hidden = true; this.captionBox.textContent = ''; }
    };

    QalamVideoPlayer.prototype.bindAdUi = function () {
        var self=this;
        if (this.adSkip) { this.adSkip.addEventListener('click',function(e){e.preventDefault(); if(!self.adSkip.disabled){self.finishAd('skipped');}}); }
    };

    QalamVideoPlayer.prototype.prepareAutoAdCues = function (duration) {
        if (!duration || !Array.isArray(this.ads) || !this.ads.length) { return; }
        var cues=[];
        this.ads.forEach(function(ad){
            (Array.isArray(ad.cues)?ad.cues:[]).forEach(function(sec){if(sec>0&&sec<duration-2){cues.push({time:Number(sec),ad:ad});}});
            var count=Math.max(0,Number(ad.auto_count)||0);
            if(count>0){for(var i=1;i<=count;i++){var t=duration*(i/(count+1)); if(t>2&&t<duration-2){cues.push({time:t,ad:ad});}}}
        });
        cues.sort(function(a,b){return a.time-b.time;}); this.adCues=cues;
    };

    QalamVideoPlayer.prototype.checkAdCues = function (current) {
        if (this.adActive || !this.root.classList.contains('qalam-video-is-playing') || !this.adCues.length) { return; }
        for (var i=0;i<this.adCues.length;i++) {
            var cue=this.adCues[i], key=String(cue.ad.id)+'@'+Math.round(cue.time);
            if (!this.adShown[key] && current >= cue.time && current < cue.time + 1.25) { this.adShown[key]=true; this.startAd(cue.ad); break; }
        }
    };

    QalamVideoPlayer.prototype.trackAdEvent = function (ad, eventName) {
        if (!ad || !ad.id || !window.QalamVideoRuntime || !QalamVideoRuntime.ajaxurl || !QalamVideoRuntime.adNonce) { return; }
        try {
            var body = new URLSearchParams(); body.set('action','qalam_160_video_ad_event'); body.set('nonce',QalamVideoRuntime.adNonce); body.set('ad_id',String(ad.id)); body.set('event_name',String(eventName||''));
            body.set('lesson_id',String((this.tracking&&this.tracking.post_id)||0));
            window.fetch(QalamVideoRuntime.ajaxurl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:body.toString(),keepalive:true}).catch(function(){});
        } catch(e) {}
    };

    QalamVideoPlayer.prototype.startAd = function (ad) {
        if (!this.adOverlay || !this.adMedia || !ad || !ad.url) { return; }
        var self=this; this.currentAd=ad; this.adActive=true; this.adResume=this.safeCall('getPlayerState',-1)===window.YT.PlayerState.PLAYING;
        this.safeCall('pauseVideo'); this.root.classList.add('qalam-video-ad-active'); this.adOverlay.hidden=false; this.adMedia.innerHTML=''; this.trackAdEvent(ad,'impression');
        if(this.captionBox){this.captionBox.hidden=true;}
        var media;
        var path=String(ad.url||'').split('?')[0].toLowerCase();
        var isImage=ad.type==='image'||String(ad.mime||'').indexOf('image/')===0||/\.(jpe?g|png|gif|webp|avif)$/.test(path);
        if(isImage){
            media=document.createElement('img'); media.alt=ad.title||'إعلان'; media.decoding='async';
            media.addEventListener('load',function(){self.adMedia.classList.add('qalam-ad-media-ready');});
            media.addEventListener('error',function(){self.adMedia.innerHTML='<div class="qalam-ad-media-error">تعذر تحميل صورة الإعلان</div>';});
            media.src=ad.url;
        } else {
            media=document.createElement('video'); media.src=ad.url; media.autoplay=true; media.playsInline=true; media.controls=false;
            media.addEventListener('loadeddata',function(){self.adMedia.classList.add('qalam-ad-media-ready');});
            media.addEventListener('error',function(){self.adMedia.innerHTML='<div class="qalam-ad-media-error">تعذر تحميل فيديو الإعلان</div>';});
            media.addEventListener('ended',function(){self.finishAd('completed');});
        }
        this.adMedia.classList.remove('qalam-ad-media-ready');
        this.adMedia.appendChild(media);
        var skip=Math.max(0,Number(ad.skip_after)||0); var remaining=skip;
        if(this.adSkip){this.adSkip.disabled=skip>0; this.adSkip.textContent=skip>0?'تخطي بعد '+skip+' ث':'تخطي الإعلان';}
        if(this.adCountdown){this.adCountdown.textContent='إعلان · '+(ad.title||'');}
        window.clearInterval(this.adTimer);
        this.adTimer=window.setInterval(function(){remaining--; if(self.adSkip&&remaining>0){self.adSkip.textContent='تخطي بعد '+remaining+' ث';} else if(self.adSkip){self.adSkip.disabled=false;self.adSkip.textContent='تخطي الإعلان';}},1000);
        if(ad.type==='image'){window.setTimeout(function(){if(self.adActive){self.finishAd('completed');}},Math.max(3,Number(ad.image_duration)||10)*1000);}
    };

    QalamVideoPlayer.prototype.finishAd = function () {
        if(!this.adActive){return;} this.adActive=false; this.currentAd=null; window.clearInterval(this.adTimer); this.adTimer=null;
        if(this.adMedia){var v=this.adMedia.querySelector('video'); if(v){try{v.pause();}catch(e){}} this.adMedia.innerHTML='';}
        if(this.adOverlay){this.adOverlay.hidden=true;} this.root.classList.remove('qalam-video-ad-active');
        if(this.adResume){this.safeCall('playVideo');} this.adResume=false; this.showControls(true);
    };

    QalamVideoPlayer.prototype.toggleFullscreen = function () {
        var fullscreenEl = document.fullscreenElement || document.webkitFullscreenElement;
        if (fullscreenEl === this.root) {
            if (document.exitFullscreen) { document.exitFullscreen(); }
            else if (document.webkitExitFullscreen) { document.webkitExitFullscreen(); }
            return;
        }
        if (this.pseudoFullscreen) {
            this.exitPseudoFullscreen();
            return;
        }

        var request = this.root.requestFullscreen || this.root.webkitRequestFullscreen;
        if (request) {
            try {
                var result = request.call(this.root);
                if (result && typeof result.catch === 'function') {
                    var self = this;
                    result.catch(function () { self.enterPseudoFullscreen(); });
                }
            } catch (e) {
                this.enterPseudoFullscreen();
            }
        } else {
            this.enterPseudoFullscreen();
        }
    };

    QalamVideoPlayer.prototype.enterPseudoFullscreen = function () {
        this.pseudoFullscreen = true;
        this.root.classList.add('qalam-video-pseudo-fullscreen', 'qalam-video-is-fullscreen');
        document.documentElement.classList.add('qalam-video-lock-scroll');
        document.body.classList.add('qalam-video-lock-scroll');
        this.showControls(false);
    };

    QalamVideoPlayer.prototype.exitPseudoFullscreen = function () {
        this.pseudoFullscreen = false;
        this.root.classList.remove('qalam-video-pseudo-fullscreen', 'qalam-video-is-fullscreen');
        document.documentElement.classList.remove('qalam-video-lock-scroll');
        document.body.classList.remove('qalam-video-lock-scroll');
    };

    QalamVideoPlayer.prototype.syncFullscreenState = function () {
        var fullscreenEl = document.fullscreenElement || document.webkitFullscreenElement;
        this.root.classList.toggle('qalam-video-is-fullscreen', fullscreenEl === this.root || this.pseudoFullscreen);
    };

    QalamVideoPlayer.prototype.startTicker = function () {
        var self = this;
        window.clearInterval(this.tickTimer);
        this.tickTimer = window.setInterval(function () { self.updateTimeline(false); }, 250);
    };

    QalamVideoPlayer.prototype.updateTimeline = function (force) {
        if (!this.ready || (this.dragging && !force)) { return; }
        var duration = Number(this.safeCall('getDuration', 0)) || 0;
        var current = Number(this.safeCall('getCurrentTime', 0)) || 0;
        var ratio = duration > 0 ? Math.max(0, Math.min(1, current / duration)) : 0;
        if (this.root.classList.contains('qalam-video-is-playing')) {
            this.maxWatchTime = Math.max(this.maxWatchTime, current);
            this.enableCompleteButtonIfEligible(current, duration);
        }
        if (this.progress && !this.dragging) {
            this.progress.value = String(Math.round(ratio * 1000));
            this.paintRange(this.progress, ratio * 100, '--qalam-progress');
        }
        if (this.currentTime) { this.currentTime.textContent = formatTime(current); }
        if (this.duration) { this.duration.textContent = formatTime(duration); }
        this.updateCaption(current);
        this.prepareAutoAdCues(duration);
        this.checkAdCues(current);
    };

    QalamVideoPlayer.prototype.paintRange = function (element, percent, variable) {
        if (element) { element.style.setProperty(variable, Math.max(0, Math.min(100, percent)) + '%'); }
    };

    QalamVideoPlayer.prototype.showControls = function (schedule) {
        this.root.classList.remove('qalam-video-controls-hidden');
        window.clearTimeout(this.hideTimer);
        if (schedule && this.root.classList.contains('qalam-video-is-playing') && !this.root.classList.contains('qalam-video-menu-open')) {
            this.scheduleHide();
        }
    };

    QalamVideoPlayer.prototype.scheduleHide = function () {
        var self = this;
        window.clearTimeout(this.hideTimer);
        if (!this.root.classList.contains('qalam-video-is-playing') || this.root.classList.contains('qalam-video-menu-open')) { return; }
        this.hideTimer = window.setTimeout(function () {
            self.root.classList.add('qalam-video-controls-hidden');
        }, 2600);
    };

    QalamVideoPlayer.prototype.toastMessage = function (message) {
        var self = this;
        if (!this.toast) { return; }
        this.toast.textContent = message;
        this.toast.classList.add('is-visible');
        window.clearTimeout(this.toastTimer);
        this.toastTimer = window.setTimeout(function () { self.toast.classList.remove('is-visible'); }, 800);
    };

    QalamVideoPlayer.prototype.safeCall = function (method, fallback, args) {
        if (!this.player || typeof this.player[method] !== 'function') { return fallback; }
        try {
            var result = this.player[method].apply(this.player, args || []);
            return typeof result === 'undefined' ? fallback : result;
        } catch (e) {
            return fallback;
        }
    };

    function initRoot(root) {
        if (!root || playerRegistry.has(root) || root.getAttribute('data-qalam-video-initialized') === '1') { return; }
        root.setAttribute('data-qalam-video-initialized', '1');
        var instance = new QalamVideoPlayer(root);
        playerRegistry.set(root, instance);
    }

    function scan(scope) {
        var base = scope || document;
        if (base.matches && base.matches('[data-qalam-video-player]')) { initRoot(base); }
        if (base.querySelectorAll) {
            qa(base, '[data-qalam-video-player]').forEach(initRoot);
        }
    }

    function start() {
        scan(document);
        if (window.MutationObserver) {
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    Array.prototype.slice.call(mutation.addedNodes || []).forEach(function (node) {
                        if (node && node.nodeType === 1) { scan(node); }
                    });
                });
            });
            observer.observe(document.documentElement, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
