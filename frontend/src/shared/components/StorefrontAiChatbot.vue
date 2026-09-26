<script setup lang="ts">
import { ref, computed, nextTick, onMounted, watch } from "vue";
import { useCartStore } from "@/modules/cart/cart.store";
import { useCatalogStore } from "@/modules/catalog/catalog.store";
import type { ToyProduct } from "@/shared/types/toy.types";

interface ChatMessage {
  id: string;
  role: "user" | "model";
  text: string;
  timestamp: string;
  products?: SuggestedProduct[];
  suggestedActions?: string[];
}

interface SuggestedProduct {
  id: number | string;
  name: string;
  sku: string;
  price: number;
  stock: number;
  in_stock: boolean;
  image_url: string;
  brand?: string;
  category?: string;
  condition?: string;
  rating?: number;
}

const cartStore = useCartStore();
const catalogStore = useCatalogStore();

const isOpen = ref(false);
const inputMessage = ref("");
const isLoading = ref(false);
const messagesContainer = ref<HTMLElement | null>(null);
const chatInput = ref<HTMLInputElement | null>(null);
const hasUnread = ref(true);
const showWelcomeBubble = ref(true);

const defaultQuickPrompts = [
  {
    label: "🃏 Pokémon 151 Stock",
    prompt:
      "Do you have the Pokémon Scarlet & Violet 151 Elite Trainer Box in stock and how much is it?",
  },
  {
    label: "📦 One Piece OP-05",
    prompt: "How many boxes of One Piece Card Game OP-05 do you have in stock?",
  },
  {
    label: "🎟️ Active Promos",
    prompt: "What promo codes can I apply for discounts today?",
  },
  {
    label: "🚚 Shipping & Rates",
    prompt:
      "How much is shipping and what is the minimum spend for free shipping?",
  },
  {
    label: "🤖 Gunpla Model Kits",
    prompt: "Show me available Bandai Gunpla kits in stock with prices.",
  },
];

const quickPrompts = ref(defaultQuickPrompts);

const messages = ref<ChatMessage[]>([
  {
    id: "welcome",
    role: "model",
    text: "Konnichiwa, collector! ✨ I'm **Aiko**, your RLG Hobby AI concierge!\n\nI have live access to our warehouse inventory, real-time stock levels, pricing, active promo codes, and shipping policies. How can I help level up your collection today? 🃏📦",
    timestamp: "Just now",
    suggestedActions: [
      "🃏 Pokémon 151 Stock",
      "📦 One Piece OP-05 Box",
      "🎟️ Active Promo Codes",
      "🚚 Shipping policy",
    ],
  },
]);

// Load quick prompts from backend
const fetchQuickPrompts = async () => {
  try {
    const res = await fetch("/api/ai/chat/quick-prompts");
    if (res.ok) {
      const json = await res.json();
      if (json.success && Array.isArray(json.data) && json.data.length > 0) {
        quickPrompts.value = json.data;
      }
    }
  } catch (err) {
    console.warn("Could not load dynamic quick prompts, using defaults.", err);
  }
};

onMounted(() => {
  fetchQuickPrompts();
  setTimeout(() => {
    showWelcomeBubble.value = false;
  }, 10000);
});

const scrollToBottom = async () => {
  await nextTick();
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
};

watch(isOpen, (newVal) => {
  if (newVal) {
    hasUnread.value = false;
    showWelcomeBubble.value = false;
    scrollToBottom();
    nextTick(() => {
      chatInput.value?.focus();
    });
  }
});

const toggleChat = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    showWelcomeBubble.value = false;
  }
};

const clearChat = () => {
  messages.value = [
    {
      id: "welcome-" + Date.now(),
      role: "model",
      text: "Memory cleared! 🧹 Ask me anything about products, current stock counts, or checkout coupons.",
      timestamp: "Just now",
      suggestedActions: [
        "🃏 Check Pokémon TCG stock",
        "📦 Show One Piece boxes",
        "🎟️ What promo codes are active?",
      ],
    },
  ];
};

const formatMarkdown = (text: string): string => {
  if (!text) return "";

  let html = text
    // Escape HTML tags to prevent XSS
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    // Bold: **text**
    .replace(
      /\*\*(.*?)\*\*/g,
      '<strong class="font-bold text-amber-300">$1</strong>',
    )
    // Italic: *text*
    .replace(/\*(.*?)\*/g, '<em class="italic text-slate-300">$1</em>')
    // Inline code: `code`
    .replace(
      /`([^`]+)`/g,
      '<code class="px-1.5 py-0.5 rounded bg-slate-800 text-rose-300 font-mono text-[11px] border border-slate-700/60">$1</code>',
    )
    // Bullet points: • item or - item
    .replace(
      /^[•\-]\s+(.*)$/gm,
      '<li class="ml-4 list-disc text-slate-200 my-0.5">$1</li>',
    )
    // Line breaks
    .replace(/\n\n/g, "<br/><br/>")
    .replace(/\n/g, "<br/>");

  return html;
};

const sendMessage = async (customPrompt?: string) => {
  const textToSend = customPrompt || inputMessage.value.trim();
  if (!textToSend || isLoading.value) return;

  inputMessage.value = "";

  // Append user message
  const userMsgId = "user-" + Date.now();
  messages.value.push({
    id: userMsgId,
    role: "user",
    text: textToSend,
    timestamp: new Date().toLocaleTimeString([], {
      hour: "2-digit",
      minute: "2-digit",
    }),
  });

  isLoading.value = true;
  scrollToBottom();

  try {
    // Format history for backend
    const historyPayload = messages.value
      .filter((m) => m.id !== userMsgId && m.id !== "welcome")
      .slice(-6)
      .map((m) => ({
        role: m.role === "model" ? "model" : "user",
        content: m.text,
      }));

    const res = await fetch("/api/ai/chat", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        message: textToSend,
        history: historyPayload,
      }),
    });

    const json = await res.json();

    if (res.ok && json.success && json.data) {
      messages.value.push({
        id: "bot-" + Date.now(),
        role: "model",
        text: json.data.reply || "I've checked our database!",
        timestamp: new Date().toLocaleTimeString([], {
          hour: "2-digit",
          minute: "2-digit",
        }),
        products: json.data.suggested_products || [],
        suggestedActions: json.data.suggested_actions || [],
      });
    } else {
      messages.value.push({
        id: "bot-err-" + Date.now(),
        role: "model",
        text:
          json.message ||
          "I'm having a brief connection hitch checking the warehouse, but I'm ready to try again! ⚡",
        timestamp: new Date().toLocaleTimeString([], {
          hour: "2-digit",
          minute: "2-digit",
        }),
      });
    }
  } catch (e: any) {
    messages.value.push({
      id: "bot-err-" + Date.now(),
      role: "model",
      text: "Unable to reach the assistant server. Please check your internet connection or try again shortly.",
      timestamp: new Date().toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
      }),
    });
  } finally {
    isLoading.value = false;
    scrollToBottom();
  }
};

// Convert suggested product to ToyProduct format and add to cart
const handleAddToCart = (item: SuggestedProduct) => {
  const toy: ToyProduct = {
    id: "prod-" + item.id,
    name: item.name,
    slug: item.sku ? item.sku.toLowerCase() : "product-" + item.id,
    description: item.name,
    category: "tcg",
    ageGroup: "12+",
    price: item.price,
    stock: item.stock,
    brand: item.brand || "The Pokémon Company",
    imageUrl: item.image_url,
    galleryImages: [item.image_url],
    tags: [item.brand || "Import", item.condition || "New"],
    rating: item.rating || 5.0,
    reviewCount: 10,
    features: [
      "100% Authentic Japanese Import",
      item.condition || "Mint Condition",
      `SKU: ${item.sku}`,
    ],
  };

  cartStore.addItem(toy, 1);
};

// View product in detail modal
const handleViewProduct = (item: SuggestedProduct) => {
  const toy: ToyProduct = {
    id: "prod-" + item.id,
    name: item.name,
    slug: item.sku ? item.sku.toLowerCase() : "product-" + item.id,
    description: item.name,
    category: "tcg",
    ageGroup: "12+",
    price: item.price,
    stock: item.stock,
    brand: item.brand || "The Pokémon Company",
    imageUrl: item.image_url,
    galleryImages: [item.image_url],
    tags: [item.brand || "Import", item.condition || "New"],
    rating: item.rating || 5.0,
    reviewCount: 10,
    features: [
      "100% Authentic Japanese Import",
      item.condition || "Mint Condition",
      `SKU: ${item.sku}`,
    ],
  };

  catalogStore.openDetailModal(toy);
};
</script>

<template>
  <div class="fixed bottom-6 right-6 z-50 select-none font-display">
    <!-- Chat Window Container -->
    <transition
      enter-active-class="transition duration-300 ease-out transform"
      enter-from-class="opacity-0 translate-y-6 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-200 ease-in transform"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-6 scale-95"
    >
      <div
        v-if="isOpen"
        class="w-[94vw] sm:w-[430px] h-[610px] max-h-[85vh] bg-slate-950/95 backdrop-blur-xl border border-slate-800 rounded-3xl shadow-2xl shadow-black/80 flex flex-col overflow-hidden text-slate-100 mb-4 ring-1 ring-white/10"
      >
        <!-- Header -->
        <div
          class="p-4 bg-gradient-to-r from-slate-900 via-slate-900 to-indigo-950/80 border-b border-slate-800/80 flex items-center justify-between"
        >
          <div class="flex items-center gap-3">
            <div class="relative">
              <div
                class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-rose-500 via-purple-600 to-amber-400 p-0.5 shadow-md shadow-rose-950/50"
              >
                <div
                  class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center text-lg"
                >
                  ✨
                </div>
              </div>
              <span
                class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 border-2 border-slate-950 rounded-full animate-pulse"
              ></span>
            </div>
            <div>
              <div class="flex items-center gap-1.5">
                <h3 class="font-black text-sm text-white tracking-tight">
                  Aiko AI
                </h3>
                <span
                  class="text-[9px] font-extrabold uppercase px-1.5 py-0.2 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30"
                >
                  Concierge
                </span>
              </div>
              <p
                class="text-[10px] text-emerald-400 font-medium flex items-center gap-1"
              >
                <span>●</span> Real-time Warehouse Stock Sync
              </p>
            </div>
          </div>

          <div class="flex items-center gap-1">
            <button
              type="button"
              class="p-2 rounded-xl text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition-colors cursor-pointer text-xs"
              title="Clear conversation"
              @click="clearChat"
            >
              🧹
            </button>
            <button
              type="button"
              class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors cursor-pointer text-sm font-bold"
              title="Minimize chat"
              @click="toggleChat"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Quick Topic Suggestion Bar -->
        <div
          class="px-3 py-2 bg-slate-900/60 border-b border-slate-800/60 overflow-x-auto flex gap-1.5 scrollbar-none"
        >
          <button
            v-for="(qp, idx) in quickPrompts"
            :key="idx"
            type="button"
            class="px-2.5 py-1 rounded-full bg-slate-800/80 hover:bg-rose-600/30 hover:text-rose-200 text-slate-300 text-[11px] font-medium whitespace-nowrap border border-slate-700/60 transition-all cursor-pointer flex-shrink-0"
            :disabled="isLoading"
            @click="sendMessage(qp.prompt)"
          >
            {{ qp.label }}
          </button>
        </div>

        <!-- Messages Area -->
        <div
          ref="messagesContainer"
          class="flex-1 p-4 overflow-y-auto space-y-4 text-xs scrollbar-thin scrollbar-thumb-slate-800"
        >
          <div
            v-for="msg in messages"
            :key="msg.id"
            class="flex flex-col"
            :class="msg.role === 'user' ? 'items-end' : 'items-start'"
          >
            <!-- Sender Avatar & Timestamp header for Bot -->
            <div
              v-if="msg.role === 'model'"
              class="flex items-center gap-1.5 mb-1 px-1 text-[10px] text-slate-400 font-semibold"
            >
              <span class="text-rose-400">✨ Aiko</span>
              <span>•</span>
              <span>{{ msg.timestamp }}</span>
            </div>

            <!-- Bubble Content -->
            <div
              class="max-w-[88%] rounded-2xl p-3.5 leading-relaxed shadow-sm"
              :class="
                msg.role === 'user'
                  ? 'bg-gradient-to-r from-rose-600 via-rose-700 to-indigo-600 text-white rounded-tr-xs'
                  : 'bg-slate-900/90 text-slate-200 border border-slate-800 rounded-tl-xs'
              "
            >
              <div v-html="formatMarkdown(msg.text)" class="space-y-1"></div>
            </div>

            <!-- Embedded Product Cards (if any mentioned in response) -->
            <div
              v-if="msg.products && msg.products.length > 0"
              class="mt-2.5 w-full max-w-[94%] space-y-2"
            >
              <p
                class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-1"
              >
                Featured Catalog Matches:
              </p>
              <div
                v-for="prod in msg.products"
                :key="prod.id"
                class="p-2.5 bg-slate-900/95 border border-slate-800 rounded-2xl flex items-center gap-3 hover:border-slate-700 transition-all shadow-md group"
              >
                <!-- Thumbnail -->
                <img
                  :src="prod.image_url"
                  :alt="prod.name"
                  class="w-14 h-14 rounded-xl object-cover bg-slate-950 flex-shrink-0 border border-slate-800"
                  loading="lazy"
                />

                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-1.5">
                    <span
                      class="text-[9px] font-extrabold px-1.5 py-0.2 rounded"
                      :class="
                        prod.in_stock
                          ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'
                          : 'bg-rose-500/20 text-rose-400 border border-rose-500/30'
                      "
                    >
                      {{
                        prod.in_stock
                          ? `In Stock (${prod.stock})`
                          : "Out of Stock"
                      }}
                    </span>
                    <span
                      v-if="prod.sku"
                      class="text-[9px] text-slate-400 font-mono truncate"
                    >
                      {{ prod.sku }}
                    </span>
                  </div>

                  <h4
                    class="text-xs font-bold text-white truncate mt-0.5 group-hover:text-rose-400 transition-colors"
                  >
                    {{ prod.name }}
                  </h4>

                  <div class="flex items-center justify-between mt-1">
                    <span class="text-xs font-black text-amber-400 font-mono">
                      ₱{{ Number(prod.price).toLocaleString() }}
                    </span>

                    <div class="flex items-center gap-1">
                      <button
                        type="button"
                        class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-bold transition-colors cursor-pointer"
                        @click="handleViewProduct(prod)"
                      >
                        Inspect
                      </button>
                      <button
                        v-if="prod.in_stock"
                        type="button"
                        class="px-2.5 py-1 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-[10px] font-bold transition-all shadow-sm active:scale-95 cursor-pointer flex items-center gap-1"
                        @click="handleAddToCart(prod)"
                      >
                        <span>+</span>
                        <span>Cart</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Follow-up Action Suggestion Chips -->
            <div
              v-if="
                msg.suggestedActions &&
                msg.suggestedActions.length > 0 &&
                !isLoading
              "
              class="mt-2 flex flex-wrap gap-1.5 max-w-[90%]"
            >
              <button
                v-for="(action, aIdx) in msg.suggestedActions"
                :key="aIdx"
                type="button"
                class="px-2.5 py-1 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-300 text-[10px] font-semibold border border-slate-800 hover:border-slate-700 transition-all cursor-pointer text-left"
                @click="sendMessage(action)"
              >
                {{ action }}
              </button>
            </div>
          </div>

          <!-- Typing Indicator -->
          <div v-if="isLoading" class="flex flex-col items-start space-y-1">
            <span class="text-[10px] text-slate-400 font-semibold px-1"
              >✨ Aiko checking vault...</span
            >
            <div
              class="p-3 rounded-2xl bg-slate-900/80 border border-slate-800 rounded-tl-xs flex items-center gap-1.5"
            >
              <span
                class="w-2 h-2 rounded-full bg-rose-500 animate-bounce"
              ></span>
              <span
                class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce [animation-delay:0.2s]"
              ></span>
              <span
                class="w-2 h-2 rounded-full bg-amber-400 animate-bounce [animation-delay:0.4s]"
              ></span>
              <span class="text-[11px] text-slate-400 ml-1"
                >Checking live warehouse database...</span
              >
            </div>
          </div>
        </div>

        <!-- Input Area Footer -->
        <div class="p-3 bg-slate-900/95 border-t border-slate-800/80">
          <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
            <div class="relative flex-1">
              <input
                ref="chatInput"
                v-model="inputMessage"
                type="text"
                :disabled="isLoading"
                placeholder="Ask Aiko about stock, cards, promo codes, shipping..."
                class="w-full py-2.5 px-3.5 pr-8 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-xs placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500/30 transition-all disabled:opacity-60"
              />
              <span
                v-if="isLoading"
                class="absolute right-3 top-3 w-3 h-3 border-2 border-rose-500 border-t-transparent rounded-full animate-spin"
              ></span>
            </div>

            <button
              type="submit"
              :disabled="!inputMessage.trim() || isLoading"
              class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-600 via-rose-700 to-indigo-600 hover:from-rose-500 hover:to-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed text-white flex items-center justify-center font-bold text-sm shadow-md transition-all active:scale-95 cursor-pointer flex-shrink-0"
              title="Send message"
            >
              <svg
                class="w-4 h-4 transform rotate-90"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"
                />
              </svg>
            </button>
          </form>

          <div
            class="flex items-center justify-between mt-2 px-1 text-[10px] text-slate-500"
          >
            <span>Powered by Gemini 3.8 Flash</span>
            <span class="flex items-center gap-1">
              <span class="w-1 h-1 rounded-full bg-emerald-400"></span>
              Live Vault Ready
            </span>
          </div>
        </div>
      </div>
    </transition>

    <!-- Welcome Speech Bubble for immediate discoverability -->
    <transition
      enter-active-class="transition duration-300 ease-out transform"
      enter-from-class="opacity-0 translate-y-2 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-200 ease-in transform"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="showWelcomeBubble && !isOpen"
        class="absolute bottom-16 right-0 w-64 p-3 bg-slate-950/95 backdrop-blur-md border border-rose-500/40 rounded-2xl shadow-2xl shadow-black/80 text-xs text-slate-200 select-none cursor-pointer hover:border-rose-400 transition-colors"
        @click="toggleChat"
      >
        <div
          class="flex items-center justify-between pb-1.5 mb-1.5 border-b border-slate-800"
        >
          <div
            class="flex items-center gap-1.5 text-amber-300 font-bold text-[11px]"
          >
            <span>✨</span>
            <span>Aiko AI Assistant</span>
          </div>
          <button
            type="button"
            class="text-slate-500 hover:text-slate-300 text-xs cursor-pointer p-0.5"
            @click.stop="showWelcomeBubble = false"
          >
            ✕
          </button>
        </div>
        <p class="text-[11px] leading-relaxed text-slate-300">
          Need help checking live stock counts, Japanese imports, or active
          coupons? Chat with me!
        </p>
        <!-- Speech Bubble pointer -->
        <div
          class="absolute -bottom-1.5 right-6 w-3 h-3 bg-slate-950 border-r border-b border-rose-500/40 transform rotate-45"
        ></div>
      </div>
    </transition>

    <!-- Floating Trigger Launcher Button -->
    <button
      type="button"
      class="group relative flex items-center gap-2.5 p-1.5 pr-2 sm:pr-4 rounded-full bg-slate-950/95 backdrop-blur-xl border border-rose-500/50 hover:border-rose-400 text-white shadow-2xl shadow-rose-950/60 transition-all duration-300 hover:scale-105 active:scale-95 cursor-pointer ring-1 ring-white/10"
      @click="toggleChat"
      :title="isOpen ? 'Close assistant' : 'Chat with Aiko AI Assistant'"
    >
      <!-- Sparkle Avatar Circle -->
      <div
        class="w-11 h-11 rounded-full bg-gradient-to-tr from-rose-600 via-purple-600 to-amber-400 p-0.5 shadow-lg shadow-rose-600/30 group-hover:rotate-12 transition-transform flex-shrink-0"
      >
        <div
          class="w-full h-full bg-slate-950 rounded-full flex items-center justify-center text-lg"
        >
          {{ isOpen ? "✕" : "✨" }}
        </div>
      </div>

      <div class="text-left hidden sm:block">
        <div class="flex items-center gap-1.5">
          <span class="text-xs font-black text-white tracking-tight"
            >Ask Aiko AI</span
          >
          <span
            class="text-[9px] font-extrabold px-1 py-0.2 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 animate-pulse"
          >
            Live
          </span>
        </div>
        <p class="text-[10px] text-slate-400 font-medium">
          Stocks &amp; Orders Concierge
        </p>
      </div>

      <!-- Pulse ping indicator for unread -->
      <span
        v-if="hasUnread && !isOpen"
        class="absolute -top-1 -right-1 flex h-3.5 w-3.5"
      >
        <span
          class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"
        ></span>
        <span
          class="relative inline-flex rounded-full h-3.5 w-3.5 bg-rose-500 border border-white/40"
        ></span>
      </span>
    </button>
  </div>
</template>
