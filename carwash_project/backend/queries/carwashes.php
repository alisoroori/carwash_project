<?php
// File: /carwash_project/carwash_project/backend/queries/carwashes.php

/**
 * CarWash Queries for Managing Carwash Records
 */

// Include the database connection
require_once '../includes/db.php';

/**
 * Get all carwashes
 */
function getAllCarwashes() {
    $conn = getDBConnection();
    $query = "SELECT * FROM carwashes";
    $stmt = $conn->query($query);
    return $stmt->fetchAll();
}

/**
 * Get a carwash by ID
 */
function getCarwashById($id) {
    $conn = getDBConnection();
    $query = "SELECT * FROM carwashes WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
}

/**
 * Add a new carwash
 */
function addCarwash($data) {
    $conn = getDBConnection();
    $query = "INSERT INTO carwashes (name, location, services, contact) VALUES (:name, :location, :services, :contact)";
    $stmt = $conn->prepare($query);
    return $stmt->execute($data);
}

/**
 * Update an existing carwash
 */
function updateCarwash($id, $data) {
    $conn = getDBConnection();
    $query = "UPDATE carwashes SET name = :name, location = :location, services = :services, contact = :contact WHERE id = :id";
    $stmt = $conn->prepare($query);
    $data['id'] = $id;
    return $stmt->execute($data);
}

/**
 * Delete a carwash
 */
function deleteCarwash($id) {
    $conn = getDBConnection();
    $query = "DELETE FROM carwashes WHERE id = :id";
    $stmt = $conn->prepare($query);
    return $stmt->execute(['id' => $id]);
}
?>