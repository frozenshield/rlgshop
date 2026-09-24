<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

onMounted(() => {
  const token = route.query.token as string
  if (token) {
    localStorage.setItem('auth_token', token)
    router.push('/')
  } else {
    router.push('/login?error=auth_failed')
  }
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center font-display bg-amber-50/30">
    <div class="text-center space-y-3 p-8 bg-white rounded-3xl border border-amber-200/80 shadow-md max-w-sm">
      <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-2xl mx-auto animate-bounce">
        🔴
      </div>
      <h3 class="text-base font-extrabold text-slate-900">Connecting Trainer Account</h3>
      <p class="text-xs text-slate-500 font-medium">Signing you in, please wait...</p>
    </div>
  </div>
</template>
