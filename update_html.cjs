const fs = require('fs');
const file = 'resources/js/Pages/Admin/Rules/Index.vue';
let content = fs.readFileSync(file, 'utf8');

// Remove Import Rules Button
content = content.replace(/<div v-if="!editingRuleId">[\s\S]*?<\/div>/, '');

// Remove 1. Impostazioni Generali content, keeping just Name and Type
const generalSettingsRegex = /<h3 class="text-md font-bold mb-4 border-b pb-2">1\. Impostazioni Generali<\/h3>[\s\S]*?<!-- TRIGGER E APPLICAZIONE -->/g;
const newGeneralSettings = `<h3 class="text-md font-bold mb-4 border-b pb-2">1. Impostazioni Generali</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel for="type" value="Template Regola" />
                                        <select v-model="form.type" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm font-bold text-indigo-700">
                                            <option value="Points">1. Motore Punti Base</option>
                                            <option value="Discount">2. Sconto Diretto</option>
                                            <option value="Welcome">3. Bonus Benvenuto (Primo Acquisto)</option>
                                            <option value="Referral">4. Programma Passaparola</option>
                                            <option value="Tiers">5. Soglie VIP (Livelli)</option>
                                            <option value="Cashback">6. Programma Borsellino Virtuale</option>
                                            <option value="Missions">7. Missioni (Gamification)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel for="name" value="Nome Etichetta (Interno)" />
                                        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required />
                                    </div>
                                </div>
                            </div>
                            <!-- TRIGGER E APPLICAZIONE -->`;
content = content.replace(generalSettingsRegex, newGeneralSettings);

// Remove specific_items option from triggers
content = content.replace(/<option v-if="form\.type !== 'Missions'" value="specific_items">Solo acquistando articoli specifici<\/option>/, '');

// Remove Specific Items section
content = content.replace(/<!-- Specific Items -->[\s\S]*?<!-- PRIORITÀ E CUMULABILITÀ -->/, '<!-- PRIORITÀ E CUMULABILITÀ -->');

// Missions Specific Items and Bundle Items removal from Mission options
content = content.replace(/<option value="specific_items">Acquisto di Prodotti Specifici<\/option>/, '');
content = content.replace(/<option value="bundle_items">Acquisto di un Bundle di Prodotti<\/option>/, '');

// Remove mission_products UI
content = content.replace(/<div v-if="\['specific_items', 'bundle_items'\]\.includes\(form\.parameters\.mission_type\)" class="md:col-span-2">[\s\S]*?<\/div>[\s]*<\/div>[\s]*<!-- LISTA REGOLE -->/, '</div>\n<!-- LISTA REGOLE -->');

// List Filters and Headers (Brand, Shop, Loyalty)
const listFiltersRegex = /<div class="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow flex flex-col md:flex-row gap-4">[\s\S]*?<\/div>/;
content = content.replace(listFiltersRegex, '');

// Table headers
content = content.replace(/<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\s*Insegna\s*<\/th>/, '');
content = content.replace(/<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\s*Programma\/Negozi\s*<\/th>/, '');

// Table body
content = content.replace(/<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">\s*{{ rule\.brand\?\.name }}\s*<\/td>/g, '');
content = content.replace(/<td class="px-6 py-4 text-sm text-gray-500">[\s\S]*?<\/td>/, '');

// Bulk Duplicate Checkbox header and row
content = content.replace(/<th scope="col" class="px-6 py-3 text-left w-10">[\s\S]*?<\/th>/, '');
content = content.replace(/<td class="px-6 py-4 whitespace-nowrap">[\s\S]*?<\/td>/, '');

// Bulk Action Button
content = content.replace(/<div class="mt-4" v-if="selectedRules\.length > 0">[\s\S]*?<\/div>/, '');

// Modals removal
content = content.replace(/<!-- Import Modal -->[\s\S]*?<!-- Duplicate Modal -->[\s\S]*?<\/template>/, '</template>');

fs.writeFileSync(file, content);
