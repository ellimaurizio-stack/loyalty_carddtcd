<template>
    <div v-if="(brands && brands.length > 0) || (stores && stores.length > 0)" class="mb-6 bg-white shadow sm:rounded-lg p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-700">Contesto Operativo</h3>
            <p class="text-xs text-gray-500">Seleziona l'Insegna e/o il Negozio per visualizzare o sovrascrivere le impostazioni specifiche.</p>
        </div>
        <div class="flex items-center gap-4">
            
            <!-- Insegna Selector (Super Admin) -->
            <div v-if="brands && brands.length > 0" class="w-48">
                <label class="block text-xs font-medium text-gray-500 mb-1">Insegna</label>
                <select
                    v-model="selectedBrand"
                    @change="changeBrand"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                        {{ brand.name }}
                    </option>
                </select>
            </div>

            <!-- Store Selector (Super Admin / Brand Manager) -->
            <div v-if="stores && stores.length > 0" class="w-48">
                <label class="block text-xs font-medium text-gray-500 mb-1">Negozio (Opzionale)</label>
                <select
                    v-model="selectedStore"
                    @change="changeStore"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option :value="null">-- Default (Tutta l'insegna) --</option>
                    <option v-for="store in stores" :key="store.id" :value="store.id">
                        {{ store.name }}
                    </option>
                </select>
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    brands: {
        type: Array,
        default: () => []
    },
    stores: {
        type: Array,
        default: () => []
    },
    currentBrandId: {
        type: [Number, String],
        default: null
    },
    currentStoreId: {
        type: [Number, String],
        default: null
    }
});

const selectedBrand = ref(props.currentBrandId);
const selectedStore = ref(props.currentStoreId);

// Sync se i props cambiano
watch(() => props.currentBrandId, (newVal) => {
    selectedBrand.value = newVal;
});
watch(() => props.currentStoreId, (newVal) => {
    selectedStore.value = newVal;
});

const changeBrand = () => {
    if (!selectedBrand.value) return;
    
    // Quando cambio brand, resetto lo store
    router.get(
        window.location.pathname,
        { brand_id: selectedBrand.value, store_id: null },
        { preserveState: false }
    );
};

const changeStore = () => {
    const params = {};
    if (selectedBrand.value) params.brand_id = selectedBrand.value;
    if (selectedStore.value) params.store_id = selectedStore.value;

    router.get(
        window.location.pathname,
        params,
        { preserveState: false }
    );
};
</script>
