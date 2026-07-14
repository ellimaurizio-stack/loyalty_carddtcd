<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
    store: Object,
    customer: Object,
    pwaSettings: Object,
    rewards: Array,
    loyaltyProgram: Object,
});

const deferredPrompt = ref(null);
const showInstallBtn = ref(false);

onMounted(() => {
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent the mini-infobar from appearing on mobile
        e.preventDefault();
        // Stash the event so it can be triggered later.
        deferredPrompt.value = e;
        // Update UI notify the user they can install the PWA
        showInstallBtn.value = true;
    });
});

const installPwa = async () => {
    if (deferredPrompt.value) {
        deferredPrompt.value.prompt();
        const { outcome } = await deferredPrompt.value.userChoice;
        if (outcome === 'accepted') {
            console.log('User accepted the install prompt');
            showInstallBtn.value = false;
        }
        deferredPrompt.value = null;
    }
};

const bg = computed(() => props.pwaSettings?.background_color || '#f3f4f6');
const bgImage = computed(() => props.pwaSettings?.background_image_path ? `url(/storage/${props.pwaSettings.background_image_path})` : 'none');
const primary = computed(() => props.pwaSettings?.primary_color || '#4f46e5');
const text = computed(() => props.pwaSettings?.text_color || '#111827');
const logo = computed(() => props.pwaSettings?.logo_path ? `/storage/${props.pwaSettings.logo_path}` : null);
const name = computed(() => props.pwaSettings?.app_name || 'Loyalty App');
const cardColor = computed(() => props.pwaSettings?.card_color || '#4f46e5');
const cardTextColor = computed(() => props.pwaSettings?.card_text_color || '#ffffff');

const qrValue = computed(() => props.customer.card_identifier);
</script>

<template>
    <Head title="Loyalty Dashboard" />
    <div class="min-h-screen pb-20 bg-cover bg-center bg-no-repeat" :style="{ backgroundColor: bg, backgroundImage: bgImage, color: text }">
        
        <!-- Header -->
        <div class="pt-8 px-6 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <img v-if="logo" :src="logo" class="h-10 w-10 rounded-full object-cover shadow-sm">
                <div v-else class="h-10 w-10 rounded-full bg-gray-200 shadow-sm flex items-center justify-center">
                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <div class="font-bold text-xl">{{ name }}</div>
            </div>
            <Link :href="route('pwa.logout', {store: store.slug})" method="post" as="button" class="text-sm opacity-60 hover:opacity-100 font-medium">Esci</Link>
        </div>

        <div class="px-6 mt-6">
            <h1 class="text-2xl font-bold mb-1">Ciao, {{ customer.name || customer.email }}</h1>
            <p class="opacity-70 mb-6 text-sm">Ecco il tuo riepilogo fedeltà</p>
            
            <!-- Virtual Card -->
            <div class="w-full rounded-3xl p-6 shadow-xl relative overflow-hidden" :style="{ backgroundColor: cardColor, color: cardTextColor }">
                <!-- Decorative circle -->
                <div class="absolute -bottom-16 -right-16 w-48 h-48 rounded-full bg-white opacity-10"></div>
                
                <div class="flex justify-between items-start relative z-10">
                    <div>
                        <div class="font-bold tracking-widest text-xs opacity-80 uppercase">Carta Fedeltà</div>
                        <div class="text-4xl font-black mt-2">{{ customer.loyalty_points }} <span class="text-lg opacity-80 font-medium">PT</span></div>
                        <div v-if="customer.cashback_balance > 0" class="text-lg font-bold mt-1 opacity-90">€ {{ customer.cashback_balance }} cashback</div>
                    </div>
                    <div class="bg-white p-2 rounded-xl shadow-inner">
                        <qrcode-vue :value="qrValue" :size="70" level="M" />
                    </div>
                </div>
            <div class="mt-8 flex justify-between items-end relative z-10">
                    <div class="text-sm font-mono tracking-widest opacity-90">{{ customer.card_identifier }}</div>
                </div>
            </div>
            
            <!-- Install App Button -->
            <button v-if="showInstallBtn" @click="installPwa" class="mt-6 w-full h-14 rounded-2xl flex items-center justify-center space-x-3 text-white font-bold text-lg shadow-lg active:scale-95 transition-transform" :style="{ backgroundColor: primary }">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Installa App sul Telefono</span>
            </button>
            
            <!-- Add to Wallet Buttons (Opzione C - Predisposizione) -->
            <div class="mt-6 space-y-3">
                <button class="w-full h-14 bg-black rounded-2xl flex items-center justify-center space-x-3 text-white opacity-40 cursor-not-allowed transition-transform active:scale-95" disabled>
                    <svg class="h-6 w-6" viewBox="0 0 384 512" fill="currentColor"><path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/></svg>
                    <span class="font-semibold text-[17px]">Aggiungi a Apple Wallet</span>
                </button>
                <button class="w-full h-14 bg-black rounded-2xl flex items-center justify-center space-x-3 text-white opacity-40 cursor-not-allowed transition-transform active:scale-95" disabled>
                    <span class="font-bold text-xl tracking-tight">G Pay</span>
                    <span class="text-gray-400 font-light">|</span>
                    <span class="font-medium text-[16px]">Aggiungi a Google Wallet</span>
                </button>
                <p class="text-xs text-center opacity-50 mt-2">La funzione Wallet nativo sarà sbloccata a breve!</p>
            </div>

            <!-- Rewards Catalog -->
            <div class="mt-8">
                <h3 class="font-bold text-lg mb-4">Premi Disponibili</h3>
                
                <div v-if="rewards.length === 0" class="text-center opacity-60 py-8 bg-white/30 rounded-2xl backdrop-blur-sm border border-white/50 shadow-sm">
                    Nessun premio configurato al momento.
                </div>
                
                <div v-else class="space-y-4">
                    <div v-for="reward in rewards" :key="reward.id" class="bg-white/60 backdrop-blur-md rounded-2xl p-4 border border-white/80 shadow-sm flex items-center justify-between">
                        <div>
                            <div class="font-bold text-md text-gray-900">{{ reward.name }}</div>
                            <div class="text-sm text-gray-600 mt-1">
                                <span v-if="reward.condition_type === 'points'">Usa {{ reward.condition_value }} Punti</span>
                                <span v-else-if="reward.condition_type === 'purchase_amount'">Spesa min. € {{ reward.condition_value }}</span>
                            </div>
                        </div>
                        <div class="w-10 h-10 rounded-full flex items-center justify-center shadow-md transition-colors" :class="customer.loyalty_points >= reward.condition_value && reward.condition_type === 'points' ? 'text-white' : 'bg-gray-200 text-gray-400'" :style="customer.loyalty_points >= reward.condition_value && reward.condition_type === 'points' ? { backgroundColor: primary } : {}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
