<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useAdminStore } from "../admin.store";
import type { StaffMember } from "../admin.types";

const adminStore = useAdminStore();

// ─── Payment Gateways ────────────────────────────────────────────────────────
interface PaymentMethod {
  id: number;
  name: string;
  code: string;
  label: string;
  status: "active" | "inactive";
  description: string | null;
  icon: string | null;
}

const paymentMethods = ref<PaymentMethod[]>([]);
const isLoadingPayments = ref(false);
const isSavingPayments = ref(false);
const pendingStatuses = ref<Record<number, "active" | "inactive">>({});
const paymentError = ref("");

// Map PrimeIcons / codes → friendly emoji
const gatewayEmoji: Record<string, string> = {
  gcash: "📱",
  stripe: "🌐",
  paypal: "🅿️",
  paymaya: "💳",
  visa_master_card: "💳",
  cod: "💵",
};

const gatewayEmoji2: Record<string, string> = {
  "pi pi-mobile": "📱",
  "pi pi-credit-card": "💳",
  "pi pi-paypal": "🅿️",
  "pi pi-wallet": "💳",
  "pi pi-money-bill": "💵",
};

function getGatewayIcon(method: PaymentMethod): string {
  return (
    gatewayEmoji[method.code] ||
    (method.icon ? gatewayEmoji2[method.icon] : null) ||
    "💳"
  );
}

const fetchPaymentMethods = async () => {
  isLoadingPayments.value = true;
  paymentError.value = "";
  try {
    const res = await fetch("/api/ref-payment-methods?status=all");
    if (!res.ok) throw new Error("Failed to load payment methods");
    const data: PaymentMethod[] = await res.json();
    paymentMethods.value = data;
    // Seed pending statuses mirror from current DB values
    pendingStatuses.value = {};
    data.forEach((m) => {
      pendingStatuses.value[m.id] = m.status;
    });
  } catch (e) {
    console.error("fetchPaymentMethods error:", e);
    paymentError.value = "Could not load payment methods from server.";
  } finally {
    isLoadingPayments.value = false;
  }
};

const saveGatewaySettings = async () => {
  isSavingPayments.value = true;
  paymentError.value = "";
  try {
    // Only PATCH methods whose pending status differs from the loaded status
    const changed = paymentMethods.value.filter(
      (m) => pendingStatuses.value[m.id] !== m.status,
    );

    await Promise.all(
      changed.map((m) =>
        fetch(`/api/ref-payment-methods/${m.id}/status`, {
          method: "PATCH",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify({ status: pendingStatuses.value[m.id] }),
        }),
      ),
    );

    // Refresh from server to confirm persisted values
    await fetchPaymentMethods();
    showFeedback("Payment gateway configurations saved successfully!");
  } catch (e) {
    console.error("saveGatewaySettings error:", e);
    paymentError.value = "Failed to save gateway settings. Please try again.";
  } finally {
    isSavingPayments.value = false;
  }
};

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

// ─── Shipping & Tax Rules (Multi-Row & Toggles) ───────────────────────────
interface ShippingTaxRule {
  id: number;
  name: string;
  standard_shipping_fee: number | string;
  free_shipping_threshold: number | string;
  vat_percentage: number | string;
  is_active: boolean;
}

const shippingRules = ref<ShippingTaxRule[]>([]);
const isLoadingShipping = ref(false);
const shippingError = ref("");

// Add Rule Modal State
const isAddRuleOpen = ref(false);
const newRuleName = ref("Standard Logistics & Philippine VAT");
const newRuleFee = ref<number>(100);
const newRuleThreshold = ref<number>(2500);
const newRuleVat = ref<number>(12);
const newRuleActive = ref(true);
const isSubmittingNewRule = ref(false);

// Edit Rule Modal State
const isEditRuleOpen = ref(false);
const editingRule = ref<ShippingTaxRule | null>(null);
const editRuleName = ref("");
const editRuleFee = ref<number>(100);
const editRuleThreshold = ref<number>(2500);
const editRuleVat = ref<number>(12);
const editRuleActive = ref(true);
const isUpdatingRule = ref(false);

const fetchShippingRules = async () => {
  isLoadingShipping.value = true;
  shippingError.value = "";
  try {
    const res = await fetch("/api/shipping-tax?all=1");
    if (!res.ok) throw new Error("Failed to load shipping and tax rules");
    const json = await res.json();
    if (json.success && Array.isArray(json.data)) {
      shippingRules.value = json.data;

      // Sync active rule with adminStore.settings for store-wide backward compatibility
      const activeRule = json.data.find((r: ShippingTaxRule) => r.is_active);
      if (activeRule) {
        adminStore.settings.flatShippingRate = Number(activeRule.standard_shipping_fee);
        adminStore.settings.freeShippingThreshold = Number(activeRule.free_shipping_threshold);
        adminStore.settings.taxRatePercent = Number(activeRule.vat_percentage);
      }
    }
  } catch (e) {
    console.error("fetchShippingRules error:", e);
    shippingError.value = "Could not load shipping & tax rules from server.";
  } finally {
    isLoadingShipping.value = false;
  }
};

const toggleRuleStatus = async (rule: ShippingTaxRule) => {
  try {
    const res = await fetch(`/api/shipping-tax/${rule.id}/toggle`, {
      method: "PATCH",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
    });
    if (!res.ok) throw new Error("Failed to toggle status");
    const json = await res.json();
    if (json.success && json.data) {
      rule.is_active = Boolean(json.data.is_active);
      showFeedback(`"${rule.name}" is now ${rule.is_active ? "Active" : "Inactive"}.`);
      await fetchShippingRules();
    }
  } catch (e) {
    console.error("toggleRuleStatus error:", e);
    showFeedback("Failed to update status. Please try again.");
  }
};

const addShippingRule = async () => {
  shippingError.value = "";
  if (!newRuleName.value.trim()) {
    shippingError.value = "Please provide a name/label for this rule.";
    return;
  }

  isSubmittingNewRule.value = true;
  try {
    const payload = {
      name: newRuleName.value.trim(),
      standard_shipping_fee: Number(newRuleFee.value) || 0,
      free_shipping_threshold: Number(newRuleThreshold.value) || 0,
      vat_percentage: Number(newRuleVat.value) || 0,
      is_active: newRuleActive.value,
    };

    const res = await fetch("/api/shipping-tax", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });

    const json = await res.json();
    if (!res.ok) {
      shippingError.value = json?.message || "Failed to create rule.";
      return;
    }

    await fetchShippingRules();
    isAddRuleOpen.value = false;
    newRuleName.value = "";
    newRuleFee.value = 100;
    newRuleThreshold.value = 2500;
    newRuleVat.value = 12;
    newRuleActive.value = true;
    showFeedback(`New shipping fee & VAT rule "${json.data?.name}" created successfully!`);
  } catch (e) {
    console.error("addShippingRule error:", e);
    shippingError.value = "Server error while creating shipping rule.";
  } finally {
    isSubmittingNewRule.value = false;
  }
};

const openEditRule = (rule: ShippingTaxRule) => {
  editingRule.value = rule;
  editRuleName.value = rule.name;
  editRuleFee.value = Number(rule.standard_shipping_fee);
  editRuleThreshold.value = Number(rule.free_shipping_threshold);
  editRuleVat.value = Number(rule.vat_percentage);
  editRuleActive.value = Boolean(rule.is_active);
  isEditRuleOpen.value = true;
};

const saveEditRule = async () => {
  if (!editingRule.value) return;
  isUpdatingRule.value = true;
  shippingError.value = "";
  try {
    const payload = {
      name: editRuleName.value.trim(),
      standard_shipping_fee: Number(editRuleFee.value) || 0,
      free_shipping_threshold: Number(editRuleThreshold.value) || 0,
      vat_percentage: Number(editRuleVat.value) || 0,
      is_active: editRuleActive.value,
    };

    const res = await fetch(`/api/shipping-tax/${editingRule.value.id}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });

    const json = await res.json();
    if (res.ok) {
      await fetchShippingRules();
      isEditRuleOpen.value = false;
      showFeedback(`Shipping & VAT rule "${payload.name}" updated successfully!`);
    } else {
      shippingError.value = json?.message || "Failed to update rule.";
    }
  } catch (e) {
    console.error("saveEditRule error:", e);
    shippingError.value = "Failed to update shipping rule.";
  } finally {
    isUpdatingRule.value = false;
  }
};

const deleteRule = async (rule: ShippingTaxRule) => {
  if (!confirm(`Are you sure you want to remove the shipping & tax rule "${rule.name}"?`)) {
    return;
  }

  try {
    const res = await fetch(`/api/shipping-tax/${rule.id}`, {
      method: "DELETE",
      headers: {
        Accept: "application/json",
      },
    });

    if (res.ok) {
      await fetchShippingRules();
      showFeedback(`Rule "${rule.name}" removed successfully.`);
    } else {
      const json = await res.json();
      showFeedback(json?.message || "Failed to delete rule.");
    }
  } catch (e) {
    console.error("deleteRule error:", e);
  }
};

// ─── Store Localization & Currencies / Weight Units ───────────────────────
interface CurrencyItem {
  id: number;
  code: string;
  name: string;
  symbol: string;
  label: string;
  exchange_rate: number | string;
  is_active: boolean;
}

interface WeightUnitItem {
  id: number;
  code: string;
  name: string;
  symbol: string;
  system: string;
  is_active: boolean;
}

interface LocalizationSettings {
  id: number;
  store_legal_name: string;
  brand_logo_url: string | null;
  brand_logo_title: string;
  brand_logo_subtitle: string;
  ref_currency_id: number | null;
  currency_code: string;
  timezone: string;
  ref_weight_unit_id: number | null;
  weight_unit_code: string;
  date_format: string;
}

const currencies = ref<CurrencyItem[]>([]);
const weightUnits = ref<WeightUnitItem[]>([]);
const localization = ref<LocalizationSettings>({
  id: 1,
  store_legal_name: "RLG Hobby Shop",
  brand_logo_url: "/logo.png",
  brand_logo_title: "RLG Online Shop",
  brand_logo_subtitle: "Storefront, Admin & Favicon",
  ref_currency_id: 1,
  currency_code: "PHP",
  timezone: "Asia/Manila (GMT+8)",
  ref_weight_unit_id: 1,
  weight_unit_code: "kg_g",
  date_format: "YYYY-MM-DD",
});
const isLoadingLocalization = ref(false);
const isSavingLocalization = ref(false);
const localizationError = ref("");

const fetchCurrencies = async () => {
  try {
    const res = await fetch("/api/ref-currencies");
    if (res.ok) {
      const json = await res.json();
      if (json.success && Array.isArray(json.data)) {
        currencies.value = json.data;
      }
    }
  } catch (e) {
    console.error("Failed to load currencies", e);
  }
};

const fetchWeightUnits = async () => {
  try {
    const res = await fetch("/api/ref-weight-units");
    if (res.ok) {
      const json = await res.json();
      if (json.success && Array.isArray(json.data)) {
        weightUnits.value = json.data;
      }
    }
  } catch (e) {
    console.error("Failed to load weight units", e);
  }
};

const fetchLocalization = async () => {
  isLoadingLocalization.value = true;
  localizationError.value = "";
  try {
    const res = await fetch("/api/localization");
    if (res.ok) {
      const json = await res.json();
      if (json.success && json.data) {
        localization.value = {
          ...localization.value,
          ...json.data,
        };
        adminStore.settings.storeName =
          json.data.store_legal_name || "RLG Hobby Shop";
        adminStore.settings.currency = json.data.currency_code || "PHP";
      }
    }
  } catch (e) {
    console.error("Failed to load store localization", e);
    localizationError.value =
      "Failed to load localization settings from server.";
  } finally {
    isLoadingLocalization.value = false;
  }
};

const saveLocalizationSettings = async () => {
  isSavingLocalization.value = true;
  localizationError.value = "";
  try {
    const payload = {
      store_legal_name: localization.value.store_legal_name,
      brand_logo_url: localization.value.brand_logo_url,
      brand_logo_title: localization.value.brand_logo_title,
      brand_logo_subtitle: localization.value.brand_logo_subtitle,
      currency_code: localization.value.currency_code,
      ref_currency_id: localization.value.ref_currency_id,
      timezone: localization.value.timezone,
      weight_unit_code: localization.value.weight_unit_code,
      ref_weight_unit_id: localization.value.ref_weight_unit_id,
      date_format: localization.value.date_format,
    };

    const res = await fetch("/api/localization", {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });

    const json = await res.json();
    if (res.ok && json.success) {
      localization.value = {
        ...localization.value,
        ...json.data,
      };
      adminStore.settings.storeName = json.data.store_legal_name;
      adminStore.settings.currency = json.data.currency_code;
      showFeedback("Store localization parameters saved successfully!");
    } else {
      localizationError.value =
        json?.message || "Failed to save localization settings.";
    }
  } catch (e) {
    console.error("saveLocalizationSettings error:", e);
    localizationError.value =
      "Server error while saving localization settings.";
  } finally {
    isSavingLocalization.value = false;
  }
};

onMounted(() => {
  fetchRoles();
  fetchStaff();
  fetchPaymentMethods();
  fetchShippingRules();
  fetchCurrencies();
  fetchWeightUnits();
  fetchLocalization();
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
      <!-- Section header -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Payment Processor Gateways
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">
            Enable or disable localized Philippine and international payment
            methods. Changes are saved directly to the database.
          </p>
        </div>
        <button
          type="button"
          class="p-2 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
          title="Refresh from server"
          :disabled="isLoadingPayments"
          @click="fetchPaymentMethods"
        >
          <svg
            class="w-4 h-4"
            :class="{ 'animate-spin': isLoadingPayments }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
        </button>
      </div>

      <!-- Error -->
      <div
        v-if="paymentError"
        class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl flex items-center justify-between"
      >
        <span>{{ paymentError }}</span>
        <button
          type="button"
          class="text-rose-500 hover:text-rose-700 cursor-pointer"
          @click="paymentError = ''"
        >
          ✕
        </button>
      </div>

      <!-- Loading skeleton -->
      <div
        v-if="isLoadingPayments"
        class="grid grid-cols-1 md:grid-cols-2 gap-4"
      >
        <div
          v-for="i in 6"
          :key="i"
          class="p-5 rounded-2xl border border-slate-200 bg-slate-50 animate-pulse h-24"
        />
      </div>

      <!-- Gateway cards grid -->
      <div
        v-else
        class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-5"
      >
        <!-- Status legend -->
        <div class="flex items-center gap-4 text-[11px] text-slate-500">
          <span class="flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block" />
            Active — customers can choose this at checkout
          </span>
          <span class="flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-slate-300 inline-block" />
            Inactive — hidden from checkout
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="method in paymentMethods"
            :key="method.id"
            class="group p-5 rounded-2xl border transition-all duration-200 flex items-center justify-between gap-4"
            :class="
              pendingStatuses[method.id] === 'active'
                ? 'border-emerald-200 bg-emerald-50/40'
                : 'border-slate-200 bg-slate-50/60'
            "
          >
            <!-- Left: icon + info -->
            <div class="flex items-center gap-3 min-w-0">
              <!-- Icon circle -->
              <div
                class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-xl transition-colors"
                :class="
                  pendingStatuses[method.id] === 'active'
                    ? 'bg-white border border-emerald-200 shadow-sm'
                    : 'bg-white border border-slate-200'
                "
              >
                {{ getGatewayIcon(method) }}
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <h4 class="font-bold text-slate-900 text-sm leading-tight">
                    {{ method.label }}
                  </h4>
                  <!-- Live status badge -->
                  <span
                    class="px-1.5 py-0.5 rounded text-[10px] font-bold border"
                    :class="
                      method.status === 'active'
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                        : 'bg-slate-100 text-slate-500 border-slate-200'
                    "
                  >
                    {{ method.status === "active" ? "✓ Live" : "Disabled" }}
                  </span>
                  <!-- Pending change indicator -->
                  <span
                    v-if="pendingStatuses[method.id] !== method.status"
                    class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                  >
                    ● Unsaved
                  </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5 leading-snug">
                  {{ method.description || "—" }}
                </p>
                <p class="text-[10px] text-slate-400 font-mono mt-0.5">
                  code: {{ method.code }}
                </p>
              </div>
            </div>

            <!-- Right: toggle -->
            <label
              class="relative inline-flex items-center cursor-pointer flex-shrink-0"
            >
              <input
                type="checkbox"
                class="sr-only peer"
                :checked="pendingStatuses[method.id] === 'active'"
                @change="
                  pendingStatuses[method.id] =
                    pendingStatuses[method.id] === 'active'
                      ? 'inactive'
                      : 'active'
                "
              />
              <div
                class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"
              />
            </label>
          </div>
        </div>

        <!-- Unsaved count summary -->
        <div
          v-if="paymentMethods.some((m) => pendingStatuses[m.id] !== m.status)"
          class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 font-semibold flex items-center gap-2"
        >
          <span class="text-base">⚠️</span>
          You have unsaved changes.
          {{
            paymentMethods.filter((m) => pendingStatuses[m.id] !== m.status)
              .length
          }}
          gateway(s) will be updated when you save.
        </div>

        <!-- Save button -->
        <div class="flex justify-end pt-2 border-t border-slate-100">
          <button
            type="button"
            class="px-5 py-2.5 font-bold text-xs rounded-xl shadow-xs cursor-pointer flex items-center gap-2 transition-all"
            :class="
              isSavingPayments
                ? 'bg-slate-400 text-white cursor-not-allowed'
                : 'bg-slate-900 hover:bg-slate-800 text-white'
            "
            :disabled="isSavingPayments"
            @click="saveGatewaySettings"
          >
            <span
              v-if="isSavingPayments"
              class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"
            />
            <span>{{
              isSavingPayments ? "Saving…" : "Save Gateway Settings"
            }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 3: Shipping & Tax Zones -->
    <div v-else-if="activeTab === 'shipping'" class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Shipping Rates &amp; Value-Added Tax (VAT) Rules
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">
            Configure logistics rate cards, free delivery thresholds, and Philippine VAT calculations.
            Toggle active/inactive per rule, or add another fee row.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="p-2 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
            title="Refresh from server"
            :disabled="isLoadingShipping"
            @click="fetchShippingRules"
          >
            <svg
              class="w-4 h-4"
              :class="{ 'animate-spin': isLoadingShipping }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
              />
            </svg>
          </button>
          <button
            type="button"
            class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer flex items-center gap-1.5 transition-all"
            @click="isAddRuleOpen = true"
          >
            <span>+ Add Fee / Tax Rule</span>
          </button>
        </div>
      </div>

      <!-- Error alert -->
      <div
        v-if="shippingError"
        class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl flex items-center justify-between animate-fade-in"
      >
        <span>{{ shippingError }}</span>
        <button
          type="button"
          class="text-rose-500 hover:text-rose-700 text-xs font-bold cursor-pointer"
          @click="shippingError = ''"
        >
          ✕
        </button>
      </div>

      <!-- Status legend -->
      <div class="flex items-center gap-4 text-[11px] text-slate-500">
        <span class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block" />
          Active — applied at customer checkout and order tax calculation
        </span>
        <span class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-slate-300 inline-block" />
          Inactive — paused / tax-exempt
        </span>
      </div>

      <!-- Shipping rules table -->
      <div
        class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden"
      >
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr
                class="bg-slate-50 border-b border-slate-200/80 text-slate-500 uppercase font-bold text-[10px] tracking-wider"
              >
                <th class="p-4">Fee / Rule Name</th>
                <th class="p-4">Standard Shipping (PHP)</th>
                <th class="p-4">Free Shipping Order Threshold (PHP)</th>
                <th class="p-4">Philippine VAT (%)</th>
                <th class="p-4 text-center">Status Toggle</th>
                <th class="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <!-- Loading -->
              <tr v-if="isLoadingShipping">
                <td colspan="6" class="p-8 text-center text-slate-400">
                  <div class="flex items-center justify-center gap-2">
                    <span
                      class="w-4 h-4 border-2 border-slate-400 border-t-transparent rounded-full animate-spin"
                    ></span>
                    <span class="text-xs font-semibold"
                      >Loading shipping &amp; tax rules...</span
                    >
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-else-if="shippingRules.length === 0">
                <td colspan="6" class="p-8 text-center text-slate-400">
                  <p class="font-bold text-slate-600 text-xs">
                    No shipping &amp; tax fee rules found
                  </p>
                  <p class="text-[11px] text-slate-400 mt-1">
                    Click "+ Add Fee / Tax Rule" to create a shipping &amp; tax calculation row.
                  </p>
                </td>
              </tr>

              <!-- Rule Rows -->
              <tr
                v-for="rule in shippingRules"
                :key="rule.id"
                class="hover:bg-slate-50/70 transition-colors"
                :class="rule.is_active ? 'bg-emerald-50/10' : ''"
              >
                <!-- Column 1: Rule Name -->
                <td class="p-4">
                  <div class="flex items-center gap-2.5">
                    <div
                      class="w-8 h-8 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-sm flex-shrink-0"
                    >
                      🚚
                    </div>
                    <div>
                      <span class="font-bold text-slate-900 block leading-tight">
                        {{ rule.name }}
                      </span>
                      <span class="text-[10px] text-slate-400 font-mono">
                        Rule ID: #{{ rule.id }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Column 2: Standard Shipping Fee -->
                <td class="p-4">
                  <span class="font-bold text-slate-900 font-mono text-xs">
                    ₱{{ Number(rule.standard_shipping_fee).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  </span>
                </td>

                <!-- Column 3: Free Shipping Threshold -->
                <td class="p-4">
                  <span class="font-bold text-slate-900 font-mono text-xs">
                    ₱{{ Number(rule.free_shipping_threshold).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  </span>
                </td>

                <!-- Column 4: Philippine VAT (%) -->
                <td class="p-4">
                  <span
                    class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border inline-flex items-center gap-1"
                    :class="
                      Number(rule.vat_percentage) > 0
                        ? 'bg-indigo-50 text-indigo-700 border-indigo-200'
                        : 'bg-slate-100 text-slate-500 border-slate-200'
                    "
                  >
                    <span>{{ Number(rule.vat_percentage).toFixed(2) }}%</span>
                    <span v-if="Number(rule.vat_percentage) === 12" class="text-[9px] font-extrabold uppercase">
                      (BIR 12%)
                    </span>
                  </span>
                </td>

                <!-- Column 5: Status Toggle -->
                <td class="p-4 text-center">
                  <div class="inline-flex items-center gap-2.5">
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input
                        type="checkbox"
                        class="sr-only peer"
                        :checked="rule.is_active"
                        @change="toggleRuleStatus(rule)"
                      />
                      <div
                        class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"
                      />
                    </label>
                    <span
                      class="text-[10px] font-bold px-2 py-0.5 rounded-full border"
                      :class="
                        rule.is_active
                          ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                          : 'bg-slate-100 text-slate-500 border-slate-200'
                      "
                    >
                      {{ rule.is_active ? "Active" : "Inactive" }}
                    </span>
                  </div>
                </td>

                <!-- Column 6: Actions -->
                <td class="p-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      type="button"
                      class="text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                      @click="openEditRule(rule)"
                    >
                      Edit Rule
                    </button>
                    <button
                      type="button"
                      class="text-xs font-bold text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded-lg transition-colors cursor-pointer"
                      title="Remove Rule"
                      @click="deleteRule(rule)"
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

    <!-- TAB 4: Store Localization -->
    <div v-else class="space-y-4">
      <div
        class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6"
      >
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
              Store Localization &amp; Formats
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
              Manage base transaction currency, default time zones, and weight conventions
            </p>
          </div>
          <button
            type="button"
            class="p-2 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
            title="Refresh from server"
            :disabled="isLoadingLocalization"
            @click="fetchLocalization"
          >
            <svg
              class="w-4 h-4"
              :class="{ 'animate-spin': isLoadingLocalization }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
              />
            </svg>
          </button>
        </div>

        <!-- Error alert if any -->
        <div
          v-if="localizationError"
          class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl flex items-center justify-between animate-fade-in"
        >
          <span>{{ localizationError }}</span>
          <button
            type="button"
            class="text-rose-500 hover:text-rose-700 text-xs font-bold cursor-pointer"
            @click="localizationError = ''"
          >
            ✕
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
          <!-- Active Brand Logo -->
          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Active Brand Logo</label
            >
            <div
              class="flex items-center gap-3 p-2 bg-slate-50 rounded-xl border border-slate-200 h-[42px]"
            >
              <img
                :src="localization.brand_logo_url || '/logo.png'"
                alt="Active Shop Logo"
                class="w-8 h-8 object-contain rounded-lg shadow-2xs"
              />
              <div class="text-[10px] text-slate-500 leading-tight">
                <span class="font-bold text-slate-800 block">
                  {{ localization.brand_logo_title || "RLG Online Shop" }}
                </span>
                <span>{{
                  localization.brand_logo_subtitle ||
                  "Storefront, Admin & Favicon"
                }}</span>
              </div>
            </div>
          </div>

          <!-- Store Legal Name -->
          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Store Legal Name</label
            >
            <input
              v-model="localization.store_legal_name"
              type="text"
              placeholder="e.g. RLG Hobby Shop"
              class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 font-bold focus:outline-none focus:border-slate-800"
            />
          </div>

          <!-- Default Store Currency -->
          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Default Store Currency</label
            >
            <select
              v-model="localization.currency_code"
              class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 font-bold focus:outline-none focus:border-slate-800 cursor-pointer"
            >
              <option
                v-for="curr in currencies"
                :key="curr.code"
                :value="curr.code"
                class="text-slate-900 bg-white"
              >
                {{ curr.label || `${curr.name} (${curr.code} ${curr.symbol})` }}
              </option>
            </select>
          </div>

          <!-- Timezone -->
          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Timezone</label
            >
            <select
              v-model="localization.timezone"
              class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 font-bold focus:outline-none focus:border-slate-800 cursor-pointer"
            >
              <option value="Asia/Manila (GMT+8)" class="text-slate-900 bg-white">
                Asia/Manila (GMT+8) — Philippines
              </option>
              <option value="Asia/Tokyo (GMT+9)" class="text-slate-900 bg-white">
                Asia/Tokyo (GMT+9) — Japan
              </option>
              <option value="Asia/Singapore (GMT+8)" class="text-slate-900 bg-white">
                Asia/Singapore (GMT+8) — Singapore
              </option>
              <option value="Asia/Hong_Kong (GMT+8)" class="text-slate-900 bg-white">
                Asia/Hong_Kong (GMT+8) — Hong Kong
              </option>
              <option value="Asia/Seoul (GMT+9)" class="text-slate-900 bg-white">
                Asia/Seoul (GMT+9) — South Korea
              </option>
              <option value="UTC (GMT+0)" class="text-slate-900 bg-white">
                UTC (GMT+0) — Universal Time
              </option>
              <option value="America/New_York (GMT-5)" class="text-slate-900 bg-white">
                America/New_York (GMT-5) — US Eastern
              </option>
              <option value="America/Los_Angeles (GMT-8)" class="text-slate-900 bg-white">
                America/Los_Angeles (GMT-8) — US Pacific
              </option>
              <option value="Europe/London (GMT+0)" class="text-slate-900 bg-white">
                Europe/London (GMT+0) — UK
              </option>
              <option value="Europe/Paris (GMT+1)" class="text-slate-900 bg-white">
                Europe/Paris (GMT+1) — Central Europe
              </option>
              <option value="Australia/Sydney (GMT+11)" class="text-slate-900 bg-white">
                Australia/Sydney (GMT+11) — Australia
              </option>
            </select>
          </div>

          <!-- Weight Unit -->
          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Weight Unit</label
            >
            <select
              v-model="localization.weight_unit_code"
              class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 font-bold focus:outline-none focus:border-slate-800 cursor-pointer"
            >
              <option
                v-for="unit in weightUnits"
                :key="unit.code"
                :value="unit.code"
                class="text-slate-900 bg-white"
              >
                {{ unit.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
          <button
            type="button"
            :disabled="isSavingLocalization"
            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer flex items-center gap-2 transition-all"
            @click="saveLocalizationSettings"
          >
            <span
              v-if="isSavingLocalization"
              class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"
            ></span>
            <span>{{
              isSavingLocalization
                ? "Saving..."
                : "Save Localization Settings"
            }}</span>
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

    <!-- Add Shipping & Tax Rule Modal -->
    <teleport to="body">
      <div
        v-if="isAddRuleOpen"
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
                Add Shipping &amp; Tax Fee Rule
              </h3>
              <p class="text-xs text-slate-500">
                Configure rate card, free threshold &amp; Philippine VAT
              </p>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center cursor-pointer transition-colors"
              @click="isAddRuleOpen = false"
            >
              ✕
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Fee / Rule Name</label
              >
              <input
                v-model="newRuleName"
                type="text"
                placeholder="e.g. Standard Nationwide Logistics &amp; 12% VAT"
                class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-medium focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 [color-scheme:light]"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Standard Nationwide Shipping Fee (PHP ₱)</label
              >
              <input
                v-model.number="newRuleFee"
                type="number"
                step="0.01"
                placeholder="100.00"
                class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-medium focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 [color-scheme:light]"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Free Shipping Order Threshold (PHP ₱)</label
              >
              <input
                v-model.number="newRuleThreshold"
                type="number"
                step="0.01"
                placeholder="2500.00"
                class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-medium focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 [color-scheme:light]"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Philippine VAT Percentage (% BIR Rate)</label
              >
              <input
                v-model.number="newRuleVat"
                type="number"
                step="0.01"
                placeholder="12.00"
                class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-medium focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 [color-scheme:light]"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Initial Status</label
              >
              <div class="flex items-center gap-4 pt-1">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                  <input
                    v-model="newRuleActive"
                    type="radio"
                    :value="true"
                    class="accent-emerald-600"
                  />
                  <span class="font-bold text-emerald-700">Active</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                  <input
                    v-model="newRuleActive"
                    type="radio"
                    :value="false"
                    class="accent-slate-600"
                  />
                  <span class="font-bold text-slate-600">Inactive</span>
                </label>
              </div>
            </div>
          </div>

          <div class="flex gap-3 pt-3">
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer transition-colors"
              @click="isAddRuleOpen = false"
            >
              Cancel
            </button>
            <button
              type="button"
              :disabled="isSubmittingNewRule"
              class="flex-1 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white font-bold text-xs transition-all cursor-pointer flex items-center justify-center gap-1.5"
              @click="addShippingRule"
            >
              <span
                v-if="isSubmittingNewRule"
                class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"
              ></span>
              <span>{{ isSubmittingNewRule ? "Saving..." : "Add Fee &amp; VAT Rule" }}</span>
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Edit Shipping & Tax Rule Modal -->
    <teleport to="body">
      <div
        v-if="isEditRuleOpen && editingRule"
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
                Edit Shipping &amp; Tax Rule
              </h3>
              <p class="text-xs text-slate-500">
                Update fees and tax calculation for Rule #{{ editingRule.id }}
              </p>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center cursor-pointer transition-colors"
              @click="isEditRuleOpen = false"
            >
              ✕
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Fee / Rule Name</label
              >
              <input
                v-model="editRuleName"
                type="text"
                class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-medium focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 [color-scheme:light]"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Standard Nationwide Shipping (PHP ₱)</label
              >
              <input
                v-model.number="editRuleFee"
                type="number"
                step="0.01"
                class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-medium focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 [color-scheme:light]"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Free Shipping Order Threshold (PHP ₱)</label
              >
              <input
                v-model.number="editRuleThreshold"
                type="number"
                step="0.01"
                class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-medium focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 [color-scheme:light]"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Philippine VAT Percentage (%)</label
              >
              <input
                v-model.number="editRuleVat"
                type="number"
                step="0.01"
                class="w-full p-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-medium focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 [color-scheme:light]"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1"
                >Status</label
              >
              <div class="flex items-center gap-4 pt-1">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                  <input
                    v-model="editRuleActive"
                    type="radio"
                    :value="true"
                    class="accent-emerald-600"
                  />
                  <span class="font-bold text-emerald-700">Active</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                  <input
                    v-model="editRuleActive"
                    type="radio"
                    :value="false"
                    class="accent-slate-600"
                  />
                  <span class="font-bold text-slate-600">Inactive</span>
                </label>
              </div>
            </div>
          </div>

          <div class="flex gap-3 pt-3">
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer transition-colors"
              @click="isEditRuleOpen = false"
            >
              Cancel
            </button>
            <button
              type="button"
              :disabled="isUpdatingRule"
              class="flex-1 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white font-bold text-xs transition-all cursor-pointer flex items-center justify-center gap-1.5"
              @click="saveEditRule"
            >
              <span
                v-if="isUpdatingRule"
                class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"
              ></span>
              <span>{{ isUpdatingRule ? "Saving..." : "Save Changes" }}</span>
            </button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>
