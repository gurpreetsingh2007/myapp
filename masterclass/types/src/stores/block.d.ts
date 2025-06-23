export declare const useJsonDataStore: import("pinia").StoreDefinition<"jsonData", Pick<{
    isLoading: import("vue").Ref<boolean, boolean>;
    error: import("vue").Ref<string | null, string | null>;
    jsonData: {
        id?: string | undefined;
        json_data?: string | undefined;
        success?: boolean | undefined;
    };
    hasError: import("vue").ComputedRef<boolean>;
    isDataLoaded: import("vue").ComputedRef<boolean>;
    fetchJsonData: (path: string, id: string) => Promise<any>;
    updateJsonData: (id: string, path: string, data: string) => Promise<any>;
}, "error" | "jsonData" | "isLoading">, Pick<{
    isLoading: import("vue").Ref<boolean, boolean>;
    error: import("vue").Ref<string | null, string | null>;
    jsonData: {
        id?: string | undefined;
        json_data?: string | undefined;
        success?: boolean | undefined;
    };
    hasError: import("vue").ComputedRef<boolean>;
    isDataLoaded: import("vue").ComputedRef<boolean>;
    fetchJsonData: (path: string, id: string) => Promise<any>;
    updateJsonData: (id: string, path: string, data: string) => Promise<any>;
}, "hasError" | "isDataLoaded">, Pick<{
    isLoading: import("vue").Ref<boolean, boolean>;
    error: import("vue").Ref<string | null, string | null>;
    jsonData: {
        id?: string | undefined;
        json_data?: string | undefined;
        success?: boolean | undefined;
    };
    hasError: import("vue").ComputedRef<boolean>;
    isDataLoaded: import("vue").ComputedRef<boolean>;
    fetchJsonData: (path: string, id: string) => Promise<any>;
    updateJsonData: (id: string, path: string, data: string) => Promise<any>;
}, "fetchJsonData" | "updateJsonData">>;
