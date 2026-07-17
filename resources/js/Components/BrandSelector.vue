<template>
    <div v-if="brands && brands.length > 0" class="mb-6 bg-white shadow sm:rounded-lg p-4 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-medium text-gray-700">Contesto Operativo (Super Admin)</h3>
            <p class="text-xs text-gray-500">Seleziona l'Insegna per visualizzare o modificare le sue impostazioni specifiche.</p>
        </div>
        <div class="w-64">
            <select
                v-model="selectedBrand"
                @change="changeBrand"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
                <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                    {{ brand.name }}
                </option>
            </select>
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
    currentBrandId: {
        type: [Number, String],
        default: null
    }
});

const selectedBrand = ref(props.currentBrandId);

// Sync if props change from outside
watch(() => props.currentBrandId, (newVal) => {
    selectedBrand.value = newVal;
});

const changeBrand = () => {
    if (!selectedBrand.value) return;
    
    // Ricarica la pagina corrente passando il parametro brand_id in querystring
    router.get(
        window.location.pathname,
        { brand_id: selectedBrand.value },
        { preserveState: false }
    );
};
</script>
