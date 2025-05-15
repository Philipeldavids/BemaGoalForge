<?php

namespace BemaGoalForge\Utilities;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Logger
{
    /**
     * Logs messages or errors for debugging purposes.
     *
     * @param string $message
     * @param string $severity
     * @return bool
     */
    public function logMessage(string $message, string $severity = 'info'): bool
    {
        try {
            $logEntry = "[" . strtoupper($severity) . "] " . current_time('mysql') . " - " . $message . PHP_EOL;
            $logFile = GOALFORGE_LOG_DIR . 'plugin.log';

            return file_put_contents($logFile, $logEntry, FILE_APPEND) !== false;
        } catch (\Exception $e) {
            error_log('Bema GoalForge Logger Error: Failed to write log: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Logs an error message.
     *
     * @param string $message
     */
    public function logError(string $message): void
    {
        error_log('Bema GoalForge Error: ' . $message);
    }

    /**
     * Clears the plugin's log files during maintenance.
     *
     * @return bool
     */
    public function clearLogs(): bool
    {
        $logFile = GOALFORGE_LOG_DIR . 'plugin.log';

        if (file_exists($logFile)) {
            return unlink($logFile);
        }

        $this->logError('Attempted to clear non-existent log file.');
        return false;
    }
}
