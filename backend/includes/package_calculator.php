<?php
class PackageCalculator
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function calculatePackagePrice($packageId, $date)
    {
        // Get base package info
        $package = $this->getPackageDetails($packageId);
        $basePrice = $package['price'];

        // Apply time-based discounts
        $timeDiscount = $this->getTimeBasedDiscount($date);

        // Apply loyalty discount if applicable
        $loyaltyDiscount = $this->getLoyaltyDiscount($_SESSION['user_id']);

        // Calculate final price
        $finalPrice = $basePrice;
        $finalPrice *= (1 - ($package['discount_percentage'] / 100));
        $finalPrice *= (1 - $timeDiscount);
        $finalPrice *= (1 - $loyaltyDiscount);

        return [
            'base_price' => $basePrice,
            'package_discount' => $package['discount_percentage'],
            'time_discount' => $timeDiscount * 100,
            'loyalty_discount' => $loyaltyDiscount * 100,
            'final_price' => $finalPrice
        ];
    }

    private function getTimeBasedDiscount($date)
    {
        $hour = date('H', strtotime($date));
        // Off-peak hours (before 10AM or after 4PM)
        if ($hour < 10 || $hour > 16) {
            return 0.1; // 10% discount
        }
        return 0;
    }
}
