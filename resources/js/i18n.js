import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import en from '../../lang/en.json';
import es from '../../lang/es.json';

const dictionaries = { en, es };

/**
 * @param {string} key
 * @param {Record<string, string | number>} [params]
 * @returns {string}
 */
export function translate(locale, key, params = {}) {
    const dict = dictionaries[locale] ?? dictionaries.en;
    const text = dict[key] ?? dictionaries.en[key] ?? key;

    return Object.entries(params).reduce(
        (acc, [name, value]) => acc.replaceAll(`:${name}`, String(value)),
        text,
    );
}

export function useTrans() {
    const page = usePage();
    const locale = computed(() => page.props.locale ?? 'en');

    const t = (key, params) => translate(locale.value, key, params);

    return { t, locale };
}

export const i18n = { en, es };
