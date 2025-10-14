<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\queries\reviews.php

require_once '../includes/db.php';

/**
 * Add a new review
 */
function addReview($userId, $carwashId, $rating, $comment)
{
    $conn = getDBConnection();
    $query = "INSERT INTO reviews (user_id, carwash_id, rating, comment, created_at) VALUES (:user_id, :carwash_id, :rating, :comment, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':user_id' => $userId,
        ':carwash_id' => $carwashId,
        ':rating' => $rating,
        ':comment' => $comment
    ]);
    return $conn->lastInsertId();
}

/**
 * Get all reviews for a specific carwash
 */
function getReviewsByCarwash($carwashId)
{
    $conn = getDBConnection();
    $query = "SELECT * FROM reviews WHERE carwash_id = :carwash_id ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([':carwash_id' => $carwashId]);
    return $stmt->fetchAll();
}

/**
 * Update a review
 */
function updateReview($reviewId, $rating, $comment)
{
    $conn = getDBConnection();
    $query = "UPDATE reviews SET rating = :rating, comment = :comment WHERE id = :review_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':review_id' => $reviewId,
        ':rating' => $rating,
        ':comment' => $comment
    ]);
    return $stmt->rowCount();
}

/**
 * Delete a review
 */
function deleteReview($reviewId)
{
    $conn = getDBConnection();
    $query = "DELETE FROM reviews WHERE id = :review_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':review_id' => $reviewId]);
    return $stmt->rowCount();
}

/**
 * Get a review by its ID
 */
function getReviewById($reviewId)
{
    $conn = getDBConnection();
    $query = "SELECT * FROM reviews WHERE id = :review_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':review_id' => $reviewId]);
    return $stmt->fetch();
}
?>