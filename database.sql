CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    avatar_icon VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    author VARCHAR(100) NOT NULL,
    synopsis TEXT,
    genre VARCHAR(100),
    mood VARCHAR(100), -- Analyzed mood for playlist
    cover_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(title, author)
);

CREATE TABLE IF NOT EXISTS user_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    status ENUM('reading', 'completed') DEFAULT 'reading',
    progress INT DEFAULT 0, -- percent
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS characters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    traits JSON, -- Store valid JSON: ["brave", "smart"]
    image_url VARCHAR(255), -- Generated AI Image URL
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS playlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    name VARCHAR(255),
    description TEXT,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    artist VARCHAR(255),
    url VARCHAR(255), -- YouTube or Spotify link
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE
);

-- Gamification
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    points_required INT DEFAULT 0,
    icon_class VARCHAR(50) -- CSS class for icon
);

CREATE TABLE IF NOT EXISTS user_achievements (
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, achievement_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS points_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50), -- e.g., 'book_read', 'playlist_listened'
    points INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Initial Data
INSERT INTO achievements (name, description, points_required, icon_class) VALUES 
('Lector Novato', 'Registra tu primer libro', 10, 'bi-book'),
('Explorador Musical', 'Escucha 3 playlists', 50, 'bi-music-note'),
('Maratón Literaria', 'Completa 3 libros', 100, 'bi-trophy');

INSERT INTO achievements (name, description, points_required, icon_class) VALUES
('Lector Casual', 'Completa 5 libros', 150, 'bi-book-half'),
('Lector Constante', 'Completa 10 libros', 300, 'bi-journals'),
('Lector Voraz', 'Completa 25 libros', 800, 'bi-bookmark-heart'),
('Lector Legendario', 'Completa 50 libros', 1600, 'bi-award'),
('Bibliófilo', 'Completa 100 libros', 3200, 'bi-trophy-fill'),
('Bibliotecario', 'Añade 10 libros', 120, 'bi-journal-plus'),
('Coleccionista', 'Añade 25 libros', 300, 'bi-archive'),
('Explorador de Géneros', 'Lee 5 géneros distintos', 200, 'bi-grid'),
('Maratón Semanal', 'Completa 3 libros', 250, 'bi-speedometer'),
('Maratón Mensual', 'Completa 10 libros', 600, 'bi-speedometer2'),
('Trilogía', 'Completa 3 libros', 180, 'bi-bookmark-star'),
('Saga Máxima', 'Completa 20 libros', 1200, 'bi-bookmark-check'),
('Creador de Playlists', 'Crea 3 playlists', 60, 'bi-collection-play'),
('DJ Literario', 'Crea 10 playlists', 200, 'bi-music-note-list'),
('Melómano', 'Escucha 10 playlists', 200, 'bi-headphones'),
('Personajista', 'Crea 5 personajes', 100, 'bi-person'),
('Elenco Completo', 'Crea 15 personajes', 300, 'bi-people'),
('Mood Explorer', 'Analiza el mood de 10 libros', 150, 'bi-emoji-smile'),
('Autor Fetiche', 'Completa 5 libros', 300, 'bi-person-vcard');
