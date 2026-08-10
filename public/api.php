<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

/** @var Auth $auth */
/** @var Files $files */

/*
 * JSON endpoint for rename / delete / reorder.
 *
 * The endpoint it replaces, documentplacer.php, had no authentication check of
 * any kind: an unauthenticated POST could rename or reorder every record, and
 * because it echoed back whole client-supplied records it could also repoint a
 * record's `source` at another user's file. Here every action requires a
 * session, a CSRF token, and ownership of the record being touched.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$username = $auth->requireLoginJson();
Csrf::verify(json: true);

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    json_response(['ok' => false, 'error' => 'Malformed request body.'], 400);
}

$action = (string) ($payload['action'] ?? '');

switch ($action) {
    case 'rename':
        $fileId = (string) ($payload['file_id'] ?? '');
        $title  = trim((string) ($payload['title'] ?? ''));

        if ($fileId === '') {
            json_response(['ok' => false, 'error' => 'Missing file id.'], 400);
        }
        if ($title === '') {
            json_response(['ok' => false, 'error' => 'A title cannot be empty.'], 422);
        }
        if (mb_strlen($title) > 180) {
            json_response(['ok' => false, 'error' => 'That title is too long (max 180 characters).'], 422);
        }

        if (!$files->rename($fileId, $username, $title)) {
            // Same response whether the file is missing or owned by someone
            // else, so the API does not confirm which ids exist.
            json_response(['ok' => false, 'error' => 'That file does not exist or is not yours.'], 403);
        }

        json_response(['ok' => true, 'title' => $title]);

        // no break — json_response exits

    case 'delete':
        $fileId = (string) ($payload['file_id'] ?? '');

        if ($fileId === '') {
            json_response(['ok' => false, 'error' => 'Missing file id.'], 400);
        }

        if (!$files->delete($fileId, $username)) {
            json_response(['ok' => false, 'error' => 'That file does not exist or is not yours.'], 403);
        }

        json_response(['ok' => true]);

    case 'reorder':
        $order = $payload['order'] ?? null;

        if (!is_array($order)) {
            json_response(['ok' => false, 'error' => 'Missing order.'], 400);
        }

        // Only ids cross the wire, and reorder() ignores any that the caller
        // does not own, so this cannot touch another user's records.
        $files->reorder(array_values(array_filter($order, 'is_string')), $username);

        json_response(['ok' => true]);

    default:
        json_response(['ok' => false, 'error' => 'Unknown action.'], 400);
}
