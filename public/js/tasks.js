/* ====== CONFIG ====== */
const API_URL = (window.CI && window.CI.apiUrl) || '/api/tasks';
const CSRF    = (window.CI && window.CI.csrf)   || null;

const STATE = {
  query: { page: 1, per_page: 10, search: '', completed: '', sort: '-created_at' },
  links: { next: null, prev: null },
  isPageLoading: false,
};

/* ====== DOM ====== */
const $ = (s) => document.querySelector(s);
const tasksTable      = $('#tasksTable');
const metaInfo        = $('#metaInfo');
const prevBtn         = $('#prevBtn');
const nextBtn         = $('#nextBtn');
const openModalBtn    = $('#openModalBtn');
const cancelTaskBtn   = $('#cancelTaskBtn');
const saveTaskBtn     = $('#saveTaskBtn');
const taskModal       = $('#taskModal');
const modalTitle      = $('#modalTitle');
const taskTitle       = $('#taskTitle');
const titleError      = $('#titleError');
const taskId          = $('#taskId');
const searchInput     = $('#searchInput');
const completedSelect = $('#completedSelect');
const sortSelect      = $('#sortSelect');
const applyFiltersBtn = $('#applyFiltersBtn');
const loadingOverlay  = $('#loadingOverlay');
const toastContainer  = $('#toastContainer');

/* ====== UTIL ====== */
function esc(str) {
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function qs(paramsObj) {
  const sp = new URLSearchParams();
  if (paramsObj.page) sp.set('page', paramsObj.page);
  if (paramsObj.per_page) sp.set('per_page', paramsObj.per_page);
  if (paramsObj.search) sp.set('search', paramsObj.search);
  if (paramsObj.completed !== '' && paramsObj.completed != null) sp.set('completed', paramsObj.completed);
  if (paramsObj.sort) sp.set('sort', paramsObj.sort);
  return '?' + sp.toString();
}

function setPageLoading(on) {
  STATE.isPageLoading = !!on;
  loadingOverlay.classList.toggle('hidden', !on);
  [prevBtn, nextBtn, openModalBtn, applyFiltersBtn].forEach(b => b && (b.disabled = on));
}

function buttonLoading(btn, on, labelWhileLoading = 'Procesando…') {
  if (!btn) return;
  btn.disabled = !!on;
  if (on) {
    btn.dataset._label = btn.textContent;
    btn.textContent = labelWhileLoading;
    btn.classList.add('opacity-70');
  } else {
    btn.textContent = btn.dataset._label || btn.textContent;
    btn.classList.remove('opacity-70');
  }
}

function showToast(type, message, timeout = 3500) {
  const colors = { success: 'bg-green-600', error: 'bg-red-600', info: 'bg-slate-700', warn: 'bg-yellow-600' };
  const icons  = { success: '✅',           error: '❌',         info: 'ℹ️',           warn: '⚠️' };
  const el = document.createElement('div');
  el.className = `text-white px-4 py-3 rounded shadow ${colors[type] || colors.info}`;
  el.setAttribute('role', type === 'error' ? 'alert' : 'status');
  el.innerHTML = `<span class="mr-2">${icons[type] || icons.info}</span>${esc(message)}`;
  toastContainer.appendChild(el);
  setTimeout(() => el.remove(), timeout);
}

/* ====== API WRAPPER ====== */
function buildHeaders(hasBody) {
  const h = { Accept: 'application/json' };
  if (hasBody) h['Content-Type'] = 'application/json';
  if (CSRF && CSRF.hash) h['X-CSRF-TOKEN'] = CSRF.hash; // asegúrate en Config\Security que headerName sea X-CSRF-TOKEN
  return h;
}

async function api(url, options = {}) {
  const res = await fetch(url, {
    headers: { ...buildHeaders(!!options.body), ...(options.headers || {}) },
    ...options,
  });

  // refrescar token CSRF si el servidor envía uno nuevo
  const newCsrf = res.headers.get('X-CSRF-TOKEN');
  if (newCsrf && window.CI && window.CI.csrf) {
    window.CI.csrf.hash = newCsrf;
  }

  if (res.status === 204) return { json: null, res };

  let json = null;
  try { json = await res.json(); } catch {}

  if (res.status === 422) {
    const err = new Error('Validation');
    err.status = 422;
    err.errors = json?.errors || {};
    throw err;
  }

  if (!res.ok && res.status !== 201) {
    const err = new Error(`HTTP ${res.status}`);
    err.status = res.status;
    err.payload = json;
    throw err;
  }

  return { json, res };
}

/* ====== RENDER ====== */
function renderTasks(tasks) {
  tasksTable.innerHTML = '';
  for (const t of tasks) {
    const tr = document.createElement('tr');
    tr.className = 'border-b border-gray-200 hover:bg-gray-100';
    tr.innerHTML = `
      <td class="py-3 px-6 text-left">${esc(t.title)}</td>
      <td class="py-3 px-6 text-center">${t.completed == 1 ? '✅ Completada' : '⏳ Pendiente'}</td>
      <td class="py-3 px-6 text-center space-x-2">
        ${t.completed == 0
          ? `<button data-action="complete" data-id="${t.id}" class="bg-green-500 text-white px-2 py-1 rounded">Completar</button>`
          : ''
        }
        <button data-action="edit" data-id="${t.id}" class="bg-yellow-500 text-white px-2 py-1 rounded">Editar</button>
        <button data-action="delete" data-id="${t.id}" class="bg-red-500 text-white px-2 py-1 rounded">Eliminar</button>
      </td>
    `;
    tasksTable.appendChild(tr);
  }
}

function updatePager(meta = {}, links = {}) {
  metaInfo.textContent = (meta.page && meta.total_pages)
    ? `Página ${meta.page} de ${meta.total_pages} — Total: ${meta.total ?? 0}`
    : '';

  STATE.links.next = links?.next || null;
  STATE.links.prev = links?.prev || null;

  prevBtn.disabled = !STATE.links.prev || STATE.isPageLoading;
  nextBtn.disabled = !STATE.links.next || STATE.isPageLoading;
}

/* ====== ACCIONES ====== */
async function loadList() {
  setPageLoading(true);
  try {
    const url = API_URL + qs(STATE.query);
    const { json } = await api(url);
    renderTasks(json.data || []);
    updatePager(json.meta, json.links);
  } catch (e) {
    console.error(e);
    showToast('error', 'No se pudo cargar la lista de tareas.');
  } finally {
    setPageLoading(false);
  }
}

function openModal(task = null) {
  taskModal.classList.remove('hidden');
  modalTitle.textContent = task ? 'Editar Tarea' : 'Nueva Tarea';
  taskTitle.value = task?.title || '';
  taskId.value = task?.id || '';
  titleError.classList.add('hidden');
  titleError.textContent = '';
  taskTitle.focus();
}

function closeModal() {
  taskModal.classList.add('hidden');
}

async function saveTask() {
  const title = taskTitle.value.trim();
  const id = taskId.value;

  titleError.classList.add('hidden');
  titleError.textContent = '';

  if (!title) {
    titleError.textContent = 'El título es obligatorio.';
    titleError.classList.remove('hidden');
    return;
  }

  buttonLoading(saveTaskBtn, true, id ? 'Guardando…' : 'Creando…');

  try {
    if (id) {
      await api(`${API_URL}/${id}`, { method: 'PUT', body: JSON.stringify({ title }) });
      showToast('success', 'Tarea actualizada.');
    } else {
      await api(API_URL, { method: 'POST', body: JSON.stringify({ title }) });
      showToast('success', 'Tarea creada.');
    }
    closeModal();
    await loadList();
  } catch (e) {
    if (e.status === 422 && e.errors) {
      if (e.errors.title) {
        titleError.textContent = e.errors.title;
        titleError.classList.remove('hidden');
        showToast('warn', 'Revisa los datos del formulario.');
      } else {
        showToast('error', 'Datos inválidos.');
      }
    } else {
      showToast('error', 'No se pudo guardar la tarea.');
    }
  } finally {
    buttonLoading(saveTaskBtn, false);
  }
}

async function editTask(id) {
  setPageLoading(true);
  try {
    const { json } = await api(`${API_URL}/${id}`);
    openModal(json);
  } catch (e) {
    showToast('error', 'No se pudo cargar la tarea.');
  } finally {
    setPageLoading(false);
  }
}

async function completeTask(id, btn) {
  buttonLoading(btn, true, 'Completando…');
  try {
    await api(`${API_URL}/${id}`, { method: 'PUT', body: JSON.stringify({ completed: 1 }) });
    showToast('success', 'Tarea completada.');
    await loadList();
  } catch (e) {
    showToast('error', 'No se pudo completar la tarea.');
  } finally {
    buttonLoading(btn, false);
  }
}

async function deleteTask(id, btn) {
  if (!confirm('¿Estás seguro de eliminar esta tarea?')) return;
  buttonLoading(btn, true, 'Eliminando…');
  try {
    await api(`${API_URL}/${id}`, { method: 'DELETE' }); // 204
    showToast('success', 'Tarea eliminada.');
    await loadList();
  } catch (e) {
    showToast('error', 'No se pudo eliminar la tarea.');
  } finally {
    buttonLoading(btn, false);
  }
}

/* ====== EVENTOS ====== */
document.addEventListener('DOMContentLoaded', () => {
  loadList();

  // Abrir/Cerrar modal
  openModalBtn.addEventListener('click', () => openModal());
  cancelTaskBtn.addEventListener('click', closeModal);
  saveTaskBtn.addEventListener('click', saveTask);

  // Paginación
  prevBtn.addEventListener('click', () => {
    if (!STATE.links.prev) return;
    const url = new URL(STATE.links.prev, window.location.origin);
    STATE.query = Object.fromEntries(url.searchParams.entries());
    STATE.query.page = Number(STATE.query.page || 1);
    loadList();
  });
  nextBtn.addEventListener('click', () => {
    if (!STATE.links.next) return;
    const url = new URL(STATE.links.next, window.location.origin);
    STATE.query = Object.fromEntries(url.searchParams.entries());
    STATE.query.page = Number(STATE.query.page || 1);
    loadList();
  });

  // Filtros
  applyFiltersBtn.addEventListener('click', () => {
    STATE.query.page = 1;
    STATE.query.search = (searchInput.value || '').trim();
    STATE.query.completed = completedSelect.value; // '', 'true', 'false'
    STATE.query.sort = sortSelect.value;
    loadList();
  });

  // Delegación de eventos en la tabla
  tasksTable.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    const id = Number(btn.dataset.id);
    const action = btn.dataset.action;
    if (action === 'edit') return editTask(id);
    if (action === 'delete') return deleteTask(id, btn);
    if (action === 'complete') return completeTask(id, btn);
  });
});
