<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const props = defineProps({
    pwaSettings: Object,
    errors: Object,
    card_identifier: String,
});

const isPosMode = computed(() => {
    return new URLSearchParams(window.location.search).get('pos_mode') === 'true';
});

const form = useForm({
    name: '',
    phone: '',
    dob: '',
    email: '',
    password: '',
    password_confirmation: '',
    privacy: false,
    pos_mode: false,
    card_identifier: props.card_identifier || '',
});

onMounted(() => {
    form.pos_mode = isPosMode.value;
});

const submit = () => {
    form.post(route('pwa.register.post'), {
        onSuccess: (page) => {
            if (isPosMode.value && page.props?.flash?.customer) {
                // In POS mode, we could communicate with the Flutter WebView if needed
                // Currently returning JSON from controller, so Inertia handles it
                if (window.FlutterChannel) {
                    window.FlutterChannel.postMessage(JSON.stringify(page.props.flash.customer));
                }
            }
        }
    });
};

const bg = computed(() => props.pwaSettings?.background_color || '#f3f4f6');
const bgImage = computed(() => props.pwaSettings?.background_image_path ? `url(/storage/${props.pwaSettings.background_image_path})` : 'none');
const primary = computed(() => props.pwaSettings?.primary_color || '#4f46e5');
const text = computed(() => props.pwaSettings?.text_color || '#111827');
const logo = computed(() => props.pwaSettings?.logo_path ? `/storage/${props.pwaSettings.logo_path}` : null);
const appName = computed(() => props.pwaSettings?.app_name || 'Loyalty App');

const fields = computed(() => props.pwaSettings?.registration_fields || {
    name: { enabled: true, required: true },
    phone: { enabled: false, required: false },
    dob: { enabled: false, required: false },
});

const privacyPolicy = computed(() => props.pwaSettings?.privacy_policy || '');
</script>

<template>
    <Head title="Registrati" />
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-cover bg-center bg-no-repeat" :style="{ backgroundColor: bg, backgroundImage: bgImage, color: text }">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <img v-if="logo" :src="logo" alt="Logo" class="mx-auto h-24 w-auto rounded-full object-cover shadow-lg mb-4" />
            <h2 class="mt-6 text-3xl font-extrabold">{{ appName }}</h2>
            <p class="mt-2 text-sm opacity-70">
                <span v-if="!isPosMode">Crea il tuo profilo fedeltà o collega la tua carta esistente</span>
                <span v-else>Registrazione Rapida Cliente</span>
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
            <div class="bg-white/80 backdrop-blur py-8 px-4 shadow-xl sm:rounded-2xl sm:px-10 border border-gray-100">
                <form class="space-y-6" @submit.prevent="submit">
                    
                    <div v-if="fields.name.enabled">
                        <label for="name" class="block text-sm font-medium" :style="{ color: text }"> Nome e Cognome <span v-if="fields.name.required" class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input v-model="form.name" id="name" type="text" :required="fields.name.required" class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-black" placeholder="Mario Rossi">
                        </div>
                        <p v-if="errors.name" class="mt-2 text-sm text-red-600">{{ errors.name }}</p>
                    </div>

                    <div v-if="fields.phone.enabled">
                        <label for="phone" class="block text-sm font-medium" :style="{ color: text }"> Numero di Telefono <span v-if="fields.phone.required" class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input v-model="form.phone" id="phone" type="tel" :required="fields.phone.required" class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-black" placeholder="+39 333 1234567">
                        </div>
                        <p v-if="errors.phone" class="mt-2 text-sm text-red-600">{{ errors.phone }}</p>
                    </div>

                    <div v-if="fields.dob.enabled">
                        <label for="dob" class="block text-sm font-medium" :style="{ color: text }"> Data di Nascita <span v-if="fields.dob.required" class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input v-model="form.dob" id="dob" type="date" :required="fields.dob.required" class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-black">
                        </div>
                        <p v-if="errors.dob" class="mt-2 text-sm text-red-600">{{ errors.dob }}</p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium" :style="{ color: text }"> Indirizzo Email <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input v-model="form.email" id="email" type="email" required class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-black" placeholder="mario.rossi@email.com">
                        </div>
                        <p v-if="!isPosMode" class="text-xs mt-1 opacity-70">Usa la stessa email fornita in negozio se hai già una carta.</p>
                        <p v-if="errors.email" class="mt-2 text-sm text-red-600">{{ errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium" :style="{ color: text }"> Password <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input v-model="form.password" id="password" type="password" required class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-black">
                        </div>
                        <p v-if="errors.password" class="mt-2 text-sm text-red-600">{{ errors.password }}</p>
                    </div>
                    
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium" :style="{ color: text }"> Conferma Password <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input v-model="form.password_confirmation" id="password_confirmation" type="password" required class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-black">
                        </div>
                    </div>

                    <!-- Privacy Policy -->
                    <div v-if="privacyPolicy" class="flex items-start">
                        <div class="flex items-center h-5">
                            <input v-model="form.privacy" id="privacy" type="checkbox" required class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2">
                        </div>
                        <div class="ml-2 text-sm">
                            <label for="privacy" class="font-medium" :style="{ color: text }">{{ privacyPolicy }}</label>
                        </div>
                    </div>
                    <p v-if="errors.privacy" class="mt-1 text-sm text-red-600">{{ errors.privacy }}</p>

                    <div>
                        <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-transform active:scale-95" :style="{ backgroundColor: primary }">
                            Registrati
                        </button>
                    </div>
                </form>

                <div v-if="!isPosMode" class="mt-6 text-center">
                    <p class="text-sm opacity-80" :style="{ color: text }">
                        Hai già un account?
                        <Link :href="route('pwa.login')" class="font-bold hover:underline" :style="{ color: primary }">
                            Accedi
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
