<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/Components/ui/sheet';
import PresenceAvatars from './PresenceAvatars.vue';
import { usePermissions } from '../Composables/usePermissions';

const props = defineProps({
  task: {
    type: Object,
    default: null,
  },
  projectId: {
    type: Number,
    required: true,
  },
  workspaceId: {
    type: Number,
    required: true,
  },
  mode: {
    type: String,
    default: 'view',
    validator: (val) => ['view', 'create'].includes(val),
  },
  initialStatus: {
    type: String,
    default: 'backlog',
  },
  members: {
    type: Array,
    required: true,
  },
  open: {
    type: Boolean,
    default: false,
  }
});

const emit = defineEmits(['close', 'task-created', 'task-updated', 'task-deleted']);

const { can } = usePermissions();
const readOnly = computed(() => !can('edit-tasks'));

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id);

const isSaving = ref(false);
const saveStatus = ref(''); // '', 'saving', 'saved', 'error'
const showDeleteConfirm = ref(false);
const hasChangesBeenSaved = ref(false);

// Comments state
const comments = ref([]);
const newComment = ref('');
const isSubmittingComment = ref(false);
const commentsLoading = ref(false);

const getInitials = (name) => {
  if (!name) return '?';
  return name.trim().split(/\s+/).map(n => n[0]).slice(0, 2).join('').toUpperCase();
};

const formatCommentDate = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }) + ' at ' + date.toLocaleTimeString(undefined, {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  });
};

const fetchComments = async () => {
  if (!props.task) return;
  commentsLoading.value = true;
  try {
    const response = await fetch(`/api/v1/workspaces/${props.workspaceId}/projects/${props.projectId}/tasks/${props.task.id}/comments`);
    if (response.ok) {
      const result = await response.json();
      comments.value = result.data;
    }
  } catch (e) {
    console.error("Failed to load comments");
  } finally {
    commentsLoading.value = false;
  }
};

let modalChannel = null;

const setupEchoListener = () => {
  if (window.Echo && props.task) {
    // Leave previous channel if any
    if (modalChannel) {
      window.Echo.leaveChannel('workspace.' + props.workspaceId);
    }
    
    modalChannel = window.Echo.private('workspace.' + props.workspaceId);
    modalChannel.listen('TaskCommented', (e) => {
      if (e.task.id === props.task.id) {
        if (!comments.value.some(c => c.id === e.comment.id)) {
          comments.value.push(e.comment);
        }
      }
    });
  }
};

const cleanupEchoListener = () => {
  if (modalChannel && window.Echo) {
    modalChannel.stopListening('TaskCommented');
    modalChannel = null;
  }
};

onUnmounted(() => {
  cleanupEchoListener();
  triggerAutoSave?.cancel();
});

const getCookie = (name) => {
  if (typeof document === 'undefined') return null;
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
  return null;
};

// Form states
const createForm = ref({
  title: '',
  description: '',
  status: props.initialStatus,
  priority: 'low',
  assigneeId: null,
});

const editForm = ref({
  title: '',
  description: '',
  status: 'backlog',
  priority: 'low',
  assigneeId: null,
});

// Initialize form
const initForms = () => {
  if (props.mode === 'create') {
    createForm.value = {
      title: '',
      description: '',
      status: props.initialStatus,
      priority: 'low',
      assigneeId: null,
    };
  } else if (props.task) {
    editForm.value = {
      title: props.task.title || '',
      description: props.task.description || '',
      status: props.task.status?.value || props.task.status || 'backlog',
      priority: props.task.priority?.value || props.task.priority || 'low',
      assigneeId: props.task.assignees && props.task.assignees.length > 0 ? props.task.assignees[0].id : null,
    };
    saveStatus.value = '';
    showDeleteConfirm.value = false;
  }
};

const isFormDirty = computed(() => {
  if (!props.task) return false;
  const currentAssigneeId = props.task.assignees && props.task.assignees.length > 0 ? props.task.assignees[0].id : null;
  const currentStatus = props.task.status?.value || props.task.status || 'backlog';
  const currentPriority = props.task.priority?.value || props.task.priority || 'low';
  
  const assigneeId1 = editForm.value.assigneeId ? Number(editForm.value.assigneeId) : null;
  const assigneeId2 = currentAssigneeId ? Number(currentAssigneeId) : null;
  
  return editForm.value.title !== (props.task.title || '') ||
         editForm.value.description !== (props.task.description || '') ||
         editForm.value.status !== currentStatus ||
         editForm.value.priority !== currentPriority ||
         assigneeId1 !== assigneeId2;
});

let lastTaskId = null;

watch(() => [props.task?.id, props.open], () => {
  // Reset forms only when the task changes or the modal is opened/closed
  const currentTaskId = props.task?.id || null;
  const taskChanged = currentTaskId !== lastTaskId;
  lastTaskId = currentTaskId;

  if (props.open) {
    hasChangesBeenSaved.value = false;
  }

  if (taskChanged || !isFormDirty.value) {
    initForms();
  }

  if (props.open && props.task && props.mode === 'view') {
    fetchComments();
    setupEchoListener();
  } else {
    cleanupEchoListener();
  }
}, { immediate: true });

// Custom debounce with .cancel() support — prevents stale saves after modal close
function debounce(fn, delay) {
  let timeoutId;
  function debounced(...args) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn.apply(this, args), delay);
  }
  debounced.cancel = () => clearTimeout(timeoutId);
  return debounced;
}

const performAutoSave = async () => {
  if (!props.task || readOnly.value) return;
  saveStatus.value = 'saving';

  try {
    const xsrf = getCookie('XSRF-TOKEN');
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (xsrf) {
      headers['X-XSRF-TOKEN'] = xsrf;
    }

    const response = await fetch(`/api/v1/workspaces/${props.workspaceId}/projects/${props.projectId}/tasks/${props.task.id}`, {
      method: 'PATCH',
      headers,
      body: JSON.stringify({
        title: editForm.value.title,
        description: editForm.value.description,
        status: editForm.value.status,
        priority: editForm.value.priority,
        assignee_ids: editForm.value.assigneeId ? [Number(editForm.value.assigneeId)] : [],
      }),
    });

    if (!response.ok) throw new Error();
    const result = await response.json();
    emit('task-updated', result.data);
    saveStatus.value = 'saved';
    hasChangesBeenSaved.value = true;
  } catch (error) {
    saveStatus.value = 'error';
    toast.error('Failed to auto-save task.');
  }
};

const triggerAutoSave = debounce(performAutoSave, 500);

const handleClose = () => {
  triggerAutoSave.cancel();
  if (hasChangesBeenSaved.value) {
    toast.success('Task updated');
    hasChangesBeenSaved.value = false;
  }
  saveStatus.value = '';
  emit('close');
};

// Watchers for inline edits in view mode
watch(() => [editForm.value.title, editForm.value.description, editForm.value.status, editForm.value.priority, editForm.value.assigneeId], () => {
  if (props.mode === 'view' && props.task && !readOnly.value && isFormDirty.value) {
    triggerAutoSave();
  }
}, { deep: true });

const submitCreate = async () => {
  isSaving.value = true;
  try {
    const xsrf = getCookie('XSRF-TOKEN');
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (xsrf) {
      headers['X-XSRF-TOKEN'] = xsrf;
    }

    const response = await fetch(`/api/v1/workspaces/${props.workspaceId}/projects/${props.projectId}/tasks`, {
      method: 'POST',
      headers,
      body: JSON.stringify({
        title: createForm.value.title,
        description: createForm.value.description,
        status: createForm.value.status,
        priority: createForm.value.priority,
        assignee_ids: createForm.value.assigneeId ? [Number(createForm.value.assigneeId)] : [],
      }),
    });

    if (!response.ok) throw new Error();
    const result = await response.json();
    emit('task-created', result.data);
    emit('close');
    toast.success('Task created successfully!');
  } catch (error) {
    toast.error('Failed to create task.');
  } finally {
    isSaving.value = false;
  }
};

const submitDelete = async () => {
  if (!props.task) return;
  isSaving.value = true;

  // Reset immediately so handleClose (triggered by Sheet's @update:open) does NOT
  // fire a spurious 'Task updated' toast alongside the 'Task deleted' toast.
  hasChangesBeenSaved.value = false;
  triggerAutoSave.cancel();

  try {
    const xsrf = getCookie('XSRF-TOKEN');
    const headers = {
      'Accept': 'application/json',
    };
    if (xsrf) {
      headers['X-XSRF-TOKEN'] = xsrf;
    }

    const response = await fetch(`/api/v1/workspaces/${props.workspaceId}/projects/${props.projectId}/tasks/${props.task.id}`, {
      method: 'DELETE',
      headers,
    });

    if (!response.ok) throw new Error();
    emit('task-deleted', props.task.id);
    emit('close');
    toast.success('Task deleted successfully!');
  } catch (error) {
    toast.error('Failed to delete task.');
  } finally {
    isSaving.value = false;
  }
};

const submitComment = async () => {
  if (!newComment.value.trim() || isSubmittingComment.value || !props.task) return;
  isSubmittingComment.value = true;
  try {
    const xsrf = getCookie('XSRF-TOKEN');
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (xsrf) {
      headers['X-XSRF-TOKEN'] = xsrf;
    }

    const response = await fetch(`/api/v1/workspaces/${props.workspaceId}/projects/${props.projectId}/tasks/${props.task.id}/comments`, {
      method: 'POST',
      headers,
      body: JSON.stringify({
        content: newComment.value,
      }),
    });

    if (response.ok) {
      const result = await response.json();
      if (!comments.value.some(c => c.id === result.data.id)) {
        comments.value.push(result.data);
      }
      newComment.value = '';
      toast.success('Comment posted successfully!');
    } else {
      throw new Error();
    }
  } catch (error) {
    toast.error('Failed to post comment.');
  } finally {
    isSubmittingComment.value = false;
  }
};
</script>

<template>
  <Sheet :open="open" @update:open="val => !val && handleClose()">
    <SheetContent class="w-full sm:max-w-[560px] overflow-y-auto bg-surface border-l border-border p-6 sm:p-8 shadow-2xl">
      
      <!-- CREATE MODE -->
      <div v-if="mode === 'create'" class="space-y-6">
        <SheetHeader class="space-y-1.5">
          <SheetTitle class="font-display text-2xl font-extrabold text-text tracking-tight">Create Task</SheetTitle>
          <SheetDescription class="text-sm text-text-muted">Add a new task to your project board.</SheetDescription>
        </SheetHeader>

        <form @submit.prevent="submitCreate" class="space-y-5 pt-4 border-t border-border/40">
          <!-- Title -->
          <div class="space-y-1.5">
            <label for="task-title" class="label-uppercase-tracked block">Task Title</label>
            <input
              id="task-title"
              type="text"
              v-model="createForm.title"
              class="block w-full rounded-lg border border-border px-3.5 py-2.5 bg-surface text-sm text-text-secondary shadow-sm hover:border-border-strong focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all outline-none"
              placeholder="e.g. Design homepage hero section"
              required
              :disabled="isSaving"
            />
          </div>

          <!-- Description -->
          <div class="space-y-1.5">
            <label for="task-desc" class="label-uppercase-tracked block">Description</label>
            <textarea
              id="task-desc"
              v-model="createForm.description"
              class="block w-full rounded-lg border border-border px-3.5 py-2.5 bg-surface text-sm text-text-secondary shadow-sm hover:border-border-strong focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all h-28 resize-none outline-none"
              placeholder="Provide a detailed task description..."
              :disabled="isSaving"
            />
          </div>

          <!-- Grid selectors -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Status -->
            <div class="space-y-1.5">
              <label for="task-status" class="label-uppercase-tracked block">Status</label>
              <div class="relative">
                <select
                  id="task-status"
                  v-model="createForm.status"
                  class="appearance-none block w-full rounded-lg border border-border bg-surface pl-3 pr-10 py-2.5 text-sm text-text-secondary hover:border-border-strong focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all cursor-pointer disabled:cursor-not-allowed outline-none"
                  required
                  :disabled="isSaving"
                >
                  <option value="backlog">Backlog</option>
                  <option value="in_progress">In Progress</option>
                  <option value="in_review">In Review</option>
                  <option value="done">Done</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                  <svg class="h-4 w-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Priority -->
            <div class="space-y-1.5">
              <label for="task-priority" class="label-uppercase-tracked block">Priority</label>
              <div class="relative">
                <select
                  id="task-priority"
                  v-model="createForm.priority"
                  class="appearance-none block w-full rounded-lg border border-border bg-surface pl-3 pr-10 py-2.5 text-sm text-text-secondary hover:border-border-strong focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all cursor-pointer disabled:cursor-not-allowed outline-none"
                  required
                  :disabled="isSaving"
                >
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                  <svg class="h-4 w-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <!-- Assignee -->
          <div class="space-y-1.5">
            <label for="task-assignee" class="label-uppercase-tracked block">Assignee</label>
            <div class="relative">
              <select
                id="task-assignee"
                v-model="createForm.assigneeId"
                class="appearance-none block w-full rounded-lg border border-border bg-surface pl-3 pr-10 py-2.5 text-sm text-text-secondary hover:border-border-strong focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all cursor-pointer disabled:cursor-not-allowed outline-none"
                :disabled="isSaving"
              >
                <option :value="null">Unassigned</option>
                <option v-for="member in members" :key="member.id" :value="member.id">
                  {{ member.name }}
                </option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <svg class="h-4 w-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="pt-5 flex justify-end gap-3 border-t border-border/40">
            <button
              type="button"
              @click="handleClose"
              class="inline-flex items-center justify-center rounded-xl border border-border bg-surface px-4 py-2.5 text-sm font-medium text-text-secondary hover:bg-surface-2 transition-all cursor-pointer outline-none focus:ring-4 focus:ring-border/50"
              :disabled="isSaving"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="inline-flex items-center justify-center rounded-xl bg-primary hover:bg-primary-dark text-white px-5 py-2.5 text-sm font-medium transition-all shadow-md shadow-primary/20 hover:shadow-primary/30 cursor-pointer disabled:opacity-50 disabled:shadow-none shimmer-btn overflow-hidden outline-none focus:ring-4 focus:ring-primary/10"
              :disabled="isSaving || !createForm.title.trim()"
            >
              <span v-if="isSaving" class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Creating...</span>
              </span>
              <span v-else>Create Task</span>
            </button>
          </div>
        </form>
      </div>

      <div v-else-if="mode === 'view' && task" class="space-y-6">
        <SheetHeader class="space-y-2">
          <SheetTitle class="sr-only">Task Details</SheetTitle>
          <SheetDescription class="sr-only">View and edit details of this task.</SheetDescription>
          <div class="flex items-center justify-between border-b border-border/40 pb-3 mb-1">
            <span class="font-mono text-xs text-text-muted tracking-wider bg-slate-50 border border-border/60 rounded-md px-2.5 py-1">TASK-{{ task.id }}</span>
            
            <!-- Saving Status Indicator -->
            <div class="h-6 flex items-center">
              <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
                mode="out-in"
              >
                <div v-if="saveStatus === 'saving'" :key="'saving'" class="inline-flex items-center gap-1.5 text-xs text-primary font-medium">
                  <svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>Saving...</span>
                </div>
                <div v-else-if="saveStatus === 'saved'" :key="'saved'" class="inline-flex items-center gap-1.5 text-xs text-accent-green font-medium">
                  <svg class="h-3.5 w-3.5 text-accent-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>Saved</span>
                </div>
                <div v-else-if="saveStatus === 'error'" :key="'error'" class="inline-flex items-center gap-1.5 text-xs text-accent-red font-medium">
                  <svg class="h-3.5 w-3.5 text-accent-red" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <span>Save failed</span>
                </div>
              </Transition>
            </div>
          </div>
          
          <!-- Presence Channel Avatars -->
          <div class="pt-1">
            <PresenceAvatars :key="task.id" :task-id="task.id" :current-user-id="currentUserId" />
          </div>
        </SheetHeader>

        <!-- Inline Editable Title -->
        <div class="space-y-1 pt-2">
          <input
            type="text"
            v-model="editForm.title"
            class="block w-full font-display text-2xl font-extrabold text-text bg-transparent border border-transparent hover:bg-slate-50/50 hover:border-border/60 rounded-xl px-3 py-2.5 focus:border-primary focus:bg-surface focus:shadow-lg focus:shadow-primary/5 focus:ring-4 focus:ring-primary/10 transition-all duration-200 outline-none"
            required
            :disabled="readOnly"
            placeholder="Task Title"
          />
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-border/40">
          
          <!-- Left Column (Description & Comments) -->
          <div class="md:col-span-2 space-y-6">
            <!-- Description -->
            <div class="space-y-2">
              <label class="label-uppercase-tracked block">Description</label>
              <textarea
                v-model="editForm.description"
                class="block w-full rounded-xl border border-transparent hover:bg-slate-50/50 hover:border-border/60 focus:border-primary bg-transparent focus:bg-surface p-3 text-sm text-text-secondary placeholder-text-muted/70 h-40 resize-none transition-all duration-200 focus:shadow-lg focus:shadow-primary/5 focus:ring-4 focus:ring-primary/10 outline-none"
                placeholder="Add a detailed description for this task..."
                :disabled="readOnly"
              />
            </div>

            <!-- Comments Section -->
            <div class="space-y-4 pt-6 border-t border-border/40">
              <h4 class="text-sm font-semibold text-text font-display">Comments</h4>
              
              <!-- Comment List -->
              <div v-if="comments.length > 0" class="space-y-4 max-h-[280px] overflow-y-auto pr-2 custom-scrollbar">
                <div v-for="comment in comments" :key="comment.id" class="flex gap-3 text-sm">
                  <!-- User Avatar/Initials -->
                  <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 text-primary flex items-center justify-center font-semibold text-xs border border-primary/15 shadow-sm select-none">
                    {{ getInitials(comment.user?.name) }}
                  </div>
                  <!-- Content & Time -->
                  <div class="flex-1 space-y-1.5 bg-slate-50/70 p-3.5 rounded-xl border border-border/70 shadow-sm transition-all duration-200 hover:bg-slate-50 hover:border-border">
                    <div class="flex items-center justify-between">
                      <span class="font-semibold text-text text-xs">{{ comment.user?.name || 'Unknown User' }}</span>
                      <span class="text-[10px] text-text-muted font-mono">{{ formatCommentDate(comment.created_at) }}</span>
                    </div>
                    <p class="text-text-secondary whitespace-pre-line text-xs leading-relaxed">{{ comment.content }}</p>
                  </div>
                </div>
              </div>
              <div v-else-if="commentsLoading" class="text-xs text-text-muted py-2 italic animate-pulse">Loading comments...</div>
              <div v-else class="text-xs text-text-muted py-2 italic">No comments yet. Be the first to start the conversation!</div>

              <!-- Add Comment Form -->
              <form @submit.prevent="submitComment" class="flex gap-2.5 items-start pt-3 border-t border-border/20">
                <textarea
                  v-model="newComment"
                  class="block w-full rounded-xl border border-border/80 px-4 py-3 bg-surface text-xs text-text-secondary placeholder-text-muted/65 shadow-inner-sm focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all h-16 resize-none outline-none"
                  placeholder="Share feedback or update progress..."
                  required
                  :disabled="isSubmittingComment"
                />
                <button
                  type="submit"
                  class="flex-shrink-0 inline-flex items-center justify-center rounded-xl bg-primary hover:bg-primary-dark text-white px-4 py-2 text-xs font-semibold shadow-md shadow-primary/20 hover:shadow-primary/30 transition-all cursor-pointer disabled:opacity-50 disabled:shadow-none h-16 shimmer-btn overflow-hidden outline-none focus:ring-4 focus:ring-primary/10"
                  :disabled="isSubmittingComment || !newComment.trim()"
                >
                  <span v-if="isSubmittingComment" class="flex items-center justify-center">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                  </span>
                  <span v-else>Post</span>
                </button>
              </form>
            </div>
          </div>

          <!-- Right Column (Metadata Selectors) -->
          <div class="space-y-5 bg-slate-50/50 rounded-xl p-5 border border-border/80 shadow-sm self-start">
            <!-- Status -->
            <div class="space-y-1.5">
              <label for="edit-status" class="label-uppercase-tracked block">Status</label>
              <div class="relative">
                <select
                  id="edit-status"
                  v-model="editForm.status"
                  class="appearance-none block w-full rounded-lg border border-border bg-surface pl-3 pr-10 py-2.5 text-xs text-text-secondary hover:border-border-strong focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all cursor-pointer disabled:cursor-not-allowed outline-none"
                  :disabled="readOnly"
                >
                  <option value="backlog">Backlog</option>
                  <option value="in_progress">In Progress</option>
                  <option value="in_review">In Review</option>
                  <option value="done">Done</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                  <svg class="h-4 w-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Priority -->
            <div class="space-y-1.5">
              <label for="edit-priority" class="label-uppercase-tracked block">Priority</label>
              <div class="relative">
                <select
                  id="edit-priority"
                  v-model="editForm.priority"
                  class="appearance-none block w-full rounded-lg border border-border bg-surface pl-3 pr-10 py-2.5 text-xs text-text-secondary hover:border-border-strong focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all cursor-pointer disabled:cursor-not-allowed outline-none"
                  :disabled="readOnly"
                >
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                  <svg class="h-4 w-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Assignee -->
            <div class="space-y-1.5">
              <label for="edit-assignee" class="label-uppercase-tracked block">Assignee</label>
              <div class="relative">
                <select
                  id="edit-assignee"
                  v-model="editForm.assigneeId"
                  class="appearance-none block w-full rounded-lg border border-border bg-surface pl-3 pr-10 py-2.5 text-xs text-text-secondary hover:border-border-strong focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all cursor-pointer disabled:cursor-not-allowed outline-none"
                  :disabled="readOnly"
                >
                  <option :value="null">Unassigned</option>
                  <option v-for="member in members" :key="member.id" :value="member.id">
                    {{ member.name }}
                  </option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                  <svg class="h-4 w-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Delete Task (Members only) -->
        <div v-if="can('delete-tasks')" class="pt-6 border-t border-border/40 flex justify-between items-center">
          <div v-if="!showDeleteConfirm">
            <button
              @click="showDeleteConfirm = true"
              class="text-xs font-semibold text-accent-red hover:text-accent-red/80 hover:underline cursor-pointer transition-all outline-none"
            >
              Delete Task
            </button>
          </div>
          <div v-else class="flex items-center gap-3">
            <span class="text-xs text-text-secondary font-medium">Are you sure?</span>
            <button
              @click="submitDelete"
              class="text-xs font-semibold bg-accent-red hover:bg-accent-red-dark text-white rounded-lg px-3 py-1.5 cursor-pointer transition-all shadow-sm shadow-accent-red/10"
              :disabled="isSaving"
            >
              Delete
            </button>
            <button
              @click="showDeleteConfirm = false"
              class="text-xs font-semibold text-text-muted hover:text-text-secondary transition-all cursor-pointer outline-none"
              :disabled="isSaving"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>

    </SheetContent>
  </Sheet>
</template>
