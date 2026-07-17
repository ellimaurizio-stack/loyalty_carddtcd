<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BrandSelector from '@/Components/BrandSelector.vue';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    brands: Array,
    currentBrandId: [Number, String],
});

const form = useForm({
    bg_color: props.settings.bg_color || '#FFFFFF',
    header_color: props.settings.header_color || '#3F51B5',
    header_text: props.settings.header_text || 'Cassa Rapida',
    header_text_color: props.settings.header_text_color || '#FFFFFF',
    pay_btn_color: props.settings.pay_btn_color || '#4CAF50',
    pay_btn_text: props.settings.pay_btn_text || 'Paga con NFC',
    pay_btn_text_color: props.settings.pay_btn_text_color || '#FFFFFF',
    cart_icon_color: props.settings.cart_icon_color || '#42A5F5',
    logo: null,
    background_image: null,
});

const submit = () => {
    form.post(route('app-settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="App Cassa Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">App Cassa Settings</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <BrandSelector 
                    :brands="brands" 
                    :currentBrandId="currentBrandId" 
                />

                <div v-if="$page.props.flash.success" class="mb-4 font-medium text-sm text-green-600 bg-green-100 p-4 rounded-md">
                    {{ $page.props.flash.success }}
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Header App</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Testo Header</label>
                                    <input type="text" v-model="form.header_text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Colore Sfondo Header</label>
                                    <input type="color" v-model="form.header_color" class="mt-1 block h-10 w-full">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Colore Testo Header</label>
                                    <input type="color" v-model="form.header_text_color" class="mt-1 block h-10 w-full">
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-200">

                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Sfondo e Logo App</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Colore Sfondo Cassa</label>
                                    <input type="color" v-model="form.bg_color" class="mt-1 block h-10 w-full">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Immagine Sfondo App</label>
                                    <input type="file" @input="form.background_image = $event.target.files[0]" class="mt-1 block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-full file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-indigo-50 file:text-indigo-700
                                        hover:file:bg-indigo-100
                                    ">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Logo App (Alto Centro)</label>
                                    <input type="file" @input="form.logo = $event.target.files[0]" class="mt-1 block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-full file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-indigo-50 file:text-indigo-700
                                        hover:file:bg-indigo-100
                                    ">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Colore Icona Carrello</label>
                                    <input type="color" v-model="form.cart_icon_color" class="mt-1 block h-10 w-full">
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-200">

                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Pulsante Pagamento NFC</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Testo Pulsante Pagamento</label>
                                    <input type="text" v-model="form.pay_btn_text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Colore Sfondo Pulsante</label>
                                    <input type="color" v-model="form.pay_btn_color" class="mt-1 block h-10 w-full">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Colore Testo Pulsante</label>
                                    <input type="color" v-model="form.pay_btn_text_color" class="mt-1 block h-10 w-full">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" :disabled="form.processing">
                                Salva Layout
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
