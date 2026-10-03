import './assets/tailwind/index.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import dompurifyPlugin from './plugins/dompurify'

import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import 'primeicons/primeicons.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(dompurifyPlugin)
app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      darkModeSelector: '.dark',
    },
  },
})

app.mount('#app')
