interface Config {
    file_name: string;
    status: string;
    last_modified: string;
    errors: string[];
}
export declare const useConfigStore: import("pinia").StoreDefinition<"config", Pick<{
    configs: import("vue").Ref<{
        file_name: string;
        status: string;
        last_modified: string;
        errors: string[];
    }[], Config[] | {
        file_name: string;
        status: string;
        last_modified: string;
        errors: string[];
    }[]>;
    modifiedFiles: import("vue").Ref<Set<string> & Omit<Set<string>, keyof Set<any>>, Set<string> | (Set<string> & Omit<Set<string>, keyof Set<any>>)>;
    loading: import("vue").Ref<boolean, boolean>;
    error: import("vue").Ref<string | null, string | null>;
    deploying: import("vue").Ref<boolean, boolean>;
    showDeployStatus: import("vue").Ref<boolean, boolean>;
    deploySuccess: import("vue").Ref<boolean, boolean>;
    deployMessage: import("vue").Ref<string, string>;
    pendingCount: import("vue").ComputedRef<number>;
    isModified: (fileName: string) => boolean;
    loadConfigs: () => Promise<void>;
    markModified: (fileName: string) => void;
}, "error" | "configs" | "modifiedFiles" | "loading" | "deploying" | "showDeployStatus" | "deploySuccess" | "deployMessage">, Pick<{
    configs: import("vue").Ref<{
        file_name: string;
        status: string;
        last_modified: string;
        errors: string[];
    }[], Config[] | {
        file_name: string;
        status: string;
        last_modified: string;
        errors: string[];
    }[]>;
    modifiedFiles: import("vue").Ref<Set<string> & Omit<Set<string>, keyof Set<any>>, Set<string> | (Set<string> & Omit<Set<string>, keyof Set<any>>)>;
    loading: import("vue").Ref<boolean, boolean>;
    error: import("vue").Ref<string | null, string | null>;
    deploying: import("vue").Ref<boolean, boolean>;
    showDeployStatus: import("vue").Ref<boolean, boolean>;
    deploySuccess: import("vue").Ref<boolean, boolean>;
    deployMessage: import("vue").Ref<string, string>;
    pendingCount: import("vue").ComputedRef<number>;
    isModified: (fileName: string) => boolean;
    loadConfigs: () => Promise<void>;
    markModified: (fileName: string) => void;
}, "pendingCount">, Pick<{
    configs: import("vue").Ref<{
        file_name: string;
        status: string;
        last_modified: string;
        errors: string[];
    }[], Config[] | {
        file_name: string;
        status: string;
        last_modified: string;
        errors: string[];
    }[]>;
    modifiedFiles: import("vue").Ref<Set<string> & Omit<Set<string>, keyof Set<any>>, Set<string> | (Set<string> & Omit<Set<string>, keyof Set<any>>)>;
    loading: import("vue").Ref<boolean, boolean>;
    error: import("vue").Ref<string | null, string | null>;
    deploying: import("vue").Ref<boolean, boolean>;
    showDeployStatus: import("vue").Ref<boolean, boolean>;
    deploySuccess: import("vue").Ref<boolean, boolean>;
    deployMessage: import("vue").Ref<string, string>;
    pendingCount: import("vue").ComputedRef<number>;
    isModified: (fileName: string) => boolean;
    loadConfigs: () => Promise<void>;
    markModified: (fileName: string) => void;
}, "isModified" | "loadConfigs" | "markModified">>;
export {};
