<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { computed, watch } from 'vue';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
    assignableRoles: {
        type: Array,
        required: true,
    },
    brands: {
        type: Array,
        required: true,
    },
    stores: {
        type: Array,
        required: true,
    }
});

const page = usePage();
const currentUserRole = computed(() => page.props.auth.user.role);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: '',
    brand_id: '',
    store_id: '',
});

// Clear brand_id and store_id when role changes
watch(() => form.role, (newRole) => {
    if (newRole !== 'brand_manager' && newRole !== 'store_manager') {
        form.brand_id = '';
        form.store_id = '';
    }
    if (newRole === 'brand_manager') {
        form.store_id = '';
    }
});

const submit = () => {
    form.post(route('users.store'), {
        onSuccess: () => form.reset(),
    });
};

const deleteUser = (id) => {
    if (confirm('Are you sure you want to delete this user?')) {
        useForm({}).delete(route('users.destroy', id));
    }
};
</script>

<template>
    <Head title="Gestione Utenti" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Utenti (Users)
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                
                <!-- Create User Form -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">Create New User</h2>
                        <p class="mt-1 text-sm text-gray-600">Add a new admin or manager to the system.</p>
                    </header>

                    <form @submit.prevent="submit" class="mt-6 space-y-6 max-w-xl">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="password" value="Password" />
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="form.password"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="role" value="Role" />
                            <select
                                id="role"
                                v-model="form.role"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required
                            >
                                <option value="" disabled>Select a role</option>
                                <option v-for="role in assignableRoles" :key="role" :value="role">
                                    {{ role }}
                                </option>
                            </select>
                        </div>

                        <div v-if="form.role === 'brand_manager' && currentUserRole === 'super_admin'">
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

                        <div v-if="form.role === 'store_manager'">
                            <InputLabel for="store_id" value="Store (Negozio)" />
                            <select
                                id="store_id"
                                v-model="form.store_id"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required
                            >
                                <option value="" disabled>Select a store</option>
                                <option v-for="store in stores" :key="store.id" :value="store.id">
                                    {{ store.name }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Create User</PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- List Users -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <header class="mb-4">
                        <h2 class="text-lg font-medium text-gray-900">All Users</h2>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Name</th>
                                    <th scope="col" class="px-6 py-3">Email</th>
                                    <th scope="col" class="px-6 py-3">Role</th>
                                    <th scope="col" class="px-6 py-3">Brand</th>
                                    <th scope="col" class="px-6 py-3">Store</th>
                                    <th scope="col" class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.id" class="border-b bg-white">
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ user.name }}</td>
                                    <td class="px-6 py-4">{{ user.email }}</td>
                                    <td class="px-6 py-4">{{ user.role }}</td>
                                    <td class="px-6 py-4">{{ user.brand_name || '-' }}</td>
                                    <td class="px-6 py-4">{{ user.store_name || '-' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <DangerButton 
                                            @click="deleteUser(user.id)" 
                                            v-if="$page.props.auth.user.id !== user.id">
                                            Delete
                                        </DangerButton>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center">No users found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
