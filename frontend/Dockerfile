FROM node:20-alpine AS builder

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

COPY . .

# ビルド時に環境変数を設定
# PHP_BACKEND_URL: サーバーサイドプロキシ用（Next.js rewrites）
# NEXT_PUBLIC_PHP_DIRECT_URL: クライアントサイドから直接アクセス用
ARG PHP_BACKEND_URL=http://backend:8080
ARG NEXT_PUBLIC_PHP_DIRECT_URL=http://localhost:8080
ENV PHP_BACKEND_URL=${PHP_BACKEND_URL}
ENV NEXT_PUBLIC_PHP_DIRECT_URL=${NEXT_PUBLIC_PHP_DIRECT_URL}

RUN npm run build

# 本番ステージ
FROM node:20-alpine AS runner

WORKDIR /app

ENV NODE_ENV=production

COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static
COPY --from=builder /app/public ./public

EXPOSE 3000

CMD ["node", "server.js"]
