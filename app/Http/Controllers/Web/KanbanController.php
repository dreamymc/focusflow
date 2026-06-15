<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KanbanController extends Controller
{
    public function show(Request $request, Workspace $workspace, Project $project)
    {
        if ($project->workspace_id !== $workspace->id) {
            abort(404);
        }

        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->forWorkspace($workspace)
            ->with(['assignees'])
            ->get();

        $byStatus = $tasks->groupBy(function ($task) {
            return $task->status->value;
        });

        return Inertia::render('Projects/Kanban', [
            'project' => $project,
            'workspace' => $workspace,
            'columns' => [
                ['id' => 'backlog', 'label' => 'Backlog', 'color' => '#6B7280', 'tasks' => $byStatus['backlog'] ?? []],
                ['id' => 'in_progress', 'label' => 'In Progress', 'color' => '#3B82F6', 'tasks' => $byStatus['in_progress'] ?? []],
                ['id' => 'in_review', 'label' => 'In Review', 'color' => '#F59E0B', 'tasks' => $byStatus['in_review'] ?? []],
                ['id' => 'done', 'label' => 'Done', 'color' => '#10B981', 'tasks' => $byStatus['done'] ?? []],
            ],
            'members' => $workspace->users()->select('users.id', 'users.name')->get(),
        ]);
    }
}
