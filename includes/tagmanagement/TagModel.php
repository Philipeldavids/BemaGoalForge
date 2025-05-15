<?php

namespace BemaGoalForge\TagManagement;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class TagModel
{
    /**
     * Saves a new tag to the database.
     *
     * @param int $entityId
     * @param string $tagName
     * @return bool
     */
    public function saveTag(int $entityId, string $tagName): bool
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'goalforge_tags';
        $result = $wpdb->insert(
            $table_name,
            [
                'entity_id' => $entityId,
                'tag_name'  => sanitize_text_field($tagName),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s']
        );

        return $result !== false;
    }

    /**
     * Deletes a tag from the database.
     *
     * @param int $tagId
     * @return bool
     */
    public function deleteTag(int $tagId): bool
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'goalforge_tags';
        $result = $wpdb->delete(
            $table_name,
            ['id' => $tagId],
            ['%d']
        );

        return $result !== false;
    }
}
