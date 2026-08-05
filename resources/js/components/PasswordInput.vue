<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { useVModel } from '@vueuse/core';
import { Eye, EyeOff } from 'lucide-vue-next';
import { ref, useAttrs, type HTMLAttributes } from 'vue';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        defaultValue?: string | number;
        modelValue?: string | number;
        class?: HTMLAttributes['class'];
    }>(),
    {},
);

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void;
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
});

const attrs = useAttrs() as {
    class?: HTMLAttributes['class'];
    [key: string]: unknown;
};
const { class: className, ...inputAttrs } = attrs;

const showPassword = ref(false);
const inputRef = ref<InstanceType<typeof Input> | null>(null);

defineExpose({
    focus: () => inputRef.value?.$el?.focus(),
    select: () => inputRef.value?.$el?.select(),
});
</script>

<template>
    <div class="relative">
        <Input
            ref="inputRef"
            v-bind="inputAttrs"
            v-model="modelValue"
            :type="showPassword ? 'text' : 'password'"
            :class="cn('pr-10', props.class, className)"
        />

        <Button
            type="button"
            variant="ghost"
            size="icon"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
            :aria-pressed="showPassword"
            :title="showPassword ? 'Hide password' : 'Show password'"
            class="absolute top-1/2 right-1 h-7 w-7 -translate-y-1/2 text-muted-foreground hover:bg-transparent hover:text-foreground"
            tabindex="-1"
            @click="showPassword = !showPassword"
        >
            <EyeOff v-if="showPassword" class="size-4" />
            <Eye v-else class="size-4" />
        </Button>
    </div>
</template>
