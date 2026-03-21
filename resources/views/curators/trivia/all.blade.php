@extends('layouts.sidebar')

@section('content')
<style>
    body { 
    background:#f5f3ff; 
    font-family: 'Inter', sans-serif; 
    }
    
    .wrap {
      max-width:1100px;
      margin:0 auto;
      padding:1rem
    }
    
    .h1 {
      font-size:1.8rem;
      font-weight:800;
      color:#4c1d95;
      margin:0 0 .75rem
    }
    
    .muted {
      color:#6b7280
    }

    /* buttons */
    .btn {
      display:inline-flex;
      align-items:center;
      justify-content:center;
      font-weight:700;
      font-size:.95rem;
      border-radius:10px;
      padding:.55rem .85rem;
      border:none;
      cursor:pointer;
      transition:.2s
    }
    
    .btn-add {
      background:#7e22ce;
      color:#fff
    }
    
    .btn-add:hover { 
      background:#6b21a8
    }
    
    .btn-edit {
      background:#fbbf24;
      color:#1f2937
    }
    
    .btn-edit:hover {
      background:#f59e0b
    }
    
    .btn-del {
      background:#ef4444;
      color:#fff
    }
    
    .btn-del:hover {
      background:#dc2626
    }
    
    .btn-ghost {
      background:#eef2ff;
      color:#3730a3;
      border:1px solid #c7d2fe
    }

    /* alerts */
    .notice {
      padding:.75rem 1rem;
      border-radius:10px;
      margin:.75rem 0
    }
    
    .ok {
      background:#d1fae5;
      color:#065f46
    }
    
    .err {
      background:#fee2e2;
      color:#991b1b
    }

    /* cards (non-tabular) */
    .cards {
      display:grid;
      grid-template-columns:repeat(3,minmax(0,1fr));
      gap:12px;
      margin-top:.5rem
    }
    
    .card {
      background:#fff;
      border-radius:12px;
      box-shadow:0 8px 24px rgba(17,24,39,.06);
      padding:14px;
      display:flex;
      flex-direction:column;
      gap:10px
    }

    .landmark {
      display:flex;
      flex-direction:column;
      align-items:flex-start;
      gap:.35rem;
      font-weight:700;
      color:#4c1d95
    }

    .pill {
      display:inline-flex;
      align-items:center;
      gap:.35rem;
      background:#eef2ff;
      border:1px solid #c7d2fe;
      color:#3730a3;
      border-radius:999px;
      padding:.2rem .6rem;
      font-size:.8rem;
      font-weight:700
    }
    
    .qtext {
      font-weight:700;
      color:#0f172a
    }
    
    .actions {
      display:flex;
      gap:.4rem;
      flex-wrap:wrap;
      margin-top:2px
    }
    
    .link {
      color:#7e22ce;
      text-decoration:none;
      font-weight:700
    }
    
    .link:hover{color:#5b21b6;text-decoration:underline}

    /* modal */
    .overlay {
      display:none;
      position:fixed;
      inset:0;
      background:rgba(0,0,0,.45);
      backdrop-filter:blur(2px);
      z-index:50;
      justify-content:center;
      align-items:center
    }
    
    .modal {
      background:#fff;
      padding:1.25rem 1.5rem;
      border-radius:14px;
      width:520px;
      max-width:92vw;
      box-shadow:0 16px 48px rgba(17,24,39,.18)
    }
    
    .modal h2 {
      font-size:1.2rem;
      font-weight:800;
      color:#4c1d95;
      margin:0 0 .5rem
    }
    
    .row {
      margin:.6rem 0
    }
    
    .row label {
      display:block;
      font-weight:700;
      margin-bottom:.25rem
    }
    
    .row input[type="text"], .row select{width:100%;
      padding:.6rem .7rem;
      border:1px solid #e5e7eb;
      border-radius:10px
    }
    
    .choice {
      display:flex;
      align-items:center;
      gap:.5rem;
      margin:.4rem 0
      }

    .pager {
      display:flex;
      justify-content:center;
      align-items:center;
      gap:.35rem;
      margin-top:1rem;
      flex-wrap:wrap;
    }

    .page-btn {
      padding:.45rem .7rem;
      border-radius:8px;
      border:1px solid #ddd6fe;
      background:#ffffff;
      color:#5b21b6;
      font-weight:700;
      text-decoration:none;
      font-size:.9rem;
      min-width:36px;
      text-align:center;
    }

    .page-btn:hover {
      background:#f5f3ff;
    }

    .page-btn.active {
      background:#7e22ce;
      color:#fff;
      border-color:#7e22ce;
    }

    .page-btn.disabled {
      pointer-events:none;
      opacity:.45;
    }

    @media (max-width: 980px) {
      .cards {
        grid-template-columns:repeat(2,minmax(0,1fr));
      }
    }

    @media (max-width: 640px) {
      .cards {
        grid-template-columns:1fr;
      }
    }
</style>

<div class="wrap">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap">
    <h1 class="h1">Question Bank</h1>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
      <button class="btn btn-add" onclick="openAdd()">➕ Add Trivia</button>
    </div>
  </div>

  @if (session('success'))
    <div class="notice ok">{{ session('success') }}</div>
  @endif
  @if ($errors->any())
    <div class="notice err">
      <ul style="margin:0;padding-left:1.1rem">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if($triviaPaginator->total() > 0)
    <div class="cards">
      @foreach ($triviaPaginator as $t)
        <div class="card">
          <div class="landmark">
            <span class="pill">🏷 {{ $t['landmark_name'] }}</span>
          </div>

          <div class="qtext">
            {{ $t['question'] }}
          </div>

          <div class="actions">
            <button class="btn btn-edit" onclick='openEdit(@json($t))'>✏️ Edit</button>
            <form action="{{ route('curators.trivia.destroy', $t['trivia_id']) }}"
                  method="POST"
                  onsubmit="return confirm('Delete this trivia?');">
              @csrf @method('DELETE')
              <button class="btn btn-del" type="submit">🗑 Delete</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>

    @if ($triviaPaginator->lastPage() > 1)
      <div class="pager">
        <a href="{{ $triviaPaginator->previousPageUrl() ?: '#' }}"
           class="page-btn {{ $triviaPaginator->onFirstPage() ? 'disabled' : '' }}">Prev</a>

        @for ($page = 1; $page <= $triviaPaginator->lastPage(); $page++)
          <a href="{{ $triviaPaginator->url($page) }}"
             class="page-btn {{ $triviaPaginator->currentPage() === $page ? 'active' : '' }}">
            {{ $page }}
          </a>
        @endfor

        <a href="{{ $triviaPaginator->hasMorePages() ? $triviaPaginator->nextPageUrl() : '#' }}"
           class="page-btn {{ $triviaPaginator->hasMorePages() ? '' : 'disabled' }}">Next</a>
      </div>
    @endif
  @else
    <p class="muted" style="margin-top:1rem">No trivia in the Question Bank yet.</p>
  @endif
</div>


<div id="addOverlay" class="overlay">
  <div class="modal">
    <h2>Add Trivia (Question Bank)</h2>
    <form action="{{ route('curators.trivia.store') }}" method="POST">
      @csrf
      <div class="row">
        <label>Landmark</label>
        <select name="landmark_id" required>
          <option value="">-- Select Landmark --</option>
          @foreach($landmarkList as $lm)
            <option value="{{ $lm['id'] }}">{{ $lm['name'] }}</option>
          @endforeach
        </select>
      </div>

      <div class="row">
        <label>Question</label>
        <input type="text" name="question" required>
      </div>

      <div class="row">
        <label>Choices (select one correct)</label>
        <div id="addChoices">
          @for ($i = 0; $i < 4; $i++)
          <div class="choice">
            <input type="radio" name="correct_answer" value="" required>
            <input type="text" name="choices[]" placeholder="Choice {{ $i+1 }}" required style="flex:1">
          </div>
          @endfor
        </div>
        <button type="button" class="btn btn-ghost" onclick="addChoice('addChoices')">＋ Add Choice</button>
      </div>

      <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:.75rem">
        <button type="button" class="btn" onclick="closeAdd()">Cancel</button>
        <button type="submit" class="btn btn-add">Save</button>
      </div>
    </form>
  </div>
</div>


<div id="editOverlay" class="overlay">
  <div class="modal">
    <h2>Edit Trivia (Question Bank)</h2>
    <form id="editForm" method="POST">
      @csrf @method('PUT')

      <div class="row">
        <label>Question</label>
        <input type="text" id="edit_question" name="question" required>
      </div>

      <div class="row">
        <label>Choices (select one correct)</label>
        <div id="editChoices"></div>
        <button type="button" class="btn btn-ghost" onclick="addChoice('editChoices')">＋ Add Choice</button>
      </div>

      <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:.75rem">
        <button type="button" class="btn" onclick="closeEdit()">Cancel</button>
        <button type="submit" class="btn btn-add">Update</button>
      </div>
    </form>
  </div>
</div>

<script>
  
  function openAdd(){ document.getElementById('addOverlay').style.display='flex'; syncRadios('addChoices'); }
  function closeAdd(){ document.getElementById('addOverlay').style.display='none'; }
  function openEdit(t){
      document.getElementById('editOverlay').style.display='flex';
      const form = document.getElementById('editForm');
      form.action = `/curators/trivia/${t.trivia_id}`;
      document.getElementById('edit_question').value = t.question || '';

      const box = document.getElementById('editChoices');
      box.innerHTML = '';
      const choices = Array.isArray(t.choices) ? t.choices : [];
      const atleast = Math.max(4, choices.length || 4);

      for(let i=0;i<atleast;i++){
          const val = choices[i] || '';
          const row = document.createElement('div');
          row.className='choice';
          row.innerHTML = `
              <input type="radio" name="correct_answer" value="${escapeHtml(val)}" ${t.correct_answer===val?'checked':''} required>
              <input type="text" name="choices[]" value="${escapeHtml(val)}" placeholder="Choice ${i+1}" required style="flex:1">
          `;
          box.appendChild(row);
      }
      syncRadios('editChoices');
  }
  function closeEdit(){ document.getElementById('editOverlay').style.display='none'; }

  function addChoice(containerId){
      const box = document.getElementById(containerId);
      const idx = box.querySelectorAll('.choice').length + 1;
      const row = document.createElement('div');
      row.className='choice';
      row.innerHTML = `
          <input type="radio" name="correct_answer" value="" required>
          <input type="text" name="choices[]" placeholder="Choice ${idx}" required style="flex:1">
      `;
      box.appendChild(row);
      syncRadios(containerId);
  }

  function syncRadios(containerId){
      const box = document.getElementById(containerId);
      box.querySelectorAll('.choice').forEach(ch => {
          const radio = ch.querySelector('input[type="radio"]');
          const text  = ch.querySelector('input[type="text"]');
          const setRadio = () => { radio.value = text.value; };
          text.addEventListener('input', setRadio);
          setRadio();
      });
  }

  function escapeHtml(s){
      return String(s)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
  }

 
  document.getElementById('addOverlay').addEventListener('click', (e)=>{
      if(e.target.id==='addOverlay') closeAdd();
  });
  document.getElementById('editOverlay').addEventListener('click', (e)=>{
      if(e.target.id==='editOverlay') closeEdit();
  });
</script>
@endsection
