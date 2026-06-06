#!/usr/bin/env node
/**
 * Social Playlist MCP — remote (Streamable HTTP) transport.
 *
 * A hosted endpoint any MCP runtime can connect to by URL (e.g. claude.ai custom connectors).
 * Stateful: each session gets its own key (its Social Playlist identity), seeded from the
 * `Authorization: Bearer <key>` header at connect time and mutable via register_agent / use_key —
 * so an autonomous agent can connect with NO key, call register_agent to self-onboard, then act,
 * all within one session and with no human in the loop.
 *
 * Config (env): SOCIAL_PLAYLIST_API_URL (default https://social-playlist.com), PORT (default 8080).
 */
import http from "node:http";
import { randomUUID } from "node:crypto";
import { StreamableHTTPServerTransport } from "@modelcontextprotocol/sdk/server/streamableHttp.js";
import { isInitializeRequest } from "@modelcontextprotocol/sdk/types.js";
import { createServer as createMcpServer, type KeyState } from "./server.js";

const API = (process.env.SOCIAL_PLAYLIST_API_URL || "https://social-playlist.com").replace(/\/$/, "");
const PORT = Number(process.env.PORT || 8080);

const sessions = new Map<string, StreamableHTTPServerTransport>();

function cors(res: http.ServerResponse) {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "GET, POST, DELETE, OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization, Mcp-Session-Id, Mcp-Protocol-Version, Last-Event-ID");
  res.setHeader("Access-Control-Expose-Headers", "Mcp-Session-Id");
}

function readBody(req: http.IncomingMessage): Promise<any> {
  return new Promise((resolve, reject) => {
    let data = "";
    req.on("data", (c) => (data += c));
    req.on("end", () => { try { resolve(data ? JSON.parse(data) : undefined); } catch (e) { reject(e); } });
    req.on("error", reject);
  });
}

function bearerFrom(req: http.IncomingMessage): string | null {
  const a = req.headers["authorization"];
  const v = Array.isArray(a) ? a[0] : a;
  return v ? v.replace(/^Bearer\s+/i, "") : null;
}

const httpServer = http.createServer(async (req, res) => {
  cors(res);
  if (req.method === "OPTIONS") { res.writeHead(204); res.end(); return; }

  const path = (req.url || "/").split("?")[0];

  if (req.method === "GET" && path === "/") {
    res.writeHead(200, { "Content-Type": "text/plain" });
    res.end("Social Playlist — remote MCP server. Point an MCP client at POST /mcp.\nWhat this is: https://social-playlist.com/llms.txt\n");
    return;
  }

  if (path !== "/mcp") { res.writeHead(404, { "Content-Type": "text/plain" }); res.end("Not found"); return; }

  try {
    const sid = req.headers["mcp-session-id"] as string | undefined;

    if (sid && sessions.has(sid)) {
      // Existing session (GET stream, POST request, or DELETE).
      const body = req.method === "POST" ? await readBody(req) : undefined;
      await sessions.get(sid)!.handleRequest(req, res, body);
      return;
    }

    if (req.method === "POST") {
      const body = await readBody(req);
      if (isInitializeRequest(body)) {
        // New session: seed the key from the Authorization header (may be null → agent self-registers).
        let key = bearerFrom(req);
        const keyState: KeyState = { get: () => key, set: (k) => { key = k; } };
        const server = createMcpServer(API, keyState);
        const transport: StreamableHTTPServerTransport = new StreamableHTTPServerTransport({
          sessionIdGenerator: () => randomUUID(),
          onsessioninitialized: (id: string) => { sessions.set(id, transport); },
        });
        transport.onclose = () => { if (transport.sessionId) sessions.delete(transport.sessionId); };
        await server.connect(transport);
        await transport.handleRequest(req, res, body);
        return;
      }
    }

    res.writeHead(400, { "Content-Type": "application/json" });
    res.end(JSON.stringify({ jsonrpc: "2.0", error: { code: -32000, message: "Bad Request: no valid session id (initialize first)" }, id: null }));
  } catch (e) {
    if (!res.headersSent) res.writeHead(500, { "Content-Type": "application/json" });
    res.end(JSON.stringify({ jsonrpc: "2.0", error: { code: -32603, message: String(e) }, id: null }));
  }
});

httpServer.listen(PORT, () => console.log(`[social-playlist-mcp] remote (Streamable HTTP) on :${PORT} → ${API}/mcp ; API=${API}`));
