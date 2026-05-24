@extends('layouts.sidebar')

@section('content')
<style>
    body { 
    background:#f5f3ff; 
    font-family: 'Inter', sans-serif; 
    }
    
    .wrap {
      max-width:2000px;
      margin:0 auto;
      padding:1rem
    }
    
    .topbar {
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:.9rem;
      flex-wrap:wrap;
      margin-bottom:.5rem
    }

    .title-group {
      display:flex;
      flex-direction:column;
      gap:.2rem
    }

    .h1 {
      font-size:2rem;
      font-weight:800;
      color:#7A2E1F;
      margin:0
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
      transition:.18s ease;
      box-shadow:0 2px 8px rgba(15,23,42,.08)
    }
    
    .btn-add {
      background:#E8B34B;
      color:#7A2E1F;
      border:1px solid #F3C96A
    }
    
    .btn-add:hover { 
      background:#F3C96A;
      transform:translateY(-1px)
    }
    
    .btn-edit {
      background:#fbbf24;
      color:#1f2937
    }
    
    .btn-edit:hover {
      background:#f59e0b;
      transform:translateY(-1px)
    }
    
    .btn-del {
      background:#ef4444;
      color:#fff
    }
    
    .btn-del:hover {
      background:#dc2626;
      transform:translateY(-1px)
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
      gap:14px;
      margin-top:.75rem
    }
    
    .card {
      background:#fff;
      border-radius:14px;
      border:1px solid #edf0f5;
      box-shadow:0 8px 24px rgba(17,24,39,.06);
      padding:15px;
      display:flex;
      flex-direction:column;
      gap:12px;
      min-height:0;
      transition:transform .15s ease, box-shadow .15s ease
    }

    .card-body {
      display:flex;
      flex-direction:column;
      gap:10px;
      flex:1;
    }

    .card:hover {
      transform:translateY(-2px);
      box-shadow:0 14px 30px rgba(17,24,39,.12)
    }

    .card--editing {
      border-color:#F3C96A;
      box-shadow:0 0 0 2px rgba(232,179,75,.35), 0 8px 24px rgba(17,24,39,.06);
    }

    .card-footer {
      display:flex;
      gap:.5rem;
      align-items:center;
      justify-content:flex-end;
      flex-wrap:wrap;
      margin-top:auto;
      padding-top:.65rem;
      border-top:1px solid #f1f5f9;
    }

    .card-footer .js-delete-form {
      display:inline-flex;
      margin:0
    }

    .btn-trivia {
      display:inline-flex;
      align-items:center;
      justify-content:center;
      font-size:.875rem;
      line-height:1.25;
      padding:.25rem .75rem;
      border-radius:.375rem;
      border:none;
      cursor:pointer;
      font-weight:600;
      text-decoration:none;
      transition:background .15s ease, filter .15s ease;
      box-sizing:border-box;
    }

    .btn-trivia-edit {
      background:#3b82f6;
      color:#fff
    }

    .btn-trivia-edit:hover {
      background:#2563eb
    }

    .btn-trivia-del {
      background:#ef4444;
      color:#fff
    }

    .btn-trivia-del:hover {
      background:#dc2626
    }

    .pill {
      display:inline-flex;
      align-items:center;
      gap:.35rem;
      background:#fff7ed;
      border:1px solid #F3C96A;
      color:#7A2E1F;
      border-radius:999px;
      padding:.2rem .6rem;
      font-size:.8rem;
      font-weight:700
    }
    
    .qtext {
      font-weight:700;
      color:#111827;
      line-height:1.45;
      font-size:1.02rem;
      word-break:break-word;
    }

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
      color:#7A2E1F;
      margin:0 0 .5rem
    }

    .modal-message {
      margin: 0;
      color: #374151;
      line-height: 1.45;
    }

    .modal-actions {
      display:flex;
      gap:.5rem;
      justify-content:flex-end;
      margin-top:.9rem;
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
      border:1px solid #e5e7eb;
      background:#ffffff;
      color:#7A2E1F;
      font-weight:700;
      text-decoration:none;
      font-size:.9rem;
      min-width:36px;
      text-align:center;
      transition:all .15s ease;
    }

    .page-btn:hover {
      background:#fff7ed;
      border-color:#F3C96A;
      transform:translateY(-1px);
    }

    .page-btn.active {
      background:#E8B34B;
      color:#7A2E1F;
      border-color:#F3C96A;
    }

    .page-btn.disabled {
      pointer-events:none;
      opacity:.5;
      background:#f9fafb;
      color:#9ca3af;
      border-color:#e5e7eb;
    }

    @media (max-width: 980px) {
      .cards {
        grid-template-columns:repeat(2,minmax(0,1fr));
      }
    }

    @media (max-width: 640px) {
      .topbar {
        align-items:stretch
      }
      .topbar > div:last-child {
        width:100%
      }
      .topbar .btn-add {
        width:100%
      }
      .cards {
        grid-template-columns:1fr;
      }
    }
</style>

<div class="wrap">
  <div class="topbar">
    <div class="title-group">
      <h1 class="h1">Question Bank</h1>
      @if($triviaPaginator->total() > 0)
        <p class="muted" style="margin:0;">{{ $triviaPaginator->total() }} question{{ $triviaPaginator->total() > 1 ? 's' : '' }} found</p>
      @endif
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
      @if(count($landmarkList) > 0)
      <button class="btn btn-add" onclick="openAdd()">Add Trivia</button>
      @endif
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
        @php
          $triviaLmKey = trim((string) ($t['landmark_id'] ?? ''));
          $canEditTrivia = isset($writableLandmarkIdSet[$triviaLmKey])
              || ($assignedLandmarkId !== '' && $triviaLmKey === $assignedLandmarkId);
          $isEditingThis = !empty($autoOpenTrivia) && ($autoOpenTrivia['trivia_id'] ?? '') === ($t['trivia_id'] ?? '');
        @endphp
        <div class="card{{ $isEditingThis ? ' card--editing' : '' }}">
            <div class="card-body">
                <span class="pill">{{ $t['landmark_name'] }}</span>
                <div class="qtext">{{ $t['question'] }}</div>
            </div>
            @if ($canEditTrivia)
                <div class="card-footer">
                    <a href="{{ route('curators.trivia.show', $t['trivia_id']) }}"
                       class="btn-trivia btn-trivia-edit">
                        Edit
                    </a>
                    <form action="{{ route('curators.trivia.destroy', $t['trivia_id']) }}"
                          method="POST"
                          class="js-delete-form">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-trivia btn-trivia-del">Delete</button>
                    </form>
                </div>
            @endif
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
    <p class="muted" style="margin-top:1rem;background:#fff;border:1px dashed #e5e7eb;border-radius:10px;padding:.8rem 1rem;">No trivia in the Question Bank yet. Add your first question to get started.</p>
  @endif
</div>


<div id="addOverlay" class="overlay">
  <div class="modal">
    <h2>Add Trivia (Question Bank)</h2>
    <form action="{{ route('curators.trivia.store') }}" method="POST">
      @csrf
      @if(count($landmarkList) === 1)
        <input type="hidden" name="landmark_id" value="{{ $landmarkList[0]['id'] }}">
      @else
      <div class="row">
        <label>Site</label>
        <select name="landmark_id" required>
          <option value="">-- Select site --</option>
          @foreach($landmarkList as $lm)
            <option value="{{ $lm['id'] }}">{{ $lm['name'] }}</option>
          @endforeach
        </select>
      </div>
      @endif

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
        <button type="button" class="btn btn-ghost" onclick="addChoice('addChoices')">Add Choice</button>
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
        <button type="button" class="btn btn-ghost" onclick="addChoice('editChoices')">Add Choice</button>
      </div>

      <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:.75rem">
        <button type="button" class="btn" onclick="closeEdit()">Cancel</button>
        <button type="submit" class="btn btn-add">Update</button>
      </div>
    </form>
  </div>
</div>

<div id="deleteOverlay" class="overlay">
  <div class="modal">
    <h2>Delete Trivia</h2>
    <p class="modal-message">Are you sure you want to delete this trivia?</p>
    <div class="modal-actions">
      <button type="button" class="btn btn-del" onclick="confirmDelete()">Delete</button>
      <button type="button" class="btn" onclick="closeDelete()">Cancel</button>
    </div>
  </div>
</div>

<script>
  let pendingDeleteForm = null;
  const triviaUpdateRouteTemplate = @json(route('curators.trivia.update', ['id' => '__TRIVIA_ID__']));
  const triviaIndexUrl = @json(route('curators.trivia.all'));

  function openAdd(){ 
      document.getElementById('addOverlay').style.display='flex'; 
      document.body.style.overflow='hidden';
      syncRadios('addChoices'); 
  }
  function closeAdd(){ 
      document.getElementById('addOverlay').style.display='none'; 
      document.body.style.overflow='';
  }

  function openEdit(t){
      document.getElementById('editOverlay').style.display='flex';
      document.body.style.overflow='hidden';
      const form = document.getElementById('editForm');
      form.action = triviaUpdateRouteTemplate.replace('__TRIVIA_ID__', encodeURIComponent(t.trivia_id || ''));
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
  function closeEdit(){
      document.getElementById('editOverlay').style.display='none';
      document.body.style.overflow='';
      const path = window.location.pathname.replace(/\/+$/, '');
      if (/^\/curators\/trivia\/[^/]+$/.test(path)) {
          window.location.assign(triviaIndexUrl);
      }
  }

  function openDelete(form){
      pendingDeleteForm = form;
      document.getElementById('deleteOverlay').style.display='flex';
      document.body.style.overflow='hidden';
  }

  function closeDelete(){
      document.getElementById('deleteOverlay').style.display='none';
      pendingDeleteForm = null;
      document.body.style.overflow='';
  }

  function confirmDelete(){
      if (pendingDeleteForm) {
          pendingDeleteForm.submit();
      }
  }

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
  document.getElementById('deleteOverlay').addEventListener('click', (e)=>{
      if(e.target.id==='deleteOverlay') closeDelete();
  });

  document.querySelectorAll('.js-delete-form').forEach((form)=>{
      form.addEventListener('submit', (e)=>{
          e.preventDefault();
          openDelete(form);
      });
  });

  @if (!empty($autoOpenTrivia))
  document.addEventListener('DOMContentLoaded', ()=>{
      const t = @json($autoOpenTrivia);
      if (t && t.trivia_id) {
          openEdit(t);
      }
  });
  @endif

  window.addEventListener('keydown', (e)=>{
      if(e.key === 'Escape'){
          closeAdd();
          closeEdit();
          closeDelete();
      }
  });
</script>
@endsection
