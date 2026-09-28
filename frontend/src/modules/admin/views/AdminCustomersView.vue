<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from "vue";
import { useAdminStore } from "../admin.store";
import type {
  CustomerProfile,
  CustomerMessageItem,
  CustomerReviewItem,
} from "../admin.types";
import { formatCurrency } from "@/shared/utils/currency.util";

const adminStore = useAdminStore();

// Top Tab Switcher: Separated into Profiles, Messages, Reviews
const activeTab = ref<"profiles" | "messages" | "reviews">("profiles");

// TAB 1: Profiles State
const searchQuery = ref("");
const selectedSegment = ref<
  "All" | "VIP" | "Regular" | "Wholesale" | "Inactive"
>("All");
const selectedCustomer = ref<CustomerProfile | null>(null);
const isProfileModalOpen = ref(false);

// TAB 2: Live Chat Desk State (conversations & messages API)
const chatSearchQuery = ref("");
const chatStatusFilter = ref<"all" | "active" | "resolved" | "closed">("all");
const adminReplyInput = ref("");
const adminChatScrollContainer = ref<HTMLElement | null>(null);
const isSendingAdminReply = ref(false);
let adminChatPollTimer: any = null;

// Legacy Messages State (customer_message table)
const messageSearchQuery = ref("");
const messageStatusFilter = ref<"all" | "ongoing" | "resolve">("all");
const selectedMessage = ref<CustomerMessageItem | null>(null);
const messageReplyText = ref("");
const isMessageReplyModalOpen = ref(false);
const isSendingMessageReply = ref(false);

// TAB 3: Reviews State (customer_review table)
const reviewSearchQuery = ref("");
const reviewStarFilter = ref<
  "all" | "5" | "4" | "3" | "needs_reply" | "replied"
>("all");
const selectedReview = ref<CustomerReviewItem | null>(null);
const reviewReplyText = ref("");
const isReviewReplyModalOpen = ref(false);
const isSendingReviewReply = ref(false);
const isDeletingReview = ref<number | null>(null);

const fetchDbCustomers = async () => {
  try {
    const res = await fetch("/api/customer-profiles");
    if (res.ok) {
      const json = await res.json();
      if (json.success && Array.isArray(json.data) && json.data.length > 0) {
        const dbCustomers: CustomerProfile[] = json.data.map((p: any) => ({
          id: `CUST-${p.id}`,
          name: p.name || p.user?.name || "Collector",
          email: p.user?.email || "customer@rlghobby.com",
          phone: p.phone || "N/A",
          city: p.city || "Metro Manila",
          totalOrders: p.orders_count || 1,
          lifetimeValue: p.lifetime_value || 4500,
          segment: (p.segment_rank || p.segment || "Regular") as any,
          lastOrderDate: p.updated_at
            ? p.updated_at.slice(0, 10)
            : "2026-09-25",
          notes: p.bio || p.notes || undefined,
        }));
        const dbIds = new Set(dbCustomers.map((c) => c.id));
        const remaining = adminStore.customers.filter((c) => !dbIds.has(c.id));
        adminStore.customers = [...dbCustomers, ...remaining];
      }
    }
  } catch (e) {
    console.error("Failed to load customers from database", e);
  }
};

onMounted(async () => {
  await Promise.all([
    fetchDbCustomers(),
    adminStore.fetchConversations(),
    adminStore.fetchCustomerMessages(),
    adminStore.fetchCustomerReviews(),
  ]);

  if (adminStore.conversations.length > 0 && !adminStore.activeConversationId) {
    selectConversation(adminStore.conversations[0].id);
  }

  adminChatPollTimer = setInterval(() => {
    if (activeTab.value === "messages" && adminStore.activeConversationId) {
      adminStore.fetchConversationMessages(adminStore.activeConversationId);
    }
  }, 4000);
});

onUnmounted(() => {
  if (adminChatPollTimer) {
    clearInterval(adminChatPollTimer);
  }
});

watch(activeTab, (tab) => {
  if (tab === "messages") {
    if (adminStore.conversations.length === 0) {
      adminStore.fetchConversations().then(() => {
        if (
          adminStore.conversations.length > 0 &&
          !adminStore.activeConversationId
        ) {
          selectConversation(adminStore.conversations[0].id);
        }
      });
    } else if (
      !adminStore.activeConversationId &&
      adminStore.conversations.length > 0
    ) {
      selectConversation(adminStore.conversations[0].id);
    }
  }
});

// Profile Helpers
const filteredCustomers = computed(() => {
  return adminStore.customers.filter((c) => {
    const matchesSegment =
      selectedSegment.value === "All" || c.segment === selectedSegment.value;
    const matchesSearch =
      c.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      c.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      c.city.toLowerCase().includes(searchQuery.value.toLowerCase());
    return matchesSegment && matchesSearch;
  });
});

const openProfile = (c: CustomerProfile) => {
  selectedCustomer.value = c;
  isProfileModalOpen.value = true;
};

const getSegmentBadge = (segment: string) => {
  switch (segment) {
    case "VIP":
      return "bg-amber-100 text-amber-800 border-amber-200";
    case "Wholesale":
      return "bg-purple-100 text-purple-800 border-purple-200";
    case "Regular":
      return "bg-blue-100 text-blue-800 border-blue-200";
    case "Inactive":
      return "bg-slate-100 text-slate-600 border-slate-200";
    default:
      return "bg-slate-100 text-slate-700";
  }
};

// Live Chat Desk Helpers & Computed
const totalUnreadChatCount = computed(() => {
  return adminStore.conversations.reduce(
    (acc, c) => acc + (c.unread_count || 0),
    0,
  );
});

const filteredConversations = computed(() => {
  return adminStore.conversations.filter((c) => {
    const matchesStatus =
      chatStatusFilter.value === "all" || c.status === chatStatusFilter.value;
    const q = chatSearchQuery.value.toLowerCase().trim();
    const customerName = (c.customer?.name || "Customer").toLowerCase();
    const customerEmail = (c.customer?.email || "").toLowerCase();
    const latestText = (c.latest_message?.content || "").toLowerCase();
    const matchesSearch =
      !q ||
      customerName.includes(q) ||
      customerEmail.includes(q) ||
      latestText.includes(q);
    return matchesStatus && matchesSearch;
  });
});

const scrollAdminChatToBottom = () => {
  nextTick(() => {
    if (adminChatScrollContainer.value) {
      adminChatScrollContainer.value.scrollTop =
        adminChatScrollContainer.value.scrollHeight;
    }
  });
};

const selectConversation = async (convId: number) => {
  await adminStore.fetchConversationMessages(convId);
  scrollAdminChatToBottom();
};

const handleSendAdminReply = async (customText?: string) => {
  const content = (customText || adminReplyInput.value).trim();
  if (!content || !adminStore.activeConversationId || isSendingAdminReply.value)
    return;

  isSendingAdminReply.value = true;
  adminReplyInput.value = "";
  try {
    await adminStore.sendAdminChatMessage(
      adminStore.activeConversationId,
      content,
    );
    scrollAdminChatToBottom();
  } finally {
    isSendingAdminReply.value = false;
  }
};

const handleUpdateStatus = async (status: "active" | "closed" | "resolved") => {
  if (!adminStore.activeConversationId) return;
  await adminStore.updateChatConversationStatus(
    adminStore.activeConversationId,
    status,
  );
};

const formatChatTime = (dateStr?: string) => {
  if (!dateStr) return "";
  try {
    const d = new Date(dateStr);
    const now = new Date();
    const isToday = d.toDateString() === now.toDateString();
    if (isToday) {
      return d.toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
      });
    }
    return d.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      hour: "numeric",
      minute: "2-digit",
    });
  } catch {
    return dateStr;
  }
};

// Customer Messages Helpers & Filter (customer_message API)
const ongoingMessagesCount = computed(() => {
  return adminStore.customerMessages.filter((m) => m.status === "ongoing")
    .length;
});

const filteredMessages = computed(() => {
  return adminStore.customerMessages.filter((m) => {
    const matchesStatus =
      messageStatusFilter.value === "all" ||
      m.status === messageStatusFilter.value;
    const q = messageSearchQuery.value.toLowerCase().trim();
    const matchesSearch =
      !q ||
      m.subject.toLowerCase().includes(q) ||
      m.message.toLowerCase().includes(q) ||
      m.customerName.toLowerCase().includes(q) ||
      m.email.toLowerCase().includes(q);
    return matchesStatus && matchesSearch;
  });
});

const openMessageReply = (msg: CustomerMessageItem) => {
  selectedMessage.value = msg;
  messageReplyText.value =
    msg.staffReply ||
    `Hi ${msg.customerName},\n\nThank you for reaching out to RLG Hobby Shop support regarding "${msg.subject}". `;
  isMessageReplyModalOpen.value = true;
};

const sendMessageReply = async () => {
  if (selectedMessage.value && messageReplyText.value.trim()) {
    isSendingMessageReply.value = true;
    try {
      await adminStore.replyToCustomerMessage(
        selectedMessage.value.id,
        messageReplyText.value.trim(),
      );
      isMessageReplyModalOpen.value = false;
    } finally {
      isSendingMessageReply.value = false;
    }
  }
};

const toggleMessageStatus = async (msg: CustomerMessageItem) => {
  const newStatus = msg.status === "resolve" ? "ongoing" : "resolve";
  await adminStore.toggleCustomerMessageStatus(msg.id, newStatus);
};

// Customer Reviews Helpers & Filter (customer_review API)
const averageReviewRating = computed(() => {
  if (adminStore.customerReviews.length === 0) return "5.0";
  const sum = adminStore.customerReviews.reduce((acc, r) => acc + r.stars, 0);
  return (sum / adminStore.customerReviews.length).toFixed(1);
});

const needsReplyReviewsCount = computed(() => {
  return adminStore.customerReviews.filter((r) => !r.staffReply).length;
});

const filteredReviews = computed(() => {
  return adminStore.customerReviews.filter((r) => {
    let matchesFilter = true;
    if (reviewStarFilter.value === "5") matchesFilter = r.stars === 5;
    else if (reviewStarFilter.value === "4") matchesFilter = r.stars === 4;
    else if (reviewStarFilter.value === "3") matchesFilter = r.stars === 3;
    else if (reviewStarFilter.value === "needs_reply")
      matchesFilter = !r.staffReply;
    else if (reviewStarFilter.value === "replied")
      matchesFilter = !!r.staffReply;

    const q = reviewSearchQuery.value.toLowerCase().trim();
    const matchesSearch =
      !q ||
      r.productName.toLowerCase().includes(q) ||
      r.message.toLowerCase().includes(q) ||
      r.customerName.toLowerCase().includes(q) ||
      r.email.toLowerCase().includes(q);
    return matchesFilter && matchesSearch;
  });
});

const openReviewReply = (r: CustomerReviewItem) => {
  selectedReview.value = r;
  reviewReplyText.value =
    r.staffReply ||
    `Thank you for your review, ${r.customerName}! We take extra care in packaging all authentic collectible hobby items. Enjoy your collection!`;
  isReviewReplyModalOpen.value = true;
};

const sendReviewReply = async () => {
  if (selectedReview.value && reviewReplyText.value.trim()) {
    isSendingReviewReply.value = true;
    try {
      await adminStore.replyToCustomerReview(
        selectedReview.value.id,
        reviewReplyText.value.trim(),
      );
      isReviewReplyModalOpen.value = false;
    } finally {
      isSendingReviewReply.value = false;
    }
  }
};

const deleteReview = async (id: number) => {
  if (
    confirm("Are you sure you want to permanently delete this customer review?")
  ) {
    isDeletingReview.value = id;
    try {
      await adminStore.deleteCustomerReview(id);
    } finally {
      isDeletingReview.value = null;
    }
  }
};
</script>

<template>
  <div class="space-y-6">
    <!-- Header with 3 Toggles -->
    <div
      class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs"
    >
      <div>
        <h1
          class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight"
        >
          Customer Relationship Management (CRM)
        </h1>
        <p class="text-xs text-slate-500">
          Collector profiles, purchase history LTV, behavioral segmentation, and
          centralized inquiry inbox.
        </p>
      </div>

      <!-- Tab Switcher (Separated into Profiles, Customer Messages, and Customer Reviews) -->
      <div
        class="flex flex-wrap sm:flex-nowrap gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200 self-start lg:self-auto"
      >
        <!-- Toggle 1: Customer Profiles -->
        <button
          type="button"
          class="px-3.5 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
          :class="
            activeTab === 'profiles'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'profiles'"
        >
          <span>👥 Customer Profiles</span>
          <span class="text-[11px] text-slate-400 font-semibold"
            >({{ adminStore.customers.length }})</span
          >
        </button>

        <!-- Toggle 2: Customer Live Chat Desk (conversations & messages table) -->
        <button
          type="button"
          class="px-3.5 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
          :class="
            activeTab === 'messages'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'messages'"
        >
          <span>💬 Live Chat Desk</span>
          <span
            v-if="totalUnreadChatCount > 0"
            class="px-1.5 py-0.2 rounded-full bg-rose-600 text-white text-[10px] font-extrabold shadow-2xs"
            title="Unread buyer messages"
          >
            {{ totalUnreadChatCount }}
          </span>
          <span v-else class="text-[11px] text-slate-400 font-semibold">
            ({{ adminStore.conversations.length }})
          </span>
        </button>

        <!-- Toggle 3: Customer Reviews (customer_review table) -->
        <button
          type="button"
          class="px-3.5 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
          :class="
            activeTab === 'reviews'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'reviews'"
        >
          <span>⭐ Customer Reviews</span>
          <span
            v-if="needsReplyReviewsCount > 0"
            class="px-1.5 py-0.2 rounded-full bg-amber-500 text-white text-[10px] font-extrabold shadow-2xs"
            title="Reviews awaiting staff reply"
          >
            {{ needsReplyReviewsCount }}
          </span>
          <span v-else class="text-[11px] text-slate-400 font-semibold">
            ({{ adminStore.customerReviews.length }})
          </span>
        </button>
      </div>
    </div>

    <!-- TAB 1: Customer Profiles & Segmentation -->
    <div v-if="activeTab === 'profiles'" class="space-y-4">
      <div
        class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4"
      >
        <div class="relative w-full md:w-80">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search collectors by name, email, or city..."
            class="w-full text-xs px-3.5 py-2.5 pl-9 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-rose-500"
          />
          <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
        </div>

        <div
          class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 overflow-x-auto w-full md:w-auto"
        >
          <button
            v-for="seg in [
              'All',
              'VIP',
              'Regular',
              'Wholesale',
              'Inactive',
            ] as const"
            :key="seg"
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all cursor-pointer"
            :class="
              selectedSegment === seg
                ? 'bg-white text-slate-900 shadow-xs'
                : 'text-slate-500 hover:text-slate-800'
            "
            @click="selectedSegment = seg"
          >
            {{ seg }}
          </button>
        </div>
      </div>

      <!-- Customers Table -->
      <div
        class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden"
      >
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr
                class="bg-slate-50 border-b border-slate-200/80 text-slate-500 uppercase font-bold text-[10px] tracking-wider"
              >
                <th class="p-4">Customer Name</th>
                <th class="p-4">Contact &amp; Location</th>
                <th class="p-4">Segment Rank</th>
                <th class="p-4">Lifetime Value (LTV)</th>
                <th class="p-4">Total Orders</th>
                <th class="p-4">Last Active</th>
                <th class="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="c in filteredCustomers"
                :key="c.id"
                class="hover:bg-slate-50/70 transition-colors"
              >
                <td class="p-4">
                  <div class="flex items-center gap-3">
                    <div
                      class="w-8 h-8 rounded-full bg-slate-900 text-white font-black text-xs flex items-center justify-center flex-shrink-0"
                    >
                      {{ c.name.charAt(0) }}
                    </div>
                    <div>
                      <span class="font-bold text-slate-900 block">{{
                        c.name
                      }}</span>
                      <span class="text-[10px] text-slate-400 font-mono">{{
                        c.id
                      }}</span>
                    </div>
                  </div>
                </td>

                <td class="p-4">
                  <span class="text-slate-900 block">{{ c.email }}</span>
                  <span class="text-[11px] text-slate-400"
                    >{{ c.city }} &bull; {{ c.phone }}</span
                  >
                </td>

                <td class="p-4">
                  <span
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold border inline-block"
                    :class="getSegmentBadge(c.segment)"
                  >
                    {{ c.segment }}
                  </span>
                </td>

                <td class="p-4">
                  <span class="font-extrabold text-slate-900">{{
                    formatCurrency(c.lifetimeValue)
                  }}</span>
                </td>

                <td class="p-4">
                  <span class="font-bold text-slate-700"
                    >{{ c.totalOrders }} orders</span
                  >
                </td>

                <td class="p-4">
                  <span class="text-slate-500">{{ c.lastOrderDate }}</span>
                </td>

                <td class="p-4 text-right">
                  <button
                    type="button"
                    class="text-xs font-bold text-rose-600 hover:text-rose-700 cursor-pointer"
                    @click="openProfile(c)"
                  >
                    View LTV &rarr;
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 2: Customer Live Chat Desk (Conversations & Real-Time Messages) -->
    <div v-else-if="activeTab === 'messages'" class="space-y-4">
      <!-- Chat Desk Container: Two Column Split View -->
      <div
        class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[640px] h-[720px]"
      >
        <!-- LEFT COLUMN: Conversation Threads List (4 cols) -->
        <div
          class="lg:col-span-4 border-r border-slate-200 flex flex-col h-full bg-slate-50/50"
        >
          <!-- Thread List Header & Search -->
          <div class="p-3.5 border-b border-slate-200 space-y-2.5 bg-white">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span
                  class="text-xs font-black text-slate-900 uppercase tracking-wider"
                >
                  Conversations
                </span>
                <span
                  v-if="totalUnreadChatCount > 0"
                  class="px-2 py-0.5 rounded-full bg-rose-600 text-white text-[10px] font-black"
                >
                  {{ totalUnreadChatCount }} unread
                </span>
              </div>
              <button
                type="button"
                class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 text-xs transition-colors cursor-pointer"
                title="Refresh thread list"
                :disabled="adminStore.isLoadingConversations"
                @click="adminStore.fetchConversations()"
              >
                <span
                  :class="{
                    'inline-block animate-spin':
                      adminStore.isLoadingConversations,
                  }"
                  >🔄</span
                >
              </button>
            </div>

            <!-- Search input -->
            <div class="relative">
              <input
                v-model="chatSearchQuery"
                type="text"
                placeholder="Search by buyer name or message..."
                class="w-full text-xs px-3 py-2 pl-8 rounded-xl bg-slate-100 border border-slate-200 focus:outline-none focus:border-indigo-500 focus:bg-white transition-colors"
              />
              <span class="absolute left-2.5 top-2 text-slate-400 text-xs"
                >🔍</span
              >
            </div>

            <!-- Status filter tabs -->
            <div
              class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-xl border border-slate-200 text-[11px]"
            >
              <button
                v-for="st in ['all', 'active', 'resolved', 'closed'] as const"
                :key="st"
                type="button"
                class="flex-1 py-1 rounded-lg font-bold capitalize transition-all cursor-pointer text-center"
                :class="
                  chatStatusFilter === st
                    ? 'bg-white text-slate-900 shadow-xs'
                    : 'text-slate-500 hover:text-slate-800'
                "
                @click="chatStatusFilter = st"
              >
                {{ st }}
              </button>
            </div>
          </div>

          <!-- Thread Items List -->
          <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
            <div
              v-if="
                adminStore.isLoadingConversations &&
                adminStore.conversations.length === 0
              "
              class="p-8 text-center text-xs text-slate-400 font-medium"
            >
              <div class="inline-block animate-spin text-lg mb-1">⏳</div>
              <p>Loading conversations...</p>
            </div>

            <div
              v-else-if="filteredConversations.length === 0"
              class="p-8 text-center text-xs text-slate-400 space-y-1"
            >
              <div class="text-2xl">📭</div>
              <p class="font-bold text-slate-600">No conversations found</p>
              <p class="text-[11px] text-slate-400">
                Buyer messages from the storefront live chat will appear here.
              </p>
            </div>

            <button
              v-for="conv in filteredConversations"
              :key="conv.id"
              type="button"
              class="w-full p-3.5 text-left transition-all cursor-pointer flex items-start gap-3 relative"
              :class="
                adminStore.activeConversationId === conv.id
                  ? 'bg-indigo-50/80 border-l-4 border-indigo-600 shadow-2xs'
                  : 'hover:bg-slate-100/70 border-l-4 border-transparent'
              "
              @click="selectConversation(conv.id)"
            >
              <!-- Avatar -->
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center font-black text-xs flex-shrink-0 text-white shadow-2xs"
                :class="
                  adminStore.activeConversationId === conv.id
                    ? 'bg-gradient-to-tr from-indigo-600 to-blue-600'
                    : 'bg-slate-800'
                "
              >
                {{ (conv.customer?.name || "C").charAt(0).toUpperCase() }}
              </div>

              <!-- Thread Info -->
              <div class="flex-1 min-w-0 space-y-1">
                <div class="flex items-center justify-between gap-1">
                  <span class="text-xs font-bold text-slate-900 truncate">
                    {{ conv.customer?.name || "Customer #" + conv.customer_id }}
                  </span>
                  <span class="text-[10px] text-slate-400 flex-shrink-0">
                    {{ formatChatTime(conv.updated_at) }}
                  </span>
                </div>

                <div class="flex items-center gap-1.5 flex-wrap">
                  <span
                    v-if="conv.customer?.customer_profile?.segment_rank"
                    class="px-1.5 py-0.2 rounded text-[9px] font-bold border"
                    :class="
                      getSegmentBadge(
                        conv.customer.customer_profile.segment_rank,
                      )
                    "
                  >
                    {{ conv.customer.customer_profile.segment_rank }}
                  </span>
                  <span
                    class="px-1.5 py-0.2 rounded-full text-[9px] font-bold uppercase tracking-wider"
                    :class="
                      conv.status === 'active'
                        ? 'bg-emerald-100 text-emerald-800'
                        : conv.status === 'resolved'
                          ? 'bg-blue-100 text-blue-800'
                          : 'bg-slate-200 text-slate-700'
                    "
                  >
                    {{ conv.status }}
                  </span>
                </div>

                <p class="text-xs text-slate-500 truncate leading-snug">
                  {{ conv.latest_message?.content || "No messages yet" }}
                </p>
              </div>

              <!-- Unread badge -->
              <span
                v-if="conv.unread_count && conv.unread_count > 0"
                class="w-5 h-5 rounded-full bg-rose-600 text-white text-[10px] font-black flex items-center justify-center flex-shrink-0 absolute right-3 bottom-3 shadow-2xs"
              >
                {{ conv.unread_count }}
              </span>
            </button>
          </div>
        </div>

        <!-- RIGHT COLUMN: Active Chat Dialogue Stream (8 cols) -->
        <div class="lg:col-span-8 flex flex-col h-full bg-white min-h-0">
          <!-- State: No Conversation Selected -->
          <div
            v-if="!adminStore.activeConversation"
            class="flex-1 flex flex-col items-center justify-center p-8 text-center space-y-3"
          >
            <div
              class="w-16 h-16 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-3xl"
            >
              💬
            </div>
            <h3 class="text-sm font-bold text-slate-800">
              Select a Conversation
            </h3>
            <p class="text-xs text-slate-500 max-w-sm">
              Choose a buyer conversation from the left thread list to review
              their order history, inquiry, and reply in real-time.
            </p>
          </div>

          <!-- State: Active Conversation Open -->
          <template v-else>
            <!-- Chat Room Header -->
            <div
              class="p-4 border-b border-slate-200 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
              <div class="flex items-center gap-3">
                <div
                  class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-600 to-blue-600 text-white font-black text-sm flex items-center justify-center flex-shrink-0 shadow-2xs"
                >
                  {{
                    (adminStore.activeConversation.customer?.name || "C")
                      .charAt(0)
                      .toUpperCase()
                  }}
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900">
                      {{
                        adminStore.activeConversation.customer?.name ||
                        "Collector"
                      }}
                    </h3>
                    <span
                      class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                      :class="
                        adminStore.activeConversation.status === 'active'
                          ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                          : adminStore.activeConversation.status === 'resolved'
                            ? 'bg-blue-100 text-blue-800 border border-blue-200'
                            : 'bg-slate-100 text-slate-700 border border-slate-200'
                      "
                    >
                      {{ adminStore.activeConversation.status }}
                    </span>
                  </div>
                  <div
                    class="text-[11px] text-slate-500 flex items-center gap-2"
                  >
                    <span>{{
                      adminStore.activeConversation.customer?.email
                    }}</span>
                    <span v-if="adminStore.activeConversation.customer?.phone"
                      >&bull;
                      {{ adminStore.activeConversation.customer.phone }}</span
                    >
                  </div>
                </div>
              </div>

              <!-- Status Action Buttons -->
              <div class="flex items-center gap-2">
                <span
                  class="text-[11px] text-slate-400 font-bold uppercase tracking-wider"
                  >Status:</span
                >
                <div
                  class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-xl border border-slate-200"
                >
                  <button
                    type="button"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer"
                    :class="
                      adminStore.activeConversation.status === 'active'
                        ? 'bg-emerald-600 text-white shadow-xs'
                        : 'text-slate-600 hover:text-slate-900'
                    "
                    @click="handleUpdateStatus('active')"
                  >
                    Active
                  </button>
                  <button
                    type="button"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer"
                    :class="
                      adminStore.activeConversation.status === 'resolved'
                        ? 'bg-blue-600 text-white shadow-xs'
                        : 'text-slate-600 hover:text-slate-900'
                    "
                    @click="handleUpdateStatus('resolved')"
                  >
                    Resolved
                  </button>
                  <button
                    type="button"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer"
                    :class="
                      adminStore.activeConversation.status === 'closed'
                        ? 'bg-slate-700 text-white shadow-xs'
                        : 'text-slate-600 hover:text-slate-900'
                    "
                    @click="handleUpdateStatus('closed')"
                  >
                    Closed
                  </button>
                </div>
              </div>
            </div>

            <!-- Messages Stream Area -->
            <div
              ref="adminChatScrollContainer"
              class="flex-1 p-4 overflow-y-auto space-y-3.5 bg-slate-50/40 text-xs min-h-0 scroll-smooth"
            >
              <!-- Loading spinner -->
              <div
                v-if="
                  adminStore.isLoadingChatMessages &&
                  adminStore.activeConversationMessages.length === 0
                "
                class="py-12 text-center text-slate-400 font-medium"
              >
                <div class="inline-block animate-spin text-xl mb-1">⏳</div>
                <p>Loading messages...</p>
              </div>

              <!-- Empty Room -->
              <div
                v-else-if="adminStore.activeConversationMessages.length === 0"
                class="py-12 text-center text-slate-400 space-y-1"
              >
                <div class="text-2xl">💬</div>
                <p class="font-bold text-slate-600">
                  No messages in this chat room yet
                </p>
                <p class="text-[11px]">
                  Send a message below to greet this customer.
                </p>
              </div>

              <!-- Message Dialogue Bubbles -->
              <template v-else>
                <div
                  v-for="msg in adminStore.activeConversationMessages"
                  :key="msg.id"
                  class="flex flex-col"
                  :class="
                    msg.sender_id ===
                      adminStore.activeConversation.customer_id ||
                    msg.sender?.user_type === 'customer'
                      ? 'items-start'
                      : 'items-end'
                  "
                >
                  <!-- Bubble Sender Label -->
                  <div
                    class="flex items-center gap-1 text-[10px] text-slate-400 mb-1 px-1 font-semibold"
                  >
                    <span
                      v-if="
                        msg.sender_id ===
                          adminStore.activeConversation.customer_id ||
                        msg.sender?.user_type === 'customer'
                      "
                    >
                      👤
                      {{
                        msg.sender?.name ||
                        adminStore.activeConversation.customer?.name ||
                        "Customer"
                      }}
                    </span>
                    <span v-else class="text-indigo-600 font-bold">
                      🧑‍💼
                      {{
                        msg.sender?.name ||
                        adminStore.currentAdmin?.name ||
                        "Shop Staff"
                      }}
                      (You)
                    </span>
                  </div>

                  <!-- Speech Bubble -->
                  <div
                    class="max-w-[80%] p-3.5 rounded-2xl shadow-2xs text-xs leading-relaxed"
                    :class="
                      msg.sender_id ===
                        adminStore.activeConversation.customer_id ||
                      msg.sender?.user_type === 'customer'
                        ? 'bg-white border border-slate-200 text-slate-800 rounded-tl-xs'
                        : 'bg-indigo-600 text-white rounded-tr-xs shadow-indigo-600/20'
                    "
                  >
                    <p class="whitespace-pre-wrap break-words">
                      {{ msg.content }}
                    </p>
                  </div>

                  <!-- Bubble Timestamp & Read Receipt -->
                  <div
                    class="flex items-center gap-1 text-[9px] text-slate-400 mt-1 px-1"
                  >
                    <span>{{ formatChatTime(msg.created_at) }}</span>
                    <span
                      v-if="
                        msg.sender_id !==
                          adminStore.activeConversation.customer_id &&
                        msg.sender?.user_type !== 'customer'
                      "
                      :class="
                        msg.is_read
                          ? 'text-emerald-600 font-bold'
                          : 'text-slate-400'
                      "
                    >
                      &bull; {{ msg.is_read ? "✓✓ Seen by buyer" : "✓ Sent" }}
                    </span>
                  </div>
                </div>
              </template>
            </div>

            <!-- Quick Macro Reply Chips -->
            <div
              class="px-4 py-2 bg-white border-t border-slate-100 flex items-center gap-1.5 overflow-x-auto text-[11px]"
            >
              <span
                class="text-slate-400 font-bold uppercase tracking-wider text-[10px] flex-shrink-0"
              >
                Quick:
              </span>
              <button
                type="button"
                class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium whitespace-nowrap cursor-pointer transition-colors"
                @click="
                  handleSendAdminReply(
                    'Hello! Your order has been securely packed and handed over to our courier partner. 🚚',
                  )
                "
              >
                📦 Order Packed &amp; Shipped
              </button>
              <button
                type="button"
                class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium whitespace-nowrap cursor-pointer transition-colors"
                @click="
                  handleSendAdminReply(
                    'All our collectible booster boxes and single cards are 100% authentic Japanese imports. ✨',
                  )
                "
              >
                ✨ Authenticity Guaranteed
              </button>
              <button
                type="button"
                class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium whitespace-nowrap cursor-pointer transition-colors"
                @click="
                  handleSendAdminReply(
                    'Thank you for choosing RLG Hobby Shop! Please let us know if you need anything else.',
                  )
                "
              >
                🙏 Thank You Note
              </button>
            </div>

            <!-- Message Composer -->
            <div class="p-3.5 bg-white border-t border-slate-200">
              <form
                class="flex items-center gap-2"
                @submit.prevent="handleSendAdminReply()"
              >
                <input
                  v-model="adminReplyInput"
                  type="text"
                  placeholder="Type a message to customer... (Press Enter to send)"
                  class="flex-1 bg-slate-50 border border-slate-300 focus:border-indigo-500 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white transition-colors"
                  :disabled="isSendingAdminReply"
                  @keydown.enter.exact.prevent="handleSendAdminReply()"
                />
                <button
                  type="submit"
                  class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs transition-all shadow-sm cursor-pointer disabled:opacity-50 flex items-center gap-1.5 flex-shrink-0"
                  :disabled="isSendingAdminReply || !adminReplyInput.trim()"
                >
                  <span v-if="isSendingAdminReply" class="animate-spin"
                    >⏳</span
                  >
                  <span v-else>Send ➤</span>
                </button>
              </form>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- TAB 3: Customer Reviews (Integrated with /api/customer-reviews & customer_review table) -->
    <div v-else-if="activeTab === 'reviews'" class="space-y-4">
      <!-- Reviews Header Metrics & Filter Bar -->
      <div
        class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4"
      >
        <div class="flex items-center gap-3 w-full md:w-auto">
          <!-- Rating badge -->
          <div
            class="px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200/80 flex items-center gap-1.5"
          >
            <span class="text-amber-500 font-black text-sm"
              >⭐ {{ averageReviewRating }}</span
            >
            <span class="text-[11px] text-amber-800 font-bold"
              >Store Rating</span
            >
          </div>

          <!-- Search Input -->
          <div class="relative flex-1 md:w-72">
            <input
              v-model="reviewSearchQuery"
              type="text"
              placeholder="Search reviews by product or customer..."
              class="w-full text-xs px-3.5 py-2.5 pl-9 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-amber-500"
            />
            <span class="absolute left-3 top-2.5 text-slate-400 text-xs"
              >🔍</span
            >
          </div>
        </div>

        <!-- Filter tabs by star rating / reply status -->
        <div
          class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 overflow-x-auto w-full md:w-auto"
        >
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all cursor-pointer"
            :class="
              reviewStarFilter === 'all'
                ? 'bg-white text-slate-900 shadow-xs'
                : 'text-slate-500 hover:text-slate-800'
            "
            @click="reviewStarFilter = 'all'"
          >
            All Reviews ({{ adminStore.customerReviews.length }})
          </button>
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all cursor-pointer text-amber-600"
            :class="
              reviewStarFilter === '5'
                ? 'bg-white font-extrabold shadow-xs'
                : 'hover:text-amber-800'
            "
            @click="reviewStarFilter = '5'"
          >
            5 Stars ⭐⭐⭐⭐⭐
          </button>
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all cursor-pointer text-amber-600"
            :class="
              reviewStarFilter === '4'
                ? 'bg-white font-extrabold shadow-xs'
                : 'hover:text-amber-800'
            "
            @click="reviewStarFilter = '4'"
          >
            4 Stars ⭐⭐⭐⭐
          </button>
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all cursor-pointer flex items-center gap-1"
            :class="
              reviewStarFilter === 'needs_reply'
                ? 'bg-white text-rose-700 shadow-xs'
                : 'text-slate-500 hover:text-slate-800'
            "
            @click="reviewStarFilter = 'needs_reply'"
          >
            <span>Needs Reply</span>
            <span
              v-if="needsReplyReviewsCount > 0"
              class="px-1.5 py-0.2 rounded-full bg-rose-600 text-white text-[9px] font-bold"
            >
              {{ needsReplyReviewsCount }}
            </span>
          </button>
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all cursor-pointer"
            :class="
              reviewStarFilter === 'replied'
                ? 'bg-white text-emerald-700 shadow-xs'
                : 'text-slate-500 hover:text-slate-800'
            "
            @click="reviewStarFilter = 'replied'"
          >
            ✓ Replied
          </button>
        </div>
      </div>

      <!-- Reviews Cards List -->
      <div
        class="bg-white rounded-2xl border border-slate-200/80 shadow-xs divide-y divide-slate-100"
      >
        <div
          v-if="adminStore.isLoadingReviews"
          class="p-8 text-center text-xs text-slate-400 font-bold"
        >
          Loading reviews from customer_review table...
        </div>

        <div
          v-else-if="filteredReviews.length === 0"
          class="p-8 text-center text-xs text-slate-400 space-y-1"
        >
          <div class="text-2xl">⭐</div>
          <p class="font-bold text-slate-600">No customer reviews found.</p>
          <p class="text-[11px]">
            Product reviews submitted by verified buyers will appear here.
          </p>
        </div>

        <div
          v-for="rev in filteredReviews"
          :key="rev.id"
          class="p-5 flex flex-col md:flex-row md:items-start justify-between gap-4 hover:bg-slate-50/50 transition-colors"
        >
          <div class="space-y-2.5 flex-1 min-w-0">
            <!-- Review Header: Badge + Product Title + Stars -->
            <div class="flex items-center gap-2 flex-wrap">
              <span
                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800"
              >
                REVIEW
              </span>

              <span class="text-xs font-extrabold text-slate-900">
                Review for {{ rev.productName }} ({{ rev.stars }} Stars)
              </span>

              <!-- Star Rating Graphic -->
              <span class="text-amber-400 text-xs tracking-tight">
                {{ "★".repeat(rev.stars) }}{{ "☆".repeat(5 - rev.stars) }}
              </span>

              <!-- Unreplied Indicator Dot -->
              <span
                v-if="!rev.staffReply"
                class="w-2 h-2 rounded-full bg-rose-600"
                title="Awaiting staff acknowledgment"
              ></span>
            </div>

            <!-- Review Message Body -->
            <p
              class="text-xs text-slate-700 leading-relaxed bg-slate-50/80 p-3 rounded-xl border border-slate-100"
            >
              "{{ rev.message }}"
            </p>

            <!-- Review Attached Photo if available -->
            <div v-if="rev.image" class="pt-1">
              <img
                :src="rev.image"
                alt="Review Attachment"
                class="w-20 h-20 rounded-xl object-contain bg-slate-950 border border-slate-800 p-1"
              />
            </div>

            <!-- Staff Support Reply (if answered) -->
            <div
              v-if="rev.staffReply"
              class="p-3 bg-emerald-50/80 rounded-xl border border-emerald-200/80 space-y-1"
            >
              <div
                class="flex items-center justify-between text-[10px] font-black text-emerald-800 uppercase tracking-wider"
              >
                <span>STAFF SUPPORT REPLY:</span>
                <span
                  v-if="rev.staffName"
                  class="text-emerald-700 font-semibold normal-case"
                >
                  by {{ rev.staffName }} &bull; {{ rev.repliedAt || "Replied" }}
                </span>
              </div>
              <p class="text-xs text-emerald-900 font-medium">
                {{ rev.staffReply }}
              </p>
            </div>

            <!-- Customer Reviewer Metadata -->
            <div
              class="flex items-center gap-2.5 text-[11px] text-slate-400 pt-0.5"
            >
              <span class="font-bold text-slate-700">
                {{ rev.customerName }} ({{ rev.email }})
              </span>
              <span>&bull;</span>
              <span>{{ rev.createdAt }}</span>
            </div>
          </div>

          <!-- Actions: Reply to Review & Delete -->
          <div
            class="flex items-center gap-2 self-start md:self-auto flex-shrink-0"
          >
            <button
              type="button"
              class="px-3.5 py-1.5 rounded-xl font-bold text-xs cursor-pointer shadow-2xs transition-all flex items-center gap-1.5"
              :class="
                rev.staffReply
                  ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'
                  : 'bg-slate-900 hover:bg-slate-800 text-white'
              "
              @click="openReviewReply(rev)"
            >
              <span>{{
                rev.staffReply ? "✓ Resolved" : "Reply & Resolve"
              }}</span>
            </button>

            <button
              type="button"
              class="p-1.5 rounded-xl hover:bg-rose-50 text-slate-400 hover:text-rose-600 text-xs transition-colors cursor-pointer"
              title="Delete customer review"
              :disabled="isDeletingReview === rev.id"
              @click="deleteReview(rev.id)"
            >
              🗑️
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Customer CRM Detail Modal -->
    <teleport to="body">
      <div
        v-if="isProfileModalOpen && selectedCustomer"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
      >
        <div
          class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display"
        >
          <div
            class="flex items-center justify-between pb-3 border-b border-slate-100"
          >
            <div>
              <h3 class="text-base font-bold text-slate-900">
                {{ selectedCustomer.name }}
              </h3>
              <p class="text-xs text-slate-500">{{ selectedCustomer.email }}</p>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer"
              @click="isProfileModalOpen = false"
            >
              ✕
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <div class="grid grid-cols-2 gap-3">
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-[10px] font-bold text-slate-400 uppercase"
                  >Lifetime Value (LTV)</span
                >
                <p class="text-base font-black text-slate-900 mt-0.5">
                  {{ formatCurrency(selectedCustomer.lifetimeValue) }}
                </p>
              </div>
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-[10px] font-bold text-slate-400 uppercase"
                  >Orders Completed</span
                >
                <p class="text-base font-black text-slate-900 mt-0.5">
                  {{ selectedCustomer.totalOrders }}
                </p>
              </div>
            </div>

            <div
              class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1"
            >
              <span class="text-[10px] font-bold text-slate-400 uppercase"
                >Collector Behavioral Segment</span
              >
              <div class="pt-1">
                <select
                  v-model="selectedCustomer.segment"
                  class="text-xs font-bold p-1.5 rounded-lg border border-slate-300 bg-white"
                >
                  <option value="VIP">👑 VIP Collector</option>
                  <option value="Regular">Regular Buyer</option>
                  <option value="Wholesale">💼 Wholesale Partner</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>
            </div>

            <div
              v-if="selectedCustomer.notes"
              class="p-3 bg-amber-50 rounded-xl border border-amber-200/80"
            >
              <span class="text-[10px] font-bold text-amber-800 uppercase"
                >Collector Internal Notes</span
              >
              <p class="text-amber-900 mt-0.5">{{ selectedCustomer.notes }}</p>
            </div>
          </div>

          <div class="pt-2 border-t border-slate-100 flex justify-end">
            <button
              type="button"
              class="px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl cursor-pointer"
              @click="isProfileModalOpen = false"
            >
              Save Profile
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Reply to Customer Message Modal (customer_message API) -->
    <teleport to="body">
      <div
        v-if="isMessageReplyModalOpen && selectedMessage"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
      >
        <div
          class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display"
        >
          <div
            class="flex items-center justify-between pb-3 border-b border-slate-100"
          >
            <div>
              <h3 class="text-base font-bold text-slate-900">
                Reply to {{ selectedMessage.customerName }}
              </h3>
              <p class="text-xs text-slate-500">
                Subject: {{ selectedMessage.subject }}
              </p>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer"
              @click="isMessageReplyModalOpen = false"
            >
              ✕
            </button>
          </div>

          <div
            class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600"
          >
            <span class="font-bold text-slate-800">Original Inquiry:</span>
            <p class="mt-1">"{{ selectedMessage.message }}"</p>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1"
              >Official Support Response</label
            >
            <textarea
              v-model="messageReplyText"
              rows="5"
              placeholder="Type your official reply here..."
              class="w-full text-xs p-3 rounded-xl border border-slate-300 focus:outline-none focus:border-slate-900"
            ></textarea>
          </div>

          <div class="flex justify-between items-center pt-2">
            <span class="text-[11px] text-slate-400">
              Will record response &amp; resolve ticket
            </span>
            <button
              type="button"
              class="px-4 py-2 bg-slate-900 text-white font-bold text-xs rounded-xl cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
              :disabled="isSendingMessageReply || !messageReplyText.trim()"
              @click="sendMessageReply"
            >
              <span>{{
                isSendingMessageReply ? "Sending..." : "Send & Resolve Ticket"
              }}</span>
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Reply to Customer Review Modal (customer_review API) -->
    <teleport to="body">
      <div
        v-if="isReviewReplyModalOpen && selectedReview"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
      >
        <div
          class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display"
        >
          <div
            class="flex items-center justify-between pb-3 border-b border-slate-100"
          >
            <div>
              <div class="flex items-center gap-2">
                <span class="text-amber-400 text-xs">
                  {{ "★".repeat(selectedReview.stars) }}
                </span>
                <h3 class="text-base font-bold text-slate-900">
                  Reply to {{ selectedReview.customerName }}'s Review
                </h3>
              </div>
              <p class="text-xs text-slate-500">
                Item: {{ selectedReview.productName }}
              </p>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer"
              @click="isReviewReplyModalOpen = false"
            >
              ✕
            </button>
          </div>

          <div
            class="p-3 bg-amber-50/60 rounded-xl border border-amber-200/80 text-xs text-slate-700"
          >
            <span class="font-bold text-amber-900">Customer Review:</span>
            <p class="mt-1 italic">"{{ selectedReview.message }}"</p>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1"
              >Store Support Response</label
            >
            <textarea
              v-model="reviewReplyText"
              rows="5"
              placeholder="Type your public store reply to this review..."
              class="w-full text-xs p-3 rounded-xl border border-slate-300 focus:outline-none focus:border-amber-600"
            ></textarea>
          </div>

          <div class="flex justify-between items-center pt-2">
            <span class="text-[11px] text-slate-400">
              Published publicly under the product review
            </span>
            <button
              type="button"
              class="px-4 py-2 bg-slate-900 text-white font-bold text-xs rounded-xl cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
              :disabled="isSendingReviewReply || !reviewReplyText.trim()"
              @click="sendReviewReply"
            >
              <span>{{
                isSendingReviewReply ? "Posting..." : "Post Official Response"
              }}</span>
            </button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>
