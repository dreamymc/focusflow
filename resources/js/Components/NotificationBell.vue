<template>
    <div class="relative inline-block">
        <button
            @click="toggleDropdown"
            class="relative p-2 text-gray-500 hover:text-gray-900 focus:outline-none transition-colors duration-200"
            aria-label="Notifications"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="w-6 h-6"
            >
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span
                v-if="unreadCount > 0"
                class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-red-600 rounded-full"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <div
            v-if="isOpen"
            class="absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-md shadow-lg z-50 overflow-hidden"
        >
            <div class="py-2">
                <div v-if="notifications.length === 0" class="px-4 py-3 text-sm text-center text-gray-500">
                    No new notifications
                </div>
                <ul v-else class="max-h-64 overflow-y-auto">
                    <li
                        v-for="(notification, index) in notifications"
                        :key="index"
                        class="px-4 py-3 border-b border-gray-100 last:border-b-0 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        {{ notification.message }}
                    </li>
                </ul>
            </div>
            <div v-if="notifications.length > 0" class="border-t border-gray-200 bg-gray-50 p-2 text-center">
                <button
                    @click="clearNotifications"
                    class="text-sm text-blue-600 hover:text-blue-800 font-medium focus:outline-none"
                >
                    Clear all
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    workspaceId: {
        type: [Number, String],
        required: true,
    },
});

const unreadCount = ref(0);
const notifications = ref([]);
const isOpen = ref(false);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const clearNotifications = () => {
    notifications.value = [];
    unreadCount.value = 0;
    isOpen.value = false;
};

const handleNewNotification = (message) => {
    notifications.value.unshift({ message, time: new Date() });
    unreadCount.value++;
};

let channel = null;

onMounted(() => {
    if (window.Echo) {
        channel = window.Echo.private(`workspace.${props.workspaceId}`);

        channel.listen('TaskMoved', (e) => {
            const taskName = e.task?.title || 'A task';
            handleNewNotification(`${taskName} was moved.`);
        });

        channel.listen('TaskAssigned', (e) => {
            const taskName = e.task?.title || 'A task';
            handleNewNotification(`You were assigned to ${taskName}.`);
        });

        channel.listen('TaskCommented', (e) => {
            const taskName = e.task?.title || 'a task';
            handleNewNotification(`New comment on ${taskName}.`);
        });
    } else {
        console.warn('Laravel Echo is not initialized. Notifications will not work.');
    }
});

onUnmounted(() => {
    if (channel && window.Echo) {
        window.Echo.leave(`workspace.${props.workspaceId}`);
    }
});
</script>
