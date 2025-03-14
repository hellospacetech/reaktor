<script setup lang="ts">
import { ChevronDownIcon } from '@heroicons/vue/20/solid';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { usePage } from '@inertiajs/vue3';
import type { Organization, User } from '@/types/models';
import { isBillingActivated } from '@/utils/billing';
import { canManageBilling } from '@/utils/permissions';
import { switchOrganization } from '@/utils/useOrganization';

const page = usePage<{
    jetstream: {
        canCreateTeams: boolean;
        hasTeamFeatures: boolean;
        managesProfilePhotos: boolean;
        hasApiFeatures: boolean;
    };
    auth: {
        user: User & {
            all_teams: Organization[];
        };
    };
}>();

const switchToTeam = (organization: Organization) => {
    switchOrganization(organization.id);
};
</script>

<template>
    <Dropdown v-if="page.props.jetstream.hasTeamFeatures" align="bottom" width="60">
        <template #trigger>
            <div
                data-testid="organization_switcher"
                class="flex hover:bg-white/10 cursor-pointer transition px-2 py-1 rounded-lg w-full items-center justify-between font-medium">
                <div class="flex flex-1 space-x-2 items-center w-[calc(100%-30px)]">
                    <div
                        class="rounded sm:rounded-lg bg-[#1F40C3] font-semibold text-xs sm:text-sm flex-shrink-0 text-white size-6 sm:size-7 flex items-center justify-center">
                        <svg class="size-5" viewBox="0 0 390 390" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M34.7129 309.85L64.8535 353.527L194.442 264.091L324.03 353.527L354.211 309.85L194.442 199.636L34.7129 309.85Z"
                                fill="white" />
                            <path
                                d="M194.444 37.5C87.2362 37.5 0 124.691 0 231.884H53.0932C53.0932 153.959 116.519 90.6066 194.444 90.6066C272.368 90.6066 335.794 154 335.794 231.884H388.887C388.887 124.732 301.651 37.5408 194.444 37.5408V37.5Z"
                                fill="white" />
                        </svg>
                    </div>
                    <span class="text-sm flex-1 truncate font-semibold">
                        {{ page.props.auth.user.current_team.name }}
                    </span>
                </div>
                <div class="w-[30px]">
                    <button class="p-1 transition hover:bg-white/10 rounded-full flex items-center w-8 h-8">
                        <ChevronDownIcon class="w-5 sm:w-full mt-[1px]"></ChevronDownIcon>
                    </button>
                </div>
            </div>
        </template>

        <template #content>
            <div class="w-60">
                <!-- Organization Management -->
                <div class="block px-4 py-2 text-xs text-muted">
                    Manage Organization
                </div>

                <!-- Organization Settings -->
                <DropdownLink
:href="route(
                    'teams.show',
                    page.props.auth.user.current_team.id
                )
                    ">
                    Organization Settings
                </DropdownLink>

                <DropdownLink v-if="canManageBilling() && isBillingActivated()" href="/billing">
                    Billing
                </DropdownLink>

                <!-- <DropdownLink
                    v-if="page.props.jetstream.canCreateTeams"
                    :href="route('teams.create')">
                    Create new organization
                </DropdownLink> -->

                <!-- Organization Switcher -->
                <template v-if="page.props.auth.user.all_teams.length > 1">
                    <div class="border-t border-card-background-separator" />

                    <div class="block px-4 py-2 text-xs text-muted">
                        Switch Organizations
                    </div>

                    <template v-for="team in page.props.auth.user.all_teams" :key="team.id">
                        <form @submit.prevent="switchToTeam(team)">
                            <DropdownLink as="button">
                                <div class="flex items-center">
                                    <svg
v-if="
                                        team.id ==
                                        page.props.auth.user.current_team_id
                                    " class="me-2 h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path
stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>

                                    <div>
                                        {{ team.name }}
                                    </div>
                                </div>
                            </DropdownLink>
                        </form>
                    </template>
                </template>
            </div>
        </template>
    </Dropdown>
</template>
