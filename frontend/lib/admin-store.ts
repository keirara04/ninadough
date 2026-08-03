import { create } from "zustand";
import { persist } from "zustand/middleware";

interface AdminUser {
  name: string;
  email: string;
  role: string;
}

interface AdminState {
  token: string | null;
  user: AdminUser | null;
  setSession: (token: string, user: AdminUser) => void;
  clearSession: () => void;
}

export const useAdminStore = create<AdminState>()(
  persist(
    (set) => ({
      token: null,
      user: null,
      setSession: (token, user) => set({ token, user }),
      clearSession: () => set({ token: null, user: null }),
    }),
    { name: "ninadough-admin" },
  ),
);
