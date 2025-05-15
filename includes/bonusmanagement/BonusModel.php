<?php

namespace BemaGoalForge\BonusManagement;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class BonusModel
{
    /**
     * Saves a bonus assignment to the database.
     *
     * @param int $projectId
     * @param float $bonusAmount
     * @return bool
     */
    public function saveBonus(int $projectId, float $bonusAmount): bool
    {
        global $wpdb;
        $bonus_table = $wpdb->prefix . 'goalforge_project_bonuses';

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $bonus_table WHERE project_id = %d", $projectId));
        $result = false;

        if ($existing) {
            $result = $wpdb->update(
                $bonus_table,
                ['bonus_amount' => $bonusAmount],
                ['project_id' => $projectId],
                ['%f'],
                ['%d']
            );
        } else {
            $result = $wpdb->insert(
                $bonus_table,
                [
                    'project_id' => $projectId,
                    'bonus_amount' => $bonusAmount,
                    'created_at'   => current_time('mysql'),
                ],
                ['%d', '%f', '%s']
            );
        }
       

        return $result !== false;
    }
}
