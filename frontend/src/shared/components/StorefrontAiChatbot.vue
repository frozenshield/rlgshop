<script setup lang="ts">
import { ref, computed, nextTick, onMounted, watch } from "vue";
import { useCartStore } from "@/modules/cart/cart.store";
import { useCatalogStore } from "@/modules/catalog/catalog.store";
import { useAuthStore } from "@/modules/auth/auth.store";
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

interface StaffMessageRecord {
  id: number;
  user_id: number;
  subject: string;
  message: string;
  status: "ongoing" | "resolve";
  staff_reply?: string | null;
  staff?: { name?: string };
  resolved_at?: string | null;
  created_at?: string;
}

const cartStore = useCartStore();
const catalogStore = useCatalogStore();
const authStore = useAuthStore();

// Main Drawer State
const isOpen = ref(false);
const activeMode = ref<"ai" | "staff">("ai");

// AI Chatbot State
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
    label: "🤖 Japanese Gunpla",
    prompt:
      "Show me Japanese Gunpla model kits and scale figures available right now.",
  },
];

const quickPrompts = ref(defaultQuickPrompts);

const messages = ref<ChatMessage[]>([
  {
    id: "welcome-1",
    role: "model",
    text: "Kon'nichiwa, Trainer! ⚡ I am Aiko, your RLG Hobby Concierge. Looking for sealed booster boxes, single card restocks, Gunpla kits, or active discount codes? Ask me anything!",
    timestamp: new Date().toLocaleTimeString([], {
      hour: "2-digit",
      minute: "2-digit",
    }),
  },
]);

// Staff Support Messaging State (customer_message API)
const staffMessages = ref<StaffMessageRecord[]>([]);
const isLoadingStaff = ref(false);
const isSendingStaff = ref(false);
const staffSubject = ref("Order & Delivery Tracking");
const staffMessageText = ref("");
const staffCustomSubject = ref("");
const staffSubTab = ref<"compose" | "history">("compose");
const staffSuccessNotice = ref("");
const staffErrorNotice = ref("");

const subjectOptions = [
  "Order & Delivery Tracking",
  "Pre-Order Allocation (Pokémon / One Piece)",
  "Card Condition & Authenticity Check",
  "Product Restock Notification",
  "Wholesale & Bulk Inquiries",
  "Other Support Question",
];

const openAuthModal = () => {
  if (typeof window !== "undefined") {
    window.dispatchEvent(new CustomEvent("open-auth-modal"));
  }
};

const fetchStaffMessages = async () => {
  if (!authStore.token) {
    staffMessages.value = [];
    return;
  }

  isLoadingStaff.value = true;
  try {
    const headers: Record<string, string> = {
      Authorization: `Bearer ${authStore.token}`,
    };
    const res = await fetch("/api/customer-messages", { headers });
    if (res.ok) {
      const json = await res.json();
      if (json.success && Array.isArray(json.data)) {
        staffMessages.value = json.data;
      }
    }
  } catch (e) {
    console.warn("Could not load customer messages", e);
  } finally {
    isLoadingStaff.value = false;
  }
};

const hasStaffReply = computed(() => {
  return staffMessages.value.some((m) => !!m.staff_reply);
});

const sendStaffMessage = async () => {
  if (!staffMessageText.value.trim()) {
    staffErrorNotice.value = "Please write a message for the staff.";
    return;
  }

  isSendingStaff.value = true;
  staffErrorNotice.value = "";
  staffSuccessNotice.value = "";

  const finalSubject =
    staffSubject.value === "Other Support Question" &&
    staffCustomSubject.value.trim()
      ? staffCustomSubject.value.trim()
      : staffSubject.value;

  try {
    const headers: Record<string, string> = {
      "Content-Type": "application/json",
    };
    if (authStore.token) {
      headers["Authorization"] = `Bearer ${authStore.token}`;
    }

    const userId = authStore.currentUser?.id
      ? parseInt(String(authStore.currentUser.id).replace(/\D/g, ""))
      : undefined;

    const res = await fetch("/api/customer-messages", {
      method: "POST",
      headers,
      body: JSON.stringify({
        subject: finalSubject,
        message: staffMessageText.value.trim(),
        user_id: isNaN(userId as number) ? undefined : userId,
      }),
    });

    if (res.ok) {
      const json = await res.json();
      staffSuccessNotice.value =
        "Your message has been sent to our staff support desk! We will reply promptly.";
      staffMessageText.value = "";
      staffCustomSubject.value = "";
      if (json.data) {
        staffMessages.value.unshift(json.data);
      } else {
        await fetchStaffMessages();
      }
      staffSubTab.value = "history";
    } else {
      const err = await res.json();
      staffErrorNotice.value =
        err.message || "Failed to send message. Please try again.";
    }
  } catch (e) {
    staffErrorNotice.value = "Network error sending support message.";
  } finally {
    isSendingStaff.value = false;
  }
};

onMounted(() => {
  if (authStore.token) {
    fetchStaffMessages();
  }
});

watch(
  () => authStore.token,
  (newToken) => {
    if (newToken) {
      fetchStaffMessages();
    } else {
      staffMessages.value = [];
    }
  },
);

const openAiChat = () => {
  if (isOpen.value && activeMode.value === "ai") {
    isOpen.value = false;
  } else {
    activeMode.value = "ai";
    isOpen.value = true;
    hasUnread.value = false;
    showWelcomeBubble.value = false;
    scrollToBottom();
  }
};

const openStaffSupport = () => {
  if (isOpen.value && activeMode.value === "staff") {
    isOpen.value = false;
  } else {
    activeMode.value = "staff";
    isOpen.value = true;
    showWelcomeBubble.value = false;
    fetchStaffMessages();
  }
};

const toggleChat = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    showWelcomeBubble.value = false;
  }
};

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  });
};

const clearChat = () => {
  messages.value = [
    {
      id: "welcome-reset-" + Date.now(),
      role: "model",
      text: "Chat cleared! How can I assist your collector hunt today? ⚡",
      timestamp: new Date().toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
      }),
    },
  ];
  quickPrompts.value = defaultQuickPrompts;
};

const sendMessage = async (customText?: string) => {
  const query = customText || inputMessage.value;
  if (!query.trim() || isLoading.value) return;

  const userMsgId = "user-" + Date.now();
  messages.value.push({
    id: userMsgId,
    role: "user",
    text: query.trim(),
    timestamp: new Date().toLocaleTimeString([], {
      hour: "2-digit",
      minute: "2-digit",
    }),
  });

  inputMessage.value = "";
  isLoading.value = true;
  scrollToBottom();

  try {
    const historyPayload = messages.value
      .filter(
        (m) => m.id !== userMsgId && (m.role === "user" || m.role === "model"),
      )
      .map((m) => ({
        role: m.role,
        text: m.text,
      }));

    const res = await fetch("/api/ai/chatbot", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        message: query.trim(),
        history: historyPayload.slice(-8),
      }),
    });

    if (res.ok) {
      const data = await res.json();
      messages.value.push({
        id: "bot-" + Date.now(),
        role: "model",
        text: data.reply || "I found some collector items in stock for you!",
        timestamp: new Date().toLocaleTimeString([], {
          hour: "2-digit",
          minute: "2-digit",
        }),
        products: data.products || [],
        suggestedActions: data.suggestedActions || [],
      });

      if (data.suggestedActions && data.suggestedActions.length > 0) {
        quickPrompts.value = data.suggestedActions.map((act: string) => ({
          label: act,
          prompt: act,
        }));
      }
    } else {
      messages.value.push({
        id: "bot-err-" + Date.now(),
        role: "model",
        text: "I'm temporarily having trouble synchronizing with the warehouse database. Please try asking again in a moment!",
        timestamp: new Date().toLocaleTimeString([], {
          hour: "2-digit",
          minute: "2-digit",
        }),
      });
    }
  } catch (err) {
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

const formatTimeAgo = (dateStr?: string) => {
  if (!dateStr) return "Just now";
  try {
    return new Date(dateStr).toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return dateStr;
  }
};
</script>

<template>
  <div
    class="fixed bottom-6 right-6 z-50 select-none font-display flex flex-col items-end"
  >
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
        class="w-[94vw] sm:w-[440px] h-[620px] max-h-[85vh] bg-slate-950/95 backdrop-blur-xl border border-slate-800 rounded-3xl shadow-2xl shadow-black/80 flex flex-col overflow-hidden text-slate-100 mb-4 ring-1 ring-white/10"
      >
        <!-- Master Mode Switch Header -->
        <div
          class="p-3 bg-slate-900/90 border-b border-slate-800/80 flex items-center justify-between gap-2"
        >
          <!-- Two mode tabs -->
          <div
            class="flex items-center gap-1.5 p-1 bg-slate-950 rounded-2xl border border-slate-800 flex-1"
          >
            <button
              type="button"
              class="flex-1 py-1.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-1.5"
              :class="
                activeMode === 'staff'
                  ? 'bg-gradient-to-r from-indigo-600 to-blue-600 text-white shadow-md'
                  : 'text-slate-400 hover:text-white'
              "
              @click="activeMode = 'staff'"
            >
              <span>💬</span>
              <span>Message Staff</span>
              <span
                v-if="hasStaffReply"
                class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"
              ></span>
            </button>

            <button
              type="button"
              class="flex-1 py-1.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-1.5"
              :class="
                activeMode === 'ai'
                  ? 'bg-gradient-to-r from-rose-600 to-purple-600 text-white shadow-md'
                  : 'text-slate-400 hover:text-white'
              "
              @click="activeMode = 'ai'"
            >
              <span>✨</span>
              <span>Ask Aiko AI</span>
              <span
                class="text-[9px] px-1 py-0.2 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30"
              >
                Live
              </span>
            </button>
          </div>

          <button
            type="button"
            class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors cursor-pointer text-sm font-bold flex-shrink-0"
            title="Minimize"
            @click="isOpen = false"
          >
            ✕
          </button>
        </div>

        <!-- ================= MODE 1: STAFF SUPPORT MESSAGING ================= -->
        <div
          v-if="activeMode === 'staff'"
          class="flex-1 flex flex-col min-h-0 bg-slate-950"
        >
          <!-- Staff Subheader -->
          <div
            class="px-4 py-3 bg-gradient-to-r from-indigo-950/60 via-slate-900 to-slate-950 border-b border-slate-800/80 flex items-center justify-between"
          >
            <div class="flex items-center gap-2.5">
              <div
                class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 via-blue-600 to-amber-400 p-0.5 flex-shrink-0"
              >
                <div
                  class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center text-sm font-bold"
                >
                  🧑‍💼
                </div>
              </div>
              <div>
                <h4 class="text-xs font-black text-white">
                  RLG Collector Support Desk
                </h4>
                <p
                  class="text-[10px] text-emerald-400 flex items-center gap-1 font-semibold"
                >
                  <span
                    class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"
                  ></span>
                  Human Staff Online &bull; Avg reply: under 15 mins
                </p>
              </div>
            </div>

            <!-- Compose vs History subtabs -->
            <div
              class="flex items-center gap-1 bg-slate-900 p-1 rounded-xl border border-slate-800 text-[11px]"
            >
              <button
                type="button"
                class="px-2.5 py-1 rounded-lg font-bold transition-colors cursor-pointer"
                :class="
                  staffSubTab === 'compose'
                    ? 'bg-indigo-600 text-white'
                    : 'text-slate-400 hover:text-white'
                "
                @click="staffSubTab = 'compose'"
              >
                Send
              </button>
              <button
                type="button"
                class="px-2.5 py-1 rounded-lg font-bold transition-colors cursor-pointer flex items-center gap-1"
                :class="
                  staffSubTab === 'history'
                    ? 'bg-indigo-600 text-white'
                    : 'text-slate-400 hover:text-white'
                "
                @click="staffSubTab = 'history'"
              >
                <span>Inbox</span>
                <span
                  v-if="staffMessages.length > 0"
                  class="text-[9px] bg-slate-800 px-1 rounded-full"
                >
                  {{ staffMessages.length }}
                </span>
              </button>
            </div>
          </div>

          <!-- Staff Compose View -->
          <div
            v-if="staffSubTab === 'compose'"
            class="flex-1 p-4 overflow-y-auto space-y-3.5 text-xs"
          >
            <div
              v-if="staffSuccessNotice"
              class="p-3 bg-emerald-950/80 border border-emerald-500/40 rounded-xl text-emerald-300 font-semibold flex items-center gap-2"
            >
              <span>✓</span>
              <span>{{ staffSuccessNotice }}</span>
            </div>

            <div class="space-y-1">
              <label
                class="text-[11px] font-bold text-slate-300 uppercase tracking-wider block"
              >
                Inquiry Topic:
              </label>
              <select
                v-model="staffSubject"
                class="w-full p-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-indigo-400 cursor-pointer"
              >
                <option v-for="opt in subjectOptions" :key="opt" :value="opt">
                  {{ opt }}
                </option>
              </select>
            </div>

            <div
              v-if="staffSubject === 'Other Support Question'"
              class="space-y-1"
            >
              <label
                class="text-[11px] font-bold text-slate-300 uppercase tracking-wider block"
              >
                Custom Topic:
              </label>
              <input
                v-model="staffCustomSubject"
                type="text"
                placeholder="Briefly state your topic..."
                class="w-full p-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-indigo-400 placeholder-slate-500"
              />
            </div>

            <div class="space-y-1">
              <label
                class="text-[11px] font-bold text-slate-300 uppercase tracking-wider block"
              >
                Message for Staff:
              </label>
              <textarea
                v-model="staffMessageText"
                rows="5"
                placeholder="Ask about Japanese booster box authenticity, courier tracking follow-ups, case break reservations, or order details..."
                class="w-full p-3 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-indigo-400 placeholder-slate-500 leading-relaxed"
              ></textarea>
            </div>

            <div
              class="p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 text-[11px] text-slate-400 flex items-center justify-between"
            >
              <span
                >Sending as:
                <strong class="text-white">{{
                  authStore.currentUser?.name || "Verified Collector"
                }}</strong></span
              >
              <span class="text-indigo-400 font-mono">{{
                authStore.currentUser?.email || "Registered Guest"
              }}</span>
            </div>

            <div
              v-if="staffErrorNotice"
              class="text-xs text-rose-400 font-bold"
            >
              {{ staffErrorNotice }}
            </div>

            <button
              type="button"
              class="w-full py-3 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white font-black rounded-xl shadow-lg shadow-indigo-600/25 transition-all cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2"
              :disabled="isSendingStaff || !staffMessageText.trim()"
              @click="sendStaffMessage"
            >
              <span>{{
                isSendingStaff
                  ? "Sending to Staff Desk..."
                  : "Send Message to Staff"
              }}</span>
              <span>&rarr;</span>
            </button>
          </div>

          <!-- Staff Inquiries / History View -->
          <div v-else class="flex-1 p-4 overflow-y-auto space-y-3 text-xs">
            <!-- Unauthenticated Gate Notice -->
            <div
              v-if="!authStore.isAuthenticated"
              class="py-8 text-center space-y-3 px-4"
            >
              <div class="text-3xl">🔒</div>
              <p class="font-bold text-slate-200">Sign in to view your inbox</p>
              <p class="text-[11px] text-slate-400">
                Your support inquiries and staff responses are private and
                scoped to your verified collector account.
              </p>
              <button
                type="button"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs cursor-pointer shadow-md"
                @click="openAuthModal"
              >
                Sign In / Register
              </button>
            </div>

            <div
              v-else-if="isLoadingStaff"
              class="py-8 text-center text-slate-400"
            >
              Loading your support messages...
            </div>

            <div
              v-else-if="staffMessages.length === 0"
              class="py-8 text-center space-y-2"
            >
              <div class="text-3xl">📭</div>
              <p class="font-bold text-slate-300">No support inquiries yet</p>
              <p class="text-[11px] text-slate-500">
                Need help with an order or product? Send our staff a message.
              </p>
              <button
                type="button"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs cursor-pointer mt-2"
                @click="staffSubTab = 'compose'"
              >
                Write a Message
              </button>
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="msg in staffMessages"
                :key="msg.id"
                class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2.5"
              >
                <!-- Message Header -->
                <div class="flex items-center justify-between gap-2">
                  <span class="font-bold text-white text-xs truncate">
                    {{ msg.subject }}
                  </span>
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                    :class="
                      msg.status === 'resolve' || msg.staff_reply
                        ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                        : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'
                    "
                  >
                    {{
                      msg.status === "resolve" || msg.staff_reply
                        ? "✓ Resolved"
                        : "🟡 Ongoing"
                    }}
                  </span>
                </div>

                <!-- Customer Message Body -->
                <p
                  class="text-xs text-slate-300 leading-relaxed bg-slate-950/60 p-2.5 rounded-xl border border-slate-800"
                >
                  "{{ msg.message }}"
                </p>

                <!-- Staff Support Reply Box -->
                <div
                  v-if="msg.staff_reply"
                  class="p-3 rounded-xl bg-gradient-to-r from-emerald-950/70 to-slate-900 border border-emerald-500/40 text-xs space-y-1"
                >
                  <div
                    class="flex items-center justify-between text-[10px] font-black text-emerald-400 uppercase tracking-wider"
                  >
                    <span class="flex items-center gap-1">
                      <span>🧑‍💼</span>
                      <span
                        >Staff Reply ({{
                          msg.staff?.name || "Admin Chief"
                        }}):</span
                      >
                    </span>
                    <span class="text-slate-400 font-normal">
                      {{ formatTimeAgo(msg.resolved_at || msg.created_at) }}
                    </span>
                  </div>
                  <p class="text-slate-100 text-xs font-medium">
                    {{ msg.staff_reply }}
                  </p>
                </div>

                <div
                  v-else
                  class="text-[10px] text-amber-400/90 font-medium flex items-center gap-1"
                >
                  <span>⏳</span>
                  <span>Awaiting response from human staff support</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ================= MODE 2: AI CHATBOT (AIKO) ================= -->
        <div v-else class="flex-1 flex flex-col min-h-0 bg-slate-950">
          <!-- Subheader -->
          <div
            class="p-3 bg-gradient-to-r from-slate-900 to-indigo-950/60 border-b border-slate-800/80 flex items-center justify-between"
          >
            <div class="flex items-center gap-2.5">
              <div
                class="w-8 h-8 rounded-xl bg-gradient-to-tr from-rose-500 via-purple-600 to-amber-400 p-0.5"
              >
                <div
                  class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center text-sm"
                >
                  ✨
                </div>
              </div>
              <div>
                <h4 class="text-xs font-black text-white">Aiko AI Concierge</h4>
                <p class="text-[10px] text-emerald-400 flex items-center gap-1">
                  <span>●</span> Real-time Warehouse Stock Sync
                </p>
              </div>
            </div>

            <button
              type="button"
              class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 text-xs cursor-pointer"
              title="Clear chat"
              @click="clearChat"
            >
              🧹 Clear
            </button>
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
              <div
                v-if="msg.role === 'model'"
                class="flex items-center gap-1.5 mb-1 text-[10px] text-slate-400 font-semibold"
              >
                <span>✨ Aiko</span>
                <span>&bull;</span>
                <span>{{ msg.timestamp }}</span>
              </div>

              <!-- Message Text Bubble -->
              <div
                class="max-w-[85%] p-3.5 rounded-2xl leading-relaxed whitespace-pre-wrap font-normal"
                :class="
                  msg.role === 'user'
                    ? 'bg-gradient-to-tr from-rose-600 to-indigo-600 text-white rounded-br-xs shadow-md shadow-rose-950/40'
                    : 'bg-slate-900 border border-slate-800 text-slate-200 rounded-bl-xs shadow-md'
                "
              >
                {{ msg.text }}
              </div>

              <!-- Suggested Products Carousel/Cards -->
              <div
                v-if="msg.products && msg.products.length > 0"
                class="w-full mt-2.5 space-y-2"
              >
                <div
                  class="text-[10px] font-extrabold uppercase tracking-wider text-amber-300 flex items-center gap-1"
                >
                  <span>⚡</span>
                  <span>Recommended Collectibles in Stock:</span>
                </div>
                <div class="grid grid-cols-1 gap-2">
                  <div
                    v-for="prod in msg.products"
                    :key="prod.id"
                    class="p-2.5 rounded-xl bg-slate-900/90 border border-slate-800 flex items-center gap-3 hover:border-slate-700 transition-colors"
                  >
                    <img
                      :src="prod.image_url"
                      :alt="prod.name"
                      class="w-12 h-12 rounded-lg object-contain bg-slate-950 border border-slate-800 p-0.5 flex-shrink-0 cursor-pointer"
                      @click="handleViewProduct(prod)"
                    />
                    <div
                      class="flex-1 min-w-0 cursor-pointer"
                      @click="handleViewProduct(prod)"
                    >
                      <h4
                        class="font-bold text-xs text-white truncate hover:text-amber-300 transition-colors"
                      >
                        {{ prod.name }}
                      </h4>
                      <div class="flex items-center gap-2 mt-0.5">
                        <span
                          class="font-bold text-amber-400 font-mono text-xs"
                        >
                          ₱{{
                            Number(prod.price).toLocaleString("en-US", {
                              minimumFractionDigits: 2,
                            })
                          }}
                        </span>
                        <span class="text-[10px] text-emerald-400 font-medium">
                          ✓ {{ prod.stock }} in stock
                        </span>
                      </div>
                    </div>
                    <button
                      type="button"
                      class="p-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs transition-transform active:scale-95 cursor-pointer flex-shrink-0"
                      title="Add to cart"
                      @click="handleAddToCart(prod)"
                    >
                      🛒
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Loading Typing Indicator -->
            <div
              v-if="isLoading"
              class="p-3 rounded-2xl bg-slate-900/80 border border-slate-800 rounded-tl-xs flex items-center gap-1.5 w-fit"
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
                >Checking live warehouse stock...</span
              >
            </div>
          </div>

          <!-- AI Input Area Footer -->
          <div class="p-3 bg-slate-900/95 border-t border-slate-800/80">
            <form
              @submit.prevent="sendMessage()"
              class="flex items-center gap-2"
            >
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
        class="mb-3 w-64 p-3 bg-slate-950/95 backdrop-blur-md border border-rose-500/40 rounded-2xl shadow-2xl shadow-black/80 text-xs text-slate-200 select-none cursor-pointer hover:border-rose-400 transition-colors"
        @click="openAiChat"
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
      </div>
    </transition>

    <!-- DUAL FLOATING LAUNCHERS DOCK: Message Staff + Ask Aiko AI -->
    <div class="flex items-center gap-3">
      <!-- Launcher 1: Message Staff (Support) -->
      <button
        type="button"
        class="group relative flex items-center gap-2.5 p-1.5 pr-2 sm:pr-4 rounded-full bg-slate-950/95 backdrop-blur-xl border border-indigo-500/50 hover:border-indigo-400 text-white shadow-2xl shadow-indigo-950/60 transition-all duration-300 hover:scale-105 active:scale-95 cursor-pointer ring-1 ring-white/10"
        @click="openStaffSupport"
        :title="
          isOpen && activeMode === 'staff'
            ? 'Close support'
            : 'Message Support Team'
        "
      >
        <!-- Support Avatar Circle with Gradient -->
        <div
          class="w-11 h-11 rounded-full bg-gradient-to-tr from-indigo-500 via-blue-600 to-amber-400 p-0.5 shadow-lg shadow-indigo-600/30 group-hover:rotate-12 transition-transform flex-shrink-0"
        >
          <div
            class="w-full h-full bg-slate-950 rounded-full flex items-center justify-center text-lg"
          >
            {{ isOpen && activeMode === "staff" ? "✕" : "💬" }}
          </div>
        </div>

        <div class="text-left hidden sm:block">
          <div class="flex items-center gap-1.5">
            <span class="text-xs font-black text-white tracking-tight">
              Message Staff
            </span>
            <span
              class="text-[9px] font-extrabold px-1.5 py-0.2 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30"
            >
              Support
            </span>
          </div>
          <p class="text-[10px] text-slate-400 font-medium">
            Collector Help &amp; Inquiries
          </p>
        </div>

        <!-- Pulse indicator for staff reply -->
        <span
          v-if="hasStaffReply && (!isOpen || activeMode !== 'staff')"
          class="absolute -top-1 -right-1 flex h-3.5 w-3.5"
        >
          <span
            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"
          ></span>
          <span
            class="relative inline-flex rounded-full h-3.5 w-3.5 bg-indigo-500 border border-white/40"
          ></span>
        </span>
      </button>

      <!-- Launcher 2: Ask Aiko AI (Live) -->
      <button
        type="button"
        class="group relative flex items-center gap-2.5 p-1.5 pr-2 sm:pr-4 rounded-full bg-slate-950/95 backdrop-blur-xl border border-rose-500/50 hover:border-rose-400 text-white shadow-2xl shadow-rose-950/60 transition-all duration-300 hover:scale-105 active:scale-95 cursor-pointer ring-1 ring-white/10"
        @click="openAiChat"
        :title="
          isOpen && activeMode === 'ai'
            ? 'Close assistant'
            : 'Chat with Aiko AI Assistant'
        "
      >
        <!-- Sparkle Avatar Circle -->
        <div
          class="w-11 h-11 rounded-full bg-gradient-to-tr from-rose-600 via-purple-600 to-amber-400 p-0.5 shadow-lg shadow-rose-600/30 group-hover:rotate-12 transition-transform flex-shrink-0"
        >
          <div
            class="w-full h-full bg-slate-950 rounded-full flex items-center justify-center text-lg"
          >
            {{ isOpen && activeMode === "ai" ? "✕" : "✨" }}
          </div>
        </div>

        <div class="text-left hidden sm:block">
          <div class="flex items-center gap-1.5">
            <span class="text-xs font-black text-white tracking-tight">
              Ask Aiko AI
            </span>
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
          v-if="hasUnread && (!isOpen || activeMode !== 'ai')"
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
  </div>
</template>
