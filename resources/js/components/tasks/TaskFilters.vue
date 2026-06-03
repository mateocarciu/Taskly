<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/composables/useInitials';
import type { Tag, TeamMember } from '@/types';
import {
    Calendar,
    Check,
    Filter,
    Search,
    Tag as TagIcon,
    User,
    X,
} from 'lucide-vue-next';
import { computed, reactive, watch } from 'vue';

interface FilterState {
    search: string;
    assignee_id: number | 'unassigned' | '';
    tag_ids: number[];
    due_date: string;
}

const props = defineProps<{
    filters: {
        search?: string;
        assignee_id?: number | 'unassigned' | '';
        tag_ids?: number[];
        due_date?: string;
    };
    teamMembers: TeamMember[];
    tags: Tag[];
}>();

const emit = defineEmits<{
    change: [filters: FilterState];
}>();

const { getInitials } = useInitials();

// Reactive local state for filters
const localFilters = reactive<FilterState>({
    search: '',
    assignee_id: '',
    tag_ids: [],
    due_date: '',
});

// Watch props to sync changes from URL
watch(
    () => props.filters,
    (newFilters) => {
        localFilters.search = newFilters?.search || '';
        localFilters.assignee_id = newFilters?.assignee_id || '';
        
        // Handle tag_ids correctly, forcing numbers
        if (newFilters?.tag_ids) {
            localFilters.tag_ids = Array.isArray(newFilters.tag_ids)
                ? newFilters.tag_ids.map(Number)
                : [Number(newFilters.tag_ids)];
        } else {
            localFilters.tag_ids = [];
        }
        
        localFilters.due_date = newFilters?.due_date || '';
    },
    { immediate: true, deep: true },
);

// Emits the current filter state
const emitChange = () => {
    emit('change', { ...localFilters });
};

// Debounced search logic
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
const onSearchInput = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        emitChange();
    }, 300);
};

// Assignee selection helper
const selectAssignee = (id: number | 'unassigned' | '') => {
    localFilters.assignee_id = id;
    emitChange();
};

const selectedAssigneeLabel = computed(() => {
    if (localFilters.assignee_id === 'unassigned') return 'Unassigned';
    if (localFilters.assignee_id === '') return 'All assignees';
    const member = props.teamMembers.find(
        (m) => m.id === Number(localFilters.assignee_id),
    );
    return member ? member.name : 'All assignees';
});

const selectedAssigneeMember = computed(() => {
    if (localFilters.assignee_id === '' || localFilters.assignee_id === 'unassigned') {
        return null;
    }
    return props.teamMembers.find((m) => m.id === Number(localFilters.assignee_id)) || null;
});

// Tags selection helper
const isTagSelected = (id: number) => localFilters.tag_ids.includes(id);

const toggleTag = (id: number) => {
    if (isTagSelected(id)) {
        localFilters.tag_ids = localFilters.tag_ids.filter((tid) => tid !== id);
    } else {
        localFilters.tag_ids.push(id);
    }
    emitChange();
};

const selectedTagsLabel = computed(() => {
    if (localFilters.tag_ids.length === 0) return 'All tags';
    if (localFilters.tag_ids.length === 1) {
        const tag = props.tags.find((t) => t.id === localFilters.tag_ids[0]);
        return tag ? tag.name : '1 tag';
    }
    return `${localFilters.tag_ids.length} tags`;
});

// Due date selection helper
const selectDueDate = (val: string) => {
    localFilters.due_date = val;
    emitChange();
};

const isCustomDate = (val: string) => {
    return val !== '' && !['overdue', 'today', 'week', 'none'].includes(val);
};

const dueDateLabel = computed(() => {
    switch (localFilters.due_date) {
        case 'overdue':
            return 'Overdue';
        case 'today':
            return 'Due today';
        case 'week':
            return 'Due this week';
        case 'none':
            return 'No due date';
        case '':
            return 'Any due date';
        default:
            try {
                return new Date(localFilters.due_date).toLocaleDateString('en-US', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                });
            } catch {
                return localFilters.due_date;
            }
    }
});

// Reset all filters
const clearFilters = () => {
    localFilters.search = '';
    localFilters.assignee_id = '';
    localFilters.tag_ids = [];
    localFilters.due_date = '';
    emitChange();
};

const hasActiveFilters = computed(() => {
    return (
        localFilters.search !== '' ||
        localFilters.assignee_id !== '' ||
        localFilters.tag_ids.length > 0 ||
        localFilters.due_date !== ''
    );
});
</script>

<template>
    <div class="flex flex-wrap items-center gap-3">
        <!-- Keyword Search -->
        <div class="relative w-full max-w-xs">
            <Search
                class="absolute top-1/2 left-3 -translate-y-1/2 size-4 text-muted-foreground"
            />
            <Input
                v-model="localFilters.search"
                type="text"
                placeholder="Search tasks..."
                class="pl-9 h-9"
                @input="onSearchInput"
            />
        </div>

        <!-- Assignee Filter -->
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="outline"
                    size="sm"
                    class="h-9 gap-1.5 border-dashed"
                >
                    <User class="size-3.5 text-muted-foreground" />
                    <span>Assignee:</span>
                    <span
                        v-if="selectedAssigneeMember"
                        class="flex items-center gap-1 font-normal text-foreground"
                    >
                        <Avatar class="size-4.5 border border-border">
                            <AvatarFallback class="text-[8px] font-semibold">
                                {{ getInitials(selectedAssigneeMember.name) }}
                            </AvatarFallback>
                        </Avatar>
                        {{ selectedAssigneeLabel }}
                    </span>
                    <span v-else class="font-normal text-muted-foreground">{{
                        selectedAssigneeLabel
                    }}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent class="w-56" align="start">
                <DropdownMenuLabel>Filter by assignee</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    class="cursor-pointer gap-2"
                    @click="selectAssignee('')"
                >
                    <div class="flex size-4.5 items-center justify-center">
                        <Filter class="size-3 text-muted-foreground" />
                    </div>
                    <span>All assignees</span>
                    <Check
                        v-if="localFilters.assignee_id === ''"
                        class="ml-auto size-3.5 text-primary"
                    />
                </DropdownMenuItem>
                <DropdownMenuItem
                    class="cursor-pointer gap-2"
                    @click="selectAssignee('unassigned')"
                >
                    <Avatar class="size-4.5 border border-border">
                        <AvatarFallback class="text-[8px] font-semibold"
                            >--</AvatarFallback
                        >
                    </Avatar>
                    <span>Unassigned</span>
                    <Check
                        v-if="localFilters.assignee_id === 'unassigned'"
                        class="ml-auto size-3.5 text-primary"
                    />
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-for="member in teamMembers"
                    :key="member.id"
                    class="cursor-pointer gap-2"
                    @click="selectAssignee(member.id)"
                >
                    <Avatar class="size-4.5 border border-border">
                        <AvatarFallback class="text-[8px] font-semibold">
                            {{ getInitials(member.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <span class="truncate">{{ member.name }}</span>
                    <Check
                        v-if="Number(localFilters.assignee_id) === member.id"
                        class="ml-auto size-3.5 text-primary"
                    />
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>

        <!-- Tags Filter -->
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="outline"
                    size="sm"
                    class="h-9 gap-1.5 border-dashed"
                >
                    <TagIcon class="size-3.5 text-muted-foreground" />
                    <span>Tags:</span>
                    <span
                        v-if="localFilters.tag_ids.length > 0"
                        class="font-normal text-primary bg-primary/10 rounded-full px-2 py-0.5 text-xs font-semibold"
                    >
                        {{ selectedTagsLabel }}
                    </span>
                    <span v-else class="font-normal text-muted-foreground">{{
                        selectedTagsLabel
                    }}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent class="w-56" align="start">
                <DropdownMenuLabel>Filter by tags</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <div
                    v-if="tags.length === 0"
                    class="px-2 py-4 text-center text-xs text-muted-foreground"
                >
                    No tags available
                </div>
                <template v-else>
                    <DropdownMenuItem
                        v-for="tag in tags"
                        :key="tag.id"
                        class="cursor-pointer gap-2"
                        @click.prevent="toggleTag(tag.id)"
                    >
                        <span
                            class="inline-block size-3 shrink-0 rounded-full"
                            :style="{ backgroundColor: tag.color }"
                        />
                        <span class="truncate">{{ tag.name }}</span>
                        <Check
                            v-if="isTagSelected(tag.id)"
                            class="ml-auto size-3.5 text-primary"
                        />
                    </DropdownMenuItem>
                </template>
            </DropdownMenuContent>
        </DropdownMenu>

        <!-- Due Date Filter -->
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="outline"
                    size="sm"
                    class="h-9 gap-1.5 border-dashed"
                >
                    <Calendar class="size-3.5 text-muted-foreground" />
                    <span>Due:</span>
                    <span
                        v-if="localFilters.due_date !== ''"
                        class="font-normal text-foreground"
                        >{{ dueDateLabel }}</span
                    >
                    <span v-else class="font-normal text-muted-foreground">{{
                        dueDateLabel
                    }}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent class="w-64" align="start">
                <DropdownMenuLabel>Filter by due date</DropdownMenuLabel>
                <DropdownMenuSeparator />
                
                <div class="px-2 py-1 text-xs font-semibold text-muted-foreground">Custom Date</div>
                <div class="px-2 pb-2 pt-0.5">
                    <Input
                        type="date"
                        :model-value="isCustomDate(localFilters.due_date) ? localFilters.due_date : ''"
                        class="h-9"
                        @update:model-value="selectDueDate(String($event))"
                    />
                </div>
                
                <DropdownMenuSeparator />
                
                <div class="px-2 py-1 text-xs font-semibold text-muted-foreground">Shortcuts</div>
                <DropdownMenuItem
                    class="cursor-pointer gap-2"
                    @click="selectDueDate('')"
                >
                    <span>Any due date</span>
                    <Check
                        v-if="localFilters.due_date === ''"
                        class="ml-auto size-3.5 text-primary"
                    />
                </DropdownMenuItem>
                <DropdownMenuItem
                    class="cursor-pointer gap-2 text-destructive focus:text-destructive focus:bg-destructive/10"
                    @click="selectDueDate('overdue')"
                >
                    <span>Overdue</span>
                    <Check
                        v-if="localFilters.due_date === 'overdue'"
                        class="ml-auto size-3.5 text-destructive"
                    />
                </DropdownMenuItem>
                <DropdownMenuItem
                    class="cursor-pointer gap-2"
                    @click="selectDueDate('today')"
                >
                    <span>Due today</span>
                    <Check
                        v-if="localFilters.due_date === 'today'"
                        class="ml-auto size-3.5 text-primary"
                    />
                </DropdownMenuItem>
                <DropdownMenuItem
                    class="cursor-pointer gap-2"
                    @click="selectDueDate('week')"
                >
                    <span>Due this week</span>
                    <Check
                        v-if="localFilters.due_date === 'week'"
                        class="ml-auto size-3.5 text-primary"
                    />
                </DropdownMenuItem>
                <DropdownMenuItem
                    class="cursor-pointer gap-2"
                    @click="selectDueDate('none')"
                >
                    <span>No due date</span>
                    <Check
                        v-if="localFilters.due_date === 'none'"
                        class="ml-auto size-3.5 text-primary"
                    />
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>

        <!-- Clear filters button -->
        <Button
            v-if="hasActiveFilters"
            variant="ghost"
            size="sm"
            class="h-9 px-2.5 text-muted-foreground hover:text-foreground"
            @click="clearFilters"
        >
            Reset
            <X class="ml-1 size-3.5" />
        </Button>
    </div>
</template>
