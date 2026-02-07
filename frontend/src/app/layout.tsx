import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";
import { ThemeToggle } from "@/components/ThemeToggle";
import { AuthProvider } from "@/components/AuthProvider";
import { HeaderAuth } from "@/components/HeaderAuth";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "LYLY PDF Generator",
  description: "PDF Generator for custom acrylic products",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="ja" className="dark" suppressHydrationWarning>
      <body
        className={`${geistSans.variable} ${geistMono.variable} antialiased min-h-screen`}
      >
        <AuthProvider>
          <div className="min-h-screen grid-bg">
            <header className="border-b bg-background/80 backdrop-blur-sm sticky top-0 z-50">
              <div className="max-w-7xl mx-auto px-6 py-4 flex items-center gap-4">
                <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-sky-500 flex items-center justify-center font-mono font-bold text-background">
                  LY
                </div>
                <div>
                  <h1 className="font-mono font-semibold text-lg">
                    LYLY <span className="text-emerald-500">PDF</span>
                  </h1>
                </div>
                <div className="ml-auto flex items-center gap-3">
                  <span className="text-xs text-muted-foreground font-mono">
                    v2.0 / Next.js
                  </span>
                  <HeaderAuth />
                  <ThemeToggle />
                </div>
              </div>
            </header>
            <main className="max-w-7xl mx-auto p-6">
              {children}
            </main>
          </div>
        </AuthProvider>
      </body>
    </html>
  );
}
