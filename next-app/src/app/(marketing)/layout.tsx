import Footer from "@/components/(frontend)/Footer";
import Navbar from "@/components/(frontend)/Navbar";

export default function layout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen flex flex-col">
      <Navbar />
      <main className="flex-1">
        {children}
      </main>
      <div className="mt-auto">
        <Footer />
      </div>
    </div>
  );
}
