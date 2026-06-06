import { defineConfig } from 'astro/config';

// Static output (default). The build (dist/) is served by the FrankenPHP app at `/`,
// same origin as the API at `/api`, so the client uses relative URLs (no CORS).
export default defineConfig({
  site: 'https://social-playlist.com',
});
