import './styles/index.scss'
import { createApp } from 'vue'
import Cookies from 'js-cookie'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import router from './router'
import i18n from './lang' // Internationalization
import App from './views/App.vue'
import './permission' // permission control
import 'bootstrap-icons/font/bootstrap-icons.scss'
import Icon from './components/Icon/Icon.vue'
import VueApexCharts from "vue3-apexcharts"; // ✅ Import ApexCharts

// Import all Element Plus icons
import * as Icons from '@element-plus/icons-vue'

// ✅ Import CASL Ability
import { Ability } from '@casl/ability'
import { abilitiesPlugin } from '@casl/vue'
import { defineAbilitiesFor } from '@/utils/ability'

// ✅ Initialize CASL Ability
const ability = new Ability([]) // Start with empty rules

const app = createApp(App)




app.use(i18n)

app.config.globalProperties.productionTip = false

app.use(ElementPlus, {
  size: Cookies.get('size') || 'medium', // set element-plus default size
  i18n: (key, value) => i18n.t(key, value)
})


app.use(abilitiesPlugin, ability) // ✅ Register CASL
app.config.globalProperties.$ability = ability // ✅ Provide CASL for Options API
app.provide('ability', ability) // ✅ Provide CASL for Composition API


// pinia
import { createPinia } from 'pinia'
app.use(createPinia())

// element svg icon
import ElSvgIcon from '@/components/ElSvgIcon.vue'

app.component('ElSvgIcon', ElSvgIcon)

app.component('Icon', Icon)

// Register all icons globally
for (const [key, component] of Object.entries(Icons)) {
  app.component(key, component)
}

// ✅ Register ApexCharts globally
app.component("apexchart", VueApexCharts);




app.use(router).mount('#app')
