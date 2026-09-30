<script setup lang="ts">
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';
import type { ButtonVariants } from '@/components/ui/button';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

type Tone = 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'violet';

/**
 * Icon-only action button. The label is the accessible name and the tooltip;
 * pass `as-child` with a Link or anchor to render a navigation action. The
 * tone tints the button by the meaning of the action.
 */
const props = withDefaults(
    defineProps<{
        label: string;
        tone?: Tone;
        variant?: ButtonVariants['variant'];
        size?: ButtonVariants['size'];
        asChild?: boolean;
        class?: HTMLAttributes['class'];
    }>(),
    { tone: 'neutral', variant: 'outline', size: 'icon-sm', asChild: false },
);

defineOptions({ inheritAttrs: false });

const tones: Record<Tone, string> = {
    neutral: '',
    info: 'border-sky-300 bg-sky-50 text-sky-700 hover:bg-sky-100 hover:text-sky-800 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:bg-sky-500/20 dark:hover:text-sky-200',
    success:
        'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20 dark:hover:text-emerald-200',
    warning:
        'border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 hover:text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20 dark:hover:text-amber-200',
    danger: 'border-red-300 bg-red-50 text-red-700 hover:bg-red-100 hover:text-red-800 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-300 dark:hover:bg-red-500/20 dark:hover:text-red-200',
    violet: 'border-violet-300 bg-violet-50 text-violet-700 hover:bg-violet-100 hover:text-violet-800 dark:border-violet-500/40 dark:bg-violet-500/10 dark:text-violet-300 dark:hover:bg-violet-500/20 dark:hover:text-violet-200',
};

const classes = computed(() => [tones[props.tone], props.class]);
</script>

<template>
    <TooltipProvider :delay-duration="200">
        <Tooltip>
            <TooltipTrigger as-child>
                <Button
                    v-bind="$attrs"
                    :variant="variant"
                    :size="size"
                    :as-child="asChild"
                    :class="classes"
                    :aria-label="label"
                >
                    <slot />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ label }}</TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
