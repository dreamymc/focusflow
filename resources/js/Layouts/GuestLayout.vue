<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <div class="min-h-screen flex font-sans text-text antialiased">
    <!-- Brand Panel (Left) - Hidden on Mobile -->
    <div class="hidden lg:flex lg:w-1/2 bg-dark-bg relative overflow-hidden flex-col justify-between p-12 select-none">
      <!-- Background subtle radial gradient bloom -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -left-40 w-[500px] h-[500px] rounded-full bg-primary/10 blur-[100px]"></div>
        <div class="absolute -bottom-40 -right-40 w-[500px] h-[500px] rounded-full bg-secondary/10 blur-[100px]"></div>
        <div class="absolute top-1/2 left-1/3 w-[300px] h-[300px] rounded-full bg-indigo-500/5 blur-[80px]"></div>
      </div>

      <!-- Floating Geometric Shapes -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <!-- Rotated square -->
        <div class="absolute top-1/4 left-1/4 w-32 h-32 border border-white/5 rounded-2xl rotate-12 blur-[0.5px]"></div>
        <!-- Rotated square 2 -->
        <div class="absolute top-1/3 right-1/3 w-16 h-16 border border-white/5 rotate-45 blur-[0.5px]"></div>
        <!-- Empty ring -->
        <div class="absolute bottom-1/4 right-1/4 w-24 h-24 rounded-full border border-white/5 blur-[0.5px]"></div>
        <!-- Dotted pattern -->
        <div class="absolute bottom-16 left-16 opacity-5 blur-[0.5px]">
          <svg width="96" height="96" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <pattern id="dots" x="0" y="0" width="16" height="16" patternUnits="userSpaceOnUse">
                <circle cx="2" cy="2" r="1.5" fill="white" />
              </pattern>
            </defs>
            <rect width="96" height="96" fill="url(#dots)" />
          </svg>
        </div>
      </div>

      <!-- Header / Logo -->
      <div class="relative z-10 flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl bg-white/10 flex items-center justify-center border border-white/10 shadow-lg backdrop-blur-md">
          <span class="font-display font-extrabold text-2xl text-white">F</span>
        </div>
        <span class="font-display text-2xl font-extrabold tracking-tight text-white">
          Focus<span class="text-primary">Flow</span>
        </span>
      </div>

      <!-- Premium Testimonial/Quote Block -->
      <div class="relative z-10 max-w-md my-auto space-y-6">
        <div class="space-y-4">
          <!-- Premium Quote Mark -->
          <svg class="h-8 w-8 text-white/20" fill="currentColor" viewBox="0 0 32 32" aria-hidden="true">
            <path d="M9.333 4.8C4.12 8.167 1.6 14.133 1.6 22.7c0 4.533 2.72 7.7 6.8 7.7 4.12 0 7.04-3.4 7.04-7.7 0-3.967-2.6-7.033-6.44-7.567.907-2.6 2.947-4.433 5.387-5.5L9.333 4.8zm16 0C20.12 8.167 17.6 14.133 17.6 22.7c0 4.533 2.72 7.7 6.8 7.7 4.12 0 7.04-3.4 7.04-7.7 0-3.967-2.6-7.033-6.44-7.567.907-2.6 2.947-4.433 5.387-5.5L25.333 4.8z" />
          </svg>
          <blockquote class="text-white text-xl lg:text-2xl font-light leading-relaxed italic">
            "FocusFlow has completely changed how our engineering and design teams collaborate. Real-time updates, clear sprint cycles, and zero visual noise."
          </blockquote>
        </div>
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-full bg-white/10 flex items-center justify-center border border-white/10 shadow-inner">
            <span class="font-display font-bold text-sm text-white">AC</span>
          </div>
          <div>
            <p class="font-semibold text-white text-sm">Lead Product Designer</p>
            <p class="text-white/50 text-xs">Acme Corp</p>
          </div>
        </div>
      </div>

      <!-- Footer info -->
      <div class="relative z-10 text-white/50 text-sm">
        &copy; {{ new Date().getFullYear() }} FocusFlow. All rights reserved.
      </div>
    </div>

    <!-- Form Panel (Right) -->
    <div class="w-full lg:w-1/2 flex items-center justify-center bg-surface-2 p-6 md:p-12">
      <div class="w-full max-w-md">
        <!-- Logo for Mobile only -->
        <div class="flex items-center gap-3 mb-8 justify-center lg:hidden">
          <div class="h-8 w-8 rounded-lg bg-primary flex items-center justify-center shadow-md">
            <span class="font-display font-extrabold text-lg text-white">F</span>
          </div>
          <span class="font-display text-xl font-extrabold tracking-tight text-text">
            Focus<span class="text-primary">Flow</span>
          </span>
        </div>
        
        <!-- Center Form Card -->
        <div class="bg-form-bg rounded-xl border border-border p-8 shadow-md">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>
