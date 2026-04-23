<?php
/**
 * SDG 14 — Life Below Water (Enhanced)
 * Pledge data → pledges.json | Newsletter → subscribers.json
 */
 $dDir = __DIR__;
 $pFile = $dDir . '/pledges.json';
 $sFile = $dDir . '/subscribers.json';

 $pledges = []; $subs = [];
if (file_exists($pFile)) { $d = json_decode(file_get_contents($pFile), true); if (is_array($d)) $pledges = $d; }
if (file_exists($sFile)) { $d = json_decode(file_get_contents($sFile), true); if (is_array($d)) $subs = $d; }

 $pMsg = ''; $pOk = false;
 $nMsg = ''; $nOk = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* Pledge */
    if (isset($_POST['pledge_submit'])) {
        $nm = trim($_POST['name'] ?? '');
        $em = trim($_POST['email'] ?? '');
        $pt = trim($_POST['pledge_text'] ?? '');
        $err = [];
        if (empty($nm)) $err[] = 'Name is required.';
        if (empty($em) || !filter_var($em, FILTER_VALIDATE_EMAIL)) $err[] = 'Valid email required.';
        if (empty($pt)) $err[] = 'Please write your pledge.';
        if (strlen($pt) > 500) $err[] = 'Pledge must be under 500 characters.';
        if (empty($err)) {
            $pledges[] = ['name'=>$nm,'email'=>$em,'pledge'=>$pt,'date'=>date('Y-m-d H:i:s')];
            file_put_contents($pFile, json_encode($pledges, JSON_PRETTY_PRINT));
            $pOk = true; $pMsg = 'Thank you, '.htmlspecialchars($nm).'! Your pledge has been recorded.';
        } else { $pMsg = implode(' ', $err); }
    }
    /* Newsletter */
    if (isset($_POST['nl_submit'])) {
        $ne = trim($_POST['nl_email'] ?? '');
        if (empty($ne) || !filter_var($ne, FILTER_VALIDATE_EMAIL)) {
            $nMsg = 'Please enter a valid email address.';
        } elseif (in_array($ne, array_column($subs, 'email'))) {
            $nOk = true; $nMsg = 'You are already subscribed — thank you!';
        } else {
            $subs[] = ['email'=>$ne,'date'=>date('Y-m-d H:i:s')];
            file_put_contents($sFile, json_encode($subs, JSON_PRETTY_PRINT));
            $nOk = true; $nMsg = 'Welcome aboard! You will receive ocean updates.';
        }
    }
}
 $pCount = count($pledges);
 $recentP = array_slice(array_reverse($pledges), 0, 6);
 $sCount = count($subs);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SDG 14 — Life Below Water</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --s:#0a1628;--d:#061220;--ab:#030a14;
  --teal:#0d7377;--cyan:#00e5c7;--coral:#ff6b6b;--gold:#ffd700;--lime:#00ffa3;
  --txt:#d4e5f7;--muted:#5a7d99;
  --cbg:rgba(13,115,119,.06);--cbr:rgba(0,229,199,.12);
  --glass:rgba(10,22,40,.78);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:'Outfit',sans-serif;background:var(--s);color:var(--txt);overflow-x:hidden;transition:background-color .4s}
.fd{font-family:'Playfair Display',serif}

/* Loader */
#loader{position:fixed;inset:0;z-index:9999;background:var(--ab);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;transition:opacity .8s,visibility .8s}
#loader.hidden{opacity:0;visibility:hidden;pointer-events:none}
.ld-ring{width:52px;height:52px;border:3px solid rgba(0,229,199,.15);border-top-color:var(--cyan);border-radius:50%;animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.ld-txt{font-size:12px;letter-spacing:4px;text-transform:uppercase;color:var(--muted)}

/* Canvas */
#oceanCanvas{position:fixed;inset:0;z-index:0;pointer-events:none}

/* Nav */
.nav{position:fixed;top:0;left:0;right:0;z-index:200;padding:14px 0;transform:translateY(-100%);transition:transform .4s,background .4s}
.nav.vis{transform:translateY(0)}
.nav.scrolled{background:var(--glass);backdrop-filter:blur(18px);border-bottom:1px solid var(--cbr)}

/* Hero */
.hero{position:relative;z-index:1;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20px}
.hero-title{font-size:clamp(2.8rem,8vw,7rem);font-weight:900;line-height:1.05;background:linear-gradient(90deg,#d4e5f7 0%,#00e5c7 30%,#d4e5f7 60%,#00e5c7 90%);background-size:200% auto;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:shimmer 5s linear infinite}
@keyframes shimmer{to{background-position:200% center}}
.hero-sub{font-size:clamp(.95rem,2.2vw,1.25rem);font-weight:300;color:var(--muted);max-width:580px;margin-top:18px;line-height:1.75}

/* SDG Badge */
.sdg-badge{display:inline-flex;align-items:center;gap:12px;padding:8px 22px 8px 8px;border:1px solid var(--cbr);border-radius:999px;background:var(--cbg);margin-bottom:24px}
.sdg-icon{width:44px;height:44px;border-radius:50%;background:var(--teal);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;color:#fff}
.sdg-label{font-weight:600;font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:var(--cyan)}

/* Scroll cue */
.scroll-cue{position:absolute;bottom:36px;display:flex;flex-direction:column;align-items:center;gap:8px;color:var(--muted);font-size:11px;letter-spacing:3px;text-transform:uppercase;animation:cBob 2.5s ease-in-out infinite}
.scroll-cue i{font-size:16px;color:var(--cyan)}
@keyframes cBob{0%,100%{transform:translateY(0);opacity:.5}50%{transform:translateY(8px);opacity:1}}

/* Wave separator */
.wave-sep{position:relative;z-index:1;width:100%;line-height:0;margin-top:-2px}
.wave-sep svg{display:block;width:100%;height:auto}
.wave-sep.flip{transform:scaleY(-1)}

/* Section base */
.os{position:relative;z-index:1;padding:100px 20px}
.stag{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:600;letter-spacing:3px;text-transform:uppercase;color:var(--cyan);margin-bottom:12px}
.stit{font-size:clamp(1.8rem,4.5vw,3rem);font-weight:900;line-height:1.15;margin-bottom:18px}
.sdesc{color:var(--muted);font-size:1rem;line-height:1.8;max-width:660px}

/* Cards base */
.zcard{background:var(--cbg);border:1px solid var(--cbr);border-radius:18px;padding:28px 24px;position:relative;overflow:hidden;transition:transform .4s,border-color .4s,box-shadow .4s}
.zcard:hover{transform:translateY(-5px);border-color:rgba(0,229,199,.28);box-shadow:0 20px 56px rgba(0,229,199,.07)}
.zglow{position:absolute;border-radius:50%;filter:blur(55px);opacity:.12;pointer-events:none}

/* Creature cards */
.ccard{background:var(--cbg);border:1px solid var(--cbr);border-radius:16px;overflow:hidden;transition:transform .4s,border-color .4s,box-shadow .4s}
.ccard:hover{transform:translateY(-6px);border-color:rgba(0,229,199,.28);box-shadow:0 20px 56px rgba(0,229,199,.07)}
.cimg{height:190px;position:relative;overflow:hidden}
.cimg img{width:100%;height:100%;object-fit:cover;transition:transform .6s}
.ccard:hover .cimg img{transform:scale(1.07)}
.cimg::after{content:'';position:absolute;inset:0;background:linear-gradient(to top,var(--s) 0%,transparent 55%)}
.cbody{padding:18px 20px 22px}
.cbody h3{font-size:1.1rem;font-weight:700;margin-bottom:5px}
.cbody p{color:var(--muted);font-size:.86rem;line-height:1.65}
.ctag{display:inline-block;padding:3px 10px;border-radius:999px;font-size:10px;font-weight:600;letter-spacing:.5px;margin-top:10px}

/* Threat bars */
.threat-bar{height:7px;background:rgba(255,107,107,.1);border-radius:4px;margin-top:14px;overflow:hidden}
.threat-fill{height:100%;border-radius:4px;width:0%;transition:width 1.6s cubic-bezier(.22,1,.36,1)}

/* Stats */
.stat-num{font-size:clamp(2.2rem,4.5vw,3.5rem);font-weight:900;background:linear-gradient(135deg,var(--cyan),var(--teal));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}

/* ===== FACTS TICKER ===== */
.ticker{position:relative;z-index:1;overflow:hidden;padding:18px 0;background:rgba(0,229,199,.03);border-top:1px solid var(--cbr);border-bottom:1px solid var(--cbr)}
.ticker-track{display:flex;gap:60px;animation:tickerScroll 45s linear infinite;width:max-content}
.ticker-track:hover{animation-play-state:paused}
.ticker-item{display:flex;align-items:center;gap:10px;white-space:nowrap;font-size:.88rem;color:var(--muted)}
.ticker-item i{color:var(--cyan);font-size:14px;flex-shrink:0}
@keyframes tickerScroll{to{transform:translateX(-50%)}}

/* ===== TARGETS ===== */
.target-item{display:flex;gap:16px;align-items:flex-start;padding:20px;background:var(--cbg);border:1px solid var(--cbr);border-radius:14px;transition:border-color .3s}
.target-item:hover{border-color:rgba(0,229,199,.25)}
.target-id{font-size:.85rem;font-weight:700;color:var(--cyan);background:rgba(0,229,199,.08);padding:4px 10px;border-radius:8px;flex-shrink:0;min-width:48px;text-align:center}
.target-bar{height:6px;background:rgba(0,229,199,.08);border-radius:3px;margin-top:10px;overflow:hidden;flex:1}
.target-fill{height:100%;border-radius:3px;width:0%;transition:width 1.8s cubic-bezier(.22,1,.36,1)}

/* ===== SOLUTIONS ===== */
.sol-card{background:var(--cbg);border:1px solid var(--cbr);border-radius:18px;padding:32px 26px;text-align:center;transition:transform .4s,border-color .4s,box-shadow .4s}
.sol-card:hover{transform:translateY(-6px);border-color:rgba(0,229,199,.28);box-shadow:0 20px 56px rgba(0,229,199,.07)}
.sol-icon{width:56px;height:56px;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px}

/* ===== CALCULATOR ===== */
.calc-wrap{background:var(--cbg);border:1px solid var(--cbr);border-radius:22px;padding:36px 32px;backdrop-filter:blur(10px);max-width:640px;margin:0 auto}
.calc-q{margin-bottom:22px}
.calc-q label{display:block;font-size:.9rem;font-weight:500;margin-bottom:8px}
.calc-opts{display:flex;flex-wrap:wrap;gap:8px}
.calc-opt{padding:8px 16px;border:1px solid var(--cbr);border-radius:10px;font-size:.85rem;cursor:pointer;transition:all .25s;background:transparent;color:var(--muted);font-family:inherit}
.calc-opt:hover{border-color:rgba(0,229,199,.3);color:var(--txt)}
.calc-opt.active{background:rgba(0,229,199,.12);border-color:var(--cyan);color:var(--cyan)}
.calc-result{margin-top:28px;padding:24px;border-radius:16px;text-align:center;opacity:0;transform:translateY(12px);transition:all .5s}
.calc-result.show{opacity:1;transform:translateY(0)}
.calc-result-num{font-size:3rem;font-weight:900}
.calc-result-label{font-size:.9rem;color:var(--muted);margin-top:4px}

/* ===== QUIZ ===== */
.quiz-wrap{background:var(--cbg);border:1px solid var(--cbr);border-radius:22px;padding:36px 32px;max-width:680px;margin:0 auto;backdrop-filter:blur(10px)}
.quiz-progress{display:flex;gap:4px;margin-bottom:24px}
.quiz-pip{flex:1;height:4px;border-radius:2px;background:rgba(0,229,199,.1);transition:background .4s}
.quiz-pip.done{background:var(--cyan)}
.quiz-pip.wrong{background:var(--coral)}
.quiz-q-text{font-size:1.1rem;font-weight:600;margin-bottom:16px;line-height:1.5}
.quiz-opt{display:block;width:100%;text-align:left;padding:14px 18px;border:1px solid var(--cbr);border-radius:12px;margin-bottom:8px;background:transparent;color:var(--txt);font-family:inherit;font-size:.92rem;cursor:pointer;transition:all .25s}
.quiz-opt:hover:not(:disabled){border-color:rgba(0,229,199,.3);background:rgba(0,229,199,.04)}
.quiz-opt.correct{border-color:var(--lime);background:rgba(0,255,163,.08);color:var(--lime)}
.quiz-opt.incorrect{border-color:var(--coral);background:rgba(255,107,107,.08);color:var(--coral)}
.quiz-fact{margin-top:14px;padding:14px 16px;border-radius:12px;background:rgba(0,229,199,.04);border:1px solid rgba(0,229,199,.1);font-size:.88rem;color:var(--muted);line-height:1.6;animation:fadeUp .4s ease}
.quiz-next{margin-top:16px;padding:12px 28px;border:none;border-radius:10px;background:var(--teal);color:#fff;font-family:inherit;font-weight:600;cursor:pointer;transition:transform .2s,box-shadow .2s}
.quiz-next:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(13,115,119,.35)}
.quiz-score{font-size:4rem;font-weight:900}
@keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* ===== TIMELINE ===== */
.tl-line{position:absolute;left:50%;top:0;bottom:0;width:2px;background:linear-gradient(to bottom,transparent,var(--cyan),var(--teal),transparent);transform:translateX(-50%)}
.tl-item{position:relative;display:flex;align-items:flex-start;margin-bottom:40px}
.tl-item:nth-child(odd){flex-direction:row-reverse}
.tl-item:nth-child(odd) .tl-content{text-align:right;padding-right:40px;padding-left:0}
.tl-item:nth-child(even) .tl-content{padding-left:40px}
.tl-dot{position:absolute;left:50%;top:6px;width:14px;height:14px;border-radius:50%;background:var(--s);border:3px solid var(--cyan);transform:translateX(-50%);z-index:2;box-shadow:0 0 16px rgba(0,229,199,.3)}
.tl-content{width:calc(50% - 30px)}
.tl-year{font-size:.8rem;font-weight:700;color:var(--cyan);letter-spacing:2px;margin-bottom:4px}
.tl-title{font-size:1.05rem;font-weight:700;margin-bottom:4px}
.tl-desc{font-size:.85rem;color:var(--muted);line-height:1.6}
@media(max-width:768px){
  .tl-line{left:20px}
  .tl-dot{left:20px}
  .tl-item,.tl-item:nth-child(odd){flex-direction:row}
  .tl-item:nth-child(odd) .tl-content,.tl-item:nth-child(even) .tl-content{width:calc(100% - 50px);padding-left:50px;padding-right:0;text-align:left}
}

/* Pledge bottle */
.pledge-bottle{background:rgba(0,229,199,.03);border:1px solid rgba(0,229,199,.15);border-radius:22px;backdrop-filter:blur(12px);padding:36px;animation:bFloat 6s ease-in-out infinite;max-width:540px;margin:0 auto}
@keyframes bFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
.fi{width:100%;padding:13px 16px;background:rgba(0,229,199,.04);border:1px solid rgba(0,229,199,.15);border-radius:11px;color:var(--txt);font-family:inherit;font-size:.92rem;outline:none;transition:border-color .3s,box-shadow .3s}
.fi:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(0,229,199,.1)}
.fi::placeholder{color:var(--muted)}
.btn-p{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;background:linear-gradient(135deg,var(--teal),#0a8f8f);color:#fff;border:none;border-radius:11px;font-family:inherit;font-size:.95rem;font-weight:600;cursor:pointer;transition:transform .3s,box-shadow .3s}
.btn-p:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(13,115,119,.4)}
.btn-p:active{transform:translateY(0)}
.pcard{background:var(--cbg);border:1px solid var(--cbr);border-radius:13px;padding:16px 20px;animation:pIn .5s ease both}
.pcard p{font-style:italic;color:var(--txt);font-size:.9rem;line-height:1.6}
.pcard .au{color:var(--cyan);font-size:.8rem;font-weight:600;margin-top:6px}
@keyframes pIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

/* Newsletter */
.nl-wrap{background:linear-gradient(135deg,rgba(13,115,119,.1),rgba(0,229,199,.04));border:1px solid rgba(0,229,199,.18);border-radius:22px;padding:44px 32px;text-align:center;max-width:640px;margin:0 auto}

/* Alerts */
.alert{padding:12px 18px;border-radius:11px;font-size:.9rem;margin-bottom:18px}
.alert-ok{background:rgba(0,229,199,.1);border:1px solid rgba(0,229,199,.25);color:var(--cyan)}
.alert-err{background:rgba(255,107,107,.1);border:1px solid rgba(255,107,107,.25);color:var(--coral)}

/* Depth meter */
.dm{position:fixed;right:22px;top:50%;transform:translateY(-50%);z-index:150;display:flex;align-items:center;gap:10px;opacity:0;transition:opacity .5s;pointer-events:none}
.dm.vis{opacity:1}
.dm-track{width:3px;height:170px;background:rgba(0,229,199,.08);border-radius:2px;position:relative}
.dm-fill{position:absolute;bottom:0;width:100%;background:linear-gradient(to top,var(--cyan),var(--teal));border-radius:2px;transition:height .25s}
.dm-dot{position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:9px;height:9px;background:var(--cyan);border-radius:50%;box-shadow:0 0 12px var(--cyan);transition:bottom .25s}
.dm-label{font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--cyan);writing-mode:vertical-rl;font-weight:600}

/* Scroll top */
.stt{position:fixed;bottom:28px;right:28px;z-index:200;width:44px;height:44px;border-radius:50%;background:var(--glass);border:1px solid var(--cbr);color:var(--cyan);display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0;transform:translateY(18px);transition:opacity .3s,transform .3s,background .3s;backdrop-filter:blur(10px);font-size:14px}
.stt.vis{opacity:1;transform:translateY(0)}
.stt:hover{background:rgba(0,229,199,.12)}

/* Reveal */
.rv{opacity:0;transform:translateY(28px);transition:opacity .7s ease,transform .7s ease}
.rv.vis{opacity:1;transform:translateY(0)}
.rv-d1{transition-delay:.1s}.rv-d2{transition-delay:.2s}.rv-d3{transition-delay:.3s}.rv-d4{transition-delay:.4s}.rv-d5{transition-delay:.5s}

/* Dive cue between sections */
.dive-cue{position:relative;z-index:1;text-align:center;padding:8px 0;color:var(--muted);font-size:11px;letter-spacing:4px;text-transform:uppercase}
.dive-cue i{color:var(--cyan);margin:0 6px;font-size:10px}

footer{position:relative;z-index:1;border-top:1px solid var(--cbr);padding:36px 20px;text-align:center;color:var(--muted);font-size:.85rem}

@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}
@media(max-width:768px){.dm{display:none!important}.pledge-bottle{padding:24px 18px;animation:none}.hero{padding:20px 16px}.os{padding:70px 16px}}
</style>
</head>
<body>

<!-- Loader -->
<div id="loader"><div class="ld-ring"></div><div class="ld-txt">Descending</div></div>

<!-- Canvas -->
<canvas id="oceanCanvas"></canvas>

<!-- Nav -->
<nav class="nav" id="mainNav">
  <div class="max-w-6xl mx-auto px-5 flex items-center justify-between">
    <a href="#hero" class="flex items-center gap-3 no-underline text-white">
      <svg width="30" height="30" viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="48" stroke="#0d7377" stroke-width="3.5"/><path d="M10,40 C22,30 38,52 50,40 C62,28 78,52 90,40" stroke="#00e5c7" stroke-width="4.5" stroke-linecap="round"/><path d="M10,55 C22,45 38,65 50,55 C62,45 78,65 90,55" stroke="#0d7377" stroke-width="4.5" stroke-linecap="round"/><path d="M10,70 C22,60 38,78 50,70 C62,60 78,78 90,70" stroke="#0a5e5e" stroke-width="4.5" stroke-linecap="round"/></svg>
      <span class="fd font-bold text-base">SDG 14</span>
    </a>
    <div class="hidden md:flex items-center gap-7">
      <a href="#about" class="text-xs text-gray-400 hover:text-white transition no-underline tracking-wide">About</a>
      <a href="#zones" class="text-xs text-gray-400 hover:text-white transition no-underline tracking-wide">Zones</a>
      <a href="#life" class="text-xs text-gray-400 hover:text-white transition no-underline tracking-wide">Life</a>
      <a href="#threats" class="text-xs text-gray-400 hover:text-white transition no-underline tracking-wide">Threats</a>
      <a href="#solutions" class="text-xs text-gray-400 hover:text-white transition no-underline tracking-wide">Solutions</a>
      <a href="#pledge" class="text-xs px-5 py-2 rounded-full bg-teal-700/30 border border-teal-600/40 text-white hover:bg-teal-700/50 transition no-underline tracking-wide">Take Action</a>
    </div>
  </div>
</nav>

<!-- Depth Meter -->
<div class="dm" id="dm">
  <div class="dm-track"><div class="dm-fill" id="dmFill"></div><div class="dm-dot" id="dmDot"></div></div>
  <div class="dm-label" id="dmLabel">Surface</div>
</div>

<!-- Scroll to Top -->
<button class="stt" id="stt" aria-label="Scroll to top"><i class="fas fa-chevron-up"></i></button>

<!-- ===== HERO ===== -->
<section class="hero" id="hero">
  <div class="sdg-badge">
    <div class="sdg-icon">14</div>
    <span class="sdg-label">Life Below Water</span>
  </div>
  <h1 class="hero-title fd">Conserve &amp; Sustain<br>Our Oceans</h1>
  <p class="hero-sub">Beneath the waves lies a world that sustains all life on Earth. Dive in to understand its beauty, its fragility, and what we must do to protect it.</p>
  <div class="scroll-cue"><span>Descend</span><i class="fas fa-angle-down"></i></div>
</section>

<div class="wave-sep"><svg viewBox="0 0 1440 90" preserveAspectRatio="none"><path d="M0,35 C240,85 480,0 720,45 C960,90 1200,15 1440,55 L1440,90 L0,90Z" fill="rgba(13,115,119,.07)"/><path d="M0,55 C360,10 600,75 900,35 C1100,10 1300,65 1440,45 L1440,90 L0,90Z" fill="rgba(13,115,119,.03)"/></svg></div>

<!-- ===== ABOUT ===== -->
<section class="os" id="about">
  <div class="max-w-6xl mx-auto">
    <div class="grid md:grid-cols-2 gap-14 items-center">
      <div class="rv">
        <div class="stag"><i class="fas fa-water"></i> Understanding SDG 14</div>
        <h2 class="stit fd">The Ocean Is Not<br>Just Water</h2>
        <p class="sdesc">SDG 14 calls us to conserve and sustainably use the oceans, seas, and marine resources. The ocean produces over half the world's oxygen, absorbs 30% of CO2, and supports the livelihoods of over 3 billion people.</p>
        <p class="sdesc mt-4">From coral bleaching to plastic-choked trenches, the signals are clear: if the ocean fails, everything fails. This goal demands urgent, coordinated global action across conservation, pollution reduction, and sustainable fisheries.</p>
      </div>
      <div class="grid grid-cols-2 gap-4 rv rv-d2">
        <div class="zcard"><div style="font-size:13px;font-weight:700;color:var(--cyan);margin-bottom:6px;letter-spacing:1px">70.8%</div><h3 style="font-size:1.15rem;font-weight:700;margin-bottom:8px">Earth's Surface</h3><p style="color:var(--muted);font-size:.88rem;line-height:1.6">Covered by ocean, making it the planet's dominant feature.</p><div class="zglow" style="background:var(--cyan);width:90px;height:90px;top:-25px;right:-25px"></div></div>
        <div class="zcard"><div style="font-size:13px;font-weight:700;color:var(--cyan);margin-bottom:6px;letter-spacing:1px">50-80%</div><h3 style="font-size:1.15rem;font-weight:700;margin-bottom:8px">Oxygen Supply</h3><p style="color:var(--muted);font-size:.88rem;line-height:1.6">Produced by marine phytoplankton through photosynthesis.</p><div class="zglow" style="background:var(--teal);width:90px;height:90px;bottom:-25px;left:-25px"></div></div>
        <div class="zcard"><div style="font-size:13px;font-weight:700;color:var(--gold);margin-bottom:6px;letter-spacing:1px">~230K</div><h3 style="font-size:1.15rem;font-weight:700;margin-bottom:8px">Known Species</h3><p style="color:var(--muted);font-size:.88rem;line-height:1.6">And potentially millions more yet to be discovered in the deep.</p><div class="zglow" style="background:var(--gold);width:90px;height:90px;top:-25px;left:-25px"></div></div>
        <div class="zcard"><div style="font-size:13px;font-weight:700;color:var(--coral);margin-bottom:6px;letter-spacing:1px">3 Billion</div><h3 style="font-size:1.15rem;font-weight:700;margin-bottom:8px">Lives Depend On It</h3><p style="color:var(--muted);font-size:.88rem;line-height:1.6">People rely on the ocean for food, income, and coastal protection.</p><div class="zglow" style="background:var(--coral);width:90px;height:90px;bottom:-25px;right:-25px"></div></div>
      </div>
    </div>
  </div>
</section>

<!-- Facts Ticker -->
<div class="ticker">
  <div class="ticker-track" id="tickerTrack">
    <span class="ticker-item"><i class="fas fa-heartbeat"></i> A blue whale's heart is the size of a small car</span>
    <span class="ticker-item"><i class="fas fa-ruler"></i> The Mariana Trench reaches 10,994 metres deep</span>
    <span class="ticker-item"><i class="fas fa-fish"></i> Sharks have existed for over 400 million years — before trees</span>
    <span class="ticker-item"><i class="fas fa-globe-americas"></i> The ocean contains 97% of Earth's water</span>
    <span class="ticker-item"><i class="fas fa-seedling"></i> Seagrass captures carbon 35 times faster than tropical rainforests</span>
    <span class="ticker-item"><i class="fas fa-lightbulb"></i> Over 80% of the ocean remains unmapped and unexplored</span>
    <span class="ticker-item"><i class="fas fa-tint"></i> A single drop of ocean water can contain 10 million viruses</span>
    <span class="ticker-item"><i class="fas fa-water"></i> Ocean currents act as a global conveyor belt regulating climate</span>
    <span class="ticker-item"><i class="fas fa-heartbeat"></i> A blue whale's heart is the size of a small car</span>
    <span class="ticker-item"><i class="fas fa-ruler"></i> The Mariana Trench reaches 10,994 metres deep</span>
    <span class="ticker-item"><i class="fas fa-fish"></i> Sharks have existed for over 400 million years — before trees</span>
    <span class="ticker-item"><i class="fas fa-globe-americas"></i> The ocean contains 97% of Earth's water</span>
    <span class="ticker-item"><i class="fas fa-seedling"></i> Seagrass captures carbon 35 times faster than tropical rainforests</span>
    <span class="ticker-item"><i class="fas fa-lightbulb"></i> Over 80% of the ocean remains unmapped and unexplored</span>
    <span class="ticker-item"><i class="fas fa-tint"></i> A single drop of ocean water can contain 10 million viruses</span>
    <span class="ticker-item"><i class="fas fa-water"></i> Ocean currents act as a global conveyor belt regulating climate</span>
  </div>
</div>

<!-- ===== OCEAN ZONES ===== -->
<section class="os" id="zones" style="padding-top:80px">
  <div class="max-w-6xl mx-auto text-center">
    <div class="stag justify-center rv"><i class="fas fa-layer-group"></i> Ocean Layers</div>
    <h2 class="stit fd rv rv-d1">A World Within Worlds</h2>
    <p class="sdesc mx-auto rv rv-d2">The ocean is not one habitat but many, stacked in layers of light, pressure, and life.</p>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 mt-12">
      <div class="zcard rv rv-d1" style="border-top:3px solid #00e5c7">
        <div style="font-size:12px;font-weight:700;color:#00e5c7;margin-bottom:6px;letter-spacing:1px">0 — 200 m</div>
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:8px">Sunlight Zone</h3>
        <p style="color:var(--muted);font-size:.88rem;line-height:1.65">Where light penetrates and photosynthesis thrives. Home to 90% of all marine life — coral reefs, sea turtles, dolphins.</p><div class="zglow" style="background:#00e5c7;width:100px;height:100px;top:-20px;right:-20px"></div>
      </div>
      <div class="zcard rv rv-d2" style="border-top:3px solid #0d7377">
        <div style="font-size:12px;font-weight:700;color:#0d7377;margin-bottom:6px;letter-spacing:1px">200 — 1,000 m</div>
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:8px">Twilight Zone</h3>
        <p style="color:var(--muted);font-size:.88rem;line-height:1.65">Light fades to a deep blue gloom. Strange creatures with enormous eyes and bioluminescent organs drift through dimness.</p><div class="zglow" style="background:#0d7377;width:100px;height:100px;top:-20px;right:-20px"></div>
      </div>
      <div class="zcard rv rv-d3" style="border-top:3px solid #065a5e">
        <div style="font-size:12px;font-weight:700;color:#065a5e;margin-bottom:6px;letter-spacing:1px">1,000 — 4,000 m</div>
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:8px">Midnight Zone</h3>
        <p style="color:var(--muted);font-size:.88rem;line-height:1.65">Complete darkness. Pressure at 400 atmospheres. Yet giant squid, viperfish, and jellyfish pulse with their own light.</p><div class="zglow" style="background:#065a5e;width:100px;height:100px;top:-20px;right:-20px"></div>
      </div>
      <div class="zcard rv rv-d4" style="border-top:3px solid var(--lime)">
        <div style="font-size:12px;font-weight:700;color:var(--lime);margin-bottom:6px;letter-spacing:1px">4,000 — 11,000 m</div>
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:8px">The Abyss</h3>
        <p style="color:var(--muted);font-size:.88rem;line-height:1.65">The hadal zone descends into trenches deeper than Everest is tall. Yet amphipods and extremophile microbes thrive here.</p><div class="zglow" style="background:var(--lime);width:100px;height:100px;top:-20px;right:-20px"></div>
      </div>
    </div>
  </div>
</section>

<div class="dive-cue"><i class="fas fa-circle"></i> Deeper <i class="fas fa-chevron-down"></i><i class="fas fa-circle"></i></div>

<!-- ===== MARINE LIFE ===== -->
<section class="os" id="life" style="padding-top:50px">
  <div class="max-w-6xl mx-auto">
    <div class="text-center mb-12">
      <div class="stag justify-center rv"><i class="fas fa-fish"></i> Marine Biodiversity</div>
      <h2 class="stit fd rv rv-d1">Life In All Its Forms</h2>
      <p class="sdesc mx-auto rv rv-d2">From the smallest plankton to the largest animal ever to live, marine biodiversity is staggering.</p>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <div class="ccard rv rv-d1"><div class="cimg"><img src="https://picsum.photos/seed/bluewhale42/600/400.jpg" alt="Blue Whale" loading="lazy"></div><div class="cbody"><h3>Blue Whale</h3><p>The largest animal to ever live — up to 30 metres. Their hearts are the size of small cars, yet they feed on tiny krill.</p><span class="ctag" style="background:rgba(255,107,107,.12);color:var(--coral)">Endangered</span></div></div>
      <div class="ccard rv rv-d2"><div class="cimg"><img src="https://picsum.photos/seed/seaturtle88/600/400.jpg" alt="Sea Turtle" loading="lazy"></div><div class="cbody"><h3>Sea Turtle</h3><p>Ancient navigators roaming oceans for 110 million years. All seven species are threatened by plastic and habitat loss.</p><span class="ctag" style="background:rgba(255,215,0,.12);color:var(--gold)">Vulnerable</span></div></div>
      <div class="ccard rv rv-d3"><div class="cimg"><img src="https://picsum.photos/seed/coralreef55/600/400.jpg" alt="Coral Reef" loading="lazy"></div><div class="cbody"><h3>Coral Reefs</h3><p>Rainforests of the sea — less than 1% of the ocean floor but supporting 25% of all marine species.</p><span class="ctag" style="background:rgba(255,107,107,.12);color:var(--coral)">Critical</span></div></div>
      <div class="ccard rv rv-d1"><div class="cimg"><img src="https://picsum.photos/seed/jellyfish99/600/400.jpg" alt="Jellyfish" loading="lazy"></div><div class="cbody"><h3>Jellyfish</h3><p>Over 500 million years old — older than dinosaurs. No brain, heart, or blood, yet they thrive in every ocean.</p><span class="ctag" style="background:rgba(0,229,199,.12);color:var(--cyan)">Ancient</span></div></div>
      <div class="ccard rv rv-d2"><div class="cimg"><img src="https://picsum.photos/seed/seahorse77/600/400.jpg" alt="Seahorse" loading="lazy"></div><div class="cbody"><h3>Seahorse</h3><p>Males carry and give birth to young. Their prehensile tails anchor to seagrass, but coastal development threatens them.</p><span class="ctag" style="background:rgba(255,215,0,.12);color:var(--gold)">Vulnerable</span></div></div>
      <div class="ccard rv rv-d3"><div class="cimg"><img src="https://picsum.photos/seed/mantaray66/600/400.jpg" alt="Manta Ray" loading="lazy"></div><div class="cbody"><h3>Manta Ray</h3><p>Gentle giants with 7-metre wingspans. They possess the largest brain-to-body ratio of any cold-blooded fish.</p><span class="ctag" style="background:rgba(255,107,107,.12);color:var(--coral)">Endangered</span></div></div>
    </div>
  </div>
</section>

<div class="wave-sep flip"><svg viewBox="0 0 1440 70" preserveAspectRatio="none"><path d="M0,25 C480,70 960,0 1440,45 L1440,70 L0,70Z" fill="rgba(0,229,199,.03)"/></svg></div>

<!-- ===== THREATS ===== -->
<section class="os" id="threats" style="padding-top:50px">
  <div class="max-w-4xl mx-auto">
    <div class="text-center mb-12">
      <div class="stag justify-center rv"><i class="fas fa-exclamation-triangle"></i> Crisis Points</div>
      <h2 class="stit fd rv rv-d1">The Oceans Are Under Siege</h2>
      <p class="sdesc mx-auto rv rv-d2">Human activity is pushing marine ecosystems toward collapse at an unprecedented rate.</p>
    </div>
    <div class="flex flex-col gap-5">
      <div class="zcard rv rv-d1"><div class="flex items-start gap-4"><div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(255,107,107,.1)"><i class="fas fa-trash-alt" style="color:var(--coral)"></i></div><div class="flex-1"><h3 class="text-base font-bold mb-1">Plastic Pollution</h3><p style="color:var(--muted);font-size:.88rem;line-height:1.7">8 million tonnes of plastic enter the ocean every year. By 2050, there may be more plastic than fish by weight. Microplastics have been found in the Mariana Trench, Arctic ice, and human blood.</p><div class="threat-bar"><div class="threat-fill" data-width="85" style="background:linear-gradient(90deg,#0d7377,var(--coral))"></div></div><div class="flex justify-between mt-2 text-xs" style="color:var(--muted)"><span>Severity</span><span style="color:var(--coral)">Critical</span></div></div></div></div>
      <div class="zcard rv rv-d2"><div class="flex items-start gap-4"><div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(255,215,0,.1)"><i class="fas fa-anchor" style="color:var(--gold)"></i></div><div class="flex-1"><h3 class="text-base font-bold mb-1">Overfishing</h3><p style="color:var(--muted);font-size:.88rem;line-height:1.7">34% of fish stocks are biologically unsustainable. Industrial fleets strip the ocean faster than species can reproduce, destroying food chains and coastal livelihoods.</p><div class="threat-bar"><div class="threat-fill" data-width="72" style="background:linear-gradient(90deg,#0d7377,var(--gold))"></div></div><div class="flex justify-between mt-2 text-xs" style="color:var(--muted)"><span>Severity</span><span style="color:var(--gold)">Severe</span></div></div></div></div>
      <div class="zcard rv rv-d3"><div class="flex items-start gap-4"><div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(0,229,199,.1)"><i class="fas fa-temperature-high" style="color:var(--cyan)"></i></div><div class="flex-1"><h3 class="text-base font-bold mb-1">Ocean Acidification &amp; Warming</h3><p style="color:var(--muted);font-size:.88rem;line-height:1.7">The ocean has absorbed 30% of human CO2, dropping pH by 0.1 — a 26% acidity increase. This dissolves shells, bleaches coral, and disrupts food webs.</p><div class="threat-bar"><div class="threat-fill" data-width="78" style="background:linear-gradient(90deg,#0d7377,var(--cyan))"></div></div><div class="flex justify-between mt-2 text-xs" style="color:var(--muted)"><span>Severity</span><span style="color:var(--cyan)">Escalating</span></div></div></div></div>
    </div>
  </div>
</section>

<!-- ===== STATISTICS ===== -->
<section class="os" id="stats" style="padding-top:50px">
  <div class="max-w-5xl mx-auto">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="text-center p-6 rv rv-d1"><div class="stat-num" data-target="8" data-suffix="M">0</div><div style="color:var(--muted);font-size:.9rem;margin-top:6px;line-height:1.5">Tonnes of plastic entering oceans yearly</div></div>
      <div class="text-center p-6 rv rv-d2"><div class="stat-num" data-target="80" data-suffix="%">0</div><div style="color:var(--muted);font-size:.9rem;margin-top:6px;line-height:1.5">Of ocean pollution originates on land</div></div>
      <div class="text-center p-6 rv rv-d3"><div class="stat-num" data-target="90" data-suffix="%">0</div><div style="color:var(--muted);font-size:.9rem;margin-top:6px;line-height:1.5">Of large fish stocks already depleted</div></div>
      <div class="text-center p-6 rv rv-d4"><div class="stat-num" data-target="3" data-suffix="B">0</div><div style="color:var(--muted);font-size:.9rem;margin-top:6px;line-height:1.5">People depending on the ocean</div></div>
    </div>
  </div>
</section>

<div class="dive-cue"><i class="fas fa-circle"></i> Deeper <i class="fas fa-chevron-down"></i><i class="fas fa-circle"></i></div>

<!-- ===== SDG 14 TARGETS PROGRESS ===== -->
<section class="os" id="targets" style="padding-top:50px">
  <div class="max-w-4xl mx-auto">
    <div class="text-center mb-12">
      <div class="stag justify-center rv"><i class="fas fa-bullseye"></i> Target Tracker</div>
      <h2 class="stit fd rv rv-d1">SDG 14 Targets &amp; Progress</h2>
      <p class="sdesc mx-auto rv rv-d2">The UN defined 10 specific targets for Life Below Water. Here is how the world is tracking toward each one.</p>
    </div>
    <div class="flex flex-col gap-3" id="targetList">
      <div class="target-item rv rv-d1"><div class="target-id">14.1</div><div class="flex-1"><div class="text-sm font-bold mb-1">Reduce Marine Pollution</div><div style="font-size:.82rem;color:var(--muted);line-height:1.5">By 2025, prevent and significantly reduce marine pollution of all kinds, particularly from land-based activities.</div><div class="target-bar"><div class="target-fill" data-width="25" style="background:linear-gradient(90deg,var(--coral),var(--gold))"></div></div></div><div class="text-sm font-bold" style="color:var(--coral);min-width:36px;text-align:right">25%</div></div>
      <div class="target-item rv rv-d1"><div class="target-id">14.2</div><div class="flex-1"><div class="text-sm font-bold mb-1">Protect &amp; Restore Ecosystems</div><div style="font-size:.82rem;color:var(--muted);line-height:1.5">Sustainably manage and protect marine and coastal ecosystems to avoid significant adverse impacts.</div><div class="target-bar"><div class="target-fill" data-width="30" style="background:linear-gradient(90deg,var(--coral),var(--gold))"></div></div></div><div class="text-sm font-bold" style="color:var(--gold);min-width:36px;text-align:right">30%</div></div>
      <div class="target-item rv rv-d2"><div class="target-id">14.3</div><div class="flex-1"><div class="text-sm font-bold mb-1">Reduce Ocean Acidification</div><div style="font-size:.82rem;color:var(--muted);line-height:1.5">Minimize and address the impacts of ocean acidification, including through enhanced scientific cooperation.</div><div class="target-bar"><div class="target-fill" data-width="15" style="background:linear-gradient(90deg,var(--coral),var(--coral))"></div></div></div><div class="text-sm font-bold" style="color:var(--coral);min-width:36px;text-align:right">15%</div></div>
      <div class="target-item rv rv-d2"><div class="target-id">14.4</div><div class="flex-1"><div class="text-sm font-bold mb-1">Sustainable Fishing</div><div style="font-size:.82rem;color:var(--muted);line-height:1.5">Regulate harvesting, end overfishing, illegal and destructive fishing practices, and implement science-based management plans.</div><div class="target-bar"><div class="target-fill" data-width="35" style="background:linear-gradient(90deg,var(--coral),var(--gold))"></div></div></div><div class="text-sm font-bold" style="color:var(--gold);min-width:36px;text-align:right">35%</div></div>
      <div class="target-item rv rv-d3"><div class="target-id">14.5</div><div class="flex-1"><div class="text-sm font-bold mb-1">Conserve Coastal &amp; Marine Areas</div><div style="font-size:.82rem;color:var(--muted);line-height:1.5">Conserve at least 10% of coastal and marine areas, consistent with international law.</div><div class="target-bar"><div class="target-fill" data-width="28" style="background:linear-gradient(90deg,var(--coral),var(--gold))"></div></div></div><div class="text-sm font-bold" style="color:var(--gold);min-width:36px;text-align:right">28%</div></div>
      <div class="target-item rv rv-d3"><div class="target-id">14.6</div><div class="flex-1"><div class="text-sm font-bold mb-1">End Harmful Fisheries Subsidies</div><div style="font-size:.82rem;color:var(--muted);line-height:1.5">Prohibit certain forms of fisheries subsidies that contribute to overcapacity and overfishing.</div><div class="target-bar"><div class="target-fill" data-width="20" style="background:linear-gradient(90deg,var(--coral),var(--coral))"></div></div></div><div class="text-sm font-bold" style="color:var(--coral);min-width:36px;text-align:right">20%</div></div>
      <div class="target-item rv rv-d4"><div class="target-id">14.7</div><div class="flex-1"><div class="text-sm font-bold mb-1">Increase Economic Benefits</div><div style="font-size:.82rem;color:var(--muted);line-height:1.5">Increase the economic benefits to SIDS and LDCs from the sustainable use of marine resources.</div><div class="target-bar"><div class="target-fill" data-width="22" style="background:linear-gradient(90deg,var(--coral),var(--coral))"></div></div></div><div class="text-sm font-bold" style="color:var(--coral);min-width:36px;text-align:right">22%</div></div>
      <div class="target-item rv rv-d4"><div class="target-id">14.a</div><div class="flex-1"><div class="text-sm font-bold mb-1">Increase Scientific Knowledge</div><div style="font-size:.82rem;color:var(--muted);line-height:1.5">Enhance scientific knowledge, develop research capacity, and transfer marine technology.</div><div class="target-bar"><div class="target-fill" data-width="40" style="background:linear-gradient(90deg,var(--gold),var(--cyan))"></div></div></div><div class="text-sm font-bold" style="color:var(--cyan);min-width:36px;text-align:right">40%</div></div>
      <div class="target-item rv rv-d5"><div class="target-id">14.b</div><div class="flex-1"><div class="text-sm font-bold mb-1">Support Small-Scale Fishers</div><div style="font-size:.82rem;color:var(--muted);line-height:1.5">Provide access of small-scale artisanal fishers to marine resources and markets.</div><div class="target-bar"><div class="target-fill" data-width="25" style="background:linear-gradient(90deg,var(--coral),var(--gold))"></div></div></div><div class="text-sm font-bold" style="color:var(--gold);min-width:36px;text-align:right">25%</div></div>
      <div class="target-item rv rv-d5"><div class="target-id">14.c</div><div class="flex-1"><div class="text-sm font-bold mb-1">Strengthen International Law</div><div style="font-size:.82rem;color:var(--muted);line-height:1.5">Enhance the conservation and sustainable use of oceans through international law, notably UNCLOS.</div><div class="target-bar"><div class="target-fill" data-width="30" style="background:linear-gradient(90deg,var(--coral),var(--gold))"></div></div></div><div class="text-sm font-bold" style="color:var(--gold);min-width:36px;text-align:right">30%</div></div>
    </div>
  </div>
</section>

<!-- ===== SOLUTIONS ===== -->
<section class="os" id="solutions" style="padding-top:50px">
  <div class="max-w-6xl mx-auto">
    <div class="text-center mb-12">
      <div class="stag justify-center rv"><i class="fas fa-hand-holding-heart"></i> What You Can Do</div>
      <h2 class="stit fd rv rv-d1">Solutions Start With You</h2>
      <p class="sdesc mx-auto rv rv-d2">Individual actions, multiplied by billions of people, create unstoppable momentum. Here are concrete steps you can take today.</p>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <div class="sol-card rv rv-d1"><div class="sol-icon" style="background:rgba(0,229,199,.1);color:var(--cyan)"><i class="fas fa-ban"></i></div><h3 style="font-size:1.1rem;font-weight:700;margin-bottom:8px">Refuse Single-Use Plastics</h3><p style="color:var(--muted);font-size:.86rem;line-height:1.65">Carry reusable bags, bottles, and cutlery. Skip the straw. Choose products with minimal packaging. Every piece refused is one less in the ocean.</p></div>
      <div class="sol-card rv rv-d2"><div class="sol-icon" style="background:rgba(255,215,0,.1);color:var(--gold)"><i class="fas fa-fish"></i></div><h3 style="font-size:1.1rem;font-weight:700;margin-bottom:8px">Choose Sustainable Seafood</h3><p style="color:var(--muted);font-size:.86rem;line-height:1.65">Look for MSC or ASC certified products. Use seafood guides like Seafood Watch. Ask restaurants about sourcing. Avoid overfished species.</p></div>
      <div class="sol-card rv rv-d3"><div class="sol-icon" style="background:rgba(0,255,163,.1);color:var(--lime)"><i class="fas fa-leaf"></i></div><h3 style="font-size:1.1rem;font-weight:700;margin-bottom:8px">Reduce Carbon Footprint</h3><p style="color:var(--muted);font-size:.86rem;line-height:1.65">Walk, cycle, use public transit. Eat less meat. Switch to renewable energy. Less CO2 means less ocean warming and acidification.</p></div>
      <div class="sol-card rv rv-d1"><div class="sol-icon" style="background:rgba(255,107,107,.1);color:var(--coral)"><i class="fas fa-shield-alt"></i></div><h3 style="font-size:1.1rem;font-weight:700;margin-bottom:8px">Support Marine Protected Areas</h3><p style="color:var(--muted);font-size:.86rem;line-height:1.65">Donate to organisations creating MPAs. Advocate for the 30x30 pledge. Even landlocked actions influence ocean policy through trade and treaties.</p></div>
      <div class="sol-card rv rv-d2"><div class="sol-icon" style="background:rgba(0,229,199,.1);color:var(--cyan)"><i class="fas fa-broom"></i></div><h3 style="font-size:1.1rem;font-weight:700;margin-bottom:8px">Join Beach Cleanups</h3><p style="color:var(--muted);font-size:.86rem;line-height:1.65">Participate in local or global cleanup events. Organise one yourself. Even inland — 80% of ocean plastic comes from rivers and streams.</p></div>
      <div class="sol-card rv rv-d3"><div class="sol-icon" style="background:rgba(255,215,0,.1);color:var(--gold)"><i class="fas fa-bullhorn"></i></div><h3 style="font-size:1.1rem;font-weight:700;margin-bottom:8px">Educate &amp; Advocate</h3><p style="color:var(--muted);font-size:.86rem;line-height:1.65">Share what you learn. Talk to friends, family, colleagues. Support ocean literacy in schools. Contact representatives about ocean policy.</p></div>
    </div>
  </div>
</section>

<div class="wave-sep"><svg viewBox="0 0 1440 70" preserveAspectRatio="none"><path d="M0,20 C360,65 720,5 1080,45 C1260,65 1380,35 1440,25 L1440,70 L0,70Z" fill="rgba(0,229,199,.04)"/></svg></div>

<!-- ===== PLASTIC FOOTPRINT CALCULATOR ===== -->
<section class="os" id="calculator" style="padding-top:50px">
  <div class="max-w-4xl mx-auto text-center">
    <div class="stag justify-center rv"><i class="fas fa-calculator"></i> Interactive Tool</div>
    <h2 class="stit fd rv rv-d1">Plastic Footprint Calculator</h2>
    <p class="sdesc mx-auto rv rv-d2">Estimate your annual plastic contribution. Answer honestly — awareness is the first step to change.</p>
    <div class="calc-wrap rv rv-d2 mt-10" id="calcWrap">
      <div class="calc-q" data-calc="0">
        <label>How many plastic water bottles do you use per week?</label>
        <div class="calc-opts">
          <button class="calc-opt" data-val="0">None</button>
          <button class="calc-opt" data-val="3">1-3</button>
          <button class="calc-opt" data-val="7">4-7</button>
          <button class="calc-opt" data-val="14">8-14</button>
          <button class="calc-opt" data-val="21">15+</button>
        </div>
      </div>
      <div class="calc-q" data-calc="1">
        <label>How many plastic shopping bags do you use per week?</label>
        <div class="calc-opts">
          <button class="calc-opt" data-val="0">None (reusable)</button>
          <button class="calc-opt" data-val="3">1-3</button>
          <button class="calc-opt" data-val="7">4-7</button>
          <button class="calc-opt" data-val="14">8-14</button>
          <button class="calc-opt" data-val="21">15+</button>
        </div>
      </div>
      <div class="calc-q" data-calc="2">
        <label>How often do you order takeaway with plastic containers?</label>
        <div class="calc-opts">
          <button class="calc-opt" data-val="0">Never</button>
          <button class="calc-opt" data-val="2">1-2 times/week</button>
          <button class="calc-opt" data-val="5">3-5 times/week</button>
          <button class="calc-opt" data-val="10">Daily</button>
        </div>
      </div>
      <div class="calc-q" data-calc="3">
        <label>How many personal care products with microplastics do you use?</label>
        <div class="calc-opts">
          <button class="calc-opt" data-val="0">None (I check labels)</button>
          <button class="calc-opt" data-val="2">1-2 products</button>
          <button class="calc-opt" data-val="5">3-5 products</button>
          <button class="calc-opt" data-val="8">Not sure / many</button>
        </div>
      </div>
      <div class="calc-q" data-calc="4">
        <label>Do you buy items with excess plastic packaging?</label>
        <div class="calc-opts">
          <button class="calc-opt" data-val="0">I actively avoid it</button>
          <button class="calc-opt" data-val="3">Sometimes</button>
          <button class="calc-opt" data-val="7">Mostly</button>
          <button class="calc-opt" data-val="10">I don't pay attention</button>
        </div>
      </div>
      <div class="calc-result" id="calcResult">
        <div class="calc-result-num" id="calcNum" style="color:var(--cyan)">0</div>
        <div class="calc-result-label">kilograms of plastic per year</div>
        <p style="font-size:.85rem;color:var(--muted);margin-top:12px;line-height:1.6" id="calcMsg"></p>
      </div>
    </div>
  </div>
</section>

<!-- ===== OCEAN QUIZ ===== -->
<section class="os" id="quiz" style="padding-top:50px">
  <div class="max-w-4xl mx-auto text-center">
    <div class="stag justify-center rv"><i class="fas fa-brain"></i> Test Your Knowledge</div>
    <h2 class="stit fd rv rv-d1">Ocean IQ Challenge</h2>
    <p class="sdesc mx-auto rv rv-d2">8 questions to test how much you know about our oceans. Can you score 100%?</p>
    <div class="quiz-wrap rv rv-d2 mt-10" id="quizWrap">
      <div class="quiz-progress" id="quizProgress"></div>
      <div id="quizBody"></div>
    </div>
  </div>
</section>

<!-- Facts Ticker 2 -->
<div class="ticker" style="border-top:none">
  <div class="ticker-track" style="animation-direction:reverse;animation-duration:55s">
    <span class="ticker-item"><i class="fas fa-water"></i> Coral reefs generate $36 billion per year in tourism revenue</span>
    <span class="ticker-item"><i class="fas fa-balance-scale"></i> Phytoplankton produce more oxygen than all forests combined</span>
    <span class="ticker-item"><i class="fas fa-satellite-dish"></i> Only 5% of the ocean floor has been mapped in high resolution</span>
    <span class="ticker-item"><i class="fas fa-clock"></i> The ocean absorbs 93% of excess heat from climate change</span>
    <span class="ticker-item"><i class="fas fa-map-marker-alt"></i> There are over 5.25 trillion pieces of plastic in the ocean</span>
    <span class="ticker-item"><i class="fas fa-ship"></i> Shipping accounts for roughly 3% of global greenhouse gas emissions</span>
    <span class="ticker-item"><i class="fas fa-water"></i> Coral reefs generate $36 billion per year in tourism revenue</span>
    <span class="ticker-item"><i class="fas fa-balance-scale"></i> Phytoplankton produce more oxygen than all forests combined</span>
    <span class="ticker-item"><i class="fas fa-satellite-dish"></i> Only 5% of the ocean floor has been mapped in high resolution</span>
    <span class="ticker-item"><i class="fas fa-clock"></i> The ocean absorbs 93% of excess heat from climate change</span>
    <span class="ticker-item"><i class="fas fa-map-marker-alt"></i> There are over 5.25 trillion pieces of plastic in the ocean</span>
    <span class="ticker-item"><i class="fas fa-ship"></i> Shipping accounts for roughly 3% of global greenhouse gas emissions</span>
  </div>
</div>

<!-- ===== CONSERVATION TIMELINE ===== -->
<section class="os" id="timeline" style="padding-top:80px">
  <div class="max-w-4xl mx-auto">
    <div class="text-center mb-14">
      <div class="stag justify-center rv"><i class="fas fa-history"></i> Milestones</div>
      <h2 class="stit fd rv rv-d1">The Fight For Our Oceans</h2>
      <p class="sdesc mx-auto rv rv-d2">Key moments in the global effort to protect marine ecosystems, from the first treaties to today.</p>
    </div>
    <div class="relative" style="padding:10px 0">
      <div class="tl-line"></div>
      <div class="tl-item rv rv-d1"><div class="tl-dot"></div><div class="tl-content"><div class="tl-year">1972</div><div class="tl-title">London Convention</div><div class="tl-desc">First international agreement to prevent marine pollution by dumping waste at sea.</div></div></div>
      <div class="tl-item rv rv-d2"><div class="tl-dot"></div><div class="tl-content"><div class="tl-year">1982</div><div class="tl-title">UNCLOS Adopted</div><div class="tl-desc">United Nations Convention on the Law of the Sea establishes the legal framework for all ocean activities.</div></div></div>
      <div class="tl-item rv rv-d1"><div class="tl-dot"></div><div class="tl-content"><div class="tl-year">1992</div><div class="tl-title">Rio Earth Summit</div><div class="tl-desc">Agenda 21 includes Chapter 17 dedicated to protection of oceans and coastal areas.</div></div></div>
      <div class="tl-item rv rv-d2"><div class="tl-dot"></div><div class="tl-content"><div class="tl-year">1998</div><div class="tl-title">International Year of the Ocean</div><div class="tl-desc">UN-designated year raises global awareness about ocean issues and marine science.</div></div></div>
      <div class="tl-item rv rv-d1"><div class="tl-dot"></div><div class="tl-content"><div class="tl-year">2006</div><div class="tl-title">Coral Reef Crisis</div><div class="tl-desc">Mass bleaching events documented across the Caribbean and Pacific, linked to rising sea temperatures.</div></div></div>
      <div class="tl-item rv rv-d2"><div class="tl-dot"></div><div class="tl-content"><div class="tl-year">2015</div><div class="tl-title">SDGs Adopted</div><div class="tl-desc">All 193 UN member states adopt the 17 Sustainable Development Goals, including SDG 14.</div></div></div>
      <div class="tl-item rv rv-d1"><div class="tl-dot"></div><div class="tl-content"><div class="tl-year">2017</div><div class="tl-title">UN Ocean Conference</div><div class="tl-desc">First global conference dedicated to SDG 14. Over 1,400 voluntary commitments registered.</div></div></div>
      <div class="tl-item rv rv-d2"><div class="tl-dot"></div><div class="tl-content"><div class="tl-year">2022</div><div class="tl-title">30x30 at COP15</div><div class="tl-desc">Historic agreement to protect 30% of Earth's land and oceans by 2030 under the Global Biodiversity Framework.</div></div></div>
      <div class="tl-item rv rv-d1"><div class="tl-dot"></div><div class="tl-content"><div class="tl-year">2025</div><div class="tl-title">Global Plastics Treaty</div><div class="tl-desc">Negotiations ongoing for a legally binding international treaty to end plastic pollution.</div></div></div>
    </div>
  </div>
</section>

<!-- ===== PLEDGE ===== -->
<section class="os" id="pledge" style="padding-top:50px">
  <div class="max-w-6xl mx-auto">
    <div class="text-center mb-12">
      <div class="stag justify-center rv"><i class="fas fa-hand-holding-water"></i> Take Action</div>
      <h2 class="stit fd rv rv-d1">Your Voice Matters</h2>
      <p class="sdesc mx-auto rv rv-d2">Every pledge is a ripple. Together, they become a wave of change.</p>
    </div>
    <div class="grid lg:grid-cols-2 gap-10 items-start">
      <div class="pledge-bottle rv rv-d1">
        <?php if($pMsg): ?><div class="alert alert-<?= $pOk?'ok':'err' ?>"><i class="fas fa-<?= $pOk?'check-circle':'times-circle' ?> mr-2"></i><?= $pMsg ?></div><?php endif; ?>
        <form method="POST" action="#pledge" id="pledgeForm">
          <div class="mb-4"><label class="block text-sm font-medium mb-2" style="color:var(--muted)">Your Name</label><input type="text" name="name" class="fi" placeholder="e.g. Marina Oceanus" required maxlength="80" value="<?= htmlspecialchars($_POST['name']??'') ?>"></div>
          <div class="mb-4"><label class="block text-sm font-medium mb-2" style="color:var(--muted)">Email Address</label><input type="email" name="email" class="fi" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email']??'') ?>"></div>
          <div class="mb-5"><label class="block text-sm font-medium mb-2" style="color:var(--muted)">Your Pledge</label><textarea name="pledge_text" class="fi" rows="3" placeholder="I pledge to reduce my single-use plastic consumption..." required maxlength="500"><?= htmlspecialchars($_POST['pledge_text']??'') ?></textarea><div class="text-right mt-1 text-xs" style="color:var(--muted)"><span id="cc">0</span>/500</div></div>
          <button type="submit" name="pledge_submit" class="btn-p w-full justify-center"><i class="fas fa-paper-plane"></i> Send Your Pledge</button>
        </form>
        <p class="text-center text-xs mt-4" style="color:var(--muted)"><i class="fas fa-lock mr-1"></i> Your email is stored only for pledge verification.</p>
      </div>
      <div class="rv rv-d2">
        <h3 class="text-base font-bold mb-5 flex items-center gap-2"><i class="fas fa-comments" style="color:var(--cyan)"></i> Recent Pledges <span class="text-sm font-normal" style="color:var(--muted)">(<?= $pCount ?> total)</span></h3>
        <?php if(empty($recentP)): ?>
          <div class="text-center py-10" style="color:var(--muted)"><i class="fas fa-water text-2xl mb-3" style="color:var(--cbr)"></i><p>Be the first to pledge for our oceans.</p></div>
        <?php else: foreach($recentP as $i=>$pl): ?>
          <div class="pcard mb-3" style="animation-delay:<?= $i*.1 ?>s"><p>"<?= htmlspecialchars($pl['pledge']) ?>"</p><div class="au">— <?= htmlspecialchars($pl['name']) ?> &middot; <?= date('M j, Y', strtotime($pl['date'])) ?></div></div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- ===== NEWSLETTER ===== -->
<section class="os" id="newsletter" style="padding-top:50px">
  <div class="max-w-4xl mx-auto">
    <div class="nl-wrap rv">
      <div style="width:52px;height:52px;border-radius:16px;background:rgba(0,229,199,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:22px;color:var(--cyan)"><i class="fas fa-envelope-open-text"></i></div>
      <h2 class="stit fd" style="font-size:clamp(1.5rem,3.5vw,2.2rem)">Stay Informed, Stay Involved</h2>
      <p style="color:var(--muted);font-size:.95rem;max-width:440px;margin:0 auto 24px;line-height:1.7">Get monthly updates on ocean conservation progress, new research, and ways to help — straight to your inbox.</p>
      <?php if($nMsg): ?><div class="alert alert-<?= $nOk?'ok':'err' ?>" style="max-width:420px;margin:0 auto 18px"><i class="fas fa-<?= $nOk?'check-circle':'times-circle' ?> mr-2"></i><?= $nMsg ?></div><?php endif; ?>
      <form method="POST" action="#newsletter" class="flex flex-col sm:flex-row gap-3 justify-center items-center" style="max-width:440px;margin:0 auto">
        <input type="email" name="nl_email" class="fi flex-1 w-full" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['nl_email']??'') ?>">
        <button type="submit" name="nl_submit" class="btn-p whitespace-nowrap"><i class="fas fa-paper-plane"></i> Subscribe</button>
      </form>
      <p class="text-xs mt-4" style="color:var(--muted)"><?= $sCount ?> ocean advocates subscribed. No spam, ever.</p>
    </div>
  </div>
</section>

<!-- ===== FOOTER ===== -->
<footer>
  <div class="max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-5">
      <div class="flex items-center gap-3">
        <svg width="24" height="24" viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="48" stroke="#0d7377" stroke-width="3"/><path d="M12,42 C24,32 38,52 50,42 C62,32 76,52 88,42" stroke="#00e5c7" stroke-width="4" stroke-linecap="round" fill="none"/><path d="M12,56 C24,46 38,66 50,56 C62,46 76,66 88,56" stroke="#0d7377" stroke-width="4" stroke-linecap="round" fill="none"/></svg>
        <span class="fd font-bold text-sm">SDG 14 — Life Below Water</span>
      </div>
      <div class="flex items-center gap-5">
        <a href="#about" class="text-xs hover:text-white transition no-underline" style="color:var(--muted)">About</a>
        <a href="#zones" class="text-xs hover:text-white transition no-underline" style="color:var(--muted)">Zones</a>
        <a href="#life" class="text-xs hover:text-white transition no-underline" style="color:var(--muted)">Life</a>
        <a href="#solutions" class="text-xs hover:text-white transition no-underline" style="color:var(--muted)">Solutions</a>
        <a href="#pledge" class="text-xs hover:text-white transition no-underline" style="color:var(--muted)">Act</a>
      </div>
    </div>
    <div class="flex items-center justify-center gap-4 mb-4">
      <a href="https://sdgs.un.org/goals/goal14" target="_blank" rel="noopener" class="text-xs px-4 py-2 rounded-full border no-underline transition hover:bg-white/5" style="color:var(--cyan);border-color:var(--cbr)"><i class="fas fa-external-link-alt mr-1"></i> UN SDG 14 Official</a>
      <a href="https://www.unep.org/explore-topics/oceans-seas" target="_blank" rel="noopener" class="text-xs px-4 py-2 rounded-full border no-underline transition hover:bg-white/5" style="color:var(--cyan);border-color:var(--cbr)"><i class="fas fa-external-link-alt mr-1"></i> UNEP Oceans</a>
    </div>
    <p style="font-size:.78rem">Built to raise awareness for the United Nations Sustainable Development Goal 14. Data sourced from UN, IUCN, and UNEP reports.</p>
  </div>
</footer>

<script>
/* ===========================
   CANVAS OCEAN PARTICLE SYSTEM
   =========================== */
const C=document.getElementById('oceanCanvas'),X=C.getContext('2d');
const prM=window.matchMedia('(prefers-reduced-motion:reduce)').matches;
let W,H,mx=-999,my=-999,sp=0;
function rs(){W=C.width=innerWidth;H=C.height=innerHeight}
rs();addEventListener('resize',rs);
addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY});
addEventListener('mouseleave',()=>{mx=my=-999});

const mob=innerWidth<768;
const bN=prM?0:(mob?14:30),plN=prM?0:(mob?12:35),rN=prM?0:4,fN=prM?0:(mob?2:4);

/* Bubbles */
const bub=[];
for(let i=0;i<bN;i++) bub.push({x:Math.random()*2000,y:Math.random()*2000,r:Math.random()*3+1,sp:Math.random()*.35+.12,wb:Math.random()*6.28,ws:Math.random()*.012+.006,op:Math.random()*.3+.08});

/* Plankton */
const plk=[];
for(let i=0;i<plN;i++) plk.push({x:Math.random()*2000,y:Math.random()*2000,r:Math.random()*1.2+.3,vx:(Math.random()-.5)*.2,vy:(Math.random()-.5)*.12,op:Math.random()*.45+.06,gl:Math.random()>.72});

/* Light rays */
const ray=[];
for(let i=0;i<rN;i++) ray.push({x:Math.random()*2000,w:Math.random()*70+35,op:Math.random()*.035+.012,dr:Math.random()*.12+.04,an:(Math.random()-.5)*.06});

/* Fish */
const fsh=[];
for(let i=0;i<fN;i++) fsh.push({x:Math.random()*2000,y:Math.random()*1200+200,sp:(Math.random()*.35+.2)*(Math.random()>.5?1:-1),sz:Math.random()*10+7,wb:Math.random()*6.28,op:Math.random()*.18+.06});

/* Cursor trail */
const trail=[];
addEventListener('mousemove',e=>{if(trail.length<15&&mx>0) trail.push({x:e.clientX,y:e.clientY,r:Math.random()*1.8+.5,op:.4,vy:-Math.random()*.4-.2,vx:(Math.random()-.5)*.3})});

function dFish(f){
  X.save();X.translate(f.x,f.y);X.scale(f.sp>0?1:-1,1);X.globalAlpha=f.op;
  X.fillStyle='#00e5c7';X.beginPath();X.ellipse(0,0,f.sz,f.sz*.36,0,0,6.28);X.fill();
  X.beginPath();X.moveTo(-f.sz*.75,0);X.lineTo(-f.sz*1.3,-f.sz*.3);X.lineTo(-f.sz*1.3,f.sz*.3);X.closePath();X.fill();
  X.restore();
}

function anim(){
  X.clearRect(0,0,W,H);
  const df=1-sp*.55;

  /* Rays */
  ray.forEach(r=>{
    const op=r.op*df*2;if(op<.002)return;
    X.save();X.translate(r.x,0);X.rotate(r.an);
    const g=X.createLinearGradient(0,0,0,H);g.addColorStop(0,`rgba(0,229,199,${op})`);g.addColorStop(1,'rgba(0,229,199,0)');
    X.fillStyle=g;X.beginPath();X.moveTo(-r.w/2,0);X.lineTo(r.w/2,0);X.lineTo(r.w*.7,H);X.lineTo(-r.w*.7,H);X.closePath();X.fill();X.restore();
    r.x+=r.dr;if(r.x>W+r.w)r.x=-r.w;
  });

  /* Bubbles */
  bub.forEach(b=>{
    b.wb+=b.ws;b.x+=Math.sin(b.wb)*.45;b.y-=b.sp;
    if(b.y<-10){b.y=H+10;b.x=Math.random()*W}
    const dx=b.x-mx,dy=b.y-my,d=Math.sqrt(dx*dx+dy*dy);
    if(d<110&&d>0){b.x+=dx/d*1.2;b.y+=dy/d*1.2}
    const op=b.op*df;if(op<.01)return;
    X.beginPath();X.arc(b.x,b.y,Math.max(.4,b.r),0,6.28);
    X.strokeStyle=`rgba(0,229,199,${op})`;X.lineWidth=.5;X.stroke();
    X.beginPath();X.arc(b.x-b.r*.3,b.y-b.r*.3,Math.max(.15,b.r*.22),0,6.28);
    X.fillStyle=`rgba(255,255,255,${op*.5})`;X.fill();
  });

  /* Trail */
  for(let i=trail.length-1;i>=0;i--){
    const t=trail[i];t.x+=t.vx;t.y+=t.vy;t.op-=.012;
    if(t.op<=0){trail.splice(i,1);continue}
    X.beginPath();X.arc(t.x,t.y,Math.max(.2,t.r),0,6.28);
    X.strokeStyle=`rgba(0,229,199,${t.op})`;X.lineWidth=.4;X.stroke();
  }

  /* Plankton */
  plk.forEach(p=>{
    p.x+=p.vx;p.y+=p.vy;
    if(p.x<-10)p.x=W+10;if(p.x>W+10)p.x=-10;
    if(p.y<-10)p.y=H+10;if(p.y>H+10)p.y=-10;
    const op=p.op*df;if(op<.01)return;
    if(p.gl&&sp>.25){
      const gop=op*Math.min(1,(sp-.25)*3.5);
      X.beginPath();X.arc(p.x,p.y,Math.max(.5,p.r*5),0,6.28);
      const gr=X.createRadialGradient(p.x,p.y,0,p.x,p.y,Math.max(.5,p.r*5));
      gr.addColorStop(0,`rgba(0,255,163,${gop*.4})`);gr.addColorStop(1,'rgba(0,255,163,0)');
      X.fillStyle=gr;X.fill();
    }
    X.beginPath();X.arc(p.x,p.y,Math.max(.15,p.r),0,6.28);
    X.fillStyle=p.gl&&sp>.25?`rgba(0,255,163,${op})`:`rgba(180,220,255,${op})`;X.fill();
  });

  /* Fish */
  fsh.forEach(f=>{
    f.wb+=.018;f.y+=Math.sin(f.wb)*.25;f.x+=f.sp;
    if(f.sp>0&&f.x>W+f.sz*2){f.x=-f.sz*2;f.y=Math.random()*H*.6+H*.15}
    if(f.sp<0&&f.x<-f.sz*2){f.x=W+f.sz*2;f.y=Math.random()*H*.6+H*.15}
    const op=f.op*df;if(op>.01){const sv=f.op;f.op=op;dFish(f);f.op=sv}
  });

  requestAnimationFrame(anim);
}
if(!prM) anim();

/* ===========================
   SCROLL: BG + DEPTH
   =========================== */
const nav=document.getElementById('mainNav'),dmE=document.getElementById('dm'),
dmF=document.getElementById('dmFill'),dmD=document.getElementById('dmDot'),
dmL=document.getElementById('dmLabel'),stt=document.getElementById('stt');

const dz=[{p:0,n:'Surface'},{p:.12,n:'Sunlight Zone'},{p:.3,n:'Twilight Zone'},{p:.55,n:'Midnight Zone'},{p:.78,n:'The Abyss'},{p:1,n:'Mariana Trench'}];
function gDN(p){let n=dz[0].n;for(const z of dz)if(p>=z.p)n=z.n;return n}
let lbg='';
addEventListener('scroll',()=>{
  const ms=document.body.scrollHeight-innerHeight;
  sp=ms>0?Math.min(1,scrollY/ms):0;
  const r=Math.round(10+(3-10)*sp),g=Math.round(22+(8-22)*sp),b=Math.round(40+(16-40)*sp);
  const bg=`rgb(${r},${g},${b})`;if(bg!==lbg){document.body.style.backgroundColor=bg;lbg=bg}
  nav.classList.toggle('vis',scrollY>80);nav.classList.toggle('scrolled',scrollY>180);
  dmE.classList.toggle('vis',scrollY>250);
  const pct=sp*100;dmF.style.height=pct+'%';dmD.style.bottom=pct+'%';dmL.textContent=gDN(sp);
  stt.classList.toggle('vis',scrollY>500);
},{passive:true});
stt.onclick=()=>scrollTo({top:0,behavior:'smooth'});

/* ===========================
   INTERSECTION OBSERVER
   =========================== */
const rvO=new IntersectionObserver(es=>{es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('vis');rvO.unobserve(e.target)}})},{threshold:.1,rootMargin:'0px 0px -30px 0px'});
document.querySelectorAll('.rv').forEach(el=>rvO.observe(el));

/* Stat counters */
const sO=new IntersectionObserver(es=>{es.forEach(e=>{if(!e.isIntersecting)return;const el=e.target,t=+el.dataset.target,s=el.dataset.suffix||'';let c=0;const step=Math.max(1,Math.ceil(t/55));const iv=setInterval(()=>{c+=step;if(c>=t){c=t;clearInterval(iv)}el.textContent=c+s},25);sO.unobserve(el)})},{threshold:.5});
document.querySelectorAll('.stat-num').forEach(el=>sO.observe(el));

/* All fill bars (threats + targets) */
const fO=new IntersectionObserver(es=>{es.forEach(e=>{if(!e.isIntersecting)return;e.target.style.width=e.target.dataset.width+'%';fO.unobserve(e.target)})},{threshold:.25});
document.querySelectorAll('.threat-fill,.target-fill').forEach(el=>fO.observe(el));

/* Char counter */
const ta=document.querySelector('textarea[name="pledge_text"]'),ccE=document.getElementById('cc');
if(ta&&ccE) ta.addEventListener('input',()=>{ccE.textContent=ta.value.length;ccE.style.color=ta.value.length>450?'var(--coral)':'var(--muted)'});

/* ===========================
   PLASTIC FOOTPRINT CALCULATOR
   =========================== */
const calcAns=[null,null,null,null,null];
document.querySelectorAll('.calc-opt').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const qi=+btn.closest('.calc-q').dataset.calc;
    btn.closest('.calc-opts').querySelectorAll('.calc-opt').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');calcAns[qi]=+btn.dataset.val;
    if(calcAns.every(a=>a!==null)) showCalc();
  });
});
function showCalc(){
  /* bottle ~25g, bag ~8g, container ~40g, microplastic ~5g/product, packaging ~30g */
  const annual=calcAns[0]*25*52+calcAns[1]*8*52+calcAns[2]*40*52+calcAns[3]*5*52+calcAns[4]*30*52;
  const kg=(annual/1000).toFixed(1);
  const el=document.getElementById('calcResult');
  document.getElementById('calcNum').textContent=kg;
  const msg=document.getElementById('calcMsg');
  let col;
  if(kg<10){col='var(--lime)';msg.textContent='Excellent! Your footprint is well below average. Keep leading by example and help others reduce theirs.'}
  else if(kg<25){col='var(--cyan)';msg.textContent='Good effort! You are below the global average of ~32kg/year. Small changes could bring you even lower.'}
  else if(kg<45){col='var(--gold)';msg.textContent='You are near the global average. Consider switching to reusables and checking product labels — it adds up fast.'}
  else{col='var(--coral)';msg.textContent='Your footprint is above average. The good news: reducing plastic is one of the easiest environmental changes to make. Start with one swap today.'}
  document.getElementById('calcNum').style.color=col;
  el.style.background=col.replace(')',',0.06)').replace('var(','rgba(').replace(/--[\w]+/g,m=>{
    const map={'--lime':'0,255,163','--cyan':'0,229,199','--gold':'255,215,0','--coral':'255,107,107'};return map[m]||'0,229,199'});
  el.style.border='1px solid '+col;
  el.classList.add('show');
}

/* ===========================
   OCEAN QUIZ
   =========================== */
const QD=[
  {q:"What percentage of the Earth's surface is covered by ocean?",o:["50%","60%","70.8%","85%"],c:2,f:"The ocean covers 70.8% of Earth's surface — that's roughly 361 million square kilometres."},
  {q:"How much of the ocean has been explored by humans?",o:["About 5%","About 20%","About 50%","About 80%"],c:0,f:"Over 80% of the ocean remains unmapped, unobserved, and unexplored. We know more about the surface of Mars."},
  {q:"What produces over 50% of the oxygen in Earth's atmosphere?",o:["Rainforests","Marine phytoplankton","Alpine vegetation","Wetlands"],c:1,f:"Tiny marine phytoplankton are responsible for 50-80% of atmospheric oxygen through photosynthesis."},
  {q:"Approximately how many tonnes of plastic enter the ocean each year?",o:["1 million","4 million","8 million","15 million"],c:2,f:"An estimated 8 million tonnes of plastic enter the ocean annually — equivalent to dumping a garbage truck of plastic every minute."},
  {q:"What is the deepest point in the ocean?",o:["Puerto Rico Trench","Tonga Trench","Mariana Trench","Java Trench"],c:2,f:"The Challenger Deep in the Mariana Trench reaches 10,994 metres — deeper than Mount Everest is tall."},
  {q:"By what year could there be more plastic than fish in the ocean by weight?",o:["2030","2040","2050","2100"],c:2,f:"The Ellen MacArthur Foundation and World Economic Forum project that by 2050, oceans could contain more plastic than fish by weight."},
  {q:"Coral reefs support approximately what percentage of all known marine species?",o:["10%","15%","25%","40%"],c:2,f:"Despite covering less than 1% of the ocean floor, coral reefs support roughly 25% of all marine species."},
  {q:"How much of the excess heat from climate change does the ocean absorb?",o:["About 30%","About 60%","About 93%","About 100%"],c:2,f:"The ocean has absorbed over 93% of the excess heat trapped by greenhouse gases since the 1970s, dramatically slowing warming on land."}
];
let qIdx=0,qScore=0,qAnswered=false;
const qProg=document.getElementById('quizProgress'),qBody=document.getElementById('quizBody');

function initQuiz(){
  qProg.innerHTML='';QD.forEach((_,i)=>{const d=document.createElement('div');d.className='quiz-pip';d.id='qp'+i;qProg.appendChild(d)});
  renderQ();
}
function renderQ(){
  if(qIdx>=QD.length){showScore();return}
  qAnswered=false;
  const q=QD[qIdx];
  qBody.innerHTML=`<div class="quiz-q-text">${qIdx+1}. ${q.q}</div>${q.o.map((o,i)=>`<button class="quiz-opt" data-i="${i}">${o}</button>`).join('')}`;
  qBody.querySelectorAll('.quiz-opt').forEach(b=>b.addEventListener('click',()=>answerQ(+b.dataset.i)));
}
function answerQ(i){
  if(qAnswered)return;qAnswered=true;
  const q=QD[qIdx],btns=qBody.querySelectorAll('.quiz-opt'),pip=document.getElementById('qp'+qIdx);
  btns.forEach((b,bi)=>{b.disabled=true;if(bi===q.c)b.classList.add('correct');if(bi===i&&i!==q.c)b.classList.add('incorrect')});
  if(i===q.c){qScore++;pip.classList.add('done')}else{pip.classList.add('wrong')}
  qBody.insertAdjacentHTML('beforeend',`<div class="quiz-fact"><i class="fas fa-info-circle" style="color:var(--cyan);margin-right:6px"></i>${q.f}</div><button class="quiz-next" id="qNext">${qIdx<QD.length-1?'Next Question':'See Results'}</button>`);
  document.getElementById('qNext').addEventListener('click',()=>{qIdx++;renderQ()});
}
function showScore(){
  const pct=Math.round(qScore/QD.length*100);
  let col,msg;
  if(pct>=100){col='var(--lime)';msg="Perfect score! You are an ocean champion. Now share your knowledge and inspire others."}
  else if(pct>=75){col='var(--cyan)';msg="Excellent! You know your oceans well. A few more facts and you will be unstoppable."}
  else if(pct>=50){col='var(--gold)';msg="Good effort! You have a solid foundation. Keep learning — the ocean has endless stories to tell."}
  else{col='var(--coral)';msg="The ocean still holds many surprises for you. Explore the sections above and try again!"}
  qBody.innerHTML=`<div style="padding:20px 0"><div class="quiz-score" style="color:${col}">${qScore}/${QD.length}</div><div style="font-size:1.3rem;font-weight:700;margin-top:8px">${pct}% Correct</div><p style="color:var(--muted);font-size:.92rem;margin-top:14px;line-height:1.7;max-width:440px;margin-left:auto;margin-right:auto">${msg}</p><button class="quiz-next" style="margin-top:20px" onclick="qIdx=0;qScore=0;initQuiz()"><i class="fas fa-redo mr-2"></i>Try Again</button></div>`;
}
initQuiz();

/* ===========================
   LOADER
   =========================== */
addEventListener('load',()=>setTimeout(()=>document.getElementById('loader').classList.add('hidden'),350));
</script>
</body>
</html>