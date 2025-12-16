<div class="container mt-5">
    <h1>Subir Nuevo Libro</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="/books/storeUpload" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="book_file" class="form-label">Archivo del Libro (PDF, EPUB, MOBI)</label>
            <input type="file" class="form-control" id="book_file" name="book_file" required>
        </div>
        <div class="mb-3">
            <label for="title" class="form-label">Título</label>
            <input type="text" class="form-control" id="title" name="title" placeholder="Título del libro">
        </div>
        <div class="mb-3">
            <label for="author" class="form-label">Autor</label>
            <input type="text" class="form-control" id="author" name="author" placeholder="Autor del libro">
        </div>
        <div class="mb-3">
            <label for="synopsis" class="form-label">Sinopsis</label>
            <textarea class="form-control" id="synopsis" name="synopsis" rows="3" placeholder="Breve descripción del libro"></textarea>
        </div>
        <div class="mb-3">
            <label for="genre" class="form-label">Género</label>
            <input type="text" class="form-control" id="genre" name="genre" placeholder="Género del libro">
        </div>
        <div class="mb-3">
            <label for="mood" class="form-label">Estado de ánimo</label>
            <input type="text" class="form-control" id="mood" name="mood" placeholder="Estado de ánimo principal del libro">
        </div>
        <div class="mb-3">
            <label for="cover_url" class="form-label">URL de la Portada (opcional)</label>
            <input type="url" class="form-control" id="cover_url" name="cover_url" placeholder="URL de la imagen de portada">
        </div>
        <button type="submit" class="btn btn-primary">Subir Libro</button>
    </form>
</div>