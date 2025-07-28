CREATE DATABASE IF NOT EXISTS startpage DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE startpage;

DROP TABLE IF EXISTS bookmarks;
DROP TABLE IF EXISTS categories;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    description TEXT,
    favicon_url VARCHAR(255),
    category_id INT,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

INSERT INTO categories (name) VALUES 
('Work'), 
('News'), 
('Development'), 
('Tools');

INSERT INTO bookmarks (title, url, description, favicon_url, category_id, sort_order) VALUES
('Google', 'https://www.google.com', 'Search engine', 'https://www.google.com/favicon.ico', 1, 0),
('YouTube', 'https://www.youtube.com', 'Videos and music', 'https://www.youtube.com/favicon.ico', 1, 1),
('Hacker News', 'https://news.ycombinator.com', 'Tech news and discussion', 'https://news.ycombinator.com/favicon.ico', 2, 0),
('BBC News', 'https://www.bbc.com/news', 'World news', 'https://www.bbc.com/favicon.ico', 2, 1),
('GitHub', 'https://github.com', 'Code hosting', 'https://github.com/favicon.ico', 3, 0),
('Stack Overflow', 'https://stackoverflow.com', 'Programming Q&A', 'https://stackoverflow.com/favicon.ico', 3, 1),
('ChatGPT', 'https://chat.openai.com', 'AI assistant', 'https://chat.openai.com/favicon.ico', 4, 0),
('Canva', 'https://www.canva.com', 'Design tool', 'https://www.canva.com/favicon.ico', 4, 1);