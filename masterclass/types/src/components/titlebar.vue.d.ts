import { RouterLink } from 'vue-router';
import { ArrowLeftIcon, HomeIcon } from '@heroicons/vue/24/outline';
import { useUserStore } from '@/stores/user';
declare const isOpen: import("vue").Ref<boolean, boolean>;
declare const isSearchFocused: import("vue").Ref<boolean, boolean>;
declare const showMobileSearch: import("vue").Ref<boolean, boolean>;
declare const showActionMenu: import("vue").Ref<boolean, boolean>;
declare const isCompactView: import("vue").ComputedRef<boolean>;
declare const currentPageTitle: import("vue").ComputedRef<string>;
declare const breadcrumbs: import("vue").ComputedRef<{
    name: string;
    path: string;
}[]>;
declare function goBack(): void;
declare function logout(): void;
declare function toggleActionMenu(): void;
declare const __VLS_ctx: InstanceType<__VLS_PickNotAny<typeof __VLS_self, new () => {}>>;
declare var __VLS_49: {};
type __VLS_Slots = __VLS_PrettifyGlobal<__VLS_OmitStringIndex<typeof __VLS_ctx.$slots> & {
    actions?: (props: typeof __VLS_49) => any;
}>;
declare const __VLS_self: import("vue").DefineComponent<{}, {
    RouterLink: typeof RouterLink;
    ArrowLeftIcon: typeof ArrowLeftIcon;
    HomeIcon: typeof HomeIcon;
    useUserStore: typeof useUserStore;
    isOpen: typeof isOpen;
    isSearchFocused: typeof isSearchFocused;
    showMobileSearch: typeof showMobileSearch;
    showActionMenu: typeof showActionMenu;
    isCompactView: typeof isCompactView;
    currentPageTitle: typeof currentPageTitle;
    breadcrumbs: typeof breadcrumbs;
    goBack: typeof goBack;
    logout: typeof logout;
    toggleActionMenu: typeof toggleActionMenu;
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
declare const __VLS_component: import("vue").DefineComponent<{}, {}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & {
    new (): {
        $slots: S;
    };
};
