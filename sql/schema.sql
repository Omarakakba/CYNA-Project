-- CYNA — Schéma de base de données
-- Auteur : Omar Akakba
-- Créer la base : CREATE DATABASE cyna_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE cyna_db;

CREATE TABLE user (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,          -- Haché avec password_hash()
    role       ENUM('user', 'admin') DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE category (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE product (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    description TEXT,
    price       DECIMAL(10,2) NOT NULL,
    image       VARCHAR(255),
    category_id INT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES category(id)
);

CREATE TABLE `order` (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    status     ENUM('pending', 'paid', 'shipped', 'cancelled') DEFAULT 'pending',
    total      DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id)
);

CREATE TABLE order_item (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL DEFAULT 1,
    price      DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES `order`(id),
    FOREIGN KEY (product_id) REFERENCES product(id)
);

CREATE TABLE payment (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT NOT NULL UNIQUE,
    stripe_id  VARCHAR(255),
    status     ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    amount     DECIMAL(10,2) NOT NULL,
    paid_at    DATETIME,
    FOREIGN KEY (order_id) REFERENCES `order`(id)
);
