<?php

use App\Models\User;
use App\Models\Task;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('workspace.{workspaceId}', function (User $user, int $workspaceId) {
    return $user->workspaces()->where('workspaces.id', $workspaceId)->exists();
});

Broadcast::channel('task.{taskId}', function (User $user, int $taskId) {
    $task = Task::find($taskId);
    if (! $task) {
        return false;
    }

    if ($user->workspaces()->where('workspaces.id', $task->workspace_id)->exists()) {
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
    }

    return false;
});
