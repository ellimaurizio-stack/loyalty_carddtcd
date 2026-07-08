const fs = require('fs');
const file = 'resources/js/Pages/Admin/Rules/Index.vue';
let content = fs.readFileSync(file, 'utf8');

const newTop = `<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { watch, ref, computed } from 'vue';

const props = defineProps({
    rules: Array,
    program: Object,
});

const editingRuleId = ref(null);

const form = useForm({
    name: '',
    type: 'Points',
    is_active: true,
    priority: 0,
    is_stackable: true,
    conditions: {
        trigger_type: 'always', // always, nth_purchase, date_range, specific_days
        nth_purchase_count: 5,
        nth_purchase_recurrence: 'once', // once, recurring
        date_start: '',
        date_end: '',
        allowed_days: [],
    },
    parameters: {
        euros_per_point: 1,
        min_spend: 0,
        discount_type: 'percent',
        discount_value: 0,
        welcome_reward_type: 'points',
        welcome_reward_value: 0,
        referrer_reward_type: 'points',
        referrer_reward_value: 0,
        referred_reward_type: 'points',
        referred_reward_value: 0,
        tiers: [
            {
                threshold: 100,
                name: 'Livello 1',
                rewards: [
                    { type: 'multiplier', value: 1.5 }
                ]
            }
        ],
        mission_type: 'visits',
        mission_target: 5,
        mission_reward_type: 'points',
        mission_reward_value: 0,
        mission_is_repeatable: false
    }
});

const filteredRules = computed(() => {
    return props.rules;
});

watch(() => form.type, (newType) => {
    if (newType === 'Points') form.name = 'Motore Punti Base';
    else if (newType === 'Discount') form.name = 'Sconto Diretto';
    else if (newType === 'Welcome') form.name = 'Bonus Benvenuto';
    else if (newType === 'Referral') form.name = 'Programma Passaparola';
});

const submit = () => {
    if (editingRuleId.value) {
        form.put(route('promotional-rules.update', editingRuleId.value), {
            preserveScroll: true,
            onSuccess: () => {
                cancelEdit();
            },
        });
    } else {
        form.post(route('promotional-rules.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('name');
            },
        });
    }
};

const editRule = (rule) => {
    editingRuleId.value = rule.id;
    form.name = rule.name;
    form.type = rule.type;
    form.is_active = rule.is_active;
    form.priority = rule.priority;
    form.is_stackable = rule.is_stackable;
    
    if (rule.conditions) {
        form.conditions = { ...form.conditions, ...rule.conditions };
    }
    
    if (rule.parameters) {
        form.parameters = { ...form.parameters, ...rule.parameters };
    }

    setTimeout(() => {
        document.getElementById('rule-form-container')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
};

const cancelEdit = () => {
    editingRuleId.value = null;
    form.reset();
};

const toggleRule = (rule) => {
    const data = {
        name: rule.name,
        type: rule.type,
        is_active: !rule.is_active,
        priority: rule.priority,
        is_stackable: rule.is_stackable,
        conditions: rule.conditions || { trigger_type: 'always' },
        parameters: rule.parameters || {}
    };

    router.put(route('promotional-rules.update', rule.id), data, {
        preserveScroll: true,
    });
};

const deleteRule = (rule) => {
    if (confirm("Vuoi davvero eliminare questa regola?")) {
        router.delete(route('promotional-rules.destroy', rule.id), { preserveScroll: true });
    }
}
</script>
`;

content = content.replace(/<script setup>[\s\S]*?<\/script>/, newTop);

fs.writeFileSync(file, content);
