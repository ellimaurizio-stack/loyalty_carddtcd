<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    settings: Object
});

const form = useForm({
    app_name: props.settings?.app_name || 'Loyalty App',
    primary_color: props.settings?.primary_color || '#4f46e5',
    background_color: props.settings?.background_color || '#f3f4f6',
    text_color: props.settings?.text_color || '#111827',
    logo: null,
    background_image: null,
    registration_fields: {
        name: { enabled: props.settings?.registration_fields?.name?.enabled ?? true, required: props.settings?.registration_fields?.name?.required ?? true },
        phone: { enabled: props.settings?.registration_fields?.phone?.enabled ?? false, required: props.settings?.registration_fields?.phone?.required ?? false },
        dob: { enabled: props.settings?.registration_fields?.dob?.enabled ?? false, required: props.settings?.registration_fields?.dob?.required ?? false }
    },
    privacy_policy: props.settings?.privacy_policy || 'Accetto i termini e le condizioni d\'uso.',
});

const logoPreview = ref(props.settings?.logo_path ? `/storage/${props.settings.logo_path}` : null);
const bgPreview = ref(props.settings?.background_image_path ? `/storage/${props.settings.background_image_path}` : null);

const handleLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const handleBgChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.background_image = file;
        bgPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('admin.pwa.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="PWA Editor" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editor App Clienti (PWA)</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row gap-8">
                
                <!-- Editor Form -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex-1">
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome App</label>
                            <input v-model="form.app_name" type="text" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Logo App (quadrato raccomandato)</label>
                            <input @change="handleLogoChange" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-indigo-50 file:text-indigo-700
                                hover:file:bg-indigo-100
                                "/>
                            <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="mt-2 w-full">
                                {{ form.progress.percentage }}%
                            </progress>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Colore Primario</label>
                                <div class="flex items-center mt-1">
                                    <input v-model="form.primary_color" type="color" class="h-10 w-10 border-0 rounded p-0 cursor-pointer">
                                    <input v-model="form.primary_color" type="text" class="ml-2 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sfondo App</label>
                                <div class="flex items-center mt-1">
                                    <input v-model="form.background_color" type="color" class="h-10 w-10 border-0 rounded p-0 cursor-pointer">
                                    <input v-model="form.background_color" type="text" class="ml-2 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Immagine Sfondo</label>
                                <input @change="handleBgChange" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-500
                                    file:mr-2 file:py-1 file:px-2
                                    file:rounded file:border-0
                                    file:text-xs file:font-semibold
                                    file:bg-indigo-50 file:text-indigo-700
                                    hover:file:bg-indigo-100
                                    "/>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Colore Testo</label>
                                <div class="flex items-center mt-1">
                                    <input v-model="form.text_color" type="color" class="h-10 w-10 border-0 rounded p-0 cursor-pointer">
                                    <input v-model="form.text_color" type="text" class="ml-2 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">
                        <h3 class="text-lg font-bold mb-4">Campi di Registrazione (PWA & POS)</h3>
                        
                        <div class="space-y-4">
                            <!-- Name Field -->
                            <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border">
                                <div>
                                    <span class="font-medium text-gray-700">Nome e Cognome</span>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="form.registration_fields.name.enabled" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-600">Mostra</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="form.registration_fields.name.required" :disabled="!form.registration_fields.name.enabled" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 disabled:opacity-50">
                                        <span class="ml-2 text-sm text-gray-600">Obbligatorio</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Phone Field -->
                            <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border">
                                <div>
                                    <span class="font-medium text-gray-700">Telefono</span>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="form.registration_fields.phone.enabled" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-600">Mostra</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="form.registration_fields.phone.required" :disabled="!form.registration_fields.phone.enabled" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 disabled:opacity-50">
                                        <span class="ml-2 text-sm text-gray-600">Obbligatorio</span>
                                    </label>
                                </div>
                            </div>

                            <!-- DOB Field -->
                            <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border">
                                <div>
                                    <span class="font-medium text-gray-700">Data di Nascita</span>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="form.registration_fields.dob.enabled" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-600">Mostra</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="form.registration_fields.dob.required" :disabled="!form.registration_fields.dob.enabled" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 disabled:opacity-50">
                                        <span class="ml-2 text-sm text-gray-600">Obbligatorio</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700">Testo per Privacy Policy</label>
                            <textarea v-model="form.privacy_policy" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Testo da accettare..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">Gli utenti dovranno accettare la privacy con una spunta prima di registrarsi.</p>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Salva Impostazioni
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Live Preview Mockup -->
                <div class="w-full md:w-80 flex justify-center">
                    <div class="mockup-phone relative border-8 border-gray-800 rounded-[3rem] h-[600px] w-[300px] shadow-xl overflow-hidden bg-white bg-cover bg-center" 
                         :style="{ backgroundColor: form.background_color, backgroundImage: bgPreview ? `url(${bgPreview})` : 'none' }">
                        <!-- Top notch simulation -->
                        <div class="absolute top-0 inset-x-0 h-6 bg-gray-800 rounded-b-3xl w-40 mx-auto z-50"></div>
                        
                        <!-- PWA Content -->
                        <div class="flex flex-col h-full w-full relative pt-10 px-4" :style="{ color: form.text_color }">
                            
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center space-x-2">
                                    <img v-if="logoPreview" :src="logoPreview" class="h-8 w-8 rounded-full object-cover">
                                    <div v-else class="h-8 w-8 rounded-full bg-gray-200"></div>
                                    <span class="font-bold text-lg">{{ form.app_name }}</span>
                                </div>
                                <div class="w-8 h-8 rounded-full bg-gray-200 opacity-50 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                      <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Mock Card -->
                            <div class="w-full h-40 rounded-2xl p-4 flex flex-col justify-between shadow-lg relative overflow-hidden" :style="{ backgroundColor: form.primary_color, color: '#ffffff' }">
                                <div class="font-bold tracking-widest text-sm opacity-80">LOYALTY CARD</div>
                                <div class="text-3xl font-black mt-2">1,250 Punti</div>
                                <div class="mt-4 flex justify-between items-end">
                                    <div class="text-xs opacity-70">Mario Rossi</div>
                                    <div class="text-xs font-mono">0001 4452 9988</div>
                                </div>
                                <!-- Decorative circle -->
                                <div class="absolute -bottom-10 -right-10 w-32 h-32 rounded-full bg-white opacity-10"></div>
                            </div>

                            <!-- Mock Rewards -->
                            <div class="mt-8 flex-1">
                                <h3 class="font-bold mb-4 opacity-80">Premi Disponibili</h3>
                                <div class="space-y-3">
                                    <div class="bg-white/50 backdrop-blur-sm rounded-xl p-3 border border-gray-100 shadow-sm flex items-center justify-between">
                                        <div>
                                            <div class="font-bold text-sm">Sconto 10%</div>
                                            <div class="text-xs opacity-60">Usa 500 Punti</div>
                                        </div>
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center" :style="{ backgroundColor: form.primary_color, color: '#fff' }">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="bg-white/50 backdrop-blur-sm rounded-xl p-3 border border-gray-100 shadow-sm flex items-center justify-between opacity-50">
                                        <div>
                                            <div class="font-bold text-sm">Prodotto Omaggio</div>
                                            <div class="text-xs opacity-60">Usa 2000 Punti</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Placeholder Apple/Google Wallet buttons (Feature futura) -->
                            <div class="mt-auto pb-8 space-y-2">
                                <div class="w-full h-12 bg-black rounded-xl flex items-center justify-center space-x-2 text-white opacity-50 cursor-not-allowed">
                                    <svg class="h-5 w-5" viewBox="0 0 384 512" fill="currentColor"><path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/></svg>
                                    <span class="font-semibold">Add to Apple Wallet</span>
                                </div>
                                <div class="w-full h-12 bg-black rounded-xl flex items-center justify-center space-x-2 text-white opacity-50 cursor-not-allowed">
                                    <span class="font-bold">G Pay</span>
                                    <span>|</span>
                                    <span>Aggiungi a Google Wallet</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
