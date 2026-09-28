<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from "vue";
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

interface ChatRoomMessage {
  id: number;
  conversation_id: number;
  sender_id: number;
  content: string;
  is_read: boolean;
  created_at: string;
  sender?: {
    id: number;
    name: string;
    email?: string;
    user_type?: string;
  };
}

interface ChatRoom {
  id: number;
  customer_id: number;
  admin_id?: number | null;
  status: "active" | "closed" | "resolved";
  created_at: string;
  updated_at: string;
}

const activeChatRoom = ref<ChatRoom | null>(null);
const chatRoomMessages = ref<ChatRoomMessage[]>([]);
const isLoadingChatRoom = ref(false);
const isSendingChatMessage = ref(false);
const chatInputText = ref("");
const chatScrollContainer = ref<HTMLElement | null>(null);

const hasStaffReply = computed(() => {
  if (chatRoomMessages.value.length === 0) return false;
  const currentUserId = authStore.currentUser?.id
    ? parseInt(String(authStore.currentUser.id).replace(/\D/g, ""))
    : 0;
  const lastMsg = chatRoomMessages.value[chatRoomMessages.value.length - 1];
  return (
    !!lastMsg &&
    lastMsg.sender_id !== currentUserId &&
    lastMsg.sender?.user_type !== "customer"
  );
});

const scrollChatToBottom = () => {
  nextTick(() => {
    if (chatScrollContainer.value) {
      chatScrollContainer.value.scrollTop =
        chatScrollContainer.value.scrollHeight;
    }
  });
};

const formatMessageTime = (dateStr?: string) => {
  if (!dateStr) return "";
  try {
    return new Date(dateStr).toLocaleTimeString("en-US", {
      hour: "numeric",
      minute: "2-digit",
    });
  } catch {
    return "";
  }
};

const openAuthModal = () => {
  if (typeof window !== "undefined") {
    window.dispatchEvent(new CustomEvent("open-auth-modal"));
  }
};

const fetchChatRoomAndMessages = async () => {
  if (!authStore.token) {
    activeChatRoom.value = null;
    chatRoomMessages.value = [];
    return;
  }

  isLoadingChatRoom.value = true;
  try {
    const headers: Record<string, string> = {
      Authorization: `Bearer ${authStore.token}`,
    };

    // 1. Fetch conversations or start/get one
    const convRes = await fetch("/api/conversations", { headers });
    let convId: number | null = null;

    if (convRes.ok) {
      const convJson = await convRes.json();
      if (
        convJson.success &&
        Array.isArray(convJson.data) &&
        convJson.data.length > 0
      ) {
        activeChatRoom.value = convJson.data[0];
        convId = convJson.data[0].id;
      }
    }

    if (!convId) {
      const startRes = await fetch("/api/conversations", {
        method: "POST",
        headers: {
          ...headers,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({}),
      });
      if (startRes.ok) {
        const startJson = await startRes.json();
        if (startJson.success && startJson.data) {
          activeChatRoom.value = startJson.data;
          convId = startJson.data.id;
        }
      }
    }

    // 2. Fetch all messages in this conversation room
    if (convId) {
      const msgRes = await fetch(`/api/conversations/${convId}/messages`, {
        headers,
      });
      if (msgRes.ok) {
        const msgJson = await msgRes.json();
        if (msgJson.success && Array.isArray(msgJson.data)) {
          chatRoomMessages.value = msgJson.data;
          scrollChatToBottom();
        }
      }
    }
  } catch (e) {
    console.warn("Could not load chat messages", e);
  } finally {
    isLoadingChatRoom.value = false;
  }
};

const sendChatMessage = async (textToSend?: string) => {
  const content = (textToSend || chatInputText.value).trim();
  if (!content) return;

  if (!authStore.token) {
    openAuthModal();
    return;
  }

  isSendingChatMessage.value = true;
  chatInputText.value = "";

  const currentUserId = authStore.currentUser?.id
    ? parseInt(String(authStore.currentUser.id).replace(/\D/g, ""))
    : 1;

  // Optimistic message bubble
  const tempMsg: ChatRoomMessage = {
    id: Date.now(),
    conversation_id: activeChatRoom.value?.id || 0,
    sender_id: currentUserId,
    content,
    is_read: false,
    created_at: new Date().toISOString(),
    sender: {
      id: currentUserId,
      name: authStore.currentUser?.name || "You",
      user_type: "customer",
    },
  };
  chatRoomMessages.value.push(tempMsg);
  scrollChatToBottom();

  try {
    const headers: Record<string, string> = {
      Authorization: `Bearer ${authStore.token}`,
      "Content-Type": "application/json",
    };

    let convId = activeChatRoom.value?.id;
    if (!convId) {
      const startRes = await fetch("/api/conversations", {
        method: "POST",
        headers,
        body: JSON.stringify({}),
      });
      if (startRes.ok) {
        const startJson = await startRes.json();
        activeChatRoom.value = startJson.data;
        convId = startJson.data.id;
      }
    }

    if (convId) {
      const res = await fetch(`/api/conversations/${convId}/messages`, {
        method: "POST",
        headers,
        body: JSON.stringify({ content }),
      });
      if (res.ok) {
        const json = await res.json();
        if (json.success && json.data) {
          const idx = chatRoomMessages.value.findIndex(
            (m) => m.id === tempMsg.id,
          );
          if (idx !== -1) {
            chatRoomMessages.value[idx] = json.data;
          }
          if (activeChatRoom.value) {
            activeChatRoom.value.status = "active";
          }
        }
      }
    }
  } catch (e) {
    console.error("Failed to send chat message", e);
  } finally {
    isSendingChatMessage.value = false;
    scrollChatToBottom();
  }
};

let chatPollTimer: any = null;

onMounted(() => {
  if (authStore.token) {
    fetchChatRoomAndMessages();
  }

  // Periodic poll for staff replies
  chatPollTimer = setInterval(() => {
    if (
      isOpen.value &&
      activeMode.value === "staff" &&
      authStore.token &&
      activeChatRoom.value?.id
    ) {
      fetch(`/api/conversations/${activeChatRoom.value.id}/messages`, {
        headers: { Authorization: `Bearer ${authStore.token}` },
      })
        .then((r) => r.json())
        .then((json) => {
          if (
            json.success &&
            Array.isArray(json.data) &&
            json.data.length > chatRoomMessages.value.length
          ) {
            chatRoomMessages.value = json.data;
            scrollChatToBottom();
          }
        })
        .catch(() => {});
    }
  }, 4000);
});

onUnmounted(() => {
  if (chatPollTimer) {
    clearInterval(chatPollTimer);
  }
});

watch(
  () => authStore.token,
  (newToken) => {
    if (newToken) {
      fetchChatRoomAndMessages();
    } else {
      activeChatRoom.value = null;
      chatRoomMessages.value = [];
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
    fetchChatRoomAndMessages();
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
  if (item.stock <= 0) return;
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
              @click="
                activeMode = 'staff';
                fetchChatRoomAndMessages();
              "
            >
              <span>💬</span>
              <span>Live Staff Chat</span>
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

        <!-- ================= MODE 1: LIVE STAFF CONVERSATION CHAT BOX ================= -->
        <div
          v-if="activeMode === 'staff'"
          class="flex-1 flex flex-col min-h-0 bg-slate-950"
        >
          <!-- Staff Chat Room Header -->
          <div
            class="px-4 py-3 bg-gradient-to-r from-indigo-950/70 via-slate-900 to-slate-950 border-b border-slate-800/80 flex items-center justify-between"
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
                <div class="flex items-center gap-2">
                  <h4 class="text-xs font-black text-white">
                    RLG Collector Support Desk
                  </h4>
                  <span
                    v-if="activeChatRoom"
                    class="px-1.5 py-0.2 rounded-full text-[9px] font-extrabold uppercase tracking-wider"
                    :class="
                      activeChatRoom.status === 'resolved'
                        ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                        : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30'
                    "
                  >
                    {{ activeChatRoom.status }}
                  </span>
                </div>
                <p
                  class="text-[10px] text-emerald-400 flex items-center gap-1 font-semibold"
                >
                  <span
                    class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"
                  ></span>
                  Shop Staff Online &bull; Live Real-time Chat
                </p>
              </div>
            </div>

            <!-- Header Action / Refresh -->
            <button
              type="button"
              class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors cursor-pointer text-xs"
              title="Refresh messages"
              :disabled="isLoadingChatRoom"
              @click="fetchChatRoomAndMessages"
            >
              <span :class="{ 'inline-block animate-spin': isLoadingChatRoom }"
                >🔄</span
              >
            </button>
          </div>

          <!-- Unauthenticated Gate Notice -->
          <div
            v-if="!authStore.isAuthenticated"
            class="flex-1 p-6 flex flex-col items-center justify-center text-center space-y-3"
          >
            <div
              class="w-14 h-14 rounded-2xl bg-indigo-950/60 border border-indigo-500/30 flex items-center justify-center text-2xl"
            >
              🔒
            </div>
            <h4 class="font-bold text-slate-200 text-sm">
              Sign in for Live Chat
            </h4>
            <p class="text-[11px] text-slate-400 max-w-xs leading-relaxed">
              Sign in to start a private, interactive chat thread directly with
              our shop customer service specialists.
            </p>
            <button
              type="button"
              class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white font-bold rounded-xl text-xs cursor-pointer shadow-lg shadow-indigo-600/30 transition-all mt-2"
              @click="openAuthModal"
            >
              Sign In / Register
            </button>
          </div>

          <!-- Interactive Message Stream & Composer (When Authenticated) -->
          <div v-else class="flex-1 flex flex-col min-h-0">
            <!-- Messages Scroll Area -->
            <div
              ref="chatScrollContainer"
              class="flex-1 p-4 overflow-y-auto space-y-3 text-xs scroll-smooth"
            >
              <!-- Initial Loading State -->
              <div
                v-if="isLoadingChatRoom && chatRoomMessages.length === 0"
                class="py-12 text-center text-slate-400 space-y-2"
              >
                <div class="inline-block animate-spin text-xl">⏳</div>
                <p class="text-xs">Connecting to collector support room...</p>
              </div>

              <!-- Empty State / Quick Suggestion Prompts -->
              <div
                v-else-if="chatRoomMessages.length === 0"
                class="py-8 text-center space-y-3"
              >
                <div class="text-3xl">💬</div>
                <div class="space-y-1">
                  <p class="font-bold text-slate-200">
                    Start a conversation with our staff
                  </p>
                  <p class="text-[11px] text-slate-400 max-w-xs mx-auto">
                    Ask about tracking updates, card condition scans,
                    pre-orders, or order changes.
                  </p>
                </div>
                <div
                  class="flex flex-col gap-1.5 pt-2 max-w-xs mx-auto text-left"
                >
                  <button
                    type="button"
                    class="p-2 rounded-xl bg-slate-900 border border-slate-800 hover:border-indigo-500/50 hover:bg-slate-800/80 text-[11px] text-slate-300 transition-all cursor-pointer flex items-center justify-between"
                    @click="
                      sendChatMessage(
                        'Can I check the delivery status of my latest order?',
                      )
                    "
                  >
                    <span>🚚 Check status of my order</span>
                    <span class="text-indigo-400">&rarr;</span>
                  </button>
                  <button
                    type="button"
                    class="p-2 rounded-xl bg-slate-900 border border-slate-800 hover:border-indigo-500/50 hover:bg-slate-800/80 text-[11px] text-slate-300 transition-all cursor-pointer flex items-center justify-between"
                    @click="
                      sendChatMessage(
                        'Do you have stock for Japanese Pokémon booster boxes?',
                      )
                    "
                  >
                    <span>📦 Japanese Pokémon booster stock</span>
                    <span class="text-indigo-400">&rarr;</span>
                  </button>
                  <button
                    type="button"
                    class="p-2 rounded-xl bg-slate-900 border border-slate-800 hover:border-indigo-500/50 hover:bg-slate-800/80 text-[11px] text-slate-300 transition-all cursor-pointer flex items-center justify-between"
                    @click="
                      sendChatMessage(
                        'Can you verify card authenticity or grading condition?',
                      )
                    "
                  >
                    <span>✨ Card condition & authenticity check</span>
                    <span class="text-indigo-400">&rarr;</span>
                  </button>
                </div>
              </div>

              <!-- Message Bubbles Stream -->
              <template v-else>
                <div
                  v-for="msg in chatRoomMessages"
                  :key="msg.id"
                  class="flex flex-col"
                  :class="
                    msg.sender_id ===
                      (authStore.currentUser?.id
                        ? parseInt(
                            String(authStore.currentUser.id).replace(/\D/g, ''),
                          )
                        : 0) || msg.sender?.user_type === 'customer'
                      ? 'items-end'
                      : 'items-start'
                  "
                >
                  <!-- Bubble Header Label for Staff -->
                  <div
                    v-if="
                      msg.sender_id !==
                        (authStore.currentUser?.id
                          ? parseInt(
                              String(authStore.currentUser.id).replace(
                                /\D/g,
                                '',
                              ),
                            )
                          : 0) && msg.sender?.user_type !== 'customer'
                    "
                    class="flex items-center gap-1 text-[10px] text-indigo-300 font-bold mb-1 pl-1"
                  >
                    <span>🧑‍💼</span>
                    <span>{{ msg.sender?.name || "Shop Staff" }}</span>
                  </div>

                  <!-- Speech Bubble -->
                  <div
                    class="max-w-[85%] p-3 rounded-2xl shadow-sm text-xs leading-relaxed"
                    :class="
                      msg.sender_id ===
                        (authStore.currentUser?.id
                          ? parseInt(
                              String(authStore.currentUser.id).replace(
                                /\D/g,
                                '',
                              ),
                            )
                          : 0) || msg.sender?.user_type === 'customer'
                        ? 'bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-tr-xs'
                        : 'bg-slate-900 border border-slate-800 text-slate-100 rounded-tl-xs'
                    "
                  >
                    <p class="whitespace-pre-wrap break-words">
                      {{ msg.content }}
                    </p>
                  </div>

                  <!-- Bubble Timestamp & Read Receipt -->
                  <div
                    class="flex items-center gap-1 text-[9px] text-slate-500 mt-1 px-1"
                  >
                    <span>{{ formatMessageTime(msg.created_at) }}</span>
                    <span
                      v-if="
                        msg.sender_id ===
                          (authStore.currentUser?.id
                            ? parseInt(
                                String(authStore.currentUser.id).replace(
                                  /\D/g,
                                  '',
                                ),
                              )
                            : 0) || msg.sender?.user_type === 'customer'
                      "
                      :class="
                        msg.is_read
                          ? 'text-emerald-400 font-bold'
                          : 'text-slate-500'
                      "
                      :title="msg.is_read ? 'Read by staff' : 'Delivered'"
                    >
                      {{ msg.is_read ? "✓✓ Read" : "✓ Sent" }}
                    </span>
                  </div>
                </div>
              </template>
            </div>

            <!-- Fixed Bottom Message Composer -->
            <div class="p-3 bg-slate-900/90 border-t border-slate-800/80">
              <form
                class="flex items-center gap-2"
                @submit.prevent="sendChatMessage()"
              >
                <input
                  v-model="chatInputText"
                  type="text"
                  placeholder="Type a message to shop staff..."
                  class="flex-1 bg-slate-950 border border-slate-700 focus:border-indigo-400 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none transition-colors"
                  :disabled="isSendingChatMessage"
                  @keydown.enter.exact.prevent="sendChatMessage()"
                />
                <button
                  type="submit"
                  class="p-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white rounded-xl font-bold text-xs transition-all shadow-md shadow-indigo-600/30 cursor-pointer disabled:opacity-50 flex items-center justify-center flex-shrink-0"
                  :disabled="isSendingChatMessage || !chatInputText.trim()"
                >
                  <span v-if="isSendingChatMessage" class="animate-spin text-sm"
                    >⏳</span
                  >
                  <span v-else class="text-sm">➤</span>
                </button>
              </form>
              <div
                class="flex items-center justify-between text-[10px] text-slate-500 mt-2 px-1"
              >
                <span>💬 Back-and-forth direct dialogue</span>
                <span>Press Enter to send</span>
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
                        <span
                          v-if="prod.stock > 0"
                          class="text-[10px] text-emerald-400 font-medium"
                        >
                          ✓ {{ prod.stock }} in stock
                        </span>
                        <span
                          v-else
                          class="text-[10px] text-rose-400 font-bold"
                        >
                          🚫 Sold out
                        </span>
                      </div>
                    </div>
                    <button
                      type="button"
                      class="p-2 rounded-xl font-black text-xs transition-transform active:scale-95 flex-shrink-0"
                      :class="
                        prod.stock > 0
                          ? 'bg-amber-400 hover:bg-amber-300 text-slate-950 cursor-pointer'
                          : 'bg-slate-800 text-slate-500 border border-slate-700/60 cursor-not-allowed opacity-50'
                      "
                      :disabled="prod.stock <= 0"
                      :title="prod.stock > 0 ? 'Add to cart' : 'Sold out'"
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
