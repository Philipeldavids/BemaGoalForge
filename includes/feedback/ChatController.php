<?php

namespace BemaGoalForge\Feedback;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ChatController
{
    /**
     * Sends a message to the task chat.
     *
     * @param int $taskId
     * @param string $message
     * @return bool
     */
    public function sendMessage(int $taskId, string $message): bool
    {
        if (empty($message)) {
            return false;
        }

        $chatModel = new ChatModel();
        return $chatModel->saveMessage($taskId, $message);
    }

    /**
     * Retrieves all messages for a given task.
     *
     * @param int $taskId
     * @return array
     */
    public function fetchMessages(int $taskId): array
    {
        $chatModel = new ChatModel();
        return $chatModel->getMessagesByTaskId($taskId);
    }

    public static function handleChatbotQuery() {
    $message = sanitize_text_field($_POST['message'] ?? '');
    $response = self::generateChatbotResponse($message);
    wp_send_json_success([
        'reply' => $response
        ]);
    }

    public static function generateChatbotResponse($message) {
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
    return "OpenAI API key not configured.";
    }
        $api_url = 'https://api.openai.com/v1/chat/completions';

        $context = self::get_user_project_summary(get_current_user_id());
    $body = json_encode([
        'model' => 'gpt-4',
        'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant. Use this context to answer user queries:' . "\n\n" . $context],
        ['role' => 'user', 'content' => $message]
        ],
        'temperature' => 0.7
    ]);

    $response = wp_remote_post($api_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . OPENAI_API_KEY,
            'Content-Type'  => 'application/json',
        ],
        'body' => $body,
        'timeout' => 20
    ]);

    if (is_wp_error($response)) {
        return 'Sorry, there was an error contacting the AI.';
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($data['choices'][0]['message']['content'])) {
        return trim($data['choices'][0]['message']['content']);
    }

    return 'Sorry, I could not understand the response.';
}


public static function renderChatbot() {
ob_start(); ?>
<div id="goalforge-chatbot" style="border:1px solid #ccc; padding:15px; max-width:600px;">
<div id="chatbot-log" style="height:300px; overflow-y:scroll; border-bottom:1px solid #eee; margin-bottom:10px;"></div>
<input type="text" id="chatbot-input" placeholder="Ask a question..." style="width:80%;" />
<button id="chatbot-send">Send</button>
</div>
<script>
document.getElementById('chatbot-send').addEventListener('click', function () {
const input = document.getElementById('chatbot-input');
const log = document.getElementById('chatbot-log');
const msg = input.value;
if (!msg) return;
    log.innerHTML += `<p><strong>You:</strong> ${msg}</p>`;
    input.value = '';
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'goalforge_chatbot_query',
            message: msg
        })
    }).then(res => res.json()).then(data => {
        log.innerHTML += `<p><strong>Bot:</strong> ${data.response}</p>`;
        log.scrollTop = log.scrollHeight;
    });
});
</script>
<?php return ob_get_clean();

}

public static function get_user_project_summary($user_id) {
global $wpdb;
    $tasks_table       = "{$wpdb->prefix}goalforge_tasks";
    $projects_table    = "{$wpdb->prefix}goalforge_projects";
    $assignees_table   = "{$wpdb->prefix}goalforge_task_assignees";
    $checklist_table   = "{$wpdb->prefix}goalforge_task_checklists";

    // Get tasks assigned to user with project info and due date
    $tasks = $wpdb->get_results($wpdb->prepare("
        SELECT t.id AS task_id, t.title AS task_title, t.due_date, p.title AS project_title
        FROM $tasks_table t
        INNER JOIN $assignees_table a ON a.task_id = t.id
        INNER JOIN $projects_table p ON p.id = t.project_id
        WHERE a.user_id = %d
        ORDER BY p.title ASC, t.due_date ASC
    ", $user_id));

    if (empty($tasks)) {
        return "User has no assigned tasks or projects.";
    }

    // Get checklist counts
    $task_ids = wp_list_pluck($tasks, 'task_id');
    $checklist_map = [];

    if (!empty($task_ids)) {
        $in_clause = implode(',', array_map('intval', $task_ids));
        $checklists = $wpdb->get_results("
            SELECT task_id, is_completed
            FROM $checklist_table
            WHERE task_id IN ($in_clause)
        ");

        foreach ($checklists as $item) {
            $tid = $item->task_id;
            if (!isset($checklist_map[$tid])) {
                $checklist_map[$tid] = ['total' => 0, 'completed' => 0];
            }
            $checklist_map[$tid]['total'] += 1;
            if ((int)$item->is_completed === 1) {
                $checklist_map[$tid]['completed'] += 1;
            }
        }
    }

    // Group tasks by project
    $grouped = [];
    foreach ($tasks as $task) {
        $due = $task->due_date ? date('M j, Y', strtotime($task->due_date)) : 'No due date';
        $check = $checklist_map[$task->task_id] ?? ['completed' => 0, 'total' => 0];
        $progress = $check['total'] > 0 ? "{$check['completed']} of {$check['total']} checklist items completed" : "No checklist items";

        $grouped[$task->project_title][] = "- Task: {$task->task_title} (Due: {$due}, {$progress})";
    }

    // Format output
    $summary = "User is assigned to the following projects and tasks:\n\n";
    foreach ($grouped as $project => $taskLines) {
        $summary .= "Project: {$project}\n" . implode("\n", $taskLines) . "\n\n";
    }

    return $summary;
}


}