# Phase 7 — Real-Time Wiring Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Connect WebSocket events (`TaskMoved`, `TaskAssigned`, `TaskCommented`) to the Kanban board so it updates live without refresh, and implement drag outlines, optimistic rollback shake animations, and toast notifications.

**Architecture:** 
- Subscriptions are handled via `window.Echo` on the private channel `workspace.{id}`.
- Kanban listeners are safely stopped in `onUnmounted` using `.stopListening(...)` instead of leaving the channel, preserving the persistent subscription in the navbar `NotificationBell.vue`.
- Visual drag over feedback uses CSS `:has(.sortable-ghost)` to style the target column container.
- Rollback failures inject a `shaking` boolean onto the reverted task inside `KanbanBoard.vue`, which activates a scoped keyframe animation in `TaskCard.vue`.

**Tech Stack:** Vue 3, Inertia.js, Laravel Echo, Pusher JS, Tailwind CSS, vue-sonner.

---

### Task 1: Style Column Drag Hover Outline

**Files:**
- Modify: `resources/js/Components/KanbanColumn.vue`

**Step 1: Write the visual feedback selector**
Add the outline style under a `<style scoped>` tag or inside class rules to target the column container when a ghost placeholder is dropped inside it.

```vue
<!-- in resources/js/Components/KanbanColumn.vue -->
<style scoped>
/* Target the VueDraggable container when SortableJS has placed the ghost element inside it */
.flex-1:has(.ghost-class),
.flex-1:has(.sortable-ghost) {
  outline: 2px dashed #3B82F6;
  outline-offset: -2px;
  background-color: rgba(59, 130, 246, 0.05);
}
</style>
```

**Step 2: Commit**
```bash
git add resources/js/Components/KanbanColumn.vue
git commit -m "feat(phase7): add drag hover dashed outline to kanban columns"
```

---

### Task 3: Implement Task Shaking Animation & Rollback

**Files:**
- Modify: `resources/js/Components/TaskCard.vue`
- Modify: `resources/js/Components/KanbanBoard.vue`

**Step 1: Add shaking styling to TaskCard.vue**
Add the keyframes and `animate-shake` class to `TaskCard.vue`.

```vue
<!-- in resources/js/Components/TaskCard.vue -->
<template>
  <div
    class="bg-surface rounded-lg border border-border p-3 shadow-sm select-none transition-all duration-200"
    :class="[
      readOnly
        ? 'cursor-default'
        : 'cursor-grab active:cursor-grabbing hover:shadow-md hover:translate-y-[-1px] hover:border-border-strong',
      task.shaking ? 'animate-shake' : ''
    ]"
    @mousedown="onMouseDown"
    @mouseup="onMouseUp"
    @click="onClick"
  >
  ...
</template>

<style scoped>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-4px); }
  40%, 80% { transform: translateX(4px); }
}

.animate-shake {
  animation: shake 0.4s ease-in-out;
  border-color: #EF4444 !important;
}
</style>
```

**Step 2: Inject shaking flag on rollback in KanbanBoard.vue**
Update the error catch block inside `handleTaskMoved` in `KanbanBoard.vue` to mark the reverted task object as shaking, then remove it after 800ms.

```javascript
// in resources/js/Components/KanbanBoard.vue
  } catch (error) {
    // Flag the reverted task as shaking
    let revertedTask = null;
    for (const col of backup) {
      revertedTask = col.tasks.find(t => t.id === taskId);
      if (revertedTask) {
        revertedTask.shaking = true;
        break;
      }
    }

    // Revert state
    localColumns.value = backup;
    emit('task-moved', { taskId, fromColumn: toColumn, toColumn: fromColumn, newIndex: oldIndex, oldIndex: newIndex });
    toast.error('Failed to move task. Reverted changes.');

    // Remove shake animation class after completion
    setTimeout(() => {
      if (revertedTask) {
        revertedTask.shaking = false;
      }
    }, 800);
  }
```

**Step 3: Commit**
```bash
git add resources/js/Components/TaskCard.vue resources/js/Components/KanbanBoard.vue
git commit -m "feat(phase7): add shake animation on task move failure rollback"
```

---

### Task 4: Implement Kanban WebSocket Subscriptions

**Files:**
- Modify: `resources/js/Pages/Projects/Kanban.vue`

**Step 1: Set up Echo listeners in onMounted and stop them in onUnmounted**
Listen to `TaskMoved`, `TaskAssigned`, and `TaskCommented` events. Ensure we update `localColumns` in place reactively and do not use `.leave()` to avoid breaking the NotificationBell.

```javascript
// in resources/js/Pages/Projects/Kanban.vue
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id);

let workspaceChannel = null;

onMounted(() => {
  if (window.Echo) {
    workspaceChannel = window.Echo.private(`workspace.${props.workspace.id}`);

    // Listen for TaskMoved
    workspaceChannel.listen('TaskMoved', (e) => {
      // Avoid reacting if this user initiated the move (local state is already optimistic)
      // Note: we can check the task's last updater or standard payload user reference
      const eventUserId = e.user_id || e.task?.updated_by; // adapt based on backend payload
      if (eventUserId && Number(eventUserId) === Number(currentUserId.value)) {
        return;
      }

      // Perform local updates
      const updatedTask = e.task;
      const prevStatus = e.previousStatus;
      const newStatus = typeof updatedTask.status === 'object' ? updatedTask.status.value : updatedTask.status;

      // 1. Remove from old column
      for (const col of localColumns.value) {
        const idx = col.tasks.findIndex(t => t.id === updatedTask.id);
        if (idx !== -1) {
          col.tasks.splice(idx, 1);
          break;
        }
      }

      // 2. Add to new column
      const targetCol = localColumns.value.find(c => c.id === newStatus);
      if (targetCol) {
        targetCol.tasks.push(updatedTask);
      }

      toast.info(`Task "${updatedTask.title}" was moved to ${newStatus}`);
    });

    // Listen for TaskAssigned
    workspaceChannel.listen('TaskAssigned', (e) => {
      const updatedTask = e.task;
      const assignedUser = e.user;

      // Update task in columns
      for (const col of localColumns.value) {
        const task = col.tasks.find(t => t.id === updatedTask.id);
        if (task) {
          task.assignees = [assignedUser];
          break;
        }
      }

      if (Number(assignedUser.id) === Number(currentUserId.value)) {
        toast.success(`You were assigned to "${updatedTask.title}"`);
      } else {
        toast.info(`"${assignedUser.name}" was assigned to "${updatedTask.title}"`);
      }
    });

    // Listen for TaskCommented
    workspaceChannel.listen('TaskCommented', (e) => {
      const updatedTask = e.task;
      const comment = e.comment;

      // If task modal is open for this task, we can update it or notify
      if (selectedTask.value && selectedTask.value.id === updatedTask.id) {
        // Option to reload or update in-place if payload supports it
      }
      
      toast.info(`New comment on "${updatedTask.title}": "${comment}"`);
    });
  }
});

onUnmounted(() => {
  if (workspaceChannel) {
    workspaceChannel.stopListening('TaskMoved');
    workspaceChannel.stopListening('TaskAssigned');
    workspaceChannel.stopListening('TaskCommented');
  }
});
```

**Step 2: Commit**
```bash
git add resources/js/Pages/Projects/Kanban.vue
git commit -m "feat(phase7): subscribe to Reverb workspace private channel events in Kanban"
```

---

### Task 5: Add Action Toasts for Operations

**Files:**
- Modify: `resources/js/Pages/Projects/Kanban.vue`
- Modify: `resources/js/Components/TaskModal.vue`

**Step 1: Check existing alerts and toast triggers**
Verify that the `TaskModal.vue` triggers Sonner toasts correctly for task creation, manual inline save failures, and task deletions. Update any generic dialog triggers or error flows to emit unified clean toast warnings.

**Step 2: Commit**
```bash
git add resources/js/Pages/Projects/Kanban.vue resources/js/Components/TaskModal.vue
git commit -m "feat(phase7): ensure Sonner toast triggers are aligned with all user actions"
```
