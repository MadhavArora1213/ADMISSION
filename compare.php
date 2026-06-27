<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/college_helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$navBase = '/ADMISSION';

$id1 = $_GET['id1'] ?? '';
$id2 = $_GET['id2'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Compare Colleges – AdmissionSeason</title>
<meta name="description" content="Compare top colleges side-by-side on fees, placements, ratings, rankings and more.">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $navBase ?>/assets/css/style.css?v=<?= time() ?>">
<style>
.cmp{padding:100px 0 60px;min-height:100vh}
.cmp-hero{text-align:center;margin-bottom:36px}
.cmp-hero .nh-badge{margin:0 auto 14px}
.cmp-hero h1{font-size:1.8rem;font-weight:800;color:var(--text);margin-bottom:6px;letter-spacing:-.02em}
.cmp-hero p{color:var(--text2);font-size:.9rem}

/* ── Slots ── */
.cmp-slots{display:grid;grid-template-columns:1fr 60px 1fr;gap:0;align-items:stretch;margin-bottom:32px}
.cmp-slot{background:var(--card);border:2px dashed var(--border);border-radius:16px;min-height:220px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;cursor:pointer;transition:all .3s;position:relative;padding:28px 20px}
.cmp-slot:hover{border-color:var(--secondary);background:rgba(25,55,109,.015)}
.cmp-slot.filled{border-style:solid;border-color:var(--border);cursor:default}
.cmp-slot.filled:hover{border-color:var(--secondary)}
.cmp-slot-icon{width:60px;height:60px;border-radius:16px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--secondary)}
.cmp-slot.filled .cmp-slot-icon{width:52px;height:52px;border-radius:12px}
.cmp-slot h3{font-size:1rem;font-weight:700;color:var(--text);text-align:center;line-height:1.3}
.cmp-slot .loc{font-size:.78rem;color:var(--text3);display:flex;align-items:center;gap:4px}
.cmp-slot .meta{font-size:.7rem;color:var(--text2);display:flex;gap:6px;flex-wrap:wrap;justify-content:center}
.cmp-slot .meta span{background:var(--primary-light);padding:2px 8px;border-radius:4px;font-weight:600}
.cmp-slot .rm{position:absolute;top:10px;right:10px;width:26px;height:26px;border-radius:50%;background:rgba(0,0,0,.05);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.75rem;color:var(--text3);transition:all .2s}
.cmp-slot .rm:hover{background:rgba(239,68,68,.1);color:#ef4444}
.cmp-vs{display:flex;align-items:center;justify-content:center}
.cmp-vs span{width:44px;height:44px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:800;letter-spacing:.02em}

/* ── Apply Button ── */
.cmp-apply{display:flex;justify-content:center;margin-bottom:40px}
.cmp-apply-btn{padding:14px 56px;background:linear-gradient(135deg,var(--secondary),#4f46e5);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;transition:all .3s;font-family:var(--font);display:inline-flex;align-items:center;gap:8px}
.cmp-apply-btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(25,55,109,.3)}
.cmp-apply-btn:disabled{opacity:.35;cursor:not-allowed;transform:none;box-shadow:none}

/* ── Results ── */
.cmp-results{display:none}
.cmp-results.show{display:block}

/* Summary header */
.cmp-summary{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:32px}
.cmp-sum-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:24px;display:flex;gap:16px;align-items:center}
.cmp-sum-card img{width:64px;height:64px;border-radius:14px;object-fit:cover;background:var(--primary-light);flex-shrink:0}
.cmp-sum-info h3{font-size:1rem;font-weight:700;color:var(--text);line-height:1.3;margin-bottom:4px}
.cmp-sum-info .loc{font-size:.78rem;color:var(--text3);display:flex;align-items:center;gap:4px;margin-bottom:6px}
.cmp-sum-tags{display:flex;gap:6px;flex-wrap:wrap}
.cmp-sum-tags span{font-size:.62rem;font-weight:700;padding:2px 8px;border-radius:4px;background:var(--primary-light);color:var(--text2);text-transform:uppercase}

/* Comparison sections */
.cmp-section{margin-bottom:24px;background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden}
.cmp-sec-hdr{padding:16px 24px;background:var(--primary);color:#fff;display:flex;align-items:center;gap:10px;font-weight:700;font-size:.85rem}
.cmp-sec-hdr i{font-size:1.1rem}
.cmp-row{display:grid;grid-template-columns:200px 1fr 1fr;border-bottom:1px solid var(--border)}
.cmp-row:last-child{border-bottom:none}
.cmp-row:hover{background:rgba(25,55,109,.015)}
.cmp-label{padding:14px 20px;font-weight:600;color:var(--text2);font-size:.82rem;display:flex;align-items:center;background:var(--primary-soft)}
.cmp-val{padding:14px 20px;font-size:.88rem;color:var(--text);display:flex;flex-direction:column;justify-content:center}
.cmp-val.winner{color:#059669;font-weight:700}
.cmp-val .big{font-size:1.2rem;font-weight:800;letter-spacing:-.02em}
.cmp-val .sub{font-size:.7rem;color:var(--text3);margin-top:2px}

/* Visual bars */
.cmp-bar{height:8px;background:var(--primary-light);border-radius:4px;overflow:hidden;margin-top:6px}
.cmp-bar-fill{height:100%;border-radius:4px;transition:width .8s cubic-bezier(.22,.68,0,1.1)}
.cmp-bar-fill.blue{background:linear-gradient(90deg,#3b82f6,#6366f1)}
.cmp-bar-fill.green{background:linear-gradient(90deg,#10b981,#059669)}
.cmp-bar-fill.amber{background:linear-gradient(90deg,#f59e0b,#d97706)}

/* Star rating */
.cmp-stars{display:flex;align-items:center;gap:4px;margin-top:4px}
.cmp-stars i{color:#f59e0b;font-size:.85rem}
.cmp-stars span{font-size:.72rem;color:var(--text3)}

/* Verdict */
.cmp-verdict{background:linear-gradient(135deg,rgba(25,55,109,.04),rgba(79,70,229,.04));border:1px solid var(--border);border-radius:16px;padding:28px;text-align:center;margin-bottom:32px}
.cmp-verdict h3{font-size:1.1rem;font-weight:800;color:var(--text);margin-bottom:8px}
.cmp-verdict p{color:var(--text2);font-size:.88rem;max-width:600px;margin:0 auto}
.cmp-verdict .winner-tag{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#059669,#10b981);color:#fff;padding:8px 20px;border-radius:10px;font-weight:700;font-size:.9rem;margin-top:12px}

/* Modal */
.cmp-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.cmp-modal.open{display:flex}
.cmp-modal-box{background:#fff;border-radius:20px;width:92%;max-width:500px;max-height:80vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,.25);animation:modalIn .25s ease}
@keyframes modalIn{from{opacity:0;transform:scale(.96) translateY(10px)}to{opacity:1;transform:none}}
.cmp-modal-hdr{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.cmp-modal-hdr h3{font-size:1.05rem;font-weight:700;color:var(--text)}
.cmp-modal-close{width:32px;height:32px;border-radius:8px;border:none;background:rgba(0,0,0,.05);cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--text3);transition:all .2s}
.cmp-modal-close:hover{background:rgba(239,68,68,.1);color:#ef4444}
.cmp-modal-body{padding:20px 24px;overflow-y:auto;flex:1}
.cmp-search{width:100%;padding:12px 16px 12px 40px;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;font-family:var(--font2);background:var(--background);transition:border-color .2s}
.cmp-search:focus{outline:none;border-color:var(--secondary)}
.cmp-swrap{position:relative;margin-bottom:16px}
.cmp-swrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:1rem}
.cmp-sresults{display:flex;flex-direction:column;gap:6px}
.cmp-sitem{padding:12px 14px;border:1px solid var(--border);border-radius:10px;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:12px}
.cmp-sitem:hover{border-color:var(--secondary);background:rgba(25,55,109,.03)}
.cmp-sitem.sel{border-color:var(--secondary);background:rgba(25,55,109,.06);box-shadow:0 0 0 2px rgba(25,55,109,.1)}
.cmp-sitem .si-info{flex:1;min-width:0}
.cmp-sitem .si-name{font-size:.85rem;font-weight:700;color:var(--text)}
.cmp-sitem .si-meta{font-size:.7rem;color:var(--text3);margin-top:2px}
.cmp-modal-ftr{padding:14px 24px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
.cmp-modal-ftr button{padding:10px 24px;border-radius:10px;font-size:.85rem;font-weight:600;cursor:pointer;border:none;font-family:var(--font);transition:all .2s}
.cmp-btn-cancel{background:var(--primary-light);color:var(--text2)}
.cmp-btn-cancel:hover{background:rgba(15,23,42,.1)}
.cmp-btn-ok{background:linear-gradient(135deg,var(--secondary),#4f46e5);color:#fff}
.cmp-btn-ok:hover{opacity:.9}
.cmp-btn-ok:disabled{opacity:.35;cursor:not-allowed}

/* Empty */
.cmp-empty{text-align:center;padding:60px 20px}
.cmp-empty i{font-size:3.5rem;color:var(--border);margin-bottom:14px;display:block}
.cmp-empty h2{font-size:1.2rem;font-weight:700;color:var(--text);margin-bottom:6px}
.cmp-empty p{color:var(--text2);font-size:.88rem}

/* Loading */
.cmp-loading{text-align:center;padding:60px}
.cmp-loading i{font-size:2rem;color:var(--secondary);animation:spin 1s linear infinite;display:block;margin-bottom:12px}
@keyframes spin{to{transform:rotate(360deg)}}

/* Responsive */
@media(max-width:768px){
  .cmp-slots{grid-template-columns:1fr;gap:12px}
  .cmp-vs{display:none}
  .cmp-summary{grid-template-columns:1fr}
  .cmp-row{grid-template-columns:120px 1fr 1fr}
  .cmp-label{padding:10px 14px;font-size:.75rem}
  .cmp-val{padding:10px 14px;font-size:.82rem}
  .cmp-sug-card{width:160px}
  .cmp-sum-card img{width:48px;height:48px}
}
@media(max-width:480px){
  .cmp-row{grid-template-columns:1fr}
  .cmp-row .cmp-label{background:var(--primary-light);font-size:.72rem;padding:8px 14px}
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="cmp">
  <div class="container">

    <div class="cmp-hero">
      <div class="nh-badge"><i class="ph-fill ph-scales"></i> Compare</div>
      <h1>Compare Colleges Side by Side</h1>
      <p>Select two colleges to compare fees, placements, ratings, rankings and more</p>
    </div>

    <!-- Slots -->
    <div class="cmp-slots">
      <div class="cmp-slot" id="slot1" onclick="openModal(1)">
        <div class="cmp-slot-icon"><i class="ph ph-plus"></i></div>
        <h3>Add College</h3>
        <span class="loc">Click to select first college</span>
      </div>
      <div class="cmp-vs"><span>VS</span></div>
      <div class="cmp-slot" id="slot2" onclick="openModal(2)">
        <div class="cmp-slot-icon"><i class="ph ph-plus"></i></div>
        <h3>Add College</h3>
        <span class="loc">Click to select second college</span>
      </div>
    </div>

    <div class="cmp-apply">
      <button class="cmp-apply-btn" id="applyBtn" disabled onclick="loadComparison()">
        <i class="ph ph-scales"></i> Compare Now
      </button>
    </div>

    <!-- Results -->
    <div class="cmp-results" id="results"></div>

  </div>
</div>

<!-- Modal -->
<div class="cmp-modal" id="modal">
  <div class="cmp-modal-box">
    <div class="cmp-modal-hdr">
      <h3 id="modalTitle">Select College</h3>
      <button class="cmp-modal-close" onclick="closeModal()"><i class="ph ph-x"></i></button>
    </div>
    <div class="cmp-modal-body">
      <div class="cmp-swrap">
        <i class="ph ph-magnifying-glass"></i>
        <input type="text" class="cmp-search" id="mSearch" placeholder="Search college name or city…" oninput="doSearch(this.value)">
      </div>
      <div id="sugLabel" class="cmp-suggested-label">Search results</div>
      <div class="cmp-sresults" id="sResults"></div>
    </div>
    <div class="cmp-modal-ftr">
      <button class="cmp-btn-cancel" onclick="closeModal()">Cancel</button>
      <button class="cmp-btn-ok" id="mOk" disabled onclick="applyPick()">Apply</button>
    </div>
  </div>
</div>

<script>
const B='<?=$navBase?>';
let slot=null,pick=null,col={1:null,2:null},timer=null;

function openModal(s){
  slot=s;pick=null;
  document.getElementById('modal').classList.add('open');
  document.getElementById('modalTitle').textContent='Select College For Slot '+s;
  document.getElementById('mSearch').value='';
  document.getElementById('mOk').disabled=true;
  document.getElementById('sugLabel').textContent='Search results';
  document.getElementById('sResults').innerHTML='<div style="text-align:center;padding:24px;color:var(--text3);font-size:.85rem">Type to search colleges</div>';
  setTimeout(()=>document.getElementById('mSearch').focus(),100);
}

function closeModal(){document.getElementById('modal').classList.remove('open');pick=null}

function doSearch(q){
  clearTimeout(timer);
  if(q.length<1){document.getElementById('sResults').innerHTML='<div style="text-align:center;padding:24px;color:var(--text3);font-size:.85rem">Type to search colleges</div>';document.getElementById('sugLabel').textContent='Search results';return;}
  timer=setTimeout(()=>{
    fetch(`${B}/api/college_search.php?q=${encodeURIComponent(q)}`)
    .then(r=>r.json()).then(data=>{
      let h='';
      data.forEach(c=>{
        const oid=col[1]?.id||col[2]?.id;
        if(c.id===oid)return;
        h+=`<div class="cmp-sitem" data-id="${c.id}" data-name="${esc(c.name)}" data-city="${esc(c.city_name||'')}" data-naac="${esc(c.naac_grade||'')}" data-course-count="${c.course_count||0}">
          <div class="si-info"><div class="si-name">${esc(c.name)}</div>
          <div class="si-meta">${esc(c.city_name||'')}${c.naac_grade?' · NAAC '+esc(c.naac_grade):''}${c.ranking_nirf?' · NIRF #'+c.ranking_nirf:''}</div></div></div>`;
      });
      if(!data.length) h='<div style="text-align:center;padding:24px;color:var(--text3);font-size:.85rem">No colleges found</div>';
      document.getElementById('sResults').innerHTML=h;
      document.getElementById('sugLabel').textContent='Search results';
      bindSitems();
    });
  },250);
}

function pickItem(el){
  document.querySelectorAll('.cmp-sitem').forEach(e=>e.classList.remove('sel'));
  el.classList.add('sel');
  pick={id:el.dataset.id,name:el.dataset.name,city:el.dataset.city,naac:el.dataset.naac};
  document.getElementById('mOk').disabled=false;
}

function bindSitems(){
  document.querySelectorAll('.cmp-sitem').forEach(el=>{
    el.onclick=function(){pickItem(this)};
  });
}

function applyPick(){
  if(!pick)return;
  col[slot]=pick;
  updateSlots();
  closeModal();
}

function quickPick(id,name,city,naac,logo){
  const other=col[1]?.id===id?1:col[2]?.id===id?2:null;
  if(other)return;
  const s=col[1]?2:1;
  col[s]={id,name,city,naac,logo};
  updateSlots();
}

function removeSlot(s){
  col[s]=null;
  updateSlots();
  document.getElementById('results').classList.remove('show');
  document.getElementById('results').innerHTML='';
}

function updateSlots(){
  [1,2].forEach(s=>{
    const el=document.getElementById('slot'+s);
    const c=col[s];
    if(c){
      el.classList.add('filled');
      el.innerHTML=`<button class="rm" onclick="event.stopPropagation();removeSlot(${s})"><i class="ph ph-x"></i></button>
        <div class="cmp-slot-icon" style="background:linear-gradient(135deg,var(--secondary),#4f46e5);color:#fff"><i class="ph ph-buildings"></i></div>
        <h3>${esc(c.name)}</h3>
        ${c.city?`<div class="loc"><i class="ph ph-map-pin"></i> ${esc(c.city)}</div>`:''}
        <div class="meta">${c.naac?`<span>NAAC ${esc(c.naac)}</span>`:''}</div>`;
      el.onclick=null;
    }else{
      el.classList.remove('filled');
      el.innerHTML=`<div class="cmp-slot-icon"><i class="ph ph-plus"></i></div>
        <h3>Add College</h3>
        <span class="loc">Click to select</span>`;
      el.onclick=()=>openModal(s);
    }
  });
  document.getElementById('applyBtn').disabled=!(col[1]&&col[2]);
}

function loadComparison(){
  if(!col[1]||!col[2])return;
  const btn=document.getElementById('applyBtn');
  btn.innerHTML='<i class="ph ph-spinner"></i> Comparing…';btn.disabled=true;
  document.getElementById('results').innerHTML='<div class="cmp-loading"><i class="ph ph-spinner"></i> Loading comparison data…</div>';
  document.getElementById('results').classList.add('show');

  fetch(`${B}/api/college_compare.php?id1=${col[1].id}&id2=${col[2].id}`)
  .then(r=>r.json()).then(d=>{
    if(d.error){alert(d.error);btn.innerHTML='<i class="ph ph-scales"></i> Compare Now';btn.disabled=false;return;}
    render(d.college1,d.college2);
    btn.innerHTML='<i class="ph ph-scales"></i> Compare Now';btn.disabled=false;
  }).catch(()=>{btn.innerHTML='<i class="ph ph-scales"></i> Compare Now';btn.disabled=false;});
}

function render(a,b){
  const imgA=a.cover_image_url||a.logo_url||'';
  const imgB=b.cover_image_url||b.logo_url||'';
  const locA=[a.city_name,a.state_name].filter(Boolean).join(', ');
  const locB=[b.city_name,b.state_name].filter(Boolean).join(', ');
  const rA=parseFloat(a.overall_rating_avg)||0;
  const rB=parseFloat(b.overall_rating_avg)||0;
  const rsA=a.review_stats||{};
  const rsB=b.review_stats||{};

  function fmt(n,suffix=''){return n?'₹'+Number(n).toLocaleString('en-IN',{minimumFractionDigits:1,maximumFractionDigits:1})+suffix:'—'}
  function fmtI(n){return n?Number(n).toLocaleString('en-IN'):'—'}
  function bar(v,mx,cls){const p=mx>0?Math.min(v/mx*100,100):0;return `<div class="cmp-bar"><div class="cmp-bar-fill ${cls}" style="width:${p}%"></div></div>`}
  function w(va,vb,lower){if(!va||!vb)return'';const na=parseFloat(String(va).replace(/[^0-9.]/g,''))||0;const nb=parseFloat(String(vb).replace(/[^0-9.]/g,''))||0;if(lower?na<nb:na>nb)return'winner';return''}

  // Verdict
  let scores={a:0,b:0};
  const checks=[[rA,rB,0],[parseFloat(a.avg_package)||0,parseFloat(b.avg_package)||0,1],[parseFloat(a.highest_package)||0,parseFloat(b.highest_package)||0,1],[parseFloat(c1_placement_pct(a)),parseFloat(c1_placement_pct(b)),1],[parseInt(a.total_students)||0,parseInt(b.total_students)||0,0]];
  function c1_placement_pct(c){return c.placement_pct||0}
  checks.forEach(([va,vb,hig])=>{if(va&&vb){if(hig?(va>vb?scores.a++:va<vb?scores.b++:0):(va>vb?scores.a++:va<vb?scores.b++:0))}});
  const overallWinner=scores.a>scores.b?a:scores.b>scores.a?b:null;

  let h=`<div class="cmp-summary">
    <div class="cmp-sum-card">
      ${imgA?`<img src="${B}/${imgA}" alt="">`:`<div style="width:64px;height:64px;border-radius:14px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--secondary);flex-shrink:0"><i class="ph ph-buildings"></i></div>`}
      <div class="cmp-sum-info"><h3>${esc(a.name)}</h3>
      <div class="loc"><i class="ph ph-map-pin"></i> ${esc(locA)}</div>
      <div class="cmp-sum-tags">${a.college_type?`<span>${esc(a.college_type)}</span>`:''}${a.naac_grade?`<span>NAAC ${esc(a.naac_grade)}</span>`:''}${a.ranking_nirf?`<span>NIRF #${a.ranking_nirf}</span>`:''}</div></div>
    </div>
    <div class="cmp-sum-card">
      ${imgB?`<img src="${B}/${imgB}" alt="">`:`<div style="width:64px;height:64px;border-radius:14px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--secondary);flex-shrink:0"><i class="ph ph-buildings"></i></div>`}
      <div class="cmp-sum-info"><h3>${esc(b.name)}</h3>
      <div class="loc"><i class="ph ph-map-pin"></i> ${esc(locB)}</div>
      <div class="cmp-sum-tags">${b.college_type?`<span>${esc(b.college_type)}</span>`:''}${b.naac_grade?`<span>NAAC ${esc(b.naac_grade)}</span>`:''}${b.ranking_nirf?`<span>NIRF #${b.ranking_nirf}</span>`:''}</div></div>
    </div>
  </div>`;

  // Verdict card
  if(overallWinner){
    h+=`<div class="cmp-verdict">
      <h3>Overall Comparison Result</h3>
      <p>Based on ratings, placements, rankings and student reviews</p>
      <div class="winner-tag"><i class="ph-fill ph-trophy"></i> ${esc(overallWinner.name)} wins</div>
    </div>`;
  }

  // Section: Institute Info
  h+=sec('Institute Information','ph-info',[
    row('Established Year',a.established_year||'—',b.established_year||'—'),
    row('Ownership',ucfirst(a.college_type||'—'),ucfirst(b.college_type||'—')),
    row('NAAC Grade',a.naac_grade?'NAAC '+a.naac_grade:'—',b.naac_grade?'NAAC '+b.naac_grade:'—',true),
    row('Total Students',a.total_students?fmtI(a.total_students)+'+':'—',b.total_students?fmtI(b.total_students)+'+':'—',true),
    row('Campus Area',a.campus_area_acres?a.campus_area_acres+' acres':'—',b.campus_area_acres?b.campus_area_acres+' acres':'—'),
    row('Accreditations',(a.facilities||[]).join(', ')||'—',(b.facilities||[]).join(', ')||'—'),
  ]);

  // Section: Ratings
  h+=sec('Ratings & Reviews','ph-star',[
    rowRating('Overall Rating',rA,rsA.total_reviews,rB,rsB.total_reviews),
    rowBar('Placements',rsA.avg_placements,rsB.avg_placements),
    rowBar('Infrastructure',rsA.avg_infra,rsB.avg_infra),
    rowBar('Faculty',rsA.avg_faculty,rsB.avg_faculty),
    rowBar('Campus Life',rsA.avg_social,rsB.avg_social),
    rowBar('Value for Money',rsA.avg_value,rsB.avg_value),
  ]);

  // Section: Rankings
  h+=sec('Rankings','ph-ranking',[
    row('NIRF Rank',a.ranking_nirf?'#'+a.ranking_nirf:'—',b.ranking_nirf?'#'+b.ranking_nirf:'—',true,true),
    row('QS World Rank',a.ranking_qs?'#'+a.ranking_qs:'—',b.ranking_qs?'#'+b.ranking_qs:'—',true,true),
    row('Times Rank',a.ranking_times?'#'+a.ranking_times:'—',b.ranking_times?'#'+b.ranking_times:'—',true,true),
  ]);

  // Section: Placements
  const maxPkg=Math.max(a.avg_package||0,b.avg_package||0);
  const maxHPkg=Math.max(a.highest_package||0,b.highest_package||0);
  h+=sec('Placements','ph-briefcase',[
    rowBar('Avg Package',a.avg_package,b.avg_package,'green',' LPA'),
    rowBar('Highest Package',a.highest_package,b.highest_package,'amber',' LPA'),
    row('Median Package',fmt(a.median_package,' LPA'),fmt(b.median_package,' LPA')),
    row('% Batch Placed',a.placement_pct?Math.round(a.placement_pct)+'%':'—',b.placement_pct?Math.round(b.placement_pct)+'%':'—',true),
    row('Students Placed',a.total_placed?fmtI(a.total_placed):'—',b.total_placed?fmtI(b.total_placed):'—'),
    row('Top Recruiters',a.top_recruiters||'—',b.top_recruiters||'—'),
  ]);

  // Section: Fees
  h+=sec('Fees','ph-money',[
    row('Min Annual Fee',a.min_fee?'₹'+fmtI(a.min_fee):'—',b.min_fee?'₹'+fmtI(b.min_fee):'—',true,true),
    row('Max Total Fee',a.max_total_fee?'₹'+fmtI(a.max_total_fee):'—',b.max_total_fee?'₹'+fmtI(b.max_total_fee):'—',true,true),
  ]);

  // Section: Courses
  const maxC=Math.max((a.courses||[]).length,(b.courses||[]).length,0);
  let courseRows=[];
  for(let i=0;i<Math.min(maxC,8);i++){
    const ca=(a.courses||[])[i];
    const cb=(b.courses||[])[i];
    courseRows.push(row(
      i===0?'Available Courses':'',
      ca?`${esc(ca.course_name)} <span style="color:var(--text3);font-size:.72rem">· ${ca.course_level} · ${ca.duration_years||'?'}y</span>`:'—',
      cb?`${esc(cb.course_name)} <span style="color:var(--text3);font-size:.72rem">· ${cb.course_level} · ${cb.duration_years||'?'}y</span>`:'—'
    ));
  }
  if(courseRows.length) h+=sec('Courses','ph-graduation-cap',courseRows);

  // Section: Admission
  h+=sec('Admission','ph-file-text',[
    row('Accepted Exams',(a.accepted_exams||[]).join(', ')||'—',(b.accepted_exams||[]).join(', ')||'—'),
  ]);

  document.getElementById('results').innerHTML=h;
  document.getElementById('results').classList.add('show');
  document.getElementById('results').scrollIntoView({behavior:'smooth',block:'start'});
}

function sec(title,icon,rows){
  return `<div class="cmp-section"><div class="cmp-sec-hdr"><i class="ph ${icon}"></i> ${title}</div>${rows.join('')}</div>`;
}
function row(label,a,b,lower,betterHigh){
  const cls=betterHigh===true?'':w(a,b,lower||false);
  return `<div class="cmp-row"><div class="cmp-label">${label}</div><div class="cmp-val ${cls==='winner'?'winner':''}">${a}</div><div class="cmp-val ${cls==='winner'&&b!==a?'winner':''}">${b}</div></div>`;
}
function rowRating(label,rA,nA,rB,nB){
  return `<div class="cmp-row"><div class="cmp-label">${label}</div>
    <div class="cmp-val ${w(rA,rB)?'winner':''}"><span class="big">${rA?rA+'/5':'—'}</span>${nA?`<span class="sub">${nA} verified reviews</span>`:''}${rA?bar(rA,5,'blue'):''}</div>
    <div class="cmp-val ${w(rB,rA)?'winner':''}"><span class="big">${rB?rB+'/5':'—'}</span>${nB?`<span class="sub">${nB} verified reviews</span>`:''}${rB?bar(rB,5,'blue'):''}</div></div>`;
}
function rowBar(label,vA,vB,cls,sfx){
  cls=cls||'blue';sfx=sfx||'';
  const mx=Math.max(vA||0,vB||0)||1;
  return `<div class="cmp-row"><div class="cmp-label">${label}</div>
    <div class="cmp-val ${w(vA,vB)?'winner':''}"><span class="big">${vA?vA+sfx:'—'}</span>${bar(vA,mx,cls)}</div>
    <div class="cmp-val ${w(vB,vA)?'winner':''}"><span class="big">${vB?vB+sfx:'—'}</span>${bar(vB,mx,cls)}</div></div>`;
}

function esc(s){const d=document.createElement('div');d.textContent=s;return d.innerHTML}
function ucfirst(s){return s.charAt(0).toUpperCase()+s.slice(1)}

<?php if($id1&&$id2):?>
document.addEventListener('DOMContentLoaded',()=>{
  fetch(`${B}/api/college_compare.php?id1=<?=htmlspecialchars($id1)?>&id2=<?=htmlspecialchars($id2)?>`).then(r=>r.json()).then(d=>{
    if(d.college1&&d.college2){
      col[1]={id:d.college1.id,name:d.college1.name,city:d.college1.city_name,naac:d.college1.naac_grade};
      col[2]={id:d.college2.id,name:d.college2.name,city:d.college2.city_name,naac:d.college2.naac_grade};
      updateSlots();render(d.college1,d.college2);
    }
  });
});
<?php endif;?>

document.getElementById('modal').addEventListener('click',function(e){if(e.target===this)closeModal()});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeModal()});
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>
