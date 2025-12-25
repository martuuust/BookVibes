# Guía Rápida de Instalación - Mejoras de Canciones IA

## 🚀 Pasos de Instalación

### 1. Actualizar Base de Datos

Ejecuta la migración para añadir los nuevos campos:

**Opción A - Desde línea de comandos:**
```bash
cd c:\Users\marty\OneDrive\Escritorio\bookVibes\BookVibes
mysql -u root -p bookvibes < migrations\add_ai_song_fields.sql
```

**Opción B - Desde phpMyAdmin:**
1. Abre phpMyAdmin
2. Selecciona la base de datos `bookvibes`
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido de `migrations/add_ai_song_fields.sql`
5. Haz clic en "Continuar"

### 2. Configurar Variables de Entorno (Opcional)

Para generar canciones reales con audio, necesitas una API key de Suno AI.

**Opción A - Configurar en Windows:**
```cmd
setx SUNO_API_KEY "tu_api_key_aqui"
setx SUNO_API_ENDPOINT "https://api.sunoapi.org/api/v1"
```

**Opción B - Configurar en php.ini:**
Añade estas líneas al final de tu `php/php.ini`:
```ini
; Suno AI Configuration
SUNO_API_KEY=tu_api_key_aqui
SUNO_API_ENDPOINT=https://api.sunoapi.org/api/v1
```

**Opción C - Configurar en Apache (si usas Apache):**
Añade al `.htaccess` o `httpd.conf`:
```apache
SetEnv SUNO_API_KEY "tu_api_key_aqui"
SetEnv SUNO_API_ENDPOINT "https://api.sunoapi.org/api/v1"
```

### 3. Obtener API Key (Opcional pero Recomendado)

Para generar canciones reales:

1. **Visita uno de estos proveedores:**
   - [SunoAPI.org](https://sunoapi.org) - Más popular
   - [MusicAPI.ai](https://musicapi.ai) - Más características
   - [Apiframe.ai](https://apiframe.ai) - Más económico

2. **Regístrate** y obtén tu API key

3. **Configura** la API key como se indica arriba

### 4. Reiniciar Servidor

Después de configurar las variables de entorno:

```bash
# Si usas start.bat
# Cierra la ventana actual y ejecuta de nuevo:
start.bat

# O reinicia manualmente PHP
```

## ✅ Verificación

### Probar sin API Key (Modo Demo)

1. Busca un libro nuevo
2. Ve a la página del libro
3. Verás 2 canciones AI generadas con:
   - ✅ Títulos únicos
   - ✅ Letras completas
   - ✅ Descripciones de melodía
   - ✅ Información de voz (masculina/femenina)
   - ❌ Sin audio real (URL será '#')

### Probar con API Key (Modo Completo)

1. Configura la API key
2. Reinicia el servidor
3. Busca un libro nuevo
4. Las canciones tendrán:
   - ✅ Todo lo anterior
   - ✅ URL de audio real
   - ✅ Duración real (2-3 minutos)
   - ✅ Archivo MP3/WAV reproducible

## 🎯 Características Nuevas

### Para Cada Libro

- **2 canciones únicas** generadas automáticamente
- **Voz masculina** en la primera canción
- **Voz femenina** en la segunda canción
- **Letras personalizadas** basadas en el libro
- **Duración mínima de 2 minutos**
- **Estilo musical** acorde al mood del libro

### Estilos por Mood

- **Melancolía**: indie folk, sad piano ballad
- **Alegría**: upbeat pop, happy indie
- **Misterio**: dark ambient, mysterious electronic
- **Romance**: romantic ballad, love song
- **Aventura**: epic orchestral, cinematic
- **Terror**: horror ambient, dark electronic
- **Fantasía**: fantasy orchestral, magical folk
- **Ciencia Ficción**: synthwave, futuristic electronic

## 🔍 Solución de Problemas

### Las canciones no aparecen

1. Verifica que la migración se ejecutó correctamente:
   ```sql
   DESCRIBE songs;
   -- Deberías ver los nuevos campos: is_ai_generated, lyrics, etc.
   ```

2. Verifica que eres usuario Pro:
   ```sql
   SELECT account_type FROM users WHERE id = TU_USER_ID;
   -- Debe ser 'Pro'
   ```

### Error de API

1. Verifica que la API key es correcta
2. Comprueba que curl está habilitado en PHP:
   ```bash
   php -m | findstr curl
   ```

3. Revisa los logs de error de PHP

### Las canciones tienen URL '#'

Esto es normal si:
- No has configurado una API key (modo demo)
- La API falló (revisa logs)
- Es la primera vez que se genera (puede tardar)

## 📊 Estructura de Datos

Cada canción generada incluye:

```php
[
    'title' => 'Ecos de Macondo',
    'artist' => 'BookVibes AI',
    'url' => 'https://...' o '#',
    'is_ai_generated' => 1,
    'lyrics' => '[Intro]\n...',
    'melody_description' => 'Canción completa de 2-3 minutos...',
    'duration' => 135, // segundos
    'voice_gender' => 'male' o 'female',
    'music_style' => 'indie folk',
    'status' => 'active' o 'pending_generation'
]
```

## 🎉 ¡Listo!

Ahora cada libro tendrá su propia banda sonora personalizada. Las canciones se generan automáticamente cuando:

1. Buscas un libro nuevo
2. Eres usuario Pro
3. El libro no tiene canciones AI todavía

Para más información, consulta `docs/AI_SONG_IMPROVEMENTS.md`
