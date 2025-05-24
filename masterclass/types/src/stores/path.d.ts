export declare const useBreadcrumbStore: import("pinia").StoreDefinition<"breadcrumb", Pick<{
    breadcrumbs: import("vue").ComputedRef<{
        name: string;
        path: string;
    }[]>;
}, never>, Pick<{
    breadcrumbs: import("vue").ComputedRef<{
        name: string;
        path: string;
    }[]>;
}, "breadcrumbs">, Pick<{
    breadcrumbs: import("vue").ComputedRef<{
        name: string;
        path: string;
    }[]>;
}, never>>;
export declare const Title: import("pinia").StoreDefinition<"title", Pick<{
    t: import("vue").ComputedRef<string>;
}, never>, Pick<{
    t: import("vue").ComputedRef<string>;
}, "t">, Pick<{
    t: import("vue").ComputedRef<string>;
}, never>>;
export declare const Path: import("pinia").StoreDefinition<"infoBlock", Pick<{
    info: import("vue").ComputedRef<{
        service: import("vue-router").LocationQueryValue | import("vue-router").LocationQueryValue[];
        sectionId: string;
        block_id: string;
        store_number: string;
    }>;
}, never>, Pick<{
    info: import("vue").ComputedRef<{
        service: import("vue-router").LocationQueryValue | import("vue-router").LocationQueryValue[];
        sectionId: string;
        block_id: string;
        store_number: string;
    }>;
}, "info">, Pick<{
    info: import("vue").ComputedRef<{
        service: import("vue-router").LocationQueryValue | import("vue-router").LocationQueryValue[];
        sectionId: string;
        block_id: string;
        store_number: string;
    }>;
}, never>>;
