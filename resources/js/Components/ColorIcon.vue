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
</script>

<template>
  <div
    class="flex items-center justify-center font-display font-bold text-white select-none shrink-0"
    :class="[sizeClasses, colorClasses]"
  >
    {{ initial }}
  </div>
</template>
