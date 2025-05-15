<?php

namespace BemaGoalForge\TagManagement;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use BemaGoalForge\TagManagement\TagModel;

class TagController
{
    /**
     * Adds a tag to a task or project.
     *
     * @param int $entityId Task or project ID
     * @param string $tagName
     * @return bool
     */
    public function addTag(int $entityId, string $tagName): bool
    {
        if (empty($entityId) || empty($tagName)) {
            error_log("GoalForge: Missing entity ID or tag name in addTag.");
            return false;
        }

        $tagModel = new TagModel();
        return $tagModel->saveTag($entityId, $tagName);
    }

    /**
     * Removes a tag from a task or project.
     *
     * @param int $tagId
     * @return bool
     */
    public function removeTag(int $tagId): bool
    {
        if (empty($tagId)) {
            error_log("GoalForge: Missing tag ID in removeTag.");
            return false;
        }

        $tagModel = new TagModel();
        return $tagModel->deleteTag($tagId);
    }
}
