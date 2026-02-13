# 🚀 Guía de Activación: Jicotea-Genio

## 📊 Monitor de Activación Final

| Requisito | Estado | Acción |
|-----------|--------|--------|
| **Archivos Sincronizados** | ✅ OK | Ninguna |
| **Motor de Datos Registrado** | ✅ OK | Ya incluido en `functions.php` |
| **Llamada en Footer** | ✅ OK | Código insertado en `footer.php` |
| **Propiedades de Venta** | ⚠️ Pendiente | Crear primer anuncio en el panel |

## 🛠️ Paso 1: Activar el Motor de Datos ✅

**Estado: COMPLETADO**

El archivo `jicotea-data-engine.php` ya está registrado en `functions.php`:

```php
// Cargar el motor de datos RAG para Jicotea-Genio
require_once get_stylesheet_directory() . '/../../src-telovendo/custom-modules/jicotea-data-engine.php';
```

**Ubicación del archivo:**
- `src-telovendo/custom-modules/jicotea-data-engine.php`

**Verificación:**
- ✅ Archivo existe
- ✅ Incluido en `functions.php`
- ✅ REST API endpoint: `/wp-json/jicotea/v1/properties`

## 🛠️ Paso 2: Inyectar Jicotea en el Footer ✅

**Estado: COMPLETADO**

El código ya está insertado en `footer.php`:

```php
<div id="jicotea-ia-anchor">
    <img src="<?php echo get_stylesheet_directory_uri(); ?>/src-telovendo/assets/img/chatbot-jicotea.png" class="jicotea-genio-ia" alt="Jicotea-Genio">
</div>
<script src="<?php echo get_stylesheet_directory_uri(); ?>/src-telovendo/assets/js/jicotea-chat.js" defer async></script>
```

**Ubicación del archivo:**
- `src-telovendo/theme-logic/footer.php`

**Rutas configuradas:**
- ✅ Avatar: `/src-telovendo/assets/img/chatbot-jicotea.png`
- ✅ Script: `/src-telovendo/assets/js/jicotea-chat.js`
- ✅ Estilos: `/src-telovendo/assets/css/jicotea-chat.css`

## 🛠️ Paso 3: Crear el Contenido ⚠️

**Estado: PENDIENTE - Acción Requerida**

Para que Jicotea-Genio tenga propiedades que mostrar, necesitas crear contenido en WordPress:

### Instrucciones:

1. **Ir al Escritorio de WordPress**
   - Acceder a `/wp-admin`

2. **Buscar la sección "Propiedades"**
   - En el menú lateral, buscar "Propiedades Cuba"
   - O ir a: Propiedades → Añadir nueva

3. **Crear una propiedad de prueba**
   - **Título**: "Casa mística en Santiago"
   - **Municipio**: Santiago de Cuba (campo ACF `municipio_cuba`)
   - **Precio**: 15000 (campo ACF `precio_usd`)
   - **Tipo de Operación**: Venta / Renta / Permuta
   - **Publicar**

4. **Verificar campos ACF**
   Asegúrate de tener estos campos personalizados:
   - `precio_usd` (Number)
   - `municipio_cuba` (Text)
   - `provincia_cuba` (Text) - Opcional

## ✅ Verificación Final

### Checklist de Activación:

- [x] Motor de datos registrado en `functions.php`
- [x] Footer actualizado con código de Jicotea
- [x] Rutas de archivos correctas
- [x] Estilos CSS cargados
- [x] Script JavaScript cargado
- [ ] **Propiedades creadas en WordPress** ← Acción pendiente

### Prueba de Funcionamiento:

1. **Verificar que el avatar aparece**
   - Debe aparecer en la esquina inferior izquierda
   - Con animación de levitación

2. **Probar el chat**
   - Hacer clic en el avatar
   - Debe abrirse la ventana de chat estilo WhatsApp

3. **Probar búsqueda de propiedades**
   - Escribir: "busco casa en Santiago"
   - Debe mostrar las propiedades creadas

4. **Verificar REST API**
   - Visitar: `/wp-json/jicotea/v1/properties`
   - Debe devolver JSON con las propiedades

## 🎯 Siguiente Paso

**Crear tu primera propiedad en WordPress** para que Jicotea-Genio tenga contenido que mostrar y vender.

Una vez creada la propiedad, Jicotea-Genio podrá:
- ✅ Mostrar propiedades disponibles
- ✅ Contar historias de piratas según el municipio
- ✅ Sugerir puntos de interés cercanos
- ✅ Cerrar ventas con humor cubano

