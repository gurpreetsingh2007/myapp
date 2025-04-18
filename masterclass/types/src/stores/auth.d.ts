export declare const useAuthStore: import("pinia").StoreDefinition<"auth", {
    token: string;
}, {}, {
    setToken(newToken: string): void;
    clearToken(): void;
}>;
