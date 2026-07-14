<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const props = defineProps({
    store: Object,
    pwaSettings: Object,
    errors: Object,
    card_identifier: String,
    formFields: { type: Array, default: () => [] },
    disclaimers: { type: Array, default: () => [] },
});

const isPosMode = computed(() => {
    return new URLSearchParams(window.location.search).get('pos_mode') === 'true';
});

const initialData = {
    email: '',
    password: '',
    password_confirmation: '',
    pos_mode: false,
    card_identifier: props.card_identifier || '',
};

props.formFields.forEach(f => {
    if (f.name !== 'email' && f.name !== 'password') {
        initialData[f.name] = '';
    }
});

props.disclaimers.forEach(d => {
    initialData['disclaimer_' + d.id] = false;
});

const form = useForm(initialData);

onMounted(() => {
    form.pos_mode = isPosMode.value;
});

const submit = () => {
    form.post(route('pwa.register.post', {store: props.store.slug}), {
        onSuccess: (page) => {
            if (isPosMode.value && page.props?.flash?.customer) {
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
                    
                    <template v-for="field in formFields" :key="field.name">
                        <div v-if="field.name !== 'email' && field.name !== 'password'">
                            <label :for="field.name" class="block text-sm font-medium" :style="{ color: text }"> {{ field.label }} <span v-if="field.required" class="text-red-500">*</span></label>
                            <div class="mt-1">
                                <input v-model="form[field.name]" :id="field.name" :type="field.type === 'number' ? 'number' : (field.type === 'date' ? 'date' : 'text')" :required="field.required" class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-black">
                            </div>
                            <p v-if="errors[field.name]" class="mt-2 text-sm text-red-600">{{ errors[field.name] }}</p>
                        </div>
                    </template>

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

                    <!-- Disclaimers -->
                    <div class="space-y-4 pt-4 border-t border-gray-200" v-if="disclaimers.length > 0">
                        <div v-for="disclaimer in disclaimers" :key="disclaimer.id" class="flex items-start">
                            <div class="flex items-center h-5">
                                <input v-model="form['disclaimer_' + disclaimer.id]" :id="'disclaimer_' + disclaimer.id" type="checkbox" :required="disclaimer.is_mandatory" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2">
                            </div>
                            <div class="ml-2 text-sm flex flex-col w-full">
                                <label :for="'disclaimer_' + disclaimer.id" class="font-medium" :style="{ color: text }">
                                    {{ disclaimer.text }} <span v-if="disclaimer.is_mandatory" class="text-red-500">*</span>
                                </label>
                                <a v-if="disclaimer.pdf_path" :href="`/storage/${disclaimer.pdf_path}`" target="_blank" class="text-indigo-600 hover:underline mt-1 text-xs">Visualizza Documento</a>
                            </div>
                            <p v-if="errors['disclaimer_' + disclaimer.id]" class="mt-1 text-sm text-red-600">{{ errors['disclaimer_' + disclaimer.id] }}</p>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-transform active:scale-95" :style="{ backgroundColor: primary }">
                            Registrati
                        </button>
                    </div>
                </form>

                <div v-if="!isPosMode" class="mt-6 text-center">
                    <p class="text-sm opacity-80" :style="{ color: text }">
                        Hai già un account?
                        <Link :href="route('pwa.login', {store: store.slug})" class="font-bold hover:underline" :style="{ color: primary }">
                            Accedi
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
