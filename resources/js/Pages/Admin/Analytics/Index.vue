<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import { Bubble } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  PointElement,
  LinearScale,
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, PointElement, LinearScale);

const activeTab = ref('config');

const importForm = useForm({
    file: null
});

const submitImport = () => {
    if (!importForm.file) {
        alert('Seleziona un file CSV prima di importare.');
        return;
    }
    importForm.post(route('admin.analytics.import'), {
        preserveScroll: true,
        onSuccess: () => {
            importForm.reset();
            // Reset also the input file element via DOM if necessary, but v-model/reset usually handles it.
        }
    });
};

const config = ref({
    start_date: '',
    end_date: '',
    seasonality: '',
    source: 'internal'
});

const sliders = ref({
    min_lift: 1.0,
    min_support: 0.01,
    min_confidence: 0.1
});

const tempSliders = ref({
    min_lift: 1.0,
    min_support: 0.01,
    min_confidence: 0.1
});

const filters = ref({
    category: '',
    category_strict: false,
    bundle_type: ''
});

const originalReport = ref(null);
const loading = ref(false);
const showWarning = ref(false);
const warningMessage = ref('');

// Computed filtered basket per i grafici pre-conferma e la lista
const filteredBasket = computed(() => {
    if (!originalReport.value || !originalReport.value.basket) return [];
    
    return originalReport.value.basket.filter(rule => {
        // Filtri Slider (Guardrails)
        if (rule.lift < tempSliders.value.min_lift) return false;
        if (rule.support < tempSliders.value.min_support) return false;
        if (rule.confidence < tempSliders.value.min_confidence) return false;

        // Filtro Bundle Type
        if (filters.value.bundle_type && rule.type !== filters.value.bundle_type) return false;

        // Filtro Categoria
        if (filters.value.category) {
            const antCat = rule.antecedent_category;
            const conCat = rule.consequent_category;
            const cat = filters.value.category;

            if (filters.value.category_strict) {
                if (antCat !== cat || conCat !== cat) return false;
            } else {
                if (antCat !== cat && conCat !== cat) return false;
            }
        }

        return true;
    });
});

const chartData = computed(() => {
    const dataPoints = filteredBasket.value.map(rule => ({
        x: rule.support * 100, // asse x: supporto %
        y: rule.confidence * 100, // asse y: confidenza %
        r: rule.lift * 5, // raggio: lift
        label: `${rule.antecedent} + ${rule.consequent}`,
        type: rule.type
    }));

    return {
        datasets: [
            {
                label: 'Bundle Sicuri',
                backgroundColor: 'rgba(79, 70, 229, 0.6)', // indigo
                data: dataPoints.filter(p => p.type === 'Bundle Sicuri')
            },
            {
                label: 'Associazioni Emergenti',
                backgroundColor: 'rgba(249, 115, 22, 0.6)', // orange
                data: dataPoints.filter(p => p.type === 'Emergenti')
            }
        ]
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        tooltip: {
            callbacks: {
                label: function(context) {
                    const point = context.raw;
                    return `${point.label} | Supp: ${point.x.toFixed(1)}% | Conf: ${point.y.toFixed(1)}% | Lift: ${(point.r / 5).toFixed(2)}`;
                }
            }
        }
    },
    scales: {
        x: {
            title: { display: true, text: 'Supporto (%)' }
        },
        y: {
            title: { display: true, text: 'Confidenza (%)' }
        }
    }
};

const availableCategories = computed(() => {
    if (!originalReport.value || !originalReport.value.basket) return [];
    const cats = new Set();
    originalReport.value.basket.forEach(rule => {
        cats.add(rule.antecedent_category);
        cats.add(rule.consequent_category);
    });
    return Array.from(cats).sort();
});

const generateReport = async () => {
    loading.value = true;
    try {
        const response = await axios.post(route('admin.analytics.generate'), {
            ...config.value
        });
        originalReport.value = response.data;
        activeTab.value = 'report';
        
        // Sincronizza i temp sliders con i base sliders in fase di fetch iniziale
        tempSliders.value.min_lift = sliders.value.min_lift;
        tempSliders.value.min_support = sliders.value.min_support;
        tempSliders.value.min_confidence = sliders.value.min_confidence;

    } catch (error) {
        console.error(error);
        alert('Errore durante la generazione del report.');
    } finally {
        loading.value = false;
    }
};

const handleSliderChange = () => {
    // Il grafico si aggiornerà in tempo reale grazie a computed.
    // L'utente vede l'impatto. Se ha cambiato i valori originari (sliders), accendiamo il warning per salvare.
    if (tempSliders.value.min_lift !== sliders.value.min_lift ||
        tempSliders.value.min_support !== sliders.value.min_support ||
        tempSliders.value.min_confidence !== sliders.value.min_confidence) {
        
        warningMessage.value = `Hai modificato le soglie matematiche del modello. Il grafico si è aggiornato in tempo reale per mostrarti i risultati simulati.`;
        showWarning.value = true;
    }
};

const confirmSliderChange = () => {
    sliders.value.min_lift = tempSliders.value.min_lift;
    sliders.value.min_support = tempSliders.value.min_support;
    sliders.value.min_confidence = tempSliders.value.min_confidence;
    showWarning.value = false;
};

const cancelSliderChange = () => {
    tempSliders.value.min_lift = sliders.value.min_lift;
    tempSliders.value.min_support = sliders.value.min_support;
    tempSliders.value.min_confidence = sliders.value.min_confidence;
    showWarning.value = false;
};

</script>

<template>
    <Head title="Pricing Consultant" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Consulente Quantitativo & Pricing
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- Tabs Navigation -->
                <div class="flex space-x-4 border-b border-gray-200 pb-2">
                    <button @click="activeTab = 'config'" :class="{'border-b-2 border-indigo-600 font-bold': activeTab === 'config'}" class="pb-2 text-gray-700">1. Configurazione</button>
                    <button @click="activeTab = 'glossary'" :class="{'border-b-2 border-indigo-600 font-bold': activeTab === 'glossary'}" class="pb-2 text-gray-700">Glossario Tecnico</button>
                    <button @click="activeTab = 'report'" :disabled="!originalReport" :class="{'border-b-2 border-indigo-600 font-bold': activeTab === 'report', 'opacity-50 cursor-not-allowed': !originalReport}" class="pb-2 text-gray-700">2. Report Strategico</button>
                </div>

                <!-- TAB 1: CONFIGURATION -->
                <div v-show="activeTab === 'config'" class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-bold mb-4">Parametri di Base</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Data Inizio</label>
                            <input type="date" v-model="config.start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Data Fine</label>
                            <input type="date" v-model="config.end_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fattore Stagionalità (Opzionale)</label>
                            <input type="text" placeholder="es. Natale, Pasqua" v-model="config.seasonality" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sorgente Dati Analitica</label>
                            <select v-model="config.source" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100" disabled>
                                <option value="internal">Data Lake (Database + Import Esterni)</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">L'algoritmo gira sempre sull'intero Data Lake unificato.</p>
                        </div>
                    </div>

                    <div class="mt-8 border-t pt-6">
                        <h4 class="font-bold text-gray-800 mb-2">Arricchisci il Data Lake (Importa CSV)</h4>
                        <p class="text-sm text-gray-600 mb-4">Carica scontrini da sistemi esterni per addestrare l'algoritmo su un bacino d'utenza più grande. Le transazioni verranno salvate in modo permanente.</p>
                        
                        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ $page.props.flash.success }}</span>
                        </div>
                        <div v-if="$page.props.flash && $page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ $page.props.flash.error }}</span>
                        </div>

                        <div class="flex flex-col md:flex-row gap-4 items-center bg-gray-50 p-4 rounded-lg border">
                            <a :href="route('admin.analytics.template')" class="text-indigo-600 font-bold hover:underline text-sm flex items-center shrink-0">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Scarica CSV di Esempio
                            </a>
                            <div class="w-full md:w-auto flex-1">
                                <input type="file" @change="e => importForm.file = e.target.files[0]" accept=".csv" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                            </div>
                            <button @click="submitImport" :disabled="importForm.processing" class="shrink-0 bg-gray-800 text-white px-4 py-2 rounded text-sm font-bold hover:bg-gray-700">
                                {{ importForm.processing ? 'Caricamento...' : 'Importa Dati' }}
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button @click="generateReport" :disabled="loading" class="bg-indigo-600 text-white px-6 py-2 rounded font-bold hover:bg-indigo-700">
                            {{ loading ? 'Elaborazione in corso...' : 'Genera Strategia' }}
                        </button>
                    </div>
                </div>

                <!-- TAB: GLOSSARY -->
                <div v-show="activeTab === 'glossary'" class="bg-white p-6 rounded-lg shadow space-y-4">
                    <h3 class="text-lg font-bold">Glossario dei Termini</h3>
                    <p><strong>Elasticità della Domanda:</strong> Misura la sensibilità delle vendite al variare del prezzo. Un prodotto è "elastico" se un piccolo sconto genera un grande aumento di vendite. È "rigido" se un abbassamento di prezzo non aumenta significativamente i volumi.</p>
                    
                    <hr class="my-4">
                    
                    <p class="text-indigo-800 font-bold text-lg">Le 3 Metriche del Market Basket (Apriori)</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-2">
                        <div class="bg-gray-50 p-4 border rounded">
                            <h4 class="font-bold text-md mb-2">1. Supporto (La Popolarità)</h4>
                            <p class="text-sm">Il supporto ti dice <strong>quanto è frequente</strong> una combinazione di prodotti in assoluto su tutti gli scontrini. Ad esempio, se Latte e Biscotti si trovano in 10 scontrini su 100 totali, il supporto è del 10%.<br><br><em>Perché si modifica la soglia?</em><br>Se alzi la soglia di Supporto, escluderai i bundle "di nicchia" o rari, concentrandoti solo sulle accoppiate che vendono massicciamente ogni giorno.</p>
                        </div>
                        <div class="bg-gray-50 p-4 border rounded">
                            <h4 class="font-bold text-md mb-2">2. Confidenza (La Certezza)</h4>
                            <p class="text-sm">La confidenza ti dice <strong>quanto è forte il legame in una sola direzione</strong>. Se metto nel carrello i Biscotti, che probabilità c'è che aggiunga il Latte? Magari è dell'80% (alta confidenza). Ma non vale il viceversa!<br><br><em>Perché si modifica la soglia?</em><br>Se alzi la soglia di Confidenza, il sistema ti proporrà solo le "scommesse sicure" per le tue promozioni, scartando i bundle dove il legame psicologico del cliente è debole.</p>
                        </div>
                    </div>
                    
                    <p class="mt-4"><strong>Lift (Forza Predittiva):</strong> Un Lift = 1 significa acquisto casuale. Un Lift = 3 significa che comprare il prodotto B è 3 volte più probabile in presenza di A rispetto al normale acquisto isolato.</p>
                </div>

                <!-- TAB 2: REPORT & GLASS BOX -->
                <div v-if="activeTab === 'report' && originalReport" class="space-y-6">
                    
                    <!-- FASE 4: Guardrails (Sliders) -->
                    <div class="bg-yellow-50 border border-yellow-200 p-6 rounded-lg">
                        <h4 class="font-bold text-yellow-800 mb-2">Simulazione Soglie (Fase 4)</h4>
                        <p class="text-sm text-yellow-700 mb-4">Intervieni sulle soglie statistiche per raffinare l'algoritmo. I grafici sottostanti si aggiorneranno in <strong>tempo reale</strong> per permetterti di simulare l'impatto.</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium">Minimo Lift: {{ tempSliders.min_lift }}</label>
                                <input type="range" min="0.5" max="5.0" step="0.1" v-model="tempSliders.min_lift" @change="handleSliderChange" class="w-full">
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Minima Confidenza: {{ (tempSliders.min_confidence * 100).toFixed(0) }}%</label>
                                <input type="range" min="0.05" max="1.0" step="0.05" v-model="tempSliders.min_confidence" @change="handleSliderChange" class="w-full">
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Minimo Supporto: {{ (tempSliders.min_support * 100).toFixed(1) }}%</label>
                                <input type="range" min="0.001" max="0.5" step="0.005" v-model="tempSliders.min_support" @change="handleSliderChange" class="w-full">
                            </div>
                        </div>
                    </div>

                    <!-- FASE 2: Grafico a Dispersione (Scatter) e Filtri -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                            <h3 class="text-lg font-bold">Mappa Interattiva Bundle</h3>
                            
                            <!-- Filtri Categoria e Tipo -->
                            <div class="flex flex-wrap items-center gap-4 mt-4 md:mt-0">
                                <select v-model="filters.bundle_type" class="text-sm rounded border-gray-300">
                                    <option value="">Tutti i Bundle</option>
                                    <option value="Bundle Sicuri">Solo Bundle Sicuri</option>
                                    <option value="Emergenti">Solo Emergenti</option>
                                </select>

                                <div class="flex items-center gap-2">
                                    <select v-model="filters.category" class="text-sm rounded border-gray-300">
                                        <option value="">Tutte le Categorie</option>
                                        <option v-for="cat in availableCategories" :key="cat" :value="cat">{{ cat }}</option>
                                    </select>
                                    <label v-if="filters.category" class="flex items-center space-x-2 text-sm text-gray-600">
                                        <input type="checkbox" v-model="filters.category_strict" class="rounded text-indigo-600">
                                        <span>Stringente (Ambo i prodotti)</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="h-80 w-full relative">
                            <Bubble :data="chartData" :options="chartOptions" />
                        </div>
                        <p class="text-xs text-gray-500 mt-4 text-center">La dimensione della bolla rappresenta il valore del <strong>Lift</strong> (Forza dell'associazione).</p>
                    </div>

                    <!-- FASE 2: Elasticità (Proposta Autonoma) -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-bold mb-4">Elasticità del Prezzo (Prodotti da scontare vs rigidi)</h3>
                        <div v-for="(item, idx) in originalReport.elasticity" :key="idx" class="mb-6 border-b pb-4 last:border-0">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-lg">{{ item.name }}</span>
                                <span v-if="item.status === 'calculated'" :class="item.elasticity < -1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-3 py-1 rounded text-sm font-bold">
                                    {{ item.type }} (E = {{ item.elasticity }})
                                </span>
                                <span v-else class="bg-gray-100 text-gray-800 px-3 py-1 rounded text-sm font-bold">Dati Insufficienti</span>
                            </div>
                            <!-- FASE 3: Scatola di Vetro -->
                            <div class="mt-3 bg-gray-50 border-l-4 border-indigo-500 p-4 text-sm text-gray-700 whitespace-pre-line">
                                {{ item.reasoning }}
                            </div>
                        </div>
                    </div>

                    <!-- FASE 2: Market Basket (Tabella Dinamica) -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-bold mb-4">Dettaglio Bundle Filtrati ({{ filteredBasket.length }})</h3>
                        <div v-if="filteredBasket.length === 0" class="text-gray-500">Nessun bundle trovata con i filtri e le soglie attuali.</div>
                        <div v-for="(rule, idx) in filteredBasket" :key="idx" class="mb-6 border-b pb-4 last:border-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="font-bold text-indigo-600">{{ rule.antecedent }} <span class="text-xs text-gray-500 font-normal">({{ rule.antecedent_category }})</span></span>
                                <span>+</span>
                                <span class="font-bold text-green-600">{{ rule.consequent }} <span class="text-xs text-gray-500 font-normal">({{ rule.consequent_category }})</span></span>
                                <span :class="rule.type === 'Bundle Sicuri' ? 'bg-indigo-100 text-indigo-800' : 'bg-orange-100 text-orange-800'" class="ml-auto px-3 py-1 rounded text-sm font-bold">
                                    {{ rule.type }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 mb-3">
                                Lift: <strong>{{ rule.lift }}</strong> | Confidenza: {{ (rule.confidence * 100).toFixed(1) }}% | Supporto: {{ (rule.support * 100).toFixed(1) }}%
                            </div>
                            <!-- FASE 3: Scatola di Vetro -->
                            <div class="bg-gray-50 border-l-4 border-indigo-500 p-4 text-sm text-gray-700 whitespace-pre-line">
                                {{ rule.reasoning }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL SICUREZZA (Fase 4) -->
        <div v-if="showWarning" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full shadow-xl">
                <h3 class="text-lg font-bold text-indigo-600 mb-2">Simulazione Attiva</h3>
                <p class="text-sm text-gray-700 mb-4">{{ warningMessage }}</p>
                <p class="text-sm font-bold mb-6">Vuoi salvare in via definitiva questi nuovi parametri per il tuo modello strategico?</p>
                <div class="flex justify-end space-x-4">
                    <button @click="cancelSliderChange" class="px-4 py-2 text-gray-600 hover:text-gray-900">Annulla Simulazione</button>
                    <button @click="confirmSliderChange" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Applica Modello</button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
