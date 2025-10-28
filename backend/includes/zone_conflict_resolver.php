<?php
class ZoneConflictResolver
{
    private $conn;
    private $minOverlapThreshold = 0.1; // 10% overlap threshold

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function resolveConflict($newZone, $existingZone)
    {
        // Calculate overlap area
        $overlapArea = $this->calculateOverlap($newZone, $existingZone);

        if ($overlapArea['percentage'] < $this->minOverlapThreshold) {
            return $this->adjustBoundaries($newZone, $existingZone);
        }

        // If significant overlap, suggest merging or splitting
        return $overlapArea['percentage'] > 0.5 ?
            $this->suggestMerge($newZone, $existingZone) :
            $this->suggestSplit($newZone, $existingZone);
    }

    private function adjustBoundaries($newZone, $existingZone)
    {
        // Calculate minimum adjustment needed
        $adjustments = $this->calculateMinimumAdjustment($newZone, $existingZone);

        return [
            'type' => 'boundary_adjustment',
            'adjustments' => $adjustments,
            'updated_coordinates' => $this->applyAdjustments($newZone, $adjustments)
        ];
    }
}
