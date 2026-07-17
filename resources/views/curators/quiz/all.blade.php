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
      position:relative;
      transition:transform .15s ease, box-shadow .15s ease
    }

    .card-body {
      display:flex;
      flex-direction:column;
      gap:10px;
      flex:1;
      width:100%;
      box-sizing:border-box;
      padding-right:34px;
    }

    .card:hover {
      transform:translateY(-2px);
      box-shadow:0 14px 30px rgba(17,24,39,.12)
    }

    .card--editing {
      border-color:#F3C96A;
      box-shadow:0 0 0 2px rgba(232,179,75,.35), 0 8px 24px rgba(17,24,39,.06);
    }

    .card:focus-within { z-index:20; }

    .card-footer {
      display:flex;
      gap:.5rem;
      align-items:center;
      justify-content:flex-end;
      flex-wrap:wrap;
      margin-top:auto;
      padding-top:.65rem;
      min-height:1.6rem;
      border-top:1px solid #f1f5f9;
    }

    .quiz-action-menu {
      position:absolute;
      top:10px;
      right:10px;
      z-index:10;
    }

    .quiz-action-trigger {
      width:28px;
      height:28px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:0;
      border:0;
      border-radius:7px;
      background:transparent;
      color:#4b5563;
      font:inherit;
      font-size:1.25rem;
      line-height:1;
      cursor:pointer;
    }

    .quiz-action-trigger:hover,
    .quiz-action-trigger:focus-visible {
      background:#f3f4f6;
      outline:none;
    }

    .quiz-action-dropdown {
      position:absolute;
      top:calc(100% + 4px);
      right:0;
      min-width:120px;
      display:none;
      padding:.3rem;
      border:1px solid #e5e7eb;
      border-radius:9px;
      background:#fff;
      box-shadow:0 10px 24px rgba(15,23,42,.14);
    }

    .quiz-action-menu.is-open .quiz-action-dropdown { display:block; }
    .quiz-action-dropdown form { margin:0; }
    .quiz-action-item {
      width:100%;
      display:flex;
      align-items:center;
      padding:.48rem .62rem;
      border:0;
      border-radius:6px;
      background:transparent;
      color:#374151;
      font:inherit;
      font-size:.875rem;
      font-weight:600;
      line-height:1.2;
      text-align:left;
      text-decoration:none;
      cursor:pointer;
    }

    .quiz-action-item:hover,
    .quiz-action-item:focus-visible {
      background:#f3f4f6;
      outline:none;
    }

    .qtext {
      font-weight:700;
      color:#111827;
      line-height:1.45;
      font-size:1.02rem;
      word-break:break-word;
      overflow-wrap:anywhere;
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
    #deleteOverlay .modal-actions .btn {
      padding:.45rem .8rem;
      border-radius:8px;
      border:1px solid #e5e7eb;
      background:#f3f4f6;
      color:#374151;
      font-size:.85rem;
      line-height:1;
      font-weight:700;
      box-shadow:none;
    }
    #deleteOverlay .modal-actions .btn-del {
      background:#ef4444;
      border-color:#ef4444;
      color:#fff;
    }
    #deleteOverlay .modal-actions .btn-del:hover {
      background:#dc2626;
      border-color:#dc2626;
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
      justify-content:flex-end;
      align-items:center;
      gap:.35rem;
      margin-top:3.25rem;
      padding-right:.25rem;
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
      .pager {
        justify-content:flex-end;
        margin-top:2rem;
      }
    }
</style>

<div class="wrap">
  <div class="topbar">
    <div class="title-group">
      <h1 class="h1">Quiz Bank</h1>
      @if($quizPaginator->total() > 0)
        <p class="muted" style="margin:0;">{{ $quizPaginator->total() }} question{{ $quizPaginator->total() > 1 ? 's' : '' }} found</p>
      @endif
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
      @if(count($landmarkList) > 0)
      <button class="btn btn-add" onclick="openAdd()">Add Quiz</button>
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

  @if($quizPaginator->total() > 0)
    <div class="cards">
      @foreach ($quizPaginator as $t)
        @php
          $quizLmKey = trim((string) ($t['landmark_id'] ?? ''));
          $canEditQuiz = isset($writableLandmarkIdSet[$quizLmKey])
              || ($assignedLandmarkId !== '' && $quizLmKey === $assignedLandmarkId);
          $isEditingThis = !empty($autoOpenQuiz) && ($autoOpenQuiz['quiz_id'] ?? '') === ($t['quiz_id'] ?? '');
        @endphp
        <div class="card{{ $isEditingThis ? ' card--editing' : '' }}">
            @if ($canEditQuiz)
                <div class="quiz-action-menu">
                    <button type="button"
                            class="quiz-action-trigger"
                            data-quiz-menu-trigger
                            aria-label="Quiz actions"
                            aria-haspopup="menu"
                            aria-expanded="false">&#8942;</button>
                    <div class="quiz-action-dropdown" role="menu">
                        <button type="button"
                                class="quiz-action-item"
                                role="menuitem"
                                data-quiz-edit="{{ $t['quiz_id'] }}"
                                data-quiz-menu-item>Edit</button>
                        <form method="POST"
                              action="{{ route('curators.quiz.destroy', $t['quiz_id']) }}"
                              class="js-delete-form"
                              data-quiz-id="{{ $t['quiz_id'] }}"
                              data-quiz-name="{{ $t['question'] }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="quiz-action-item" role="menuitem" data-quiz-menu-item>Delete</button>
                        </form>
                    </div>
                </div>
            @endif
            <div class="card-body">
                <div class="qtext">{{ $t['question'] }}</div>
            </div>
            @if ($canEditQuiz)
                <div class="card-footer" aria-hidden="true"></div>
            @endif
        </div>
      @endforeach
    </div>

    @if ($quizPaginator->lastPage() > 1)
      <div class="pager">
        <a href="{{ $quizPaginator->previousPageUrl() ?: '#' }}"
           class="page-btn {{ $quizPaginator->onFirstPage() ? 'disabled' : '' }}">Prev</a>

        @for ($page = 1; $page <= $quizPaginator->lastPage(); $page++)
          <a href="{{ $quizPaginator->url($page) }}"
             class="page-btn {{ $quizPaginator->currentPage() === $page ? 'active' : '' }}">
            {{ $page }}
          </a>
        @endfor

        <a href="{{ $quizPaginator->hasMorePages() ? $quizPaginator->nextPageUrl() : '#' }}"
           class="page-btn {{ $quizPaginator->hasMorePages() ? '' : 'disabled' }}">Next</a>
      </div>
    @endif
  @else
    <p class="muted" style="margin-top:1rem;background:#fff;border:1px dashed #e5e7eb;border-radius:10px;padding:.8rem 1rem;">No quizzes in the Quiz Bank yet. Add your first question to get started.</p>
  @endif
</div>


<div id="addOverlay" class="overlay">
  <div class="modal">
    <h2>Add Quiz (Quiz Bank)</h2>
    <form action="{{ route('curators.quiz.store') }}" method="POST">
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
    <button type="button" class="btn" onclick="closeEdit()" aria-label="Close" style="float:right;padding:.2rem .55rem;">&times;</button>
    <h2>Edit Quiz</h2>
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
        <button type="submit" class="btn btn-add">Save changes</button>
      </div>
    </form>
  </div>
</div>

<div id="deleteOverlay" class="overlay">
  <div class="modal">
    <button type="button" class="btn" onclick="closeDelete()" aria-label="Close" style="float:right;padding:.2rem .55rem;">&times;</button>
    <h2>Delete this quiz?</h2>
    <p id="deleteQuizMessage" class="modal-message">Are you sure you want to delete this quiz question?</p>
    <form id="deleteForm" method="POST">
      @csrf
      @method('DELETE')
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeDelete()">Cancel</button>
        <button type="submit" class="btn btn-del">Delete</button>
      </div>
    </form>
  </div>
</div>

<script>
  let editHistoryPushed = false;
  let deleteHistoryPushed = false;
  const quizUpdateRouteTemplate = @json(route('curators.quiz.update', ['id' => '__QUIZ_ID__']));
  const quizDestroyRouteTemplate = @json(route('curators.quiz.destroy', ['id' => '__QUIZ_ID__']));
  const quizEditUrlTemplate = @json(route('curators.quiz.show', ['id' => '__QUIZ_ID__']));
  const quizDeleteUrlTemplate = @json(route('curators.quiz.delete-confirm', ['id' => '__QUIZ_ID__']));
  const quizIndexUrl = @json(route('curators.quiz.all'));
  const quizItemsById = @json(collect($quizPaginator->items())->keyBy('quiz_id')->all());
  const autoOpenQuiz = @json($autoOpenQuiz);
  const autoOpenQuizMode = @json($autoOpenQuizMode);

  if (autoOpenQuiz && autoOpenQuiz.quiz_id) {
      quizItemsById[autoOpenQuiz.quiz_id] = autoOpenQuiz;
  }

  function closeQuizActionMenus(exceptMenu){
      document.querySelectorAll('.quiz-action-menu.is-open').forEach((menu)=>{
          if (menu === exceptMenu) return;
          menu.classList.remove('is-open');
          const trigger = menu.querySelector('[data-quiz-menu-trigger]');
          if (trigger) trigger.setAttribute('aria-expanded', 'false');
      });
  }

  function openAdd(){ 
      document.getElementById('addOverlay').style.display='flex'; 
      document.body.style.overflow='hidden';
      syncRadios('addChoices'); 
  }
  function closeAdd(){ 
      document.getElementById('addOverlay').style.display='none'; 
      document.body.style.overflow='';
  }

  function quizEditUrl(quizId){
      return quizEditUrlTemplate.replace('__QUIZ_ID__', encodeURIComponent(quizId || ''));
  }

  function quizDeleteUrl(quizId){
      return quizDeleteUrlTemplate.replace('__QUIZ_ID__', encodeURIComponent(quizId || ''));
  }

  function quizModalRouteFromPath(){
      let match = window.location.pathname.match(/^\/curators\/quiz\/([^/]+)\/delete\/?$/);
      if (match) return { mode: 'delete', quizId: decodeURIComponent(match[1]) };

      match = window.location.pathname.match(/^\/curators\/quiz\/([^/]+)\/?$/);
      if (match) return { mode: 'edit', quizId: decodeURIComponent(match[1]) };

      return null;
  }

  function openEdit(t, updateUrl = true){
      document.getElementById('editOverlay').style.display='flex';
      document.body.style.overflow='hidden';
      const form = document.getElementById('editForm');
      form.action = quizUpdateRouteTemplate.replace('__QUIZ_ID__', encodeURIComponent(t.quiz_id || ''));
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

      if (updateUrl && t.quiz_id) {
          const targetUrl = quizEditUrl(t.quiz_id);
          if (window.location.pathname !== new URL(targetUrl, window.location.origin).pathname) {
              history.pushState({ quizEditId: t.quiz_id }, '', targetUrl);
              editHistoryPushed = true;
          }
      }
  }
  function closeEdit(updateUrl = true){
      const overlay = document.getElementById('editOverlay');
      if (overlay.style.display !== 'flex') return;

      overlay.style.display='none';
      document.body.style.overflow='';
      document.getElementById('editForm').reset();
      document.getElementById('editChoices').innerHTML = '';

      const modalRoute = quizModalRouteFromPath();
      if (updateUrl && modalRoute && modalRoute.mode === 'edit') {
          if (editHistoryPushed) {
              editHistoryPushed = false;
              history.back();
          } else {
              history.replaceState(null, '', quizIndexUrl);
          }
      }
  }

  function openDelete(quizId, name, updateUrl = true){
      document.getElementById('deleteForm').action = quizDestroyRouteTemplate.replace('__QUIZ_ID__', encodeURIComponent(quizId || ''));
      document.getElementById('deleteQuizMessage').textContent = `Are you sure you want to delete "${name || 'this quiz question'}"?`;
      document.getElementById('deleteOverlay').style.display='flex';
      document.body.style.overflow='hidden';

      if (updateUrl && quizId) {
          const targetUrl = quizDeleteUrl(quizId);
          if (window.location.pathname !== new URL(targetUrl, window.location.origin).pathname) {
              history.pushState({ quizDeleteId: quizId }, '', targetUrl);
              deleteHistoryPushed = true;
          }
      }
  }

  function closeDelete(updateUrl = true){
      const overlay = document.getElementById('deleteOverlay');
      if (overlay.style.display !== 'flex') return;

      overlay.style.display='none';
      document.getElementById('deleteForm').removeAttribute('action');
      document.body.style.overflow='';

      const modalRoute = quizModalRouteFromPath();
      if (updateUrl && modalRoute && modalRoute.mode === 'delete') {
          if (deleteHistoryPushed) {
              deleteHistoryPushed = false;
              history.back();
          } else {
              history.replaceState(null, '', quizIndexUrl);
          }
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
          closeQuizActionMenus();
          openDelete(form.dataset.quizId || '', form.dataset.quizName || '');
      });
  });

  document.querySelectorAll('[data-quiz-menu-trigger]').forEach((trigger)=>{
      trigger.addEventListener('click', (e)=>{
          e.stopPropagation();
          const menu = trigger.closest('.quiz-action-menu');
          const willOpen = !menu.classList.contains('is-open');
          closeQuizActionMenus(menu);
          menu.classList.toggle('is-open', willOpen);
          trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      });
  });

  document.querySelectorAll('[data-quiz-edit]').forEach((button)=>{
      button.addEventListener('click', ()=>{
          const quiz = quizItemsById[button.dataset.quizEdit];
          closeQuizActionMenus();
          if (quiz) openEdit(quiz);
      });
  });

  document.querySelectorAll('[data-quiz-menu-item]').forEach((item)=>{
      if (item.hasAttribute('data-quiz-edit')) return;
      item.addEventListener('click', ()=>closeQuizActionMenus());
  });

  document.addEventListener('click', (e)=>{
      if (!e.target.closest('.quiz-action-menu')) closeQuizActionMenus();
  });

  document.addEventListener('DOMContentLoaded', ()=>{
      if (autoOpenQuiz && autoOpenQuiz.quiz_id) {
          const modalUrl = window.location.href;
          history.replaceState({ quizList: true }, '', quizIndexUrl);

          if (autoOpenQuizMode === 'delete') {
              history.pushState({ quizDeleteId: autoOpenQuiz.quiz_id }, '', modalUrl);
              deleteHistoryPushed = true;
              openDelete(autoOpenQuiz.quiz_id, autoOpenQuiz.question || '', false);
          } else if (autoOpenQuizMode === 'edit') {
              history.pushState({ quizEditId: autoOpenQuiz.quiz_id }, '', modalUrl);
              editHistoryPushed = true;
              openEdit(autoOpenQuiz, false);
          }
      }
  });

  window.addEventListener('popstate', ()=>{
      const modalRoute = quizModalRouteFromPath();
      const quiz = modalRoute ? quizItemsById[modalRoute.quizId] : null;

      if (modalRoute && quiz && modalRoute.mode === 'edit') {
          editHistoryPushed = false;
          deleteHistoryPushed = false;
          closeDelete(false);
          openEdit(quiz, false);
          return;
      }

      if (modalRoute && quiz && modalRoute.mode === 'delete') {
          editHistoryPushed = false;
          deleteHistoryPushed = false;
          closeEdit(false);
          openDelete(quiz.quiz_id, quiz.question || '', false);
          return;
      }

      editHistoryPushed = false;
      deleteHistoryPushed = false;
      closeEdit(false);
      closeDelete(false);
  });

  window.addEventListener('keydown', (e)=>{
      if(e.key === 'Escape'){
          closeQuizActionMenus();
          closeAdd();
          closeEdit();
          closeDelete();
      }
  });
</script>
@endsection
