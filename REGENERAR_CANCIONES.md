# 🔧 Guía Rápida: Regenerar Canciones Únicas

## ⚠️ PROBLEMA

Las canciones que ves ahora fueron generadas con el **código antiguo** y están guardadas en la base de datos. El nuevo código mejorado **NO se aplicará** hasta que borres las canciones existentes.

## ✅ SOLUCIÓN (Elige una opción)

### **Opción 1: Desde phpMyAdmin (MÁS FÁCIL)**

1. Abre **phpMyAdmin**
2. Selecciona la base de datos `bookvibes`
3. Ve a la pestaña **SQL**
4. Copia y pega este comando:
   ```sql
   DELETE FROM songs WHERE is_ai_generated = 1;
   ```
5. Haz clic en **Continuar**
6. ✅ ¡Listo! Ahora ve a cualquier libro y las canciones se regenerarán

### **Opción 2: Desde línea de comandos**

```bash
cd c:\Users\marty\OneDrive\Escritorio\bookVibes\BookVibes
php regenerate_songs.php
```

Luego escribe `s` y presiona Enter.

### **Opción 3: SQL directo**

```bash
mysql -u root -p bookvibes -e "DELETE FROM songs WHERE is_ai_generated = 1;"
```

## 📋 Verificación

Después de borrar las canciones:

1. **Ve a la página de un libro** (cualquiera)
2. Las canciones se regenerarán automáticamente
3. **Busca otro libro diferente**
4. Verás que las canciones son **completamente diferentes**

## 🎯 Ejemplo de lo que verás

### Libro 1: "Cien Años de Soledad"
- Canción 1: "Reflejos de Macondo" (voz masculina)
- Canción 2: "Si esperanza llega a Macondo" (voz femenina)

### Libro 2: "Harry Potter"
- Canción 1: "Sueños de Harry" (voz masculina)  
- Canción 2: "Sinfonía de Misterio" (voz femenina)

**¡Completamente diferentes!**

## ❓ ¿Por qué necesito hacer esto?

El nuevo código con semillas determinísticas y 133+ plantillas **ya está instalado**, pero las canciones antiguas están en la base de datos. Al borrarlas:

1. El sistema detecta que no hay canciones AI
2. Llama al nuevo código mejorado
3. Genera canciones únicas basadas en el título del libro
4. Las guarda en la base de datos

## 🚨 Importante

**NO** necesitas borrar las canciones de YouTube/Spotify, solo las AI. El comando solo borra canciones donde `is_ai_generated = 1`.

## 📞 Si tienes problemas

1. Verifica que ejecutaste la migración:
   ```bash
   mysql -u root -p bookvibes < migrations\add_ai_song_fields.sql
   ```

2. Verifica que el campo existe:
   ```sql
   DESCRIBE songs;
   ```
   Deberías ver `is_ai_generated` en la lista.

3. Si no existe, ejecuta la migración primero.

---

**¡Ejecuta el DELETE y prueba con 2-3 libros diferentes!** 🎵
