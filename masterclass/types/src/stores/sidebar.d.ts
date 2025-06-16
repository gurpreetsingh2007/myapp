export declare const useSidebarStore: import("pinia").StoreDefinition<"sidebar", {
    isOpen: boolean;
}, {}, {
    toggle(): void;
}>;
export declare const useRightSidebarStore: import("pinia").StoreDefinition<"rightsidebar", Pick<{
    isOpen: import("vue").Ref<boolean, boolean>;
    currentImageIndex: import("vue").Ref<number, number>;
    backgroundImageUrl: import("vue").ComputedRef<unknown>;
    toggle: () => void;
    changeBackgroundImage: () => void;
}, "isOpen" | "currentImageIndex">, Pick<{
    isOpen: import("vue").Ref<boolean, boolean>;
    currentImageIndex: import("vue").Ref<number, number>;
    backgroundImageUrl: import("vue").ComputedRef<unknown>;
    toggle: () => void;
    changeBackgroundImage: () => void;
}, "backgroundImageUrl">, Pick<{
    isOpen: import("vue").Ref<boolean, boolean>;
    currentImageIndex: import("vue").Ref<number, number>;
    backgroundImageUrl: import("vue").ComputedRef<unknown>;
    toggle: () => void;
    changeBackgroundImage: () => void;
}, "toggle" | "changeBackgroundImage">>;
