<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ContextSelector from '@/Components/ContextSelector.vue';
import { watch, ref, computed, onMounted } from 'vue';

const props = defineProps({
    rules: Array,
    program: Object,
    products: Array,
    brands: Array,
    stores: Array,
    currentBrandId: [Number, String],
    currentStoreId: [Number, String],
});

const editingRuleId = ref(null);

const form = useForm({
    name: '',
    type: 'Points',
    is_active: true,
    priority: 0,
    is_stackable: true,
    conditions: {
        trigger_type: 'always', // always, nth_purchase, date_range, specific_days, specific_products
        nth_purchase_count: 5,
        nth_purchase_recurrence: 'once', // once, recurring
        date_start: '',
        date_end: '',
        allowed_days: [],
        trigger_products: [], // { ean, quantity }
        target_segment: '', // NEW: Apply only to specific RFM Segment
    },
    parameters: {
        euros_per_point: 1,
        min_spend: 0,
        discount_type: 'percent',
        discount_value: 0,
        welcome_reward_type: 'points',
        welcome_reward_value: 0,
        referrer_reward_type: 'points',
        referrer_reward_value: 0,
        referred_reward_type: 'points',
        referred_reward_value: 0,
        tiers: [
            {
                threshold: 100,
                name: 'Livello 1',
                rewards: [
                    { type: 'multiplier', value: 1.5 }
                ]
            }
        ],
        mission_type: 'visits',
        mission_target: 5,
        mission_reward_type: 'points',
        mission_reward_value: 0,
        mission_is_repeatable: false,
        reward_type: 'points', // For ProductReward template
        reward_value: 0,
        // Bundle Parameters
        bundle_products: [], // { ean, quantity }
        bundle_discount_type: 'percent',
        bundle_discount_value: 0,
        bundle_application_type: 'pos_direct', // pos_direct, pwa_coupon
        coupon_type: 'discount', // discount, physical_prize
        coupon_prize_name: '',
        coupon_bg_color: '#4f46e5',
        coupon_text_color: '#ffffff',
        coupon_title: 'Sconto Speciale per Te!',
        coupon_subtitle: 'Mostra questo codice in cassa',
        coupon_image_url: '',
        // SpecialMultiplier
        special_multiplier: 2.0
    }
});

onMounted(() => {
    // Parse URL parameters for automatic pre-filling from RFM Dashboard
    const urlParams = new URLSearchParams(window.location.search);
    const targetSegment = urlParams.get('target_segment');
    const templateType = urlParams.get('template');

    if (targetSegment) {
        form.conditions.target_segment = targetSegment;
    }
    
    if (templateType) {
        form.type = templateType;
        // Trigger watch logic
        if (templateType === 'Bundle') form.name = 'Offerta Bundle Esclusiva';
        else if (templateType === 'SpecialMultiplier') form.name = 'Moltiplicatore Punti Speciale';
        else if (templateType === 'Discount') form.name = 'Sconto Diretto (Promo Rapida)';
        
        setTimeout(() => {
            document.getElementById('rule-form-container')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 300);
    }
});

const filteredRules = computed(() => {
    return props.rules;
});

watch(() => form.type, (newType) => {
    if (newType === 'Points') form.name = 'Motore Punti Base';
    else if (newType === 'Discount') form.name = 'Sconto Diretto';
    else if (newType === 'Welcome') form.name = 'Bonus Benvenuto';
    else if (newType === 'Referral') form.name = 'Programma Passaparola';
    else if (newType === 'ProductReward') {
        form.name = 'Premio su Acquisto Prodotto';
        form.conditions.trigger_type = 'specific_products';
    } else if (newType === 'Bundle') {
        form.name = 'Offerta Bundle Esclusiva';
        form.conditions.trigger_type = 'specific_products';
    } else if (newType === 'SpecialMultiplier') {
        form.name = 'Moltiplicatore Punti Speciale';
    }
});

const submit = () => {
    const submitData = form.transform((data) => ({
        ...data,
        brand_id: props.currentBrandId,
        store_id: props.currentStoreId
    }));

    if (editingRuleId.value) {
        submitData.put(route('promotional-rules.update', editingRuleId.value), {
            preserveScroll: true,
            onSuccess: () => {
                cancelEdit();
            },
        });
    } else {
        submitData.post(route('promotional-rules.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('name');
            },
        });
    }
};

const editRule = (rule) => {
    editingRuleId.value = rule.id;
    form.name = rule.name;
    form.type = rule.type;
    form.is_active = rule.is_active;
    form.priority = rule.priority;
    form.is_stackable = rule.is_stackable;
    
    if (rule.conditions) {
        form.conditions = { ...form.conditions, ...rule.conditions };
    }
    
    if (rule.parameters) {
        form.parameters = { ...form.parameters, ...rule.parameters };
    }

    setTimeout(() => {
        document.getElementById('rule-form-container')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
};

const cancelEdit = () => {
    editingRuleId.value = null;
    form.reset();
};

const toggleRule = (rule) => {
    const data = {
        name: rule.name,
        type: rule.type,
        is_active: !rule.is_active,
        priority: rule.priority,
        is_stackable: rule.is_stackable,
        conditions: rule.conditions || { trigger_type: 'always' },
        parameters: rule.parameters || {},
        brand_id: props.currentBrandId,
        store_id: props.currentStoreId
    };

    router.put(route('promotional-rules.update', rule.id), data, {
        preserveScroll: true,
    });
};

const deleteRule = (rule) => {
    if (confirm("Vuoi davvero eliminare questa regola?")) {
        router.delete(route('promotional-rules.destroy', rule.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Rule Engine (Regole Promozionali)" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Rule Engine Promozionale</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <ContextSelector 
                    :brands="brands" 
                    :stores="stores" 
                    :current-brand-id="currentBrandId"
                    :current-store-id="currentStoreId"
                    base-route="promotional-rules.index"
                />
                
                <div v-if="$page.props.flash?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ $page.props.flash.success }}</span>
                </div>

                <!-- Add New Rule Form -->
                <div id="rule-form-container" class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <section>
                        <header class="flex justify-between items-start">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ editingRuleId ? 'Modifica Regola' : 'Crea Nuova Regola (Rule Builder)' }}
                                </h2>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ editingRuleId ? 'Aggiorna i parametri della tua regola. Le modifiche avranno effetto immediato sulle prossime transazioni.' : 'Scegli un Template di regola e compila i campi per aggiungerla al tuo motore promozionale.' }}
                                </p>
                            </div>
                        </header>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            
                            <!-- GENERAL SETTINGS -->
                            <div class="bg-gray-50 dark:bg-gray-700 p-5 rounded-xl border border-gray-100 dark:border-gray-600">
                                <h3 class="text-md font-bold mb-4 border-b pb-2">1. Impostazioni Generali</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel for="type" value="Template Regola" />
                                        <select v-model="form.type" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm font-bold text-indigo-700">
                                            <option value="Points">1. Motore Punti Base</option>
                                            <option value="Discount">2. Sconto Diretto</option>
                                            <option value="Welcome">3. Bonus Benvenuto (Primo Acquisto)</option>
                                            <option value="Referral">4. Programma Passaparola</option>
                                            <option value="Tiers">5. Soglie VIP (Livelli)</option>
                                            <option value="Cashback">6. Programma Borsellino Virtuale</option>
                                            <option value="Missions">7. Missioni (Gamification)</option>
                                            <option value="ProductReward">8. Vantaggi su Prodotti Specifici</option>
                                            <option value="Bundle">9. Bundle Ad Hoc (Combo Prodotti)</option>
                                            <option value="SpecialMultiplier">10. Moltiplicatore Special (Segmenti)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel for="name" value="Nome Etichetta (Interno)" />
                                        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required />
                                    </div>
                                </div>
                            </div>
                            <!-- TRIGGER E APPLICAZIONE -->
                            <div class="bg-blue-50 dark:bg-blue-900/30 p-5 rounded-xl border border-blue-100 dark:border-blue-800">
                                <h3 class="text-md font-bold text-blue-800 dark:text-blue-200 mb-4 border-b border-blue-200 pb-2">2. Condizioni di Attivazione (Trigger)</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2">
                                        <InputLabel value="Quando deve scattare questa regola?" />
                                        <select v-model="form.conditions.trigger_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                            <option value="always">Sempre attiva (in ogni scontrino)</option>
                                            <option v-if="form.type !== 'Missions'" value="user_birthday">Nel giorno del compleanno dell'utente</option>
                                            <option v-if="form.type !== 'Missions'" value="nth_purchase">Dopo un certo numero di acquisti (N° Scontrini)</option>
                                            <option value="date_range">In un periodo temporale specifico</option>
                                            <option value="specific_days">In giorni specifici della settimana</option>
                                            <option value="specific_products">All'acquisto di Prodotti Specifici dal Catalogo</option>
                                            
                                        </select>
                                    </div>

                                    <div class="md:col-span-2 mt-4 mb-2 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <InputLabel value="Applica solo al Segmento RFM (Opzionale)" />
                                        <select v-model="form.conditions.target_segment" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-yellow-900 bg-white">
                                            <option value="">Tutti i Clienti (Nessun Filtro)</option>
                                            <option value="Campioni">Campioni</option>
                                            <option value="Clienti Fedeli">Clienti Fedeli</option>
                                            <option value="Potenziali Leali">Potenziali Leali</option>
                                            <option value="Nuovi Clienti">Nuovi Clienti</option>
                                            <option value="Promettenti">Promettenti</option>
                                            <option value="Necessitano Attenzione">Necessitano Attenzione</option>
                                            <option value="Quasi Dormienti">Quasi Dormienti</option>
                                            <option value="A Rischio">A Rischio</option>
                                            <option value="Non Perderli!">Non Perderli!</option>
                                            <option value="Ibernati">Ibernati</option>
                                            <option value="Persi">Persi</option>
                                        </select>
                                        <p class="text-xs text-yellow-700 mt-1">Selezionando un segmento, questa regola si applicherà <b>esclusivamente</b> a quegli utenti.</p>
                                    </div>

                                    <!-- Nth Purchase -->
                                    <template v-if="form.conditions.trigger_type === 'nth_purchase'">
                                        <div>
                                            <InputLabel value="Numero di Acquisti (N)" />
                                            <TextInput type="number" step="1" min="1" class="mt-1 block w-full" v-model="form.conditions.nth_purchase_count" />
                                        </div>
                                        <div>
                                            <InputLabel value="Frequenza" />
                                            <select v-model="form.conditions.nth_purchase_recurrence" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                                <option value="once">Una sola volta (es. solo al 5° acquisto)</option>
                                                <option value="recurring">Ricorrente (es. ogni 5 acquisti)</option>
                                            </select>
                                        </div>
                                    </template>

                                    <!-- Date Range -->
                                    <template v-if="form.conditions.trigger_type === 'date_range'">
                                        <div>
                                            <InputLabel value="Data di Inizio" />
                                            <TextInput type="date" class="mt-1 block w-full" v-model="form.conditions.date_start" />
                                        </div>
                                        <div>
                                            <InputLabel value="Data di Fine" />
                                            <TextInput type="date" class="mt-1 block w-full" v-model="form.conditions.date_end" />
                                        </div>
                                    </template>

                                    <!-- Specific Days -->
                                    <div v-if="form.conditions.trigger_type === 'specific_days'" class="md:col-span-2">
                                        <InputLabel value="Seleziona i giorni consentiti" />
                                        <div class="mt-2 flex flex-wrap gap-4">
                                            <label v-for="day in ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica']" :key="day" class="flex items-center space-x-2">
                                                <input type="checkbox" :value="day" v-model="form.conditions.allowed_days" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ day }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Specific Products -->
                                    <div v-if="form.conditions.trigger_type === 'specific_products'" class="md:col-span-2">
                                        <InputLabel value="Prodotti Richiesti (devono essere tutti presenti nello scontrino)" />
                                        
                                        <div v-for="(prodLine, index) in form.conditions.trigger_products" :key="index" class="flex items-center space-x-4 mt-2">
                                            <select v-model="prodLine.ean" class="block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                                <option disabled value="">-- Seleziona un prodotto --</option>
                                                <option v-for="p in products" :key="p.id" :value="p.ean">
                                                    {{ p.name }} (EAN: {{ p.ean }})
                                                </option>
                                            </select>
                                            
                                            <div class="flex items-center w-32">
                                                <span class="mr-2 text-sm">Q.tà:</span>
                                                <TextInput type="number" step="1" min="1" v-model="prodLine.quantity" class="w-full text-sm py-1" required />
                                            </div>
                                            
                                            <button @click.prevent="form.conditions.trigger_products.splice(index, 1)" class="text-red-500 hover:text-red-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>

                                        <button @click.prevent="form.conditions.trigger_products.push({ean: '', quantity: 1})" class="mt-3 text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            Aggiungi Prodotto Richiesto
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- PRIORITÀ E CUMULABILITÀ -->
                            <div class="bg-gray-50 dark:bg-gray-900/30 p-5 rounded-xl border border-gray-200 dark:border-gray-800">
                                <h3 class="text-md font-bold text-gray-800 dark:text-gray-200 mb-4 border-b border-gray-200 pb-2">3. Priorità e Cumulabilità</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel value="Priorità di Esecuzione" />
                                        <TextInput type="number" step="1" class="mt-1 block w-full" v-model="form.priority" />
                                        <p class="mt-1 text-xs text-gray-500">Numeri più alti indicano priorità maggiore. Es. 100 viene eseguito prima di 0.</p>
                                    </div>
                                    <div class="flex items-center pt-6">
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input type="checkbox" v-model="form.is_stackable" class="h-5 w-5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Cumulabile con altre regole</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- TEMPLATE SPECIFIC FIELDS -->
                            <div class="bg-indigo-50 dark:bg-indigo-900 p-5 rounded-xl border border-indigo-100 dark:border-indigo-700">
                                <h3 class="text-md font-bold text-indigo-800 dark:text-indigo-200 mb-4 border-b border-indigo-200 pb-2">4. Effetto della Regola</h3>
                                
                                <!-- TEMPLATE 1: POINTS -->
                                <div v-if="form.type === 'Points'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel value="Euro necessari per 1 Punto" />
                                        <TextInput type="number" step="0.1" min="0.1" class="mt-1 block w-full" v-model="form.parameters.euros_per_point" required />
                                        <p class="text-xs text-gray-500 mt-1">Esempio: 1 = un punto ogni euro. 0.5 = due punti ogni euro.</p>
                                    </div>
                                    <div>
                                        <InputLabel value="Spesa Minima per attivare l'accumulo (€)" />
                                        <TextInput type="number" step="0.5" min="0" class="mt-1 block w-full" v-model="form.parameters.min_spend" required />
                                    </div>
                                </div>

                                <!-- TEMPLATE 2: DISCOUNT -->
                                <div v-if="form.type === 'Discount'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel value="Tipo di Sconto" />
                                        <select v-model="form.parameters.discount_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                            <option value="percent">Percentuale (%)</option>
                                            <option value="fixed">Valore Fisso (€)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel value="Valore dello Sconto" />
                                        <TextInput type="number" step="0.5" min="0" class="mt-1 block w-full" v-model="form.parameters.discount_value" required />
                                    </div>
                                </div>

                                <!-- TEMPLATE 3: WELCOME -->
                                <div v-if="form.type === 'Welcome'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2 text-sm text-indigo-700 mb-2">
                                        Questa regola si attiverà automaticamente alla <b>prima transazione</b> dell'utente.
                                    </div>
                                    <div>
                                        <InputLabel value="Tipo di Premio Benvenuto" />
                                        <select v-model="form.parameters.welcome_reward_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                            <option value="points">Punti Extra Aggiuntivi</option>
                                            <option value="discount_percent">Sconto Percentuale (%)</option>
                                            <option value="discount_fixed">Sconto Fisso (€)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel value="Valore" />
                                        <TextInput type="number" step="0.5" min="0" class="mt-1 block w-full" v-model="form.parameters.welcome_reward_value" required />
                                    </div>
                                </div>

                                <!-- TEMPLATE 4: REFERRAL -->
                                <div v-if="form.type === 'Referral'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2 text-sm text-indigo-700 mb-2">
                                        Assegna una ricompensa quando l'Utente A (Invitante) fa iscrivere l'Utente B, e quest'ultimo fa il primo acquisto.
                                    </div>
                                    <div class="p-4 border border-indigo-200 bg-white rounded-lg">
                                        <h4 class="font-bold text-indigo-900 mb-2">Ricompensa Invitante</h4>
                                        <div class="space-y-4">
                                            <div>
                                                <InputLabel value="Tipo di Premio" />
                                                <select v-model="form.parameters.referrer_reward_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" disabled>
                                                    <option value="points">Punti Extra</option>
                                                </select>
                                                <p class="text-xs text-gray-500 mt-1">L'invitante può ricevere solo Punti Extra.</p>
                                            </div>
                                            <div>
                                                <InputLabel value="Valore" />
                                                <TextInput type="number" step="0.5" min="0" class="mt-1 block w-full text-sm" v-model="form.parameters.referrer_reward_value" required />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 border border-indigo-200 bg-white rounded-lg">
                                        <h4 class="font-bold text-indigo-900 mb-2">Ricompensa Invitato</h4>
                                        <div class="space-y-4">
                                            <div>
                                                <InputLabel value="Tipo di Premio" />
                                                <select v-model="form.parameters.referred_reward_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                    <option value="points">Punti Extra</option>
                                                    <option value="discount_percent">Sconto (%)</option>
                                                </select>
                                            </div>
                                            <div>
                                                <InputLabel value="Valore" />
                                                <TextInput type="number" step="0.5" min="0" class="mt-1 block w-full text-sm" v-model="form.parameters.referred_reward_value" required />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- TEMPLATE 5: TIERS (LIVELLI MULTIPLI) -->
                                <div v-if="form.type === 'Tiers'" class="space-y-4">
                                    <div class="text-sm text-indigo-700 mb-2">
                                        Attiva vantaggi permanenti quando l'utente supera certe soglie di Punti Storici (Livelli).
                                    </div>
                                    
                                    <div v-for="(tier, tierIndex) in form.parameters.tiers" :key="tierIndex" class="p-4 border border-indigo-200 bg-white rounded-lg relative">
                                        <button @click.prevent="form.parameters.tiers.splice(tierIndex, 1)" v-if="form.parameters.tiers.length > 1" class="absolute top-4 right-4 text-red-500 hover:text-red-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 pr-8">
                                            <div>
                                                <InputLabel value="Nome Livello (Es. Gold, Livello 2)" />
                                                <TextInput type="text" class="mt-1 block w-full text-sm" v-model="tier.name" required />
                                            </div>
                                            <div>
                                                <InputLabel value="Soglia Punti Storici Richiesta" />
                                                <TextInput type="number" step="1" min="1" class="mt-1 block w-full text-sm" v-model="tier.threshold" required />
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4 pt-4 border-t border-gray-100">
                                            <h5 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Vantaggi per questo Livello</h5>
                                            
                                            <div v-for="(reward, rewardIndex) in tier.rewards" :key="rewardIndex" class="flex flex-col space-y-2 mb-4 p-3 bg-gray-50 rounded border border-gray-100">
                                                <div class="flex items-center space-x-3">
                                                    <select v-model="reward.type" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                        <option value="multiplier">Moltiplicatore Punti (es. x1.5)</option>
                                                        <option value="discount_percent">Sconto Fisso Permanente (%)</option>
                                                        <option value="discount_fixed">Sconto a Valore (€)</option>
                                                        <option value="referral_multiplier">Moltiplicatore Punti Invito (Passaparola)</option>
                                                        <option value="special_days">Giorni Speciali</option>
                                                        <option value="happy_hour">Happy Hour</option>
                                                    </select>
                                                    <button @click.prevent="tier.rewards.splice(rewardIndex, 1)" v-if="tier.rewards.length > 1" class="text-red-400 hover:text-red-600 p-1 flex-shrink-0">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                    </button>
                                                </div>
                                                
                                                <div class="grid grid-cols-2 gap-3" v-if="['multiplier', 'discount_percent', 'referral_multiplier'].includes(reward.type)">
                                                    <div>
                                                        <InputLabel :value="reward.type === 'discount_percent' ? 'Sconto (%)' : 'Moltiplicatore (es. 1.5)'" />
                                                        <TextInput type="number" step="0.1" min="0.1" class="block w-full text-sm" v-model="reward.value" required />
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-3" v-if="reward.type === 'discount_fixed'">
                                                    <div>
                                                        <InputLabel value="Sconto Fisso (€)" />
                                                        <TextInput type="number" step="0.5" min="0.5" class="block w-full text-sm" v-model="reward.value" required />
                                                    </div>
                                                    <div>
                                                        <InputLabel value="Spesa Minima (€)" />
                                                        <TextInput type="number" step="0.5" min="0" class="block w-full text-sm" v-model="reward.min_spend" required />
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-3" v-if="reward.type === 'special_days'">
                                                    <div>
                                                        <InputLabel value="Giorno della Settimana" />
                                                        <select v-model="reward.special_day" class="block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                                            <option value="1">Lunedì</option>
                                                            <option value="2">Martedì</option>
                                                            <option value="3">Mercoledì</option>
                                                            <option value="4">Giovedì</option>
                                                            <option value="5">Venerdì</option>
                                                            <option value="6">Sabato</option>
                                                            <option value="0">Domenica</option>
                                                            <option value="birthday">Compleanno dell'utente</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <InputLabel value="Moltiplicatore Punti" />
                                                        <TextInput type="number" step="0.1" min="1.1" class="block w-full text-sm" v-model="reward.value" required />
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-3 gap-3" v-if="reward.type === 'happy_hour'">
                                                    <div>
                                                        <InputLabel value="Ora Inizio" />
                                                        <TextInput type="time" class="block w-full text-sm" v-model="reward.hh_start" required />
                                                    </div>
                                                    <div>
                                                        <InputLabel value="Ora Fine" />
                                                        <TextInput type="time" class="block w-full text-sm" v-model="reward.hh_end" required />
                                                    </div>
                                                    <div>
                                                        <InputLabel value="Moltiplicatore" />
                                                        <TextInput type="number" step="0.1" min="1.1" class="block w-full text-sm" v-model="reward.value" required />
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <button @click.prevent="tier.rewards.push({type: 'multiplier', value: 1.5})" class="mt-2 text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                Aggiungi Vantaggio
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <button @click.prevent="form.parameters.tiers.push({threshold: 500, name: 'Nuovo Livello', rewards: [{type: 'multiplier', value: 1.5}]})" class="w-full py-2 border-2 border-dashed border-indigo-300 text-indigo-600 rounded-lg font-bold hover:bg-indigo-50 hover:border-indigo-400 transition-colors flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Aggiungi un'altra Soglia Livello
                                    </button>
                                </div>

                                <!-- TEMPLATE 6: CASHBACK -->
                                <div v-if="form.type === 'Cashback'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2 text-sm text-indigo-700 mb-2">
                                        Invece dei punti, l'utente accumula un borsellino virtuale spendibile.
                                    </div>
                                    <div>
                                        <InputLabel value="Percentuale Borsellino (%)" />
                                        <TextInput type="number" step="0.5" min="0" class="mt-1 block w-full" v-model="form.parameters.cashback_percent" required />
                                        <p class="text-xs text-gray-500 mt-1">Es. 5 = Il 5% dello scontrino diventa credito virtuale.</p>
                                    </div>
                                    <div>
                                        <InputLabel value="Scadenza Credito (Giorni)" />
                                        <TextInput type="number" step="1" min="0" class="mt-1 block w-full" v-model="form.parameters.cashback_expiration_days" />
                                        <p class="text-xs text-gray-500 mt-1">Lascia 0 per non far scadere mai il credito.</p>
                                    </div>
                                </div>

                                <!-- TEMPLATE 8: MISSIONS -->
                                <div v-if="form.type === 'Missions'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2 text-sm text-indigo-700 mb-2">
                                        L'utente deve raggiungere un obiettivo per sbloccare un premio.
                                    </div>
                                    <div>
                                        <InputLabel value="Obiettivo Missione" />
                                        <select v-model="form.parameters.mission_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                            <option value="visits">Numero di Visite (Scontrini)</option>
                                            <option value="spend">Spesa Totale Cumulata (€)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel value="Target da raggiungere" />
                                        <TextInput type="number" step="1" min="1" class="mt-1 block w-full" v-model="form.parameters.mission_target" required />
                                    </div>
                                    <div>
                                        <InputLabel value="Tipo Premio al Completamento" />
                                        <select v-model="form.parameters.mission_reward_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                            <option value="points">Punti Extra</option>
                                            <option value="discount_fixed">Sconto Fisso (€)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel value="Valore Premio" />
                                        <TextInput type="number" step="1" min="1" class="mt-1 block w-full" v-model="form.parameters.mission_reward_value" required />
                                    </div>
                                    <div class="md:col-span-2 flex items-center mt-2">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="checkbox" v-model="form.parameters.mission_is_repeatable" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-5 h-5">
                                            <span class="ml-2 text-md font-medium text-gray-800">Missione Ripetibile (Ricomincia da zero una volta completata)</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- TEMPLATE 9: PRODUCT REWARDS -->
                                <div v-if="form.type === 'ProductReward'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2 text-sm text-indigo-700 mb-2">
                                        Questa regola definisce cosa vince l'utente se soddisfa le condizioni di acquisto prodotto inserite sopra.
                                    </div>
                                    <div>
                                        <InputLabel value="Vantaggio Ricevuto (Premio)" />
                                        <select v-model="form.parameters.reward_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                            <option value="points">Punti Extra Aggiuntivi</option>
                                            <option value="cashback">Accredito Cashback (€)</option>
                                            <option value="physical_prize">Premio Fisico (Da ritirare in cassa)</option>
                                            <option value="discount_fixed">Sconto Fisso per la spesa (€)</option>
                                            <option value="discount_percent">Sconto in Percentuale (%)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel value="Valore (Num / Testo)" />
                                        <TextInput type="text" class="mt-1 block w-full" v-model="form.parameters.reward_value" required placeholder="Es. 100, 5, T-Shirt, ecc." />
                                        <p class="text-xs text-gray-500 mt-1">Per punti e sconti scrivi un numero. Per i premi fisici scrivi il nome del premio.</p>
                                    </div>
                                </div>

                                <!-- TEMPLATE 10: SPECIAL MULTIPLIER -->
                                <div v-if="form.type === 'SpecialMultiplier'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2 text-sm text-indigo-700 mb-2">
                                        Applica un moltiplicatore ai punti standard guadagnati con gli acquisti, ideale per targetizzare specifici segmenti RFM (es. Campioni).
                                    </div>
                                    <div>
                                        <InputLabel value="Moltiplicatore Punti" />
                                        <TextInput type="number" step="0.1" min="1.1" class="mt-1 block w-full font-bold text-lg text-indigo-700" v-model="form.parameters.special_multiplier" required />
                                        <p class="text-xs text-gray-500 mt-1">Esempio: 2 = Doppio dei punti. 1.5 = +50% dei punti.</p>
                                    </div>
                                </div>

                                <!-- TEMPLATE 11: BUNDLE AD HOC -->
                                <div v-if="form.type === 'Bundle'" class="space-y-6">
                                    <div class="text-sm text-indigo-700 mb-2">
                                        Imposta la logica: "Se compri i <b>Prodotti Richiesti</b>, ricevi un vantaggio sui <b>Prodotti in Bundle</b>".
                                    </div>
                                    
                                    <div class="p-4 border border-indigo-200 bg-white rounded-lg">
                                        <h4 class="font-bold text-indigo-900 mb-3">Scegli i Prodotti in Bundle (L'offerta)</h4>
                                        <div v-for="(bundleLine, index) in form.parameters.bundle_products" :key="index" class="flex items-center space-x-4 mt-2">
                                            <select v-model="bundleLine.ean" class="block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                                <option disabled value="">-- Seleziona un prodotto --</option>
                                                <option v-for="p in products" :key="p.id" :value="p.ean">
                                                    {{ p.name }} (EAN: {{ p.ean }})
                                                </option>
                                            </select>
                                            
                                            <div class="flex items-center w-32">
                                                <span class="mr-2 text-sm">Q.tà:</span>
                                                <TextInput type="number" step="1" min="1" v-model="bundleLine.quantity" class="w-full text-sm py-1" required />
                                            </div>
                                            
                                            <button @click.prevent="form.parameters.bundle_products.splice(index, 1)" class="text-red-500 hover:text-red-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                        <button @click.prevent="form.parameters.bundle_products.push({ean: '', quantity: 1})" class="mt-3 text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            Aggiungi Prodotto al Bundle
                                        </button>
                                    </div>

                                    <div class="p-4 border border-indigo-200 bg-white rounded-lg grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="md:col-span-2">
                                            <h4 class="font-bold text-indigo-900 border-b pb-2">Come erogare il vantaggio?</h4>
                                            <div class="flex gap-6 mt-4">
                                                <label class="flex items-center cursor-pointer">
                                                    <input type="radio" v-model="form.parameters.bundle_application_type" value="pos_direct" class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                                    <span class="ml-2 text-sm text-gray-700 font-medium">Sconto Immediato in Cassa (Al pagamento)</span>
                                                </label>
                                                <label class="flex items-center cursor-pointer">
                                                    <input type="radio" v-model="form.parameters.bundle_application_type" value="pwa_coupon" class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                                    <span class="ml-2 text-sm text-gray-700 font-medium">Emetti Coupon PWA (Da bruciare in futuro)</span>
                                                </label>
                                            </div>
                                        </div>

                                        <template v-if="form.parameters.bundle_application_type === 'pos_direct' || form.parameters.coupon_type === 'discount'">
                                            <div>
                                                <InputLabel value="Tipo Sconto sui prodotti Bundle" />
                                                <select v-model="form.parameters.bundle_discount_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                                    <option value="percent">Sconto Percentuale (%)</option>
                                                    <option value="fixed">Sconto a Valore (€)</option>
                                                    <option value="free">Gratis (100% Sconto)</option>
                                                </select>
                                            </div>
                                            <div v-if="form.parameters.bundle_discount_type !== 'free'">
                                                <InputLabel value="Valore dello Sconto" />
                                                <TextInput type="number" step="0.5" min="0.5" class="mt-1 block w-full" v-model="form.parameters.bundle_discount_value" required />
                                            </div>
                                        </template>

                                        <!-- COUPON EDITOR -->
                                        <div v-if="form.parameters.bundle_application_type === 'pwa_coupon'" class="md:col-span-2 mt-4 bg-gray-50 p-4 border border-gray-200 rounded-lg shadow-inner">
                                            <h4 class="font-bold text-gray-800 mb-3 flex items-center">
                                                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                                Editor Grafico Coupon PWA
                                            </h4>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="md:col-span-2">
                                                    <InputLabel value="Cosa contiene il Coupon?" />
                                                    <select v-model="form.parameters.coupon_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                                        <option value="discount">Sconto sui prodotti in Bundle (configurato sopra)</option>
                                                        <option value="physical_prize">Premio Fisico / Omaggio Ritiro (Nessuno sconto cassa)</option>
                                                    </select>
                                                </div>

                                                <div v-if="form.parameters.coupon_type === 'physical_prize'" class="md:col-span-2">
                                                    <InputLabel value="Nome del Premio Fisico" />
                                                    <TextInput type="text" class="mt-1 block w-full" v-model="form.parameters.coupon_prize_name" placeholder="Es. T-Shirt Ufficiale, Borraccia..." required />
                                                </div>

                                                <div>
                                                    <InputLabel value="Titolo Coupon" />
                                                    <TextInput type="text" class="mt-1 block w-full" v-model="form.parameters.coupon_title" required />
                                                </div>
                                                <div>
                                                    <InputLabel value="Sottotitolo" />
                                                    <TextInput type="text" class="mt-1 block w-full" v-model="form.parameters.coupon_subtitle" required />
                                                </div>
                                                <div>
                                                    <InputLabel value="Colore Sfondo (Hex)" />
                                                    <div class="flex items-center mt-1">
                                                        <input type="color" v-model="form.parameters.coupon_bg_color" class="h-10 w-10 p-1 border border-gray-300 rounded cursor-pointer">
                                                        <TextInput type="text" class="ml-2 block w-full" v-model="form.parameters.coupon_bg_color" />
                                                    </div>
                                                </div>
                                                <div>
                                                    <InputLabel value="Colore Testo (Hex)" />
                                                    <div class="flex items-center mt-1">
                                                        <input type="color" v-model="form.parameters.coupon_text_color" class="h-10 w-10 p-1 border border-gray-300 rounded cursor-pointer">
                                                        <TextInput type="text" class="ml-2 block w-full" v-model="form.parameters.coupon_text_color" />
                                                    </div>
                                                </div>
                                                <div class="md:col-span-2">
                                                    <InputLabel value="URL Immagine di Copertina (Opzionale)" />
                                                    <TextInput type="url" class="mt-1 block w-full" v-model="form.parameters.coupon_image_url" placeholder="https://..." />
                                                </div>
                                            </div>

                                            <div class="mt-6 border border-gray-300 rounded-xl overflow-hidden max-w-sm mx-auto shadow-lg" :style="{ backgroundColor: form.parameters.coupon_bg_color }">
                                                <div v-if="form.parameters.coupon_image_url" class="h-32 w-full bg-cover bg-center" :style="{ backgroundImage: `url(${form.parameters.coupon_image_url})` }"></div>
                                                <div v-else class="h-16 w-full bg-black/10"></div>
                                                <div class="p-6 text-center border-t-2 border-dashed border-black/20" :style="{ color: form.parameters.coupon_text_color }">
                                                    <h5 class="text-xl font-black uppercase tracking-wide">{{ form.parameters.coupon_title }}</h5>
                                                    <p class="mt-2 text-sm opacity-90">{{ form.parameters.coupon_subtitle }}</p>
                                                    <div v-if="form.parameters.coupon_type === 'physical_prize'" class="mt-4 inline-block bg-white/20 px-3 py-1 rounded-full text-xs font-bold backdrop-blur-sm">
                                                        🏆 {{ form.parameters.coupon_prize_name }}
                                                    </div>
                                                    <div class="mt-6 mx-auto w-32 h-32 bg-white rounded-lg p-2 opacity-80 flex items-center justify-center">
                                                        <span class="text-black font-mono text-xs">QR CODE HERE</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 border-t pt-4">
                                <PrimaryButton :disabled="form.processing" class="py-3 px-8 text-lg">
                                    {{ editingRuleId ? 'Aggiorna Regola' : 'Crea Regola ed Attiva' }}
                                </PrimaryButton>

                                <button v-if="editingRuleId" @click.prevent="cancelEdit" class="py-3 px-6 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md font-semibold transition-colors">
                                    Annulla Modifica
                                </button>

                                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                    <p v-if="form.recentlySuccessful" class="text-sm font-bold text-green-600">
                                        {{ editingRuleId ? 'Regola aggiornata.' : 'Regola creata con successo.' }}
                                    </p>
                                </Transition>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- List of Rules (Interruttori ON/OFF) -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <header class="mb-6 flex flex-col gap-4">
                        <div class="flex justify-between items-center w-full">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Regole Attive / Disattive</h2>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Cerca e clicca sull'interruttore per accendere o spegnere una meccanica in tempo reale.</p>
                            </div>
                        </div>
                    </header>
                    
                    <div v-if="filteredRules.length === 0" class="text-gray-500 dark:text-gray-400">
                        Nessuna regola promozionale inserita per i filtri selezionati.
                    </div>
                    <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                        <li v-for="rule in filteredRules" :key="rule.id" class="py-4 flex items-center justify-between">
                            <div class="flex items-start gap-4">
                                <div class="flex flex-col">
                                    <span class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                        {{ rule.name }} 
                                        <span class="text-xs ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded uppercase font-bold">{{ rule.type }}</span>
                                    </span>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 mt-2 bg-gray-50 dark:bg-gray-900 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                                        <div v-if="rule.type === 'Points'" class="flex items-center space-x-2">
                                            <span class="font-bold">Euros/Point:</span> <span>{{ rule.parameters?.euros_per_point }}</span>
                                        </div>
                                        <div v-if="rule.type === 'Discount'" class="flex items-center space-x-2">
                                            <span class="font-bold">Discount:</span> <span>{{ rule.parameters?.discount_value }} {{ rule.parameters?.discount_type === 'percent' ? '%' : '€' }}</span>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">
                                        Trigger: {{ rule.conditions?.trigger_type }} | 
                                        Stackable: {{ rule.is_stackable ? 'Yes' : 'No' }} | 
                                        Priority: {{ rule.priority }}
                                    </div>
                                    <div class="mt-1 text-xs text-indigo-600 font-medium" v-if="rule.type === 'ProductReward'">
                                        Premio: {{ rule.parameters?.reward_value }} ({{ rule.parameters?.reward_type }})
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" :checked="rule.is_active" @change="toggleRule(rule)" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                    <span class="ml-3 text-sm font-medium" :class="rule.is_active ? 'text-green-600' : 'text-gray-500'">{{ rule.is_active ? 'Attiva' : 'Disattiva' }}</span>
                                </label>
                                
                                <button @click="editRule(rule)" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">
                                    Modifica
                                </button>
                                <button @click="deleteRule(rule)" class="text-red-600 hover:text-red-900 font-medium text-sm">
                                    Elimina
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
