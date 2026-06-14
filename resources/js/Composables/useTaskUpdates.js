import { ref, onMounted, onUnmounted } from 'vue';

export function useTaskUpdates(taskId) {
    const channelName = `task.${taskId}`;
    const members = ref([]);

    onMounted(() => {
        if (window.Echo) {
            window.Echo.join(channelName)
                .here((users) => {
                    members.value = users;
                })
                .joining((user) => {
                    members.value.push(user);
                })
                .leaving((user) => {
                    members.value = members.value.filter(m => m.id !== user.id);
                });
        } else {
            console.warn('Laravel Echo is not initialized. Make sure window.Echo is available.');
        }
    });

    onUnmounted(() => {
        if (window.Echo) {
            window.Echo.leave(channelName);
        }
    });

    return {
        members,
    };
}
