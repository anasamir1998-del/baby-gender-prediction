// ============ CONFIG ============
let MAIN_BABY_NAME = "";
let SUB_BABY_NAME = "";

// ============ API CONFIG ============
const API_BASE = 'api';

// ============ EVENT DYNAMICS ============
const urlParams = new URLSearchParams(window.location.search);
const eventSlug = urlParams.get('event');
let eventData = null;

if (!eventSlug) {
    window.location.href = 'landing.html';
}

// ============ LIVE PRESENCE (VIEWERS COUNT) ============
const liveViewersBadge = document.getElementById('liveViewersBadge');
const liveViewersCount = document.getElementById('liveViewersCount');

let mySessionId = localStorage.getItem('presenceSessionId');
if (!mySessionId) {
    mySessionId = 'sess_' + Math.random().toString(36).substr(2, 12);
    localStorage.setItem('presenceSessionId', mySessionId);
}

function startPresenceTracking() {
    if (!liveViewersBadge || !liveViewersCount) return;

    // Send heartbeat to register presence
    async function sendHeartbeat() {
        try {
            await fetch(`${API_BASE}/presence.php?slug=${eventSlug}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sessionId: mySessionId })
            });
        } catch (e) {
            console.log('Presence heartbeat error:', e);
        }
    }

    // Poll viewer count
    async function fetchViewerCount() {
        try {
            const res = await fetch(`${API_BASE}/presence.php?slug=${eventSlug}`);
            const data = await res.json();
            const count = data.count || 0;
            liveViewersCount.textContent = String(count);
            liveViewersBadge.style.display = 'inline-flex';
        } catch (e) {
            console.log('Presence count error:', e);
        }
    }

    // Initial heartbeat and count
    sendHeartbeat();
    fetchViewerCount();

    // Regular heartbeat every 15 seconds
    setInterval(sendHeartbeat, 15000);
    // Poll count every 10 seconds
    setInterval(fetchViewerCount, 10000);

    // Cleanup on page leave
    window.addEventListener('pagehide', () => {
        navigator.sendBeacon(`${API_BASE}/presence.php?slug=${eventSlug}&session=${mySessionId}`, '');
    });
    window.addEventListener('beforeunload', () => {
        navigator.sendBeacon(`${API_BASE}/presence.php?slug=${eventSlug}&session=${mySessionId}`, '');
    });
}

// Suspense messages - dramatic & teasing
const SUSPENSE_MESSAGES = [
    { text: "⏳ خلص الوقت!", delay: 3500 },
    { text: "اللحظة اللي ننتظرها من زمان... <span class='emoji-fix'>🔥</span>", delay: 3500 },
    { text: "يا ترى ولد ولا بنت؟ <span class='emoji-fix'>🤔</span>", delay: 3500 },
    { text: "نبضات القلب تزيد... <span class='emoji-fix'>💓</span>", delay: 3500 },
    { text: "تتوقعون إحساسكم بمحله؟ <span class='emoji-fix'>✨</span>", delay: 3500 },
    { text: "ترقبوا الخبر الزين... <span class='emoji-fix'>🚀</span>", delay: 3500 },
    { text: "جاهزيييين؟؟ <span class='emoji-fix'>😍</span>", delay: 3500 },

    { text: "<span class='huge-countdown'>10</span>", delay: 1000 },
    { text: "<span class='huge-countdown'>9</span>", delay: 1000 },
    { text: "<span class='huge-countdown'>8</span>", delay: 1000 },
    { text: "<span class='huge-countdown'>7</span>", delay: 1000 },
    { text: "<span class='huge-countdown'>6</span>", delay: 1000 },
    { text: "<span class='huge-countdown'>5</span>", delay: 1000 },
    { text: "<span class='huge-countdown'>4</span>", delay: 1000 },
    { text: "<span class='huge-countdown'>3</span>", delay: 1000 },
    { text: "<span class='huge-countdown'>2</span>", delay: 1000 },
    { text: "<span class='huge-countdown'>1</span>", delay: 1000 }
];

// ============ DOM ============
const welcomePage = document.getElementById('welcomePage');
const countdownPage = document.getElementById('countdownPage');
const suspensePage = document.getElementById('suspensePage');
const revealPage = document.getElementById('revealPage');

const boyBtn = document.getElementById('boyBtn');
const girlBtn = document.getElementById('girlBtn');
const nameWrap = document.getElementById('nameWrap');
const visitorName = document.getElementById('visitorName');
const enterBtn = document.getElementById('enterBtn');

const cdDays = document.getElementById('cdDays');
const cdHours = document.getElementById('cdHours');
const cdMinutes = document.getElementById('cdMinutes');
const cdSeconds = document.getElementById('cdSeconds');
const yourPredLabel = document.getElementById('yourPredLabel');
const suspenseText = document.getElementById('suspenseText');

const revealName = document.getElementById('revealName');

const closeAdminBtn = document.getElementById('closeAdminBtn');

const heartbeatSnd = document.getElementById('heartbeatSnd');
const popSnd = document.getElementById('popSnd');
const cheerSnd = document.getElementById('cheerSnd');
const suspenseMusic = document.getElementById('suspenseMusic');
const countdownSnd = document.getElementById('countdownSnd');


const boyPercent = document.getElementById('boyPercent');
const boyFill = document.getElementById('boyFill');
const girlPercent = document.getElementById('girlPercent');
const girlFill = document.getElementById('girlFill');

const balloonContainer = document.getElementById('balloonContainer');
const balloon = document.getElementById('balloon');
const revealContent = document.getElementById('revealContent');

// ============ LIVE CHAT DOM ============
const liveChatWrapper = document.getElementById('liveChatWrapper');
const liveChatMessages = document.getElementById('liveChatMessages');
const liveChatInput = document.getElementById('liveChatInput');
const liveChatSendBtn = document.getElementById('liveChatSendBtn');
const chatToggleBtn = document.getElementById('chatToggleBtn');

// ============ AUDIO ENGINE ============
let isAudioUnlocked = false;
const audioOverlay = document.getElementById('audioOverlay');
const activateAudioBtn = document.getElementById('activateAudioBtn');

async function unlockAudio() {
    if (isAudioUnlocked) return;
    
    // 1. Prepare other sounds silently
    const otherSounds = [heartbeatSnd, cheerSnd, popSnd, countdownSnd];
    for (const s of otherSounds) {
        if (s) {
            try {
                s.muted = false;
                await s.play();
                s.pause();
                s.currentTime = 0;
            } catch (e) {
                console.log("Silent unlock failed for:", s.id, e);
            }
        }
    }
    
    // 2. Mark as unlocked
    isAudioUnlocked = true;
    audioOverlay.style.display = 'none';
    
    // 3. Show sound toggle
    const soundToggle = document.getElementById('soundToggle');
    if (soundToggle) soundToggle.style.display = 'flex';
    
    // 4. Finally, start the suspense music separately
    if (suspenseMusic) {
        try {
            suspenseMusic.muted = false;
            suspenseMusic.currentTime = 0;
            await suspenseMusic.play();
        } catch (e) {
            console.log("Music play error:", e);
        }
    }
}



if (activateAudioBtn) {
    activateAudioBtn.onclick = () => unlockAudio();
}


// ============ FULLSCREEN & SOUND TOGGLE ============
const fullscreenBtn = document.getElementById('fullscreenBtn');
const soundToggle = document.getElementById('soundToggle');

if (fullscreenBtn) {
    fullscreenBtn.onclick = () => {
        const docEl = document.documentElement;
        const requestFs = docEl.requestFullscreen || docEl.webkitRequestFullscreen || docEl.mozRequestFullScreen || docEl.msRequestFullscreen;
        const exitFs = document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || document.msExitFullscreen;

        if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.mozFullScreenElement && !document.msFullscreenElement) {
            if (requestFs) {
                requestFs.call(docEl).catch(err => {
                    console.log(`Error: ${err.message}`);
                });
                fullscreenBtn.querySelector('.icon').textContent = '❐';
            } else {
                alert("عذراً، خاصية ملء الشاشة غير مدعومة في متصفح الآيفون. يرجى استخدام خاصية 'إضافة إلى الشاشة الرئيسية' (Add to Home Screen) من قائمة المشاركة للحصول على تجربة كاملة.");
            }
        } else {
            if (exitFs) {
                exitFs.call(document);
                fullscreenBtn.querySelector('.icon').textContent = '⛶';
            }
        }
    };
}


if (soundToggle) {
    soundToggle.onclick = () => {
        const isMuted = suspenseMusic.muted;
        [heartbeatSnd, cheerSnd, suspenseMusic, popSnd, countdownSnd].forEach(s => {
            if (s) s.muted = !isMuted;
        });
        soundToggle.classList.toggle('active', isMuted);
        soundToggle.querySelector('.icon').textContent = isMuted ? '🔊' : '🔇';
    };
}


// Crowd Voice Effect (Simulated)
function speakNumber(n) {
    if (!window.speechSynthesis) return;
    window.speechSynthesis.cancel();
    
    // Main Voice
    const u1 = new SpeechSynthesisUtterance(n.toString());
    u1.lang = 'ar-SA'; u1.rate = 1.7; u1.pitch = 1.0;
    window.speechSynthesis.speak(u1);
    
    // Crowd voices (delay & pitch variants)
    setTimeout(() => {
        const u2 = new SpeechSynthesisUtterance(n.toString());
        u2.lang = 'ar-SA'; u2.rate = 1.5; u2.pitch = 0.8; u2.volume = 0.6;
        window.speechSynthesis.speak(u2);
    }, 50);
}



let selectedGender = null;
let countdownInterval = null;
let isSuspenseStarted = false; // Prevent double execution
let globalTargetDate = new Date(Date.now() + 3600000); // Default fallback

// Fetch target date from API and poll for updates
async function fetchTargetDate() {
    try {
        const res = await fetch(`${API_BASE}/events.php?slug=${eventSlug}`);
        const data = await res.json();
        if (data.success && data.target_date) {
            globalTargetDate = new Date(data.target_date);
            // Restart countdown with new date if it's already running
            if (countdownPage.classList.contains('active')) {
                clearInterval(countdownInterval);
                startCountdown();
            }
        }
    } catch (e) {
        console.log('Failed to fetch target date:', e);
    }
}

// ============ PARTICLE BACKGROUND ============
const canvas = document.getElementById('particleCanvas');
const ctx = canvas.getContext('2d');
let particles = [];

function resizeCanvas() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

function createParticles() {
    particles = [];
    const count = Math.min(60, Math.floor(window.innerWidth / 20));
    for (let i = 0; i < count; i++) {
        particles.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            size: Math.random() * 3 + 1,
            speedX: (Math.random() - 0.5) * 0.5,
            speedY: (Math.random() - 0.5) * 0.5,
            opacity: Math.random() * 0.4 + 0.1,
            hue: Math.random() * 60 + 280 // purple-pink range
        });
    }
}
createParticles();

function drawParticles() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach(p => {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        ctx.fillStyle = `hsla(${p.hue}, 80%, 70%, ${p.opacity})`;
        ctx.fill();
        p.x += p.speedX;
        p.y += p.speedY;
        if (p.x < 0) p.x = canvas.width;
        if (p.x > canvas.width) p.x = 0;
        if (p.y < 0) p.y = canvas.height;
        if (p.y > canvas.height) p.y = 0;
    });
    // Draw connections
    for (let i = 0; i < particles.length; i++) {
        for (let j = i + 1; j < particles.length; j++) {
            const dx = particles[i].x - particles[j].x;
            const dy = particles[i].y - particles[j].y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < 120) {
                ctx.beginPath();
                ctx.strokeStyle = `rgba(168, 85, 247, ${0.06 * (1 - dist / 120)})`;
                ctx.lineWidth = 0.5;
                ctx.moveTo(particles[i].x, particles[i].y);
                ctx.lineTo(particles[j].x, particles[j].y);
                ctx.stroke();
            }
        }
    }
    requestAnimationFrame(drawParticles);
}
drawParticles();

// ============ GENDER SELECTION ============
boyBtn.addEventListener('click', () => selectGender('boy'));
girlBtn.addEventListener('click', () => selectGender('girl'));

function selectGender(gender) {
    selectedGender = gender;
    boyBtn.classList.toggle('selected', gender === 'boy');
    girlBtn.classList.toggle('selected', gender === 'girl');
    nameWrap.style.display = 'block';
    enterBtn.style.display = 'block';
    visitorName.focus();
}

// ============ ENTER CELEBRATION ============
enterBtn.addEventListener('click', () => {
    if (!selectedGender) return;
    const name = visitorName.value.trim() || 'زائر';

    // Save prediction locally
    localStorage.setItem('celebPrediction', selectedGender);
    localStorage.setItem('celebVisitor', name);

    // Save prediction to API
    fetch(`${API_BASE}/predictions.php?slug=${eventSlug}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            name: name,
            gender: selectedGender,
            date: new Date().toLocaleString('ar-EG')
        })
    }).catch(e => console.log('Save prediction error:', e));

    // Show prediction label
    yourPredLabel.textContent = selectedGender === 'boy' ? '👦 ولد' : '👧 بنت';
    yourPredLabel.className = 'pred-badge ' + selectedGender;

    // Navigate to countdown
    goToPage('countdown');
    startCountdown();

    // Start background tasks
    updateLiveStats();
    listenForReactions();

    // Unlock audio (browsers need interaction)
    suspenseMusic.volume = 0.2;
    heartbeatSnd.volume = 0.4;
});

// ============ IDEA 1: LIVE STATS ============
function updateLiveStats() {
    // Avoid registering multiple polling if user comes back using localStorage
    if (updateLiveStats._listening) return;
    updateLiveStats._listening = true;

    async function fetchStats() {
        try {
            const res = await fetch(`${API_BASE}/predictions.php?slug=${eventSlug}&count=1`);
            const data = await res.json();
            const total = parseInt(data.total) || 0;
            const boys = parseInt(data.boys) || 0;

            if (total === 0) {
                boyPercent.textContent = '0%';
                boyFill.style.width = '0%';
                girlPercent.textContent = '0%';
                girlFill.style.width = '0%';
                return;
            }

            const boyP = Math.round((boys / total) * 100);
            const girlP = 100 - boyP;

            boyPercent.textContent = `${boyP}%`;
            boyFill.style.width = `${boyP}%`;
            girlPercent.textContent = `${girlP}%`;
            girlFill.style.width = `${girlP}%`;
        } catch (e) {
            console.log('Stats poll error:', e);
        }
    }
    fetchStats();
    setInterval(fetchStats, 5000);
}


// ============ IDEA 3: LIVE REACTIONS ============
function sendReaction(emoji) {
    fetch(`${API_BASE}/reactions.php?slug=${eventSlug}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ emoji: emoji })
    }).catch(e => console.log('Reaction error:', e));
}

let lastReactionId = 0;
function listenForReactions() {
    async function pollReactions() {
        try {
            const res = await fetch(`${API_BASE}/reactions.php?slug=${eventSlug}&after=${lastReactionId}`);
            const reactions = await res.json();
            if (Array.isArray(reactions)) {
                reactions.forEach(r => {
                    spawnFloatingEmoji(r.emoji);
                    if (r.id > lastReactionId) lastReactionId = r.id;
                });
            }
        } catch (e) {
            console.log('Reactions poll error:', e);
        }
    }
    pollReactions();
    setInterval(pollReactions, 3000);
}

function spawnFloatingEmoji(emoji) {
    const el = document.createElement('div');
    el.className = 'floating-reaction';
    el.textContent = emoji;
    el.style.left = Math.random() * 80 + 10 + '%';
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 4000);
}




// ============ PAGE NAVIGATION ============
function goToPage(name) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    const page = {
        welcome: welcomePage,
        countdown: countdownPage,
        suspense: suspensePage,
        reveal: revealPage
    }[name];
    if (page) page.classList.add('active');

    // Show live chat on countdown and reveal pages
    if(liveChatWrapper) {
        if(name === 'countdown' || name === 'reveal') {
            liveChatWrapper.classList.add('visible');
        } else {
            liveChatWrapper.classList.remove('visible');
        }
    }
}

// ============ LIVE CHAT LOGIC ============
if (chatToggleBtn && liveChatWrapper) {
    chatToggleBtn.addEventListener('click', () => {
        liveChatWrapper.classList.toggle('collapsed');
    });
}

let mySenderId = localStorage.getItem('chatSenderId');
if (!mySenderId) {
    mySenderId = 'user_' + Math.random().toString(36).substr(2, 9);
    localStorage.setItem('chatSenderId', mySenderId);
}

function initLiveChat() {
    if (!liveChatMessages) return;

    let lastChatId = 0;
    let isInitialLoad = true;

    function escapeHTML(str) {
        return String(str).replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag] || tag)
        );
    }

    function addMessageToUI(msg) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'chat-message';
        
        const isMe = msg.sender_id === mySenderId;
        if (isMe) msgDiv.classList.add('self');
        
        msgDiv.innerHTML = `
            <span class="msg-sender">${escapeHTML(msg.name)}</span>
            <span class="msg-text">${escapeHTML(msg.text)}</span>
        `;
        
        liveChatMessages.appendChild(msgDiv);
        liveChatMessages.scrollTop = liveChatMessages.scrollHeight;
    }

    // Initial load of last 50 messages
    async function loadMessages() {
        try {
            const res = await fetch(`${API_BASE}/chat.php?slug=${eventSlug}`);
            const messages = await res.json();
            if (Array.isArray(messages)) {
                messages.forEach(msg => {
                    addMessageToUI(msg);
                    if (parseInt(msg.id) > lastChatId) lastChatId = parseInt(msg.id);
                });
            }
            isInitialLoad = false;
        } catch (e) {
            console.log('Chat load error:', e);
        }
    }

    // Poll for new messages
    async function pollNewMessages() {
        if (isInitialLoad) return;
        try {
            const res = await fetch(`${API_BASE}/chat.php?slug=${eventSlug}&after=${lastChatId}`);
            const messages = await res.json();
            if (Array.isArray(messages)) {
                messages.forEach(msg => {
                    addMessageToUI(msg);
                    if (parseInt(msg.id) > lastChatId) lastChatId = parseInt(msg.id);
                });
            }
        } catch (e) {
            console.log('Chat poll error:', e);
        }
    }

    loadMessages();
    setInterval(pollNewMessages, 3000);
    
    function sendChatMessage() {
        const text = liveChatInput.value.trim();
        if (!text) return;
        
        const myName = localStorage.getItem('celebVisitor') || 'زائر';
        
        fetch(`${API_BASE}/chat.php?slug=${eventSlug}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: myName,
                text: text,
                senderId: mySenderId
            })
        }).catch(e => console.log('Chat send error:', e));
        
        liveChatInput.value = '';
    }
    
    if(liveChatSendBtn && liveChatInput) {
        liveChatSendBtn.addEventListener('click', sendChatMessage);
        liveChatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendChatMessage();
        });
    }
}

// Call initLiveChat once
initLiveChat();

// ============ COUNTDOWN ============
function startCountdown() {
    const target = globalTargetDate;
    const totalDuration = 1000 * 60 * 60 * 24; // Use 24h as a base for the excitement bar max width

    function update() {
        const now = Date.now();
        const diff = target.getTime() - now;

        if (diff <= 0) {
            clearInterval(countdownInterval);
            cdDays.textContent = '00';
            cdHours.textContent = '00';
            cdMinutes.textContent = '00';
            cdSeconds.textContent = '00';


            // Go to suspense only once
            if (!isSuspenseStarted) {
                isSuspenseStarted = true;
                setTimeout(() => startSuspense(), 1000);
            }
            return;
        }

        const days = Math.floor(diff / 86400000);
        const hours = Math.floor((diff % 86400000) / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);

        cdDays.textContent = String(days).padStart(2, '0');
        cdHours.textContent = String(hours).padStart(2, '0');
        cdMinutes.textContent = String(minutes).padStart(2, '0');
        cdSeconds.textContent = String(seconds).padStart(2, '0');

        // Countdown update finished
    }


    update();
    countdownInterval = setInterval(update, 1000);
}

// ============ SUSPENSE ============
async function startSuspense() {
    // Force user to activate sound before seeing the suspense
    if (!isAudioUnlocked) {
        audioOverlay.style.display = 'flex';
        // Wait for user to click activateAudioBtn
        await new Promise(resolve => {
            const originalUnlock = unlockAudio;
            unlockAudio = function() {
                originalUnlock();
                resolve();
            };
        });
    }

    goToPage('suspense');
    // music already started in unlockAudio


    // Play suspense heartbeat animation
    document.body.style.animation = 'none';

    for (let i = 0; i < SUSPENSE_MESSAGES.length; i++) {
        let msg = SUSPENSE_MESSAGES[i];
        suspenseText.innerHTML = msg.text;
        if (heartbeatSnd) { heartbeatSnd.currentTime = 0; heartbeatSnd.play().catch(() => {}); }


        // Alternate color mode (once pink, once blue)
        if (i % 2 === 0) {
            suspenseText.classList.remove('blue-mode');
        } else {
            suspenseText.classList.add('blue-mode');
        }

        // Add smooth cinematic animation
        suspenseText.style.animation = 'none';
        void suspenseText.offsetWidth; // trigger reflow

        // Shake effect for countdown numbers
        if (msg.text.includes('huge-countdown')) {
            const n = msg.text.replace(/[^0-9]/g, '');
            if (n === "10") {
                countdownSnd.play().catch(() => {});
            }
            suspenseText.style.animation = 'popTextNumber 1s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards';
            document.body.style.animation = 'shake 0.3s ease';
            // speakNumber(n); // Disabled to avoid double voice
            setTimeout(() => { document.body.style.animation = ''; }, 300);

        } else {


            // For 5-second sentences
            suspenseText.style.animation = 'popTextSentence 5s ease-in-out forwards';
            suspenseText.style.animation = 'popTextSentence 5s ease-in-out forwards';

        }

        await new Promise(r => setTimeout(r, msg.delay));
    }

    suspenseMusic.pause();
    heartbeatSnd.pause();
    startReveal();
}


// Add shake keyframe dynamically
const shakeStyle = document.createElement('style');
shakeStyle.textContent = `
    @keyframes shake {
        0%,100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
`;
document.head.appendChild(shakeStyle);

// ============ GRAND REVEAL ============
function startReveal() {
    goToPage('reveal');
    
    // Hide balloon (Idea 6) and show reveal content immediately
    balloonContainer.style.display = 'none';
    revealContent.style.display = 'block';

    // Play reveal sounds automatically
    popSnd.play().catch(() => {});
    cheerSnd.play().catch(() => {});
    
    // Animate the whole name
    revealName.innerHTML = `
        <div class="main-name-reveal">${MAIN_BABY_NAME}</div>
        <div class="sub-name-reveal">${SUB_BABY_NAME}</div>
    `;
    revealName.style.opacity = '1';

    // Start confetti
    launchConfetti();

    // Load and show visitor predictions
    showVisitorPredictions();
}


// ============ IDEA 5: SHARE CARD ============
function downloadCard() {
    const area = document.getElementById('souvenirCard');
    const btn = document.querySelector('.share-btn');
    
    // Preparation for clean capture
    btn.style.display = 'none';
    
    // Set date
    const d = new Date();
    document.getElementById('cardDate').textContent = `${d.getFullYear()}/${d.getMonth()+1}/${d.getDate()}`;

    // Capture using better settings for RTL and Arabic
    html2canvas(area, {
        backgroundColor: null,
        scale: 3, // High quality
        useCORS: true,
        allowTaint: true,
        letterRendering: true, // Helps with some Arabic issues
    }).then(canvas => {
        const link = document.createElement('a');
        link.download = `baby-reveal-${MAIN_BABY_NAME}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        btn.style.display = 'block';
    }).catch(err => {
        console.error("Capture Error:", err);
        btn.style.display = 'block';
    });
}



// ============ VISITOR PREDICTIONS ============
async function showVisitorPredictions() {
    const container = document.createElement('div');
    container.className = 'predictions-wall';
    revealPage.appendChild(container);

    try {
        const res = await fetch(`${API_BASE}/predictions.php?slug=${eventSlug}`);
        const entries = await res.json();
        if (!Array.isArray(entries) || entries.length === 0) return;

        // Sort by timestamp (newest first)
        entries.sort((a, b) => (parseInt(b.timestamp) || 0) - (parseInt(a.timestamp) || 0));
        
        entries.forEach((pred, index) => {
            setTimeout(() => {
                const bubble = document.createElement('div');
                const isBoy = pred.gender === 'boy';
                bubble.className = `pred-bubble ${pred.gender}`;
                
                // Random scale for variety
                const scale = Math.random() * 0.3 + 0.85; // 0.85 to 1.15
                bubble.style.transform = `scale(${scale})`;
                
                bubble.innerHTML = `
                    <div class="bubble-icon">${isBoy ? '👦' : '👧'}</div>
                    <div class="bubble-content">
                        <span class="bubble-name">${pred.name}</span>
                        <span class="bubble-text">توقع ${isBoy ? 'ولد' : 'بنت'}</span>
                    </div>
                `;
                
                // Random position and timing
                const x = Math.random() * 85 + 5; 
                bubble.style.left = `${x}%`;
                bubble.style.animationDuration = `${Math.random() * 5 + 12}s`;
                bubble.style.animationDelay = `${Math.random() * 2}s`;
                
                container.appendChild(bubble);
            }, index * 400); 
        });
    } catch (e) {
        console.log('Failed to load predictions:', e);
    }
}

// ============ CONFETTI ============
function launchConfetti() {
    const confettiCanvas = document.getElementById('confettiCanvas');
    const cCtx = confettiCanvas.getContext('2d');
    confettiCanvas.width = window.innerWidth;
    confettiCanvas.height = window.innerHeight;

    const pieces = [];
    const colors = ['#ff6b9d', '#ffa2c4', '#f9a8d4', '#c4b5fd', '#a855f7', '#f6d365', '#ff85a1', '#fbc2eb', '#fff'];

    function addBurst() {
        for (let i = 0; i < 80; i++) {
            pieces.push({
                x: Math.random() * confettiCanvas.width,
                y: -20,
                w: Math.random() * 10 + 5,
                h: Math.random() * 6 + 3,
                color: colors[Math.floor(Math.random() * colors.length)],
                speedX: (Math.random() - 0.5) * 6,
                speedY: Math.random() * 4 + 2,
                rotation: Math.random() * 360,
                rotSpeed: (Math.random() - 0.5) * 10,
                opacity: 1
            });
        }
    }

    addBurst();
    setTimeout(addBurst, 1000);
    setTimeout(addBurst, 2500);
    setTimeout(addBurst, 4000);

    function animateConfetti() {
        cCtx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);

        for (let i = pieces.length - 1; i >= 0; i--) {
            const p = pieces[i];
            cCtx.save();
            cCtx.translate(p.x, p.y);
            cCtx.rotate((p.rotation * Math.PI) / 180);
            cCtx.globalAlpha = p.opacity;
            cCtx.fillStyle = p.color;
            cCtx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            cCtx.restore();

            p.x += p.speedX;
            p.y += p.speedY;
            p.speedY += 0.05; // gravity
            p.rotation += p.rotSpeed;
            p.opacity -= 0.003;

            if (p.y > confettiCanvas.height + 20 || p.opacity <= 0) {
                pieces.splice(i, 1);
            }
        }

        if (pieces.length > 0) {
            requestAnimationFrame(animateConfetti);
        }
    }

    animateConfetti();

    // Keep adding confetti bursts
    let burstCount = 0;
    const burstInterval = setInterval(() => {
        addBurst();
        burstCount++;
        if (burstCount > 10) clearInterval(burstInterval);
    }, 3000);
}

// ============ ADMIN PANEL ============
// Ctrl+Shift+A or triple-tap to open admin
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey && e.shiftKey && e.key === 'A') {
        e.preventDefault();
        openAdmin();
    }
});

let tapCount = 0;
let tapTimer = null;
document.addEventListener('click', (e) => {
    // Triple tap on top-left corner
    if (e.clientX < 60 && e.clientY < 60) {
        tapCount++;
        clearTimeout(tapTimer);
        tapTimer = setTimeout(() => { tapCount = 0; }, 800);
        if (tapCount >= 3) {
            tapCount = 0;
            openAdmin();
        }
    }
});

async function openAdmin() {
    adminPanel.style.display = 'flex';
    try {
        const res = await fetch(`${API_BASE}/settings.php?key=targetDate`);
        const data = await res.json();
        if (data.value) {
            const d = new Date(data.value);
            const offset = d.getTimezoneOffset();
            const local = new Date(d.getTime() - offset * 60000);
            targetDateInput.value = local.toISOString().slice(0, 16);
        }
    } catch (e) {
        console.log('Failed to load admin settings:', e);
    }
}

saveTargetBtn.addEventListener('click', async () => {
    const val = targetDateInput.value;
    if (val) {
        try {
            const dateISO = new Date(val).toISOString();
            const res = await fetch(`${API_BASE}/settings.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ key: 'targetDate', value: dateISO })
            });
            const result = await res.json();
            if (result.success) {
                adminPanel.style.display = 'none';
                alert('✅ تم حفظ الموعد ونشره لجميع الزوار بنجاح!');
            } else {
                alert('حدث خطأ أثناء الحفظ');
            }
        } catch (err) {
            alert('حدث خطأ أثناء الحفظ');
            console.error(err);
        }
    }
});

closeAdminBtn.addEventListener('click', () => {
    adminPanel.style.display = 'none';
});

// ============ INITIALIZE ============
document.addEventListener('DOMContentLoaded', () => {
    loadEvent();
});

async function loadEvent() {
    try {
        const res = await fetch(`${API_BASE}/events.php?slug=${eventSlug}`);
        const data = await res.json();
        
        if (!data.success) {
            if (data.status === 'expired') {
                showExpiredOverlay();
            } else {
                alert('الاحتفالية غير موجودة!');
                window.location.href = 'landing.html';
            }
            return;
        }
        
        eventData = data;
        MAIN_BABY_NAME = eventData.baby_name;
        SUB_BABY_NAME = eventData.sub_baby_name;
        globalTargetDate = new Date(eventData.target_date);
        
        // Dynamic Title
        document.title = `🎀 احتفالية الكشف | ${MAIN_BABY_NAME}`;

        // Apply custom gender styles
        applyGenderStyles();

        // Start presence
        startPresenceTracking();

        const existingPrediction = localStorage.getItem('celebPrediction');
        if (existingPrediction) {
            selectedGender = existingPrediction;
            yourPredLabel.textContent = selectedGender === 'boy' ? '👦 ولد' : '👧 بنت';
            yourPredLabel.className = 'pred-badge ' + selectedGender;
            goToPage('countdown');
            startCountdown();
            updateLiveStats();
            listenForReactions();
        } else {
            goToPage('welcome');
        }
        
        // Poll for target date changes
        setInterval(fetchTargetDate, 10000);
    } catch (e) {
        console.error(e);
        alert('فشل تحميل بيانات الاحتفالية من الخادم');
    }
}

function showExpiredOverlay() {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.96);backdrop-filter:blur(10px);z-index:99999;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px;text-align:center;font-family:"Tajawal",sans-serif;direction:rtl;';
    overlay.innerHTML = `
        <div style="font-size:5rem;margin-bottom:20px;animation:bounce 2s infinite;">⏳</div>
        <h1 style="color:#7c3aed;font-size:2rem;font-weight:900;margin-bottom:12px;">انتهت الفترة التجريبية للاحتفالية</h1>
        <p style="color:#6b7280;font-size:1.1rem;max-width:500px;margin-bottom:30px;line-height:1.6;">عذراً، انتهت الفترة التجريبية الخاصة بهذه الصفحة. يرجى من منظم الاحتفالية الدخول للوحة التحكم وتفعيل الاشتراك لمدة 5 أيام لإتاحتها للزوار.</p>
        <a href="landing.html" style="background:linear-gradient(135deg,#7c3aed,#a855f7);color:white;text-decoration:none;padding:14px 35px;border-radius:50px;font-weight:800;font-size:1.1rem;box-shadow:0 8px 24px rgba(124,58,237,0.3);">الذهاب للموقع الرئيسي</a>
    `;
    document.body.appendChild(overlay);
}

function applyGenderStyles() {
    const revealIcon = document.querySelector('.reveal-icon');
    const revealTitle = document.querySelector('.reveal-title');
    const cardGenderTag = document.querySelector('.card-gender-tag');
    
    // Update souvenir card text
    const cardName = document.getElementById('cardName');
    const cardSubText = document.getElementById('cardSubText');
    if (cardName) cardName.textContent = MAIN_BABY_NAME;
    if (cardSubText) cardSubText.textContent = SUB_BABY_NAME;

    if (eventData.revealed_gender === 'boy') {
        if (revealIcon) revealIcon.innerHTML = '👦⭐';
        if (revealTitle) revealTitle.textContent = 'إنه ولد!';
        if (cardGenderTag) {
            cardGenderTag.textContent = 'إنه ولد! 👦';
            cardGenderTag.style.background = '#0284c7';
            cardGenderTag.style.boxShadow = '0 6px 15px rgba(2, 132, 199, 0.3)';
        }
        // Souvenir card customizations for boy
        const souvenirCard = document.getElementById('souvenirCard');
        if (souvenirCard) {
            souvenirCard.style.background = 'linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%)';
            souvenirCard.style.border = '10px solid #bae6fd';
            const inner = souvenirCard.querySelector('.card-inner');
            if (inner) inner.style.borderColor = '#7dd3fc';
            const head = souvenirCard.querySelector('.card-header');
            if (head) {
                head.style.color = '#0284c7';
                head.textContent = '✨ ذكرى الكشف عن المولود ✨';
            }
            const mainName = souvenirCard.querySelector('.card-main-name');
            if (mainName) {
                mainName.style.color = '#0284c7';
                mainName.style.textShadow = '0 4px 10px rgba(2, 132, 199, 0.15)';
            }
            const foot = souvenirCard.querySelector('.card-footer');
            if (foot) {
                foot.style.color = '#0284c7';
                foot.innerHTML = '<p>اللهم أنبته نباتاً حسناً 🤲</p><div id="cardDate" style="margin-top:10px; font-size: 0.8rem; opacity: 0.5;"></div>';
            }
        }
        // Grand reveal background colors can also be boy-themed
        const revealPage = document.getElementById('revealPage');
        if (revealPage) {
            revealPage.style.background = 'linear-gradient(-45deg, #e0f2fe, #bae6fd, #e0c3fc, #d4b8f0)';
        }
    } else {
        if (revealIcon) revealIcon.innerHTML = '👸🎀';
        if (revealTitle) revealTitle.textContent = 'إنها بنت!';
        if (cardGenderTag) {
            cardGenderTag.textContent = 'إنها بنت! 👸';
            cardGenderTag.style.background = '#db2777';
            cardGenderTag.style.boxShadow = '0 6px 15px rgba(219, 39, 119, 0.3)';
        }
    }
}
