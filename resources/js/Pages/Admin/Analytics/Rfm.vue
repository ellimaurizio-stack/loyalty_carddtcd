<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    total_customers: Number,
    segments: Object,
    trends: Array,
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
    const url = new URL(route('admin.analytics.rfm.export'));
    url.searchParams.append('segment', segmentName);
    if (selectedBrand.value) url.searchParams.append('brand_id', selectedBrand.value);
    if (selectedStore.value) url.searchParams.append('store_id', selectedStore.value);
    
    window.location.href = url.toString();
}

const showPromoModal = ref(false);
const activeSegment = ref('');

function quickAction(segmentName) {
    activeSegment.value = segmentName;
    showPromoModal.value = true;
}

function closePromoModal() {
    showPromoModal.value = false;
    activeSegment.value = '';
}

function createPromo(templateType) {
    const urlParams = {
        target_segment: activeSegment.value,
        template: templateType
    };
    if (selectedBrand.value) urlParams.brand_id = selectedBrand.value;
    if (selectedStore.value) urlParams.store_id = selectedStore.value;
    
    router.get(route('promotional-rules.index'), urlParams);
    closePromoModal();
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

                <!-- Totali e Trend -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center flex flex-col justify-center">
                        <p class="text-gray-500 text-sm uppercase tracking-wide">Clienti Analizzati</p>
                        <p class="text-4xl font-extrabold text-indigo-600 mt-2">{{ total_customers }}</p>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-gray-500 text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Trend e Allarmi (Ultimi Movimenti)
                        </h3>
                        <div v-if="trends.length === 0" class="text-sm text-gray-500 italic">
                            Nessun movimento rilevante rilevato al momento.
                        </div>
                        <ul v-else class="space-y-3">
                            <li v-for="(t, i) in trends" :key="i" class="text-sm border-b pb-2 last:border-0">
                                <span class="font-bold text-gray-900">{{ t.total }} Clienti</span> sono passati da 
                                <span class="line-through text-gray-400">{{ t.rfm_previous_segment }}</span> 
                                a <span class="font-semibold" :class="segmentDetails[t.rfm_segment]?.color.split(' ')[1]">{{ t.rfm_segment }}</span>
                            </li>
                        </ul>
                    </div>
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

        <!-- Modale Azione Rapida Promo -->
        <div v-if="showPromoModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closePromoModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Seleziona Template Regola per: {{ activeSegment }}
                                </h3>
                                <p class="text-sm text-gray-500 mt-1 mb-4">Scegli quale meccanica applicare al segmento. Verrai reindirizzato al Rule Engine per completare i dettagli dell'offerta.</p>
                                <div class="mt-4 space-y-3 max-h-96 overflow-y-auto pr-2">
                                    <button @click="createPromo('Bundle')" class="w-full text-left p-4 border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors">
                                        <div class="font-bold text-indigo-900">Bundle Ad Hoc (Sconto o Premio Fisico)</div>
                                        <div class="text-xs text-indigo-700">Se compri X, ricevi Y in bundle (o emetti un Coupon su App).</div>
                                    </button>
                                    <button @click="createPromo('SpecialMultiplier')" class="w-full text-left p-4 border border-green-200 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                                        <div class="font-bold text-green-900">Moltiplicatore Punti Speciale</div>
                                        <div class="text-xs text-green-700">Moltiplica i punti accumulati dal segmento (es. x2).</div>
                                    </button>
                                    <button @click="createPromo('Discount')" class="w-full text-left p-4 border border-gray-200 hover:bg-gray-50 rounded-lg transition-colors">
                                        <div class="font-bold text-gray-900">Sconto Diretto (Fisso o %)</div>
                                        <div class="text-xs text-gray-600">Sconto applicato direttamente al totale spesa.</div>
                                    </button>
                                    <button @click="createPromo('Points')" class="w-full text-left p-4 border border-gray-200 hover:bg-gray-50 rounded-lg transition-colors">
                                        <div class="font-bold text-gray-900">Motore Punti Base</div>
                                        <div class="text-xs text-gray-600">Cambia le regole di accumulo standard.</div>
                                    </button>
                                    <button @click="createPromo('ProductReward')" class="w-full text-left p-4 border border-gray-200 hover:bg-gray-50 rounded-lg transition-colors">
                                        <div class="font-bold text-gray-900">Vantaggi su Prodotti Specifici</div>
                                        <div class="text-xs text-gray-600">Premia l'acquisto di specifici EAN in scontrino.</div>
                                    </button>
                                    <button @click="createPromo('Missions')" class="w-full text-left p-4 border border-gray-200 hover:bg-gray-50 rounded-lg transition-colors">
                                        <div class="font-bold text-gray-900">Missioni Gamification</div>
                                        <div class="text-xs text-gray-600">Sfide ripetibili per aumentare le visite o la spesa.</div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="closePromoModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Annulla
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
