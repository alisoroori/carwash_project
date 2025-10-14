<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\queries\services.php

/**
 * CarWash Services Queries
 * This file contains CRUD operations for managing carwash services.
 */

require_once '../includes/db.php';

/**
 * Get all services
 */
function getAllServices() {
    $conn = getDBConnection();
    $query = "SELECT * FROM services";
    $stmt = $conn->query($query);
    return $stmt->fetchAll();
}

/**
 * Get service by ID
 */
function getServiceById($id) {
    $conn = getDBConnection();
    $query = "SELECT * FROM services WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
}

/**
 * Create a new service
 */
function createService($name, $description, $price) {
    $conn = getDBConnection();
    $query = "INSERT INTO services (name, description, price) VALUES (:name, :description, :price)";
    $stmt = $conn->prepare($query);
    return $stmt->execute(['name' => $name, 'description' => $description, 'price' => $price]);
}

/**
 * Update an existing service
 */
function updateService($id, $name, $description, $price) {
    $conn = getDBConnection();
    $query = "UPDATE services SET name = :name, description = :description, price = :price WHERE id = :id";
    $stmt = $conn->prepare($query);
    return $stmt->execute(['id' => $id, 'name' => $name, 'description' => $description, 'price' => $price]);
}

/**
 * Delete a service
 */
function deleteService($id) {
    $conn = getDBConnection();
    $query = "DELETE FROM services WHERE id = :id";
    $stmt = $conn->prepare($query);
    return $stmt->execute(['id' => $id]);
}
?>