CREATE DATABASE IF NOT EXISTS startpage DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE startpage;

DROP TABLE IF EXISTS bookmarks;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS pages;

CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    page_id INT,
    sort_order INT DEFAULT 0,
    preferences VARCHAR(200) DEFAULT '{"cat_width": 3, "no_descr": 0, "show_fav": 1}',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);

CREATE TABLE bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(200) NOT NULL,
    description VARCHAR(200),
    favicon_url VARCHAR(255),
    category_id INT,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Insert default page
INSERT INTO pages (name, sort_order) VALUES ('My Startpage', 0);

-- Insert categories (now associated with the default page)
INSERT INTO categories (name, page_id, sort_order) VALUES 
('Work', 1, 0), 
('News', 1, 1), 
('Development', 1, 2), 
('Tools', 1, 3);

INSERT INTO bookmarks (title, url, description, favicon_url, category_id, sort_order) VALUES
('Google', 'https://www.google.com', 'Search engine', 'https://www.google.com/favicon.ico', 1, 0),
('YouTube', 'https://www.youtube.com', 'Videos and music', 'https://www.youtube.com/favicon.ico', 1, 1),
('Hacker News', 'https://news.ycombinator.com', 'Tech news and discussion', 'https://news.ycombinator.com/favicon.ico', 2, 0),
('BBC News', 'https://www.bbc.com/news', 'World news', 'https://www.bbc.com/favicon.ico', 2, 1),
('GitHub', 'https://github.com', 'Code hosting', 'https://github.com/favicon.ico', 3, 0),
('Stack Overflow', 'https://stackoverflow.com', 'Programming Q&A', 'https://stackoverflow.com/favicon.ico', 3, 1),
('ChatGPT', 'https://chat.openai.com', 'AI assistant', 'https://chat.openai.com/favicon.ico', 4, 0),
('Canva', 'https://www.canva.com', 'Design tool', 'https://www.canva.com/favicon.ico', 4, 1);