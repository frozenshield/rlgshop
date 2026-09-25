<script setup lang="ts">
import { ref, watch } from "vue";
import { useAuthStore, type UserSettings } from "../auth.store";
import BaseButton from "@/shared/components/BaseButton.vue";

interface Props {
  isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "saved", message: string): void;
}>();

const authStore = useAuthStore();

// ─── Local Form State (Mapped to Backend Fields) ─────────────────────────────
/**
 * BACKEND FIELD MAPPINGS:
 * - current_password: string (for password change)
 * - new_password: string (new password)
 * - new_password_confirmation: string (confirm new password)
 * - email_notifications: boolean (order status emails)
 * - order_updates_sms: boolean (SMS updates)
 * - marketing_emails: boolean (newsletter & drops)
 * - currency_preference: 'PHP' | 'USD' | 'JPY'
 * - two_factor_auth: boolean (2FA enabled)
 * - public_collection: boolean (public profile wishlist)
 */
const settingsForm = ref<UserSettings>({
  email_notifications: true,
  order_updates_sms: true,
  marketing_emails: false,
  currency_preference: "PHP",
  two_factor_auth: false,
  public_collection: true,
});

const currentPassword = ref("");
const newPassword = ref("");
const confirmPassword = ref("");
const passwordError = ref("");
const passwordSuccess = ref("");

const isSaving = ref(false);

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      settingsForm.value = { ...authStore.settings };
      currentPassword.value = "";
      newPassword.value = "";
      confirmPassword.value = "";
      passwordError.value = "";
      passwordSuccess.value = "";
    }
  },
  { immediate: true },
);

const handleSaveSettings = async () => {
  isSaving.value = true;
  passwordError.value = "";
  passwordSuccess.value = "";

  // Validate password change if entered
  if (newPassword.value || confirmPassword.value) {
    if (!currentPassword.value) {
      passwordError.value = "Current password is required to change password.";
      isSaving.value = false;
      return;
    }
    if (newPassword.value.length < 8) {
      passwordError.value = "New password must be at least 8 characters.";
      isSaving.value = false;
      return;
    }
    if (newPassword.value !== confirmPassword.value) {
      passwordError.value = "New passwords do not match.";
      isSaving.value = false;
      return;
    }
    passwordSuccess.value = "Password updated successfully!";
  }

  try {
    await authStore.updateSettings(settingsForm.value);
    emit("saved", "Account settings saved successfully! ⚙️");
    setTimeout(() => {
      emit("close");
    }, 600);
  } finally {
    isSaving.value = false;
  }
};
</script>

<template>
  <teleport to="body">
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto"
        @click.self="emit('close')"
      >
        <div
          class="relative w-full max-w-xl bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl p-6 sm:p-8 text-slate-100 font-display my-8 animate-scale-up"
        >
          <!-- Modal Header -->
          <div
            class="flex items-center justify-between pb-5 border-b border-slate-800"
          >
            <div class="flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-2xl bg-indigo-950/80 border border-indigo-500/30 flex items-center justify-center text-lg text-indigo-400"
              >
                ⚙️
              </div>
              <div>
                <h3 class="text-xl font-black text-white">Account Settings</h3>
                <p class="text-xs text-slate-400">
                  Manage security, notifications, and shopping preferences
                </p>
              </div>
            </div>

            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-800/80 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-colors cursor-pointer text-sm"
              @click="emit('close')"
            >
              ✕
            </button>
          </div>

          <form class="space-y-6 my-6" @submit.prevent="handleSaveSettings">
            <!-- Section 1: Security & Password -->
            <div class="space-y-4">
              <h4
                class="text-xs font-bold uppercase tracking-wider text-indigo-400 flex items-center gap-2"
              >
                <span>🔒 Security &amp; Password</span>
                <span class="h-px bg-slate-800 flex-1"></span>
              </h4>

              <div class="space-y-3">
                <div>
                  <label
                    for="current-password"
                    class="block text-xs font-semibold text-slate-300 mb-1"
                  >
                    Current Password
                  </label>
                  <input
                    id="current-password"
                    v-model="currentPassword"
                    type="password"
                    placeholder="Enter current password to change"
                    class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition-colors"
                  />
                  <p class="text-[10px] text-slate-500 mt-1">
                    Backend field: <code>current_password</code>
                  </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label
                      for="new-password"
                      class="block text-xs font-semibold text-slate-300 mb-1"
                    >
                      New Password
                    </label>
                    <input
                      id="new-password"
                      v-model="newPassword"
                      type="password"
                      placeholder="Min. 8 characters"
                      class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition-colors"
                    />
                    <p class="text-[10px] text-slate-500 mt-1">
                      Backend field: <code>new_password</code>
                    </p>
                  </div>

                  <div>
                    <label
                      for="confirm-password"
                      class="block text-xs font-semibold text-slate-300 mb-1"
                    >
                      Confirm Password
                    </label>
                    <input
                      id="confirm-password"
                      v-model="confirmPassword"
                      type="password"
                      placeholder="Re-enter new password"
                      class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition-colors"
                    />
                    <p class="text-[10px] text-slate-500 mt-1">
                      Backend field: <code>new_password_confirmation</code>
                    </p>
                  </div>
                </div>

                <p v-if="passwordError" class="text-xs font-bold text-rose-400">
                  {{ passwordError }}
                </p>
                <p
                  v-if="passwordSuccess"
                  class="text-xs font-bold text-emerald-400"
                >
                  {{ passwordSuccess }}
                </p>

                <!-- Two-Factor Authentication Toggle -->
                <div
                  class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800"
                >
                  <div>
                    <h5 class="text-xs font-bold text-white">
                      Two-Factor Authentication (2FA)
                    </h5>
                    <p class="text-[11px] text-slate-400">
                      Add an extra layer of security on login
                    </p>
                    <p class="text-[10px] text-slate-500 mt-0.5">
                      Backend field: <code>two_factor_auth</code>
                    </p>
                  </div>
                  <label
                    class="relative inline-flex items-center cursor-pointer"
                  >
                    <input
                      v-model="settingsForm.two_factor_auth"
                      type="checkbox"
                      class="sr-only peer"
                    />
                    <div
                      class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-400"
                    ></div>
                  </label>
                </div>
              </div>
            </div>

            <!-- Section 2: Notifications -->
            <div class="space-y-4">
              <h4
                class="text-xs font-bold uppercase tracking-wider text-indigo-400 flex items-center gap-2"
              >
                <span>🔔 Notification Preferences</span>
                <span class="h-px bg-slate-800 flex-1"></span>
              </h4>

              <div class="space-y-2.5">
                <!-- Email Notifications -->
                <div
                  class="flex items-center justify-between p-3 rounded-xl bg-slate-950/70 border border-slate-800"
                >
                  <div>
                    <h5 class="text-xs font-bold text-white">
                      Order Status &amp; Dispatch
                    </h5>
                    <p class="text-[11px] text-slate-400">
                      Receive tracking updates via registered email
                    </p>
                    <p class="text-[10px] text-slate-500">
                      Backend field: <code>email_notifications</code>
                    </p>
                  </div>
                  <input
                    v-model="settingsForm.email_notifications"
                    type="checkbox"
                    class="w-4 h-4 rounded text-amber-400 bg-slate-900 border-slate-700 focus:ring-0 cursor-pointer"
                  />
                </div>

                <!-- SMS Updates -->
                <div
                  class="flex items-center justify-between p-3 rounded-xl bg-slate-950/70 border border-slate-800"
                >
                  <div>
                    <h5 class="text-xs font-bold text-white">
                      SMS Delivery Alerts
                    </h5>
                    <p class="text-[11px] text-slate-400">
                      Get real-time rider delivery notifications on mobile
                    </p>
                    <p class="text-[10px] text-slate-500">
                      Backend field: <code>order_updates_sms</code>
                    </p>
                  </div>
                  <input
                    v-model="settingsForm.order_updates_sms"
                    type="checkbox"
                    class="w-4 h-4 rounded text-amber-400 bg-slate-900 border-slate-700 focus:ring-0 cursor-pointer"
                  />
                </div>

                <!-- Marketing Emails -->
                <div
                  class="flex items-center justify-between p-3 rounded-xl bg-slate-950/70 border border-slate-800"
                >
                  <div>
                    <h5 class="text-xs font-bold text-white">
                      Restock Drops &amp; Flash Deals
                    </h5>
                    <p class="text-[11px] text-slate-400">
                      Be notified of rare booster boxes &amp; Gunpla arrivals
                    </p>
                    <p class="text-[10px] text-slate-500">
                      Backend field: <code>marketing_emails</code>
                    </p>
                  </div>
                  <input
                    v-model="settingsForm.marketing_emails"
                    type="checkbox"
                    class="w-4 h-4 rounded text-amber-400 bg-slate-900 border-slate-700 focus:ring-0 cursor-pointer"
                  />
                </div>
              </div>
            </div>

            <!-- Section 3: Currency & Privacy -->
            <div class="space-y-4">
              <h4
                class="text-xs font-bold uppercase tracking-wider text-indigo-400 flex items-center gap-2"
              >
                <span>🌐 Regional &amp; Privacy</span>
                <span class="h-px bg-slate-800 flex-1"></span>
              </h4>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label
                    for="settings-currency"
                    class="block text-xs font-semibold text-slate-300 mb-1"
                  >
                    Default Store Currency
                  </label>
                  <select
                    id="settings-currency"
                    v-model="settingsForm.currency_preference"
                    class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-amber-400 transition-colors"
                  >
                    <option value="PHP">Philippine Peso (PHP ₱)</option>
                    <option value="USD">US Dollar (USD $)</option>
                    <option value="JPY">Japanese Yen (JPY ¥)</option>
                  </select>
                  <p class="text-[10px] text-slate-500 mt-1">
                    Backend field: <code>currency_preference</code>
                  </p>
                </div>

                <div
                  class="flex items-center justify-between p-3 rounded-xl bg-slate-950/70 border border-slate-800 self-end"
                >
                  <div>
                    <h5 class="text-xs font-bold text-white">
                      Public Showcase
                    </h5>
                    <p class="text-[10px] text-slate-400">
                      Share wishlist with community
                    </p>
                    <p class="text-[10px] text-slate-500">
                      Backend field: <code>public_collection</code>
                    </p>
                  </div>
                  <input
                    v-model="settingsForm.public_collection"
                    type="checkbox"
                    class="w-4 h-4 rounded text-amber-400 bg-slate-900 border-slate-700 focus:ring-0 cursor-pointer"
                  />
                </div>
              </div>
            </div>

            <!-- Footer Actions -->
            <div
              class="pt-5 border-t border-slate-800 flex items-center justify-between gap-3"
            >
              <button
                type="button"
                class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer"
                @click="emit('close')"
              >
                Cancel
              </button>

              <BaseButton
                type="submit"
                variant="primary"
                size="md"
                :disabled="isSaving"
              >
                <span v-if="isSaving">Saving...</span>
                <span v-else>Save Preferences</span>
              </BaseButton>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </teleport>
</template>
