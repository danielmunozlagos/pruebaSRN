# Proyecto CodeIgniter con Docker

Este proyecto utiliza **CodeIgniter 4** y se ejecuta en un entorno **Dockerizado**. A continuación se detallan los pasos para clonar, configurar, levantar el entorno de desarrollo y ejecutar pruebas unitarias.

---

## 🚀 Requisitos

- [Docker](https://www.docker.com/)
- [Docker Compose](https://docs.docker.com/compose/)
- [Composer](https://getcomposer.org/) (solo si necesitas ejecutar comandos localmente fuera del contenedor)

---

## 🔧 Pasos para clonar y levantar el proyecto usando Docker

```bash
# 1. Clonar el repositorio
git clone https://github.com/danielmunozlagos/pruebaSRN.git
cd pruebaSRN

# 2. Crear archivo .env a partir del archivo base
cp env .env

# 3. Revisar credenciales de MySQL en archivo .env desde archivo docker-compose.yml
# Ejemplo por defecto:
# MYSQL_ROOT_PASSWORD: rootpass
# MYSQL_DATABASE: codeigniter_db
# MYSQL_USER: ci_user
# MYSQL_PASSWORD: ci_pass

# 4. Levantar los contenedores
docker compose up -d --build

# 5. Crear base de datos para pruebas
docker exec -it mysql_db mysql -u root -p

# Dentro del cliente MySQL:
# CREATE DATABASE codeigniter_test_db;
# EXIT;

# 6. Instalar dependencias y ejecutar migraciones
docker exec -it codeigniter_app bash
composer install
php spark migrate
```

---

## 🌐 Acceder al Frontend

Una vez levantado el entorno, puedes acceder a la aplicación en tu navegador:

```
http://localhost:8080
```

---

## 🧪 Ejecutar pruebas unitarias

```bash
# 1. Acceder al contenedor de la aplicación
docker exec -it codeigniter_app bash

# 2. Generar archivo phpunit.xml a partir del de ejemplo
cp phpunit.xml.dist phpunit.xml

# 3. Ejecutar migraciones para entorno de pruebas
php spark migrate -n tests

# 4. Ejecutar las pruebas
vendor/bin/phpunit
```

---

## ⚠️ Posibles problemas con permisos

En algunos sistemas (especialmente Linux), al levantar los contenedores pueden generarse problemas de permisos en los directorios `writable/` o `vendor/`, especialmente si el contenedor escribe archivos con un usuario diferente al de tu sistema.

### 🔧 Solución recomendada

Dentro del contenedor de la aplicación:

```bash
docker exec -it codeigniter_app bash
chown -R www-data:www-data writable/
chown -R www-data:www-data vendor/
```

Si persisten los problemas, puedes aplicar permisos más amplios (solo en entornos de desarrollo):

```bash
chmod -R 777 writable/
chmod -R 777 vendor/
```

> ⚠️ **Advertencia:** Evita usar permisos 777 en entornos de producción. Úsalos solo para desarrollo local como solución rápida.

---

## 📁 Estructura del Proyecto (Resumen)

```
├── app/
├── public/
├── tests/
├── .env
├── docker-compose.yml
├── Dockerfile
├── README.md
├── phpunit.xml.dist
├── phpunit.xml
└── ...
```

---

## ✅ Notas adicionales

- Asegúrate de que los puertos **8080** (frontend) y **3306** (MySQL) estén disponibles.
- Puedes modificar las variables del entorno en el archivo `.env` según tus necesidades.
