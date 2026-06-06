# Remote (hosted) Social Playlist MCP server — Streamable HTTP.
# Deploy as its own Railway service (root directory = mcp/). Build context = mcp/.

# ── build ──
FROM node:22-alpine AS build
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund
COPY . .
RUN npm run build

# ── runtime ──
FROM node:22-alpine
WORKDIR /app
ENV NODE_ENV=production
COPY package.json package-lock.json* ./
RUN (npm ci --omit=dev --no-audit --no-fund || npm install --omit=dev --no-audit --no-fund) && npm cache clean --force
COPY --from=build /app/dist ./dist
EXPOSE 8080
# PORT is injected by Railway; http.ts defaults to 8080. SOCIAL_PLAYLIST_API_URL should point at the API.
CMD ["node", "dist/http.js"]
