<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import {
    fromDatetimeParts,
    toDateInputValue,
    toTimeInputValue,
} from '@/composables/useDateFormatter';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        label?: string;
        disabled?: boolean;
        error?: string;
    }>(),
    {
        label: '',
        disabled: false,
        error: '',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const dateInput = ref(toDateInputValue(props.modelValue));
const timeInput = ref(toTimeInputValue(props.modelValue));

const syncModel = () => {
    emit(
        'update:modelValue',
        fromDatetimeParts(dateInput.value, timeInput.value),
    );
};

watch(
    () => props.modelValue,
    (value) => {
        const nextDate = toDateInputValue(value);
        const nextTime = toTimeInputValue(value);
        if (nextDate !== dateInput.value) dateInput.value = nextDate;
        if (nextTime !== timeInput.value) timeInput.value = nextTime;
    },
);

const onDateInput = (value: string | number) => {
    dateInput.value = String(value);
    syncModel();
};

const onTimeInput = (value: string | number) => {
    timeInput.value = String(value);
    syncModel();
};

const fieldId = computed(() => props.label.toLowerCase().replace(/\s+/g, '-'));
</script>

<template>
    <Label v-if="label" :for="fieldId" class="text-xs text-muted-foreground">
        {{ label }}
    </Label>
    <div class="flex gap-2">
        <Input
            :id="`${fieldId}-date`"
            type="date"
            class="h-9 flex-1"
            :model-value="dateInput"
            :disabled="disabled"
            @update:model-value="onDateInput"
        />
        <Input
            :id="`${fieldId}-time`"
            type="time"
            step="60"
            class="h-9 w-28 shrink-0"
            :model-value="timeInput"
            :disabled="disabled"
            @update:model-value="onTimeInput"
        />
    </div>
    <InputError :message="error" />
</template>
