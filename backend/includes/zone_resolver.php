<?php
class ZoneOverlapResolver {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function suggestResolution($newZone, $existingZones) {
        $suggestions = [];
        
        foreach ($existingZones as $zone) {
            $overlap = $this->calculateOverlap($newZone, $zone);
            
            if ($overlap['percentage'] > 0) {
                $suggestions[] = [
                    'zone_id' => $zone['id'],
                    'zone_name' => $zone['name'],
                    'overlap_percentage' => $overlap['percentage'],
                    'resolution_options' => $this->generateResolutionOptions($overlap)
                ];
            }
        }

        return [
            'has_conflicts' => count($suggestions) > 0,
            'suggestions' => $suggestions,
            'automated_fix' => $this->suggestAutomatedFix($newZone, $suggestions)
        ];
    }

    private function generateResolutionOptions($overlap) {
        return [
            'split_zone' => $this->calculateSplitZone($overlap),
            'merge_zones' => $this->calculateMergeOption($overlap),
            'adjust_boundaries' => $this->suggestBoundaryAdjustment($overlap)
        ];
    }
}
