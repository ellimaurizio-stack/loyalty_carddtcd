<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    total_customers: Number,
    segments: Object,
    brands: Array,
    stores: Array,
    filters: Object
});

const userRole = usePage().props.auth.user.role;

const selectedBrand = ref(props.filters.brand_id || '');
const selectedStore = ref(props.filters.store_id || '');

const segmentDetails = {
    'Campioni': { desc: 'Comprano spessissimo, di recente e spendono molto.', action: 'Non dare sconti. Offri ricompense esclusive.', color: 'bg-emerald-100 text-emerald-800' },
    'Clienti Fedeli': { desc: 'Comprano regolarmente.', action: 'Up-selling e bundle.', color: 'bg-green-100 text-green-800' },
    'Potenziali Leali': { desc: 'Hanno acquistato di recente ma spesa bassa.', action: 'Programma fedeltà a soglie.', color: 'bg-teal-100 text-teal-800' },
    'Nuovi Clienti': { desc: 'Hanno appena fatto il primo acquisto.', action: 'Email di benvenuto, coupon 2° acquisto.', color: 'bg-blue-100 text-blue-800' },
    'Promettenti': { desc: 'Comprato una volta tempo fa.', action: 'Offerta a tempo limitato.', color: 'bg-indigo-100 text-indigo-800' },
    'Necessitano Attenzioni': { desc: 'Stanno perdendo frequenza.', action: 'Promozioni stagionali.', color: 'bg-yellow-100 text-yellow-800' },
    'In Sonno': { desc: 'Comprano raramente e distanti nel tempo.', action: 'Sconti aggressivi su categorie note.', color: 'bg-orange-100 text-orange-800' },
    'A Rischio': { desc: 'Ottimi clienti in passato, ora assenti.', action: 'Messaggio personalizzato, Cashback elevato.', color: 'bg-red-100 text-red-800' },
    'Non Perderli!': { desc: 'Campioni di ieri che stanno scappando.', action: 'Contatto umano o offerta irrinunciabile.', color: 'bg-rose-100 text-rose-800' },
    'Ibernati': { desc: 'Acquisto lontanissimo, pochissima spesa.', action: 'Ultimo tentativo di riattivazione.', color: 'bg-gray-100 text-gray-800' },
    'Persi': { desc: 'Praticamente inattivi.', action: 'Ignorare per ottimizzare budget.', color: 'bg-slate-100 text-slate-800' },
    'Altro': { desc: 'Non classificato.', action: 'Nessuna', color: 'bg-gray-50 text-gray-500' }
};

const orderedSegments = [
    'Campioni', 'Clienti Fedeli', 'Potenziali Leali', 'Nuovi Clienti', 
    'Promettenti', 'Necessitano Attenzioni', 'In Sonno', 'A Rischio', 
    'Non Perderli!', 'Ibernati', 'Persi', 'Altro'
];

function applyFilters() {
    router.get(route('admin.analytics.rfm'), {
        brand_id: selectedBrand.value,
        store_id: selectedStore.value
    }, { preserveState: true });
}

function exportCsv(segmentName) {
    alert('Export CSV per ' + segmentName + ' (in arrivo nella Fase 3)');
}

function quickAction(segmentName) {
    alert('Azione Rapida Promo per ' + segmentName + ' (in arrivo nella Fase 3)');
}
</script>

<template>
    <Head title="Segmentazione RFM" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Segmentazione RFM Clienti</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Filters Multi-Tenant -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" v-if="userRole === 'super_admin' || userRole === 'brand_manager'">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Filtri di Analisi</h3>
                    <div class="flex gap-4">
                        <div v-if="userRole === 'super_admin'" class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Brand</label>
                            <select v-model="selectedBrand" @change="applyFilters" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Tutti i Brand</option>
                                <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Store</label>
                            <select v-model="selectedStore" @change="applyFilters" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Tutti gli Store</option>
                                <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Totali -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <p class="text-gray-500 text-sm uppercase tracking-wide">Clienti Analizzati</p>
                    <p class="text-4xl font-extrabold text-indigo-600 mt-2">{{ total_customers }}</p>
                </div>

                <!-- Grid Segmenti -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="seg in orderedSegments" :key="seg" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-col justify-between border-t-4" :class="[segmentDetails[seg]?.color.split(' ')[0].replace('100', '400')]">
                        <div>
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg font-bold text-gray-900">{{ seg }}</h3>
                                <span class="px-3 py-1 rounded-full text-sm font-bold" :class="segmentDetails[seg]?.color">
                                    {{ segments[seg] || 0 }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mt-2 h-10">{{ segmentDetails[seg]?.desc }}</p>
                            <div class="mt-4 p-3 bg-gray-50 rounded-md">
                                <p class="text-xs font-semibold text-gray-500 uppercase">Azione Suggerita</p>
                                <p class="text-sm text-gray-800 mt-1">{{ segmentDetails[seg]?.action }}</p>
                            </div>
                        </div>
                        <div class="mt-6 flex gap-2">
                            <button @click="quickAction(seg)" class="flex-1 bg-indigo-50 text-indigo-700 px-3 py-2 rounded text-sm font-medium hover:bg-indigo-100 transition">
                                Crea Promo
                            </button>
                            <button @click="exportCsv(seg)" class="bg-gray-50 text-gray-700 px-3 py-2 rounded text-sm font-medium hover:bg-gray-200 transition">
                                Esporta
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
