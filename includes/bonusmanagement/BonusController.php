<?php

namespace BemaGoalForge\BonusManagement;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use BemaGoalForge\BonusManagement\BonusModel;

class BonusController
{
    /**
     * Assigns a bonus to a project.
     *
     * @param int $projectId
     * @param float $bonusAmount
     * @return bool
     */
    public function assignBonus(int $projectId, float $bonusAmount): bool
    {
        if (empty($projectId) || $bonusAmount <= 0) {
            error_log("GoalForge: Invalid project ID or bonus amount in assignBonus.");
            return false;
        }

        $bonusModel = new BonusModel();
        return $bonusModel->saveBonus($projectId, $bonusAmount);
    }
}
