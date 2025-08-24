# 🚀 Descripción
<!-- Explica qué hace este PR y por qué -->

- [ ] Nueva funcionalidad
- [ ] Fix de bug
- [ ] Otro (especificar)

---

# ✅ Checklist de desarrollo
- [ ] Código compilado sin errores en Docker (docker compose up)
- [ ] Migraciones aplicadas correctamente (php spark migrate)
- [ ] Endpoints probados con curl
- [ ] Tests unitarios pasaron (vendor/bin/phpunit)
- [ ] Documentación actualizada (README/CHANGELOG si aplica)

---

# 🔗 Evidencias

## Endpoints probados (cURL)
- Login: curl -X POST http://localhost:8080/login -H "Content-Type: application/json" -d '{"email":"test@test.com","password":"1234"}'
- Listar tareas: curl http://localhost:8080/tasks
- Crear tarea: curl -X POST http://localhost:8080/tasks -H "Content-Type: application/json" -d '{"title":"Nueva tarea"}'
- Actualizar tarea: curl -X PUT http://localhost:8080/tasks/1 -H "Content-Type: application/json" -d '{"title":"Tarea editada","completed":true}'
- Eliminar tarea: curl -X DELETE http://localhost:8080/tasks/1

## Tests unitarios
- Ejecutar: vendor/bin/phpunit
- Resultado esperado: OK (X tests, Y assertions)

---

# 📸 Screenshots / Logs
(si aplica)
