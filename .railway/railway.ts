import { defineRailway, github, postgres, project, service, volume } from "railway/iac";

export default defineRailway(() => {
  const Postgres = postgres("Postgres", { region: "us-west2" });
  Postgres.networking = { privateNetworkEndpoint: "postgres", tcpProxies: { "5432": {} } };
  const postgresVolume = volume("postgres-volume", { alerts: { usage: { "100": {}, "80": {}, "95": {} } }, allowOnlineResize: true, region: "us-west2", sizeMB: 5000 });
  const socialPlaylist = service("social-playlist", {
    // dockerfilePath from CaC: "infra/php/Dockerfile.prod"
    // builder from CaC: "DOCKERFILE"
    source: github("mxstrand/social-playlist", { branch: "main", checkSuites: false }),
    build: { builder: "DOCKERFILE", dockerfilePath: "infra/php/Dockerfile.prod" },
    healthcheck: "/llms.txt",
    healthcheckTimeout: 120,
    replicas: { "us-west2": 1 },
    deploy: { sleepApplication: true },
    domains: ["social-playlist.com", "www.social-playlist.com"],
  });
  const mcpSocialPlaylist = service("mcp.social-playlist", {
    // dockerfilePath from CaC: "Dockerfile"
    // builder from CaC: "DOCKERFILE"
    source: github("mxstrand/social-playlist", { branch: "main", checkSuites: false, rootDirectory: "/mcp" }),
    healthcheck: "/",
    replicas: { "us-west2": 1 },
    domains: ["mcp.social-playlist.com"],
    networking: { privateNetworkEndpoint: "mcpsocial-playlist" },
  });

  return project("social-playlist", {
    variables: { managed: false },
    resources: [Postgres, socialPlaylist, mcpSocialPlaylist, postgresVolume],
  });
});
