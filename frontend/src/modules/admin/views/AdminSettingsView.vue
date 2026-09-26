<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useAdminStore } from "../admin.store";
import type { StaffMember } from "../admin.types";

const adminStore = useAdminStore();

const activeTab = ref<"staff" | "payments" | "shipping" | "localization">(
  "staff",
);
const savedFeedback = ref("");
const staffError = ref("");
const isLoadingStaff = ref(false);

interface StaffRole {
  id: number;
  name: string;
  label: string;
  permissions_label: string;
  description: string;
  access_matrices?: any[];
}
const staffRoles = ref<StaffRole[]>([]);

// Add Staff Modal State
const isAddStaffOpen = ref(false);
const newStaffName = ref("");
const newStaffEmail = ref("");
const newStaffPassword = ref("");
const showNewStaffPassword = ref(false);
const newStaffRoleId = ref<number>(3);
const isSubmittingStaff = ref(false);

// Edit Staff Modal State
const isEditStaffOpen = ref(false);
const editingStaff = ref<StaffMember | null>(null);
const editStaffRoleId = ref<number>(3);
const editStaffPassword = ref("");
const showEditStaffPassword = ref(false);
const editStaffActive = ref(true);
const isUpdatingStaff = ref(false);

const fetchRoles = async () => {
  try {
    const res = await fetch("/api/staff-roles");
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data)) {
        staffRoles.value = data;
        if (data.length > 0) {
          newStaffRoleId.value = data[data.length - 1].id;
        }
      }
    }
  } catch (e) {
    console.error("Failed to load staff roles from API", e);
  }
};

const fetchStaff = async () => {
  isLoadingStaff.value = true;
  staffError.value = "";
  try {
    const res = await fetch("/api/staff");
    if (res.ok) {
      const json = await res.json();
      if (json.success && Array.isArray(json.data)) {
        adminStore.staffMembers = json.data.map((s: any) => {
          const roleName = s.role?.name || s.role_name || "Staff";
          const roleLabel =
            s.role?.label ||
            s.role_label ||
            (roleName === "Admin" ? "Super Admin (Unrestricted)" : roleName);
          let perms: string[] = [];
          if (Array.isArray(s.permissions) && s.permissions.length > 0) {
            perms = s.permissions;
          } else if (s.role?.permissions_label) {
            perms = s.role.permissions_label
              .split(",")
              .map((p: string) => p.trim());
          }

          return {
            id: s.id,
            name: s.name,
            email: s.email,
            role: roleName === "Admin" ? "Super Admin" : roleName,
            roleLabel: roleLabel,
            refStaffRoleId: s.ref_staff_role_id || s.role?.id,
            permissions: perms,
            isActive: Boolean(s.is_active),
            roleDetails: s.role,
          };
        });
      }
    } else {
      staffError.value = "Could not fetch staff from server.";
    }
  } catch (e) {
    console.error("Failed to fetch staff from API", e);
    staffError.value = "Failed to connect to the backend server.";
  } finally {
    isLoadingStaff.value = false;
  }
};

onMounted(() => {
  fetchRoles();
  fetchStaff();
});

const addStaff = async () => {
  staffError.value = "";
  if (!newStaffName.value.trim() || !newStaffEmail.value.trim()) {
    staffError.value = "Please enter both name and email.";
    return;
  }

  isSubmittingStaff.value = true;
  try {
    const payload: any = {
      name: newStaffName.value.trim(),
      email: newStaffEmail.value.trim(),
      ref_staff_role_id: newStaffRoleId.value,
      is_active: true,
    };
    if (newStaffPassword.value.trim()) {
      payload.password = newStaffPassword.value.trim();
    }

    const res = await fetch("/api/staff", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });

    const json = await res.json();
    if (!res.ok) {
      staffError.value = json?.message || "Failed to create staff member.";
      return;
    }

    await fetchStaff();
    isAddStaffOpen.value = false;
    newStaffName.value = "";
    newStaffEmail.value = "";
    newStaffPassword.value = "";
    showFeedback(
      `New staff account for "${json.data?.name}" created successfully.`,
    );
  } catch (e) {
    staffError.value = "Server error while creating staff member.";
  } finally {
    isSubmittingStaff.value = false;
  }
};

const openEditStaff = (staff: StaffMember) => {
  editingStaff.value = staff;
  editStaffRoleId.value =
    staff.refStaffRoleId ||
    (staff.role === "Super Admin" || staff.role === "Admin"
      ? 1
      : staff.role === "Store Manager"
        ? 2
        : 3);
  editStaffActive.value = staff.isActive;
  editStaffPassword.value = "";
  isEditStaffOpen.value = true;
};

const saveEditStaff = async () => {
  if (!editingStaff.value) return;
  isUpdatingStaff.value = true;
  try {
    const payload: any = {
      ref_staff_role_id: editStaffRoleId.value,
      is_active: editStaffActive.value,
    };
    if (editStaffPassword.value.trim()) {
      payload.password = editStaffPassword.value.trim();
    }

    const res = await fetch(`/api/staff/${editingStaff.value.id}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });

    if (res.ok) {
      await fetchStaff();
      isEditStaffOpen.value = false;
      editStaffPassword.value = "";
      showFeedback(`Updated permissions for ${editingStaff.value.name}.`);
    } else {
      const json = await res.json();
      staffError.value = json?.message || "Failed to update staff permissions.";
    }
  } catch (e) {
    console.error("Failed to update staff:", e);
  } finally {
    isUpdatingStaff.value = false;
  }
};

const deleteStaff = async (staff: StaffMember) => {
  if (
    !confirm(
      `Are you sure you want to remove access for "${staff.name}" (${staff.email})?`,
    )
  ) {
    return;
  }

  try {
    const res = await fetch(`/api/staff/${staff.id}`, {
      method: "DELETE",
      headers: {
        Accept: "application/json",
      },
    });

    if (res.ok) {
      await fetchStaff();
      showFeedback(`Staff member "${staff.name}" removed successfully.`);
    } else {
      const json = await res.json();
      showFeedback(json?.message || "Failed to delete staff member.");
    }
  } catch (e) {
    console.error("Error deleting staff member:", e);
  }
};

const showFeedback = (msg: string) => {
  savedFeedback.value = msg;
  setTimeout(() => {
    savedFeedback.value = "";
  }, 3500);
};
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs"
    >
      <div>
        <h1
          class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight"
        >
          Settings &amp; Store Configuration
        </h1>
        <p class="text-xs text-slate-500">
          Staff RBAC permissions, payment gateway APIs, logistics tax zones, and
          currency localization.
        </p>
      </div>

      <!-- Navigation Tabs -->
      <div
        class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200 overflow-x-auto"
      >
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
          :class="
            activeTab === 'staff'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'staff'"
        >
          👥 Staff &amp; RBAC
        </button>
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
          :class="
            activeTab === 'payments'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'payments'"
        >
          💳 Payment Gateways
        </button>
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
          :class="
            activeTab === 'shipping'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'shipping'"
        >
          🚚 Shipping &amp; Taxes
        </button>
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
          :class="
            activeTab === 'localization'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'localization'"
        >
          🌐 Localization
        </button>
      </div>
    </div>

    <!-- Alert -->
    <div
      v-if="savedFeedback"
      class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl animate-fade-in"
    >
      ✓ {{ savedFeedback }}
    </div>

    <!-- TAB 1: Staff & Role Based Access Control (RBAC) -->
    <div v-if="activeTab === 'staff'" class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Staff Role-Based Permissions
          </h2>
          <p class="text-xs text-slate-500">
            Configure access levels for administrators, inventory managers, and
            pack room fulfillment crews
          </p>
        </div>
        <button
          type="button"
          class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer flex items-center gap-1.5 transition-all"
          @click="isAddStaffOpen = true"
        >
          <span>+ Add Staff Member</span>
        </button>
      </div>

      <!-- Error alert if any -->
      <div
        v-if="staffError"
        class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl flex items-center justify-between animate-fade-in"
      >
        <span>{{ staffError }}</span>
        <button
          type="button"
          class="text-rose-500 hover:text-rose-700 text-xs font-bold cursor-pointer"
          @click="staffError = ''"
        >
          ✕
        </button>
      </div>

      <div
        class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden"
      >
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr
                class="bg-slate-50 border-b border-slate-200/80 text-slate-500 uppercase font-bold text-[10px] tracking-wider"
              >
                <th class="p-4">Staff Member</th>
                <th class="p-4">Assigned Role</th>
                <th class="p-4">Active Permissions Granted</th>
                <th class="p-4">Account Status</th>
                <th class="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <!-- Loading State -->
              <tr v-if="isLoadingStaff">
                <td colspan="5" class="p-8 text-center text-slate-400">
                  <div class="flex items-center justify-center gap-2">
                    <span
                      class="w-4 h-4 border-2 border-slate-400 border-t-transparent rounded-full animate-spin"
                    ></span>
                    <span class="text-xs font-semibold"
                      >Loading staff from database...</span
                    >
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr
                v-else-if="
                  !adminStore.staffMembers ||
                  adminStore.staffMembers.length === 0
                "
              >
                <td colspan="5" class="p-8 text-center text-slate-400">
                  <p class="font-bold text-slate-600 text-xs">
                    No staff members found in database
                  </p>
                  <p class="text-[11px] text-slate-400 mt-1">
                    Click "+ Add Staff Member" to create your first staff
                    account.
                  </p>
                </td>
              </tr>

              <!-- Staff Rows -->
              <tr
                v-for="staff in adminStore.staffMembers"
                :key="staff.id"
                class="hover:bg-slate-50/70 transition-colors"
              >
                <!-- Column 1: Staff Member -->
                <td class="p-4">
                  <div class="flex items-center gap-3">
                    <div
                      class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center flex-shrink-0"
                    >
                      {{ (staff.name || "S").charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <span
                        class="font-bold text-slate-900 block leading-tight"
                      >
                        {{ staff.name }}
                      </span>
                      <span class="text-[11px] text-slate-400 font-mono block">
                        {{ staff.email }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Column 2: Assigned Role -->
                <td class="p-4">
                  <span
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block"
                    :class="
                      staff.role === 'Super Admin' || staff.role === 'Admin'
                        ? 'bg-rose-50 text-rose-700 border-rose-200'
                        : staff.role === 'Store Manager'
                          ? 'bg-indigo-50 text-indigo-700 border-indigo-200'
                          : 'bg-amber-50 text-amber-700 border-amber-200'
                    "
                  >
                    {{ staff.roleLabel || staff.role }}
                  </span>
                </td>

                <!-- Column 3: Active Permissions Granted -->
                <td class="p-4">
                  <div class="flex flex-wrap gap-1 max-w-md">
                    <span
                      v-for="(perm, i) in staff.permissions"
                      :key="i"
                      class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-medium border border-slate-200/60"
                    >
                      {{ perm }}
                    </span>
                  </div>
                </td>

                <!-- Column 4: Account Status -->
                <td class="p-4">
                  <span
                    v-if="staff.isActive"
                    class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded inline-flex items-center gap-1"
                  >
                    <span
                      class="w-1.5 h-1.5 rounded-full bg-emerald-500"
                    ></span>
                    Active
                  </span>
                  <span
                    v-else
                    class="text-[10px] font-bold text-slate-500 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded inline-flex items-center gap-1"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                    Inactive
                  </span>
                </td>

                <!-- Column 5: Actions -->
                <td class="p-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      type="button"
                      class="text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                      @click="openEditStaff(staff)"
                    >
                      Edit Role
                    </button>
                    <button
                      type="button"
                      class="text-xs font-bold text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded-lg transition-colors cursor-pointer"
                      title="Remove Staff Member"
                      @click="deleteStaff(staff)"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 2: Payment Gateways -->
    <div v-else-if="activeTab === 'payments'" class="space-y-4">
      <div
        class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6"
      >
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Payment Processor Gateways
          </h2>
          <p class="text-xs text-slate-500">
            Enable or disable localized Philippine and international payment
            methods
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- GCash -->
          <div
            class="p-5 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-between"
          >
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-xl">📱</span>
                <h4 class="font-bold text-slate-900 text-sm">
                  GCash QR &amp; Wallet
                </h4>
              </div>
              <p class="text-xs text-slate-500">
                Direct mobile checkout for Philippine collectors
              </p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                v-model="adminStore.settings.gateways.gcash"
                type="checkbox"
                class="sr-only peer"
              />
              <div
                class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"
              ></div>
            </label>
          </div>

          <!-- Maya -->
          <div
            class="p-5 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-between"
          >
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-xl">💳</span>
                <h4 class="font-bold text-slate-900 text-sm">
                  Maya (PayMaya) Checkout
                </h4>
              </div>
              <p class="text-xs text-slate-500">
                Digital wallet and direct credit/debit card gateway
              </p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                v-model="adminStore.settings.gateways.maya"
                type="checkbox"
                class="sr-only peer"
              />
              <div
                class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"
              ></div>
            </label>
          </div>

          <!-- Stripe -->
          <div
            class="p-5 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-between"
          >
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-xl">🌐</span>
                <h4 class="font-bold text-slate-900 text-sm">
                  Stripe International
                </h4>
              </div>
              <p class="text-xs text-slate-500">
                Visa, Mastercard, AMEX, and Apple Pay
              </p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                v-model="adminStore.settings.gateways.stripe"
                type="checkbox"
                class="sr-only peer"
              />
              <div
                class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"
              ></div>
            </label>
          </div>

          <!-- COD -->
          <div
            class="p-5 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-between"
          >
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-xl">💵</span>
                <h4 class="font-bold text-slate-900 text-sm">
                  Cash on Delivery (COD)
                </h4>
              </div>
              <p class="text-xs text-slate-500">
                Pay directly to accredited logistics courier
              </p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                v-model="adminStore.settings.gateways.cod"
                type="checkbox"
                class="sr-only peer"
              />
              <div
                class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"
              ></div>
            </label>
          </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
          <button
            type="button"
            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer"
            @click="showFeedback('Payment gateway configurations updated!')"
          >
            Save Gateway Settings
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 3: Shipping & Tax Zones -->
    <div v-else-if="activeTab === 'shipping'" class="space-y-4">
      <div
        class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6"
      >
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Shipping Rates &amp; Value-Added Tax (VAT)
          </h2>
          <p class="text-xs text-slate-500">
            Configure logistics rate cards, free delivery threshold, and tax
            calculations
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Standard Nationwide Shipping (PHP)</label
            >
            <input
              v-model.number="adminStore.settings.flatShippingRate"
              type="number"
              class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 font-bold focus:outline-none focus:border-slate-800"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Free Shipping Order Threshold (PHP)</label
            >
            <input
              v-model.number="adminStore.settings.freeShippingThreshold"
              type="number"
              class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 font-bold focus:outline-none focus:border-slate-800"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Philippine VAT Percentage (%)</label
            >
            <input
              v-model.number="adminStore.settings.taxRatePercent"
              type="number"
              class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 font-bold focus:outline-none focus:border-slate-800"
            />
          </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
          <button
            type="button"
            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer"
            @click="
              showFeedback(
                'Logistics and tax calculations successfully updated!',
              )
            "
          >
            Save Logistics &amp; Tax Rules
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 4: Store Localization -->
    <div v-else class="space-y-4">
      <div
        class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6"
      >
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Store Localization &amp; Formats
          </h2>
          <p class="text-xs text-slate-500">
            Manage base transaction currency, default time zones, and date
            conventions
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Store Legal Name</label
            >
            <input
              v-model="adminStore.settings.storeName"
              type="text"
              class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 font-bold focus:outline-none focus:border-slate-800"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Default Store Currency</label
            >
            <select
              v-model="adminStore.settings.currency"
              class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 font-bold focus:outline-none focus:border-slate-800"
            >
              <option value="PHP" class="text-slate-900 bg-white">
                Philippine Peso (PHP ₱)
              </option>
              <option value="USD" class="text-slate-900 bg-white">
                US Dollar (USD $)
              </option>
              <option value="JPY" class="text-slate-900 bg-white">
                Japanese Yen (JPY ¥)
              </option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Timezone</label>
            <input
              type="text"
              value="Asia/Manila (GMT+8)"
              disabled
              class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 font-mono"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Weight Unit</label
            >
            <input
              type="text"
              value="Kilograms (kg) &bull; Grams (g)"
              disabled
              class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 font-mono"
            />
          </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
          <button
            type="button"
            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer"
            @click="showFeedback('Store localization parameters saved!')"
          >
            Save Localization Settings
          </button>
        </div>
      </div>
    </div>

    <!-- Add Staff Modal -->
    <teleport to="body">
      <div
        v-if="isAddStaffOpen"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
      >
        <div
          class="bg-white text-slate-900 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display [color-scheme:light]"
        >
          <div
            class="flex items-center justify-between pb-3 border-b border-slate-100"
          >
            <div>
              <h3 class="text-base font-bold text-slate-900">
                Add Staff Account
              </h3>
              <p class="text-xs text-slate-500">Assign role and permissions</p>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center cursor-pointer transition-colors"
              @click="isAddStaffOpen = false"
            >
              ✕
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Full Name</label
              >
              <input
                v-model="newStaffName"
                type="text"
                placeholder="e.g. Gabriel Santos"
                class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-medium focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 [color-scheme:light]"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Work Email</label
              >
              <input
                v-model="newStaffEmail"
                type="email"
                placeholder="staff@rlghobby.com"
                class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-medium focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 [color-scheme:light]"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Role Designation</label
              >
              <select
                v-model="newStaffRoleId"
                class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 text-xs font-semibold focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 [color-scheme:light]"
              >
                <option
                  v-for="role in staffRoles"
                  :key="role.id"
                  :value="role.id"
                  class="text-slate-900 bg-white font-medium"
                >
                  {{ role.label || role.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Security Password</label
              >
              <div class="relative">
                <input
                  v-model="newStaffPassword"
                  :type="showNewStaffPassword ? 'text' : 'password'"
                  placeholder="Set login password (default: AdminPass2026!)"
                  class="w-full p-2.5 pr-9 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-medium focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 [color-scheme:light]"
                />
                <button
                  type="button"
                  class="absolute right-2.5 top-2.5 text-slate-500 hover:text-slate-700 text-xs cursor-pointer"
                  @click="showNewStaffPassword = !showNewStaffPassword"
                >
                  {{ showNewStaffPassword ? "🙈" : "👁️" }}
                </button>
              </div>
              <p class="text-[11px] text-slate-600 font-medium mt-1">
                Enter a custom password or leave blank for default (<span
                  class="font-mono text-slate-900 font-bold"
                  >AdminPass2026!</span
                >).
              </p>
            </div>
          </div>

          <div class="flex gap-3 pt-3">
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer transition-colors"
              @click="isAddStaffOpen = false"
            >
              Cancel
            </button>
            <button
              type="button"
              :disabled="isSubmittingStaff"
              class="flex-1 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white font-bold text-xs transition-all cursor-pointer flex items-center justify-center gap-1.5"
              @click="addStaff"
            >
              <span
                v-if="isSubmittingStaff"
                class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"
              ></span>
              <span>{{
                isSubmittingStaff ? "Creating..." : "Create Staff Access"
              }}</span>
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Edit Staff Modal -->
    <teleport to="body">
      <div
        v-if="isEditStaffOpen && editingStaff"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
      >
        <div
          class="bg-white text-slate-900 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display animate-scale-up [color-scheme:light]"
        >
          <div
            class="flex items-center justify-between pb-3 border-b border-slate-100"
          >
            <div>
              <h3 class="text-base font-bold text-slate-900">
                Edit Staff Permissions
              </h3>
              <p class="text-xs text-slate-500">
                Update role and account status for {{ editingStaff.name }}
              </p>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center cursor-pointer transition-colors"
              @click="isEditStaffOpen = false"
            >
              ✕
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <div
              class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-0.5"
            >
              <span class="text-[10px] font-bold uppercase text-slate-500"
                >Staff Member</span
              >
              <p class="font-bold text-slate-900 text-xs">
                {{ editingStaff.name }}
              </p>
              <p class="text-[11px] text-slate-600 font-mono">
                {{ editingStaff.email }}
              </p>
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Role Designation</label
              >
              <select
                v-model="editStaffRoleId"
                class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 text-xs font-semibold focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 [color-scheme:light]"
              >
                <option
                  v-for="role in staffRoles"
                  :key="role.id"
                  :value="role.id"
                  class="text-slate-900 bg-white font-medium"
                >
                  {{ role.label || role.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Account Status</label
              >
              <div class="flex items-center gap-4 pt-1">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                  <input
                    v-model="editStaffActive"
                    type="radio"
                    :value="true"
                    class="accent-emerald-600"
                  />
                  <span class="font-bold text-emerald-700">Active</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                  <input
                    v-model="editStaffActive"
                    type="radio"
                    :value="false"
                    class="accent-slate-600"
                  />
                  <span class="font-bold text-slate-600">Inactive</span>
                </label>
              </div>
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Update Password</label
              >
              <div class="relative">
                <input
                  v-model="editStaffPassword"
                  :type="showEditStaffPassword ? 'text' : 'password'"
                  placeholder="Leave blank to keep current password"
                  class="w-full p-2.5 pr-9 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-medium focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 [color-scheme:light]"
                />
                <button
                  type="button"
                  class="absolute right-2.5 top-2.5 text-slate-500 hover:text-slate-700 text-xs cursor-pointer"
                  @click="showEditStaffPassword = !showEditStaffPassword"
                >
                  {{ showEditStaffPassword ? "🙈" : "👁️" }}
                </button>
              </div>
              <p class="text-[11px] text-slate-600 font-medium mt-1">
                Enter a new password to reset, or leave blank to keep unchanged.
              </p>
            </div>
          </div>

          <div class="flex gap-3 pt-3">
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer transition-colors"
              @click="isEditStaffOpen = false"
            >
              Cancel
            </button>
            <button
              type="button"
              :disabled="isUpdatingStaff"
              class="flex-1 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white font-bold text-xs transition-all cursor-pointer flex items-center justify-center gap-1.5"
              @click="saveEditStaff"
            >
              <span
                v-if="isUpdatingStaff"
                class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"
              ></span>
              <span>{{ isUpdatingStaff ? "Saving..." : "Save Changes" }}</span>
            </button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>
