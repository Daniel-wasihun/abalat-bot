import { computed } from 'vue';
import { useLanguageStore } from '@/stores/languageStore';

export const CATEGORY_KEYS: Record<string, string> = {
  'Spiritual Education': 'cat.spiritual',
  'Choir & Hymns':       'cat.choir',
  'Liturgy & Service':   'cat.liturgy',
  'Prayer Request':      'cat.prayer',
  'General Inquiry':     'cat.general',
  'Other':               'cat.other',
};

export const PRIORITY_KEYS: Record<string, string> = {
  'Low':      'priority.low',
  'Medium':   'priority.medium',
  'High':     'priority.high',
  'Critical': 'priority.critical',
};

export const STATUS_KEYS: Record<string, string> = {
  'New':         'status.new',
  'Read':        'status.read',
  'In Progress': 'status.inprogress',
  'Resolved':    'status.resolved',
  'Closed':      'status.closed',
};

export const LANG_KEYS: Record<string, string> = {
  'en': 'lang.en',
  'am': 'lang.am',
  'om': 'lang.om',
};

export function useEnumI18n() {
  const languageStore = useLanguageStore();
  const t = (k: string) => languageStore.translate(k);

  const tCategory = (v: string): string => v ? t(CATEGORY_KEYS[v] ?? v) : '';
  const tPriority = (v: string): string => v ? t(PRIORITY_KEYS[v] ?? v) : '';
  const tStatus   = (v: string): string => v ? t(STATUS_KEYS[v]   ?? v) : '';
  const tLang     = (v: string): string => v ? t(LANG_KEYS[v]     ?? v) : '';

  const categoryOptions = computed(() => [
    { value: 'Spiritual Education', label: t('cat.spiritual') },
    { value: 'Choir & Hymns',       label: t('cat.choir') },
    { value: 'Liturgy & Service',   label: t('cat.liturgy') },
    { value: 'Prayer Request',      label: t('cat.prayer') },
    { value: 'General Inquiry',     label: t('cat.general') },
    { value: 'Other',               label: t('cat.other') },
  ]);

  const priorityOptions = computed(() => [
    { value: 'Low',      label: t('priority.low') },
    { value: 'Medium',   label: t('priority.medium') },
    { value: 'High',     label: t('priority.high') },
    { value: 'Critical', label: t('priority.critical') },
  ]);

  const statusOptions = computed(() => [
    { value: 'New',         label: t('status.new') },
    { value: 'Read',        label: t('status.read') },
    { value: 'In Progress', label: t('status.inprogress') },
    { value: 'Resolved',    label: t('status.resolved') },
    { value: 'Closed',      label: t('status.closed') },
  ]);

  const languageOptions = computed(() => [
    { value: 'en', label: t('lang.en') },
    { value: 'am', label: t('lang.am') },
    { value: 'om', label: t('lang.om') },
  ]);

  return {
    tCategory, tPriority, tStatus, tLang,
    categoryOptions, priorityOptions, statusOptions, languageOptions,
    CATEGORY_KEYS, PRIORITY_KEYS, STATUS_KEYS,
  };
}
