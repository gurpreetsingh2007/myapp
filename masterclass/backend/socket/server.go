// client.go
package main

import (
    "crypto/tls"
    "crypto/x509"
    "flag"
    "fmt"
    "io/ioutil"
    "log"
    "os"
    "time"

    "github.com/gorilla/websocket"
)

func main() {
    // Command?line flags (override paths or URL as needed)
    serverURL := flag.String("url", "wss://myapp.local:5173/ws", "WebSocket URL")
    caCertPath := flag.String("ca", "ca.crt", "CA certificate file (PEM)")
    clientCertPath := flag.String("cert", "client.crt", "Client certificate (PEM)")
    clientKeyPath := flag.String("key", "client.key", "Client private key (PEM)")
    flag.Parse()

    // 1) Load CA certificate
    caCertPEM, err := ioutil.ReadFile(*caCertPath)
    if err != nil {
        log.Fatalf("Failed to read CA file (%s): %v", *caCertPath, err)
    }
    roots := x509.NewCertPool()
    if ok := roots.AppendCertsFromPEM(caCertPEM); !ok {
        log.Fatalf("Failed to append CA certificate")
    }

    // 2) Load client cert and key
    clientCert, err := tls.LoadX509KeyPair(*clientCertPath, *clientKeyPath)
    if err != nil {
        log.Fatalf("Failed to load client certificate/key: %v", err)
    }

    // 3) Configure TLS to use client cert + CA
    tlsConfig := &tls.Config{
        Certificates:       []tls.Certificate{clientCert},
        RootCAs:            roots,
        InsecureSkipVerify: false,              // ensure server cert is verified
        ServerName:         "myapp.local",      // must match server.crt CN/SAN
    }

    // 4) Create a Gorilla WebSocket dialer using this TLS config
    dialer := websocket.Dialer{
        TLSClientConfig: tlsConfig,
        HandshakeTimeout: 10 * time.Second,
    }

    // 5) Dial the WebSocket endpoint
    fmt.Printf("Connecting to %s …\n", *serverURL)
    conn, resp, err := dialer.Dial(*serverURL, nil)
    if err != nil {
        if resp != nil {
            body, _ := ioutil.ReadAll(resp.Body)
            log.Fatalf("Handshake failed: %v\nHTTP Status: %s\nBody: %s", err, resp.Status, string(body))
        }
        log.Fatalf("Dial error: %v", err)
    }
    defer conn.Close()
    fmt.Println("? Connected successfully!")

    // 6) (Optional) Send a test message
    err = conn.WriteMessage(websocket.TextMessage, []byte(`{"hello":"world"}`))
    if err != nil {
        log.Printf("Write error: %v", err)
    }

    // 7) Read incoming messages in a loop
    for {
        msgType, msg, err := conn.ReadMessage()
        if err != nil {
            log.Printf("Read error: %v", err)
            os.Exit(1)
        }
        var kind string
        switch msgType {
        case websocket.TextMessage:
            kind = "TEXT"
        case websocket.BinaryMessage:
            kind = "BINARY"
        default:
            kind = fmt.Sprintf("TYPE %d", msgType)
        }
        fmt.Printf("? [%s] %s\n", kind, string(msg))
    }
}
    