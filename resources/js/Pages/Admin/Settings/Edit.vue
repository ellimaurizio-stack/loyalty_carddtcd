<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BrandSelector from '@/Components/BrandSelector.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    program: Object,
    brands: Array,
    currentBrandId: [Number, String],
});

const form = useForm({
    form_fields: props.program.form_fields ? JSON.stringify(props.program.form_fields) : '[]',
    disclaimers: props.program.disclaimers ? JSON.stringify(props.program.disclaimers) : '[]',
    background_color: props.program.background_color || '#ffffff',
    primary_color: props.program.primary_color || '#3f51b5',
    otp_channel: props.program.otp_channel || 'phone',
    otp_channel_label: props.program.otp_channel_label || 'Phone Number',
    text_color: props.program.text_color || '#000000',
    translations: props.program.translations ? JSON.stringify(props.program.translations) : '{}',
    logo: null,
    background_image: null,
});

// Reactivity for dynamic form fields
const formFields = ref(props.program.form_fields || []);
const disclaimers = ref(props.program.disclaimers || []);

const translations = ref(props.program.translations || {
    page_title: 'Join our Loyalty Program!',
    intro_text: 'We noticed you shop here often. Join our loyalty program to earn rewards!',
    button_text: 'Send OTP Code'
});

const logoPreview = ref(props.program.logo_path ? `/storage/${props.program.logo_path}` : null);
const bgPreview = ref(props.program.background_image_path ? `/storage/${props.program.background_image_path}` : null);

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

// Form Fields Management
const addField = () => {
    formFields.value.push({ name: '', label: '', type: 'text', required: false });
};
const removeField = (index) => {
    formFields.value.splice(index, 1);
};

// Disclaimers Management
const addDisclaimer = () => {
    disclaimers.value.push({ id: null, text: '', is_mandatory: true, _pdfFile: null });
};
const removeDisclaimer = (index) => {
    disclaimers.value.splice(index, 1);
};

const handlePdfChange = (e, index) => {
    const file = e.target.files[0];
    if (file) {
        disclaimers.value[index]._pdfFile = file;
    }
};

const submit = () => {
    // Sync refs back to form strings
    form.form_fields = JSON.stringify(formFields.value.map(f => ({
        name: f.name, label: f.label, type: f.type, required: f.required
    })));
    
    form.disclaimers = JSON.stringify(disclaimers.value.map(d => ({
        id: d.id, text: d.text, is_mandatory: d.is_mandatory
    })));

    form.translations = JSON.stringify(translations.value);

    // Inject files into the form object directly for inertia
    disclaimers.value.forEach((d, index) => {
        if (d._pdfFile) {
            form[`disclaimer_pdf_${index}`] = d._pdfFile;
        }
    });

    form.post(route('settings.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            alert('Settings updated!');
        }
    });
};
</script>

<template>
    <Head title="App Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Loyalty App Settings</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <BrandSelector 
                    :brands="brands" 
                    :currentBrandId="currentBrandId" 
                />

                <form @submit.prevent="submit" class="space-y-6">
                    
                    <!-- Dynamic Form Builder Section -->
                    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                        <div class="md:grid md:grid-cols-3 md:gap-6">
                            <div class="md:col-span-1">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">Form Builder</h3>
                                <p class="mt-1 text-sm text-gray-500">Configure the exact fields you want to collect during enrollment.</p>
                                <button type="button" @click="addField" class="mt-4 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                                    + Add Field
                                </button>
                            </div>
                            <div class="mt-5 md:mt-0 md:col-span-2 space-y-4">
                                <div v-for="(field, index) in formFields" :key="index" class="p-4 border rounded-md bg-gray-50 flex items-start gap-4">
                                    <div class="flex-1 grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Internal Name (no spaces)</label>
                                            <input type="text" v-model="field.name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. city">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Display Label</label>
                                            <input type="text" v-model="field.label" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. City">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Type</label>
                                            <select v-model="field.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                <option value="text">Text</option>
                                                <option value="email">Email</option>
                                                <option value="number">Number</option>
                                                <option value="date">Date</option>
                                            </select>
                                        </div>
                                        <div class="flex items-center pt-6">
                                            <input type="checkbox" v-model="field.required" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <label class="ml-2 block text-sm text-gray-900">Required</label>
                                        </div>
                                    </div>
                                    <button type="button" @click="removeField(index)" class="text-red-500 hover:text-red-700 pt-6">
                                        Remove
                                    </button>
                                </div>
                                <p v-if="formFields.length === 0" class="text-sm text-gray-500 italic">No fields configured. The form will be empty.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Disclaimers Section -->
                    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                        <div class="md:grid md:grid-cols-3 md:gap-6">
                            <div class="md:col-span-1">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">Disclaimers & Policies</h3>
                                <p class="mt-1 text-sm text-gray-500">Add multiple checkboxes for privacy policies, terms, or marketing.</p>
                                <button type="button" @click="addDisclaimer" class="mt-4 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                                    + Add Disclaimer
                                </button>
                            </div>
                            <div class="mt-5 md:mt-0 md:col-span-2 space-y-4">
                                <div v-for="(disclaimer, index) in disclaimers" :key="index" class="p-4 border rounded-md bg-gray-50 flex flex-col gap-4">
                                    <div class="flex items-start gap-4">
                                        <div class="flex-1">
                                            <label class="block text-xs font-medium text-gray-700">Disclaimer Text</label>
                                            <textarea v-model="disclaimer.text" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="I accept the terms..."></textarea>
                                        </div>
                                        <div class="flex items-center pt-6">
                                            <input type="checkbox" v-model="disclaimer.is_mandatory" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <label class="ml-2 block text-sm text-gray-900">Mandatory</label>
                                        </div>
                                        <button type="button" @click="removeDisclaimer(index)" class="text-red-500 hover:text-red-700 pt-6">
                                            Remove
                                        </button>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Attach PDF (Optional)</label>
                                        <input type="file" accept="application/pdf" @change="e => handlePdfChange(e, index)" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300">
                                        <a v-if="disclaimer.pdf_path && !disclaimer._pdfFile" :href="`/storage/${disclaimer.pdf_path}`" target="_blank" class="text-xs text-indigo-600 hover:underline mt-1 block">View Current PDF</a>
                                        <span v-if="disclaimer._pdfFile" class="text-xs text-green-600 mt-1 block">New PDF selected: {{ disclaimer._pdfFile.name }}</span>
                                    </div>
                                </div>
                                <p v-if="disclaimers.length === 0" class="text-sm text-gray-500 italic">No disclaimers configured.</p>
                            </div>
                        </div>
                    </div>

                    <!-- OTP Configuration Section -->
                    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                        <div class="md:grid md:grid-cols-3 md:gap-6">
                            <div class="md:col-span-1">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">OTP Configuration</h3>
                                <p class="mt-1 text-sm text-gray-500">Configure how users will receive their verification code.</p>
                            </div>
                            <div class="mt-5 md:mt-0 md:col-span-2 space-y-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">OTP Contact Channel</label>
                                        <select v-model="form.otp_channel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="phone">Phone Number (SMS)</option>
                                            <option value="email">Email</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Field Label</label>
                                        <input type="text" v-model="form.otp_channel_label" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Phone Number">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LP Editor Section -->
                    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                        <div class="md:grid md:grid-cols-3 md:gap-6">
                            <div class="md:col-span-1">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">Landing Page Editor</h3>
                                <p class="mt-1 text-sm text-gray-500">Customize the colors, logo, and background of the app.</p>
                            </div>
                            <div class="mt-5 md:mt-0 md:col-span-2 space-y-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Background Color (Fallback)</label>
                                        <div class="mt-1 flex items-center gap-2">
                                            <input type="color" v-model="form.background_color" class="h-8 w-8 rounded cursor-pointer">
                                            <input type="text" v-model="form.background_color" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Primary Color (Buttons)</label>
                                        <div class="mt-1 flex items-center gap-2">
                                            <input type="color" v-model="form.primary_color" class="h-8 w-8 rounded cursor-pointer">
                                            <input type="text" v-model="form.primary_color" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Text Color</label>
                                        <div class="mt-1 flex items-center gap-2">
                                            <input type="color" v-model="form.text_color" class="h-8 w-8 rounded cursor-pointer">
                                            <input type="text" v-model="form.text_color" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">App Logo</label>
                                        <div class="mt-1 flex items-center gap-4">
                                            <div v-if="logoPreview" class="h-20 w-20 bg-gray-100 border rounded flex items-center justify-center overflow-hidden">
                                                <img :src="logoPreview" class="max-h-full max-w-full object-contain">
                                            </div>
                                            <div v-else class="h-20 w-20 bg-gray-100 border rounded flex items-center justify-center">
                                                <span class="text-xs text-gray-400">No logo</span>
                                            </div>
                                            <input type="file" accept="image/*" @change="handleLogoChange" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Background Image</label>
                                        <div class="mt-1 flex items-center gap-4">
                                            <div v-if="bgPreview" class="h-20 w-20 bg-gray-100 border rounded flex items-center justify-center overflow-hidden">
                                                <img :src="bgPreview" class="max-h-full max-w-full object-cover">
                                            </div>
                                            <div v-else class="h-20 w-20 bg-gray-100 border rounded flex items-center justify-center">
                                                <span class="text-xs text-gray-400">No BG</span>
                                            </div>
                                            <input type="file" accept="image/*" @change="handleBgChange" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- App Texts & Translations Section -->
                    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                        <div class="md:grid md:grid-cols-3 md:gap-6">
                            <div class="md:col-span-1">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">App Texts & Translations</h3>
                                <p class="mt-1 text-sm text-gray-500">Customize the wording used inside the Flutter app.</p>
                            </div>
                            <div class="mt-5 md:mt-0 md:col-span-2 space-y-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Page Title</label>
                                        <input type="text" v-model="translations.page_title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Introductory Text</label>
                                        <textarea v-model="translations.intro_text" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Button Text</label>
                                        <input type="text" v-model="translations.button_text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" :disabled="form.processing" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
