// ============ CONFIG ============
const BABY_NAME = "ميلان نايف باوزير";

// ============ FIREBASE CONFIG ============
const firebaseConfig = {
    apiKey: "AIzaSyCcEiiFAMHe_Q-YXlh3OKs1RA2qt57KIEI",
    authDomain: "baby-prediction-cd7f0.firebaseapp.com",
    databaseURL: "https://baby-prediction-cd7f0-default-rtdb.firebaseio.com",
    projectId: "baby-prediction-cd7f0",
    storageBucket: "baby-prediction-cd7f0.firebasestorage.app",
    messagingSenderId: "746478336440",
    appId: "1:746478336440:web:9734916b1e1a0f6338374d"
};
firebase.initializeApp(firebaseConfig);
const db = firebase.database();

// Suspense messages - dramatic & teasing
const SUSPENSE_MESSAGES = [
    { text: "⏳ خلص الوقت!", delay: 5000 },
    { text: "اللحظة اللي ننتظرها من زمان... 🔥", delay: 5000 },
    { text: "يا ترى ولد ولا بنت؟ 🤔", delay: 5000 },
    { text: "نبضات القلب تزيد... 💓", delay: 5000 },
    { text: "تتوقعون إحساسكم بمحله؟ ✨", delay: 5000 },
    { text: "ترقبوا الخبر الزين... 🚀", delay: 5000 },
    { text: "جاهزيييين؟؟ 😍", delay: 5000 },
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
const excitementFill = document.getElementById('excitementFill');
const suspenseText = document.getElementById('suspenseText');
const revealName = document.getElementById('revealName');

const adminPanel = document.getElementById('adminPanel');
const targetDateInput = document.getElementById('targetDateInput');
const saveTargetBtn = document.getElementById('saveTargetBtn');
const closeAdminBtn = document.getElementById('closeAdminBtn');

let selectedGender = null;
let countdownInterval = null;
let isSuspenseStarted = false; // Prevent double execution
let globalTargetDate = new Date(Date.now() + 3600000); // Default fallback

// Listen to Firebase for real-time target date updates
db.ref('settings/targetDate').on('value', (snap) => {
    const val = snap.val();
    if (val) {
        globalTargetDate = new Date(val);
        // Restart countdown with new date if it's already running
        if (countdownPage.classList.contains('active')) {
            clearInterval(countdownInterval);
            startCountdown();
        }
    }
});

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

    // Save prediction
    localStorage.setItem('celebPrediction', selectedGender);
    localStorage.setItem('celebVisitor', name);

    // Show prediction label
    yourPredLabel.textContent = selectedGender === 'boy' ? '👦 ولد' : '👧 بنت';
    yourPredLabel.className = 'pred-badge ' + selectedGender;

    // Navigate to countdown
    goToPage('countdown');
    startCountdown();
});

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
}

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
            excitementFill.style.width = '100%';

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

        // Update excitement bar
        const elapsed = totalDuration > 0 ? (1 - diff / totalDuration) * 100 : 0;
        excitementFill.style.width = Math.min(elapsed, 100) + '%';
    }

    update();
    countdownInterval = setInterval(update, 1000);
}

// ============ SUSPENSE ============
async function startSuspense() {
    goToPage('suspense');

    // Play suspense heartbeat animation
    document.body.style.animation = 'none';

    for (let msg of SUSPENSE_MESSAGES) {
        suspenseText.innerHTML = msg.text;

        // Add smooth cinematic animation
        suspenseText.style.animation = 'none';
        void suspenseText.offsetWidth; // trigger reflow

        // Shake effect for countdown numbers
        if (msg.text.includes('huge-countdown')) {
            suspenseText.style.animation = 'popTextNumber 1s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards';
            document.body.style.animation = 'shake 0.3s ease';
            setTimeout(() => { document.body.style.animation = ''; }, 300);
        } else {
            // For 5-second sentences
            suspenseText.style.animation = 'popTextSentence 5s ease-in-out forwards';
        }

        await new Promise(r => setTimeout(r, msg.delay));
    }

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

    // Animate the whole name
    revealName.textContent = BABY_NAME;
    revealName.style.opacity = '0';
    revealName.style.animation = 'namePopIn 1.5s cubic-bezier(.17,.67,.35,1.2) 0.5s forwards';

    // Add name animation
    const nameStyle = document.createElement('style');
    nameStyle.textContent = `
        @keyframes namePopIn {
            from { opacity:0; transform:translateY(30px) scale(0.8); }
            to { opacity:1; transform:translateY(0) scale(1); }
        }
    `;
    document.head.appendChild(nameStyle);

    // Start confetti
    launchConfetti();
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

function openAdmin() {
    adminPanel.style.display = 'flex';
    db.ref('settings/targetDate').once('value').then((snap) => {
        const stored = snap.val();
        if (stored) {
            // Format for datetime-local input
            const d = new Date(stored);
            const offset = d.getTimezoneOffset();
            const local = new Date(d.getTime() - offset * 60000);
            targetDateInput.value = local.toISOString().slice(0, 16);
        }
    });
}

saveTargetBtn.addEventListener('click', () => {
    const val = targetDateInput.value;
    if (val) {
        const dateISO = new Date(val).toISOString();
        db.ref('settings/targetDate').set(dateISO).then(() => {
            adminPanel.style.display = 'none';
            alert('✅ تم حفظ الموعد ونشره لجميع الزوار بنجاح!');
        }).catch(err => {
            alert('حدث خطأ أثناء الحفظ');
            console.error(err);
        });
    }
});

closeAdminBtn.addEventListener('click', () => {
    adminPanel.style.display = 'none';
});

// ============ INITIALIZE ============
document.addEventListener('DOMContentLoaded', () => {
    const existingPrediction = localStorage.getItem('celebPrediction');
    if (existingPrediction) {
        // Skip welcome and go straight to countdown
        selectedGender = existingPrediction;
        yourPredLabel.textContent = selectedGender === 'boy' ? '👦 ولد' : '👧 بنت';
        yourPredLabel.className = 'pred-badge ' + selectedGender;
        goToPage('countdown');
        startCountdown();
    } else {
        goToPage('welcome');
    }
});
