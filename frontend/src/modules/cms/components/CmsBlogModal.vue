<script setup lang="ts">
import { useCmsStore } from "../cms.store";

const cmsStore = useCmsStore();
</script>

<template>
  <teleport to="body">
    <transition name="modal-fade">
      <div
        v-if="cmsStore.isBlogModalOpen && cmsStore.selectedBlogPost"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm font-display"
        @click.self="cmsStore.closeBlogModal"
      >
        <div
          class="relative w-full max-w-2xl max-h-[85vh] bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl flex flex-col overflow-hidden text-slate-100"
        >
          <!-- Article Header -->
          <div
            class="p-6 sm:p-8 border-b border-slate-800 bg-slate-950/60 relative"
          >
            <button
              type="button"
              class="absolute top-6 right-6 w-9 h-9 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer text-sm"
              @click="cmsStore.closeBlogModal"
            >
              ✕
            </button>

            <div class="space-y-3 pr-10">
              <div class="flex items-center gap-2">
                <span
                  class="text-[11px] font-extrabold uppercase px-2.5 py-0.5 rounded-full bg-indigo-950/80 text-indigo-300 border border-indigo-500/30"
                >
                  {{ cmsStore.selectedBlogPost.category }}
                </span>
                <span class="text-xs text-slate-500 font-medium">
                  {{ cmsStore.selectedBlogPost.date }}
                </span>
              </div>

              <h2
                class="text-xl sm:text-2xl font-black text-white tracking-tight leading-snug"
              >
                {{ cmsStore.selectedBlogPost.title }}
              </h2>

              <p class="text-xs text-slate-400 flex items-center gap-2">
                <span>By <strong class="text-slate-200">{{ cmsStore.selectedBlogPost.author }}</strong></span>
                <span>&bull;</span>
                <span>RLG Hobby Community Journal</span>
              </p>
            </div>
          </div>

          <!-- Article Content -->
          <div class="p-6 sm:p-8 overflow-y-auto space-y-6 leading-relaxed font-sans text-slate-300 text-sm">
            <div
              v-if="cmsStore.selectedBlogPost.summary"
              class="p-4 rounded-2xl bg-indigo-950/30 border border-indigo-500/20 text-indigo-200 font-medium italic text-xs leading-relaxed"
            >
              {{ cmsStore.selectedBlogPost.summary }}
            </div>

            <div class="whitespace-pre-line text-sm leading-relaxed text-slate-300">
              {{ cmsStore.selectedBlogPost.content || cmsStore.selectedBlogPost.summary }}
            </div>
          </div>

          <!-- Article Footer -->
          <div
            class="p-4 sm:p-6 border-t border-slate-800 bg-slate-950/60 flex items-center justify-between text-xs text-slate-500"
          >
            <span>Have questions? Chat with our staff or explore the catalog.</span>
            <button
              type="button"
              class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition-colors cursor-pointer"
              @click="cmsStore.closeBlogModal"
            >
              Back to Storefront
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
  transform: scale(0.96);
}
</style>
