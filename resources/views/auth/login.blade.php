<!doctype html>
<html lang="mn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Нэвтрэх — Диспетчерийн үндэсний төв</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;display=swap">
<style>
body{margin:0;background:#FFFFFF}
a{color:#1A73FF}a:hover{color:#0B5ED7}
.inp::placeholder{color:#94A3B8}
.inp:focus{outline:none;border-color:#3D8BFF;box-shadow:0 0 0 3px rgba(61,139,255,.25)}
.inp.is-invalid{border-color:#FF6B6B}
button:focus-visible,a:focus-visible{outline:3px solid #1A73FF;outline-offset:2px}
.alert{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;border-radius:8px;font-size:14px;line-height:1.5;color:#B91C1C;background:#FEF2F2;border:1px solid #FECACA}
.alert svg{flex-shrink:0;margin-top:2px}
.brand-logo{width:84%;height:84%;object-fit:contain}
.panel a{color:#1A73FF}.panel a:hover{color:#0B5ED7}
.panel .inp::placeholder{color:#94A3B8}
.panel .inp:-webkit-autofill{-webkit-text-fill-color:#0F2A55;-webkit-box-shadow:0 0 0 100px #FFFFFF inset;caret-color:#0F2A55}
.panel button:focus-visible,.panel a:focus-visible{outline-color:#1A73FF}
.brand-icon{color:#1A73FF;display:flex}

@keyframes rise{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:none}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes pop{0%{opacity:0;transform:scale(.4)}70%{opacity:1;transform:scale(1.12)}100%{opacity:1;transform:scale(1)}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-7px)}}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes wiggle{0%,100%{transform:rotate(-8deg)}50%{transform:rotate(10deg)}}
@keyframes dash{to{stroke-dashoffset:-36}}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.2}}
@keyframes wave{0%,100%{opacity:.2}40%{opacity:1}}
@keyframes glow{0%,100%{opacity:.3;transform:scale(1)}50%{opacity:.75;transform:scale(1.15)}}
@keyframes cable{0%,100%{stroke-opacity:.55}50%{stroke-opacity:1}}
@keyframes bar{from{transform:scaleX(0)}to{transform:scaleX(1)}}
@keyframes shine{0%{transform:translateX(-120%) skewX(-20deg)}60%,100%{transform:translateX(380%) skewX(-20deg)}}
@keyframes nudge{0%,100%{transform:translateX(0)}50%{transform:translateX(4px)}}
@keyframes slideTool{from{opacity:0;transform:translateX(-30px)}to{opacity:1;transform:none}}

.r1{animation:rise .8s cubic-bezier(.2,.7,.2,1) both}
.r2{animation:rise .8s .12s cubic-bezier(.2,.7,.2,1) both}
.r3{animation:rise .8s .24s cubic-bezier(.2,.7,.2,1) both}
.r4{animation:rise .8s .36s cubic-bezier(.2,.7,.2,1) both}
.r5{animation:rise .9s .5s cubic-bezier(.2,.7,.2,1) both}
.f1{animation:rise .7s .2s cubic-bezier(.2,.7,.2,1) both}
.f2{animation:rise .7s .3s cubic-bezier(.2,.7,.2,1) both}
.f3{animation:rise .7s .4s cubic-bezier(.2,.7,.2,1) both}
.f4{animation:rise .7s .5s cubic-bezier(.2,.7,.2,1) both}
.f5{animation:rise .7s .6s cubic-bezier(.2,.7,.2,1) both}
.fade{animation:fadeIn 1.2s .8s both}
.ft1{animation:pop .5s 1s both}.ft2{animation:pop .5s 1.15s both}.ft3{animation:pop .5s 1.3s both}
.lock{animation:pop .6s .15s both,float 4s 1s ease-in-out infinite}
.logo-gear{animation:spin 10s linear infinite}

.mon{animation:float 6s ease-in-out infinite}
.twr{animation:float 6s .8s ease-in-out infinite}
.net{animation:float 6s 1.6s ease-in-out infinite}
.spin-slow{transform-box:fill-box;transform-origin:center;animation:spin 7s linear infinite}
.spin-fast{transform-box:fill-box;transform-origin:center;animation:spin 1s linear infinite}
.wig{transform-box:fill-box;transform-origin:center;animation:wiggle 2.4s ease-in-out infinite}
.wire{stroke-dasharray:3 6;animation:dash 1.6s linear infinite}
.cbl{animation:cable 2s ease-in-out infinite}
.led{animation:blink 1.3s ease-in-out infinite}
.l2{animation-delay:.4s}.l3{animation-delay:.8s}
.w1{animation:wave 1.8s ease-in-out infinite}
.w2{animation:wave 1.8s .25s ease-in-out infinite}
.w3{animation:wave 1.8s .5s ease-in-out infinite}
.halo{transform-box:fill-box;transform-origin:center;animation:glow 3s ease-in-out infinite}
.h2{animation-delay:1s}.h3{animation-delay:2s}
.ln{transform-box:fill-box;transform-origin:left center;animation:bar .6s ease-out both}
.ln1{animation-delay:1.2s}.ln2{animation-delay:1.35s}.ln3{animation-delay:1.5s}.ln4{animation-delay:1.65s}
.tool1{animation:slideTool .8s 1s cubic-bezier(.2,.7,.2,1) both}
.tool2{animation:slideTool .8s 1.2s cubic-bezier(.2,.7,.2,1) both}
.feat-gear{animation:spin 8s linear infinite}

.btn{position:relative;overflow:hidden;transition:transform .15s ease,box-shadow .15s ease,filter .15s ease}
.btn::after{content:"";position:absolute;top:0;left:0;width:26%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.35),transparent);animation:shine 3.5s 1.6s ease-in-out infinite}
.btn:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(26,115,255,.45);filter:brightness(1.08)}
.btn:hover .arw{animation:nudge .6s ease-in-out infinite}
.btn:active{transform:none;box-shadow:none}
.btn[disabled]{opacity:.75;cursor:progress}

/* Responsive */
@media (min-width:1700px){
.hero{padding:64px 110px 56px !important}
.login{max-width:420px !important}
}
@media (max-width:980px){
.wrap{flex-direction:column !important;flex-wrap:nowrap !important}
.panel{order:-1;flex:none !important;border-left:0 !important;border-bottom:1px solid #E2E8F0;padding:24px 20px 32px !important}
.login{margin:8px 0 !important}
.hero{flex:none !important;padding:40px 32px 36px !important}
.illo{max-width:460px !important;margin:0 !important}
}
@media (max-width:600px){
.panel{padding:16px 16px 28px !important}
.title{font-size:24px !important}
.hero{padding:32px 20px 28px !important;gap:20px !important}

.logo-box{width:54px !important;height:54px !important}
.brand-name{font-size:19px !important}
.brand-en{font-size:13px !important}
.headline{font-size:22px !important}
.lead{font-size:15px !important}
.feats{flex-direction:column !important;align-items:flex-start !important;gap:12px !important;font-size:15px !important}
.feats svg{width:30px;height:30px}
.sep{display:none !important}
}
@media (prefers-reduced-motion:reduce){*,*::before,*::after{animation:none !important;transition:none !important}}
</style>
</head>
<body>
<div class="wrap" style="min-height: 100vh; display: flex; flex-wrap: wrap; font-family: 'Manrope', system-ui, sans-serif; color: #0F2A55; background: #FFFFFF">

<section class="hero" style="flex: 1.25 1 540px; min-width: 0; box-sizing: border-box; padding: 48px clamp(24px, 4.5vw, 72px) 40px; display: flex; flex-direction: column; gap: 26px; position: relative; overflow: hidden; background: #FFFFFF">
<div aria-hidden="true" class="fade" style="position: absolute; right: -15%; bottom: -25%; width: 100%; height: 75%; border-radius: 50%; background: radial-gradient(closest-side, rgba(40,110,255,.10), transparent); pointer-events: none"></div>

<div class="r1" style="display: flex; align-items: center; gap: 16px; position: relative">
<div class="logo-box" style="width: 68px; height: 68px; flex-shrink: 0; border-radius: 50%; background: #FFFFFF; border: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(26,115,255,.45)">
@include('partials.brand-logo')
</div>
<div style="display: flex; flex-direction: column; gap: 6px">
<div class="brand-name" style="font-size: 26px; font-weight: 800; line-height: 1.15">Диспетчерийн үндэсний төв</div>
<div class="brand-en" style="font-size: 16px; font-weight: 800; line-height: 1.15">National Dispatching Center</div>
</div>
</div>

<div style="display: flex; flex-direction: column; gap: 16px; position: relative; margin-top: clamp(14px, 8vh, 90px)">
<h1 class="r3 headline" style="margin: 0; font-size: clamp(24px, 2.4vw, 36px); line-height: 1.1; font-weight: 800">IT засвар үйлчилгээний<br>дуудлага, хүсэлт</h1>
<p class="r4 lead" style="margin: 0; font-size: 17px; line-height: 1.5; color: #475569; max-width: 580px">Системийн хөгжүүлэлт, засвар - компьютер - сүлжээ</p>
</div>

<div class="r5 illo" style="position: relative; width: 100%; max-width: 560px; margin: auto 0 0">
<svg role="img" aria-label="Компьютер, системийн блок, свич, рутер болон засварын багаж" viewBox="0 0 800 320" width="100%" style="display: block; overflow: visible">
<defs>
<linearGradient id="sBody" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2F6BFF"></stop><stop offset="1" stop-color="#123A9E"></stop></linearGradient>
<linearGradient id="sTop" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#4C86FF"></stop><stop offset="1" stop-color="#2459D6"></stop></linearGradient>
<linearGradient id="sScr" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#1B4FD0"></stop><stop offset="1" stop-color="#0B2A7A"></stop></linearGradient>
<linearGradient id="sCase" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#0E2766"></stop><stop offset="1" stop-color="#0A1C4A"></stop></linearGradient>
</defs>

<ellipse class="fade" cx="420" cy="292" rx="380" ry="22" fill="#1A4FD0" opacity=".25"></ellipse>

<path class="wire" d="M130 214 V250 H300" fill="none" stroke="#4C9BFF" stroke-width="2"></path>
<path class="wire" d="M300 250 H470 V240" fill="none" stroke="#4C9BFF" stroke-width="2"></path>
<path class="wire" d="M405 70 H480 V130" fill="none" stroke="#4C9BFF" stroke-width="2"></path>
<path class="wire" d="M480 130 H520 V200" fill="none" stroke="#4C9BFF" stroke-width="2"></path>

<circle r="4" fill="#8CC0FF"><animateMotion dur="3.2s" repeatCount="indefinite" path="M130 214 V250 H470 V240"></animateMotion></circle>
<circle r="4" fill="#8CC0FF"><animateMotion dur="2.6s" begin="1s" repeatCount="indefinite" path="M405 70 H480 V130 H520 V200"></animateMotion></circle>

<circle class="halo" cx="300" cy="250" r="9" fill="#3D8BFF" opacity=".5"></circle><circle cx="300" cy="250" r="4.5" fill="#8CC0FF"></circle>
<circle class="halo h2" cx="480" cy="130" r="9" fill="#3D8BFF" opacity=".5"></circle><circle cx="480" cy="130" r="4.5" fill="#8CC0FF"></circle>
<circle class="halo h3" cx="480" cy="70" r="9" fill="#3D8BFF" opacity=".5"></circle><circle cx="480" cy="70" r="4.5" fill="#8CC0FF"></circle>

<g class="mon">
<rect x="20" y="30" width="226" height="164" rx="12" fill="url(#sBody)"></rect>
<rect x="31" y="41" width="204" height="140" rx="6" fill="url(#sScr)"></rect>
<rect class="ln ln1" x="42" y="58" width="34" height="6" rx="3" fill="#6E9CFF"></rect>
<rect class="ln ln2" x="42" y="72" width="26" height="6" rx="3" fill="#6E9CFF"></rect>
<rect class="ln ln3" x="42" y="140" width="34" height="6" rx="3" fill="#6E9CFF"></rect>
<rect class="ln ln4" x="42" y="154" width="26" height="6" rx="3" fill="#6E9CFF"></rect>
<g transform="translate(108 76) scale(2.4)"><g class="spin-slow"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" fill="#3D8BFF" stroke="#8CC0FF" stroke-width=".8"></path><circle cx="12" cy="12" r="3" fill="#0B2A7A"></circle></g></g>
<g transform="translate(150 88) scale(2.6)"><g class="wig"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" fill="#DCE8FF" stroke="#8CC0FF" stroke-width=".6"></path></g></g>
<path d="M118 194 H148 L156 226 H110 Z" fill="#1F4FC4"></path>
<rect x="84" y="224" width="98" height="12" rx="6" fill="#2F6BFF"></rect>
</g>

<g class="twr">
<rect x="262" y="8" width="144" height="222" rx="7" fill="url(#sCase)" stroke="#2F6BFF" stroke-width="2"></rect>
<rect x="272" y="20" width="92" height="198" rx="4" fill="#061436" stroke="#1F4FC4" stroke-width="1"></rect>
<circle class="halo" cx="303" cy="62" r="30" fill="#2F6BFF" opacity=".25"></circle>
<circle cx="303" cy="62" r="26" fill="#0B2A7A" stroke="#4C9BFF" stroke-width="2"></circle>
<g class="spin-fast">
<ellipse cx="303" cy="50" rx="6" ry="12" fill="#4C9BFF"></ellipse>
<ellipse cx="315" cy="62" rx="12" ry="6" fill="#4C9BFF"></ellipse>
<ellipse cx="303" cy="74" rx="6" ry="12" fill="#4C9BFF"></ellipse>
<ellipse cx="291" cy="62" rx="12" ry="6" fill="#4C9BFF"></ellipse>
</g>
<circle cx="303" cy="62" r="5" fill="#8CC0FF"></circle>
<rect x="322" y="40" width="34" height="34" rx="3" fill="#0E2766" stroke="#2F6BFF"></rect>
<rect x="282" y="104" width="72" height="8" rx="2" fill="#1A3E9E"></rect>
<rect x="282" y="118" width="72" height="8" rx="2" fill="#1A3E9E"></rect>
<rect x="280" y="142" width="78" height="22" rx="3" fill="#1F4FC4"></rect>
<rect class="led" x="284" y="158" width="70" height="3" rx="1.5" fill="#5CE1FF"></rect>
<rect x="282" y="176" width="40" height="30" rx="2" fill="#0E2766" stroke="#1F4FC4"></rect>
<rect x="372" y="24" width="26" height="8" rx="2" fill="#1A3E9E"></rect>
<rect x="372" y="38" width="26" height="8" rx="2" fill="#1A3E9E"></rect>
<rect x="372" y="52" width="26" height="8" rx="2" fill="#1A3E9E"></rect>
<circle class="led l2" cx="385" cy="120" r="5" fill="none" stroke="#5CE1FF" stroke-width="2"></circle>
</g>

<g class="net">
<rect x="430" y="196" width="170" height="44" rx="8" fill="url(#sBody)"></rect>
<rect x="430" y="196" width="170" height="12" rx="6" fill="url(#sTop)"></rect>
<circle class="led" cx="446" cy="222" r="3" fill="#5CE1FF"></circle>
<circle class="led l2" cx="456" cy="222" r="3" fill="#5CE1FF"></circle>
<circle class="led l3" cx="466" cy="222" r="3" fill="#5CE1FF"></circle>
<rect x="484" y="214" width="14" height="14" rx="2" fill="#061436"></rect>
<rect x="502" y="214" width="14" height="14" rx="2" fill="#061436"></rect>
<rect x="520" y="214" width="14" height="14" rx="2" fill="#061436"></rect>
<rect x="538" y="214" width="14" height="14" rx="2" fill="#061436"></rect>
<rect x="556" y="214" width="14" height="14" rx="2" fill="#061436"></rect>

<path d="M640 212 V112 M700 212 V96 M760 212 V112" stroke="#4C86FF" stroke-width="6" stroke-linecap="round"></path>
<rect x="612" y="206" width="176" height="56" rx="10" fill="url(#sBody)"></rect>
<rect x="612" y="206" width="176" height="14" rx="7" fill="url(#sTop)"></rect>
<circle class="led" cx="638" cy="240" r="3.5" fill="#5CE1FF"></circle>
<circle class="led l2" cx="652" cy="240" r="3.5" fill="#5CE1FF"></circle>
<circle class="led l3" cx="666" cy="240" r="3.5" fill="#5CE1FF"></circle>
<rect x="690" y="232" width="16" height="16" rx="2" fill="#061436"></rect>
<rect x="712" y="232" width="16" height="16" rx="2" fill="#061436"></rect>
<path class="w1" d="M752 242 A8 8 0 0 1 764 242" fill="none" stroke="#5CE1FF" stroke-width="2.5" stroke-linecap="round"></path>

<path class="cbl" d="M491 228 C491 270 560 280 600 268 S690 262 698 248" fill="none" stroke="#3D8BFF" stroke-width="4" stroke-linecap="round"></path>
<path class="cbl" d="M527 228 C530 262 620 274 660 268 S716 258 720 248" fill="none" stroke="#5CB8FF" stroke-width="4" stroke-linecap="round" style="animation-delay:1s"></path>

<circle cx="700" cy="70" r="8" fill="#5CB8FF"></circle>
<path class="w1" d="M674 50 A36 36 0 0 1 726 50" fill="none" stroke="#5CB8FF" stroke-width="9" stroke-linecap="round"></path>
<path class="w2" d="M652 30 A66 66 0 0 1 748 30" fill="none" stroke="#5CB8FF" stroke-width="9" stroke-linecap="round"></path>
<path class="w3" d="M630 10 A96 96 0 0 1 770 10" fill="none" stroke="#5CB8FF" stroke-width="9" stroke-linecap="round"></path>
</g>

<g class="tool1">
<g transform="rotate(8 330 284)">
<rect x="236" y="274" width="70" height="20" rx="10" fill="#1A73FF"></rect>
<rect x="250" y="274" width="8" height="20" fill="#0A1C4A"></rect>
<rect x="270" y="274" width="8" height="20" fill="#0A1C4A"></rect>
<rect x="306" y="280" width="16" height="8" fill="#0A1C4A"></rect>
<path d="M322 282 H424 L432 284 L424 286 H322 Z" fill="#94A3B8"></path>
</g>
</g>
<g class="tool2">
<g transform="rotate(6 540 300)">
<path d="M462 296 H600" stroke="#94A3B8" stroke-width="10" stroke-linecap="round"></path>
<circle cx="606" cy="296" r="13" fill="#94A3B8"></circle>
<rect x="606" y="290" width="16" height="12" fill="#FFFFFF"></rect>
<circle cx="458" cy="296" r="10" fill="#94A3B8"></circle>
<circle cx="458" cy="296" r="5" fill="#FFFFFF"></circle>
</g>
</g>
</svg>
</div>

<div class="feats" style="display: flex; flex-wrap: wrap; align-items: center; gap: 16px 28px; position: relative; margin-top: 8px; font-size: 18px; color: #0F2A55">
<div class="ft1" style="display: flex; align-items: center; gap: 14px">
<svg aria-hidden="true" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#4C9BFF" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M12 18v-6M9 15h6"></path></svg>
Дуудлага бүртгэх
</div>
<span class="sep" aria-hidden="true" style="width: 1px; height: 48px; background: #CBD5E1"></span>
<div class="ft2" style="display: flex; align-items: center; gap: 14px">
<svg class="feat-gear" aria-hidden="true" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#4C9BFF" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
Засварын явц
</div>
<span class="sep" aria-hidden="true" style="width: 1px; height: 48px; background: #CBD5E1"></span>
<div class="ft3" style="display: flex; align-items: center; gap: 14px">
<svg aria-hidden="true" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#4C9BFF" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
Техникийн үйлчилгээ
</div>
</div>
</section>

<main class="panel" style="flex: 1 1 400px; min-width: 0; box-sizing: border-box; padding: 32px 24px; display: flex; flex-direction: column; align-items: center; background: #EAF2FF; color: #0F2A55; border-left: 1px solid #D6E2F5">
<form method="POST" action="{{ route('login.attempt') }}" id="loginForm" class="login" novalidate style="width: 100%; max-width: 380px; margin: auto 0; padding: 20px 0; display: flex; flex-direction: column; gap: 16px">
@csrf
<div style="display: flex; flex-direction: column; align-items: center; gap: 8px; text-align: center">
<div class="lock" style="width: 72px; height: 66px; border-radius: 14px; background: #FFFFFF; border: 1px solid #D6E2F5; display: flex; align-items: center; justify-content: center; margin-bottom: 8px">
<svg aria-hidden="true" width="32" height="32" viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="12" rx="2.5" fill="#1A73FF"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3" fill="none" stroke="#1A73FF" stroke-width="2.4"></path><circle cx="12" cy="15.5" r="1.8" fill="#FFFFFF"></circle><path d="M12 16v2.6" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round"></path></svg>
</div>
<h2 class="f1 title" style="margin: 0; font-size: 28px; font-weight: 800">Тавтай морил!</h2>
<p class="f1" style="margin: 0; font-size: 15px; color: #5B6B8C">Байгууллагын эрхээрээ нэвтэрнэ үү.</p>
</div>

@if ($errors->any())
<div class="alert" role="alert">
<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
<span>{{ $errors->first() }}</span>
</div>
@endif

<div class="f2" style="display: flex; flex-direction: column; gap: 6px; margin-top: 10px">
<label for="username" style="font-size: 14px; font-weight: 500">Нэвтрэх нэр</label>
<div style="position: relative">
<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="1.6" style="position: absolute; left: 14px; top: 14px"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c0-4 4-6 8-6s8 2 8 6"></path></svg>
<input id="username" name="username" class="inp @error('username') is-invalid @enderror" type="text" value="{{ old('username') }}" autocomplete="username" placeholder="Нэвтрэх нэрээ оруулна уу" autofocus required style="width: 100%; height: 48px; box-sizing: border-box; padding: 0 14px 0 46px; font-size: 15px; font-family: inherit; color: #0F2A55; background: #FFFFFF; border: 1px solid #C7D6EE; border-radius: 8px; transition: border-color .2s, box-shadow .2s">
</div>
</div>

<div class="f3" style="display: flex; flex-direction: column; gap: 6px">
<label for="password" style="font-size: 14px; font-weight: 500">Нууц үг</label>
<div style="position: relative">
<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="1.6" style="position: absolute; left: 14px; top: 14px"><rect x="5" y="10" width="14" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"></path></svg>
<input id="password" name="password" class="inp @error('password') is-invalid @enderror" type="password" autocomplete="current-password" placeholder="Нууц үгээ оруулна уу" required style="width: 100%; height: 48px; box-sizing: border-box; padding: 0 52px 0 46px; font-size: 15px; font-family: inherit; color: #0F2A55; background: #FFFFFF; border: 1px solid #C7D6EE; border-radius: 8px; transition: border-color .2s, box-shadow .2s">
<button type="button" id="hd-eye" aria-label="Нууц үгийг харуулах" style="position: absolute; right: 2px; top: 2px; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: transparent; border: 0; border-radius: 8px; cursor: pointer">
<svg id="eye-off" style="display:none" aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="1.8" stroke-linecap="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle><path d="M3 3l18 18"></path></svg>
<svg id="eye-on" aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="1.8"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
</button>
</div>
</div>

<div class="f4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px 8px">
<label for="remember" style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: #1E293B; min-height: 44px; cursor: pointer">
<input id="remember" name="remember" value="1" type="checkbox" @checked(old('remember')) style="width: 18px; height: 18px; margin: 0; accent-color: #1A73FF">
Намайг сана
</label>
<a href="#" style="font-size: 14px; text-decoration: none; min-height: 44px; display: flex; align-items: center">Нууц үгээ мартсан?</a>
</div>

<button type="submit" id="loginBtn" class="btn f4" style="height: 50px; border: 0; border-radius: 8px; background: #1A73FF; color: #FFFFFF; font-family: inherit; font-size: 17px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 10px; cursor: pointer">
<span id="loginLabel">Нэвтрэх</span>
<svg class="arw" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
</button>
</form>

<div class="fade" style="align-self: stretch; text-align: right; font-size: 13px; color: #5B6B8C">© {{ now()->year }} Диспетчерийн үндэсний төв ТӨХХК</div>
</main>

</div>
<script>
(function(){
  var b=document.getElementById('hd-eye'),p=document.getElementById('password'),
      on=document.getElementById('eye-on'),off=document.getElementById('eye-off');
  b.addEventListener('click',function(){
    var show=p.type==='password';
    p.type=show?'text':'password';
    on.style.display=show?'none':'';
    off.style.display=show?'':'none';
    b.setAttribute('aria-label',show?'Нууц үгийг нуух':'Нууц үгийг харуулах');
  });

  // Давхар илгээхээс сэргийлнэ
  document.getElementById('loginForm').addEventListener('submit',function(){
    document.getElementById('loginBtn').disabled=true;
    document.getElementById('loginLabel').textContent='Нэвтэрч байна...';
  });
})();
</script>
</body>
</html>
