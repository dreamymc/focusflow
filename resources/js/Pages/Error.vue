<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
  status: {
    type: Number,
    required: true
  }
});

const title = computed(() => {
  return {
    503: '503: Service Unavailable',
    500: '500: Server Error',
    404: '404: Page Not Found',
    403: '403: Forbidden',
  }[props.status] || 'Error';
});

const description = computed(() => {
  return {
    503: 'Sorry, we are doing some maintenance. Please check back soon.',
    500: 'Something went wrong on our end.',
    404: "Page not found. Let's get you back on track.",
    403: "You don't have permission to view this.",
  }[props.status] || 'An unexpected error has occurred.';
});

const goBack = () => {
  if (typeof window !== 'undefined') {
    window.history.back();
  }
};
</script>

<template>
  <Head :title="title" />
  <div class="min-h-screen bg-slate-50 flex items-center justify-center p-6 select-none">
    <div class="max-w-md w-full bg-white border border-slate-200 rounded-xl p-8 shadow-sm text-center space-y-6">
      <!-- Icon illustration -->
      <div class="w-16 h-16 rounded-full bg-red-50 text-red-500 flex items-center justify-center mx-auto"
           :class="{ 'bg-amber-50 text-amber-500': status === 403, 'bg-slate-50 text-slate-500': status === 404 }">
        <svg v-if="status === 403" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
        </svg>
        <svg v-else-if="status === 404" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
          <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
        </svg>
      </div>

      <div class="space-y-2">
        <h1 class="text-xl font-bold font-display text-slate-800 leading-tight">
          {{ title }}
        </h1>
        <p class="text-sm text-slate-600 leading-normal">
          {{ description }}
        </p>
      </div>

      <!-- Action Button -->
      <div>
        <button
          v-if="status === 403"
          @click="goBack"
          class="inline-flex items-center justify-center bg-slate-800 hover:bg-slate-900 text-white font-semibold px-6 py-2.5 rounded-lg text-sm transition-colors cursor-pointer w-full"
        >
          Go Back
        </button>
        <Link
          v-else
          href="/dashboard"
          class="inline-flex items-center justify-center bg-primary hover:bg-primary-dark text-white font-semibold px-6 py-2.5 rounded-lg text-sm transition-all shadow-sm hover:shadow cursor-pointer w-full"
        >
          Go to Dashboard
        </Link>
      </div>
    </div>
  </div>
</template>
