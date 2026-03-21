@extends('layouts.sidebar')

@section('content')
<style>
  .card { 
    background:#fff;
    border-radius:12px;
    box-shadow:0 8px 24px rgba(0,0,0,.06);
    padding:1.25rem 1.25rem 
  }
  
  .btn { 
    border:0;
    border-radius:10px;
    padding:.55rem .9rem;
    font-weight:600;
    cursor:pointer 
  }

  .btn:disabled {
    opacity:.6;
    cursor:not-allowed
  }
  
  .btn-primary {
    background:#2563eb;
    color:#fff
  }

  .btn-secondary {
    background:#e5e7eb;
    color:#111827
  }

  .btn-ghost {
    background:transparent;
    color:#374151
  }

  .btn-success {
    background:#16a34a;
    color:#fff
  }

  .btn-danger {
    background:#dc2626;
    color:#fff
  }

  .chip {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:28px;
    height:28px;
    border-radius:8px;
    font-size:.85rem;
    background:#e2e8f0;
    color:#0f172a
  }

  .chip.active {
    background:#16a34a;
    color:#fff
  }

  .muted {
    color:#6b7280
  }

  .progress-outer {
    height:8px;
    width:100%;
    background:#f1f5f9;
    border-radius:999px;
    overflow:hidden
  }

  .progress-inner {
    height:100%;
    background:#7c3aed;
    width:0%
  }

  .modal-backdrop {
    position:fixed;
    inset:0;
    background:rgba(2,6,23,.45);
    backdrop-filter:blur(2px);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:1000;
    padding:1rem
  }
  
  .modal {
    background:#fff;
    border-radius:14px;
    max-width:560px;
    width:100%;
    box-shadow:0 20px 50px rgba(0,0,0,.18)
  }

  .modal .head {
    padding:1rem 1.25rem;
    border-bottom:1px solid #eef2f7;
    font-weight:700
  }

  .modal .body {
    padding:1rem 1.25rem
  }

  .modal .foot {
    padding:1rem 1.25rem;
    border-top:1px solid #eef2f7;
    display:flex;
    gap:.5rem;
    justify-content:flex-end
  }

  .answer {
    display:flex;
    gap:.65rem;
    align-items:flex-start;
    border:1px solid #e5e7eb;
    border-radius:10px;
    padding:.65rem .75rem;
    margin:.35rem 0
  }

  .answer input {
    margin-top:.2rem
  }

  .answer.correct {
    border-color:#16a34a;
    background:#f0fdf4
  }

  .answer.wrong {
    border-color:#dc2626;
    background:#fef2f2
    }
</style>


<div style="max-width:880px;margin:0 auto;padding:0 1.25rem 0.5rem;">
  <a href="{{ route('curators.trivia.all') }}" class="btn btn-ghost"
     style="display:inline-flex;gap:.4rem;align-items:center;text-decoration:none">
    ← Back to Question Bank
  </a>
</div>

<div style="max-width:880px;margin:0 auto;padding:1rem 1.25rem;">
  <h2 style="font-size:1.6rem;font-weight:800;margin:.2rem 0 1rem;">
    Quiz: {{ $landmark['name'] }} <span class="muted" style="font-size:1rem;">({{ $landmark['id'] }})</span>
  </h2>

  <div class="card">
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;justify-content:space-between;">
      <div style="flex:1">
        <div class="progress-outer">
          <div id="progressBar" class="progress-inner"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:.4rem;font-size:.9rem">
          <span class="muted">Question <span id="qIndex">1</span>/<span id="qTotal">0</span></span>
          <span class="muted">Time left: <strong id="timer">10:00</strong></span>
        </div>
      </div>
    </div>

    <div id="questionWrap" style="margin-top:.25rem"></div>

    <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
      <button id="prevBtn" class="btn btn-secondary" disabled>Previous</button>
      <button id="nextBtn" class="btn btn-secondary" disabled>Next</button>
      <button id="submitBtn" class="btn btn-primary" disabled>Submit</button>
    </div>

    <div id="pager" style="display:flex;gap:.5rem;margin-top:1rem;flex-wrap:wrap"></div>
  </div>
</div>

{{-- Submit confirm modal --}}
<div id="submitModal" class="modal-backdrop">
  <div class="modal">
    <div class="head">Submit your answers?</div>
    <div class="body">
      <p>You can still review your answers. After submitting, a result screen will appear. Do you want to proceed?</p>
    </div>
    <div class="foot">
      <button class="btn btn-secondary" onclick="closeSubmitModal()">Cancel</button>
      <button class="btn btn-success" onclick="confirmSubmit()">Submit</button>
    </div>
  </div>
</div>

{{-- Results modal (with optional answer key) --}}
<div id="resultModal" class="modal-backdrop">
  <div class="modal">
    <div class="head">Quiz Results</div>
    <div class="body">
      <div id="scoreLine" style="font-weight:700;font-size:1.15rem;margin-bottom:.25rem"></div>
      <div id="summaryLine" class="muted" style="margin-bottom:.75rem"></div>

      <button id="toggleKeyBtn" class="btn btn-ghost" style="margin:.25rem 0 .75rem 0;" onclick="toggleAnswerKey()">👁 Show Answer Key</button>

      <div id="answerKeyWrap" style="display:none;max-height:52vh;overflow:auto;border-top:1px dashed #e5e7eb;padding-top:.75rem"></div>
    </div>
    <div class="foot">
      {{-- Back to Question Bank inside results modal --}}
      <a href="{{ route('curators.trivia.all') }}" class="btn btn-ghost">Question Bank</a>
      <button class="btn btn-secondary" onclick="closeResultModal()">Close</button>
      <button class="btn btn-danger" onclick="retake()">Retake</button>
    </div>
  </div>
</div>

<script>
  const LANDMARK_ID = @json($landmark['id']);
  const FETCH_URL   = @json(route('quiz.fetch'));
  const KEY_URL     = @json(route('quiz.key')); 

  
  let questions = [];     
  let answers   = {};     
  let idx       = 0;
  let timerSec  = 10 * 60; 
  let timerId   = null;
  let answerKey = null;   

  
  const qWrap      = document.getElementById('questionWrap');
  const qIndex     = document.getElementById('qIndex');
  const qTotal     = document.getElementById('qTotal');
  const progress   = document.getElementById('progressBar');
  const pager      = document.getElementById('pager');
  const prevBtn    = document.getElementById('prevBtn');
  const nextBtn    = document.getElementById('nextBtn');
  const submitBtn  = document.getElementById('submitBtn');
  const timerEl    = document.getElementById('timer');

  const submitModal  = document.getElementById('submitModal');
  const resultModal  = document.getElementById('resultModal');
  const scoreLine    = document.getElementById('scoreLine');
  const summaryLine  = document.getElementById('summaryLine');
  const answerKeyWrap= document.getElementById('answerKeyWrap');
  const toggleKeyBtn = document.getElementById('toggleKeyBtn');

  
  loadQuiz();

  async function loadQuiz(){
    try {
      const res = await fetch(`${FETCH_URL}?landmark_id=${encodeURIComponent(LANDMARK_ID)}&limit=10`);
      const data = await res.json();
      questions = data.items || [];
      qTotal.textContent = questions.length;
      pager.innerHTML = '';
      answers = {};
      idx = 0;
      renderPager();
      renderQuestion();
      updateNavBtns();
      submitBtn.disabled = questions.length === 0;

      startTimer();
    } catch(e){
      qWrap.innerHTML = `<p class="muted">Failed to load quiz.</p>`;
    }
  }

  function startTimer(){
    clearInterval(timerId);
    tick(); 
    timerId = setInterval(()=>{
      timerSec--;
      if (timerSec <= 0){
        clearInterval(timerId);
        openSubmitModal(); 
      }
      tick();
    }, 1000);
  }

  function tick(){
    const m = Math.floor(timerSec/60);
    const s = (timerSec%60).toString().padStart(2,'0');
    timerEl.textContent = `${m}:${s}`;
    const pct = (Object.keys(answers).length / Math.max(1, questions.length)) * 100;
    progress.style.width = pct + '%';
  }

  function renderPager(){
    questions.forEach((q, i)=>{
      const b = document.createElement('button');
      b.className = 'chip' + (i===idx ? ' active' : '');
      b.textContent = (i+1);
      b.onclick = ()=>{ idx = i; renderQuestion(); updateNavBtns(); highlightPager(); };
      pager.appendChild(b);
    });
  }

  function highlightPager(){
    Array.from(pager.children).forEach((el,i)=>{
      el.classList.toggle('active', i===idx);
    });
  }

  function renderQuestion(){
    if (!questions[idx]) return;

    const q = questions[idx];
    qIndex.textContent = (idx+1);

    let html = `
      <div style="font-weight:700;margin-bottom:.6rem">${idx+1}. ${escapeHtml(q.question || '')}</div>
      <div>
    `;
    (q.choices || []).forEach((c, i)=>{
      const id = `opt_${idx}_${i}`;
      const checked = (answers[q.id] === c) ? 'checked' : '';
      html += `
        <label class="answer">
          <input type="radio" name="q_${idx}" id="${id}" value="${escapeHtml(c)}" ${checked} onchange="selectAnswer('${q.id}', this.value)">
          <span>${escapeHtml(c)}</span>
        </label>
      `;
    });
    html += `</div>`;

    qWrap.innerHTML = html;
    highlightPager();
  }

  function selectAnswer(qid, val){
    answers[qid] = val;
    tick();
  }

  function updateNavBtns(){
    prevBtn.disabled = (idx === 0);
    nextBtn.disabled = (idx >= questions.length - 1);
    submitBtn.disabled = (Object.keys(answers).length === 0 || questions.length === 0);
  }

  prevBtn.onclick = ()=>{ if (idx>0){ idx--; renderQuestion(); updateNavBtns(); } };
  nextBtn.onclick = ()=>{ if (idx<questions.length-1){ idx++; renderQuestion(); updateNavBtns(); } };
  submitBtn.onclick = openSubmitModal;

  
  function openSubmitModal(){ submitModal.style.display='flex'; }
  function closeSubmitModal(){ submitModal.style.display='none'; }
  function openResultModal(){ resultModal.style.display='flex'; }
  function closeResultModal(){ resultModal.style.display='none'; }

  async function confirmSubmit(){
    closeSubmitModal();
    clearInterval(timerId);

    
    try{
      const res = await fetch(`${KEY_URL}?landmark_id=${encodeURIComponent(LANDMARK_ID)}`);
      const data = await res.json();
      answerKey = data.items || [];
      showResults();
    }catch(e){
      scoreLine.textContent = 'Could not grade your quiz.';
      summaryLine.textContent = '';
      answerKeyWrap.innerHTML = '';
      openResultModal();
      return;
    }
  }

  function showResults(){
    const keyMap = {};
    (answerKey||[]).forEach(k => keyMap[k.id] = k.correct_answer);

    let correct = 0, blank = 0;
    const rows = [];

    questions.forEach((q, i)=>{
      const ua = answers[q.id] ?? null;
      const ca = keyMap[q.id] ?? '';
      const isCorrect = ua && ua === ca;
      if (!ua) blank++;
      if (isCorrect) correct++;

      rows.push(`
        <div style="margin-bottom:.85rem">
          <div style="font-weight:700">${i+1}. ${escapeHtml(q.question || '')}</div>
          <div style="margin-top:.35rem">
            ${(q.choices||[]).map(c => {
              const isUser   = ua === c;
              const isRight  = ca === c;
              const classes = isRight ? 'answer correct' : (isUser && !isRight ? 'answer wrong' : 'answer');
              const mark = isRight ? '✔' : (isUser && !isRight ? '✖' : '');
              return `
                <div class="${classes}">
                  <span style="min-width:1.2rem">${mark}</span>
                  <span>${escapeHtml(c)}</span>
                  ${isUser ? `<span class="muted" style="margin-left:auto">your pick</span>` : ``}
                </div>
              `;
            }).join('')}
          </div>
        </div>
      `);
    });

    const total = questions.length;
    const wrong = total - correct - blank;

    scoreLine.textContent  = `Score: ${correct} / ${total}`;
    summaryLine.textContent= `Correct: ${correct} • Wrong: ${wrong} • Blank: ${blank}`;
    answerKeyWrap.innerHTML= rows.join('');

    
    document.getElementById('answerKeyWrap').style.display = 'none';
    toggleKeyBtn.textContent = '👁 Show Answer Key';

    openResultModal();
  }

  function toggleAnswerKey(){
    const el = document.getElementById('answerKeyWrap');
    const visible = el.style.display !== 'none';
    el.style.display = visible ? 'none' : 'block';
    toggleKeyBtn.textContent = visible ? '👁 Show Answer Key' : '🙈 Hide Answer Key';
  }

  function retake(){
    
    closeResultModal();
    timerSec = 10*60;
    answerKey = null;
    questions = [];
    answers   = {};
    qWrap.innerHTML = '';
    pager.innerHTML = '';
    loadQuiz();
  }

  
  function escapeHtml(s){
    return (s||'').toString()
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  
  [submitModal, resultModal].forEach(backdrop=>{
    backdrop.addEventListener('click', (e)=>{
      if (e.target === backdrop){
        backdrop.style.display = 'none';
      }
    });
  });
</script>
@endsection
