<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Lista de Tareas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>

  <?php helper('url'); helper('security'); ?>
  <script>
    // Config global para el JS
    window.CI = {
      apiUrl: "<?= base_url('api/tasks') ?>",
      csrf: {
        // Si CSRF está habilitado, tasks.js enviará este token en 'X-CSRF-TOKEN'
        name: "<?= csrf_token() ?>",
        hash: "<?= csrf_hash() ?>",
      }
    };
  </script>
</head>
<body class="bg-gray-100 p-8">

  <div class="container mx-auto">
    <h1 class="text-3xl font-bold mb-6">📋 Lista de Tareas</h1>

    <div class="flex items-center gap-2 mb-4">
      <button id="openModalBtn" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
        ➕ Nueva Tarea
      </button>

      <!-- Filtros -->
      <input id="searchInput" class="border rounded px-2 py-1" placeholder="Buscar..." />
      <select id="completedSelect" class="border rounded px-2 py-1">
        <option value="">Todos</option>
        <option value="true">Completados</option>
        <option value="false">Pendientes</option>
      </select>
      <select id="sortSelect" class="border rounded px-2 py-1">
        <option value="-created_at">Recientes</option>
        <option value="created_at">Antiguos</option>
        <option value="title">Título A-Z</option>
        <option value="-title">Título Z-A</option>
      </select>
      <button id="applyFiltersBtn" class="px-3 py-1 bg-gray-800 text-white rounded">Aplicar</button>
    </div>

    <table class="min-w-full bg-white shadow-md rounded overflow-hidden">
      <thead>
        <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
          <th class="py-3 px-6 text-left">Título</th>
          <th class="py-3 px-6 text-center">Estado</th>
          <th class="py-3 px-6 text-center">Acciones</th>
        </tr>
      </thead>
      <tbody id="tasksTable" class="text-gray-600 text-sm"></tbody>
    </table>

    <!-- Paginación + meta -->
    <div class="flex justify-between items-center mt-3">
      <div id="metaInfo" class="text-sm text-gray-600"></div>
      <div class="space-x-2">
        <button id="prevBtn" class="px-3 py-1 bg-gray-300 rounded disabled:opacity-50" disabled>⟵ Anterior</button>
        <button id="nextBtn" class="px-3 py-1 bg-gray-300 rounded disabled:opacity-50" disabled>Siguiente ⟶</button>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div id="taskModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex justify-center items-center">
    <div class="bg-white p-6 rounded shadow-md w-96">
      <h2 class="text-xl font-bold mb-4" id="modalTitle">Nueva Tarea</h2>
      <input type="text" id="taskTitle" placeholder="Título de la tarea" class="w-full border px-3 py-2 rounded mb-1" />
      <p id="titleError" class="text-red-600 text-sm mb-3 hidden"></p>
      <input type="hidden" id="taskId" />
      <div class="flex justify-end">
        <button id="cancelTaskBtn" class="px-4 py-2 mr-2 bg-gray-300 rounded">Cancelar</button>
        <button id="saveTaskBtn" class="px-4 py-2 bg-blue-500 text-white rounded">Guardar</button>
      </div>
    </div>
  </div>

  <!-- Overlay de carga global -->
  <div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
    <div class="rounded-full border-8 border-t-8 border-gray-200 h-16 w-16 animate-spin border-t-blue-500"></div>
  </div>

  <!-- Contenedor de toasts -->
  <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

  <style>
    .animate-spin { animation: spin 1s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
  </style>

  <script src="<?= base_url('js/tasks.js') ?>" defer></script>
</body>
</html>
