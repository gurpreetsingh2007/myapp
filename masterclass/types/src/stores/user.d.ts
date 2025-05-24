interface UserState {
    name: string | null;
    email: string | null;
}
export declare const useUserStore: import("pinia").StoreDefinition<"user", UserState, {}, {
    setUser(name: string, email: string): void;
    clearUser(): void;
    loadUser(): void;
}>;
export {};
