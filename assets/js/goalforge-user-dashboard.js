jQuery(document).ready(function ($) {
    $('.goalforge-status-form select').change(function () {
        const taskId = $(this).closest('form').data('task-id');
        const status = $(this).val();

        $.post(ajaxurl, {
            action: 'goalforge_update_task_status',
            task_id: taskId,
            status: status,
        }); 
    });

    $('.add-comment-btn').on('click', function () {
    const taskId = $(this).data('task-id');
    const content = $(this).siblings('.comment-text').val();
    const parentId = $(this).siblings('input.parent-comment-id').val() || null;

    $.post(ajaxurl, {
        action: 'goalforge_add_task_comment',
        task_id: taskId,
        content: content,
        parent_id: parentId,
    }, function (res) {
        if (res.success) {
            const commentData = res.data;
            const commentHTML = `<li>
                <div><strong>${commentData.display_name}</strong></div>
                <div>${commentData.parent_id ? `<em>Replying to parent</em><br>` : ''}${commentData.comment}</div>
            </li>`;

            $(`#comments-for-${taskId}`).append(commentHTML);

            // Optional: Clear the textarea
            $(`.goalforge-comments[data-task-id="${taskId}"] .comment-text`).val('');
        } else {
            alert('Failed to post comment.');
        }
    });
});

});

jQuery(document).ready(function ($) {
    $('.reply-btn').on('click', function () {
        const parentId = $(this).data('parent-id');
        $(`.reply-form[data-parent-id="${parentId}"]`).toggle();
    });

    $('.submit-reply-btn').on('click', function () {
        const taskId = $(this).data('task-id');
        const parentId = $(this).data('parent-id');
        const content = $(this).siblings('.reply-text').val();

        $.post(ajaxurl, {
            action: 'goalforge_add_task_comment',
            task_id: taskId,
            parent_id: parentId,
            content: content,
        }, function (res) {
            // Optionally reload comments or append
            location.reload(); // or dynamically append
        });
    });
});

jQuery(document).ready(function ($) {
$('.goalforge-comments').on('click', '.edit-comment-btn', function () {
const $item = $(this).closest('.comment-item');
$item.find('.comment-text, .edit-comment-btn, .delete-comment-btn').hide();
$item.find('.edit-comment-text, .save-comment-btn').show();
});
$('.goalforge-comments').on('click', '.save-comment-btn', function () {
    const $item = $(this).closest('.comment-item');
    const commentId = $item.data('comment-id');
    const newText = $item.find('.edit-comment-text').val();

    $.post(ajaxurl, {
        action: 'goalforge_edit_comment',
        comment_id: commentId,
        content: newText
    }, function (response) {
        if (response.success) {
            $item.find('.comment-text').text(newText).show();
            $item.find('.edit-comment-text, .save-comment-btn').hide();
            $item.find('.edit-comment-btn, .delete-comment-btn').show();
        } else {
            alert(response.data.message);
        }
    });
});

$('.goalforge-comments').on('click', '.delete-comment-btn', function () {
    if (!confirm('Are you sure you want to delete this comment?')) return;

    const $item = $(this).closest('.comment-item');
    const commentId = $item.data('comment-id');

    $.post(ajaxurl, {
        action: 'goalforge_delete_comment',
        comment_id: commentId
    }, function (response) {
        if (response.success) {
            $item.remove();
        } else {
            alert(response.data.message);
        }
    });
});

});