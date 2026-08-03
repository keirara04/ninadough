import { CartSheet } from "@/components/CartSheet";
import { Footer } from "@/components/Footer";
import { StorefrontHeader } from "@/components/StorefrontHeader";
import { getPreorderDates } from "@/lib/api";

export async function StorefrontChrome({ children }: { children: React.ReactNode }) {
  const preorderDates = await getPreorderDates().catch(() => []);

  return (
    <div className="flex w-full flex-1 flex-col">
      <StorefrontHeader />
      <main className="flex-1">{children}</main>
      <Footer />
      <CartSheet preorderDates={preorderDates} />
    </div>
  );
}
