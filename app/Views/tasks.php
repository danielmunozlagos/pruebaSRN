<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Lista de Tareas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">

    <div class="container mx-auto">
        <h1 class="text-3xl font-bold mb-6">📋 Lista de Tareas</h1>

        <button onclick="openModal()" class="mb-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            ➕ Nueva Tarea
        </button>

        <table class="min-w-full bg-white shadow-md rounded overflow-hidden">
            <thead>
                <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-left">Título</th>
                    <th class="py-3 px-6 text-center">Estado</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="tasksTable" class="text-gray-600 text-sm">
                </tbody>
        </table>
    </div>

    <div id="taskModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex justify-center items-center">
        <div class="bg-white p-6 rounded shadow-md w-96">
            <h2 class="text-xl font-bold mb-4" id="modalTitle">Nueva Tarea</h2>
            <input type="text" id="taskTitle" placeholder="Título de la tarea" class="w-full border px-3 py-2 rounded mb-4" />
            <input type="hidden" id="taskId" />
            <div class="flex justify-end">
                <button onclick="closeModal()" class="px-4 py-2 mr-2 bg-gray-300 rounded">Cancelar</button>
                <button onclick="saveTask()" class="px-4 py-2 bg-blue-500 text-white rounded">Guardar</button>
            </div>
        </div>
    </div>

    <div id="loadingSpinner" class="hidden fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
        <div class="loader ease-linear rounded-full border-8 border-t-8 border-gray-200 h-16 w-16 animate-spin border-t-blue-500"></div>
    </div>

    <style>
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script>
        // ¡ÚNICO CAMBIO! Se agrega el prefijo '/api'
        const API_URL = '/api/tasks';

        // Cargar tareas al iniciar
        window.onload = loadTasks;

        async function loadTasks() {
            showLoading();
            try {
                const res = await fetch(API_URL);
                const tasks = await res.json();
                const table = document.getElementById('tasksTable');
                table.innerHTML = '';

                tasks.forEach(task => {
                    const tr = document.createElement('tr');
                    tr.className = 'border-b border-gray-200 hover:bg-gray-100';

                    const estado = task.completed == 1 ? '✅ Completada' : '⏳ Pendiente';

                    tr.innerHTML = `
                        <td class="py-3 px-6 text-left">${task.title}</td>
                        <td class="py-3 px-6 text-center">${estado}</td>
                        <td class="py-3 px-6 text-center space-x-2">
                            ${task.completed == 0
                                ? `<button onclick="completeTask(${task.id})" class="bg-green-500 text-white px-2 py-1 rounded">Completar</button>`
                                : ''
                            }
                            <button onclick="editTask(${task.id})" class="bg-yellow-400 text-white px-2 py-1 rounded">Editar</button>
                            <button onclick="deleteTask(${task.id})" class="bg-red-500 text-white px-2 py-1 rounded">Eliminar</button>
                        </td>
                    `;
                    table.appendChild(tr);
                });
            } catch (err) {
                alert('Error al cargar tareas.');
            } finally {
                hideLoading();
            }
        }

        function openModal(task = null) {
            document.getElementById('taskModal').classList.remove('hidden');
            document.getElementById('taskTitle').value = task?.title || '';
            document.getElementById('taskId').value = task?.id || '';
            document.getElementById('modalTitle').innerText = task ? 'Editar Tarea' : 'Nueva Tarea';
        }

        function closeModal() {
            document.getElementById('taskModal').classList.add('hidden');
        }

        async function saveTask() {
            const title = document.getElementById('taskTitle').value.trim();
            const id = document.getElementById('taskId').value;

            if (!title) return alert('El título es obligatorio.');

            const payload = { title };
            showLoading();

            try {
                if (id) {
                    await fetch(`${API_URL}/${id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                } else {
                    await fetch(API_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                }

                closeModal();
                loadTasks();
            } catch (err) {
                alert('Error al guardar la tarea.');
            } finally {
                hideLoading();
            }
        }

        async function deleteTask(id) {
            if (!confirm('¿Estás seguro de eliminar esta tarea?')) return;

            showLoading();
            try {
                await fetch(`${API_URL}/${id}`, {
                    method: 'DELETE'
                });
                loadTasks();
            } catch (err) {
                alert('Error al eliminar tarea.');
            } finally {
                hideLoading();
            }
        }

        async function editTask(id) {
            const res = await fetch(`${API_URL}/${id}`);
            const task = await res.json();
            openModal(task);
        }

        async function completeTask(id) {
            showLoading();
            try {
                await fetch(`${API_URL}/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ completed: 1 })
                });
                loadTasks();
            } catch (err) {
                alert('Error al completar tarea.');
            } finally {
                hideLoading();
            }
        }

        function showLoading() {
            document.getElementById('loadingSpinner').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').classList.add('hidden');
        }
    </script>
</body>
</html>