<?php
// File: /carwash_project/backend/queries/users.php

/**
 * User Management Queries
 * This file contains queries related to user management, including
 * creating, reading, updating, and deleting user records.
 */

require_once '../includes/db.php';

/**
 * Create a new user
 */
function createUser($username, $password, $email) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
    $stmt->execute(['username' => $username, 'password' => password_hash($password, PASSWORD_DEFAULT), 'email' => $email]);
    return $conn->lastInsertId();
}

/**
 * Get user by ID
 */
function getUserById($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
}

/**
 * Update user information
 */
function updateUser($id, $username, $email) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE users SET username = :username, email = :email WHERE id = :id");
    $stmt->execute(['username' => $username, 'email' => $email, 'id' => $id]);
    return $stmt->rowCount();
}

/**
 * Delete a user
 */
function deleteUser($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->rowCount();
}

/**
 * Get all users
 */
function getAllUsers() {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT * FROM users");
    return $stmt->fetchAll();
}
?>