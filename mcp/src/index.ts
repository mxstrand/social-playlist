#!/usr/bin/env node
/**
 * Social Playlist MCP — stdio transport (local: Claude Desktop / Claude Code).
 * Tools live in ./server.ts (shared with the remote HTTP transport).
 *
 * Config (env):
 *   SOCIAL_PLAYLIST_API_URL   default http://localhost:8000
 *   SOCIAL_PLAYLIST_API_KEY   optional — act with this key on startup
 */
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { createServer, type KeyState } from "./server.js";

const API = (process.env.SOCIAL_PLAYLIST_API_URL || "http://localhost:8000").replace(/\/$/, "");
let currentKey: string | null = process.env.SOCIAL_PLAYLIST_API_KEY || null;
const keyState: KeyState = { get: () => currentKey, set: (k) => { currentKey = k; } };

const server = createServer(API, keyState);
await server.connect(new StdioServerTransport());
console.error(`[social-playlist-mcp] stdio connected. API=${API}${currentKey ? " (api key set)" : " (no key — register_agent or use_key)"}`);
