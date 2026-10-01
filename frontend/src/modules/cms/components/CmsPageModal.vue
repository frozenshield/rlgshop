<script setup lang="ts">
import { computed } from "vue";
import { useCmsStore } from "../cms.store";

const cmsStore = useCmsStore();

const titles: Record<string, { title: string; subtitle: string; icon: string }> = {
  about: {
    title: "About RLG Hobby Shop",
    subtitle: "Authentic Japanese hobby imports & collector community story",
    icon: "🏪",
  },
  terms: {
    title: "Terms & Conditions",
    subtitle: "Collector orders, factory-sealed authenticity & returns policy",
    icon: "📜",
  },
  privacy: {
    title: "Privacy Policy",
    subtitle: "Data protection & confidential order fulfillment pledge",
    icon: "🔒",
  },
  faq: {
    title: "Collector FAQs",
    subtitle: "Frequently asked questions regarding shipping, TCG & Gunpla",
    icon: "❓",
  },
};

const currentMeta = computed(() => {
  return (
    titles[cmsStore.activePageKey] || {
      title: "Storefront Information",
      subtitle: "Official RLG Hobby Shop policy documentation",
      icon: "📄",
    }
  );
});

const pageBody = computed(() => {
  return (
    cmsStore.pages[cmsStore.activePageKey] ||
    "Information is currently being updated by store administrators."
  );
});
</script>

<template>
  <teleport to="body">
    <transition name="modal-fade">
      <div
        v-if="cmsStore.isPageModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm font-display"
        @click.self="cmsStore.closePageModal"
      >
        <div
          class="relative w-full max-w-2xl max-h-[85vh] bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl flex flex-col overflow-hidden text-slate-100"
        >
          <!-- Modal Header -->
          <div
            class="flex items-center justify-between p-6 border-b border-slate-800 bg-slate-950/60"
          >
            <div class="flex items-center gap-3.5">
              <div
                class="w-12 h-12 rounded-2xl bg-indigo-950/80 border border-indigo-500/30 flex items-center justify-center text-2xl shadow-inner"
              >
                {{ currentMeta.icon }}
              </div>
              <div>
                <h3 class="text-xl font-extrabold text-white tracking-tight">
                  {{ currentMeta.title }}
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                  {{ currentMeta.subtitle }}
                </p>
              </div>
            </div>

            <button
              type="button"
              class="w-9 h-9 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer text-sm"
              @click="cmsStore.closePageModal"
            >
              ✕
            </button>
          </div>

          <!-- Modal Body Content -->
          <div class="p-6 sm:p-8 overflow-y-auto space-y-4 leading-relaxed text-sm text-slate-300">
            <div
              class="whitespace-pre-line font-sans bg-slate-950/40 p-5 rounded-2xl border border-slate-800/80 text-sm leading-relaxed"
            >
              {{ pageBody }}
            </div>
          </div>

          <!-- Modal Footer -->
          <div
            class="p-4 sm:p-6 border-t border-slate-800 bg-slate-950/60 flex items-center justify-between text-xs text-slate-500"
          >
            <div class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <span>RLG Hobby Shop &bull; Verified Official Policy</span>
            </div>
            <button
              type="button"
              class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold transition-colors cursor-pointer"
              @click="cmsStore.closePageModal"
            >
              Close
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
