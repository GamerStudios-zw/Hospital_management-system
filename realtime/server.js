const http = require("http");
const WebSocket = require("ws");

const HTTP_PORT = process.env.HMS_RT_HTTP_PORT || 8090;

const server = http.createServer((req, res) => {
  if (req.method === "POST" && req.url === "/emit") {
    let body = "";
    req.on("data", (chunk) => (body += chunk));
    req.on("end", () => {
      let payload = {};
      try {
        payload = JSON.parse(body || "{}");
      } catch (e) {
        payload = {};
      }
      const message = JSON.stringify({
        event: payload.event || "refresh",
        payload: payload.payload || {}
      });

      wss.clients.forEach((client) => {
        if (client.readyState === WebSocket.OPEN) {
          client.send(message);
        }
      });

      res.writeHead(200, { "Content-Type": "application/json" });
      res.end(JSON.stringify({ status: "ok" }));
    });
    return;
  }

  res.writeHead(200, { "Content-Type": "text/plain" });
  res.end("HMS realtime server");
});

const wss = new WebSocket.Server({ server });
wss.on("connection", (ws) => {
  ws.send(JSON.stringify({ event: "connected" }));
});

server.listen(HTTP_PORT, () => {
  console.log(`HMS realtime server listening on :${HTTP_PORT}`);
});
