<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { computed } from 'vue';

const props = defineProps({
    stores: {
        type: Array,
        required: true,
    },
    brands: {
        type: Array,
        required: true,
    }
});

const page = usePage();
const isSuperAdmin = computed(() => page.props.auth.user.role === 'super_admin');

const form = useForm({
    brand_id: '',
    name: '',
    slug: '',
    address: '',
    city: '',
    zip_code: '',
    is_active: true,
});

const submit = () => {
    form.post(route('stores.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Stores" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Stores (Negozi)
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Create Store Form -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">Create New Store</h2>
                        <p class="mt-1 text-sm text-gray-600">Add a new store to your brand.</p>
                    </header>

                    <form @submit.prevent="submit" class="mt-6 space-y-6 max-w-xl">
                        <div v-if="isSuperAdmin">
                            <InputLabel for="brand_id" value="Brand (Insegna)" />
                            <select
                                id="brand_id"
                                v-model="form.brand_id"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required
                            >
                                <option value="" disabled>Select a brand</option>
                                <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                    {{ brand.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="name" value="Store Name" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="slug" value="Slug (URL friendly)" />
                            <TextInput
                                id="slug"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.slug"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="address" value="Address" />
                            <TextInput
                                id="address"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.address"
                            />
                        </div>
                        <div>
                            <InputLabel for="city" value="City" />
                            <TextInput
                                id="city"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.city"
                            />
                        </div>
                        <div>
                            <InputLabel for="zip_code" value="Zip Code" />
                            <TextInput
                                id="zip_code"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.zip_code"
                            />
                        </div>
                        <div class="block mt-4">
                            <label class="flex items-center">
                                <Checkbox name="is_active" v-model:checked="form.is_active" />
                                <span class="ms-2 text-sm text-gray-600">Is Active?</span>
                            </label>
                        </div>
                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Save Store</PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- List Stores -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <header class="mb-4">
                        <h2 class="text-lg font-medium text-gray-900">All Stores</h2>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3">ID</th>
                                    <th scope="col" class="px-6 py-3">Brand</th>
                                    <th scope="col" class="px-6 py-3">Name</th>
                                    <th scope="col" class="px-6 py-3">City</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="store in stores" :key="store.id" class="border-b bg-white">
                                    <td class="px-6 py-4">{{ store.id }}</td>
                                    <td class="px-6 py-4">{{ store.brand ? store.brand.name : '-' }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ store.name }}</td>
                                    <td class="px-6 py-4">{{ store.city }}</td>
                                    <td class="px-6 py-4">{{ store.is_active ? 'Active' : 'Inactive' }}</td>
                                </tr>
                                <tr v-if="stores.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center">No stores found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
