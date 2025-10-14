<?php
// File: /carwash_project/carwash_project/backend/queries/settings.php

/**
 * Settings Queries for CarWash Project
 * This file contains queries related to application settings,
 * allowing for the retrieval and updating of configuration options.
 */

include_once '../includes/db.php';

/**
 * Get all settings
 * Returns an associative array of all settings from the database.
 */
function getAllSettings()
{
    $conn = getDBConnection();
    $query = "SELECT * FROM settings";
    $stmt = $conn->query($query);
    return $stmt->fetchAll();
}

/**
 * Get a specific setting by key
 * @param string $key
 * @return mixed
 */
function getSetting($key)
{
    $conn = getDBConnection();
    $query = "SELECT value FROM settings WHERE `key` = :key";
    $stmt = $conn->prepare($query);
    $stmt->execute(['key' => $key]);
    return $stmt->fetchColumn();
}

/**
 * Update a specific setting by key
 * @param string $key
 * @param mixed $value
 * @return bool
 */
function updateSetting($key, $value)
{
    $conn = getDBConnection();
    $query = "UPDATE settings SET value = :value WHERE `key` = :key";
    $stmt = $conn->prepare($query);
    return $stmt->execute(['key' => $key, 'value' => $value]);
}

/**
 * Add a new setting
 * @param string $key
 * @param mixed $value
 * @return bool
 */
function addSetting($key, $value)
{
    $conn = getDBConnection();
    $query = "INSERT INTO settings (`key`, value) VALUES (:key, :value)";
    $stmt = $conn->prepare($query);
    return $stmt->execute(['key' => $key, 'value' => $value]);
}

/**
 * Delete a setting by key
 * @param string $key
 * @return bool
 */
function deleteSetting($key)
{
    $conn = getDBConnection();
    $query = "DELETE FROM settings WHERE `key` = :key";
    $stmt = $conn->prepare($query);
    return $stmt->execute(['key' => $key]);
}
?>