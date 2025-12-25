# Mejoras en la Generación de Canciones con IA

## 📋 Resumen de Mejoras

Se ha mejorado completamente el sistema de generación de canciones con IA para BookVibes. Ahora las canciones son:

✅ **Únicas y personalizadas** - Cada libro tiene canciones completamente diferentes
✅ **Con letra cantada** - Voces masculinas y femeninas alternadas
✅ **Duración mínima de 2 minutos** - Canciones completas, no fragmentos
✅ **Temática acorde al libro** - Letras y estilo musical basados en el mood y contenido
✅ **No se repiten** - Sistema de generación único por libro

## 🎵 Características Principales

### 1. Generación de Letras Inteligente

- **Extracción de palabras clave** del sinopsis del libro
- **Identificación de protagonistas** y locaciones
- **Detección de temas** (amor, muerte, guerra, libertad, etc.)
- **Análisis emocional** (esperanza, miedo, pasión, etc.)
- **Estructuras variadas**: 
  - Estructura 1: Intro → Verso → Coro → Verso → Coro → Puente → Coro → Outro
  - Estructura 2: Verso → Pre-Coro → Coro → Verso → Pre-Coro → Coro → Puente → Coro
  - Estructura 3: Intro → Verso → Verso → Coro → Verso → Puente → Coro Final → Outro

### 2. Voces Alternadas

- **Primera canción**: Voz masculina
- **Segunda canción**: Voz femenina
- Alterna automáticamente para mayor variedad

### 3. Estilos Musicales por Mood

Cada mood tiene estilos musicales específicos:

- **Melancolía**: indie folk, sad piano ballad, melancholic acoustic, emotional pop
- **Alegría**: upbeat pop, happy indie, cheerful acoustic, feel-good rock
- **Misterio**: dark ambient, mysterious electronic, suspenseful orchestral, noir jazz
- **Romance**: romantic ballad, love song, soft pop, acoustic romance
- **Aventura**: epic orchestral, adventure rock, cinematic, heroic anthem
- **Terror**: horror ambient, dark electronic, creepy orchestral, nightmare pop
- **Fantasía**: fantasy orchestral, magical folk, ethereal dream pop, mystical
- **Ciencia Ficción**: synthwave, futuristic electronic, sci-fi ambient, cyberpunk

### 4. Títulos Únicos

Sistema de 4 patrones para generar títulos únicos:
1. **Basado en personajes**: "Ecos de [Protagonista]", "Sombras en [Lugar]"
2. **Basado en mood**: "El Ritmo del [Mood]", "Memorias de [Mood]"
3. **Basado en temas**: "Entre [Tema]", "Más Allá de [Lugar]"
4. **Referencia directa**: "La Esencia de [Libro]", "[Título Corto]"

## 🔧 Integración con Suno AI API

### Opción 1: Con API Key (Recomendado para Producción)

Para generar canciones reales con audio:

1. **Obtén una API key** de uno de estos proveedores:
   - [SunoAPI.org](https://sunoapi.org)
   - [MusicAPI.ai](https://musicapi.ai)
   - [Apiframe.ai](https://apiframe.ai)

2. **Configura tu .env**:
   ```env
   SUNO_API_KEY=tu_api_key_aqui
   SUNO_API_ENDPOINT=https://api.sunoapi.org/api/v1
   ```

3. **Reinicia la aplicación**

Con la API configurada, las canciones se generarán con:
- Audio real en formato MP3/WAV
- Voces cantadas por IA (masculina/femenina)
- Duración de 2-3 minutos
- Producción profesional

### Opción 2: Sin API Key (Modo Demo)

Sin API key, el sistema genera:
- ✅ Letras completas y personalizadas
- ✅ Descripciones detalladas de melodía
- ✅ Metadata completa (título, artista, estilo, voz)
- ❌ No genera archivos de audio reales
- Estado: `pending_generation`

## 📊 Estructura de Base de Datos

Se han añadido nuevos campos a la tabla `songs`:

```sql
- is_ai_generated (TINYINT) - Indica si es generada por IA
- lyrics (TEXT) - Letra completa de la canción
- melody_description (TEXT) - Descripción detallada de la melodía
- duration (INT) - Duración en segundos
- voice_gender (VARCHAR) - 'male' o 'female'
- music_style (VARCHAR) - Estilo musical (ej: 'indie folk')
- generation_id (VARCHAR) - ID de generación de Suno API
- status (VARCHAR) - 'active', 'pending_generation', 'failed'
```

### Migración de Base de Datos

Para actualizar tu base de datos existente:

```bash
# Opción 1: Desde línea de comandos
mysql -u tu_usuario -p bookvibes < migrations/add_ai_song_fields.sql

# Opción 2: Desde phpMyAdmin
# Importa el archivo migrations/add_ai_song_fields.sql
```

## 🎯 Cómo Funciona

### Proceso de Generación

1. **Análisis del Libro**
   - Extrae título, autor, sinopsis, mood, género
   - Identifica palabras clave (protagonistas, lugares, temas)

2. **Generación de Letras**
   - Selecciona plantillas según el mood
   - Rellena variables con datos del libro
   - Crea estructura única (intro, versos, coros, puente, outro)

3. **Determinación de Estilo**
   - Mapea mood a estilos musicales
   - Considera el género del libro
   - Alterna estilos entre canciones

4. **Asignación de Voz**
   - Primera canción: voz masculina
   - Segunda canción: voz femenina

5. **Llamada a API (si está configurada)**
   - Envía letra, estilo, voz a Suno AI
   - Espera generación (hasta 2 minutos)
   - Recibe URL de audio generado

6. **Almacenamiento**
   - Guarda metadata en base de datos
   - Asocia con playlist del libro

### Ejemplo de Canción Generada

**Libro**: "Cien Años de Soledad" - Gabriel García Márquez
**Mood**: Melancolía
**Género**: Realismo Mágico

**Canción 1**:
- **Título**: "Ecos de Macondo"
- **Voz**: Masculina
- **Estilo**: indie folk
- **Duración**: 2:15
- **Letra** (extracto):
  ```
  [Intro]
  En el silencio de Macondo
  
  [Verso 1]
  En las sombras de Macondo, Aureliano camina solo
  El eco de la soledad resuena en la noche
  Entre páginas de Cien Años de Soledad, se esconde el destino
  
  [Coro]
  Oh Cien Años de Soledad, tu historia vive en mí
  Volando hacia la verdad, sin mirar atrás
  Melancolía es el camino, la soledad es el final
  ```

**Canción 2**:
- **Título**: "Memorias de Melancolía"
- **Voz**: Femenina
- **Estilo**: sad piano ballad
- **Duración**: 2:30

## 🚀 Ventajas del Nuevo Sistema

### Para Usuarios Pro

1. **Experiencia Única**: Cada libro tiene su propia banda sonora personalizada
2. **Calidad Profesional**: Canciones completas con producción de estudio
3. **Variedad**: Voces masculinas y femeninas, múltiples estilos
4. **Inmersión**: Letras que reflejan la historia del libro

### Para Desarrolladores

1. **Modular**: Fácil de extender con nuevos moods o estilos
2. **Configurable**: API endpoint y key en variables de entorno
3. **Fallback**: Funciona sin API (modo demo)
4. **Escalable**: Preparado para múltiples proveedores de IA

## 📝 Notas Técnicas

### Prevención de Duplicados

- **Hash de libro**: Se usa MD5 del título para unicidad
- **Índice de variación**: Cada canción usa un índice diferente
- **Plantillas rotativas**: 4 patrones de títulos, 3 estructuras de letras
- **Selección aleatoria**: Dentro de cada plantilla, selección aleatoria

### Optimización

- **Timeout de API**: 120 segundos máximo
- **Manejo de errores**: Fallback a metadata si API falla
- **Caché**: Canciones se guardan en BD, no se regeneran
- **Índices**: Búsquedas rápidas por `is_ai_generated` y `status`

### Limitaciones Actuales

1. **Sin API oficial de Suno**: Usamos APIs de terceros
2. **Costo**: APIs de terceros pueden tener costo por canción
3. **Tiempo de generación**: 30-120 segundos por canción
4. **Idioma**: Letras en español, pero IA puede pronunciar con acento

## 🔮 Futuras Mejoras

- [ ] Integración con Udio AI como alternativa
- [ ] Generación asíncrona con cola de trabajos
- [ ] Caché de canciones por mood/género
- [ ] Personalización de voz (tono, estilo)
- [ ] Soporte multiidioma
- [ ] Visualizador de letras sincronizado
- [ ] Descarga de canciones en MP3
- [ ] Compartir canciones en redes sociales

## 📞 Soporte

Para problemas o preguntas:
1. Revisa los logs en `error_log` de PHP
2. Verifica que la migración de BD se ejecutó correctamente
3. Confirma que la API key es válida (si usas una)
4. Comprueba que `curl` está habilitado en PHP

## 🎉 ¡Disfruta de tu Música Personalizada!

Cada libro ahora tiene su propia banda sonora única, creada específicamente para capturar su esencia y mood. ¡Feliz lectura y escucha!
