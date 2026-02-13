# 📋 Protocolo de Activación Masiva - Inyección de Datos

## 🚀 Script de Inyección Masiva

**Archivo creado:** `inyectar-datos.php` (raíz de WordPress)

Este script pobla masivamente las taxonomías jerárquicas con datos de `municipios-cuba.json`.

## 📋 Hoja de Ruta para la Activación de Menús

### Paso 1: Ejecución

1. Abra su navegador
2. Cargue la URL: `https://telovendocuba.com/inyectar-datos.php`
3. El script procesará automáticamente todos los datos del JSON

**Requisitos:**
- Debe estar logueado como administrador
- El archivo `municipios-cuba.json` debe existir
- La taxonomía `ubicacion_cubana` debe estar registrada

### Paso 2: Verificación

1. Vaya a su panel de WordPress
2. Propiedades → Añadir nueva
3. Busque la sección **"Ubicaciones Cuba"**
4. Presione **F5** para refrescar

### Paso 3: Validación de Menús

Al dar clic en las ventanas de selección, debería ver:

- **Provincia (Nivel 1):**
  - La Habana
  - Pinar del Río
  - Santiago de Cuba
  - Varadero
  - Trinidad
  - etc.

- **Municipio (Nivel 2, hijo de Provincia):**
  - La Habana → La Habana
  - Pinar del Río → Pinar del Río, Viñales
  - etc.

- **Barrio/Reparto (Nivel 3, hijo de Municipio):**
  - La Habana → La Habana → Vedado, Centro Habana, Habana Vieja, Miramar, etc.
  - etc.

### Paso 4: Seguridad ⚠️

**IMPORTANTE:** Borre el archivo `inyectar-datos.php` después de usarlo para mantener limpio su entorno modular.

```bash
# Desde la terminal o FTP
rm inyectar-datos.php
```

## 📊 Monitor de Estatus Post-Inyección

| Ventana de Menú | Estado Esperado | Función Tecnológica |
|-----------------|-----------------|---------------------|
| **Provincia** | Poblado | Filtro regional de propiedades |
| **Municipio** | Poblado | Localización exacta para el GPS |
| **Barrio/Reparto** | Poblado | Búsqueda por barrios famosos y SEO |

## 🔍 Características del Script

### Funcionalidades:

1. **Inserción Segura:**
   - Verifica si el término ya existe antes de insertar
   - Evita duplicados
   - Mantiene jerarquía correcta

2. **Manejo de Errores:**
   - Muestra errores específicos
   - Continúa procesando aunque haya errores
   - Reporte detallado al finalizar

3. **Interfaz Visual:**
   - Muestra progreso en tiempo real
   - Resumen con contadores
   - Instrucciones claras

### Estructura de Datos Procesados:

```
Provincia (Nivel 1)
  └── Municipio (Nivel 2)
      └── Barrio/Reparto (Nivel 3)
```

### Ejemplo de Salida:

```
✓ Provincia: La Habana (ID: 1)
  ✓ Municipio: La Habana (ID: 2, Padre: La Habana)
    ✓ Barrio: Vedado (ID: 3, Padre: La Habana)
    ✓ Barrio: Centro Habana (ID: 4, Padre: La Habana)
    ✓ Barrio: Habana Vieja (ID: 5, Padre: La Habana)
    ✓ Barrio: Miramar (ID: 6, Padre: La Habana)
    ...
```

## ✅ Verificación Post-Inyección

### Checklist:

- [ ] Script ejecutado sin errores
- [ ] Provincias visibles en panel de WordPress
- [ ] Municipios aparecen como hijos de provincias
- [ ] Barrios aparecen como hijos de municipios
- [ ] Jerarquía correcta en ventanas de selección
- [ ] Archivo `inyectar-datos.php` eliminado

## 🛠️ Solución de Problemas

### Error: "No tienes permisos"
**Solución:** Asegúrate de estar logueado como administrador

### Error: "No se encontró el archivo municipios-cuba.json"
**Solución:** Verifica que el archivo existe en `src-telovendo/custom-modules/municipios-cuba.json`

### Error: "La taxonomía 'ubicacion_cubana' no existe"
**Solución:** Asegúrate de que el motor de propiedades esté activo en `functions.php`

### Términos duplicados
**Solución:** El script verifica duplicados automáticamente. Si ya existen, no los inserta de nuevo.

## 🎯 Resultado Final

Una vez completada la inyección:

- ✅ Todas las provincias de Cuba están disponibles
- ✅ Todos los municipios están vinculados a sus provincias
- ✅ Todos los barrios/repartos están vinculados a sus municipios
- ✅ La jerarquía está correctamente establecida
- ✅ Las propiedades pueden ser clasificadas con precisión quirúrgica

**El sistema está listo para recibir propiedades con ubicaciones exactas.**

