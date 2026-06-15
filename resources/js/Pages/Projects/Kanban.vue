<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import { usePermissions } from '../../Composables/usePermissions';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import ColorIcon from '../../Components/ColorIcon.vue';
import KanbanBoard from '../../Components/KanbanBoard.vue';

const props = defineProps({
  project: {
    type: Object,
    required: true,
  },
  workspace: {
    type: Object,
    required: true,
  },
  columns: {
    type: Array,
    required: true,
  },
  members: {
    type: Array,
    required: true,
  }
});

const { isViewer, isMember } = usePermissions();

const openTaskModal = (taskId) => {
  console.log('Open task modal for ID:', taskId);
  toast.info('Task details modal will be available in Phase 6.');
};

const openCreateTaskModal = (status) => {
  console.log('Open create task modal for status:', status);
  toast.info(`Task creation form for status "${status}" will be available in Phase 6.`);
};
</script>

<template>
  <AuthenticatedLayout :title="project.name">
    <div class="space-y-6 flex flex-col h-full">
      <!-- Sub-header bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <!-- Breadcrumbs -->
          <div class="flex items-center gap-2 text-xs text-text-secondary font-medium mb-1">
            <Link :href="`/workspaces/${workspace.id}/projects`" class="hover:text-primary transition-colors">
              Projects
            </Link>
            <span class="text-text-muted">/</span>
            <span class="text-text-muted font-normal">{{ project.name }}</span>
          </div>

          <!-- Title header -->
          <div class="flex items-center gap-3">
            <ColorIcon :name="project.name" :id="project.id" size="lg" />
            <h1 class="font-display text-2xl font-bold text-text">{{ project.name }}</h1>
          </div>
        </div>

        <!-- Create Task shortcut (Members+) -->
        <div v-if="isMember && !isViewer">
          <button
            @click="openCreateTaskModal('backlog')"
            class="inline-flex items-center justify-center rounded-md bg-primary hover:bg-primary-dark text-white px-4 py-2 text-sm font-medium transition-colors shadow-sm gap-1 cursor-pointer"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            New Task
          </button>
        </div>
      </div>

      <!-- Kanban Board Container -->
      <div class="flex-1 min-h-0">
        <KanbanBoard
          :columns="columns"
          :members="members"
          :read-only="isViewer"
          :workspace-id="workspace.id"
          @task-selected="openTaskModal"
          @create-task="openCreateTaskModal"
        />
      </div>
    </div>
  </AuthenticatedLayout>
</template>
