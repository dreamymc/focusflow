<script setup>
import { computed } from 'vue';

const props = defineProps({
  name: {
    type: String,
    required: true
  },
  color: {
    type: String,
    default: null,
    validator: (val) => !val || ['red', 'orange', 'yellow', 'green', 'blue', 'purple', 'pink', 'gray'].includes(val)
  },
  size: {
    type: String,
    default: 'md',
    validator: (val) => ['sm', 'md', 'lg'].includes(val)
  },
  id: {
    type: Number,
    default: 1
  }
});

const computedColor = computed(() => {
  if (props.color) return props.color;
  // Deterministic color assignment based on name + ID
  const colors = ['red', 'orange', 'yellow', 'green', 'blue', 'purple', 'pink', 'gray'];
  const charSum = props.name.split('').reduce((sum, char) => sum + char.charCodeAt(0), 0);
  const index = (charSum + props.id) % colors.length;
  return colors[index];
});

const initial = computed(() => {
  return props.name ? props.name.trim().charAt(0).toUpperCase() : '?';
});

const sizeClasses = computed(() => {
  return {
    'sm': 'w-6 h-6 text-xs rounded-md',
    'md': 'w-8 h-8 text-sm rounded-lg',
    'lg': 'w-10 h-10 text-base rounded-lg'
  }[props.size];
});

const colorClasses = computed(() => {
  return {
    'red': 'bg-accent-red',
    'orange': 'bg-accent-orange',
    'yellow': 'bg-accent-yellow',
    'green': 'bg-accent-green',
    'blue': 'bg-accent-blue',
    'purple': 'bg-accent-purple',
    'pink': 'bg-accent-pink',
    'gray': 'bg-accent-gray'
  }[computedColor.value] || 'bg-accent-blue';
});

const shadowStyle = computed(() => {
  const colorMap = {
    'red': 'rgba(239, 68, 68, 0.25)',
    'orange': 'rgba(249, 115, 22, 0.25)',
    'yellow': 'rgba(245, 158, 11, 0.25)',
    'green': 'rgba(16, 185, 129, 0.25)',
    'blue': 'rgba(59, 130, 246, 0.25)',
    'purple': 'rgba(139, 92, 246, 0.25)',
    'pink': 'rgba(236, 72, 153, 0.25)',
    'gray': 'rgba(107, 114, 128, 0.25)'
  };
  const colorValue = colorMap[computedColor.value] || 'rgba(59, 130, 246, 0.25)';
  return {
    boxShadow: `0 4px 12px ${colorValue}`
  };
});
</script>

<template>
  <div
    class="flex items-center justify-center font-display font-bold text-white select-none shrink-0"
    :class="[sizeClasses, colorClasses]"
    :style="shadowStyle"
  >
    {{ initial }}
  </div>
</template>
