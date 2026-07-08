<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref } from 'vue';

const props = defineProps({
    products: Array,
});

const editingProductId = ref(null);

const form = useForm({
    name: '',
    category: '',
    ean_code: '',
    price: 0.00,
    stock: 0,
    image: null,
});

const submit = () => {
    if (editingProductId.value) {
        // Inertia non supporta file in PUT, quindi usiamo POST con _method: 'put'
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(route('products.update', editingProductId.value), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                cancelEdit();
            },
        });
    } else {
        form.post(route('products.store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                form.reset();
            },
        });
    }
};

const editProduct = (product) => {
    editingProductId.value = product.id;
    form.name = product.name;
    form.category = product.category || '';
    form.ean_code = product.ean_code;
    form.price = product.price;
    form.stock = product.stock || 0;
    form.image = null;

    setTimeout(() => {
        document.getElementById('product-form-container')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
};

const cancelEdit = () => {
    editingProductId.value = null;
    form.reset();
};

const deleteProduct = (product) => {
    if (confirm("Vuoi davvero eliminare questo prodotto?")) {
        router.delete(route('products.destroy', product.id), { preserveScroll: true });
    }
}

const updateInlineStock = (product) => {
    router.patch(route('products.update-stock', product.id), {
        stock: product.stock
    }, { preserveScroll: true });
}

const uploadCsv = (event) => {
    const file = event.target.files[0];
    if (file) {
        router.post(route('products.import'), {
            csv_file: file,
        }, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                event.target.value = '';
            }
        });
    }
}

const uploadZip = (event) => {
    const file = event.target.files[0];
    if (file) {
        router.post(route('products.import-zip'), {
            zip_file: file,
        }, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                event.target.value = '';
            }
        });
    }
}
</script>

<template>
    <Head title="Catalogo Prodotti" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Catalogo Prodotti</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div v-if="$page.props.flash?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ $page.props.flash.success }}</span>
                </div>

                <!-- Form -->
                <div id="product-form-container" class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ editingProductId ? 'Modifica Prodotto' : 'Aggiungi Nuovo Prodotto' }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Inserisci il nome, la categoria, il codice EAN (codice a barre) e il prezzo.
                            </p>
                        </header>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                                <div>
                                    <InputLabel for="name" value="Nome Prodotto" />
                                    <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required />
                                </div>
                                <div>
                                    <InputLabel for="category" value="Categoria" />
                                    <TextInput id="category" type="text" class="mt-1 block w-full" v-model="form.category" />
                                </div>
                                <div>
                                    <InputLabel for="ean_code" value="Codice EAN" />
                                    <TextInput id="ean_code" type="text" class="mt-1 block w-full" v-model="form.ean_code" required />
                                </div>
                                <div>
                                    <InputLabel for="price" value="Prezzo (€)" />
                                    <TextInput id="price" type="number" step="0.01" min="0" class="mt-1 block w-full" v-model="form.price" required />
                                </div>
                                <div>
                                    <InputLabel for="stock" value="Giacenza (q.tà)" />
                                    <TextInput id="stock" type="number" step="1" class="mt-1 block w-full" v-model="form.stock" required />
                                </div>
                                <div>
                                    <InputLabel for="image" value="Immagine (Opzionale)" />
                                    <input id="image" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" @input="form.image = $event.target.files[0]" />
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">
                                    {{ editingProductId ? 'Aggiorna Prodotto' : 'Salva Prodotto' }}
                                </PrimaryButton>

                                <button v-if="editingProductId" @click.prevent="cancelEdit" class="text-sm text-gray-600 underline">
                                    Annulla
                                </button>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- List -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <header class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Prodotti a Catalogo</h2>
                        <div class="flex items-center gap-2">
                            <a :href="route('products.csv-template')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 text-sm font-medium">
                                Scarica Template CSV
                            </a>
                            <label class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm font-medium cursor-pointer">
                                Importa CSV
                                <input type="file" class="hidden" accept=".csv" @change="uploadCsv" />
                            </label>
                            <label class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-medium cursor-pointer">
                                Carica ZIP Immagini
                                <input type="file" class="hidden" accept=".zip" @change="uploadZip" />
                            </label>
                        </div>
                    </header>
                    
                    <div v-if="products.length === 0" class="text-gray-500">
                        Nessun prodotto inserito.
                    </div>
                    <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                        <li v-for="product in products" :key="product.id" class="py-4 flex items-center justify-between">
                            <div class="flex items-center flex-1 gap-4">
                                <div v-if="product.image_path" class="w-12 h-12 rounded object-cover bg-gray-100 overflow-hidden shrink-0">
                                    <img :src="'/storage/' + product.image_path" alt="Product Image" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-12 h-12 rounded bg-gray-200 flex items-center justify-center shrink-0">
                                    <span class="text-xs text-gray-500">No img</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-md font-bold text-gray-900 dark:text-gray-100">{{ product.name }} <span v-if="product.category" class="text-sm font-normal text-gray-500">({{ product.category }})</span></span>
                                    <span class="text-sm text-gray-500">EAN: {{ product.ean_code }}</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-6">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-500">Giacenza:</span>
                                    <TextInput type="number" step="1" v-model="product.stock" @change="updateInlineStock(product)" class="w-20 text-center py-1" />
                                </div>
                                <span class="text-lg font-bold text-indigo-600 w-24 text-right">€{{ Number(product.price).toFixed(2) }}</span>
                                <button @click="editProduct(product)" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">Modifica</button>
                                <button @click="deleteProduct(product)" class="text-red-600 hover:text-red-900 font-medium text-sm">Elimina</button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
