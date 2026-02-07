import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  ...(process.env.STANDALONE === '1' ? { output: 'standalone' } : {}),
  async rewrites() {
    return [
      {
        source: '/api/:path*',
        destination: `${process.env.PHP_BACKEND_URL || 'http://localhost:8080'}/api.php?action=:path*`,
      },
    ];
  },
  // Enable Turbopack (empty config to silence warning)
  turbopack: {},
  // Webpack config for react-pdf
  webpack: (config) => {
    // Handle canvas for react-pdf
    config.resolve.alias.canvas = false;
    return config;
  },
};

export default nextConfig;
